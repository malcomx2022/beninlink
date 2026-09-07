<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Services\Parcel\DeliveryChargeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S8 / S9 — résolution du tarif de livraison, **par zone**.
 *
 * Le socle cherchait le poids exact, puis retombait sur la première ligne de
 * la catégorie (S9 : un colis lourd au tarif le plus léger) et, côté
 * administration, sans filtrer la société (S8 : le barème d'un autre
 * locataire).
 *
 * L'étape 6 a retiré les quatre colonnes le 2026-09-07, et avec elles le
 * `resolve()` qui les lisait ainsi que les deux points AJAX qui l'appelaient —
 * morts depuis que le devis complet les a remplacés. **Les deux garanties, en
 * revanche, n'ont pas été retirées** : elles valent mot pour mot sur le
 * résolveur par zones, et ce fichier les y vérifie. C'était tout l'objet de
 * cette suite ; changer de modèle n'était pas une raison de la perdre.
 *
 * Une seule chose diffère, et c'est délibéré : là où l'ancien résolveur rendait
 * **0** quand il ne trouvait rien, celui-ci rend **`null`**. Zéro est un prix ;
 * l'absence de prix n'en est pas un, et l'appelant doit refuser plutôt que de
 * facturer gratuitement.
 */
class DeliveryChargeResolverTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    private int $categoryId;

    private DeliveryZone $cotonou;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY, 'app.app_installed' => 'yes']);

        $this->merchant = Merchant::firstOrFail();
        Sanctum::actingAs($this->merchant->user, ['merchant']);

        $this->categoryId = DeliveryCategory::firstOrFail()->id;
        $societe = settings()->id;
        $this->cotonou = DeliveryZone::where('company_id', $societe)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();

        // On repart d'un barème connu, à trois tranches : jusqu'à 1, 3 et 5 kg.
        DeliveryCharge::query()->delete();
        foreach ([1 => 500, 3 => 900, 5 => 1500] as $poids => $prix) {
            $this->bareme($societe, $this->cotonou->id, $poids, $prix);
        }

        // Un autre locataire, moins cher, avec la seule ligne « 10 kg » qui
        // existe : le socle l'aurait servie à l'administration (S8). Sa zone
        // lui appartient, et c'est ce qui doit l'écarter.
        $autre = new GeneralSettings();
        $autre->forceFill(['name' => 'Autre locataire', 'status' => Status::ACTIVE, 'currency' => 'XOF'])->save();
        $zoneAilleurs = DeliveryZone::create([
            'company_id' => $autre->id,
            'code' => DeliveryZone::COTONOU,
            'name' => 'Cotonou',
            'position' => 0,
            'status' => Status::ACTIVE,
        ]);
        $this->bareme($autre->id, $zoneAilleurs->id, 10, 100);
    }

    private function bareme(int $societe, int $zoneId, int $poids, int $prix): void
    {
        DeliveryCharge::forceCreate([
            'company_id' => $societe,
            'category_id' => $this->categoryId,
            'zone_id' => $zoneId,
            'weight' => $poids,
            'amount' => $prix,
            'position' => $poids,
            'status' => Status::ACTIVE,
        ]);
    }

    private function tarif($poids, ?int $zoneId = null): ?float
    {
        return app(DeliveryChargeResolver::class)->resolveByZone(
            $this->merchant->id,
            $this->categoryId,
            $poids,
            $zoneId ?? $this->cotonou->id,
        );
    }

    public function test_exact_weight_gives_its_tier(): void
    {
        $this->assertSame(500.0, $this->tarif(1));
        $this->assertSame(900.0, $this->tarif(3));
        $this->assertSame(1500.0, $this->tarif(5));
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

    /**
     * S8, dans le modèle par zones : une zone appartient à une société, et
     * c'est le rattachement qui écarte le barème du voisin. Le colis de 10 kg
     * ne trouve donc jamais la ligne à 100 F de l'autre locataire.
     */
    public function test_another_tenants_grid_is_never_served(): void
    {
        $this->assertSame(1500.0, $this->tarif(10));

        $ailleurs = DeliveryZone::where('company_id', '!=', settings()->id)->firstOrFail();
        $this->assertSame(100.0, $this->tarif(10, $ailleurs->id), 'la zone du voisin sert bien son propre barème');
    }

    public function test_the_merchant_grid_wins_then_falls_back_to_the_company(): void
    {
        MerchantDeliveryCharge::forceCreate([
            'company_id' => settings()->id,
            'merchant_id' => $this->merchant->id,
            'category_id' => $this->categoryId,
            'zone_id' => $this->cotonou->id,
            'delivery_charge_id' => DeliveryCharge::first()->id,
            'weight' => 3,
            'amount' => 700,
            'status' => Status::ACTIVE,
        ]);

        $this->assertSame(700.0, $this->tarif(3));   // négocié, poids exact
        $this->assertSame(700.0, $this->tarif(2));   // négocié, tranche supérieure
        $this->assertSame(1500.0, $this->tarif(5));  // au-delà du négocié → société
    }

    /**
     * Sans grille, le résolveur rend `null` et non `0`. C'est le changement
     * assumé de l'étape 6 : facturer zéro est une décision, ne pas savoir
     * facturer en est une autre — et seule la seconde doit arrêter la création.
     */
    public function test_no_grid_means_null_not_a_free_delivery(): void
    {
        DeliveryCharge::query()->delete();

        $this->assertNull($this->tarif(2));
        $this->assertNull(app(DeliveryChargeResolver::class)->resolveByZone(
            $this->merchant->id, null, 2, $this->cotonou->id,
        ));
    }

    public function test_the_quote_endpoint_uses_the_same_resolution(): void
    {
        $data = $this->postJson('/api/v10/parcel/quote', [
            'category_id' => $this->categoryId,
            'delivery_type_id' => 1,
            'cash_collection' => 10000,
            'weight' => 10,
            'zone_id' => $this->cotonou->id,
        ], ['apiKey' => self::API_KEY])->assertOk()->json('data');

        $this->assertEquals(1500, $data['delivery_charge']);
    }
}
