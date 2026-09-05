<?php

namespace Tests\Feature;

use App\Enums\AccountHeads;
use App\Enums\ApprovalStatus;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Payment;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le retrait — le transporteur reverse au marchand le net qu'il lui doit.
 *
 * C'est la sortie d'argent réelle du système : le marchand demande, le
 * transporteur règle depuis un de ses comptes, et **deux** soldes bougent
 * ensemble — celui du marchand (sa créance s'éteint) et celui du compte
 * bancaire du transporteur.
 *
 * Le socle écrivait tout cela sans transaction, sans vérifier le statut de la
 * demande, et sans la scoper à la société. Ce fichier fixe les mouvements et
 * ferme les trois trous.
 */
class MerchantWithdrawalAccountingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private const CREANCE = 50000.0;
    private const RETRAIT = 30000.0;
    private const SOLDE_BANQUE = 500000.0;

    private Merchant $marchand;
    private Account $compte;
    private Payment $demande;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->current_balance = self::CREANCE;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());

        $this->compte = $this->compteDuTransporteur($this->marchand->company_id, self::SOLDE_BANQUE);
        $this->demande = $this->demandeDe($this->marchand);
    }

    private function demandeDe(Merchant $marchand, int $statut = ApprovalStatus::PENDING): Payment
    {
        $demande = new Payment();
        $demande->forceFill([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'amount' => self::RETRAIT,
            'status' => $statut,
        ])->save();

        return $demande->fresh();
    }

    private function regler(?Payment $demande = null): bool
    {
        return app(PaymentInterface::class)->processed(new Request([
            'id' => ($demande ?? $this->demande)->id,
            'from_account' => $this->compte->id,
            'transaction_id' => 'TX-001',
        ]));
    }

    private function soldeMarchand(?Merchant $marchand = null): float
    {
        return (float) Merchant::find(($marchand ?? $this->marchand)->id)->current_balance;
    }

    private function soldeBanque(): float
    {
        return (float) Account::find($this->compte->id)->balance;
    }

    // ---- le règlement -----------------------------------------------------

    /** Les deux soldes bougent ensemble : la créance s'éteint, la banque sort l'argent. */
    public function test_settling_moves_both_balances(): void
    {
        $this->assertTrue($this->regler());

        $this->assertSame(self::CREANCE - self::RETRAIT, $this->soldeMarchand());
        $this->assertSame(self::SOLDE_BANQUE - self::RETRAIT, $this->soldeBanque());
        $this->assertSame(ApprovalStatus::PROCESSED, (int) Payment::find($this->demande->id)->status);
    }

    /**
     * L'écriture doit porter **le marchand**. Le socle ne renseignait pas
     * `merchant_id` ici — il le faisait pourtant dans les deux autres méthodes
     * du même fichier — si bien que le retrait n'apparaissait sur le relevé
     * d'aucun marchand : le solde baissait sans ligne pour l'expliquer.
     */
    public function test_the_statement_line_names_the_merchant(): void
    {
        $this->regler();

        $ligne = MerchantStatement::firstOrFail();
        $this->assertSame($this->marchand->id, (int) $ligne->merchant_id);
        $this->assertSame(AccountHeads::EXPENSE, (int) $ligne->type);
        $this->assertSame(self::RETRAIT, (float) $ligne->amount);
    }

    /** Et le compte du transporteur porte la sienne. */
    public function test_the_bank_account_is_debited_with_a_trace(): void
    {
        $this->regler();

        $ligne = BankTransaction::firstOrFail();
        $this->assertSame($this->compte->id, (int) $ligne->account_id);
        $this->assertSame(AccountHeads::EXPENSE, (int) $ligne->type);
        $this->assertSame(self::RETRAIT, (float) $ligne->amount);
    }

    // ---- ce qui ne doit pas arriver --------------------------------------

    /**
     * Régler deux fois la même demande sortait l'argent deux fois : la créance
     * du marchand tombait sous zéro et le compte du transporteur était débité
     * du double. C'est le même défaut que F1 sur les recharges, du côté
     * sortant cette fois.
     */
    public function test_settling_the_same_request_twice_pays_once(): void
    {
        $this->assertTrue($this->regler());

        $this->assertFalse($this->regler(), 'Une demande deja reglee ne se regle pas deux fois.');

        $this->assertSame(self::CREANCE - self::RETRAIT, $this->soldeMarchand());
        $this->assertSame(self::SOLDE_BANQUE - self::RETRAIT, $this->soldeBanque());
        $this->assertSame(1, MerchantStatement::count());
        $this->assertSame(1, BankTransaction::count());
    }

    /** Une demande rejetée n'est pas payable. */
    public function test_a_rejected_request_is_not_payable(): void
    {
        $rejetee = $this->demandeDe($this->marchand, ApprovalStatus::REJECT);

        $this->assertFalse($this->regler($rejetee));

        $this->assertSame(self::CREANCE, $this->soldeMarchand());
        $this->assertSame(0, BankTransaction::count());
    }

    /**
     * La demande d'un autre transporteur n'est pas réglable depuis ici. Le
     * socle lisait `Payment::where('id',$request->id)` nu : un administrateur
     * réglait la demande d'une autre société — depuis son propre compte
     * bancaire, en plus.
     */
    public function test_a_request_of_another_company_cannot_be_settled(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $ailleurs->current_balance = self::CREANCE;
        $ailleurs->save();
        $demandeAilleurs = $this->demandeDe($ailleurs);

        $this->assertFalse($this->regler($demandeAilleurs));

        $this->assertSame(self::CREANCE, $this->soldeMarchand($ailleurs));
        $this->assertSame(self::SOLDE_BANQUE, $this->soldeBanque());
        $this->assertSame(ApprovalStatus::PENDING, (int) Payment::find($demandeAilleurs->id)->status);
    }

    /** Le compte bancaire de règlement doit être celui de la société, lui aussi. */
    public function test_the_paying_account_must_belong_to_the_company(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete('Y');
        $compteAilleurs = $this->compteDuTransporteur($ailleurs->company_id);

        $refuse = app(PaymentInterface::class)->processed(new Request([
            'id' => $this->demande->id,
            'from_account' => $compteAilleurs->id,
            'transaction_id' => 'TX-VOL',
        ]));

        $this->assertFalse($refuse);
        $this->assertSame(self::CREANCE, $this->soldeMarchand());
        $this->assertSame(self::SOLDE_BANQUE, (float) Account::find($compteAilleurs->id)->balance);
    }

    /** Les écritures sont atomiques : un compte de règlement inconnu n'en laisse aucune. */
    public function test_a_failure_leaves_no_half_written_books(): void
    {
        $refuse = app(PaymentInterface::class)->processed(new Request([
            'id' => $this->demande->id,
            'from_account' => 999999,
            'transaction_id' => 'TX-FANTOME',
        ]));

        $this->assertFalse($refuse);
        $this->assertSame(self::CREANCE, $this->soldeMarchand());
        $this->assertSame(0, MerchantStatement::count());
        $this->assertSame(0, BankTransaction::count());
        $this->assertSame(ApprovalStatus::PENDING, (int) Payment::find($this->demande->id)->status);
    }

    // ---- l'annulation -----------------------------------------------------

    /** Annuler un règlement rend l'argent des deux côtés, et rouvre la demande. */
    public function test_cancelling_a_settlement_gives_the_money_back(): void
    {
        $this->regler();

        $this->assertTrue(app(PaymentInterface::class)->cancelProcess($this->demande->id));

        $this->assertSame(self::CREANCE, $this->soldeMarchand());
        $this->assertSame(self::SOLDE_BANQUE, $this->soldeBanque());
        $this->assertSame(ApprovalStatus::PENDING, (int) Payment::find($this->demande->id)->status);
    }

    /**
     * Annuler une demande **jamais réglée** créditait le marchand d'un argent
     * qu'il n'avait jamais reçu, et rechargeait le compte du transporteur.
     */
    public function test_cancelling_an_unsettled_request_changes_nothing(): void
    {
        $this->assertFalse(app(PaymentInterface::class)->cancelProcess($this->demande->id));

        $this->assertSame(self::CREANCE, $this->soldeMarchand());
        $this->assertSame(self::SOLDE_BANQUE, $this->soldeBanque());
        $this->assertSame(0, MerchantStatement::count());
    }

    /** Et l'annulation ne franchit pas la frontière de société non plus. */
    public function test_cancelling_across_companies_is_refused(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $demandeAilleurs = $this->demandeDe($ailleurs, ApprovalStatus::PROCESSED);

        $this->assertFalse(app(PaymentInterface::class)->cancelProcess($demandeAilleurs->id));

        $this->assertSame(0.0, $this->soldeMarchand($ailleurs));
        $this->assertSame(ApprovalStatus::PROCESSED, (int) Payment::find($demandeAilleurs->id)->status);
    }
}
