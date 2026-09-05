<?php

namespace Tests\Feature;

use App\Enums\Wallet\WalletStatus;
use App\Http\Controllers\Payment\FedaPayController;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use App\Models\User;
use App\Services\Payments\FedaPayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Chantier 3, complément : la recharge Mobile Money depuis le panneau
 * marchand web. Même flux que l'app (`POST /api/v10/fedapay/initiate`),
 * même webhook : le retour de page ne crédite rien.
 *
 * Les routes du locataire ne sont pas enregistrées en test (hôte inconnu de
 * `domains`) : l'action est appelée directement, comme pour l'abonnement.
 */
class FedaPayWalletWebTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        $this->merchant = Merchant::firstOrFail();

        // Cible du retour de page, hors du groupe locataire en test.
        Route::get('merchant/my-wallet', fn () => 'wallet')->name('merchant-panel.my.wallet.index');
        Route::get('merchant/my-wallet/recharge', fn () => 'recharge')->name('merchant-panel.my.wallet.recharge');
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    private function passerelle(bool $ok, bool $configuree = true): void
    {
        $this->mock(FedaPayGateway::class, function ($mock) use ($ok, $configuree) {
            $mock->shouldReceive('isEnabled')->andReturn($configuree);
            $expectation = $mock->shouldReceive('initialize');
            if (!$configuree) {
                $expectation->never();
            } else {
                $expectation->once()
                    ->withArgs(fn (array $p) => $p['purpose'] === FedaPayTransaction::PURPOSE_WALLET
                        && $p['amount'] === 5000
                        && str_contains($p['callback_url'], 'channel=web')
                        && str_contains($p['callback_url'], 'reference=BL-'))
                    ->andReturn($ok
                        ? ['success' => true, 'payment_url' => 'https://sandbox-checkout.fedapay.com/w', 'provider_transaction_id' => 'FP-W-1']
                        : ['success' => false, 'message' => 'refus']);
            }
        });
    }

    private function recharger(int $amount = 5000)
    {
        return app(FedaPayController::class)->rechargeWeb(
            Request::create('/merchant/my-wallet/recharge/fedapay', 'POST', ['amount' => $amount])
        );
    }

    public function test_the_panel_opens_a_pending_recharge_and_redirects_to_fedapay(): void
    {
        $this->passerelle(true);
        Auth::login($this->merchant->user);
        $soldeAvant = (float) $this->merchant->current_balance;

        $reponse = $this->recharger();

        $this->assertSame('https://sandbox-checkout.fedapay.com/w', $reponse->getTargetUrl());

        $record = FedaPayTransaction::where('provider_transaction_id', 'FP-W-1')->firstOrFail();
        $this->assertSame(FedaPayTransaction::STATUS_PENDING, $record->status);
        $this->assertSame($this->merchant->id, (int) $record->merchant_id);
        $this->assertSame(5000, (int) $record->amount);

        $wallet = Wallet::findOrFail($record->wallet_id);
        $this->assertSame(WalletStatus::PENDING, (int) $wallet->status);
        // Rien n'est crédité à l'initiation.
        $this->assertSame($soldeAvant, (float) $this->merchant->fresh()->current_balance);
    }

    public function test_a_declined_initiation_leaves_a_rejected_trace_and_returns_to_the_form(): void
    {
        $this->passerelle(false);
        Auth::login($this->merchant->user);

        $reponse = $this->recharger();

        $this->assertSame(302, $reponse->getStatusCode());
        $record = FedaPayTransaction::where('purpose', FedaPayTransaction::PURPOSE_WALLET)->firstOrFail();
        $this->assertSame(FedaPayTransaction::STATUS_DECLINED, $record->status);
        $this->assertSame(WalletStatus::REJECTED, (int) Wallet::findOrFail($record->wallet_id)->status);
    }

    public function test_without_keys_nothing_is_created(): void
    {
        $this->passerelle(true, configuree: false);
        Auth::login($this->merchant->user);

        $this->recharger();

        $this->assertSame(0, FedaPayTransaction::count());
        $this->assertSame(0, Wallet::where('source', 'FedaPay')->count());
    }

    public function test_a_non_merchant_account_cannot_recharge(): void
    {
        $this->passerelle(true, configuree: false);
        Auth::login(User::where('email', 'company@wemaxdevs.com')->firstOrFail());

        $this->recharger();

        $this->assertSame(0, FedaPayTransaction::count());
    }

    public function test_the_web_return_goes_back_to_the_wallet_without_crediting(): void
    {
        $record = FedaPayTransaction::create([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'reference' => 'BL-WEBRETOUR',
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'amount' => 5000,
            'status' => FedaPayTransaction::STATUS_PENDING,
        ]);
        $soldeAvant = (float) $this->merchant->current_balance;

        $this->get('/fedapay/callback?reference=BL-WEBRETOUR&channel=web')
            ->assertRedirect(route('merchant-panel.my.wallet.index'));

        $this->assertSame(FedaPayTransaction::STATUS_PENDING, $record->fresh()->status);
        $this->assertSame($soldeAvant, (float) $this->merchant->fresh()->current_balance);

        // Sans `channel=web` (retour de la WebView de l'app), la page d'attente s'affiche.
        $this->get('/fedapay/callback?reference=BL-WEBRETOUR')
            ->assertOk()
            ->assertSee('BL-WEBRETOUR');
    }
}
