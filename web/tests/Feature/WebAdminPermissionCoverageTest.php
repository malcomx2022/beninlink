<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as Router;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S44 — la porte du bureau : les routes du back-office sans garde de droit.
 *
 * S41 a fermé la porte du **bâtiment** — `admin/*` exige désormais un compte de
 * type back-office. Reste celle du **bureau** : parmi les 447 routes `admin/*`,
 * **60 ne portaient aucun `hasPermission`**. Tout opérateur du back-office, quel
 * que soit son rôle et même sans un seul droit, les atteignait.
 *
 * Ce filet ne laisse plus aucune route dans un état indéterminé. Chacune est :
 *
 *  - **gardée** — elle porte un `hasPermission`, et les familles posées par ce lot
 *    sont exercées par appel HTTP ci-dessous, dans les deux sens ;
 *  - **exemptée** — avec le motif écrit pour lequel un droit n'a pas de sens ;
 *  - **à l'arriéré** — non encore mesurée, sous un plafond qui ne peut que baisser.
 *
 * ⚠️ **COMMENT LA MESURE SE FAIT, ET POURQUOI ELLE NE S'IMPROVISE PAS.** Le droit
 * d'une aide AJAX n'est pas déductible de son nom : il est celui des ÉCRANS qui
 * l'appellent (règle de S36, `hasPermission:a|b|c`). La chaîne se remonte en trois
 * sauts — route nue → fichier appelant → écran → droit de l'écran — et le premier
 * saut est le piège : **les appelants vivent dans `public/backend/js/**\/custom.js`**,
 * pas dans les vues. Une recherche limitée aux `.blade.php` déclare « sans
 * appelant » onze routes qui sont en réalité des aides AJAX bien vivantes — S40
 * vient d'en prouver les fuites.
 *
 * ⚠️ **ET POURQUOI CETTE MESURE EST UN ARRIÉRÉ, PAS UN BALAYAGE.** Chercher l'URI
 * courte (`parcel/filter`) attrape aussi `merchant/parcel/filter` : la liste
 * d'écrans se pollue de vues du panneau marchand, et le jeu de droits déduit
 * s'élargit à tort. La déduction mécanique donne une PISTE, pas un verdict — d'où
 * une route classée à l'arriéré tant que ses appelants n'ont pas été lus un par un.
 */
class WebAdminPermissionCoverageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    /**
     * Routes `admin/*` pour lesquelles un `hasPermission` n'a pas de sens.
     * Le motif est la valeur : il doit tenir en une ligne et se vérifier.
     */
    private const EXEMPTEES = [
        // S37 : gardés par l'IDENTITÉ (`abort_if((int) $id !== Auth::id(), 403)`),
        // et prouvés par `ProfileAccessTest`. Tout opérateur édite son propre
        // profil quel que soit son rôle : un droit y serait un contresens.
        'GET admin/profile/{id}' => 'profil : gardé par identité (S37)',
        'GET admin/profile/update/{id}' => 'profil : gardé par identité (S37)',
        'GET admin/profile/change-password/{id}' => 'profil : gardé par identité (S37)',
        'PUT admin/profile/update/{id}' => 'profil : gardé par identité (S37)',
        'PUT admin/profile/update-password/{id}' => 'profil : gardé par identité (S37)',

        // Liée depuis `backend/super-admin/partials/sidebar.blade.php`. Le jeu de
        // droits du super-administrateur vient d'une AUTRE table
        // (`SuperAdminPermission`), et il est déjà refusé sur les 387 routes
        // gardées du back-office locataire — mesuré. Y poser un droit de locataire
        // casserait son propre menu : la garde qui convient ici est celle du TYPE
        // de compte (S41), pas celle du droit.
        'GET admin/subscribe' => 'écran d\'abonnement lié depuis le menu du super-administrateur',
    ];

    /**
     * L'arriéré : routes dont les appelants n'ont pas encore été lus un par un.
     * Elle ne grandit jamais — le plafond ci-dessous le garde.
     */
    private const HERITAGE = [
        // S125 — vide. Les sept aides AJAX restantes portent la liste DÉRIVÉE des droits de leurs
        // écrans appelants (voir `gardesPosees()`), comme les deux sélecteurs partagés de S43.
        // S124 avait retiré les routes `addons` et gardé cinq écrans. Plus d'arriéré.
    ];

    /** Le cliquet. Ne monte jamais. */
    private const PLAFOND_HERITAGE = 0;

    /**
     * Les gardes posées par ce lot, et le droit mesuré pour chacune.
     * `[méthode, URI, droits, corps minimal]`
     */
    public static function gardesPosees(): array
    {
        $cas = [
            ['POST', 'admin/income/balance-check', 'income_create|income_update|cash_received_from_delivery_man_create|cash_received_from_delivery_man_update'],
            ['POST', 'admin/income/hub-user-accounts', 'income_create|income_update'],
            ['POST', 'admin/income/users', 'income_create|income_update'],
            ['POST', 'admin/expense/users', 'expense_create|expense_update'],
            ['POST', 'admin/salary/users', 'salary_read|salary_create|salary_update|salary_generate_create|salary_generate_update|salary_reports'],
            ['POST', 'admin/salary/search-account', 'expense_create|expense_update|salary_create|salary_update'],
            ['GET', 'admin/bank-transaction/specific/search', 'bank_transaction_read'],
            ['GET', 'admin/bank-transaction/filter/print', 'bank_transaction_read'],
            ['POST', 'admin/merchant/delivery-charge/info', 'merchant_delivery_charge_create|merchant_delivery_charge_update'],
            ['POST', 'admin/merchant/paymentmethod/change', 'merchant_payment_create|merchant_payment_update'],
            ['GET', 'admin/payment/merchant/filter', 'payment_read'],
            ['POST', 'admin/parcel/search-delivery-man-assing-multiple-parcel', 'parcel_read'],
            ['POST', 'admin/parcel/search-expense', 'expense_create|expense_update|salary_create|salary_update'],
            ['POST', 'admin/parcel/search-income', 'income_create|income_update|cash_received_from_delivery_man_create|cash_received_from_delivery_man_update'],
            ['POST', 'admin/parcel/received-warehouse-hub-selected', 'parcel_status_update'],
            ['GET', 'admin/parcel/bulkassign/print', 'parcel_read'],
            ['POST', 'admin/transertohub-selected-hub', 'parcel_status_update'],
            ['POST', 'admin/parcel/recived-by-hub/search', 'parcel_status_update'],
            ['POST', 'admin/parcel/priority/update', 'parcel_update'],
            ['GET', 'admin/parcel/deliveryMan/show', 'parcel_read'],
            ['POST', 'admin/parcel/delivery-category', 'parcel_create|parcel_update'],
            ['POST', 'admin/parcel/quote', 'parcel_create|parcel_update'],
            ['POST', 'admin/parcel/import/merchant', 'parcel_create'],
            ['POST', 'admin/get-merchant-cod', 'parcel_create|parcel_update'],
            ['POST', 'admin/accounts/current-balance', 'fund_transfer_create|fund_transfer_update'],
            ['GET', 'admin/parcel/specific/search', 'parcel_read'],
            ['POST', 'admin/push-notification/users', 'push_notification_create'],
            // S124 — le droit que le menu latéral lit pour l'entrée de l'écran.
            ['GET', 'admin/googlemap-settings/index', 'notification_settings_read'],
            ['PUT', 'admin/googlemap-settings/update', 'notification_settings_update'],
            ['GET', 'admin/subscription/history', 'subscription_read'],
            ['GET', 'admin/paid/invoice', 'paid_invoice_read'],
            ['GET', 'admin/reports/mhd-pdf', 'merchant_hub_deliveryman'],
            // S125 — chaque aide AJAX porte les droits des écrans qui l'appellent (vues + `custom.js`).
            ['POST', 'admin/parcel/search', 'parcel_status_update'],
            ['POST', 'admin/assign-pickup/parcel/search', 'parcel_status_update'],
            ['POST', 'admin/assign-return-to-merchant/parcel/search', 'parcel_status_update'],
            ['POST', 'admin/parcel/merchant', 'salary_create|salary_update|cash_received_from_delivery_man_create|cash_received_from_delivery_man_update|salary_generate_create|salary_generate_update|income_create|income_update|parcel_wise_profit|parcel_status_reports|merchant_hub_deliveryman|parcel_total_summery|salary_reports|expense_create|expense_update|wallet_request_read|parcel_create|parcel_read|parcel_update|payout_read'],
            ['POST', 'admin/parcel/hub', 'income_create|income_update|cash_received_from_delivery_man_create|cash_received_from_delivery_man_update|merchant_hub_deliveryman'],
            ['POST', 'admin/merchant/account', 'payment_read|payment_create|payment_update'],
            ['POST', 'admin/merchant/search', 'payment_read|payment_create|payment_update'],
        ];

        return array_combine(array_map(fn ($c) => $c[0] . ' ' . $c[1], $cas), $cas);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    /* ─────────────────────────────── le filet ───────────────────────────── */

    /** Aucune route `admin/*` ne reste dans un état indéterminé. */
    public function test_every_back_office_route_is_guarded_exempted_or_backlogged(): void
    {
        $inconnues = [];
        $comptees = 0;
        foreach ($this->routesDuBackOffice() as $cle => $droits) {
            $comptees++;
            if (!blank($droits)) { continue; }
            if (array_key_exists($cle, self::EXEMPTEES)) { continue; }
            if (in_array($cle, self::HERITAGE, true)) { continue; }
            $inconnues[] = $cle;
        }

        sort($inconnues);
        $this->assertSame([], $inconnues,
            "des routes du back-office n'ont ni garde, ni motif d'exemption, ni ligne à l'arriéré :\n"
            . implode("\n", $inconnues));
        $this->assertGreaterThan(400, $comptees,
            'le filet ne compte presque rien : les routes ne sont probablement pas montées');
    }

    /** L'arriéré ne grandit pas. */
    public function test_the_backlog_never_grows(): void
    {
        $this->assertLessThanOrEqual(self::PLAFOND_HERITAGE, count(self::HERITAGE),
            'l\'arriéré a grandi : une route nouvelle se garde, elle ne s\'ajoute pas à la liste d\'attente');
    }

    /**
     * Le cliquet dans l'autre sens : une ligne de l'arriéré dont la route est
     * maintenant gardée doit être RETIRÉE. Sans ce test, la liste vieillirait en
     * silence et le plafond ne voudrait plus rien dire.
     */
    public function test_the_backlog_holds_no_route_that_is_already_guarded(): void
    {
        $routes = $this->routesDuBackOffice();
        $perimees = [];
        foreach (self::HERITAGE as $cle) {
            if (!array_key_exists($cle, $routes)) {
                $perimees[] = "$cle (n'existe plus)";
            } elseif (!blank($routes[$cle])) {
                $perimees[] = "$cle (déjà gardée)";
            }
        }

        $this->assertSame([], $perimees,
            "des lignes de l'arriéré sont périmées :\n" . implode("\n", $perimees));
    }

    /** Et une exemption doit désigner une route qui existe, et rester nue. */
    public function test_every_exemption_still_designates_a_bare_route(): void
    {
        $routes = $this->routesDuBackOffice();
        $perimees = [];
        foreach (self::EXEMPTEES as $cle => $motif) {
            if (!array_key_exists($cle, $routes)) {
                $perimees[] = "$cle (n'existe plus)";
            } elseif (!blank($routes[$cle])) {
                $perimees[] = "$cle (gardée entre-temps : retirer l'exemption)";
            }
            $this->assertNotEmpty($motif, "l'exemption de $cle n'a pas de motif");
        }

        $this->assertSame([], $perimees,
            "des exemptions sont périmées :\n" . implode("\n", $perimees));
    }

    /* ───────────────── les gardes posées, prouvées par HTTP ─────────────── */

    /**
     * @dataProvider gardesPosees
     */
    public function test_a_route_of_this_lot_refuses_an_operator_without_the_right(
        string $methode, string $uri, string $droits
    ): void {
        $this->actingAs($this->agent([]));

        $this->call($methode, self::HOTE . '/' . $uri, $this->corps());

        $this->assertTrue($this->refusDeDroit(),
            "{$methode} /{$uri} sert un opérateur sans aucun droit");
    }

    /**
     * Le contrôle négatif, moitié indispensable : avec **l'un** des droits, la
     * route ne refuse plus.
     *
     * ⚠️ Le discriminateur n'est pas « 200 » mais **le message flashé** de S39 : un
     * refus de droit se reconnaît à `message.permission_denied`, et rien d'autre ne
     * le porte. Comparer à 200 rendrait ce test faux dès qu'une de ces aides
     * répond autre chose sur un corps minimal — ce qui n'a rien à voir avec le
     * droit.
     *
     * @dataProvider gardesPosees
     */
    public function test_the_same_route_answers_with_one_of_its_rights(
        string $methode, string $uri, string $droits
    ): void {
        $premier = explode('|', $droits)[0];
        $this->actingAs($this->agent([$premier]));

        $this->call($methode, self::HOTE . '/' . $uri, $this->corps());

        $this->assertFalse($this->refusDeDroit(),
            "{$methode} /{$uri} refuse un opérateur porteur de {$premier}");
    }

    /**
     * Chaque droit de chaque liste ouvre la route, pas seulement le premier. Sans
     * ce test, une liste `a|b|c` dont seul `a` fonctionne passerait au vert.
     */
    public function test_every_right_of_a_list_opens_its_route(): void
    {
        foreach (self::gardesPosees() as $cle => [$methode, $uri, $droits]) {
            foreach (explode('|', $droits) as $droit) {
                $this->app['auth']->forgetGuards();
                session()->forget('toastr::messages');
                $this->actingAs($this->agent([$droit]));
                $this->call($methode, self::HOTE . '/' . $uri, $this->corps());
                $this->assertFalse($this->refusDeDroit(), "{$cle} refuse le porteur de {$droit}");
            }
        }
    }

    /**
     * La mesure derrière la décision la plus coûteuse du lot, inscrite.
     *
     * `parcel/priority/update` est une **écriture** : elle change la priorité d'un
     * colis. Son écran appelant, l'index des colis, n'exige que `parcel_read` —
     * que portent le rôle Admin, le rôle User ET le chef de hub. La garder par
     * `parcel_update`, que **seul le rôle Admin** porte, retire donc la bascule au
     * rôle User et au chef de hub. C'est voulu, et c'est la même décision que S36
     * a prise pour le clone de colis : ce que le rôle User perd ici, il n'aurait
     * jamais dû l'avoir — un droit de lecture ne fait pas écrire.
     */
    public function test_the_priority_toggle_needs_a_write_right_not_a_read_right(): void
    {
        $this->actingAs($this->agent(['parcel_read']));
        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => 1, 'priority' => 1]);
        $this->assertTrue($this->refusDeDroit(),
            'la bascule de priorité se contente d\'un droit de LECTURE');

        $this->app['auth']->forgetGuards();
        session()->forget('toastr::messages');
        $this->actingAs($this->agent(['parcel_update']));
        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => 1, 'priority' => 1]);
        $this->assertFalse($this->refusDeDroit(),
            'la bascule de priorité refuse un droit d\'écriture');
    }

    /* ─────────────────────────── outillage ──────────────────────────────── */

    /** `"MÉTHODE uri" => liste des droits` pour toute route `admin/*` sous `auth`. */
    private function routesDuBackOffice(): array
    {
        $table = [];
        foreach (Router::getRoutes() as $route) {
            $mw = array_values(array_filter($route->gatherMiddleware(), 'is_string'));
            if (!array_intersect($mw, ['auth', 'auth:web'])) { continue; }
            if (!str_starts_with($route->uri(), 'admin/')) { continue; }
            $droits = array_values(array_map(
                fn ($m) => substr($m, strlen('hasPermission:')),
                array_filter($mw, fn ($m) => str_starts_with($m, 'hasPermission:'))));
            $cle = implode('|', array_values(array_diff($route->methods(), ['HEAD']))) . ' ' . $route->uri();
            $table[$cle] = $droits;
        }

        return $table;
    }

    /** Le discriminateur de S39 : un refus de droit flashe ce message, rien d'autre. */
    private function refusDeDroit(): bool
    {
        $notes = session('toastr::messages');

        return $notes !== null && collect($notes)
            ->contains(fn ($note) => ($note['message'] ?? null) === __('message.permission_denied'));
    }

    /** Un corps minimal : ces aides lisent des identifiants, aucune n'en a besoin pour refuser. */
    private function corps(): array
    {
        return ['id' => 1, 'priority' => 1, 'parcel_id' => 1, 'merchant_id' => 1, 'hub_id' => 1, 'search' => 'x'];
    }

    private function agent(array $droits): User
    {
        static $compteur = 0;
        $compteur++;

        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent porte ' . $compteur;
        $u->email = 'agent.porte.' . $compteur . '@example.test';
        $u->mobile = '00229979' . str_pad((string) $compteur, 5, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::ADMIN;
        $u->role_id = Role::where('company_id', settings()->id)->value('id');
        $u->permissions = $droits;
        $u->save();

        return $u;
    }
}
