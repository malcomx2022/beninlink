<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Services\Pricing\ZoneCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D4, étape 5** — le barème par zones entre dans le contrat de l'API.
 *
 * La contrainte n'est pas d'ajouter `zones` : c'est de l'ajouter **sans
 * casser ce qui existe**. Un APK déjà installé ne lit que les quatre colonnes ;
 * le jour où le serveur bascule, il doit continuer d'afficher sa grille. D'où
 * la règle que ces tests tiennent :
 *
 *   - `deliveryCharges` garde exactement sa forme d'avant ;
 *   - `zones` arrive **vide** tant que la société n'a rien configuré — c'est le
 *     signal, pour une app à jour, de rester sur l'ancien affichage ;
 *   - la grille servie annonce ce que `ChargeCalculator` facturera : même
 *     priorité au barème négocié du marchand.
 */
class DeliveryZoneApiTest extends TestCase
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
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();

        // Le jeu d'amorçage donne au marchand de démonstration un barème
        // négocié complet. Ces tests posent le leur : on part d'une table nette
        // pour que ce soit bien la ligne du test qui réponde.
        MerchantDeliveryCharge::query()->delete();
        $this->categoryId = DeliveryCategory::firstOrFail()->id;
    }

    private function appel(string $chemin): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->merchant->user, ['merchant']);

        return $this->getJson('/api/v10/' . $chemin, ['apiKey' => self::API_KEY])->assertOk();
    }

    /**
     * Une ligne de barème négocié.
     *
     * `company_id` n'est pas assignable en masse sur ce modèle du socle : passé
     * par `create()`, il partirait à `null` et `companywise()` ne rendrait
     * jamais la ligne. Le journal d'activité, lui, lit
     * `deliveryCharge.category.title` : sans `delivery_charge_id`, l'écriture
     * lève avant d'atteindre la base.
     */
    private function chargeNegociee(array $attributs): MerchantDeliveryCharge
    {
        $ligne = new MerchantDeliveryCharge();
        $ligne->company_id = $this->merchant->company_id;
        $ligne->merchant_id = $this->merchant->id;
        $ligne->delivery_charge_id = DeliveryCharge::where('company_id', $this->merchant->company_id)
            ->orderBy('weight')->firstOrFail()->id;
        $ligne->category_id = $this->categoryId;
        $ligne->status = Status::ACTIVE;
        $ligne->fill($attributs);
        $ligne->save();

        return $ligne;
    }

    private function convertir(float $supplement = ZoneCatalog::SAME_DAY_SURCHARGE): void
    {
        Auth::login($this->merchant->user);
        app(ZoneCatalog::class)->installer((int) $this->merchant->company_id, $supplement);
        Auth::logout();
    }

    // ---- La transition -----------------------------------------------------

    /**
     * Une société sans zones sert un contrat vide plutôt que d'inventer une
     * grille. Depuis l'étape 6 elle ne peut plus rien facturer non plus — c'est
     * ce que `beninlink:tarification-prete` signale.
     */
    public function test_sans_zones_le_contrat_reste_bien_forme(): void
    {
        DeliveryCharge::query()->delete();
        DeliveryZone::where('company_id', $this->merchant->company_id)->delete();
        DeliveryDelay::where('company_id', $this->merchant->company_id)->delete();

        $reponse = $this->appel('settings/delivery-charges');

        $reponse->assertJsonStructure(['data' => ['deliveryCharges', 'zones', 'delays']]);
        $this->assertSame([], $reponse->json('data.zones'));
        $this->assertSame([], $reponse->json('data.delays'));
    }

    /**
     * Le contrat d'un barème négocié suit le modèle : une zone, un montant.
     *
     * Les quatre colonnes ont été servies aux apps tant qu'un APK posé pouvait
     * les lire. L'étape 6 les retire du barème le 2026-09-07 ; les servir plus
     * longtemps aurait été annoncer un prix que le serveur ne sait plus
     * calculer.
     */
    public function test_le_bareme_negocie_est_servi_par_zone(): void
    {
        $cotonou = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();
        $negocie = $this->chargeNegociee(['zone_id' => $cotonou->id, 'weight' => 1, 'amount' => 800]);

        $ligne = $this->appel('settings/delivery-charges')->json('data.deliveryCharges.0');

        foreach (['id', 'merchant_id', 'category_id', 'delivery_charge_id', 'category', 'weight',
            'zone_id', 'zone_code', 'amount', 'status', 'statusName'] as $cle) {
            $this->assertArrayHasKey($cle, $ligne, "clé {$cle} absente du contrat");
        }
        $this->assertSame((string) $negocie->fresh()->amount, $ligne['amount']);
        $this->assertSame(DeliveryZone::COTONOU, $ligne['zone_code']);
    }

    // ---- Ce que `zones` annonce -------------------------------------------

    public function test_les_zones_portent_leur_grille_et_leurs_forfaits(): void
    {
        // La conversion pose les forfaits tranchés par le métier ; rien à
        // saisir ici, c'est justement ce que le test doit constater.
        $zones = collect($this->appel('settings/delivery-charges')->json('data.zones'))->keyBy('code');

        $this->assertSame(
            [DeliveryZone::COTONOU, DeliveryZone::PERIPHERIE, DeliveryZone::INTERIEUR, DeliveryZone::CEDEAO],
            $zones->keys()->all()
        );

        // Une zone nationale : le tarif au poids, en FCFA entiers.
        $cotonou = $zones[DeliveryZone::COTONOU];
        $this->assertFalse($cotonou['export']);
        $this->assertSame('inside_city', $cotonou['cod_key']);
        $this->assertSame([], $cotonou['countries']);
        // Le montant attendu est celui de la grille zonée en base.
        $grille = DeliveryCharge::where('company_id', $this->merchant->company_id)
            ->where('zone_id', $zones[DeliveryZone::COTONOU]['id'])->where('weight', 1)->firstOrFail();
        $this->assertSame((string) (int) $grille->amount, collect($cotonou['rates'])->firstWhere('weight', '1')['amount']);

        // La zone d'export : pas de poids, un forfait par pays. Elle a
        // désormais sa propre clé COD — `cedeao`, 3 % depuis le 2026-09-06 —
        // au lieu d'emprunter le taux « hors ville ».
        $export = $zones[DeliveryZone::CEDEAO];
        $this->assertTrue($export['export']);
        $this->assertSame('cedeao', $export['cod_key']);
        $this->assertSame([
            ['code' => 'BF', 'name' => 'Burkina Faso', 'flat_amount' => '15000'],
            ['code' => 'NG', 'name' => 'Nigeria', 'flat_amount' => '18000'],
            ['code' => 'TG', 'name' => 'Togo', 'flat_amount' => '12000'],
        ], $export['countries'], 'les forfaits CEDEAO, triés par nom de pays');
    }

    public function test_le_supplement_de_delai_est_servi_une_fois_pour_toutes_les_zones(): void
    {
        $delais = collect($this->appel('settings/delivery-charges')->json('data.delays'))->keyBy('code');

        $this->assertSame('300', $delais['same_day']['surcharge']);
        $this->assertSame('0', $delais['next_day']['surcharge']);

        // Le supplément vit dans `delays`, jamais recopié dans chaque zone :
        // c'est le mélange délai × périmètre que la refonte défait.
        foreach ($this->appel('settings/delivery-charges')->json('data.zones') as $zone) {
            foreach ($zone['rates'] as $tarif) {
                $this->assertArrayNotHasKey('same_day', $tarif);
            }
        }
    }

    public function test_la_grille_servie_est_celle_que_le_marchand_paiera(): void
    {
        $cotonou = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();

        // Barème négocié : il l'emporte sur celui de la société, exactement
        // comme dans `DeliveryChargeResolver::resolveByZone()`.
        $this->chargeNegociee(['zone_id' => $cotonou->id, 'weight' => 1, 'amount' => 650]);

        $zones = collect($this->appel('settings/delivery-charges')->json('data.zones'))->keyBy('code');
        $this->assertSame('650', collect($zones[DeliveryZone::COTONOU]['rates'])->firstWhere('weight', '1')['amount']);

        // Les autres zones gardent le barème de la société.
        $peripherie = DeliveryCharge::where('company_id', $this->merchant->company_id)
            ->where('zone_id', $zones[DeliveryZone::PERIPHERIE]['id'])->where('weight', 1)->firstOrFail();
        $this->assertSame(
            (string) (int) $peripherie->amount,
            collect($zones[DeliveryZone::PERIPHERIE]['rates'])->firstWhere('weight', '1')['amount'],
        );
    }

    public function test_une_zone_dune_autre_societe_nest_jamais_servie(): void
    {
        $voisine = GeneralSettings::findOrFail($this->merchant->company_id)->replicate();
        $voisine->name = 'Transporteur voisin';
        $voisine->save();

        $etrangere = DeliveryZone::create([
            'company_id' => $voisine->id,
            'code' => 'ailleurs',
            'name' => 'Zone du voisin',
            'position' => 9,
            'status' => Status::ACTIVE,
        ]);

        $codes = collect($this->appel('settings/delivery-charges')->json('data.zones'))->pluck('code');
        $this->assertNotContains($etrangere->code, $codes);
    }

    // ---- Le taux COD, rattaché à sa zone -----------------------------------

    public function test_chaque_taux_cod_nomme_sa_zone(): void
    {
        $charges = collect($this->appel('settings/cod-charges')->json('data.codCharges'));

        $this->assertNotEmpty($charges);
        foreach ($charges as $charge) {
            // La clé `charge` d'origine reste : le contrat hérité ne bouge pas.
            $this->assertArrayHasKey('charge', $charge);
            $this->assertArrayHasKey('zone_code', $charge);
        }

        $attendu = array_flip(\App\Services\Parcel\ChargeCalculator::COD_KEY_BY_ZONE);
        $this->assertSame(
            array_values(array_map(fn ($cle) => $attendu[$cle] ?? null, array_keys($this->merchant->cod_charges))),
            $charges->pluck('zone_code')->all()
        );
    }
}
