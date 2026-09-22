<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Middleware\PanelAccessMiddleware;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Role;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as Router;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S41 — la séparation des trois panneaux du web, par type de compte.
 *
 * `routes/web.php` place `admin/*` et `merchant/*` dans le **même** groupe
 * `auth` + `subscriptionCheck`. Le seul séparateur était le `hasPermission`
 * posé route par route — et là où il manque, la porte était ouverte à **tout
 * compte authentifié**.
 *
 * Mesuré par appel HTTP **avant** le correctif, et c'est ce que ce test
 * empêche de revenir :
 *
 * | Compte | `POST admin/parcel/priority/update` | `GET admin/payout` | `POST admin/merchant/search` |
 * |---|---|---|---|
 * | agent sans aucun droit | 200 | 200 | 200 |
 * | **marchand** | **200** (une écriture) | **200** | **200** |
 * | **livreur** | **200** | 500 | **200** |
 *
 * Et dans l'autre sens, un agent ou un livreur atteignait le panneau marchand,
 * où il récoltait un **500** — un refus annoncé comme une panne, la famille de
 * S37, fermée ici par la même garde.
 *
 * ⚠️ **Le même trou était déjà fermé sur l'API, en S5.** `UserTypeMiddleware`
 * cloisonne `deliveryman/*` et les routes marchand de `routes/api.php`. Le web
 * est resté ouvert pendant tout le chantier, et huit passes d'isolation sont
 * passées à côté : elles cherchaient un `company_id` manquant, pas un
 * `user_type` manquant.
 *
 * ⚠️ **Qui perd l'accès : personne.** Mesuré avant de poser la garde, comme
 * l'exige la règle de S36 — les comptes du back-office sont ADMIN et
 * SUPER_ADMIN et rien d'autre (`HUB` et `INCHARGE` ne sont pas des types de
 * compte mais des marqueurs de lignes de relevé) ; le livreur n'a aucun écran
 * web ; aucune vue marchande n'appelle une URL `admin/`, aucune vue du
 * back-office n'appelle une URL `merchant/` ; aucun rôle de locataire ne porte
 * `plans_*` ni `company_*` ; et il n'existe aucune usurpation d'identité.
 */
class WebPanelSeparationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    /** Les écritures et lectures du back-office qui ne portent AUCUN `hasPermission`. */
    private const ADMIN_SANS_DROIT = [
        ['POST', 'admin/merchant/search'],
        ['GET',  'admin/payout'],
        ['POST', 'admin/get-merchant-cod'],
        ['POST', 'admin/expense/users'],
    ];

    private Merchant $marchand;
    private Parcel $colis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $this->marchand = $this->marchandDe(settings()->id);
        $this->colis = Parcel::forceCreate([
            'company_id' => settings()->id, 'merchant_id' => $this->marchand->id,
            'tracking_id' => 'BL-PAN-' . uniqid(), 'customer_name' => 'Client',
            'customer_phone' => '0022997123456', 'customer_address' => 'Cotonou',
            'cash_collection' => 10000, 'current_payable' => 9000,
            'status' => ParcelStatus::PENDING, 'priority_type_id' => 1,
        ]);
    }

    /* ─────────────────────────────── le filet ───────────────────────────── */

    /**
     * Le filet : sous `auth`, toute route d'un panneau porte la garde de ce
     * panneau. Il énumère les routes MONTÉES, donc une route ajoutée demain hors
     * du groupe est prise sans que personne n'y pense.
     *
     * ⚠️ Seules les routes sous `auth` sont concernées : `POST
     * merchant/sign-up-store` est l'inscription publique d'un marchand — il n'y a
     * pas de compte à typer.
     */
    public function test_every_authenticated_route_of_a_panel_carries_its_panel_guard(): void
    {
        $attendu = [
            'admin/'       => 'panel:back-office',
            'merchant/'    => 'panel:merchant',
            'super-admin/' => 'panel:super-admin',
        ];

        $nues = [];
        $comptees = 0;
        foreach (Router::getRoutes() as $route) {
            $intergiciels = array_values(array_filter($route->gatherMiddleware(), 'is_string'));
            if (!array_intersect($intergiciels, ['auth', 'auth:web'])) {
                continue;
            }
            $uri = $route->uri();
            foreach ($attendu as $prefixe => $garde) {
                if (!str_starts_with($uri, $prefixe)) {
                    continue;
                }
                $comptees++;
                if (!in_array($garde, $intergiciels, true)) {
                    $nues[] = implode('|', array_diff($route->methods(), ['HEAD'])) . ' ' . $uri . " (attendait $garde)";
                }
                break;
            }
        }

        sort($nues);
        $this->assertSame([], $nues, "des routes d'un panneau ne portent pas sa garde :\n" . implode("\n", $nues));
        // Un PLANCHER, pas un compte exact : la mesure du jour est 552 routes sous
        // les trois panneaux, et poser 552 ferait echouer le filet au premier ajout
        // legitime (la lecon de S33). Ce que ce garde attrape est l'inverse : des
        // routes NON MONTEES, qui rendraient la liste ci-dessus vide sans rien prouver.
        $this->assertGreaterThan(500, $comptees,
            'le filet ne compte presque rien : les routes ne sont probablement pas montées');
    }

    /**
     * ⚠️ **La limite du filet, mesuree.** `routes/superadmin.php` declare lui aussi
     * un groupe `admin/` (61 URI) et le panneau `super-admin/`. Ses 61 URI sont
     * **toutes** redeclarees dans `routes/web.php` — verifie une par une — et
     * `MountsTenantRoutes` reenregistre `web.php` APRES le chargement de
     * `superadmin.php` par le `RouteServiceProvider`. La collection etant indexee
     * par methode + URI, les versions de `web.php` ecrasent les autres : le filet
     * ci-dessus est **structurellement aveugle** a ce fichier, et un sabotage l'a
     * prouve en restant vert.
     *
     * Meme limite que celle deja documentee en S36 pour le jumeau
     * super-administrateur. Le controle se fait donc sur la DECLARATION, ce qui est
     * plus faible qu'un appel HTTP — et c'est dit plutot que masque.
     */
    public function test_the_central_route_file_declares_its_panels_too(): void
    {
        $source = file_get_contents(base_path('routes/superadmin.php'));

        $this->assertStringContainsString(
            "Route::prefix('super-admin')->middleware('panel:super-admin')->group(", $source,
            'le panneau de la plateforme n\'est plus garde dans routes/superadmin.php');
        $this->assertStringContainsString(
            "['prefix' => 'admin', 'middleware' => 'panel:back-office']", $source,
            'le back-office central n\'est plus garde dans routes/superadmin.php');
    }

    /* ────────────────── un marchand n'entre pas au back-office ──────────── */

    /**
     * Le pire du lot : une ÉCRITURE. `POST admin/parcel/priority/update`
     * changeait la priorité d'un colis, et un marchand y répondait 200.
     */
    public function test_a_merchant_cannot_write_through_a_back_office_route(): void
    {
        $this->actingAs($this->utilisateurDuMarchand());

        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => $this->colis->id, 'priority' => 1])
            ->assertForbidden();

        $this->assertSame(1, (int) $this->colis->fresh()->priority_type_id,
            'la priorité a été réécrite par un marchand passé par le back-office');
    }

    /** Et les lectures nues, dont la page de paiement AUX marchands. */
    public function test_a_merchant_is_refused_on_every_unguarded_back_office_route(): void
    {
        $this->actingAs($this->utilisateurDuMarchand());

        foreach (self::ADMIN_SANS_DROIT as [$verbe, $uri]) {
            $this->call($verbe, self::HOTE . '/' . $uri, ['search' => 'PME', 'merchant_id' => $this->marchand->id, 'hub_id' => 1])
                ->assertForbidden();
        }
    }

    public function test_a_delivery_man_is_refused_on_the_back_office(): void
    {
        $this->actingAs($this->livreur());

        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => $this->colis->id, 'priority' => 1])
            ->assertForbidden();
        $this->post(self::HOTE . '/admin/merchant/search', ['search' => 'PME'])->assertForbidden();
    }

    /* ──────────── ni un agent ni un livreur n'entrent chez le marchand ──── */

    /**
     * ⚠️ Le refus attendu est **403, pas 500**. Avant la garde, un agent sur
     * `merchant/parcel/index` traversait `getMerchant($userID)` qui rend `null`,
     * puis déréférençait `$merchant->id` : une panne là où il fallait un refus.
     */
    public function test_an_agent_is_refused_on_the_merchant_panel_and_not_crashed(): void
    {
        $this->actingAs($this->agent([]));

        $this->get(self::HOTE . '/merchant/parcel/index')->assertForbidden();
        $this->get(self::HOTE . '/merchant/shops/index')->assertForbidden();
    }

    /** Et la garde ferme aussi les ÉCRITURES du panneau marchand. */
    public function test_an_agent_cannot_create_a_shop_through_the_merchant_panel(): void
    {
        $this->actingAs($this->agent([]));
        $avant = MerchantShops::count();

        $this->post(self::HOTE . '/merchant/shops/store', [
            'name' => 'Boutique intruse', 'contact_no' => '0022997000001',
            'address' => 'Cotonou', 'status' => Status::ACTIVE,
        ])->assertForbidden();

        $this->assertSame($avant, MerchantShops::count(), 'une boutique a été créée par un agent');
    }

    public function test_a_delivery_man_is_refused_on_the_merchant_panel(): void
    {
        $this->actingAs($this->livreur());

        $this->get(self::HOTE . '/merchant/parcel/index')->assertForbidden();
    }

    /* ─────────────── le panneau de la plateforme, à son seul type ───────── */

    /**
     * `super-admin/subscription/history` ne portait **aucune** garde : elle
     * répondait 200 à un agent ET à un marchand.
     */
    public function test_the_platform_panel_is_reserved_to_the_super_admin(): void
    {
        $this->actingAs($this->agent([]));
        $this->get(self::HOTE . '/super-admin/subscription/history')->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->utilisateurDuMarchand());
        $this->get(self::HOTE . '/super-admin/subscription/history')->assertForbidden();
    }

    /* ──────────────────────── les contrôles négatifs ────────────────────── */

    /** Sans cette moitié, une garde qui refuse TOUT LE MONDE passerait au vert. */
    public function test_the_back_office_still_serves_its_own_agent(): void
    {
        $this->actingAs($this->agent([]));

        $this->post(self::HOTE . '/admin/merchant/search', ['search' => 'PME'])->assertOk();

        // ⚠️ S43 a posé `payout_read` sur cet écran : il n'était joignable par un
        // agent SANS droit que parce qu'il était nu. Ce contrôle négatif mesure la
        // garde de PANNEAU, pas celle du droit — on lui donne donc le droit, et il
        // continue de dire ce qu'il a toujours dit : le back-office sert son agent.
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->agent(['payout_read'], 'versements'));
        $this->get(self::HOTE . '/admin/payout')->assertOk();
    }

    public function test_the_merchant_panel_still_serves_its_own_merchant(): void
    {
        $this->actingAs($this->utilisateurDuMarchand());

        $this->get(self::HOTE . '/merchant/parcel/index')->assertOk();

        $avant = MerchantShops::count();
        $this->post(self::HOTE . '/merchant/shops/store', [
            'name' => 'Ma boutique', 'contact_no' => '0022997000002',
            'address' => 'Cotonou', 'status' => Status::ACTIVE,
        ]);
        $this->assertSame($avant + 1, MerchantShops::count(),
            'le marchand ne peut plus créer sa propre boutique');
    }

    /**
     * ⚠️ `GET /dashboard` est déclaré HORS des deux préfixes, et c'est voulu :
     * `DashbordController::index()` branche sur `user_type` et rend
     * `backend.merchant_panel.dashboard` à un marchand. Il est **partagé**, et
     * une garde de panneau posée dessus casserait le panneau marchand. Ce test
     * inscrit la mesure pour que personne ne « corrige » ce partage.
     */
    public function test_the_shared_dashboard_stays_shared(): void
    {
        $this->actingAs($this->agent([]));
        $this->get(self::HOTE . '/dashboard')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->utilisateurDuMarchand());
        $this->get(self::HOTE . '/dashboard')->assertOk();
    }

    /* ──────────── la forme du middleware, et la mesure inscrite ─────────── */

    /**
     * Fermé par défaut : une faute de frappe dans un nom de panneau doit
     * refuser, pas ouvrir. Vérifié en unité, parce qu'aucune route déclarée ne
     * porte — et ne doit porter — un nom de panneau inconnu.
     */
    public function test_an_unknown_panel_name_refuses_instead_of_opening(): void
    {
        $requete = Request::create(self::HOTE . '/peu-importe');
        $requete->setUserResolver(fn () => $this->agent([]));

        try {
            (new PanelAccessMiddleware())->handle($requete, fn () => response('servi'), 'panneau-inexistant');
            $this->fail('un nom de panneau inconnu a laissé passer la requête');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    /** Un appel sans compte connecté ne doit pas non plus passer. */
    public function test_a_request_without_an_account_refuses(): void
    {
        $requete = Request::create(self::HOTE . '/peu-importe');
        $requete->setUserResolver(fn () => null);

        try {
            (new PanelAccessMiddleware())->handle($requete, fn () => response('servi'), 'back-office');
            $this->fail('une requête sans compte a été servie');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    /**
     * La mesure elle-même, inscrite : les comptes du back-office sont ADMIN et
     * SUPER_ADMIN, et rien d'autre. Ajouter un type ici est une DÉCISION, qui
     * doit passer par la règle de S36 — mesurer qui gagne l'accès.
     */
    public function test_the_measurement_behind_each_panel_still_holds(): void
    {
        $this->assertSame([
            'back-office' => [UserType::ADMIN, UserType::SUPER_ADMIN],
            'merchant'    => [UserType::MERCHANT],
            'super-admin' => [UserType::SUPER_ADMIN],
        ], PanelAccessMiddleware::PANNEAUX);

        $this->assertSame([UserType::MERCHANT, UserType::DELIVERYMAN], array_values(\App\Http\Middleware\UserTypeMiddleware::SCOPES),
            'le cloisonnement de l\'API (S5) a changé de portées : revoir le pendant web');
    }

    /* ─────────────────────────── outillage ──────────────────────────────── */

    private function agent(array $droits, string $suffixe = 'principal'): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent panneau ' . $suffixe;
        $u->email = 'agent.panneau.' . $suffixe . '@example.test';
        $u->mobile = '00229979100' . str_pad((string) strlen($suffixe), 2, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::ADMIN;
        $u->role_id = Role::where('company_id', settings()->id)->value('id');
        $u->permissions = $droits;
        $u->save();

        return $u;
    }

    private function marchandDe(int $societe): Merchant
    {
        $u = new User();
        $u->company_id = $societe;
        $u->name = 'Marchand panneau';
        $u->email = 'marchand.panneau@example.test';
        $u->mobile = '0022997910002';
        $u->password = bcrypt('secret');
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME panneau', 'current_balance' => 0,
        ]);
    }

    private function utilisateurDuMarchand(): User
    {
        return User::find($this->marchand->user_id);
    }

    private function livreur(): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Livreur panneau';
        $u->email = 'livreur.panneau@example.test';
        $u->mobile = '0022997910003';
        $u->password = bcrypt('secret');
        $u->user_type = UserType::DELIVERYMAN;
        $u->unique_id = 'L-PAN-1';
        $u->save();

        DeliveryMan::forceCreate([
            'company_id' => settings()->id, 'user_id' => $u->id, 'status' => Status::ACTIVE,
            'delivery_charge' => 500, 'pickup_charge' => 200, 'return_charge' => 300,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);

        return $u;
    }
}
