<?php

namespace Tests\Feature;

use App\Enums\AccountHeads;
use App\Models\Backend\DeliveryCategory;
use App\Models\Backend\Fraud;
use App\Models\Backend\Merchant;
use App\Models\Config;
use App\Models\Backend\DeliveryZone;
use App\Services\Parcel\ChargeCalculator;
use App\Services\Parcel\VatRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Décisions métier du 2026-09-05 (docs/DECISIONS_METIER.md) : ce qui se
 * règle en code — TVA au niveau société, chapitre des dépenses
 * d'acquisition, rattachement des fiches de fraude orphelines.
 */
class BusinessDecisionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $this->merchant = Merchant::firstOrFail();
        Sanctum::actingAs($this->merchant->user, ['merchant']);
    }

    private function tauxSociete(?float $taux): void
    {
        Config::companywise()->where('key', VatRate::CONFIG_KEY)->delete();
        if ($taux !== null) {
            $config = new Config();
            $config->company_id = settings()->id;
            $config->key = VatRate::CONFIG_KEY;
            $config->value = $taux;
            $config->save();
        }
    }

    /**
     * Depuis l'étape 6 (D4), un colis sans zone n'a pas de tarif : le devis
     * passe donc par une route. Ce qui est testé ici reste la **TVA**, pas le
     * tarif — la zone n'est qu'un préalable devenu obligatoire.
     */
    private function devis(): array
    {
        $zone = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();

        return app(ChargeCalculator::class)->calculate(
            $this->merchant->fresh(), DeliveryCategory::firstOrFail()->id, 1, 10000,
            null, false, $zone->id,
        );
    }

    // ---- TVA -------------------------------------------------------------

    public function test_the_seed_gives_every_company_the_benin_rate(): void
    {
        $this->assertSame(18.0, VatRate::company());
    }

    public function test_a_merchant_without_a_rate_gets_the_company_rate(): void
    {
        $this->merchant->vat = 0;
        $this->merchant->save();
        $this->tauxSociete(18);

        $this->assertSame(18.0, VatRate::for($this->merchant->fresh()));
        $devis = $this->devis();
        $this->assertSame(18.0, $devis['vat']);

        // L'assiette est bien celle de la société, à 18 % — mais le montant est
        // **arrondi au franc** depuis que `ChargeCalculator::percentage()` le fait :
        // le FCFA n'a pas de centime. Cette assertion figeait auparavant le produit
        // brut (28,799999…), c'est-à-dire l'arithmétique plutôt que la règle.
        $this->assertEquals(round($devis['total_delivery_amount'] * 0.18), $devis['vat_amount']);
    }

    public function test_a_merchant_with_a_rate_keeps_it(): void
    {
        $this->merchant->vat = 5;
        $this->merchant->save();
        $this->tauxSociete(18);

        $this->assertSame(5.0, VatRate::for($this->merchant->fresh()));
        $this->assertSame(5.0, $this->devis()['vat']);
    }

    public function test_without_any_rate_nothing_changes_from_the_original_behaviour(): void
    {
        $this->merchant->vat = 0;
        $this->merchant->save();
        $this->tauxSociete(null);

        $this->assertSame(0.0, VatRate::for($this->merchant->fresh()));
        $this->assertSame(0.0, $this->devis()['vat_amount']);
    }

    public function test_the_quote_endpoint_reports_the_resolved_rate(): void
    {
        $this->merchant->vat = 0;
        $this->merchant->save();
        $this->tauxSociete(18);

        $this->postJson('/api/v10/parcel/quote', [
            'category_id' => DeliveryCategory::firstOrFail()->id,
            'delivery_type_id' => 1,
            'cash_collection' => 10000,
            'weight' => 1,
            // D4, étape 6 : le devis se refuse sans route.
            'zone_id' => DeliveryZone::where('code', DeliveryZone::COTONOU)->firstOrFail()->id,
        ], ['apiKey' => self::API_KEY])->assertOk()->assertJsonPath('data.vat', 18);
    }

    public function test_the_vat_migration_is_idempotent_and_keeps_an_existing_rate(): void
    {
        $this->tauxSociete(7);

        (require database_path('migrations/2026_09_05_110000_add_company_vat_rate_config.php'))->up();

        $this->assertSame(1, Config::companywise()->where('key', VatRate::CONFIG_KEY)->count());
        $this->assertSame(7.0, VatRate::company());
    }

    public function test_the_settings_page_still_compiles(): void
    {
        $source = file_get_contents(resource_path('views/backend/liquid_fragile/index.blade.php'));
        $this->assertStringContainsString('vat_rate', $source);
        $compiled = Blade::compileString($source);
        $this->assertSame(substr_count($compiled, '<?php if('), substr_count($compiled, '<?php endif; ?>'));
    }

    // ---- CAC -------------------------------------------------------------

    public function test_the_acquisition_account_head_exists_once(): void
    {
        $query = DB::table('account_heads')->where('type', AccountHeads::EXPENSE)->where('name', 'Marketing et acquisition clients');
        $this->assertSame(1, $query->count());

        (require database_path('migrations/2026_09_05_130000_add_acquisition_account_head.php'))->up();
        $this->assertSame(1, $query->count());

        // Le mot-clé « acquisition » du reporting le reconnaît.
        $this->assertTrue(collect(config('saas_reporting.cac_account_heads'))
            ->contains(fn ($mot) => str_contains(mb_strtolower('Marketing et acquisition clients'), $mot)));
    }

    // ---- Fraude ----------------------------------------------------------

    public function test_orphan_fraud_reports_are_attached_to_their_authors_company(): void
    {
        $orpheline = Fraud::forceCreate([
            'company_id' => null,
            'created_by' => $this->merchant->user_id,
            'phone' => '0022997000123',
            'name' => 'Ancienne fiche',
        ]);
        $sansAuteur = Fraud::forceCreate([
            'company_id' => null,
            'created_by' => null,
            'phone' => '0022997000124',
            'name' => 'Sans auteur',
        ]);

        (require database_path('migrations/2026_09_05_120000_backfill_fraud_company_id.php'))->up();

        $this->assertSame((int) $this->merchant->user->company_id, (int) $orpheline->fresh()->company_id);
        $this->assertNull($sansAuteur->fresh()->company_id);

        // Et elle réapparaît dans la liste noire de la société.
        $this->getJson('/api/v10/fraud/index', ['apiKey' => self::API_KEY])->assertOk()
            ->assertJsonFragment(['phone' => '0022997000123']);
    }
}
