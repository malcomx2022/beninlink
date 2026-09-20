<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S37 — les écrans de profil : un refus d'accès qui s'annonçait comme une panne.
 *
 * Constat relevé en inventoriant la surface web (S28) et laissé ouvert depuis :
 * `admin/profile/{id}` et `merchant/profile/{id}` **comparaient** bien
 * l'identifiant de l'URL à l'utilisateur connecté, puis répondaient
 * **`abort(500)`**. Aucune fuite, donc — et c'est pourquoi ce n'était pas urgent —
 * mais trois conséquences réelles :
 *
 *  - l'opérateur voit une page de panne là où il devrait lire « interdit » ;
 *  - la supervision compte une erreur applicative à chaque tentative ;
 *  - un test ne peut pas distinguer un refus d'un bug, ce qui est exactement ce
 *    que la 5ᵉ passe a payé ailleurs (`Account::update` sur une colonne NOT NULL).
 *
 * En le corrigeant, **deux défauts de plus** sont apparus dans les mêmes fichiers.
 *
 * ### 1. Quatre méthodes sur cinq ne vérifiaient rien
 *
 * | Méthode | Ce que faisait le socle |
 * |---|---|
 * | `view($id)` | comparait, puis `abort(500)` |
 * | `create($id)` | **ignorait** `$id` et servait mon propre formulaire |
 * | `changePassword($id)` | idem |
 * | `update($id)` | écrivait mon profil, puis redirigeait vers `$id` |
 * | `updatePassword($id)` | idem |
 *
 * Pas de fuite là non plus — l'écriture porte toujours sur `auth()->user()->id` —
 * mais l'URL mentait, et le filet d'isolation avait dû les classer « identifiant
 * décoratif » pour cette raison. Elles refusent maintenant, et le motif
 * d'exemption devient une garde vérifiée.
 *
 * ### 2. Une écriture réussie qui finissait sur la page de panne
 *
 * `update()` et `updatePassword()` redirigeaient vers `profile.index` avec
 * l'identifiant **de l'URL**. Modifier son profil depuis
 * `/admin/profile/update/42` enregistrait bien, puis affichait un 500. La
 * destination vient désormais de l'utilisateur connecté.
 *
 * ### 3. Un troisième 500, côté marchand seulement
 *
 * `MerchantProfileRepository::get()` cherche le marchand **par son `user_id`**. Un
 * compte qui n'est pas marchand — un agent, un livreur — franchit le garde sur son
 * propre identifiant, puis la vue déréférence `null`. Ce panneau ne porte aucune
 * garde de type d'utilisateur ; il répond 404.
 *
 * ⚠️ **Rectifié par S41, et laissé ici tel quel.** « Ce panneau ne porte aucune garde
 * de type d'utilisateur » était vrai en écrivant S37, et c'est ce qui justifiait le
 * 404. S41 a posé cette garde : un non-marchand est maintenant arrêté à la frontière
 * du panneau et lit **403**. Le 404 du contrôleur reste — il garde le compte de type
 * marchand dont la ligne `merchants` manque — et les deux cas sont désormais prouvés
 * séparément.
 *
 * ⚠️ **403 et non 404**, à l'écart de la convention du reste du chantier (S23 à
 * S35 répondent 404 hors périmètre). La raison : là, cacher l'**existence** de la
 * ressource d'une autre société fait partie du cloisonnement. Ici l'identifiant est
 * celui d'un compte de la même société, que tout porteur de `user_read` voit déjà
 * dans la liste — il n'y a pas d'existence à cacher, et « interdit » est la réponse
 * exacte.
 */
class ProfileAccessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private User $moi;
    private User $unAutre;
    private User $marchand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();

        $this->moi = $this->compteDe(UserType::ADMIN, 'moi');
        $this->unAutre = $this->compteDe(UserType::ADMIN, 'autre');
        $this->marchand = Merchant::firstOrFail()->user;
    }

    /* ═══════════ le refus, et son code ═════════════════════════════════════ */

    /**
     * @dataProvider ecransDuProfil
     */
    public function test_another_accounts_profile_screen_is_forbidden_not_a_server_error(
        string $methode, string $gabarit
    ): void {
        $this->actingAs($this->moi);

        $reponse = $this->call($methode, self::HOTE . '/' . str_replace('{id}', $this->unAutre->id, $gabarit),
            $this->corps($gabarit));

        $this->assertSame(403, $reponse->getStatusCode(),
            "{$methode} /{$gabarit} devrait répondre 403 : "
            . '500 annonce une panne, 200 ouvrirait l\'écran d\'un autre compte');
    }

    /**
     * Le contrôle négatif : sur **son propre** identifiant, chaque écran répond.
     * Sans lui, `abort(403)` posé trop large passerait inaperçu.
     *
     * @dataProvider ecransDuProfil
     */
    public function test_my_own_profile_screen_still_answers(string $methode, string $gabarit): void
    {
        $compte = str_starts_with($gabarit, 'merchant/') ? $this->marchand : $this->moi;
        $this->actingAs($compte);

        $reponse = $this->call($methode, self::HOTE . '/' . str_replace('{id}', $compte->id, $gabarit),
            $this->corps($gabarit), [], [], ['HTTP_REFERER' => self::HOTE . '/']);

        $this->assertNotSame(403, $reponse->getStatusCode(),
            "{$methode} /{$gabarit} refuse le compte à qui l'écran appartient");
        $this->assertNotSame(500, $reponse->getStatusCode(),
            "{$methode} /{$gabarit} répond 500 sur son propre profil");
    }

    public static function ecransDuProfil(): array
    {
        return [
            'admin profil' => ['GET', 'admin/profile/{id}'],
            'admin modifier' => ['GET', 'admin/profile/update/{id}'],
            'admin mot de passe' => ['GET', 'admin/profile/change-password/{id}'],
            'admin enregistrer' => ['PUT', 'admin/profile/update/{id}'],
            'admin enregistrer mot de passe' => ['PUT', 'admin/profile/update-password/{id}'],
            'marchand profil' => ['GET', 'merchant/profile/{id}'],
            'marchand modifier' => ['GET', 'merchant/profile/update/{id}'],
            'marchand mot de passe' => ['GET', 'merchant/profile/change-password/{id}'],
            'marchand enregistrer' => ['PUT', 'merchant/profile/update/{id}'],
            'marchand enregistrer mot de passe' => ['PUT', 'merchant/profile/update-password/{id}'],
        ];
    }

    /* ═══════════ l'écriture qui finissait sur la page de panne ═════════════ */

    /**
     * Le second défaut, et celui qu'un utilisateur rencontrait pour de vrai : son
     * profil s'enregistrait, puis l'écran suivant était une erreur 500.
     *
     * Le garde le rend désormais inatteignable — la requête est refusée avant — et
     * la destination est écrite depuis l'utilisateur connecté pour qu'il ne
     * revienne pas si le garde était un jour relâché.
     */
    public function test_a_successful_update_lands_on_my_own_profile(): void
    {
        $this->actingAs($this->moi);

        $reponse = $this->call('PUT', self::HOTE . '/admin/profile/update/' . $this->moi->id, [
            'name' => 'Nom change',
            'address' => 'Cotonou',
        ]);

        $this->assertSame(302, $reponse->getStatusCode());
        $this->assertSame(route('profile.index', $this->moi->id), $reponse->headers->get('Location'),
            'après une écriture réussie, la destination doit être MON profil');

        $this->assertSame('Nom change', $this->moi->fresh()->name,
            'le contrôle négatif de ce test : l\'écriture doit vraiment avoir eu lieu');
    }

    /** Et la destination ne se construit plus depuis l'URL. */
    public function test_the_redirect_no_longer_reads_the_url_identifier(): void
    {
        foreach (['ProfileController', 'MerchantProfileController'] as $controleur) {
            $code = $this->codeSeul($controleur);

            $this->assertStringNotContainsString("profile.index', \$id", $code,
                "{$controleur} reconstruit la destination depuis l'URL");
            $this->assertStringNotContainsString("profile.index',\$id", $code,
                "{$controleur} reconstruit la destination depuis l'URL");
        }
    }

    /* ═══════════ le troisième 500, côté marchand ═══════════════════════════ */

    /**
     * `MerchantProfileRepository::get()` cherche le marchand par son `user_id` : un
     * agent franchissait le garde sur son propre identifiant, puis la vue
     * déréférençait `null` — d'où le 404 posé ici en S37.
     *
     * ⚠️ **Mis à jour par S41.** La phrase qui justifiait ce 404 était « le panneau
     * marchand ne porte aucune garde de type d'utilisateur » — et S41 la lui a
     * donnée. Un agent est désormais arrêté à la **frontière du panneau**, avant le
     * contrôleur, et lit **403** : « pas ton panneau » est plus exact que
     * « marchand introuvable », puisque le marchand, lui, existe peut-être très
     * bien. Le 404 du contrôleur n'est pas retiré pour autant — il garde le cas
     * suivant, qui reste atteignable.
     */
    public function test_a_non_merchant_account_is_refused_at_the_panel_boundary(): void
    {
        $this->actingAs($this->moi);

        $reponse = $this->call('GET', self::HOTE . '/merchant/profile/' . $this->moi->id);

        $this->assertSame(403, $reponse->getStatusCode(),
            'un agent doit être arrêté à la frontière du panneau marchand (S41)');
    }

    /**
     * Ce que le 404 de S37 garde ENCORE, une fois la frontière posée : un compte
     * **de type marchand** dont la ligne `merchants` manque — une incohérence de
     * données, pas un intrus. Il franchit la garde de panneau, et c'est bien le
     * contrôleur qui doit répondre « introuvable » plutôt que déréférencer `null`.
     */
    public function test_a_merchant_typed_account_without_a_merchant_row_gets_not_found(): void
    {
        $orphelin = $this->compteDe(UserType::MERCHANT, 'orphelin');
        $this->actingAs($orphelin);

        $reponse = $this->call('GET', self::HOTE . '/merchant/profile/' . $orphelin->id);

        $this->assertSame(404, $reponse->getStatusCode(),
            'un compte marchand sans ligne `merchants` doit lire « introuvable », pas une panne');
    }

    /* ═══════════ le constat inscrit : plus aucun abort(500) ici ════════════ */

    /**
     * Le constat d'origine, sous une forme qui ne peut pas se reperdre : ces deux
     * contrôleurs ne rendent plus jamais un refus en `500`.
     */
    public function test_neither_profile_controller_aborts_with_a_server_error(): void
    {
        foreach (['ProfileController', 'MerchantProfileController'] as $controleur) {
            // ⚠️ Sur le texte brut, ce test échouait — en trouvant `abort(500)` dans
            // le tableau de MA PROPRE documentation, celui qui décrit ce que faisait
            // le socle. Un test qui lit un fichier doit lire son CODE.
            $this->assertStringNotContainsString('abort(500)', $this->codeSeul($controleur),
                "{$controleur} annonce encore un refus d'accès comme une panne serveur");
        }
    }

    /** Le code exécutable d'un contrôleur, commentaires et docblocs retirés. */
    private function codeSeul(string $controleur): string
    {
        $jetons = token_get_all(file_get_contents(app_path("Http/Controllers/Backend/{$controleur}.php")));

        return collect($jetons)
            ->reject(fn ($jeton) => is_array($jeton)
                && in_array($jeton[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn ($jeton) => is_array($jeton) ? $jeton[1] : $jeton)
            ->implode('');
    }

    /**
     * Et le pendant, côté filet : ces dix routes étaient **exemptées**, avec le
     * motif « identifiant décoratif : la méthode lit `auth()->user()->id` ». Le
     * motif était exact, et il reposait sur la seule lecture du code.
     *
     * Depuis S37 le paramètre est **vérifié** : un identifiant qui n'est pas le
     * sien répond 403, et ce test le prouve par HTTP. Elles passent donc
     * d'**exemptées** à **prouvées** — un constat remplacé par une garde.
     */
    public function test_the_ten_profile_routes_are_now_proven_not_exempted(): void
    {
        $filet = file_get_contents(base_path('tests/Feature/WebIsolationCoverageTest.php'));

        foreach (self::ecransDuProfil() as [$methode, $gabarit]) {
            $declaration = $methode . ' ' . $gabarit;

            $this->assertStringContainsString(
                "'{$declaration}' => ProfileAccessTest::class",
                $filet,
                "{$declaration} devrait être inscrite comme prouvée dans le filet",
            );
        }

        $this->assertStringNotContainsString("identifiant décoratif", $filet,
            'le motif « identifiant décoratif » n\'a plus lieu d\'être : les dix routes sont gardées');
    }

    /* ═════════════════════════════ fixtures ════════════════════════════════ */

    private function compteDe(int $type, string $suffixe): User
    {
        $compte = new User();
        $compte->company_id = settings()->id;
        $compte->name = 'Compte ' . $suffixe;
        $compte->email = 'compte.s37.' . $suffixe . '@example.test';
        $compte->mobile = '00229970' . rand(100000, 999999);
        $compte->password = bcrypt('secret');
        $compte->user_type = $type;
        $compte->permissions = ['dashboard_read'];
        $compte->save();

        return $compte;
    }

    /** Le corps minimal qu'attend chaque écriture, pour ne pas tomber sur la validation. */
    private function corps(string $gabarit): array
    {
        if (str_contains($gabarit, 'update-password')) {
            return ['old_password' => 'secret', 'new_password' => 'nouveau-secret',
                'confirm_password' => 'nouveau-secret'];
        }
        if (str_contains($gabarit, 'update')) {
            return ['name' => 'Nom', 'address' => 'Cotonou'];
        }

        return [];
    }
}
