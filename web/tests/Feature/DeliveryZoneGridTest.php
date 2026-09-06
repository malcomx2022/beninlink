<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Services\Parcel\DeliveryChargeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D4, étape 1** — le barème par zones, tel que le métier l'a tranché le
 * 2026-09-06 :
 *
 *   - zones **Cotonou, Périphérie, Intérieur, CEDEAO** ;
 *   - **délai global** : un supplément par délai, jamais par zone — c'est ce
 *     qui empêche de revenir aux quatre colonnes d'origine ;
 *   - **CEDEAO au forfait par pays**, sans regarder le poids.
 *
 * La migration est **additive** : tant qu'une société n'a pas de zones, le
 * tarif reste celui des quatre colonnes. `DeliveryPricingBaselineTest`, écrit
 * avant la refonte, le vérifie et ne change pas.
 */
class DeliveryZoneGridTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    private int $categoryId;

    private array $zones = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        $this->categoryId = DeliveryCategory::firstOrFail()->id;
        DeliveryCharge::query()->delete();
    }

    private function zone(string $code, string $nom): DeliveryZone
    {
        return $this->zones[$code] ??= DeliveryZone::create([
            'company_id' => $this->merchant->company_id,
            'code' => $code,
            'name' => $nom,
            'position' => count($this->zones),
            'status' => Status::ACTIVE,
        ]);
    }

    private function tarif(string $codeZone, int $poids, float $montant): void
    {
        DeliveryCharge::forceCreate([
            'company_id' => $this->merchant->company_id,
            'category_id' => $this->categoryId,
            'zone_id' => $this->zones[$codeZone]->id,
            'weight' => $poids,
            'amount' => $montant,
            'position' => $poids,
            'status' => Status::ACTIVE,
        ]);
    }

    private function delai(string $code, float $supplement): DeliveryDelay
    {
        return DeliveryDelay::create([
            'company_id' => $this->merchant->company_id,
            'code' => $code,
            'name' => $code,
            'surcharge' => $supplement,
            'position' => 0,
            'status' => Status::ACTIVE,
        ]);
    }

    public function test_le_tarif_vient_de_la_zone_et_de_la_tranche(): void
    {
        $cotonou = $this->zone(DeliveryZone::COTONOU, 'Cotonou');
        $interieur = $this->zone(DeliveryZone::INTERIEUR, 'Intérieur');
        $this->tarif(DeliveryZone::COTONOU, 1, 800);
        $this->tarif(DeliveryZone::COTONOU, 5, 1700);
        $this->tarif(DeliveryZone::INTERIEUR, 1, 2500);
        $this->tarif(DeliveryZone::INTERIEUR, 5, 4500);

        $resolveur = app(DeliveryChargeResolver::class);

        // Même règle de poids qu'avant : la tranche immédiatement supérieure.
        $this->assertEquals(800, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 0.5, $cotonou->id));
        $this->assertEquals(1700, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 3, $cotonou->id));
        $this->assertEquals(2500, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $interieur->id));

        // Au-delà de la grille, la tranche la plus lourde (S9).
        $this->assertEquals(4500, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 40, $interieur->id));
    }

    public function test_le_supplement_de_delai_est_global_pas_par_zone(): void
    {
        $cotonou = $this->zone(DeliveryZone::COTONOU, 'Cotonou');
        $interieur = $this->zone(DeliveryZone::INTERIEUR, 'Intérieur');
        $this->tarif(DeliveryZone::COTONOU, 1, 800);
        $this->tarif(DeliveryZone::INTERIEUR, 1, 2500);

        $jourMeme = $this->delai(DeliveryDelay::SAME_DAY, 300);
        $standard = $this->delai(DeliveryDelay::STANDARD, 0);

        $resolveur = app(DeliveryChargeResolver::class);

        // Le MÊME supplément des deux côtés : c'est la décision « délai global ».
        $this->assertEquals(1100, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $cotonou->id, $jourMeme->id));
        $this->assertEquals(2800, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $interieur->id, $jourMeme->id));
        $this->assertEquals(800, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $cotonou->id, $standard->id));
    }

    public function test_la_cedeao_se_facture_au_forfait_du_pays_pas_au_poids(): void
    {
        $cedeao = $this->zone(DeliveryZone::CEDEAO, 'CEDEAO');
        DeliveryZoneCountry::create(['zone_id' => $cedeao->id, 'code' => 'TG', 'name' => 'Togo', 'flat_amount' => 15000, 'status' => Status::ACTIVE]);
        DeliveryZoneCountry::create(['zone_id' => $cedeao->id, 'code' => 'NG', 'name' => 'Nigeria', 'flat_amount' => 25000, 'status' => Status::ACTIVE]);

        $resolveur = app(DeliveryChargeResolver::class);

        // Le poids ne joue pas : 500 g ou 12 kg, c'est le forfait du pays.
        $this->assertEquals(15000, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 0.5, $cedeao->id, null, 'TG'));
        $this->assertEquals(15000, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 12, $cedeao->id, null, 'tg'));
        $this->assertEquals(25000, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 3, $cedeao->id, null, 'NG'));

        // Un pays sans forfait n'est pas facturé au hasard : l'appelant doit le voir.
        $this->assertNull($resolveur->resolveByZone($this->merchant->id, $this->categoryId, 3, $cedeao->id, null, 'GH'));
        $this->assertNull($resolveur->resolveByZone($this->merchant->id, $this->categoryId, 3, $cedeao->id));
    }

    public function test_le_bareme_negocie_du_marchand_garde_sa_priorite(): void
    {
        $cotonou = $this->zone(DeliveryZone::COTONOU, 'Cotonou');
        $this->tarif(DeliveryZone::COTONOU, 5, 1700);

        // La ligne négociée référence celle de la société, comme dans le socle
        // (le journal d'activité lit `deliveryCharge.category.title`).
        MerchantDeliveryCharge::forceCreate([
            'merchant_id' => $this->merchant->id,
            'delivery_charge_id' => DeliveryCharge::where('zone_id', $cotonou->id)->value('id'),
            'category_id' => $this->categoryId,
            'zone_id' => $cotonou->id,
            'weight' => 5,
            'amount' => 1200,
            'status' => Status::ACTIVE,
        ]);

        $this->assertEquals(
            1200,
            app(DeliveryChargeResolver::class)->resolveByZone($this->merchant->id, $this->categoryId, 4, $cotonou->id),
        );
    }

    public function test_sans_zone_configuree_rien_ne_change(): void
    {
        // Aucune zone : le nouveau chemin rend `null`, l'appelant retombe sur
        // les quatre colonnes. C'est ce qui rend la migration sans effet tant
        // que personne n'a saisi de grille.
        $this->assertNull(
            app(DeliveryChargeResolver::class)->resolveByZone($this->merchant->id, $this->categoryId, 3, 999999),
        );
    }

    public function test_la_conversion_annonce_l_ecart_que_le_supplement_unique_ne_rend_pas(): void
    {
        // Barème hérité du jeu pilote : l'écart « jour même » vaut 200, 300,
        // 300 puis 500 selon la tranche. Un supplément global ne peut pas
        // reproduire les quatre — la commande doit le dire, pas le masquer.
        foreach ([1 => [1000, 800, 1500, 2500], 3 => [1500, 1200, 2000, 3500], 5 => [2000, 1700, 2800, 4500], 10 => [3000, 2500, 4000, 6500]] as $poids => [$jm, $lend, $peri, $int]) {
            DeliveryCharge::forceCreate([
                'company_id' => $this->merchant->company_id,
                'category_id' => $this->categoryId,
                'weight' => $poids,
                'same_day' => $jm, 'next_day' => $lend, 'sub_city' => $peri, 'outside_city' => $int,
                'position' => $poids, 'status' => Status::ACTIVE,
            ]);
        }

        $this->artisan('beninlink:zones-tarifaires', ['--societe' => $this->merchant->company_id])
            ->expectsOutputToContain('varie de')
            ->assertSuccessful();

        // Constat seul : rien n'est écrit.
        $this->assertSame(0, DeliveryZone::count());

        $this->artisan('beninlink:zones-tarifaires', [
            '--societe' => $this->merchant->company_id,
            '--supplement' => 300,
            '--appliquer' => true,
        ])->assertSuccessful();

        $this->assertSame(4, DeliveryZone::count(), 'les quatre zones');
        $this->assertSame(16, DeliveryCharge::whereNotNull('zone_id')->count(), 'quatre tranches × quatre zones');

        // Les montants convertis sont ceux d'aujourd'hui, colonne par zone.
        $cotonou = DeliveryZone::where('code', DeliveryZone::COTONOU)->firstOrFail();
        $this->assertEquals(
            800,
            DeliveryCharge::where('zone_id', $cotonou->id)->where('weight', 1)->value('amount'),
            'Cotonou reprend le tarif « lendemain »',
        );

        $this->assertEquals(300, DeliveryDelay::where('code', DeliveryDelay::SAME_DAY)->value('surcharge'));

        // Les colonnes héritées sont intactes : le barème d'avant répond encore.
        $this->assertSame(4, DeliveryCharge::whereNull('zone_id')->count());
        $this->assertEquals(1000, DeliveryCharge::whereNull('zone_id')->where('weight', 1)->value('same_day'));
    }

    public function test_la_conversion_est_rejouable(): void
    {
        DeliveryCharge::forceCreate([
            'company_id' => $this->merchant->company_id,
            'category_id' => $this->categoryId,
            'weight' => 1,
            'same_day' => 1000, 'next_day' => 800, 'sub_city' => 1500, 'outside_city' => 2500,
            'position' => 1, 'status' => Status::ACTIVE,
        ]);

        $arguments = ['--societe' => $this->merchant->company_id, '--supplement' => 300, '--appliquer' => true];
        $this->artisan('beninlink:zones-tarifaires', $arguments)->assertSuccessful();
        $this->artisan('beninlink:zones-tarifaires', $arguments)->assertSuccessful();

        $this->assertSame(4, DeliveryZone::count(), 'pas de zones en double');
        $this->assertSame(4, DeliveryCharge::whereNotNull('zone_id')->count(), 'pas de lignes en double');
    }
}
