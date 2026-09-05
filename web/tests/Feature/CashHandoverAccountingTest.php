<?php

namespace Tests\Feature;

use App\Enums\AccountHeads;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\CashReceivedFromDeliveryman;
use App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * La remise d'espèces du livreur à l'agence — le pendant de la livraison.
 *
 * Livrer laisse le livreur **débiteur** de l'argent qu'il a encaissé chez le
 * client (voir `DeliveryAccountingTest`). Cette étape solde cette dette : il
 * remet les espèces à son agence, qui les dépose sur un compte du
 * transporteur.
 *
 * Trois soldes bougent ensemble, et c'est ce qui la rend délicate :
 *
 * | Compte | Mouvement |
 * |---|---|
 * | Livreur | + le montant remis — sa dette remonte vers zéro |
 * | Agence | − le montant, qui ne transite plus par elle |
 * | Compte du transporteur | + le montant déposé |
 *
 * Comme les autres étapes comptables, elle doit se faire chez soi et tout ou
 * rien (**D8**).
 */
class CashHandoverAccountingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private const REMISE = 20000.0;
    private const SOLDE_BANQUE = 100000.0;

    private DeliveryMan $livreur;
    private Account $compte;
    private Hub $agence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $marchand = \App\Models\Backend\Merchant::firstOrFail();
        $this->actingAs($marchand->user->fresh());

        $this->agence = Hub::findOrFail(auth()->user()->hub_id);
        $this->agence->current_balance = 0;
        $this->agence->save();

        $this->livreur = $this->livreur();
        // Il doit l'argent qu'il a encaissé : c'est l'état que la remise solde.
        $this->livreur->current_balance = -self::REMISE;
        $this->livreur->save();

        $this->compte = $this->compteDuTransporteur($marchand->company_id, self::SOLDE_BANQUE);
    }

    private function remettre(array $surcharges = []): bool
    {
        return app(ReceivedInterface::class)->store(new Request($surcharges + [
            'delivery_man_id' => $this->livreur->id,
            'account_id' => $this->compte->id,
            'amount' => self::REMISE,
            'date' => date('Y-m-d'),
        ]));
    }

    private function soldeLivreur(?DeliveryMan $livreur = null): float
    {
        return (float) DeliveryMan::find(($livreur ?? $this->livreur)->id)->current_balance;
    }

    private function soldeAgence(): float
    {
        return (float) Hub::find($this->agence->id)->current_balance;
    }

    private function soldeBanque(?Account $compte = null): float
    {
        return (float) Account::find(($compte ?? $this->compte)->id)->balance;
    }

    // ---- la remise --------------------------------------------------------

    /** Les trois soldes bougent ensemble, et la dette du livreur s'éteint. */
    public function test_handing_the_cash_over_settles_the_deliverymans_debt(): void
    {
        $this->assertTrue($this->remettre());

        $this->assertSame(0.0, $this->soldeLivreur(), 'La dette du livreur doit etre soldee.');
        $this->assertSame(-self::REMISE, $this->soldeAgence());
        $this->assertSame(self::SOLDE_BANQUE + self::REMISE, $this->soldeBanque());
    }

    /** Chaque mouvement laisse sa ligne, du bon côté. */
    public function test_each_movement_leaves_its_line(): void
    {
        $this->remettre();

        $this->assertSame(AccountHeads::INCOME, (int) DeliverymanStatement::firstOrFail()->type);
        $this->assertSame(AccountHeads::EXPENSE, (int) HubStatement::firstOrFail()->type);
        $this->assertSame(AccountHeads::INCOME, (int) BankTransaction::firstOrFail()->type);

        $remise = CashReceivedFromDeliveryman::firstOrFail();
        $this->assertSame(self::REMISE, (float) $remise->amount);
        $this->assertSame($this->livreur->id, (int) $remise->delivery_man_id);
        $this->assertSame($this->agence->id, (int) $remise->hub_id);
    }

    // ---- ce qui ne doit pas arriver --------------------------------------

    /**
     * Le livreur d'un autre transporteur n'est pas créditable depuis ici. Le
     * socle lisait `DeliveryMan::find()` nu : une agence soldait la dette d'un
     * livreur d'une autre société, et déposait la somme sur son propre compte.
     */
    public function test_a_deliveryman_of_another_company_cannot_be_credited(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $livreurAilleurs = $this->livreur('9', $ailleurs->company_id);
        $livreurAilleurs->current_balance = -self::REMISE;
        $livreurAilleurs->save();

        $this->assertFalse($this->remettre(['delivery_man_id' => $livreurAilleurs->id]));

        $this->assertSame(-self::REMISE, $this->soldeLivreur($livreurAilleurs));
        $this->assertSame(self::SOLDE_BANQUE, $this->soldeBanque());
        $this->assertSame(0, CashReceivedFromDeliveryman::count());
    }

    /** Le compte de dépôt doit appartenir à la société, lui aussi. */
    public function test_the_receiving_account_must_belong_to_the_company(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete('Y');
        $compteAilleurs = $this->compteDuTransporteur($ailleurs->company_id, self::SOLDE_BANQUE);

        $this->assertFalse($this->remettre(['account_id' => $compteAilleurs->id]));

        $this->assertSame(self::SOLDE_BANQUE, $this->soldeBanque($compteAilleurs));
        $this->assertSame(-self::REMISE, $this->soldeLivreur());
        $this->assertSame(0, CashReceivedFromDeliveryman::count());
    }

    /**
     * Les trois mouvements sont atomiques : un compte de dépôt inconnu ne doit
     * pas laisser le livreur crédité et la banque non alimentée.
     */
    public function test_a_failure_leaves_no_half_written_books(): void
    {
        $this->assertFalse($this->remettre(['account_id' => 999999]));

        $this->assertSame(-self::REMISE, $this->soldeLivreur());
        $this->assertSame(0.0, $this->soldeAgence());
        $this->assertSame(0, CashReceivedFromDeliveryman::count());
        $this->assertSame(0, DeliverymanStatement::count());
        $this->assertSame(0, HubStatement::count());
    }

    // ---- la correction ----------------------------------------------------

    /** Supprimer une remise la défait entièrement. */
    public function test_deleting_a_handover_reverses_it_completely(): void
    {
        $this->remettre();

        $this->assertTrue((bool) app(ReceivedInterface::class)->delete(CashReceivedFromDeliveryman::firstOrFail()->id));

        $this->assertSame(-self::REMISE, $this->soldeLivreur());
        $this->assertSame(0.0, $this->soldeAgence());
        $this->assertSame(self::SOLDE_BANQUE, $this->soldeBanque());
        $this->assertSame(0, CashReceivedFromDeliveryman::count());
    }

    /**
     * Corriger le montant d'une remise revient à défaire l'ancienne et refaire
     * la nouvelle : les soldes doivent refléter le **nouveau** montant, jamais
     * la somme des deux.
     */
    public function test_correcting_the_amount_replaces_it_rather_than_adding_to_it(): void
    {
        $this->remettre();

        $ok = app(ReceivedInterface::class)->update(new Request([
            'id' => CashReceivedFromDeliveryman::firstOrFail()->id,
            'delivery_man_id' => $this->livreur->id,
            'account_id' => $this->compte->id,
            'amount' => 15000,
            'date' => date('Y-m-d'),
        ]));

        $this->assertTrue($ok);
        $this->assertSame(-5000.0, $this->soldeLivreur(), 'Il doit encore 5 000 F.');
        $this->assertSame(self::SOLDE_BANQUE + 15000, $this->soldeBanque());
        $this->assertSame(15000.0, (float) CashReceivedFromDeliveryman::firstOrFail()->amount);
    }

    /** Et la remise d'un autre transporteur n'est ni corrigeable ni supprimable. */
    public function test_a_handover_of_another_company_is_out_of_reach(): void
    {
        $this->remettre();
        $remise = CashReceivedFromDeliveryman::firstOrFail();
        $remise->company_id = $this->marchandDUneAutreSociete()->company_id;
        $remise->save();

        $soldeLivreur = $this->soldeLivreur();
        $soldeBanque = $this->soldeBanque();

        $this->assertFalse((bool) app(ReceivedInterface::class)->delete($remise->id));
        $this->assertFalse(app(ReceivedInterface::class)->update(new Request([
            'id' => $remise->id,
            'delivery_man_id' => $this->livreur->id,
            'account_id' => $this->compte->id,
            'amount' => 15000,
            'date' => date('Y-m-d'),
        ])));

        $this->assertSame($soldeLivreur, $this->soldeLivreur());
        $this->assertSame($soldeBanque, $this->soldeBanque());
        $this->assertSame(self::REMISE, (float) CashReceivedFromDeliveryman::find($remise->id)->amount);
    }
}
