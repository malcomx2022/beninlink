<?php

namespace Tests\Feature;

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Resources\v10\WalletResource;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use App\Services\Parcel\WalletDebit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S123** — l'origine d'un mouvement de portefeuille se lit dans la langue du lecteur.
 *
 * Le socle écrit `wallets.source` en anglais : « Wallet Recharge » pour une recharge approuvée,
 * « Parcel delivery charge - #TRK » pour un débit de colis. Le marchand le lisait tel quel dans
 * son historique (web et API) et dans sa notification : « +50 000 FCFA crédités via Wallet
 * Recharge. ». La valeur stockée ne bouge pas — c'est la clé qui relie un débit à son colis —,
 * on la traduit à l'affichage par `Wallet::source_label`.
 */
class WalletSourceSpeaksFrenchTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private function mouvement(string $source, array $champs = []): Wallet
    {
        return (new Wallet())->forceFill(['source' => $source] + $champs);
    }

    public function test_the_source_label_follows_the_language_and_keeps_the_stored_key(): void
    {
        app()->setLocale('fr');
        $this->assertSame('Recharge du portefeuille', $this->mouvement(Wallet::SOURCE_RECHARGE)->source_label);
        $debit = $this->mouvement(WalletDebit::SOURCE . 'BL-0042');
        $this->assertSame('Frais de livraison du colis #BL-0042', $debit->source_label);
        $this->assertSame('FedaPay', $this->mouvement('FedaPay')->source_label, 'un nom propre passe tel quel');
        $this->assertSame(WalletDebit::SOURCE . 'BL-0042', $debit->source, 'la clé de rapprochement ne change pas');

        app()->setLocale('en');
        $this->assertSame('Wallet recharge', $this->mouvement(Wallet::SOURCE_RECHARGE)->source_label);
    }

    public function test_the_api_and_the_merchant_history_show_the_label(): void
    {
        app()->setLocale('fr');
        $ligne = (new WalletResource($this->mouvement(WalletDebit::SOURCE . 'BL-0042', ['type' => WalletType::EXPENSE])))->toArray(request());
        $this->assertSame('Frais de livraison du colis #BL-0042', $ligne['source']);

        foreach (['all_transaction', 'recharge_transaction'] as $vue) {
            $source = file_get_contents(resource_path("views/backend/merchant_panel/mywallet/{$vue}.blade.php"));
            $this->assertStringContainsString('->source_label }}', $source, "{$vue} : l'origine s'affiche traduite");
            $this->assertDoesNotMatchRegularExpression('/->source\s*\}\}/', $source, "{$vue} : plus de source brute");
        }
    }

    public function test_an_approved_recharge_notifies_in_french(): void
    {
        $this->seedTenant();
        app()->setLocale('fr');
        $marchand = Merchant::firstOrFail();

        $recharge = $this->mouvement(Wallet::SOURCE_RECHARGE, [
            'company_id' => $marchand->company_id,
            'user_id' => $marchand->user_id,
            'merchant_id' => $marchand->id,
            'transaction_id' => 'BL-RECH-123',
            'amount' => 50000,
            'type' => WalletType::INCOME,
            'payment_method' => WalletPaymentMethod::OFFLINE,
            'status' => WalletStatus::PENDING,
        ]);
        $recharge->save();
        $recharge->status = WalletStatus::APPROVED;
        $recharge->save();

        $corps = $marchand->user->notifications()->first()->data['body'];
        $this->assertStringContainsString('via Recharge du portefeuille', $corps);
        $this->assertStringNotContainsString('Wallet Recharge', $corps);
    }
}
