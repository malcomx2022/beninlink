<?php

namespace Tests\Feature;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\DeliveryCategory;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use App\Models\Backend\DeliveryZone;
use Tests\TestCase;

/**
 * Chantier 5 — alertes douanieres UEMOA / CEDEAO.
 *
 * Trois comportements, dans l'ordre du DAT §2.5 :
 *   - un colis domestique n'est pas concerne ;
 *   - un export couvert par une regle INFO ou AVERTISSEMENT est cree, avec une
 *     alerte a traiter ;
 *   - un export couvert par une regle BLOQUANTE est REFUSE (422), et aucun colis
 *     n'est ecrit.
 *
 * Le refus est verifie cote serveur : c'est la lecon de S2, une regle qui ne
 * vit que dans l'ecran ne protege rien.
 */
class CustomsAlertTest extends TestCase
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

    private function colis(array $extra = []): array
    {
        return array_merge([
            'shop_id' => MerchantShops::firstOrFail()->id,
            'category_id' => DeliveryCategory::firstOrFail()->id,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('code', DeliveryZone::COTONOU)->value('id'),
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000001',
            'customer_address' => 'Cotonou, Akpakpa',
            'cash_collection' => 50000,
        ], $extra);
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    public function test_un_colis_domestique_ne_declenche_aucune_alerte(): void
    {
        $this->postJson('/api/v10/parcel/store', $this->colis(), $this->entetes())->assertOk();

        $this->assertSame(0, CustomsAlert::count());
    }

    public function test_un_export_avec_avertissement_cree_le_colis_et_l_alerte(): void
    {
        // Togo + textile : AVERTISSEMENT au referentiel livre.
        $this->postJson('/api/v10/parcel/store', $this->colis([
            'destination_country' => 'TG',
            'customs_category' => 'textile',
        ]), $this->entetes())->assertOk();

        $parcel = Parcel::latest('id')->firstOrFail();
        $alert = CustomsAlert::firstOrFail();

        $this->assertEquals($parcel->id, $alert->parcel_id);
        $this->assertEquals($this->merchant->id, $alert->merchant_id);
        $this->assertEquals(CustomsLevel::WARNING, $alert->level);
        $this->assertEquals(CustomsAlertStatus::PENDING, $alert->status);
        $this->assertNotEmpty($alert->required_document);
    }

    /**
     * S82 (M1) — l'alerte se lit SUR LE COLIS, par son détail et par son suivi.
     *
     * Deux exports, deux alertes : chaque colis ne porte que la sienne. Un colis
     * domestique porte un tableau vide, pas une absence de clé : l'app lit
     * `customs_alerts` sans condition.
     */
    public function test_le_detail_et_le_suivi_d_un_colis_portent_ses_alertes_douanieres(): void
    {
        $this->postJson('/api/v10/parcel/store', $this->colis([
            'destination_country' => 'TG', 'customs_category' => 'textile',
        ]), $this->entetes())->assertOk();
        $togo = Parcel::latest('id')->firstOrFail();

        $this->postJson('/api/v10/parcel/store', $this->colis([
            'destination_country' => 'TG', 'customs_category' => 'textile', 'customer_name' => 'Second export',
        ]), $this->entetes())->assertOk();
        $second = Parcel::latest('id')->firstOrFail();

        $this->postJson('/api/v10/parcel/store', $this->colis(), $this->entetes())->assertOk();
        $domestique = Parcel::latest('id')->firstOrFail();

        $this->assertSame(2, CustomsAlert::count());
        $alerte = CustomsAlert::where('parcel_id', $togo->id)->firstOrFail();

        foreach (['details', 'logs'] as $lecture) {
            $this->getJson("/api/v10/parcel/{$lecture}/{$togo->id}", $this->entetes())
                ->assertOk()
                ->assertJsonCount(1, 'data.customs_alerts')
                ->assertJsonPath('data.customs_alerts.0.id', $alerte->id)
                ->assertJsonPath('data.customs_alerts.0.parcel_id', $togo->id)
                ->assertJsonPath('data.customs_alerts.0.level', CustomsLevel::WARNING)
                ->assertJsonPath('data.customs_alerts.0.status', CustomsAlertStatus::PENDING)
                ->assertJsonPath('data.customs_alerts.0.required_document', $alerte->required_document);

            // Le second export a SA propre alerte : rien de l'autre colis ne fuit.
            $this->getJson("/api/v10/parcel/{$lecture}/{$second->id}", $this->entetes())
                ->assertOk()
                ->assertJsonCount(1, 'data.customs_alerts')
                ->assertJsonPath('data.customs_alerts.0.parcel_id', $second->id);

            $this->getJson("/api/v10/parcel/{$lecture}/{$domestique->id}", $this->entetes())
                ->assertOk()
                ->assertJsonPath('data.customs_alerts', []);
        }
    }

    public function test_un_export_bloquant_est_refuse_et_ne_cree_rien(): void
    {
        // Nigeria + produits alimentaires : BLOQUANT (certificat NAFDAC).
        $this->postJson('/api/v10/parcel/store', $this->colis([
            'destination_country' => 'NG',
            'customs_category' => 'alimentaire',
        ]), $this->entetes())->assertStatus(422);

        $this->assertSame(0, Parcel::count(), 'un colis a ete cree malgre le blocage');
        $this->assertSame(0, CustomsAlert::count());
    }

    public function test_l_export_exige_une_categorie(): void
    {
        // Sans categorie, aucune regle ne s'appliquerait : le blocage se
        // contournerait en omettant le champ.
        $this->postJson('/api/v10/parcel/store', $this->colis([
            'destination_country' => 'NG',
        ]), $this->entetes())->assertStatus(422);

        $this->assertSame(0, Parcel::count());
    }

    public function test_le_devis_annonce_la_regle_douaniere(): void
    {
        $reponse = $this->postJson('/api/v10/parcel/quote', [
            'category_id' => DeliveryCategory::firstOrFail()->id,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('code', DeliveryZone::COTONOU)->value('id'),
            'cash_collection' => 50000,
            'destination_country' => 'NG',
            'customs_category' => 'alimentaire',
        ], $this->entetes())->assertOk();

        $reponse->assertJsonPath('data.customs.blocking', true);
        $reponse->assertJsonPath('data.customs.level', CustomsLevel::BLOCKING);
        $this->assertNotEmpty($reponse->json('data.customs.required_document'));
    }

    public function test_le_devis_ne_signale_rien_sur_un_colis_domestique(): void
    {
        $this->postJson('/api/v10/parcel/quote', [
            'category_id' => DeliveryCategory::firstOrFail()->id,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('code', DeliveryZone::COTONOU)->value('id'),
            'cash_collection' => 50000,
        ], $this->entetes())->assertOk()->assertJsonPath('data.customs', null);
    }

    public function test_le_referentiel_liste_les_pays_et_categories(): void
    {
        $reponse = $this->getJson('/api/v10/customs/reference', $this->entetes())->assertOk();

        $this->assertCount(8, $reponse->json('data.countries'), 'les 8 destinations du DAT');
        $this->assertNotEmpty($reponse->json('data.categories'));
    }

    public function test_une_alerte_peut_etre_marquee_traitee(): void
    {
        $this->postJson('/api/v10/parcel/store', $this->colis([
            'destination_country' => 'TG',
            'customs_category' => 'textile',
        ]), $this->entetes())->assertOk();

        $alert = CustomsAlert::firstOrFail();

        $this->putJson('/api/v10/customs/alerts/' . $alert->id . '/resolve', [], $this->entetes())
            ->assertOk()
            ->assertJsonPath('data.alert.status', CustomsAlertStatus::RESOLVED);

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_l_alerte_d_un_autre_marchand_est_hors_de_portee(): void
    {
        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin@example.test';
        $autreUtilisateur->mobile = '0022997000009';
        $autreUtilisateur->unique_id = 'U-VOISIN';
        $autreUtilisateur->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $autreUtilisateur->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->save();

        $alerteDuVoisin = CustomsAlert::create([
            'company_id' => $voisin->company_id,
            'merchant_id' => $voisin->id,
            'country_code' => 'TG',
            'country_name' => 'Togo',
            'goods_category' => 'textile',
            'level' => CustomsLevel::WARNING,
            'required_document' => "Declaration d'exportation UEMOA",
            'message' => 'Test',
            'status' => CustomsAlertStatus::PENDING,
        ]);

        $this->getJson('/api/v10/customs/alerts', $this->entetes())
            ->assertOk()
            ->assertJsonCount(0, 'data.alerts');

        $this->putJson('/api/v10/customs/alerts/' . $alerteDuVoisin->id . '/resolve', [], $this->entetes())
            ->assertNotFound();
    }
}
