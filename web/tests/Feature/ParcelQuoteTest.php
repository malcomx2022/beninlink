<?php

namespace Tests\Feature;

use App\Models\Backend\DeliveryCategory;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Services\Parcel\ChargeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * `POST /api/v10/parcel/quote` — le prix annonce avant creation doit etre celui
 * qui sera enregistre.
 *
 * C'est la contrepartie de la correction de S2 : le calcul est revenu cote
 * serveur, mais l'ecran de creation avait alors perdu l'affichage du prix. Le
 * devis le rend, sans reintroduire un second bareme chez le client — d'ou le
 * test central : **devis == ce que la creation persiste**, meme quand la requete
 * de creation porte un `chargeDetails` mensonger.
 */
class ParcelQuoteTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
    }

    private function payload(): array
    {
        return [
            'category_id' => DeliveryCategory::firstOrFail()->id,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'weight' => 1,
        ];
    }

    public function test_le_devis_reprend_le_calcul_du_serveur(): void
    {
        $merchant = Merchant::firstOrFail();
        Sanctum::actingAs($merchant->user);

        $payload = $this->payload();
        $response = $this->postJson('/api/v10/parcel/quote', $payload, ['apiKey' => self::API_KEY])
            ->assertOk();

        // Reference : le service qui fait foi, appele directement.
        $expected = app(ChargeCalculator::class)->calculate(
            $merchant,
            $payload['delivery_type_id'],
            $payload['category_id'],
            $payload['weight'],
            (float) $payload['cash_collection']
        );

        // Comparaison souple : JSON rend 50.0 sous la forme 50, l'egalite stricte
        // ne dirait rien d'utile ici.
        $data = $response->json('data');
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $data[$key], 'montant ' . $key);
        }

        // Somme rendue par le serveur : le client n'a pas a decider si le
        // sous-total porte la TVA (il ne la porte pas).
        $this->assertEquals(
            $expected['total_delivery_amount'] + $expected['vat_amount'],
            $data['total_payable_charges']
        );
    }

    public function test_le_devis_annonce_ce_que_la_creation_enregistre(): void
    {
        $merchant = Merchant::firstOrFail();
        Sanctum::actingAs($merchant->user);

        $payload = $this->payload();
        $quote = $this->postJson('/api/v10/parcel/quote', $payload, ['apiKey' => self::API_KEY])
            ->assertOk()
            ->json('data');

        $this->postJson('/api/v10/parcel/store', $payload + [
            'shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000001',
            'customer_address' => 'Cotonou, Akpakpa',
            // Le client ment sur les montants : ils doivent rester sans effet.
            'chargeDetails' => json_encode([
                'deliveryChargeAmount' => 0,
                'codChargeAmount' => 0,
                'VatAmount' => 0,
                'currentPayable' => 50000,
            ]),
        ], ['apiKey' => self::API_KEY])->assertOk();

        $parcel = Parcel::latest('id')->firstOrFail();

        $this->assertEquals($quote['delivery_charge'], (float) $parcel->delivery_charge);
        $this->assertEquals($quote['cod_amount'], (float) $parcel->cod_amount);
        $this->assertEquals($quote['vat_amount'], (float) $parcel->vat_amount);
        $this->assertEquals($quote['total_delivery_amount'], (float) $parcel->total_delivery_amount);
        $this->assertEquals($quote['current_payable'], (float) $parcel->current_payable);
    }

    public function test_la_mise_a_jour_recalcule_sans_chargeDetails(): void
    {
        $merchant = Merchant::firstOrFail();
        Sanctum::actingAs($merchant->user);

        $create = $this->payload() + [
            'shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000001',
            'customer_address' => 'Cotonou, Akpakpa',
        ];
        $this->postJson('/api/v10/parcel/store', $create, ['apiKey' => self::API_KEY])->assertOk();
        $parcel = Parcel::latest('id')->firstOrFail();

        // Le montant a encaisser change ; aucun `chargeDetails` n'accompagne la
        // requete, comme depuis que les ecrans affichent le devis au lieu de
        // calculer. Les montants doivent quand meme etre recalcules.
        // `merchant_id` est exige par le socle sur cette route (voir S17) ; les
        // ecrans l'envoient, la valeur est celle du marchand connecte.
        $this->putJson(
            '/api/v10/parcel/update/' . $parcel->id,
            array_merge($create, ['cash_collection' => 20000, 'merchant_id' => $merchant->id]),
            ['apiKey' => self::API_KEY]
        )->assertOk();

        $attendu = $this->postJson('/api/v10/parcel/quote', array_merge($this->payload(), ['cash_collection' => 20000]), ['apiKey' => self::API_KEY])
            ->assertOk()
            ->json('data');

        $parcel->refresh();
        $this->assertEquals($attendu['cod_amount'], (float) $parcel->cod_amount);
        $this->assertEquals($attendu['total_delivery_amount'], (float) $parcel->total_delivery_amount);
        $this->assertEquals($attendu['current_payable'], (float) $parcel->current_payable);
    }

    public function test_le_devis_exige_une_authentification(): void
    {
        $this->postJson('/api/v10/parcel/quote', $this->payload(), ['apiKey' => self::API_KEY])
            ->assertUnauthorized();
    }

    public function test_une_demande_incomplete_est_refusee(): void
    {
        Sanctum::actingAs(Merchant::firstOrFail()->user);

        // Sans type de livraison, aucun tarif n'est applicable.
        $this->postJson('/api/v10/parcel/quote', ['cash_collection' => 1000], ['apiKey' => self::API_KEY])
            ->assertStatus(422);
    }
}
