<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\ActiveLogController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le journal d'activité — trois défauts dans huit lignes de contrôleur.
 *
 * Relevés en enveloppant le tableau de sa vue dans un `table-responsive`
 * (§19.6 de `docs/guides/charte-web/`). Aucun n'était de l'ergonomie :
 *
 *   1. `view($id)` faisait `Activity::find($id)` **sans aucun périmètre**, alors
 *      qu'`index()` juste au-dessus scope par la société du causeur. Un
 *      opérateur de la société A lisait le détail d'une activité de la société B
 *      en changeant l'identifiant dans l'URL — champ par champ, ancienne valeur
 *      et nouvelle. Famille **S7**.
 *   2. Sa route ne portait **aucune permission**, quand `logs.index` porte
 *      `hasPermission:log_read`. Voir la liste et voir un détail sont le même
 *      droit.
 *   3. La vue rendait les valeurs avec `{!! !!}`. Ce sont des saisies
 *      d'utilisateurs, et la vue est injectée en `.html()` dans une fenêtre
 *      modale : un marchand qui nomme sa boutique avec une balise `<script>`
 *      s'exécutait dans le navigateur de l'opérateur. **XSS stocké.**
 *
 * ⚠️ Et corriger le troisième sans précaution en aurait créé un quatrième : voir
 * `test_an_array_value_is_rendered_instead_of_crashing`.
 *
 * Les routes du back-office ne sont montées qu'avec un domaine de locataire,
 * hors de portée d'un test : on exerce donc le contrôleur et la vue directement,
 * et on lit la déclaration de la route. C'est la méthode d'
 * `OnlinePayoutModuleDisabledTest`.
 */
class ActivityLogAccessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    /* ───────────────────────── 1. Le périmètre société ───────────────────── */

    /** Le détail d'une activité de MA société s'ouvre. */
    public function test_the_detail_of_my_own_company_opens(): void
    {
        $activite = $this->activiteDe($this->agentDe(settings()->id));

        $reponse = app(ActiveLogController::class)->view($activite->id);

        $this->assertSame('backend.log.view', $reponse->name());
        $this->assertSame($activite->id, $reponse->getData()['logDetails']->id);
    }

    /**
     * Celui d'une AUTRE société ne s'ouvre pas. C'est le défaut S7 : les
     * utilisateurs semés appartiennent à la société 2, et `settings()` vaut la
     * société 1 dans un test — la situation est donc exactement celle d'un
     * opérateur qui change l'identifiant dans l'URL.
     */
    public function test_the_detail_of_another_company_is_out_of_reach(): void
    {
        $autre = User::where('company_id', 2)->firstOrFail();
        $activite = $this->activiteDe($autre);

        $this->expectException(NotFoundHttpException::class);
        app(ActiveLogController::class)->view($activite->id);
    }

    /** Un identifiant qui n'existe pas ne révèle pas son absence autrement. */
    public function test_an_unknown_identifier_is_a_not_found_too(): void
    {
        $this->expectException(NotFoundHttpException::class);
        app(ActiveLogController::class)->view(999_999);
    }

    /**
     * Le détail a **exactement** le périmètre de la liste. Une activité sans
     * causeur rattaché à une société — une commande console, par exemple —
     * n'apparaît dans aucune des deux. C'est une règle du socle pour `index()` ;
     * ce lot l'aligne, il ne la change pas.
     */
    public function test_the_detail_has_exactly_the_same_scope_as_the_list(): void
    {
        $sansSociete = User::whereNull('company_id')->firstOrFail();
        $activite = $this->activiteDe($sansSociete);

        // Absente de la liste…
        $listees = Activity::whereHas('causer', fn ($q) => $q->where('company_id', settings()->id))
            ->pluck('id');
        $this->assertNotContains($activite->id, $listees->all());

        // …donc impossible à ouvrir.
        $this->expectException(NotFoundHttpException::class);
        app(ActiveLogController::class)->view($activite->id);
    }

    /* ─────────────────────────── 2. La permission ────────────────────────── */

    /** La route du détail porte la même permission que celle de la liste. */
    public function test_the_detail_route_requires_the_same_permission_as_the_list(): void
    {
        $source = file_get_contents(base_path('routes/web.php'));

        foreach (["'index'", "'view'"] as $methode) {
            $position = strpos($source, "[ActiveLogController::class, {$methode}]");
            $this->assertNotFalse($position, "route introuvable : {$methode}");

            $ligne = substr($source, $position, strpos($source, "\n", $position) - $position);
            $this->assertStringContainsString(
                "hasPermission:log_read",
                $ligne,
                "la route {$methode} du journal n'exige aucune permission",
            );
        }
    }

    /* ──────────────────────────── 3. L'échappement ───────────────────────── */

    /**
     * Une balise stockée dans une valeur journalisée s'affiche, elle ne
     * s'exécute pas. Le journal doit montrer **ce qui a été enregistré** : la
     * balise apparaît donc en clair, échappée.
     */
    public function test_a_script_stored_in_a_logged_value_is_shown_not_executed(): void
    {
        $charge = '<script>alert(1)</script>';
        $activite = $this->activiteDe($this->agentDe(settings()->id), [
            'attributes' => ['business_name' => $charge],
            'old' => ['business_name' => 'Boutique Lafia'],
        ]);

        $rendu = view('backend.log.view', ['logDetails' => $activite])->render();

        $this->assertStringNotContainsString($charge, $rendu);
        $this->assertStringContainsString('&lt;script&gt;', $rendu);
    }

    /** Y compris dans l'ancienne valeur, qui passe par un autre chemin. */
    public function test_the_old_value_is_escaped_too(): void
    {
        $charge = '<img src=x onerror=alert(1)>';
        $activite = $this->activiteDe($this->agentDe(settings()->id), [
            'attributes' => ['address' => 'Akpakpa'],
            'old' => ['address' => $charge],
        ]);

        $rendu = view('backend.log.view', ['logDetails' => $activite])->render();

        // Ce qui compte est que le CHEVRON soit échappé : sans lui, il n'y a pas
        // de balise, donc pas d'attribut `onerror` à déclencher. Le texte
        // « onerror=alert(1) » reste visible, et c'est voulu — le journal montre
        // ce qui a été enregistré.
        $this->assertStringNotContainsString('<img', $rendu);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $rendu);
    }

    /** Et la vue ne contient plus une seule sortie non échappée. */
    public function test_the_view_holds_no_unescaped_output_any_more(): void
    {
        $vue = file_get_contents(resource_path('views/backend/log/view.blade.php'));

        $this->assertStringNotContainsString('{!!', $vue);
    }

    /**
     * ⚠️ Le quatrième défaut, que corriger le troisième aurait créé.
     *
     * `properties['attributes']` reprend les attributs du modèle tels qu'il les
     * porte. `User` et `Role` déclarent `'permissions' => 'array'`, et les deux
     * sont journalisés : une modification de rôle journalise donc un **tableau**.
     *
     * Avec `{!! $value !!}`, cela donnait un avertissement et le mot « Array ».
     * Avec `{{ $value }}` posé naïvement, `htmlspecialchars()` refuse un tableau
     * en PHP 8 : **erreur 500**. D'où `logValue()`, qui rend une chaîne.
     */
    public function test_an_array_value_is_rendered_instead_of_crashing(): void
    {
        $activite = $this->activiteDe($this->agentDe(settings()->id), [
            'attributes' => ['permissions' => ['parcel_read', 'parcel_create']],
            'old' => ['permissions' => ['parcel_read']],
        ]);

        $rendu = view('backend.log.view', ['logDetails' => $activite])->render();

        $this->assertStringContainsString('parcel_create', $rendu);
        $this->assertStringNotContainsString('Array', $rendu);
    }

    /**
     * La branche « suppression » de la vue — celle qui n'a que des anciennes
     * valeurs — échappait déjà, donc **plantait déjà** sur un tableau. Elle
     * passe aussi par `logValue()`.
     */
    public function test_the_deletion_branch_survives_an_array_too(): void
    {
        $activite = $this->activiteDe($this->agentDe(settings()->id), [
            'old' => ['permissions' => ['parcel_read'], 'name' => 'Agence Cotonou'],
        ]);

        $rendu = view('backend.log.view', ['logDetails' => $activite])->render();

        $this->assertStringContainsString('parcel_read', $rendu);
        $this->assertStringContainsString('Agence Cotonou', $rendu);
    }

    /** La forme que `logValue()` garantit à la vue. */
    public function test_log_value_always_returns_a_string(): void
    {
        $this->assertSame('', logValue(null));
        $this->assertSame('', logValue(''));
        $this->assertSame('1', logValue(true));
        $this->assertSame('0', logValue(false));
        $this->assertSame('12500', logValue(12500));
        $this->assertSame('Boutique Lafia', logValue('Boutique Lafia'));
        $this->assertSame('["a","b"]', logValue(['a', 'b']));
        $this->assertSame('{"vat":18}', logValue(['vat' => 18]));

        // Pas d'échappement Unicode ni de barres obliques : le journal se lit.
        $this->assertSame('["Cotonou-Périphérie"]', logValue(['Cotonou-Périphérie']));
        $this->assertSame('["https://beninlink.bj"]', logValue(['https://beninlink.bj']));
    }

    /* ────────────────────────────── Outils ──────────────────────────────── */

    /** Un agent rattaché à la société donnée. */
    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent Test';
        $agent->email = "agent{$societe}@example.test";
        $agent->mobile = '0022997000100';
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    /** Une activité causée par cet utilisateur. */
    private function activiteDe(User $causeur, ?array $proprietes = null): Activity
    {
        activity()
            ->causedBy($causeur)
            ->withProperties($proprietes ?? [
                'attributes' => ['name' => 'Après'],
                'old' => ['name' => 'Avant'],
            ])
            ->log('updated');

        return Activity::latest('id')->firstOrFail();
    }
}
