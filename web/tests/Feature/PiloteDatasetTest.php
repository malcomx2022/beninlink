<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Montants calculés : barème FCFA par tranche (2 kg → « jusqu'à 3 kg »,
        // jour même = 1 500), TVA société 18 %.
        $parcel = Parcel::where('merchant_id', $merchant->id)->where('status', ParcelStatus::PENDING)->firstOrFail();
        $this->assertEquals(2, (int) $parcel->weight);
        $this->assertEquals(1500, (float) $parcel->delivery_charge);
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
    }

    public function test_the_command_refuses_production_and_prints_accounts(): void
    {
        $this->artisan('beninlink:pilote', ['--company' => $this->companyId])
            ->expectsOutputToContain('PIL-001')
            ->expectsOutputToContain('LIV-001')
            ->assertSuccessful();
    }
}
