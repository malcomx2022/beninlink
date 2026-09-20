<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use App\Models\Backend\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as Router;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S36 — la **garde d'accès** des routes que l'inventaire avait laissées nues.
 *
 * C'était la dernière décision ouverte du chantier de sécurité, et elle était
 * ouverte pour une bonne raison : poser `hasPermission:x` **retire l'accès** à
 * tout rôle qui ne porte pas `x`. Il fallait donc mesurer avant, rôle par rôle.
 *
 * La mesure, sur les jeux réellement semés :
 *
 * | Permission | Admin | User | Chef de hub | Verdict |
 * |---|---|---|---|---|
 * | `support_read` | ✅ | ✅ | ❌ | posée |
 * | `parcel_read` | ✅ | ✅ | ✅ | posée — personne ne perd rien |
 * | `merchant_shop_update` | ✅ | ❌ | ❌ | posée — perte **voulue** |
 * | `parcel_create` | ✅ | ❌ | ❌ | posée — perte **voulue** |
 * | les quatre droits financiers | — | — | — | liste, pas permission unique |
 *
 * 🔴 **Le cas annoncé comme « délicat » était un trou, pas un arbitrage.**
 * `parcel/create` et `parcel/store` exigent `parcel_create` ; `parcel/clone/{id}`
 * et `parcel/clone-store` **n'exigeaient rien**. Le clone était donc un
 * contournement complet du droit de création — et `duplicateStore()` crée le
 * colis **et débite le portefeuille du marchand** (`WalletDebit`). Ce que le rôle
 * `User` perd ici, il n'aurait jamais dû l'avoir.
 *
 * ⚠️ **Le cas qui ne se ferme pas par une permission unique.**
 * `expense/search-account/{id}` est appelée par **quatre** écrans aux droits
 * différents — dépenses, revenus, salaires, et le panneau du chef de hub (les
 * quatre fichiers de `public/backend/js/**` qui la nomment). `expense_create`
 * aurait fermé l'encaissement livreur à **tous les chefs de hub**, dont le jeu
 * fixe (`UserRepository::hubPermissions()`) ne le porte pas. D'où l'extension du
 * middleware : `hasPermission:a|b|c` passe si l'utilisateur porte l'une d'elles.
 *
 * Tout est prouvé **par appel HTTP** sur un hôte de locataire, pas par lecture de
 * la déclaration : c'est le routage complet qui est exercé, middleware compris.
 */
class WebPermissionGuardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private Parcel $colis;
    private MerchantShops $boutique;
    private int $marchandId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        // Sans cela, `subscriptionCheckMiddleware` redirige TOUT vers
        // `/subscription` et un test de garde qui attend `302` passe au vert sans
        // rien prouver. C'est ce que mon premier jet faisait — le `Location` disait
        // `/subscription`, pas `/`.
        $this->souscrireLeLocataire();

        $marchand = Merchant::firstOrFail();
        $this->marchandId = $marchand->id;
        $this->boutique = MerchantShops::where('merchant_id', $marchand->id)->firstOrFail();

        $this->colis = Parcel::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-S36-' . uniqid(),
            'customer_name' => 'Client',
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => \App\Enums\ParcelStatus::DELIVERED,
        ]);
    }

    /* ═══════════ les routes nues, désormais gardées ════════════════════════ */

    /**
     * @dataProvider routesDesormaisGardees
     */
    public function test_a_route_now_refuses_an_account_without_its_permission(
        string $methode, string $uri, string $permissionRequise
    ): void {
        $this->actingAs($this->agentAvec([]));

        $this->assertTrue($this->refusePourDroit($methode, $uri),
            "{$methode} /{$uri} reste ouverte à un compte sans « {$permissionRequise} »");
    }

    /**
     * Le contrôle négatif, et c'est lui qui compte : avec la permission, la route
     * répond. Sans ce test, poser `hasPermission:n_importe_quoi` passerait.
     *
     * @dataProvider routesDesormaisGardees
     */
    public function test_the_same_route_answers_with_the_permission(
        string $methode, string $uri, string $permissionRequise
    ): void {
        // Une liste `a|b|c` : porter la première suffit.
        $premiere = explode('|', $permissionRequise)[0];
        $this->actingAs($this->agentAvec([$premiere]));

        $this->assertFalse($this->refusePourDroit($methode, $uri),
            "{$methode} /{$uri} refuse un compte qui porte pourtant « {$premiere} »");
    }

    public static function routesDesormaisGardees(): array
    {
        return [
            'support/view' => ['GET', 'admin/support/view/{support}', 'support_read'],
            'parcel/delivered/logs/info' => ['GET', 'admin/parcel/delivered/logs/info/{parcel}', 'parcel_read'],
            'parcel/multiple/print/label' => ['GET', 'admin/parcel/multiple/print/label', 'parcel_read'],
            'parcel/clone' => ['GET', 'admin/parcel/clone/{parcel}', 'parcel_create'],
            'parcel/clone-store' => ['POST', 'admin/parcel/clone-store', 'parcel_create'],
            'merchant/shops/default' => ['PUT', 'admin/merchant/shops/default/{merchant}/{shop}', 'merchant_shop_update'],
            'expense/search-account' => ['POST', 'admin/expense/search-account/1',
                'expense_create|income_create|salary_create|salary_update|cash_received_from_delivery_man_create'],
        ];
    }

    /**
     * Le refus de la garde d'accès se reconnaît à sa **destination**, pas à son
     * code : `PermissionCheckMiddleware` redirige vers `/`.
     *
     * ⚠️ Comparer au seul `302` ne prouve rien, et deux cas l'ont montré :
     * `clone-store` répond `302` sur une **validation** échouée, et l'impression
     * en lot `302` parce qu'elle revient en arrière sans colis sélectionné. Les
     * deux avaient bien franchi la garde.
     *
     * ⚠️ Et comparer la destination à `/` ne suffisait pas non plus : sans en-tête
     * `Referer`, `redirect()->back()` retombe **aussi** sur `/`. Les deux refus
     * devenaient indistinguables. D'où le référent : la garde vise toujours `/`,
     * `back()` vise le référent. C'est la troisième forme du même piège dans ce
     * seul test — un refus qui ressemble à un autre refus.
     */
    private const REFERENT = self::HOTE . '/admin/parcel/index';

    private function refusePourDroit(string $methode, string $uri): bool
    {
        $reponse = $this->call($methode, self::HOTE . '/' . $this->concret($uri),
            [], [], [], ['HTTP_REFERER' => self::REFERENT]);

        return $reponse->getStatusCode() === 302
            && $reponse->headers->get('Location') === url('/');
    }

    /* ═══════════ le contournement de `parcel_create`, nommé ════════════════ */

    /**
     * 🔴 L'invariant du lot : les **quatre** portes de la création d'un colis
     * exigent le même droit. Le clone en était la porte dérobée.
     */
    public function test_the_four_doors_of_parcel_creation_require_the_same_permission(): void
    {
        $attendu = ['parcel.create', 'parcel.store', 'parcel.clone', 'parcel.clone-store'];
        $portees = [];

        foreach ($attendu as $nom) {
            $route = Router::getRoutes()->getByName($nom);
            $this->assertNotNull($route, "la route {$nom} a disparu : cet invariant n'a plus d'objet");
            $portees[$nom] = array_values(array_filter(
                $route->gatherMiddleware(),
                fn ($m) => is_string($m) && str_starts_with($m, 'hasPermission:'),
            ));
        }

        foreach ($portees as $nom => $middlewares) {
            $this->assertSame(['hasPermission:parcel_create'], $middlewares,
                "{$nom} n'exige pas `parcel_create` : le clone redevient un contournement");
        }
    }

    /** Et la preuve par l'usage : sans le droit, le clone n'enregistre rien. */
    public function test_an_account_without_the_creation_right_cannot_clone_a_parcel(): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        $avant = Parcel::withoutGlobalScopes()->count();

        $this->call('POST', self::HOTE . '/admin/parcel/clone-store', [
            'parcel_id' => $this->colis->id,
            'merchant_id' => $this->marchandId,
            'category_id' => 1,
            'cash_collection' => 5000,
        ]);

        $this->assertSame($avant, Parcel::withoutGlobalScopes()->count(),
            'un colis a été créé par le clone, sans le droit de création');
    }

    /* ═══════════ les paires sœurs : le même écran, le même droit ═══════════ */

    /**
     * La forme de **S22** : la liste est gardée, le détail ne l'est pas. Ce test
     * inscrit les paires que S36 a alignées, pour qu'elles ne se séparent plus.
     */
    public function test_each_sibling_pair_carries_the_same_permission(): void
    {
        $paires = [
            ['support.index', 'support.view'],
            ['parcel.logs', 'parcel.deliveredInfo'],
            ['parcel.print-label', 'parcel.multiple.print-label'],
            ['merchant.shops.update', 'merchant.shops.default'],
        ];

        $ecarts = [];

        foreach ($paires as [$reference, $alignee]) {
            $droits = [];
            foreach ([$reference, $alignee] as $nom) {
                $route = Router::getRoutes()->getByName($nom);
                $this->assertNotNull($route, "la route {$nom} a disparu");
                $droits[$nom] = implode(',', array_filter(
                    $route->gatherMiddleware(),
                    fn ($m) => is_string($m) && str_starts_with($m, 'hasPermission:'),
                ));
            }
            if ($droits[$reference] !== $droits[$alignee]) {
                $ecarts[] = "{$reference} = « {$droits[$reference]} » mais {$alignee} = « {$droits[$alignee]} »";
            }
        }

        $this->assertSame([], $ecarts, "Paires sœurs désalignées :\n - " . implode("\n - ", $ecarts));
    }

    /**
     * `support/view/{id}` est déclarée **deux fois** : côté locataire
     * (`routes/web.php`) et côté super-administrateur (`routes/superadmin.php`).
     * Les deux étaient nues ; les deux portent maintenant `support_read`.
     *
     * ⚠️ Celle-ci est vérifiée **sur la déclaration**, pas par un appel HTTP, et
     * c'est une limite assumée : `MountsTenantRoutes` monte `routes/web.php` sur un
     * hôte de locataire, et les routes du super-administrateur ne s'enregistrent
     * que sur un domaine **central** — l'exact opposé. Un sabotage l'a montré : le
     * retrait de cette permission laissait la suite au vert.
     *
     * `support_read` figure bien dans le jeu du super-administrateur (vérifié dans
     * `PermissionSeeder`), donc personne ne perd l'accès.
     */
    public function test_the_superadmin_twin_of_the_support_screen_is_guarded_too(): void
    {
        $source = file_get_contents(base_path('routes/superadmin.php'));

        $ligne = collect(explode("\n", $source))
            ->first(fn ($l) => str_contains($l, "Route::get('support/view/{id}'"));

        $this->assertNotNull($ligne, 'la route a disparu de superadmin.php : ce test n\'a plus d\'objet');
        $this->assertStringContainsString("hasPermission:support_read", $ligne,
            'le jumeau super-administrateur de l\'écran des tickets est reparti sans garde');

        // Et la moitié qui rend la garde inoffensive pour lui : il porte le droit.
        $jeu = collect(\App\Models\SuperAdminPermission::pluck('keywords'))
            ->flatMap(fn ($module) => array_values((array) $module))->all();

        $this->assertContains('support_read', $jeu,
            'poser support_read côté super-administrateur lui retirerait l\'accès');
    }

    /* ═══════════ les trois extensions du middleware ════════════════════════ */

    /** Une liste `a|b|c` : porter **l'une** d'elles suffit. */
    public function test_any_one_permission_of_a_list_opens_the_route(): void
    {
        foreach (['expense_create', 'income_create', 'cash_received_from_delivery_man_create'] as $droit) {
            $this->actingAs($this->agentAvec([$droit], $droit));

            $this->assertFalse($this->refusePourDroit('POST', 'admin/expense/search-account/1'),
                "« {$droit} » devrait suffire à ouvrir l'aide AJAX des comptes");
        }

        // Et un droit hors liste ne suffit pas.
        $this->actingAs($this->agentAvec(['parcel_read'], 'horsliste'));
        $this->assertTrue($this->refusePourDroit('POST', 'admin/expense/search-account/1'));
    }

    /**
     * ⚠️ `in_array($p, null)` lève une **TypeError** en PHP 8. Un compte dont le
     * jeu de permissions est nul recevait donc une **erreur 500** sur chaque route
     * gardée, au lieu d'un refus — et le socle n'exige nulle part que la colonne
     * soit remplie.
     */
    public function test_an_account_with_no_permission_set_is_refused_not_crashed(): void
    {
        $this->actingAs($this->agentAvec(null, 'sansjeu'));

        $reponse = $this->call('GET', self::HOTE . '/admin/parcel/clone/' . $this->colis->id);

        $this->assertSame(302, $reponse->getStatusCode(),
            'un jeu de permissions nul doit donner un refus, pas une erreur serveur');
    }

    /**
     * Un refus adressé à du JavaScript doit se lire comme un refus. Le socle
     * répondait `redirect('/')` — donc **200 avec le HTML du tableau de bord**
     * dans le gestionnaire de succès d'un `$.ajax`.
     */
    public function test_an_ajax_call_is_refused_with_403_not_a_redirect(): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        $reponse = $this->call('POST', self::HOTE . '/admin/expense/search-account/1',
            [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(403, $reponse->getStatusCode(),
            'un refus AJAX rendu en 302 vers / arrive dans le callback de succès');
    }

    /** La navigation de page, elle, garde la redirection du socle. */
    public function test_a_page_navigation_keeps_the_socles_redirect(): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        $reponse = $this->call('GET', self::HOTE . '/admin/parcel/clone/' . $this->colis->id);

        $this->assertSame(302, $reponse->getStatusCode());
        $this->assertSame(url('/'), $reponse->headers->get('Location'),
            'changer la destination toucherait les 197 autres déclarations : non fait');
    }

    /* ═══════════ la mesure qui a permis de décider ═════════════════════════ */

    /**
     * Ce test n'inscrit pas une garde mais la **mesure** : qui porte quoi dans les
     * jeux réellement semés. Si un jeu change, la décision doit être relue.
     */
    public function test_the_measurement_behind_each_decision_still_holds(): void
    {
        $roleUser = \App\Models\Backend\Role::where('slug', 'user')->firstOrFail()->permissions;
        $chefDeHub = (new \ReflectionClass(\App\Repositories\User\UserRepository::class))
            ->getMethod('hubPermissions');
        $chefDeHub->setAccessible(true);
        $chefDeHub = $chefDeHub->invoke(app(\App\Repositories\User\UserRepository::class));

        // Personne ne perd rien sur celles-ci.
        $this->assertContains('parcel_read', $roleUser);
        $this->assertContains('parcel_read', $chefDeHub);
        $this->assertContains('support_read', $roleUser);

        // Pertes assumées.
        $this->assertNotContains('parcel_create', $roleUser,
            'si le rôle User porte désormais parcel_create, la note de S36 sur sa perte est périmée');
        $this->assertNotContains('merchant_shop_update', $roleUser);

        // Et la raison de la liste : le chef de hub n'a aucun des droits financiers
        // « create » mais appelle bien l'aide AJAX depuis son écran d'encaissement.
        $this->assertNotContains('expense_create', $chefDeHub,
            'si le chef de hub porte expense_create, une permission unique suffirait');
        $this->assertContains('cash_received_from_delivery_man_create', $chefDeHub);
    }

    /* ═════════════════════════════ fixtures ════════════════════════════════ */

    /** Un agent de la société, porteur du jeu de permissions donné. */
    private function agentAvec(?array $permissions, string $suffixe = 'agent'): User
    {
        $agent = new User();
        $agent->company_id = settings()->id;
        $agent->name = 'Agent ' . $suffixe;
        $agent->email = 'agent.s36.' . $suffixe . '.' . uniqid() . '@example.test';
        $agent->mobile = '00229970' . rand(100000, 999999);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->permissions = $permissions;
        $agent->save();

        return $agent;
    }

    /** Remplace les paramètres du gabarit par les identifiants de la fixture. */
    private function concret(string $uri): string
    {
        return str_replace(
            ['{support}', '{parcel}', '{merchant}', '{shop}'],
            [1, $this->colis->id, $this->marchandId, $this->boutique->id],
            $uri,
        );
    }
}
