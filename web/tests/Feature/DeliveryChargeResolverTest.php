<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Backend\MerchantPanel\MerchantParcelController;
use App\Http\Controllers\Backend\ParcelController;
use App\Models\Backend\DeliveryCategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\User;
use App\Services\Parcel\DeliveryChargeResolver;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S8 / S9 — résolution du tarif de livraison.
 *
 * Le socle cherchait le poids exact, puis retombait sur la première ligne de
 * la catégorie (S9 : un colis lourd au tarif le plus léger) et, côté
 * administration, sans filtrer la société (S8 : le barème d'un autre
 * locataire). Un seul résolveur sert désormais le calculateur, les deux AJAX
 * des écrans et l'import CSV.
 */
class DeliveryChargeResolverTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY, 'app.app_installed' => 'yes']);

        $this->merchant = Merchant::firstOrFail();
        Sanctum::actingAs($this->merchant->user, ['merchant']);

        $this->categoryId = DeliveryCategory::firstOrFail()->id;
        $societe = settings()->id;

        // Le barème du seed vaut pour la société 2 ; on repart d'un barème
        // connu, à trois tranches : jusqu'à 1 kg, 3 kg et 5 kg.
        DeliveryCharge::query()->delete();
        foreach ([1 => 500, 3 => 900, 5 => 1500] as $poids => $prix) {
            $this->bareme($societe, $poids, $prix);
        }

        // Un autre locataire, moins cher, avec la seule ligne « 10 kg » qui
        // existe : le socle l'aurait servie à l'administration (S8).
        $autre = new GeneralSettings();
        $autre->forceFill(['name' => 'Autre locataire', 'status' => Status::ACTIVE, 'currency' => 'XOF'])->save();
        $this->bareme($autre->id, 10, 100);
    }

    private function bareme(int $societe, int $poids, int $prix): void
    {
        DeliveryCharge::forceCreate([
            'company_id' => $societe,
            'category_id' => $this->categoryId,
            'weight' => $poids,
            'same_day' => $prix,
            'next_day' => $prix + 10,
            'sub_city' => $prix + 20,
            'outside_city' => $prix + 30,
            'position' => $poids,
            'status' => Status::ACTIVE,
        ]);
    }

    private function tarif($poids, int $type = 1): float
    {
        return app(DeliveryChargeResolver::class)->resolve($this->merchant->id, $this->categoryId, $poids, $type);
    }

    public function test_exact_weight_and_delivery_type_column(): void
    {
        $this->assertSame(500.0, $this->tarif(1, 1));
        $this->assertSame(910.0, $this->tarif(3, 2));
        $this->assertSame(1520.0, $this->tarif(5, 3));
        $this->assertSame(1530.0, $this->tarif(5, 4));
    }

    public function test_a_heavier_parcel_never_pays_a_lighter_tier(): void
    {
        // S9 : le socle rendait la première ligne (1 kg → 500) pour 2 kg.
        $this->assertSame(900.0, $this->tarif(2));
        $this->assertSame(900.0, $this->tarif(2.5));
        $this->assertSame(1500.0, $this->tarif(4));
    }

    public function test_beyond_the_grid_the_heaviest_tier_applies(): void
    {
        // 10 kg : aucune tranche ≥ 10 dans notre société. Le socle aurait
        // pris 1 kg (S9) — ou la ligne 10 kg de l'autre locataire (S8).
        $this->assertSame(1500.0, $this->tarif(10));
        $this->assertSame(1500.0, $this->tarif(50));
    }

    public function test_the_merchant_grid_wins_then_falls_back_to_the_company(): void
    {
        MerchantDeliveryCharge::forceCreate([
            'company_id' => settings()->id,
            'merchant_id' => $this->merchant->id,
            'category_id' => $this->categoryId,
            'delivery_charge_id' => DeliveryCharge::first()->id,
            'weight' => 3,
            'same_day' => 700,
            'next_day' => 710,
            'sub_city' => 720,
            'outside_city' => 730,
            'status' => Status::ACTIVE,
        ]);

        $this->assertSame(700.0, $this->tarif(3));   // négocié, poids exact
        $this->assertSame(700.0, $this->tarif(2));   // négocié, tranche supérieure
        $this->assertSame(1500.0, $this->tarif(5));  // au-delà du négocié → société
    }

    public function test_no_grid_means_zero_not_an_error(): void
    {
        DeliveryCharge::query()->delete();
        $this->assertSame(0.0, $this->tarif(2));
        $this->assertSame(0.0, app(DeliveryChargeResolver::class)->resolve($this->merchant->id, null, 2, 1));
    }

    public function test_the_quote_endpoint_uses_the_same_resolution(): void
    {
        $data = $this->postJson('/api/v10/parcel/quote', [
            'category_id' => $this->categoryId,
            'delivery_type_id' => 1,
            'cash_collection' => 10000,
            'weight' => 10,
        ], ['apiKey' => self::API_KEY])->assertOk()->json('data');

        $this->assertEquals(1500, $data['delivery_charge']);
    }

    /**
     * Les routes du locataire (`parcel/delivery-charge`) ne sont enregistrées
     * que pour un hôte présent dans `domains` au démarrage ; en test on appelle
     * donc l'action directement, avec une requête AJAX liée au conteneur.
     */
    private function ajax(array $params): Request
    {
        $request = Request::create('/parcel/delivery-charge', 'POST', $params, [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->app->instance('request', $request);

        return $request;
    }

    public function test_the_admin_ajax_quote_is_scoped_and_weight_aware(): void
    {
        $this->actingAs(User::where('user_type', UserType::ADMIN)->firstOrFail());
        $request = $this->ajax([
            'merchant_id' => $this->merchant->id,
            'category_id' => $this->categoryId,
            'weight' => 10,
            'delivery_type_id' => 1,
        ]);

        // Avant : 100 (barème d'un autre locataire, S8) ou 500 (1 kg, S9).
        $montant = app(ParcelController::class)->deliveryCharge($request, app(DeliveryChargeResolver::class));
        $this->assertEquals(1500, $montant);
    }

    public function test_the_merchant_panel_ajax_quote_ignores_a_foreign_merchant_id(): void
    {
        $this->actingAs($this->merchant->user);
        $request = $this->ajax([
            'merchant_id' => $this->merchant->id + 999,
            'category_id' => $this->categoryId,
            'weight' => 2,
            'delivery_type_id' => 1,
        ]);

        $montant = app(MerchantParcelController::class)->deliveryCharge($request, app(DeliveryChargeResolver::class));
        $this->assertEquals(900, $montant);
    }

    public function test_the_ajax_quote_refuses_non_ajax_calls(): void
    {
        $this->actingAs($this->merchant->user);
        $request = Request::create('/parcel/delivery-charge', 'POST', ['category_id' => $this->categoryId, 'weight' => 2, 'delivery_type_id' => 1]);
        $this->app->instance('request', $request);

        $this->assertSame(0, app(MerchantParcelController::class)->deliveryCharge($request, app(DeliveryChargeResolver::class)));
    }
}
