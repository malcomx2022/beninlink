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
use App\Services\Pricing\ZoneCatalog;
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

    /** Barème du jeu pilote : jour même, lendemain, périphérie, intérieur. */
    private const PILOTE = [
        1 => [1000, 800, 1500, 2500],
        3 => [1500, 1200, 2000, 3500],
        5 => [2000, 1700, 2800, 4500],
        10 => [3000, 2500, 4000, 6500],
    ];

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

    /**
     * Depuis l'étape 6, le jeu d'amorçage installe déjà les quatre zones : le
     * code est unique par société, on reprend donc celle qui existe plutôt que
     * d'en créer une seconde.
     */
    private function zone(string $code, string $nom): DeliveryZone
    {
        return $this->zones[$code] ??= DeliveryZone::firstOrCreate(
            ['company_id' => $this->merchant->company_id, 'code' => $code],
            ['name' => $nom, 'position' => count($this->zones), 'status' => Status::ACTIVE],
        );
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
        $delai = DeliveryDelay::firstOrNew(
            ['company_id' => $this->merchant->company_id, 'code' => $code],
        );
        $delai->company_id = $this->merchant->company_id;
        $delai->code = $code;
        $delai->name = $code;
        $delai->surcharge = $supplement;
        $delai->position = 0;
        $delai->status = Status::ACTIVE;
        $delai->save();

        return $delai;
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
        // Les forfaits du métier sont déjà posés par l'installation : ce test
        // fixe les siens pour rester lisible sans dépendre de leurs montants.
        $forfait = fn (string $code, string $nom, int $montant) => DeliveryZoneCountry::updateOrCreate(
            ['zone_id' => $cedeao->id, 'code' => $code],
            ['name' => $nom, 'flat_amount' => $montant, 'status' => Status::ACTIVE],
        );
        $forfait('TG', 'Togo', 15000);
        $forfait('NG', 'Nigeria', 25000);

        $resolveur = app(DeliveryChargeResolver::class);

        // Le poids ne joue pas : 500 g ou 12 kg, c'est le forfait du pays.
        $this->assertEquals(15000, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 0.5, $cedeao->id, null, 'TG'));
        $this->assertEquals(15000, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 12, $cedeao->id, null, 'tg'));
        $this->assertEquals(25000, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 3, $cedeao->id, null, 'NG'));

        // Un pays sans forfait n'est pas facturé au hasard : l'appelant doit le voir
        // (la Guinée : le Ghana a son forfait depuis S106).
        $this->assertNull($resolveur->resolveByZone($this->merchant->id, $this->categoryId, 3, $cedeao->id, null, 'GN'));
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

    public function test_le_taux_cod_suit_la_zone(): void
    {
        $this->merchant->cod_charges = [
            'inside_city' => '1', 'sub_city' => '2', 'outside_city' => '3', 'cedeao' => '4',
        ];
        $this->merchant->save();

        $calcul = app(\App\Services\Parcel\ChargeCalculator::class);

        $this->assertEquals(1, $calcul->codRateForZone($this->merchant, $this->zone(DeliveryZone::COTONOU, 'Cotonou')));
        $this->assertEquals(2, $calcul->codRateForZone($this->merchant, $this->zone(DeliveryZone::PERIPHERIE, 'Périphérie')));
        $this->assertEquals(3, $calcul->codRateForZone($this->merchant, $this->zone(DeliveryZone::INTERIEUR, 'Intérieur')));

        // La zone d'export a sa propre clé depuis le 2026-09-06 : chaque zone
        // lit la sienne, aucune n'emprunte le taux d'une autre.
        $this->assertEquals(4, $calcul->codRateForZone($this->merchant, $this->zone(DeliveryZone::CEDEAO, 'CEDEAO')));
    }

    /**
     * La migration pose le taux **manquant**, et ne touche pas à celui qui
     * existe : un taux négocié ne se réécrit pas parce qu'on a passé une
     * migration. C'est la même règle que pour les forfaits par pays.
     */
    public function test_la_migration_pose_le_taux_manquant_sans_ecraser_lexistant(): void
    {
        $migration = require base_path('database/migrations/2026_09_06_230000_add_cedeao_cod_rate_to_merchants.php');

        // 1. Un marchand d'avant la décision : la clé lui manque.
        $this->merchant->cod_charges = ['inside_city' => '1', 'sub_city' => '2', 'outside_city' => '3'];
        $this->merchant->save();

        $migration->up();

        $this->assertSame('3', $this->merchant->fresh()->cod_charges['cedeao'], 'le taux par défaut comble ce qui manque');

        // 2. Le même, taux négocié : un second passage ne doit pas l'écraser.
        $negocie = $this->merchant->fresh();
        $negocie->cod_charges = ['inside_city' => '1', 'sub_city' => '2', 'outside_city' => '3', 'cedeao' => '7'];
        $negocie->save();

        $migration->up();

        $this->assertSame('7', $negocie->fresh()->cod_charges['cedeao'], 'un taux négocié survit à la migration');
    }

    /**
     * Un marchand dont la clé n'existe pas — créé avant la décision et jamais
     * repris par la migration — reste à zéro. On ne lui invente pas un taux.
     */
    public function test_un_marchand_sans_clé_cedeao_reste_a_zero(): void
    {
        $this->merchant->cod_charges = ['inside_city' => '1', 'sub_city' => '2', 'outside_city' => '3'];
        $this->merchant->save();

        $calcul = app(\App\Services\Parcel\ChargeCalculator::class);

        $this->assertEquals(0, $calcul->codRateForZone($this->merchant, $this->zone(DeliveryZone::CEDEAO, 'CEDEAO')));
    }

    /**
     * L'installation des zones est **rejouable**. Elle l'était comme
     * conversion, elle doit le rester comme installation : un déploiement qui
     * la relance ne doit pas doubler les zones ni les forfaits.
     */
    public function test_l_installation_des_zones_est_rejouable(): void
    {
        $arguments = ['--societe' => $this->merchant->company_id, '--supplement' => 300, '--installer' => true];
        $this->artisan('beninlink:zones-tarifaires', $arguments)->assertSuccessful();
        $this->artisan('beninlink:zones-tarifaires', $arguments)->assertSuccessful();

        $this->assertSame(4, DeliveryZone::where('company_id', $this->merchant->company_id)->count(), 'pas de zones en double');
        $this->assertSame(3, DeliveryDelay::where('company_id', $this->merchant->company_id)->count(), 'pas de délais en double');
        $cedeao = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::CEDEAO)->firstOrFail();
        $this->assertSame(count(\App\Services\Pricing\ZoneCatalog::PAYS), DeliveryZoneCountry::where('zone_id', $cedeao->id)->count(), 'pas de forfaits en double');
    }
}
