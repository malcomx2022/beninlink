<?php

namespace Tests\Feature;

use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use App\Models\Backend\DeliveryZone;
use Tests\TestCase;

/**
 * S17 — l'API colis du marchand ne doit rendre, modifier ou supprimer que les
 * colis du marchand connecte.
 *
 * Le socle travaillait sur `Parcel::find($id)` nu dans tout
 * `MerchantParcelRepository` : changer l'identifiant dans l'URL suffisait a lire
 * le colis d'un concurrent (nom, telephone et adresse du destinataire, montants)
 * puis a le modifier, en changer le statut ou le supprimer. `update()` relisait
 * meme `merchant_id` dans le corps de la requete, ce qui permettait de reaffecter
 * un colis a un autre marchand.
 *
 * Chaque test rejoue l'attaque sur une route reelle.
 */
class ParcelScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;
    private Merchant $voisin;
    private Parcel $colisDuVoisin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();

        // Un second marchand de la MEME societe : le scoping par company_id seul
        // ne le protegerait pas.
        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin@example.test';
        $autreUtilisateur->mobile = '0022997000009';
        $autreUtilisateur->unique_id = 'U-VOISIN';
        $autreUtilisateur->save();

        $this->voisin = $this->merchant->replicate();
        $this->voisin->user_id = $autreUtilisateur->id;
        $this->voisin->merchant_unique_id = 'M-VOISIN';
        $this->voisin->save();

        $this->colisDuVoisin = $this->creerColisPour($this->voisin);

        // Toutes les requetes des tests partent du marchand, pas du voisin.
        Sanctum::actingAs($this->merchant->user, ['merchant']);
    }

    private function creerColisPour(Merchant $merchant): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $merchant->company_id,
            'merchant_id' => $merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client du voisin',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Porto-Novo',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('company_id', $merchant->company_id)->where('code', DeliveryZone::COTONOU)->value('id'),
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => 'TEST-VOISIN',
            'status' => \App\Enums\ParcelStatus::PENDING,
        ])->save();

        return $parcel;
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    public function test_le_detail_d_un_colis_etranger_est_refuse(): void
    {
        $this->getJson('/api/v10/parcel/details/' . $this->colisDuVoisin->id, $this->entetes())
            ->assertNotFound();
    }

    public function test_le_suivi_d_un_colis_etranger_est_refuse(): void
    {
        $this->getJson('/api/v10/parcel/logs/' . $this->colisDuVoisin->id, $this->entetes())
            ->assertNotFound();
    }

    public function test_le_statut_d_un_colis_etranger_ne_peut_pas_etre_change(): void
    {
        $this->getJson('/api/v10/parcel/' . $this->colisDuVoisin->id . '/status/2', $this->entetes())
            ->assertNotFound();

        $this->assertEquals(
            \App\Enums\ParcelStatus::PENDING,
            $this->colisDuVoisin->fresh()->status,
            'le statut du colis du voisin a change'
        );
    }

    public function test_un_colis_etranger_ne_peut_pas_etre_modifie(): void
    {
        $this->putJson('/api/v10/parcel/update/' . $this->colisDuVoisin->id, [
            'shop_id' => MerchantShops::firstOrFail()->id,
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('company_id', Merchant::firstOrFail()->company_id)->where('code', DeliveryZone::COTONOU)->value('id'),
            'customer_name' => 'Detourne',
            'customer_phone' => '0022995000000',
            'customer_address' => 'Cotonou',
            'cash_collection' => 1,
            // La reaffectation que le socle autorisait.
            'merchant_id' => $this->merchant->id,
        ], $this->entetes())->assertNotFound();

        $apres = $this->colisDuVoisin->fresh();
        $this->assertEquals($this->voisin->id, $apres->merchant_id, 'le colis a change de marchand');
        $this->assertEquals('Client du voisin', $apres->customer_name);
    }

    public function test_un_colis_etranger_ne_peut_pas_etre_supprime(): void
    {
        $this->deleteJson('/api/v10/parcel/delete/' . $this->colisDuVoisin->id, [], $this->entetes())
            ->assertNotFound();

        $this->assertNotNull($this->colisDuVoisin->fresh(), 'le colis du voisin a ete supprime');
    }

    public function test_le_marchand_lit_toujours_son_propre_colis(): void
    {
        $sien = $this->creerColisPour($this->merchant);

        $this->getJson('/api/v10/parcel/details/' . $sien->id, $this->entetes())
            ->assertOk()
            ->assertJsonPath('data.parcel.id', $sien->id);
    }
}
