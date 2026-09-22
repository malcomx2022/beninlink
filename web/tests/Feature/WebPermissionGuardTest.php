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

            // S42 — les aides AJAX des colis, dont S40 avait fermé l'isolation en
            // signalant qu'elles ne portaient aucune garde de droit. Chacune prend
            // le droit de l'écran qui l'appelle, et l'ensemble des appelants de
            // chacune a été résolu avant de poser la garde (règle de S36).
            'parcel/priority/update' => ['POST', 'admin/parcel/priority/update', 'parcel_update'],
            'parcel/quote' => ['POST', 'admin/parcel/quote', 'parcel_create|parcel_update'],
            'parcel/delivery-category' => ['POST', 'admin/parcel/delivery-category', 'parcel_create|parcel_update'],
            'parcel/received-warehouse-hub-selected' => ['POST', 'admin/parcel/received-warehouse-hub-selected', 'parcel_status_update'],
            'transertohub-selected-hub' => ['POST', 'admin/transertohub-selected-hub', 'parcel_status_update'],
            'parcel/recived-by-hub/search' => ['POST', 'admin/parcel/recived-by-hub/search', 'parcel_status_update'],

            // S43 — les quatre écrans qui BLOQUAIENT la garde des deux sélecteurs
            // partagés : on ne dérive pas un droit d'un écran qui n'en a pas.
            'parcel/filter' => ['GET', 'admin/parcel/filter', 'parcel_read'],
            'parcel/specific/search' => ['GET', 'admin/parcel/specific/search', 'parcel_read'],
            'payout' => ['GET', 'admin/payout', 'payout_read'],
            'payout/merchant/payout' => ['GET', 'admin/payout/merchant/payout', 'payout_read'],

            // Et les deux sélecteurs eux-mêmes, désormais dérivables. Leur liste
            // complète est tenue par `SharedPickerGuardTest`, qui la recalcule
            // depuis les vues ; ici on prouve seulement qu'elle refuse et admet.
            'parcel/deliveryman/search' => ['POST', 'admin/parcel/deliveryman/search',
                'parcel_read|income_create|income_update|expense_create|expense_update'
                . '|cash_received_from_delivery_man_create|cash_received_from_delivery_man_update'
                . '|merchant_hub_deliveryman'],
            'parcel/merchant/shops' => ['POST', 'admin/parcel/merchant/shops',
                'parcel_read|parcel_create|parcel_update|income_create|income_update'
                . '|parcel_wise_profit|parcel_status_reports|parcel_total_summery'
                . '|merchant_hub_deliveryman|payout_read'],
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
     * devenaient indistinguables. D'où le référent : `back()` vise le référent,
     * tandis que la garde visait `/`.
     *
     * ⚠️ **S39 a changé la destination de la garde** : elle ne renvoie plus sur `/`
     * — la page publique du site — mais dans le back-office, avec un message. Le
     * discriminateur est donc maintenant le **message flashé**, que seule la garde
     * pose : ni une validation échouée ni un `back()` ne l'écrivent.
     */
    private const REFERENT = self::HOTE . '/admin/parcel/index';

    private function refusePourDroit(string $methode, string $uri): bool
    {
        $this->flush();

        $reponse = $this->call($methode, self::HOTE . '/' . $this->concret($uri),
            [], [], [], ['HTTP_REFERER' => self::REFERENT]);

        if ($reponse->getStatusCode() !== 302) {
            return false;
        }

        // Le message de la garde, et jamais la page publique.
        return session('toastr::messages') !== null
            && collect(session('toastr::messages'))
                ->contains(fn ($note) => ($note['message'] ?? null) === __('message.permission_denied'));
    }

    /** Vide les messages d'une requête précédente, pour ne pas lire les siens. */
    private function flush(): void
    {
        session()->forget('toastr::messages');
    }

    /**
     * S42 — la **paire sœur**, poussée jusqu'à la vue.
     *
     * La bascule de priorité était le seul contrôle de la liste des colis rendu
     * **sans condition**, alors que modifier et supprimer sont gardés deux lignes
     * plus haut dans le même tableau. Un compte en lecture seule la voyait, la
     * basculait, et écrivait.
     *
     * Garder la route sans masquer le contrôle aurait seulement transformé
     * l'écriture en erreur visible : le bouton reste, et il tombe. La règle des
     * paires sœurs vaut donc aussi entre une route et le contrôle qui l'appelle.
     */
    public function test_the_priority_toggle_is_hidden_from_a_read_only_account(): void
    {
        // ⚠️ Il faut un colis qui apparaisse vraiment dans la liste : sur une liste
        // vide, l'assertion « la bascule est absente » passe au vert sans rien
        // prouver. C'est le contrôle négatif ci-dessous qui l'a montré — il
        // échouait, et c'était lui qui avait raison.
        $visible = Parcel::forceCreate([
            'company_id' => settings()->id,
            'merchant_id' => $this->marchandId,
            'tracking_id' => 'BL-S42-' . uniqid(),
            'customer_name' => 'Client priorite',
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => \App\Enums\ParcelStatus::PENDING,
            'priority_type_id' => 1,
        ]);

        $this->actingAs($this->agentAvec(['parcel_read']));

        $liste = $this->get(self::HOTE . '/admin/parcel/index');
        $liste->assertOk();
        $this->assertStringContainsString($visible->tracking_id, $liste->getContent(),
            'la liste ne contient aucun colis : le test ne mesure rien');

        $this->assertStringNotContainsString(route('parcel.priority.status'), $liste->getContent(),
            'un compte en lecture seule voit encore la bascule de priorité');

        // Contrôle négatif : avec le droit d'écriture, la bascule est bien là.
        $this->actingAs($this->agentAvec(['parcel_read', 'parcel_update'], 'ecrivain'));

        $avecDroit = $this->get(self::HOTE . '/admin/parcel/index');
        $avecDroit->assertOk();

        $this->assertStringContainsString(route('parcel.priority.status'), $avecDroit->getContent(),
            'la bascule a disparu pour un compte qui a pourtant le droit de l\'actionner');
    }

    /**
     * S42 — deux écrans du panneau marchand appelaient la route **du
     * back-office** pour leur sélecteur de poids, là où l'écran de création
     * appelle sa jumelle marchand. Depuis la garde de panneau de S41, ces deux
     * écrans avaient donc un sélecteur cassé : un marchand y recevait un 403.
     *
     * L'invariant, et non la correction : aucune vue du panneau marchand ne
     * référence une route du back-office pour cette aide.
     */
    public function test_no_merchant_panel_view_calls_the_back_office_weight_helper(): void
    {
        $fautives = [];

        foreach (glob(resource_path('views/backend/merchant_panel/parcel/*.blade.php')) as $vue) {
            if (str_contains(file_get_contents($vue), "route('parcel.deliveryCategory.deliveryWeight')")) {
                $fautives[] = basename($vue);
            }
        }

        $this->assertSame([], $fautives, "Des vues du panneau marchand appellent la route du back-office pour le "
            . "sélecteur de poids. Depuis la garde de panneau, un marchand y reçoit 403 et le sélecteur ne se "
            . "remplit plus :\n - " . implode("\n - ", $fautives));
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

    /**
     * 🔴 **S39.** Le socle renvoyait un opérateur refusé vers `/`. Mesuré : sur un
     * domaine de locataire, `/` n'est pas le tableau de bord mais la **page publique
     * du site** (groupe `frontend`, hors de `auth`). Il était donc **éjecté du
     * back-office vers la vitrine commerciale**, sans un mot.
     */
    public function test_a_refused_navigation_never_lands_on_the_public_site(): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        $reponse = $this->call('GET', self::HOTE . '/admin/parcel/clone/' . $this->colis->id);

        $this->assertSame(302, $reponse->getStatusCode());
        $this->assertNotSame(url('/'), $reponse->headers->get('Location'),
            'un refus renvoie encore sur la page publique du site');
        $this->assertSame(route('dashboard.index'), $reponse->headers->get('Location'),
            'sans page précédente, le repli doit être le tableau de bord');
    }

    /** Et il sait pourquoi : le refus porte un message. */
    public function test_a_refused_navigation_carries_an_explicit_message(): void
    {
        $this->flush();
        $this->actingAs($this->agentAvec(['parcel_read']));

        $this->call('GET', self::HOTE . '/admin/parcel/clone/' . $this->colis->id);

        $this->assertNotNull(session('toastr::messages'),
            'un refus silencieux ne se distingue pas d\'une navigation réussie');
        $this->assertTrue(
            collect(session('toastr::messages'))
                ->contains(fn ($note) => ($note['message'] ?? null) === __('message.permission_denied')),
            'le message du refus doit être celui de `message.permission_denied`',
        );

        // ⚠️ Un sabotage l'a montré : retirer la clé de traduction laissait ce test
        // au vert, parce que `__()` replie sur la CLÉ elle-même et que les deux
        // côtés de la comparaison repliaient de la même façon. L'opérateur aurait
        // lu « message.permission_denied » à l'écran. On exige donc une phrase.
        $this->assertNotSame('message.permission_denied', __('message.permission_denied'),
            'la clé de traduction du refus est absente : l\'écran afficherait la clé brute');

        foreach (['fr', 'en'] as $langue) {
            $this->assertArrayHasKey('permission_denied', require base_path("lang/{$langue}/message.php"),
                "la traduction du refus manque en « {$langue} »");
        }
    }

    /** Le repli est atteignable : le tableau de bord n'exige aucune permission. */
    public function test_the_fallback_screen_needs_no_permission_of_its_own(): void
    {
        $route = Router::getRoutes()->getByName('dashboard.index');

        $this->assertNotNull($route, 'le repli du refus a disparu');
        $this->assertSame([], array_values(array_filter(
            $route->gatherMiddleware(),
            fn ($m) => is_string($m) && str_starts_with($m, 'hasPermission:'),
        )), 'renvoyer vers un écran lui-même gardé ferait boucler le refus');
    }

    /**
     * ⚠️ Le piège de ce lot, relevé **avant** de l'écrire. `url()->previous()` lit
     * **d'abord l'en-tête `Referer`** et ne retombe sur la session qu'à défaut
     * (`UrlGenerator::previous()`). S'en servir aurait fait de cette garde une
     * **redirection ouverte** : une page tierce pointant vers une route refusée
     * aurait renvoyé le navigateur chez elle.
     *
     * La page précédente est donc lue dans la session, et l'hôte vérifié malgré
     * tout. Ce test envoie un `Referer` étranger : il ne doit pas être suivi.
     */
    public function test_a_foreign_referer_is_never_followed(): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        $reponse = $this->call('GET', self::HOTE . '/admin/parcel/clone/' . $this->colis->id,
            [], [], [], ['HTTP_REFERER' => 'https://ailleurs.example/piege']);

        $this->assertSame(route('dashboard.index'), $reponse->headers->get('Location'),
            'l\'en-tête `Referer` d\'un tiers a été suivi : redirection ouverte');
    }

    /**
     * ⚠️ Deux gardes du repli qu'aucun de mes tests n'atteignait, et deux sabotages
     * verts l'ont dit : par le chemin normal, la page précédente de la session est
     * **toujours** du bon hôte et **jamais** l'URL refusée — le socle l'écrit
     * lui-même. Les exercer demande de semer la session à la main, ce qui est
     * légitime : une valeur périmée ou empoisonnée est exactement ce contre quoi
     * ces gardes existent.
     *
     * @dataProvider sessionsAberrantes
     */
    public function test_an_aberrant_previous_url_falls_back_to_the_dashboard(string $precedente, string $motif): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        $refusee = self::HOTE . '/admin/parcel/clone/' . $this->colis->id;

        session()->setPreviousUrl($precedente === '{refusee}' ? $refusee : $precedente);

        $reponse = $this->call('GET', $refusee);

        $this->assertSame(route('dashboard.index'), $reponse->headers->get('Location'), $motif);
    }

    public static function sessionsAberrantes(): array
    {
        return [
            'un autre hôte' => ['https://ailleurs.example/piege',
                'une page précédente d\'un autre hôte a été suivie : redirection ouverte'],
            'la page refusée elle-même' => ['{refusee}',
                'se renvoyer sur la page qui vient d\'être refusée fait boucler le refus'],
        ];
    }

    /**
     * Le contrôle négatif du repli : quand la session **porte** une page précédente
     * du back-office, c'est là qu'il revient — l'opérateur ne perd pas son contexte.
     */
    public function test_a_refused_navigation_returns_to_the_previous_back_office_page(): void
    {
        $this->actingAs($this->agentAvec(['parcel_read']));

        // Une navigation servie d'abord : c'est elle que la session retient.
        $liste = self::HOTE . '/admin/parcel/index';
        $this->assertSame(200, $this->get($liste)->getStatusCode(),
            'la fixture doit partir d\'une page que cet agent peut voir');

        $reponse = $this->call('GET', self::HOTE . '/admin/parcel/clone/' . $this->colis->id);

        $this->assertSame($liste, $reponse->headers->get('Location'),
            'le refus doit ramener sur la page précédente, pas sur le repli');
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
