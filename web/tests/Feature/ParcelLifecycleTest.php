<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\MerchantShops;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le cycle de vie d'un colis — les étapes qui ne déplacent pas d'argent.
 *
 * Ramassage assigné, reçu par le ramasseur, entrepôt, transfert entre agences,
 * livreur assigné, reprogrammations : aucune n'écrit au grand livre. Elles
 * n'en sont pas moins comptables au second degré, et c'est tout l'enjeu de ce
 * fichier : **elles décident qui sera payé à l'arrivée**.
 *
 * La livraison ne choisit pas son livreur, elle le **lit** — dans le dernier
 * événement de reprogrammation, à défaut dans celui d'affectation. Une
 * affectation posée de travers, et c'est un autre livreur qui encaisse sa
 * course. Les tests vont donc jusqu'au bout de la chaîne : ils franchissent les
 * étapes par les vraies méthodes, puis livrent, et regardent **qui a été payé**.
 */
class ParcelLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private Merchant $marchand;
    private Parcel $colis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->current_balance = 0;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());
        $this->colis = $this->colisEnAttente();
    }

    /** Un colis neuf, avant toute affectation. */
    private function colisEnAttente(?Merchant $proprietaire = null, string $suivi = 'BL-CYCLE'): Parcel
    {
        $proprietaire ??= $this->marchand;

        $colis = new Parcel();
        $colis->forceFill([
            'company_id' => $proprietaire->company_id,
            'merchant_id' => $proprietaire->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou, Akpakpa',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => self::CASH,
            'delivery_charge' => 1000,
            'cod_charge' => 1,
            'cod_amount' => 200,
            'vat' => 18,
            'vat_amount' => self::VAT,
            'liquid_fragile_amount' => 0,
            'packaging_amount' => 0,
            'total_delivery_amount' => self::CHARGES,
            'current_payable' => self::PAYABLE,
            'tracking_id' => $suivi,
            'status' => ParcelStatus::PENDING,
        ])->save();

        return $colis->fresh();
    }

    private function repo(): ParcelInterface
    {
        return app(ParcelInterface::class);
    }

    private function statut(?Parcel $colis = null): int
    {
        return (int) Parcel::find(($colis ?? $this->colis)->id)->status;
    }

    private function solde(DeliveryMan $livreur): float
    {
        return (float) DeliveryMan::find($livreur->id)->current_balance;
    }

    // ---- la chaîne de ramassage ------------------------------------------

    /** Du marchand à l'entrepôt, étape par étape. */
    public function test_the_pickup_chain_walks_the_parcel_to_the_warehouse(): void
    {
        $ramasseur = $this->livreur('R1');

        $this->assertTrue($this->repo()->pickupdatemanAssigned($this->colis->id, new Request(['delivery_man_id' => $ramasseur->id])));
        $this->assertSame(ParcelStatus::PICKUP_ASSIGN, $this->statut());

        $this->assertTrue($this->repo()->receivedBypickupman($this->colis->id, new Request()));
        $this->assertSame(ParcelStatus::RECEIVED_BY_PICKUP_MAN, $this->statut());

        $this->assertTrue($this->repo()->receivedWarehouse($this->colis->id, new Request(['hub_id' => auth()->user()->hub_id])));
        $this->assertSame(ParcelStatus::RECEIVED_WAREHOUSE, $this->statut());
    }

    /**
     * L'enjeu de l'affectation : c'est le ramasseur inscrit à l'événement qui
     * touche sa course quand le colis entre en entrepôt.
     */
    public function test_the_warehouse_reception_pays_the_assigned_pickup_man(): void
    {
        $ramasseur = $this->livreur('R1');
        $ramasseur->pickup_charge = 250;
        $ramasseur->save();

        $this->repo()->pickupdatemanAssigned($this->colis->id, new Request(['delivery_man_id' => $ramasseur->id]));
        $this->repo()->receivedWarehouse($this->colis->id, new Request(['hub_id' => auth()->user()->hub_id]));

        $this->assertSame(250.0, $this->solde($ramasseur));
    }

    /**
     * Reprogrammer le ramassage déplace la course : c'est le nouveau ramasseur
     * qui sera payé, pas celui du premier rendez-vous.
     */
    public function test_rescheduling_the_pickup_moves_the_payment(): void
    {
        $premier = $this->livreur('R1');
        $second  = $this->livreur('R2');
        foreach ([$premier, $second] as $r) { $r->pickup_charge = 250; $r->save(); }

        $this->repo()->pickupdatemanAssigned($this->colis->id, new Request(['delivery_man_id' => $premier->id]));
        $this->repo()->PickupReSchedule($this->colis->id, new Request(['delivery_man_id' => $second->id, 'date' => date('Y-m-d')]));
        $this->repo()->receivedWarehouse($this->colis->id, new Request(['hub_id' => auth()->user()->hub_id]));

        $this->assertSame(0.0, $this->solde($premier), 'Le ramasseur ecarte ne doit rien toucher.');
        $this->assertSame(250.0, $this->solde($second));
    }

    // ---- la chaîne de livraison ------------------------------------------

    /** De l'entrepôt au client. */
    public function test_the_delivery_chain_walks_the_parcel_to_the_customer(): void
    {
        $livreur = $this->livreur('L1');

        $this->assertTrue($this->repo()->deliverymanAssign($this->colis->id, new Request(['delivery_man_id' => $livreur->id])));
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, $this->statut());

        $this->assertTrue($this->repo()->parcelDelivered($this->colis->id, new Request()));
        $this->assertSame(ParcelStatus::DELIVERED, $this->statut());
        $this->assertSame(self::COURSE - self::CASH, $this->solde($livreur));
    }

    /**
     * Reprogrammer la livraison efface l'affectation précédente : un seul
     * livreur reste en lice, et c'est lui qui encaisse.
     */
    public function test_rescheduling_the_delivery_moves_the_payment(): void
    {
        $premier = $this->livreur('L1');
        $second  = $this->livreur('L2');

        $this->repo()->deliverymanAssign($this->colis->id, new Request(['delivery_man_id' => $premier->id]));
        $this->repo()->deliveryReschedule($this->colis->id, new Request(['delivery_man_id' => $second->id, 'date' => date('Y-m-d')]));
        $this->repo()->parcelDelivered($this->colis->id, new Request());

        $this->assertSame(0.0, $this->solde($premier), 'Le livreur ecarte ne doit rien toucher.');
        $this->assertSame(self::COURSE - self::CASH, $this->solde($second));
    }

    /**
     * Réaffecter un colis à un autre livreur — sans passer par la
     * reprogrammation — doit désigner **le dernier** nommé. Le socle relisait
     * la **première** affectation : le livreur écarté encaissait la course
     * d'une tournée qu'il n'avait pas faite, et celui qui l'avait faite
     * n'était pas payé.
     */
    public function test_reassigning_pays_the_last_deliveryman_named(): void
    {
        $premier = $this->livreur('L1');
        $second  = $this->livreur('L2');

        $this->repo()->deliverymanAssign($this->colis->id, new Request(['delivery_man_id' => $premier->id]));
        $this->repo()->deliverymanAssign($this->colis->id, new Request(['delivery_man_id' => $second->id]));
        $this->repo()->parcelDelivered($this->colis->id, new Request());

        $this->assertSame(0.0, $this->solde($premier), 'Le livreur ecarte ne doit rien toucher.');
        $this->assertSame(self::COURSE - self::CASH, $this->solde($second));
    }

    // ---- le transfert entre agences --------------------------------------

    /** Un colis passe d'une agence à l'autre, et la seconde le reçoit. */
    public function test_a_parcel_transfers_between_hubs(): void
    {
        $agence = Hub::where('id', '!=', auth()->user()->hub_id)->firstOr(fn () => Hub::firstOrFail());

        $this->assertTrue($this->repo()->transfertohub($this->colis->id, new Request(['hub_id' => $agence->id])));
        $this->assertSame(ParcelStatus::TRANSFER_TO_HUB, $this->statut());

        $this->assertTrue($this->repo()->receivedByHub($this->colis->id, new Request()));
        $this->assertSame(ParcelStatus::RECEIVED_BY_HUB, $this->statut());
    }

    // ---- ce qui ne doit pas arriver --------------------------------------

    /**
     * Aucune étape ne franchit la frontière de société. Le socle lisait
     * `Parcel::find($id)` nu partout : un administrateur faisait avancer le
     * colis d'un autre transporteur — et désignait au passage qui y serait
     * payé.
     */
    public function test_no_step_touches_a_parcel_of_another_company(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $colisAilleurs = $this->colisEnAttente($ailleurs, 'BL-AILLEURS');
        $livreur = $this->livreur('L1');

        $etapes = [
            'pickupdatemanAssigned' => ['delivery_man_id' => $livreur->id],
            'PickupReSchedule' => ['delivery_man_id' => $livreur->id, 'date' => date('Y-m-d')],
            'receivedBypickupman' => [],
            'receivedWarehouse' => ['hub_id' => auth()->user()->hub_id],
            'transfertohub' => ['hub_id' => auth()->user()->hub_id],
            'receivedByHub' => [],
            'deliverymanAssign' => ['delivery_man_id' => $livreur->id],
            'deliveryReschedule' => ['delivery_man_id' => $livreur->id, 'date' => date('Y-m-d')],
            'returntoQourier' => [],
        ];

        foreach ($etapes as $etape => $champs) {
            $this->assertFalse(
                $this->repo()->{$etape}($colisAilleurs->id, new Request($champs)),
                "l'etape {$etape} a touche le colis d'une autre societe",
            );
        }

        $this->assertSame(ParcelStatus::PENDING, $this->statut($colisAilleurs));
        $this->assertSame(0, ParcelEvent::where('parcel_id', $colisAilleurs->id)->count());
    }

    /**
     * Et on n'affecte pas le livreur d'un autre transporteur : il serait payé
     * par une société qui ne l'emploie pas, sur les colis d'une autre.
     */
    public function test_a_deliveryman_of_another_company_cannot_be_assigned(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $livreurAilleurs = $this->livreur('L9', $ailleurs->company_id);

        $this->assertFalse($this->repo()->deliverymanAssign($this->colis->id, new Request(['delivery_man_id' => $livreurAilleurs->id])));
        $this->assertFalse($this->repo()->pickupdatemanAssigned($this->colis->id, new Request(['delivery_man_id' => $livreurAilleurs->id])));

        $this->assertSame(ParcelStatus::PENDING, $this->statut());
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->colis->id)->count());
    }

    /**
     * Même symétrie côté ramassage : réaffecter désigne le dernier ramasseur
     * nommé, pas le premier.
     */
    public function test_reassigning_the_pickup_man_pays_the_last_named(): void
    {
        $premier = $this->livreur('R1');
        $second  = $this->livreur('R2');
        foreach ([$premier, $second] as $r) { $r->pickup_charge = 250; $r->save(); }

        $this->repo()->pickupdatemanAssigned($this->colis->id, new Request(['delivery_man_id' => $premier->id]));
        $this->repo()->pickupdatemanAssigned($this->colis->id, new Request(['delivery_man_id' => $second->id]));
        $this->repo()->receivedWarehouse($this->colis->id, new Request(['hub_id' => auth()->user()->hub_id]));

        $this->assertSame(0.0, $this->solde($premier), 'Le ramasseur ecarte ne doit rien toucher.');
        $this->assertSame(250.0, $this->solde($second));
    }

    /**
     * Une étape qui échoue n'écrit rien du tout — pas même l'événement de
     * suivi, que le socle enregistrait **avant** de charger le colis.
     *
     * Jusqu'ici, seule la contrainte de clé étrangère l'en empêchait, et par
     * accident : le colis introuvable faisait échouer l'insertion. C'est
     * désormais la garde qui refuse, avant d'écrire quoi que ce soit.
     */
    public function test_a_failing_step_leaves_no_orphan_event(): void
    {
        $livreur = $this->livreur('L1');

        $this->assertFalse($this->repo()->deliverymanAssign(999999, new Request(['delivery_man_id' => $livreur->id])));

        $this->assertSame(0, ParcelEvent::count());
    }
}
