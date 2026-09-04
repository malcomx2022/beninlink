<?php

namespace Tests\Feature;

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * `GET /api/v10/wallet/history` — mouvements du porte-monnaie prepaye.
 *
 * Aucune route ne listait `wallets` : l'ecran wallet de mobile/ affichait le
 * solde (via /profile) sans pouvoir montrer d'ou il venait. La route expose
 * `WalletRepository::get()`, qui filtre deja par utilisateur pour un marchand.
 */
class WalletHistoryTest extends TestCase
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
    }

    private function mouvement(Merchant $merchant, int $montant, string $reference, int $statut = WalletStatus::APPROVED): Wallet
    {
        $wallet = new Wallet();
        $wallet->forceFill([
            'company_id' => $merchant->company_id,
            'source' => 'test',
            'user_id' => $merchant->user_id,
            'merchant_id' => $merchant->id,
            'transaction_id' => $reference,
            'amount' => $montant,
            'type' => WalletType::INCOME,
            'payment_method' => WalletPaymentMethod::OFFLINE,
            'status' => $statut,
        ])->save();

        return $wallet;
    }

    public function test_le_marchand_voit_ses_mouvements_en_entiers(): void
    {
        $this->mouvement($this->merchant, 5000, 'RECH-1');
        $this->mouvement($this->merchant, 12000, 'RECH-2', WalletStatus::PENDING);

        Sanctum::actingAs($this->merchant->user);

        $reponse = $this->getJson('/api/v10/wallet/history', ['apiKey' => self::API_KEY])
            ->assertOk();

        $entries = $reponse->json('data.entries');
        $this->assertCount(2, $entries);
        // Ordre decroissant : le plus recent d'abord.
        $this->assertSame('RECH-2', $entries[0]['transaction_id']);
        $this->assertSame(12000, $entries[0]['amount']);
        $this->assertSame(WalletStatus::PENDING, $entries[0]['status']);
        $this->assertSame(WalletType::INCOME, $entries[1]['type']);
    }

    public function test_les_mouvements_d_un_autre_marchand_restent_invisibles(): void
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

        $this->mouvement($voisin, 9999, 'RECH-VOISIN');
        $this->mouvement($this->merchant, 1000, 'RECH-MOI');

        Sanctum::actingAs($this->merchant->user);

        $entries = $this->getJson('/api/v10/wallet/history', ['apiKey' => self::API_KEY])
            ->assertOk()
            ->json('data.entries');

        $this->assertSame(['RECH-MOI'], array_column($entries, 'transaction_id'));
    }

    public function test_sans_jeton_l_historique_est_refuse(): void
    {
        $this->getJson('/api/v10/wallet/history', ['apiKey' => self::API_KEY])
            ->assertUnauthorized();
    }
}
