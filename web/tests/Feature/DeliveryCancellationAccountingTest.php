<?php

namespace Tests\Feature;

use App\Enums\BooleanStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\VatStatement;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * L'annulation d'une livraison — le miroir de l'étape la plus lourde du socle.
 *
 * Une livraison se retire pour de bonnes raisons : le colis revient, le client
 * conteste, l'opérateur s'est trompé de ligne. L'annulation doit alors remettre
 * **exactement** les quatre comptes où ils étaient, ni plus ni moins. C'est la
 * seule propriété qui compte vraiment ici, et c'est celle que ce fichier fixe :
 * livrer puis annuler ne laisse aucun solde déplacé.
 *
 * Elle porte les mêmes obligations que l'étape qu'elle inverse (**D8**) : une
 * seule fois, chez soi, tout ou rien. Le socle n'en tenait aucune — et une
 * annulation était plus dangereuse encore qu'une livraison en double, puisque
 * rien ne vérifiait qu'il y avait bien quelque chose à annuler.
 */
class DeliveryCancellationAccountingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private Merchant $marchand;
    private DeliveryMan $livreur;
    private Parcel $colis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->current_balance = 0;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());

        $this->livreur = $this->livreur();
        $this->colis = $this->colisConfie($this->marchand, $this->livreur, 'BL-ANNULE');
    }

    private function livrer(?int $id = null): bool
    {
        return app(ParcelInterface::class)->parcelDelivered($id ?? $this->colis->id, new Request());
    }

    private function annuler(?int $id = null): bool
    {
        return app(ParcelInterface::class)->parcelDeliveredCancel($id ?? $this->colis->id, new Request());
    }

    private function livrerPartiellement(?int $id = null, float $encaisse = 12000): bool
    {
        return app(ParcelInterface::class)->parcelPartialDelivered(
            $id ?? $this->colis->id,
            new Request(['cash_collection' => $encaisse]),
        );
    }

    private function annulerPartielle(?int $id = null): bool
    {
        return app(ParcelInterface::class)->parcelPartialDeliveredCancel($id ?? $this->colis->id, new Request());
    }

    private function soldeMarchand(?Merchant $marchand = null): float
    {
        return (float) Merchant::find(($marchand ?? $this->marchand)->id)->current_balance;
    }

    private function soldeLivreur(?DeliveryMan $livreur = null): float
    {
        return (float) DeliveryMan::find(($livreur ?? $this->livreur)->id)->current_balance;
    }

    // ---- l'inversion ------------------------------------------------------

    /**
     * La propriété qui résume tout : livrer puis annuler ne déplace aucun
     * solde. Si un jour ce n'est plus vrai, c'est qu'un mouvement a été ajouté
     * d'un côté sans l'être de l'autre.
     */
    public function test_delivering_then_cancelling_leaves_every_balance_untouched(): void
    {
        $this->livrer();
        $this->assertTrue($this->annuler());

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0.0, $this->soldeLivreur());
    }

    /** Chaque écriture a sa contrepartie : les relevés se soldent à zéro. */
    public function test_every_statement_has_its_counterpart(): void
    {
        $this->livrer();
        $this->annuler();

        foreach ([MerchantStatement::class, DeliverymanStatement::class, CourierStatement::class] as $releve) {
            $this->assertSame(
                0.0,
                $this->solde($releve),
                'Le releve ' . class_basename($releve) . ' ne se solde pas a zero.',
            );
        }
    }

    /** Le colis repart au statut d'où il venait, pour pouvoir être relivré. */
    public function test_the_parcel_goes_back_to_the_assigned_state(): void
    {
        $this->livrer();
        $this->annuler();

        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, (int) Parcel::find($this->colis->id)->status);
        $this->assertSame(0, ParcelEvent::where([
            'parcel_id' => $this->colis->id,
            'parcel_status' => ParcelStatus::DELIVERED,
        ])->count());
    }

    /** Même inversion complète pour la livraison partielle. */
    public function test_a_partial_delivery_cancels_back_to_zero(): void
    {
        $this->livrerPartiellement();
        $this->assertTrue($this->annulerPartielle());

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0.0, $this->soldeLivreur());
        $this->assertSame(BooleanStatus::NO, (int) Parcel::find($this->colis->id)->partial_delivered);
    }

    // ---- ce qui ne doit pas arriver --------------------------------------

    /**
     * Annuler deux fois inversait deux fois : le marchand se retrouvait débité
     * d'un encaissement qu'il n'avait jamais eu, et le livreur crédité d'une
     * course qu'il n'avait pas faite.
     */
    public function test_cancelling_twice_reverses_once(): void
    {
        $this->livrer();
        $this->assertTrue($this->annuler());

        $this->assertFalse($this->annuler(), 'Une livraison deja annulee ne s\'annule pas.');

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0.0, $this->soldeLivreur());
    }

    /**
     * Et annuler une livraison **qui n'a jamais eu lieu** écrivait la
     * contrepartie dans le vide : le marchand débité des frais d'un colis
     * encore en entrepôt, le livreur crédité de l'encaissement d'un colis
     * qu'il n'a pas remis. C'est plus grave qu'une livraison en double : rien
     * ne signalait qu'il n'y avait rien à annuler.
     */
    public function test_cancelling_a_delivery_that_never_happened_changes_nothing(): void
    {
        $this->assertFalse($this->annuler());

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0.0, $this->soldeLivreur());
        $this->assertSame(0, MerchantStatement::count());
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, (int) Parcel::find($this->colis->id)->status);
    }

    /** Idem côté partiel : sans livraison partielle, rien à inverser. */
    public function test_cancelling_a_partial_delivery_that_never_happened_changes_nothing(): void
    {
        $this->assertFalse($this->annulerPartielle());

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0, MerchantStatement::count());
    }

    /** Une livraison complète ne s'annule pas par la porte du partiel. */
    public function test_a_full_delivery_is_not_cancelled_by_the_partial_route(): void
    {
        $this->livrer();

        $this->assertFalse($this->annulerPartielle());

        $this->assertSame(self::PAYABLE, $this->soldeMarchand());
        $this->assertSame(ParcelStatus::DELIVERED, (int) Parcel::find($this->colis->id)->status);
    }

    /**
     * Le colis d'un autre transporteur reste hors de portée — y compris quand
     * il a été **réellement** livré, donc quand l'annulation aurait de quoi
     * s'exécuter jusqu'au bout.
     */
    public function test_a_parcel_of_another_company_cannot_be_cancelled(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $livreurAilleurs = $this->livreur('9', $ailleurs->company_id);

        // La livraison a lieu dans le contexte du voisin.
        $this->actingAs($ailleurs->user->fresh());
        $colisAilleurs = $this->colisConfie($ailleurs, $livreurAilleurs, 'BL-AILLEURS', [
            'company_id' => $ailleurs->company_id,
        ]);
        $this->assertTrue($this->livrer($colisAilleurs->id));
        $soldeVoisin = $this->soldeMarchand($ailleurs);
        $ecritures = MerchantStatement::count();

        // De retour chez nous, elle doit rester inannulable.
        $this->actingAs($this->marchand->user->fresh());
        $this->assertFalse($this->annuler($colisAilleurs->id));

        $this->assertSame($soldeVoisin, $this->soldeMarchand($ailleurs));
        $this->assertSame($ecritures, MerchantStatement::count());
        $this->assertSame(ParcelStatus::DELIVERED, (int) Parcel::find($colisAilleurs->id)->status);
    }

    /**
     * L'inversion est atomique : si elle échoue au milieu, elle ne laisse pas
     * la moitié des comptes remis et l'autre moitié en l'état — un écart
     * qu'aucune relance ne saurait rattraper.
     */
    public function test_a_failing_cancellation_leaves_the_books_as_they_were(): void
    {
        $this->livrer();
        $soldeMarchand = $this->soldeMarchand();
        $soldeLivreur = $this->soldeLivreur();
        $ecritures = MerchantStatement::count();

        // L'annulation commence par **supprimer** l'événement « Livré », puis
        // inverse les comptes. En privant l'affectation de son livreur, la
        // seconde étape échoue une fois la première faite : sans transaction,
        // l'événement disparaissait alors que les comptes restaient en l'état.
        ParcelEvent::where('parcel_id', $this->colis->id)
            ->where('parcel_status', ParcelStatus::DELIVERY_MAN_ASSIGN)
            ->update(['delivery_man_id' => null]);

        $this->assertFalse($this->annuler());

        $this->assertSame($soldeMarchand, $this->soldeMarchand());
        $this->assertSame($soldeLivreur, $this->soldeLivreur());
        $this->assertSame($ecritures, MerchantStatement::count());
        $this->assertSame(ParcelStatus::DELIVERED, (int) Parcel::find($this->colis->id)->status);
        // L'événement « Livré » est toujours là : rien n'a été fait à moitié.
        $this->assertSame(1, ParcelEvent::where([
            'parcel_id' => $this->colis->id,
            'parcel_status' => ParcelStatus::DELIVERED,
        ])->count());
    }

    // ---- les deux autres annulations qui touchent aux comptes -------------

    /**
     * L'annulation de la réception en entrepôt reprend au ramasseur la course
     * qu'on venait de lui payer. Le contrôle de statut n'entourait que la
     * suppression de l'événement : les écritures, elles, s'exécutaient à
     * chaque appel, si bien qu'annuler deux fois la lui reprenait deux fois.
     */
    public function test_cancelling_a_warehouse_reception_takes_the_round_back_once(): void
    {
        $ramasseur = $this->livreur('3');
        $ramasseur->pickup_charge = 250;
        $ramasseur->current_balance = 250;
        $ramasseur->save();

        $colis = $this->colisConfie($this->marchand, $ramasseur, 'BL-ENTREPOT', [
            'status' => ParcelStatus::RECEIVED_WAREHOUSE,
        ]);
        $this->evenementRamassage($colis, $ramasseur);

        $repo = app(ParcelInterface::class);
        $this->assertTrue($repo->receivedWarehouseCancel($colis->id, new Request()));
        $this->assertSame(0.0, $this->soldeLivreur($ramasseur));

        $this->assertFalse($repo->receivedWarehouseCancel($colis->id, new Request()));
        $this->assertSame(0.0, $this->soldeLivreur($ramasseur), 'La course ne doit pas etre reprise deux fois.');
    }

    /** Et pas davantage sur le colis d'un autre transporteur. */
    public function test_a_warehouse_reception_of_another_company_cannot_be_cancelled(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $ramasseurAilleurs = $this->livreur('8', $ailleurs->company_id);
        $ramasseurAilleurs->pickup_charge = 250;
        $ramasseurAilleurs->current_balance = 250;
        $ramasseurAilleurs->save();

        $colisAilleurs = $this->colisConfie($ailleurs, $ramasseurAilleurs, 'BL-ENTREPOT-AILLEURS', [
            'company_id' => $ailleurs->company_id,
            'status' => ParcelStatus::RECEIVED_WAREHOUSE,
        ]);
        $this->evenementRamassage($colisAilleurs, $ramasseurAilleurs);

        $this->assertFalse(app(ParcelInterface::class)->receivedWarehouseCancel($colisAilleurs->id, new Request()));

        $this->assertSame(250.0, $this->soldeLivreur($ramasseurAilleurs));
        $this->assertSame(ParcelStatus::RECEIVED_WAREHOUSE, (int) Parcel::find($colisAilleurs->id)->status);
    }

    /**
     * L'annulation de l'affectation d'un retour reprend au livreur ses frais
     * de retour. Ses écritures étaient déjà enfermées dans le contrôle de
     * statut ; seul le scope société manquait.
     */
    public function test_cancelling_a_return_assignment_of_another_company_is_refused(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $livreurAilleurs = $this->livreur('7', $ailleurs->company_id);
        $livreurAilleurs->return_charge = 400;
        $livreurAilleurs->current_balance = 400;
        $livreurAilleurs->save();

        $colisAilleurs = $this->colisConfie($ailleurs, $livreurAilleurs, 'BL-RETOUR-AILLEURS', [
            'company_id' => $ailleurs->company_id,
            'status' => ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
        ]);
        $this->evenement($colisAilleurs, $livreurAilleurs, ParcelStatus::RETURN_ASSIGN_TO_MERCHANT);

        $this->assertFalse(app(ParcelInterface::class)->returnAssignToMerchantCancel($colisAilleurs->id, new Request()));

        $this->assertSame(400.0, $this->soldeLivreur($livreurAilleurs));
        $this->assertSame(ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, (int) Parcel::find($colisAilleurs->id)->status);
    }

    /** L'événement de ramassage, que l'annulation d'entrepôt relit. */
    private function evenementRamassage(Parcel $colis, DeliveryMan $ramasseur): void
    {
        $evenement = new ParcelEvent();
        $evenement->forceFill([
            'parcel_id' => $colis->id,
            'pickup_man_id' => $ramasseur->id,
            'parcel_status' => ParcelStatus::PICKUP_ASSIGN,
            'created_by' => auth()->id(),
        ])->save();

        $entrepot = new ParcelEvent();
        $entrepot->forceFill([
            'parcel_id' => $colis->id,
            'parcel_status' => ParcelStatus::RECEIVED_WAREHOUSE,
            'created_by' => auth()->id(),
        ])->save();
    }

    /** Somme signée d'un relevé : revenus moins dépenses. */
    private function solde(string $modele): float
    {
        return (float) $modele::where('type', \App\Enums\StatementType::INCOME)->sum('amount')
            - (float) $modele::where('type', \App\Enums\StatementType::EXPENSE)->sum('amount');
    }
}
