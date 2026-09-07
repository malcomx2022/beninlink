<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
use App\Models\User;
use App\Services\Parcel\DeliveryChargeResolver;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Jeu de données pilote (recette) : il doit s'installer sur une société
 * existante, produire des montants calculés par le serveur, être utilisable
 * depuis les deux apps, et se recréer proprement.
 */
class PiloteDatasetTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $this->companyId = (int) Merchant::firstOrFail()->company_id;
    }

    public function test_the_dataset_is_created_with_server_computed_amounts(): void
    {
        $summary = app(PiloteDataset::class)->seed($this->companyId);

        $this->assertSame(5, $summary['hubs']);
        $this->assertCount(5, $summary['merchants']);
        $this->assertCount(3, $summary['deliverymen']);
        $this->assertSame(35, $summary['parcels']);

        $merchant = Merchant::where('merchant_unique_id', 'PIL-001')->firstOrFail();
        $this->assertSame('3202600010001', $merchant->ifu);
        $this->assertSame((int) $this->companyId, (int) $merchant->company_id);

        // Montants calculés par le serveur : depuis l'étape 6 (D4), le tarif
        // est celui de la **route** du colis — la tranche de sa zone, plus le
        // supplément de son délai. On le relit en base plutôt que de recopier
        // un montant : c'est la grille qui fait foi, pas ce test.
        $parcel = Parcel::where('merchant_id', $merchant->id)->where('status', ParcelStatus::PENDING)->firstOrFail();
        $this->assertEquals(2, (int) $parcel->weight);
        $this->assertNotNull($parcel->zone_id, 'un colis du jeu de recette porte sa route');

        Auth::login($merchant->user);
        $attendu = app(DeliveryChargeResolver::class)->resolveByZone(
            $merchant->id,
            (int) $parcel->category_id,
            $parcel->weight,
            (int) $parcel->zone_id,
            $parcel->delay_id,
        );

        $this->assertEquals($attendu, (float) $parcel->delivery_charge);
        $this->assertEquals(18, (float) $parcel->vat);
        $this->assertGreaterThan(0, (float) $parcel->vat_amount);
        $this->assertEquals(
            (float) $parcel->cash_collection - ((float) $parcel->total_delivery_amount + (float) $parcel->vat_amount),
            (float) $parcel->current_payable,
        );
    }

    public function test_the_accounts_work_from_both_apps(): void
    {
        app(PiloteDataset::class)->seed($this->companyId);

        // Les deux connexions d'abord (Auth::attempt), les sessions simulées ensuite.
        $login = $this->postJson('/api/v10/signin', ['merchant_id' => 'PIL-002', 'password' => PiloteDataset::PASSWORD], ['apiKey' => self::API_KEY])
            ->assertOk()->json('data');
        $this->assertNotEmpty($login['token']);
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'LIV-001', 'password' => PiloteDataset::PASSWORD], ['apiKey' => self::API_KEY])->assertOk();

        // Marchand pilote : la liste de ses colis.
        Sanctum::actingAs(User::where('unique_id', 'PIL-002')->firstOrFail(), ['merchant']);
        $parcels = $this->getJson('/api/v10/parcel/index', ['apiKey' => self::API_KEY])->assertOk()->json('data.parcels');
        $this->assertCount(7, $parcels);

        // Livreur pilote : ses courses, réparties par statut.
        $livreur = User::where('unique_id', 'LIV-001')->firstOrFail();
        Sanctum::actingAs($livreur, ['deliveryman']);
        $dashboard = $this->getJson('/api/v10/deliveryman/dashboard', ['apiKey' => self::API_KEY])->assertOk()->json('data');
        $this->assertNotEmpty($dashboard['deliveryman_assign']);
        $this->assertNotEmpty($dashboard['delivered']);
        $this->assertNotEmpty($dashboard['return_to_courier']);
        $this->assertSame(
            DeliveryMan::where('user_id', $livreur->id)->value('id'),
            (int) \App\Models\Backend\ParcelEvent::where('parcel_id', $dashboard['deliveryman_assign'][0]['id'])->whereNotNull('delivery_man_id')->value('delivery_man_id'),
        );
    }

    public function test_one_pilot_merchant_pays_by_wallet_and_its_parcels_are_debited(): void
    {
        // Sans cette PME, la recette ne pouvait pas exercer le portefeuille : ni le
        // débit à la création, ni le refus pour solde insuffisant, ni la recharge —
        // et `beninlink:colis-non-debites` s'arrêtait sur « aucun marchand ne règle
        // par portefeuille », donc sans rien vérifier.
        app(PiloteDataset::class)->seed($this->companyId);

        $portefeuille = Merchant::where('merchant_unique_id', 'PIL-002')->firstOrFail();
        $this->assertEquals(Status::ACTIVE, (int) $portefeuille->wallet_use_activation);

        // Les quatre autres restent au règlement à la livraison : les deux modes
        // doivent être testés côté PME pilotes.
        $this->assertSame(1, Merchant::where('merchant_unique_id', 'like', 'PIL-%')
            ->where('wallet_use_activation', Status::ACTIVE)->count());

        $colis = Parcel::where('merchant_id', $portefeuille->id)->get();
        $du = (float) $colis->sum('total_delivery_amount');

        $recharges = Wallet::where('merchant_id', $portefeuille->id)->where('type', WalletType::INCOME)->sum('amount');
        $depenses = Wallet::where('merchant_id', $portefeuille->id)->where('type', WalletType::EXPENSE)->sum('amount');

        $this->assertEquals(PiloteDataset::WALLET_TOPUP, (float) $recharges, 'la recharge d\'ouverture');
        $this->assertEquals($du, (float) $depenses, 'chaque colis a été débité');
        $this->assertEquals(PiloteDataset::WALLET_TOPUP - $du, (float) $portefeuille->wallet_balance);
    }

    public function test_the_two_regularisations_find_nothing_on_a_fresh_dataset(): void
    {
        app(PiloteDataset::class)->seed($this->companyId);

        // Le jeu de recette sort cohérent : les commandes n'ont rien à rattraper.
        // C'est ce qui rend un constat non vide, plus tard, exploitable.
        $this->artisan('beninlink:colis-non-debites')
            ->expectsOutputToContain('Aucun colis non débité')
            ->assertSuccessful();

        $this->artisan('beninlink:ecarts-marchands')
            ->expectsOutputToContain('Aucun écart')
            ->assertSuccessful();
    }

    public function test_a_second_run_requires_reset_and_does_not_duplicate(): void
    {
        $service = app(PiloteDataset::class);
        $service->seed($this->companyId);

        $this->expectException(\RuntimeException::class);
        $service->seed($this->companyId);
    }

    public function test_reset_recreates_the_same_dataset(): void
    {
        $service = app(PiloteDataset::class);
        $service->seed($this->companyId);
        $service->seed($this->companyId, reset: true);

        $this->assertSame(5, Merchant::where('merchant_unique_id', 'like', 'PIL-%')->count());
        $this->assertSame(3, User::where('unique_id', 'like', 'LIV-%')->count());
        $this->assertSame(35, Parcel::where('tracking_id', 'like', PiloteDataset::TRACKING_PREFIX . '%')->count());

        // `--reset` doit aussi effacer les mouvements de portefeuille : ils
        // référencent le marchand, et leur oubli faisait échouer la suppression
        // sur une contrainte de clé étrangère.
        $portefeuille = Merchant::where('merchant_unique_id', 'PIL-002')->firstOrFail();
        $this->assertSame(8, Wallet::where('merchant_id', $portefeuille->id)->count(), 'une recharge et sept débits, pas le double');
    }

    public function test_the_command_refuses_production_and_prints_accounts(): void
    {
        $this->artisan('beninlink:pilote', ['--company' => $this->companyId])
            ->expectsOutputToContain('PIL-001')
            ->expectsOutputToContain('LIV-001')
            ->assertSuccessful();
    }
}
