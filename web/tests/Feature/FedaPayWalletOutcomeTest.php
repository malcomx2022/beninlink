<?php

namespace Tests\Feature;

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * F5 — les issues du webhook de recharge autres que le crédit.
 *
 * `.claude/rules/payments.md` exige un test sur ce chemin. Le crédit et son
 * idempotence sont couverts par `WalletApprovalIdempotencyTest` ; les tests de
 * recharge, eux, s'arrêtaient à l'initiation. Restaient sans preuve : le refus
 * et l'annulation, qui ferment la demande en attente, le fait qu'un refus tardif
 * ne défait jamais un crédit acquis, et la transaction inconnue.
 *
 * Le webhook répond 200 même sur un événement qu'il ignore : un code d'erreur
 * ferait réessayer FedaPay indéfiniment pour un événement qui ne nous concerne
 * pas. Seule une signature invalide donne 400.
 */
class FedaPayWalletOutcomeTest extends TestCase
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

    private function rechargeEnAttente(): Wallet
    {
        $wallet = new Wallet();
        $wallet->company_id = $this->merchant->company_id;
        $wallet->user_id = $this->merchant->user_id;
        $wallet->merchant_id = $this->merchant->id;
        $wallet->source = 'FedaPay';
        $wallet->transaction_id = 'BL-F5';
        $wallet->amount = self::MONTANT;
        $wallet->type = WalletType::INCOME;
        $wallet->payment_method = WalletPaymentMethod::OFFLINE;
        $wallet->status = WalletStatus::PENDING;
        $wallet->save();

        FedaPayTransaction::create([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'reference' => 'BL-F5',
            'provider_transaction_id' => 'PROV-F5',
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'amount' => self::MONTANT,
            'status' => FedaPayTransaction::STATUS_PENDING,
            'wallet_id' => $wallet->id,
        ]);

        return $wallet;
    }

    private function webhook(string $evenement, int $montant = self::MONTANT, string $providerId = 'PROV-F5')
    {
        $payload = json_encode([
            'name' => $evenement,
            'entity' => ['id' => $providerId, 'amount' => $montant],
        ]);
        $horodatage = time();

        return $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => "t={$horodatage},s="
                . hash_hmac('sha256', $horodatage . '.' . $payload, self::WEBHOOK_SECRET),
        ], $payload);
    }

    private function transaction(): FedaPayTransaction
    {
        return FedaPayTransaction::where('reference', 'BL-F5')->firstOrFail();
    }

    private function solde(): int
    {
        return (int) round((float) Merchant::find($this->merchant->id)->wallet_balance);
    }

    /* ───────────────────────── le rejeu ─────────────────────────────────── */

    /**
     * S66 — **la propriété qui compte** : FedaPay réessaie, un réseau duplique,
     * un opérateur renvoie le même événement. Le solde ne doit bouger qu'une fois.
     *
     * Elle était prouvée côté ABONNEMENT depuis le chantier 3
     * (`FedaPaySubscriptionTest::test_le_webhook_approuve_active_le_plan_une_seule_fois`)
     * mais **pas** côté portefeuille, alors que c'est un autre chemin de code :
     * `walletRepo->approved()` au lieu de `switchPlan()`.
     *
     * ⚠️ **DEUX gardes couvrent ce chemin**, et c'est pour cela que les trois
     * tests ci-dessous existent au lieu d'un seul :
     *
     * | garde | où | ce qu'elle fait |
     * |---|---|---|
     * | 1 | `FedaPayController::approve()` | `lockForUpdate` + `isApproved()` : le rejeu ressort avant tout crédit |
     * | 2 | `WalletRepository::approved()` | `lockForUpdate` + statut `PENDING` : une recharge déjà approuvée n'est pas re-créditée |
     *
     * Retirer **une** des deux laisse l'autre tenir : un sabotage partiel resterait
     * vert et ne mesurerait que la survivante (leçon S64). Chaque garde a donc son
     * propre détecteur, et celui-ci tient la propriété d'ensemble.
     */
    public function test_a_replayed_webhook_credits_the_wallet_only_once(): void
    {
        $this->webhook('transaction.approved')->assertOk();

        $this->assertSame(self::MONTANT, $this->solde(),
            'contrôle positif tombé : le premier webhook ne crédite plus rien');

        $this->webhook('transaction.approved')->assertOk();

        $this->assertSame(self::MONTANT, $this->solde(),
            'LE REJEU A CRÉDITÉ DEUX FOIS : les deux verrous ont sauté');
        $this->assertSame(1, Wallet::where('id', $this->wallet->id)
            ->where('status', WalletStatus::APPROVED)->count());
    }

    /**
     * La garde **1**, seule : le rejeu doit ressortir de `approve()` AVANT le
     * crédit, et la réponse le dit — `already processed`, pas `approved`.
     *
     * C'est le seul discriminant qui ne dépende pas de la garde 2 : si le verrou
     * de la transaction saute, le second appel repart vers le dépôt (qui le
     * refusera, mais pour une autre raison) et la réponse devient `approved`.
     */
    public function test_the_transaction_lock_answers_already_processed_on_a_replay(): void
    {
        $this->assertStringContainsString('approved',
            $this->webhook('transaction.approved')->assertOk()->getContent(),
            'contrôle positif tombé : le premier webhook n\'approuve plus');

        $this->assertStringContainsString('already processed',
            $this->webhook('transaction.approved')->assertOk()->getContent(),
            'le verrou de la transaction a sauté : le rejeu repart vers le dépôt '
            . 'au lieu de ressortir immédiatement');
    }

    /**
     * La garde **2**, seule : appelée deux fois en direct, sans passer par le
     * webhook, elle ne crédite qu'une fois.
     *
     * ⚠️ Le passage par le dépôt est délibéré : c'est le seul moyen d'atteindre
     * la garde 2 sans que la garde 1 ne s'interpose.
     */
    public function test_the_wallet_lock_credits_only_once_when_called_twice(): void
    {
        $depot = app(\App\Repositories\Wallet\WalletInterface::class);

        $this->assertTrue($depot->approved($this->wallet->id),
            'contrôle positif tombé : le premier crédit échoue');
        $this->assertSame(self::MONTANT, $this->solde());

        $this->assertFalse($depot->approved($this->wallet->id),
            'le dépôt accepte de créditer une recharge DÉJÀ approuvée');
        $this->assertSame(self::MONTANT, $this->solde(),
            'le verrou du portefeuille a sauté : le second appel a re-crédité');
    }

    public function test_a_declined_payment_closes_the_pending_request(): void
    {
        $this->webhook('transaction.declined')->assertOk();

        $this->assertSame(FedaPayTransaction::STATUS_DECLINED, $this->transaction()->status);
        $this->assertSame(WalletStatus::REJECTED, (int) $this->wallet->fresh()->status);
        $this->assertSame(0, $this->solde());
    }

    public function test_a_canceled_payment_closes_the_pending_request(): void
    {
        $this->webhook('transaction.canceled')->assertOk();

        $this->assertSame(FedaPayTransaction::STATUS_CANCELED, $this->transaction()->status);
        $this->assertSame(WalletStatus::REJECTED, (int) $this->wallet->fresh()->status);
        $this->assertSame(0, $this->solde());
    }

    /**
     * Un refus après approbation ne défait pas un crédit : cela relève d'un
     * remboursement, décidé à la main. Le solde ne doit pas bouger tout seul.
     */
    public function test_a_late_decline_never_undoes_an_acquired_credit(): void
    {
        $this->webhook('transaction.approved')->assertOk();
        $this->assertSame(self::MONTANT, $this->solde());

        $this->webhook('transaction.declined')->assertOk();

        $this->assertSame(self::MONTANT, $this->solde());
        $this->assertSame(FedaPayTransaction::STATUS_APPROVED, $this->transaction()->status);
        $this->assertSame(WalletStatus::APPROVED, (int) $this->wallet->fresh()->status);
    }

    /** Un événement pour une transaction que nous ne connaissons pas ne crédite rien. */
    public function test_an_unknown_transaction_is_acknowledged_without_crediting(): void
    {
        $this->webhook('transaction.approved', self::MONTANT, 'PROV-INCONNUE')->assertOk();

        $this->assertSame(0, $this->solde());
        $this->assertSame(FedaPayTransaction::STATUS_PENDING, $this->transaction()->status);
        $this->assertSame(WalletStatus::PENDING, (int) $this->wallet->fresh()->status);
    }

    /** Un événement qui ne nous concerne pas est acquitté sans rien changer. */
    public function test_an_unrelated_event_changes_nothing(): void
    {
        $this->webhook('transaction.pending')->assertOk();

        $this->assertSame(FedaPayTransaction::STATUS_PENDING, $this->transaction()->status);
        $this->assertSame(0, $this->solde());
    }

    /**
     * Le montant crédité est celui ATTENDU, jamais celui annoncé par
     * l'événement : un écart signale un rapprochement à faire, il ne fait pas
     * autorité sur le solde.
     */
    public function test_the_credited_amount_is_the_expected_one_not_the_announced_one(): void
    {
        $this->webhook('transaction.approved', 999999)->assertOk();

        $this->assertSame(self::MONTANT, $this->solde());
    }

    /** Une signature invalide n'atteint jamais la logique de crédit. */
    public function test_an_unsigned_webhook_credits_nothing(): void
    {
        $payload = json_encode([
            'name' => 'transaction.approved',
            'entity' => ['id' => 'PROV-F5', 'amount' => self::MONTANT],
        ]);

        $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertStatus(400);

        $this->assertSame(0, $this->solde());
        $this->assertSame(WalletStatus::PENDING, (int) $this->wallet->fresh()->status);
    }
}
