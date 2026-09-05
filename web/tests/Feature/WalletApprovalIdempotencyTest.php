<?php

namespace Tests\Feature;

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * F1 — le crédit d'une recharge doit être idempotent, quel que soit le chemin.
 *
 * Deux chemins mènent à `WalletRepository::approved()` : le bouton « Approuver »
 * de l'écran Demandes de recharge, et le webhook signé de FedaPay. Rien ne les
 * reliait. Une recharge FedaPay en attente est indiscernable d'une recharge
 * manuelle dans la liste — elle s'y affiche « Hors ligne », la colonne `source`
 * n'étant pas montrée — de sorte que l'administrateur l'approuvait dans sa
 * tournée quotidienne, puis le webhook créditait une seconde fois. Un double-clic
 * sur « Approuver » produisait le même doublement.
 *
 * `.claude/rules/payments.md` : « traitement idempotent sous verrou ; un webhook
 * rejoué ne crédite qu'une fois ». Ces tests couvrent les trois enchaînements.
 */
class WalletApprovalIdempotencyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const WEBHOOK_SECRET = 'wh_test_secret';
    private const MONTANT = 5000;

    private Merchant $merchant;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config([
            'fedapay.webhook_secret' => self::WEBHOOK_SECRET,
            'fedapay.webhook_tolerance' => 300,
        ]);

        $this->merchant = Merchant::firstOrFail();
        $this->merchant->wallet_balance = 0;
        $this->merchant->save();

        $this->wallet = $this->rechargeEnAttente();
    }

    /** Une recharge FedaPay ouverte : ligne `wallets` en attente + transaction. */
    private function rechargeEnAttente(): Wallet
    {
        $wallet = new Wallet();
        $wallet->company_id = $this->merchant->company_id;
        $wallet->user_id = $this->merchant->user_id;
        $wallet->merchant_id = $this->merchant->id;
        $wallet->source = 'FedaPay';
        $wallet->transaction_id = 'BL-IDEM';
        $wallet->amount = self::MONTANT;
        $wallet->type = WalletType::INCOME;
        // Le socle n'a pas de méthode « Mobile Money » : la ligne s'affiche
        // « Hors ligne » dans l'écran d'approbation. D'où le piège.
        $wallet->payment_method = WalletPaymentMethod::OFFLINE;
        $wallet->status = WalletStatus::PENDING;
        $wallet->save();

        FedaPayTransaction::create([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'reference' => 'BL-IDEM',
            'provider_transaction_id' => 'PROV-IDEM',
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'amount' => self::MONTANT,
            'status' => FedaPayTransaction::STATUS_PENDING,
            'wallet_id' => $wallet->id,
        ]);

        return $wallet;
    }

    private function webhookApprouve()
    {
        $payload = json_encode([
            'name' => 'transaction.approved',
            'entity' => ['id' => 'PROV-IDEM', 'amount' => self::MONTANT],
        ]);
        $horodatage = time();

        return $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => "t={$horodatage},s="
                . hash_hmac('sha256', $horodatage . '.' . $payload, self::WEBHOOK_SECRET),
        ], $payload);
    }

    private function approbationAdmin(): bool
    {
        // Même service que la route `wallet.request.approve` du back-office.
        return app(WalletInterface::class)->approved($this->wallet->id);
    }

    private function solde(): int
    {
        return (int) round((float) Merchant::find($this->merchant->id)->wallet_balance);
    }

    public function test_the_admin_approval_then_the_webhook_credit_only_once(): void
    {
        $this->assertTrue($this->approbationAdmin());
        $this->assertSame(self::MONTANT, $this->solde());

        $this->webhookApprouve()->assertOk();

        $this->assertSame(self::MONTANT, $this->solde());
        $this->assertSame(WalletStatus::APPROVED, (int) $this->wallet->fresh()->status);
    }

    public function test_the_webhook_then_the_admin_approval_credit_only_once(): void
    {
        $this->webhookApprouve()->assertOk();
        $this->assertSame(self::MONTANT, $this->solde());

        // L'administrateur clique sur une liste périmée : la ligne est déjà réglée.
        $this->assertFalse($this->approbationAdmin());
        $this->assertSame(self::MONTANT, $this->solde());
    }

    public function test_a_double_click_on_approve_credits_only_once(): void
    {
        $this->assertTrue($this->approbationAdmin());
        $this->assertFalse($this->approbationAdmin());

        $this->assertSame(self::MONTANT, $this->solde());
    }

    public function test_a_rejected_request_is_never_credited(): void
    {
        $this->wallet->status = WalletStatus::REJECTED;
        $this->wallet->save();

        $this->assertFalse($this->approbationAdmin());
        $this->assertSame(0, $this->solde());
        $this->assertSame(WalletStatus::REJECTED, (int) $this->wallet->fresh()->status);
    }

    public function test_a_pending_request_is_still_credited_once(): void
    {
        $this->assertTrue($this->approbationAdmin());

        $this->assertSame(self::MONTANT, $this->solde());
        $this->assertSame(WalletStatus::APPROVED, (int) $this->wallet->fresh()->status);
    }

    /** Le webhook seul, sans intervention humaine : le chemin nominal reste intact. */
    public function test_the_signed_webhook_alone_credits_the_wallet(): void
    {
        $this->webhookApprouve()->assertOk();

        $this->assertSame(self::MONTANT, $this->solde());
        $this->assertSame(WalletStatus::APPROVED, (int) $this->wallet->fresh()->status);
        $this->assertSame(
            FedaPayTransaction::STATUS_APPROVED,
            FedaPayTransaction::where('reference', 'BL-IDEM')->firstOrFail()->status,
        );
    }

    /** Et son rejeu ne crédite toujours qu'une fois. */
    public function test_a_replayed_webhook_credits_only_once(): void
    {
        $this->webhookApprouve()->assertOk();
        $this->webhookApprouve()->assertOk();

        $this->assertSame(self::MONTANT, $this->solde());
    }
}
