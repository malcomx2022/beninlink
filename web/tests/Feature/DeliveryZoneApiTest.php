<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Services\Pricing\ZoneGridConverter;
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

    private function convertir(float $supplement = ZoneGridConverter::SAME_DAY_SURCHARGE): void
    {
        Auth::login($this->merchant->user);
        app(ZoneGridConverter::class)->convert((int) $this->merchant->company_id, $supplement);
        Auth::logout();
    }

    // ---- La transition -----------------------------------------------------

    public function test_sans_zones_le_contrat_herite_ne_bouge_pas(): void
    {
        $reponse = $this->appel('settings/delivery-charges');

        // Le tableau est là, vide ou non, avec ses clés d'origine.
        $reponse->assertJsonStructure(['data' => ['deliveryCharges', 'zones', 'delays']]);
        $this->assertSame([], $reponse->json('data.zones'));
        $this->assertSame([], $reponse->json('data.delays'));
    }

    public function test_les_quatre_colonnes_restent_servies_apres_la_bascule(): void
    {
        // Un barème négocié, forme héritée : c'est ce que lit un APK déjà posé.
        $negocie = $this->chargeNegociee([
            'weight' => 1,
            'same_day' => 1000, 'next_day' => 800, 'sub_city' => 1500, 'outside_city' => 2500,
        ]);

        $this->convertir();

        $ligne = $this->appel('settings/delivery-charges')->json('data.deliveryCharges.0');

        foreach (['id', 'merchant_id', 'category_id', 'delivery_charge_id', 'category', 'weight',
            'same_day', 'next_day', 'sub_city', 'outside_city', 'status', 'statusName'] as $cle) {
            $this->assertArrayHasKey($cle, $ligne, "clé {$cle} disparue du contrat hérité");
        }
        // Même valeur, même forme qu'avant : c'est ce que lit l'APK déjà posé.
        $this->assertSame((string) $negocie->fresh()->same_day, $ligne['same_day']);
    }

    // ---- Ce que `zones` annonce -------------------------------------------

    public function test_les_zones_portent_leur_grille_et_leurs_forfaits(): void
    {
        $this->convertir();

        $cedeao = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::CEDEAO)->firstOrFail();

        Auth::login($this->merchant->user);
        app(DeliveryZoneInterface::class)->enregistrerPays($cedeao, [
            ['id' => '', 'code' => 'TG', 'name' => 'Togo', 'flat_amount' => 12000],
        ]);
        Auth::logout();

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
        // Le montant attendu est celui du barème en base : la conversion
        // reprend `next_day` pour Cotonou, sans rien déplacer.
        $herite = DeliveryCharge::where('company_id', $this->merchant->company_id)
            ->whereNull('zone_id')->where('weight', 1)->firstOrFail();
        $this->assertSame((string) (int) $herite->next_day, collect($cotonou['rates'])->firstWhere('weight', '1')['amount']);

        // La zone d'export : pas de poids, un forfait par pays. `cod_key` est
        // nul — un encaissement à l'étranger n'est pas encore tarifé, et le
        // calcul rend 0 plutôt que d'emprunter le taux « hors ville ».
        $export = $zones[DeliveryZone::CEDEAO];
        $this->assertTrue($export['export']);
        $this->assertNull($export['cod_key']);
        $this->assertSame([['code' => 'TG', 'name' => 'Togo', 'flat_amount' => '12000']], $export['countries']);
    }

    public function test_le_supplement_de_delai_est_servi_une_fois_pour_toutes_les_zones(): void
    {
        $this->convertir();

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
        $this->convertir();

        $cotonou = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();

        // Barème négocié : il l'emporte sur celui de la société, exactement
        // comme dans `DeliveryChargeResolver::resolveByZone()`.
        $this->chargeNegociee(['zone_id' => $cotonou->id, 'weight' => 1, 'amount' => 650]);

        $zones = collect($this->appel('settings/delivery-charges')->json('data.zones'))->keyBy('code');
        $this->assertSame('650', collect($zones[DeliveryZone::COTONOU]['rates'])->firstWhere('weight', '1')['amount']);

        // Les autres zones gardent le barème de la société.
        $herite = DeliveryCharge::where('company_id', $this->merchant->company_id)
            ->whereNull('zone_id')->where('weight', 1)->firstOrFail();
        $this->assertSame((string) (int) $herite->sub_city, collect($zones[DeliveryZone::PERIPHERIE]['rates'])->firstWhere('weight', '1')['amount']);
    }

    public function test_une_zone_dune_autre_societe_nest_jamais_servie(): void
    {
        $this->convertir();

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
