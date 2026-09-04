<?php

namespace Tests\Feature;

use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * L'API boutiques ne doit rendre, modifier ou supprimer que les boutiques du
 * marchand connecte.
 *
 * Le socle faisait `MerchantShops::where('id', $id)` sans filtre dans
 * `ShopsRepository` : changer l'identifiant dans l'URL suffisait a lire
 * l'adresse et le telephone de la boutique d'un concurrent, a la renommer ou
 * a la supprimer — le meme schema que S17 sur les colis. Releve en branchant
 * l'ecran boutiques de mobile/ sur `shops/update` et `shops/delete`.
 */
class ShopScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;
    private MerchantShops $boutiqueDuVoisin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();

        // Un second marchand de la MEME societe : un scoping par company_id
        // seul ne le protegerait pas.
        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin@example.test';
        $autreUtilisateur->mobile = '0022997000009';
        $autreUtilisateur->unique_id = 'U-VOISIN';
        $autreUtilisateur->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $autreUtilisateur->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->save();

        $this->boutiqueDuVoisin = new MerchantShops();
        $this->boutiqueDuVoisin->forceFill([
            'merchant_id' => $voisin->id,
            'name' => 'Boutique du voisin',
            'contact_no' => '22997000009',
            'address' => 'Parakou',
            'status' => \App\Enums\Status::ACTIVE,
        ])->save();

        Sanctum::actingAs($this->merchant->user);
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function charge(string $nom): array
    {
        return [
            'name' => $nom,
            'contact_no' => '22997000001',
            'address' => 'Cotonou, Ganhi',
            'status' => \App\Enums\Status::ACTIVE,
        ];
    }

    public function test_la_boutique_d_un_autre_marchand_est_introuvable(): void
    {
        $this->getJson('/api/v10/shops/edit/' . $this->boutiqueDuVoisin->id, $this->entetes())
            ->assertNotFound();
    }

    public function test_la_boutique_d_un_autre_marchand_ne_peut_pas_etre_modifiee(): void
    {
        $this->putJson('/api/v10/shops/update/' . $this->boutiqueDuVoisin->id, $this->charge('Piratee'), $this->entetes())
            ->assertNotFound();

        $this->assertSame('Boutique du voisin', $this->boutiqueDuVoisin->fresh()->name);
    }

    public function test_la_boutique_d_un_autre_marchand_ne_peut_pas_etre_supprimee(): void
    {
        $this->deleteJson('/api/v10/shops/delete/' . $this->boutiqueDuVoisin->id, [], $this->entetes())
            ->assertNotFound();

        $this->assertNotNull($this->boutiqueDuVoisin->fresh());
    }

    public function test_le_marchand_cree_modifie_et_supprime_sa_propre_boutique(): void
    {
        $this->postJson('/api/v10/shops/store', $this->charge('Ma boutique'), $this->entetes())
            ->assertOk();

        $boutique = MerchantShops::where('merchant_id', $this->merchant->id)
            ->where('name', 'Ma boutique')
            ->firstOrFail();

        $this->getJson('/api/v10/shops/edit/' . $boutique->id, $this->entetes())
            ->assertOk()
            ->assertJsonPath('data.shop.name', 'Ma boutique');

        $this->putJson('/api/v10/shops/update/' . $boutique->id, $this->charge('Ma boutique renommee'), $this->entetes())
            ->assertOk();
        $this->assertSame('Ma boutique renommee', $boutique->fresh()->name);

        $this->deleteJson('/api/v10/shops/delete/' . $boutique->id, [], $this->entetes())
            ->assertOk();
        $this->assertNull($boutique->fresh());
    }

    public function test_la_liste_ne_contient_que_ses_boutiques(): void
    {
        $reponse = $this->getJson('/api/v10/shops/index', $this->entetes())->assertOk();

        $ids = array_column($reponse->json('data.shops'), 'id');
        $this->assertNotContains($this->boutiqueDuVoisin->id, $ids);
    }
}
