<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Backend\ExpenseController;
use App\Http\Controllers\Backend\MerchantShopsController;
use App\Http\Controllers\Backend\ParcelController;
use App\Http\Controllers\Backend\SupportController;
use App\Models\Backend\Account;
use App\Models\Backend\Department;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Support;
use App\Models\MerchantShops;
use App\Models\User;
use App\Repositories\Account\AccountInterface;
use App\Repositories\MerchantShops\ShopsInterface;
use App\Repositories\Support\SupportInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S23 à S26 — les quatre fuites du back-office relevées par l'inventaire des
 * routes à paramètre (« 📋 Inventaire » de `CARTOGRAPHIE.md`).
 *
 * Toutes ont la même forme, celle de **S22** : la LISTE est scopée par société,
 * le DÉTAIL fait `find($id)` nu. Changer l'identifiant dans l'URL suffisait.
 *
 * Les routes du back-office ne sont montées qu'avec un domaine de locataire :
 * on exerce donc les dépôts et les contrôleurs directement, et on lit la
 * déclaration des routes. C'est la méthode d'`OnlinePayoutModuleDisabledTest` et
 * d'`ActivityLogAccessTest`.
 *
 * ⚠️ Ce commentaire ajoutait « hors de portée d'un test ». C'était faux : il
 * suffit de semer le domaine. `Tests\Concerns\MountsTenantRoutes` le fait, et
 * c'est ce qui a permis de prouver S28 par un appel HTTP. Les tests ci-dessous
 * gardent leur forme — ils prouvent ce qu'ils prouvent — mais un test NEUF sur
 * le back-office devrait passer par le trait et appeler la route pour de vrai.
 *
 * Repère : les données semées appartiennent à la **société 2**, et `settings()`
 * vaut la **société 1** dans un test. Le voisin d'une autre société est donc
 * déjà là ; c'est le nôtre qu'il faut créer.
 */
class BackOfficeScopingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    /* ─────────────── S23 — le ticket de support et son fil ──────────────── */

    public function test_a_support_ticket_of_another_company_is_out_of_reach(): void
    {
        $repo = app(SupportInterface::class);

        $mien = $this->ticketDe($this->agentDe(settings()->id));
        $autre = $this->ticketDe(User::where('company_id', 2)->firstOrFail());

        $this->assertNotNull($repo->get($mien->id), 'mon propre ticket doit rester lisible');
        $this->assertNull($repo->get($autre->id), 'le ticket d une autre société ne doit pas sortir');
    }

    public function test_the_chat_of_another_companys_ticket_is_out_of_reach(): void
    {
        $repo = app(SupportInterface::class);

        $mien = $this->ticketDe($this->agentDe(settings()->id));
        $autre = $this->ticketDe(User::where('company_id', 2)->firstOrFail());
        $this->messageSur($mien, 'Bonjour');
        $this->messageSur($autre, 'Contenu confidentiel du voisin');

        $this->assertCount(1, $repo->chats($mien->id));
        $this->assertCount(0, $repo->chats($autre->id), 'le fil d une autre société ne doit pas sortir');
    }

    public function test_the_support_screens_answer_not_found_out_of_scope(): void
    {
        $autre = $this->ticketDe(User::where('company_id', 2)->firstOrFail());
        $controleur = app(SupportController::class);

        foreach (['view', 'edit'] as $methode) {
            try {
                $controleur->{$methode}($autre->id);
                $this->fail("SupportController::{$methode}() a répondu hors périmètre");
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
    }

    /** Et la liste, elle, n'a pas changé de comportement. */
    public function test_the_support_list_still_shows_my_own_tickets(): void
    {
        $mien = $this->ticketDe($this->agentDe(settings()->id));
        $this->ticketDe(User::where('company_id', 2)->firstOrFail());

        $listes = app(SupportInterface::class)->all()->pluck('id')->all();

        $this->assertContains($mien->id, $listes);
        $this->assertCount(1, $listes);
    }

    /* ──────────────────── S24 — le compte financier ─────────────────────── */

    public function test_a_financial_account_of_another_company_is_out_of_reach(): void
    {
        $repo = app(AccountInterface::class);

        $mien = $this->compteDe(settings()->id, 'Compte BeninLink');
        $autre = $this->compteDe(2, 'Compte du voisin');

        $this->assertNotNull($repo->get($mien->id));
        $this->assertNull($repo->get($autre->id), 'le compte d une autre société ne doit pas sortir');
    }

    /**
     * Le point d'entrée qui l'exposait : `admin/expense/search-account/{id}`,
     * en POST et sans permission, renvoyait le compte tel quel en JSON.
     */
    public function test_the_expense_account_lookup_returns_nothing_out_of_scope(): void
    {
        $autre = $this->compteDe(2, 'Compte du voisin');
        $mien = $this->compteDe(settings()->id, 'Compte BeninLink');

        $controleur = app(ExpenseController::class);

        $this->assertNull($controleur->searchAccount($autre->id));
        $this->assertNotNull($controleur->searchAccount($mien->id));
    }

    /* ─────────────── S25 — la chronologie d'un colis ────────────────────── */

    public function test_the_parcel_timeline_screens_answer_not_found_out_of_scope(): void
    {
        // `ParcelRepository::get()` lit `auth()->user()->hub_id` : sans compte
        // connecté, il tombe avant d'avoir filtré quoi que ce soit.
        $this->actingAs($this->agentDe(settings()->id));

        $autre = $this->colisDe(2);
        $controleur = app(ParcelController::class);

        foreach (['logs', 'deliveredInfo'] as $methode) {
            try {
                $controleur->{$methode}($autre->id);
                $this->fail("ParcelController::{$methode}() a répondu hors périmètre");
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
    }

    /**
     * ⚠️ `ParcelRepository::parcelEvents()` reste **volontairement non scopé**,
     * et ce test l'inscrit pour que personne ne « corrige » la méthode sans
     * comprendre : le **suivi public par numéro** l'appelle
     * (`Frontend\FrontendController`), sans utilisateur authentifié — c'est sa
     * raison d'être, comme `parcelTrack()` au constat S17. La protection vit
     * donc au point d'appel, dans la garde du test précédent.
     */
    public function test_the_timeline_method_stays_unscoped_on_purpose(): void
    {
        $autre = $this->colisDe(2);
        ParcelEvent::forceCreate([
            'parcel_id' => $autre->id,
            'parcel_status' => $autre->status,
            'created_by' => User::where('company_id', 2)->firstOrFail()->id,
        ]);

        $evenements = app(\App\Repositories\Parcel\ParcelInterface::class)->parcelEvents($autre->id);

        $this->assertCount(1, $evenements, 'la méthode elle-même ne filtre pas : le suivi public en dépend');

        // L'invariant est que **aucun** appel de l'écran ne parte de l'identifiant
        // brut : tous passent le colis déjà vérifié. Il s'écrivait « exactement deux
        // appels en `$parcel->id` », ce qui comptait les deux écrans de S25 — et
        // tombait dès qu'un troisième était corrigé (S33 a réparé `details()`).
        // Un compte exact fait échouer le test sur un progrès ; la forme interdite,
        // non.
        $source = file_get_contents(app_path('Http/Controllers/Backend/ParcelController.php'));
        preg_match_all('/->parcelEvents\(([^)]*)\)/', $source, $appels);

        $this->assertNotEmpty($appels[1], 'plus aucun écran n appelle parcelEvents() : ce test n a plus d objet');
        $this->assertSame(
            [],
            array_values(array_diff(array_unique($appels[1]), ['$parcel->id'])),
            'chaque écran doit passer le colis DÉJÀ vérifié, jamais l identifiant brut',
        );
    }

    /* ───────────── S26 — la boutique par défaut d'un marchand ───────────── */

    public function test_the_default_shop_of_another_companys_merchant_cannot_be_changed(): void
    {
        $repo = app(ShopsInterface::class);

        $voisin = Merchant::where('company_id', 2)->firstOrFail();
        $sienne = MerchantShops::where('merchant_id', $voisin->id)->firstOrFail();
        $sienne->default_shop = Status::INACTIVE;
        $sienne->save();

        $this->assertFalse($repo->defaultShop($voisin->id, $sienne->id));
        $this->assertSame(Status::INACTIVE, (int) $sienne->fresh()->default_shop);
    }

    public function test_the_default_shop_of_my_own_merchant_can_be_changed(): void
    {
        [$marchand, $ancienne, $nouvelle] = $this->deuxBoutiquesDe(settings()->id);

        $this->assertTrue(app(ShopsInterface::class)->defaultShop($marchand->id, $nouvelle->id));
        $this->assertSame(Status::ACTIVE, (int) $nouvelle->fresh()->default_shop);
        $this->assertSame(Status::INACTIVE, (int) $ancienne->fresh()->default_shop);
    }

    /**
     * Le socle basculait les anciennes boutiques par défaut **avant** de
     * chercher la nouvelle, puis faisait une erreur fatale sur `null` : le
     * marchand se retrouvait sans boutique par défaut du tout. On vérifie que
     * l'ancienne survit à une demande impossible.
     */
    public function test_an_impossible_request_leaves_the_existing_default_alone(): void
    {
        [$marchand, $ancienne] = $this->deuxBoutiquesDe(settings()->id);

        $this->assertFalse(app(ShopsInterface::class)->defaultShop($marchand->id, 999_999));
        $this->assertSame(Status::ACTIVE, (int) $ancienne->fresh()->default_shop);
    }

    public function test_the_controller_answers_not_found_out_of_scope(): void
    {
        $voisin = Merchant::where('company_id', 2)->firstOrFail();
        $sienne = MerchantShops::where('merchant_id', $voisin->id)->firstOrFail();

        $this->expectException(NotFoundHttpException::class);
        app(MerchantShopsController::class)->defaultShop($voisin->id, $sienne->id);
    }

    /** Et ce n'est plus une écriture déclenchable par un GET. */
    public function test_changing_the_default_shop_is_no_longer_a_get(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString(
            "Route::put('merchant/shops/default/{merchant_id}/{id}'",
            $routes,
            'la route doit être une écriture',
        );
        $this->assertStringNotContainsString("Route::get('merchant/shops/default", $routes);

        // La vue soumet un formulaire signé, plus un lien.
        $vue = file_get_contents(resource_path('views/backend/merchant/shops/index.blade.php'));
        $this->assertStringContainsString("@method('PUT')", $vue);
        $this->assertStringContainsString('@csrf', $vue);
        $this->assertStringNotContainsString("<a href=\"{{ route('merchant.shops.default'", $vue);
    }

    /* ───────────────────────── Les routes mortes ────────────────────────── */

    /**
     * Cinq routes pointaient vers une méthode `view()` qui n'existe pas sur
     * leur contrôleur : elles répondaient 500 à chaque appel. Elles sont
     * retirées — et ce test vérifie **les deux** moitiés du constat, pour qu'on
     * ne puisse pas le « refermer » en ajoutant une méthode vide.
     */
    public function test_the_five_dead_routes_are_gone_and_their_method_still_absent(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $mortes = [
            'accounts/view/{id}' => \App\Http\Controllers\Backend\AccountController::class,
            'delivery-category/view/{id}' => \App\Http\Controllers\Backend\DeliverycategoryController::class,
            'delivery-charge/view/{id}' => \App\Http\Controllers\Backend\DeliveryChargeController::class,
            'fund-transfer/view/{id}' => \App\Http\Controllers\Backend\FundTransferController::class,
            'packaging/view/{id}' => \App\Http\Controllers\Backend\PackagingController::class,
        ];

        foreach ($mortes as $uri => $controleur) {
            $this->assertStringNotContainsString($uri, $routes, "route morte encore déclarée : {$uri}");
            $this->assertFalse(
                method_exists($controleur, 'view'),
                "{$controleur}::view() existe : la route n aurait pas dû être retirée",
            );
        }
    }

    /* ────────────────────────────── Fixtures ───────────────────────────── */

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent ' . $societe;
        $agent->email = "agent{$societe}@example.test";
        $agent->mobile = '00229970001' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function ticketDe(User $auteur): Support
    {
        return Support::forceCreate([
            'user_id' => $auteur->id,
            'department_id' => Department::first()?->id,
            'service' => 'Livraison',
            'priority' => 'high',
            'subject' => 'Ticket de ' . $auteur->id,
            'description' => 'Contenu confidentiel',
            'date' => now()->toDateString(),
        ]);
    }

    private function messageSur(Support $ticket, string $texte): void
    {
        \App\Models\Backend\SupportChat::forceCreate([
            'support_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'message' => $texte,
        ]);
    }

    private function compteDe(int $societe, string $titulaire): Account
    {
        return Account::forceCreate([
            'company_id' => $societe,
            'user_id' => User::where('company_id', $societe)->first()?->id
                ?? $this->agentDe($societe)->id,
            'account_holder_name' => $titulaire,
            'account_no' => '00' . $societe . '1234567',
            'bank' => 'Banque Atlantique',
            'balance' => 500000,
        ]);
    }

    private function colisDe(int $societe): Parcel
    {
        $marchand = Merchant::where('company_id', $societe)->first()
            ?? Merchant::firstOrFail();

        return Parcel::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-' . $societe . '-' . uniqid(),
            'customer_name' => 'Client ' . $societe,
            'customer_phone' => '0022997000200',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'status' => \App\Enums\ParcelStatus::DELIVERED,
        ]);
    }

    /** @return array{0: Merchant, 1: MerchantShops, 2: MerchantShops} */
    private function deuxBoutiquesDe(int $societe): array
    {
        $agent = $this->agentDe($societe);
        $modele = Merchant::firstOrFail();

        $marchand = $modele->replicate();
        $marchand->company_id = $societe;
        $marchand->user_id = $agent->id;
        $marchand->merchant_unique_id = 'M-SOC' . $societe;
        $marchand->save();

        $ancienne = MerchantShops::forceCreate([
            'merchant_id' => $marchand->id,
            'name' => 'Boutique historique',
            'contact_no' => '0022997000301',
            'address' => 'Akpakpa',
            'default_shop' => Status::ACTIVE,
            'status' => Status::ACTIVE,
        ]);
        $nouvelle = MerchantShops::forceCreate([
            'merchant_id' => $marchand->id,
            'name' => 'Boutique Ganhi',
            'contact_no' => '0022997000302',
            'address' => 'Ganhi',
            'default_shop' => Status::INACTIVE,
            'status' => Status::ACTIVE,
        ]);

        return [$marchand, $ancienne, $nouvelle];
    }
}
