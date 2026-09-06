<?php

namespace Tests\Feature;

use App\Exceptions\UnpricedDeliveryException;
use App\Http\Requests\MerchantPanel\Parcel\StoreRequest as StoreRequestMarchand;
use App\Http\Requests\Parcel\StoreRequest as StoreRequestAdmin;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Services\Parcel\ChargeCalculator;
use App\Services\Pricing\ZoneGridConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D4, étape 5 bis** — le colis porte sa route, et la grille facture enfin.
 *
 * Les étapes 1 à 5 avaient tout livré sauf le maillon : `resolveByZone()`
 * n'avait **aucun appelant en production**, parce qu'un colis ne savait pas
 * dire dans quelle zone il va ni sous quel délai. La grille se saisissait, se
 * servait, s'affichait — elle ne facturait pas.
 *
 * Ce que ces tests tiennent :
 *
 *  - **sans zone, rien ne change.** C'est la promesse tenue depuis l'étape 1, et
 *    `DeliveryPricingBaselineTest` en reste la preuve, jamais modifié ;
 *  - **avec une zone, c'est la grille qui facture** — montant de la zone plus le
 *    supplément du délai, et le taux COD de la zone ;
 *  - **une route non tarifée est refusée**, jamais devinée. Le calcul lève, et
 *    la validation l'attrape avant, pour que l'opérateur voie un message sur le
 *    bon champ plutôt qu'une erreur serveur.
 */
class ParcelZoneRouteTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        $this->categoryId = DeliveryCategory::firstOrFail()->id;

        Auth::login($this->merchant->user);
    }

    private function convertir(): void
    {
        app(ZoneGridConverter::class)->convert(
            (int) $this->merchant->company_id,
            ZoneGridConverter::SAME_DAY_SURCHARGE
        );
    }

    private function zone(string $code): DeliveryZone
    {
        return DeliveryZone::companywise()->where('code', $code)->firstOrFail();
    }

    private function calculer(array $route = []): array
    {
        return app(ChargeCalculator::class)->calculate(
            $this->merchant,
            $route['delivery_type_id'] ?? 2,
            $this->categoryId,
            $route['weight'] ?? 1,
            $route['cash_collection'] ?? 10000,
            null,
            false,
            $route['zone_id'] ?? null,
            $route['delay_id'] ?? null,
            $route['country'] ?? null,
        );
    }

    // ---- Le colis sait porter sa route ------------------------------------

    public function test_le_colis_porte_une_zone_et_un_delai(): void
    {
        $this->assertTrue(Schema::hasColumn('parcels', 'zone_id'));
        $this->assertTrue(Schema::hasColumn('parcels', 'delay_id'));

        // Additive : `delivery_type_id` reste, un colis sans zone est celui d'avant.
        $this->assertTrue(Schema::hasColumn('parcels', 'delivery_type_id'));
    }

    // ---- Sans zone, rien ne change ----------------------------------------

    public function test_sans_zone_le_calcul_est_celui_du_bareme_herite(): void
    {
        $this->convertir();

        $herite = DeliveryCharge::companywise()->whereNull('zone_id')
            ->where('category_id', $this->categoryId)->where('weight', 1)->firstOrFail();

        // `delivery_type_id` = 2 → colonne `next_day`, comme depuis toujours.
        $this->assertSame((float) $herite->next_day, $this->calculer()['delivery_charge']);
    }

    // ---- Avec une zone, c'est la grille qui facture ------------------------

    public function test_avec_une_zone_le_tarif_vient_de_la_grille(): void
    {
        $this->convertir();

        $herite = DeliveryCharge::companywise()->whereNull('zone_id')
            ->where('category_id', $this->categoryId)->where('weight', 1)->firstOrFail();

        $peripherie = $this->calculer(['zone_id' => $this->zone(DeliveryZone::PERIPHERIE)->id]);

        // La conversion a repris `sub_city` pour la Périphérie : le colis est
        // désormais facturé par la ligne de zone, pas par la colonne.
        $this->assertSame((float) $herite->sub_city, $peripherie['delivery_charge']);
    }

    public function test_le_supplement_du_delai_sajoute_au_tarif_de_la_zone(): void
    {
        $this->convertir();

        $zoneId = $this->zone(DeliveryZone::COTONOU)->id;
        $jourMeme = DeliveryDelay::companywise()->where('code', DeliveryDelay::SAME_DAY)->firstOrFail();

        $sans = $this->calculer(['zone_id' => $zoneId])['delivery_charge'];
        $avec = $this->calculer(['zone_id' => $zoneId, 'delay_id' => $jourMeme->id])['delivery_charge'];

        $this->assertSame((float) ZoneGridConverter::SAME_DAY_SURCHARGE, $avec - $sans);
    }

    public function test_le_taux_cod_suit_la_zone_et_non_le_type_de_livraison(): void
    {
        $this->convertir();

        $taux = $this->merchant->cod_charges;

        // `delivery_type_id` = 4 donnerait « hors ville » ; la zone Cotonou dit
        // « intra-ville ». C'est la zone qui gagne.
        $charges = $this->calculer([
            'delivery_type_id' => 4,
            'zone_id' => $this->zone(DeliveryZone::COTONOU)->id,
        ]);

        $this->assertSame((float) $taux['inside_city'], $charges['cod_charge']);
        $this->assertNotSame((float) $taux['outside_city'], $charges['cod_charge']);
    }

    public function test_la_cedeao_facture_le_forfait_du_pays(): void
    {
        $this->convertir();
        $cedeao = $this->zone(DeliveryZone::CEDEAO);

        // Le forfait du Togo vient de la décision du métier, posée par la
        // conversion : rien à saisir ici.
        $charges = $this->calculer(['zone_id' => $cedeao->id, 'country' => 'TG', 'weight' => 10]);

        // Un forfait ne regarde pas le poids : 10 kg au prix du pays.
        $this->assertSame(12000.0, $charges['delivery_charge']);
        // Et le taux COD de cette zone est le sien, pas celui de « hors ville ».
        $this->assertSame((float) $this->merchant->cod_charges['cedeao'], $charges['cod_charge']);
    }

    // ---- Une route non tarifée est refusée --------------------------------

    public function test_un_pays_non_tarife_fait_echouer_le_calcul(): void
    {
        $this->convertir();

        $this->expectException(UnpricedDeliveryException::class);

        // Le Ghana n'a pas de forfait : on refuse plutôt que d'emprunter le
        // montant d'un voisin, ce qui reviendrait à inventer un prix.
        $this->calculer(['zone_id' => $this->zone(DeliveryZone::CEDEAO)->id, 'country' => 'GH']);
    }

    public function test_une_zone_sans_grille_fait_echouer_le_calcul(): void
    {
        $vierge = DeliveryZone::create([
            'company_id' => $this->merchant->company_id,
            'code' => 'zone_vierge',
            'name' => 'Zone sans tarif',
            'position' => 9,
            'status' => \App\Enums\Status::ACTIVE,
        ]);

        $this->expectException(UnpricedDeliveryException::class);
        $this->calculer(['zone_id' => $vierge->id]);
    }

    // ---- La validation prend le relais avant le calcul --------------------

    private function valider(array $donnees, string $classe): \Illuminate\Contracts\Validation\Validator
    {
        $this->app->instance('request', Request::create('/parcel/store', 'POST', $donnees));

        return Validator::make($donnees, (new $classe())->rules());
    }

    public function test_la_validation_refuse_une_route_non_tarifee(): void
    {
        $this->convertir();

        $base = [
            'merchant_id' => $this->merchant->id,
            'category_id' => $this->categoryId,
            'delivery_type_id' => 2,
            'customer_name' => 'Aïcha Kora',
            'customer_address' => 'Cotonou, Akpakpa',
            'customer_phone' => '0022997000041',
            'weight' => 1,
            'zone_id' => $this->zone(DeliveryZone::CEDEAO)->id,
            // Le Ghana n'a pas de forfait : c'est le cas que la règle doit
            // attraper, sur le champ `zone_id`, avant que le calcul ne lève.
            'destination_country' => 'GH',
            'customs_category' => 'general',
        ];

        $validation = $this->valider($base, StoreRequestAdmin::class);

        $this->assertTrue($validation->fails());
        $this->assertArrayHasKey('zone_id', $validation->errors()->toArray());
    }

    public function test_la_validation_laisse_passer_une_route_tarifee(): void
    {
        $this->convertir();

        $validation = $this->valider([
            'merchant_id' => $this->merchant->id,
            'category_id' => $this->categoryId,
            'delivery_type_id' => 2,
            'customer_name' => 'Aïcha Kora',
            'customer_address' => 'Cotonou, Akpakpa',
            'customer_phone' => '0022997000041',
            'weight' => 1,
            'zone_id' => $this->zone(DeliveryZone::COTONOU)->id,
        ], StoreRequestAdmin::class);

        $this->assertFalse($validation->fails(), (string) $validation->errors());
    }

    public function test_sans_zone_la_validation_ne_dit_rien(): void
    {
        // Le chemin hérité doit rester exempt : la règle ne s'applique qu'à un
        // colis qui a explicitement choisi une zone.
        $validation = $this->valider([
            'shop_id' => 1,
            'category_id' => $this->categoryId,
            'delivery_type_id' => 2,
            'customer_name' => 'Aïcha Kora',
            'customer_address' => 'Cotonou, Akpakpa',
            'customer_phone' => '0022997000041',
        ], StoreRequestMarchand::class);

        $this->assertFalse($validation->fails(), (string) $validation->errors());
    }
}
