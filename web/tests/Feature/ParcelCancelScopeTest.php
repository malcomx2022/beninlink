<?php

namespace Tests\Feature;

use App\Enums\BooleanStatus;
use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\VatStatement;
use App\Models\User;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S53 — les deux dernières annulations, et pourquoi elles ont résisté.
 *
 * Ces deux routes portent leur garde depuis **S45**. Elles sont restées à
 * l'arriéré du filet S38 jusqu'ici non pas faute de garde, mais faute de
 * **preuve** — et S52 a établi la raison, qui vaut d'être écrite ici.
 *
 * ⚠️ **Deux tentatives précédentes ont échoué, et toutes deux en passant.**
 *
 * 1. J'ai d'abord cru la portée prouvée par `PartialDeliveryAccountingTest` et
 *    `DeliveryCancellationAccountingTest`, d'après un relevé antérieur.
 *    Sabotage des deux gardes, puis la suite **entière** : aucun test ne tombe.
 *    L'attribution était fausse.
 *
 * 2. J'ai alors écrit deux cas avec un colis étranger **minimal**. Ils
 *    passaient, et le sabotage les a trouvés **creux** : les deux méthodes
 *    lisent `$deliveryManAssign->deliveryMan->id` dans leur branche `else`, et
 *    sans évènement `DELIVERY_MAN_ASSIGN` cette lecture lève. La transaction
 *    était annulée, le `catch` rendait `false` — **le refus ne venait pas de la
 *    garde**.
 *
 * > Pour prouver une garde, le chemin **non gardé** doit RÉUSSIR. Un colis
 * > d'en face incomplet ne prouve rien : il fait échouer la méthode pour une
 * > raison qui n'a rien à voir avec le périmètre.
 *
 * D'où ce fichier : un colis étranger **complet** — livreur assigné, évènement
 * de statut, montants numériques, marchand porteur de son taux de retour — tel
 * que la méthode aboutirait si la garde n'était pas là. Ce que la garde empêche
 * devient alors observable :
 *
 * | Méthode | Ce que le chemin non gardé écrirait |
 * |---|---|
 * | `parcelPartialDeliveredCancel` | un `VatStatement` à **notre** `company_id` sur **leur** colis, le statut ramené à `DELIVERY_MAN_ASSIGN`, l'évènement de livraison partielle **supprimé** |
 * | `returnReceivedByMerchant` | un `ParcelEvent` de retour reçu, deux `MerchantStatement` (frais et TVA, S73) débitant **leur** marchand, le solde de **leur** livreur modifié |
 */
class ParcelCancelScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->actingAs($this->agentDe((int) settings()->id));
    }

    /**
     * L'annulation d'une livraison partielle réécrit les montants du colis et
     * inscrit une écriture de TVA. Sur le colis d'en face, rien ne doit bouger.
     */
    public function test_a_partial_delivery_of_another_company_is_never_cancelled(): void
    {
        $sien = $this->colisComplet(self::AUTRE, ParcelStatus::PARTIAL_DELIVERED);

        $this->assertFalse(
            (bool) app(ParcelInterface::class)->parcelPartialDeliveredCancel($sien->id, new Request([])),
            'la livraison partielle d\'une autre societe a ete annulee',
        );

        // Le statut, la chronologie et la TVA : aucun des trois ne bouge.
        $this->assertSame(ParcelStatus::PARTIAL_DELIVERED, (int) $sien->fresh()->status);
        $this->assertSame(1, ParcelEvent::where([
            'parcel_id' => $sien->id, 'parcel_status' => ParcelStatus::PARTIAL_DELIVERED,
        ])->count(), 'l\'evenement de livraison partielle du colis d\'en face a ete supprime');
        $this->assertSame(0, VatStatement::where('parcel_id', $sien->id)->count());
        $this->assertSame(0, CourierStatement::where('parcel_id', $sien->id)->count());

        // ⚠️ Contrôle positif : sur NOTRE colis, le même appel aboutit. Sans lui,
        // un refus pour n'importe quelle autre raison validerait le test — c'est
        // exactement ce qui avait rendu mes deux tentatives precedentes creuses.
        $mien = $this->colisComplet((int) settings()->id, ParcelStatus::PARTIAL_DELIVERED);
        $this->assertTrue(
            (bool) app(ParcelInterface::class)->parcelPartialDeliveredCancel($mien->id, new Request([])),
            'le chemin legitime ne passe pas : le cas negatif ne prouverait rien',
        );
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, (int) $mien->fresh()->status);
        $this->assertSame(BooleanStatus::NO, (int) $mien->fresh()->partial_delivered);
        $this->assertSame(1, VatStatement::where('parcel_id', $mien->id)->count());
    }

    /**
     * Le retour rendu au marchand fait avancer le colis et débite son marchand.
     * Sur le colis d'en face, ni l'un ni l'autre.
     */
    public function test_a_return_of_another_company_is_never_received_by_merchant(): void
    {
        $sien = $this->colisComplet(self::AUTRE, ParcelStatus::RETURN_TO_COURIER);

        $this->assertFalse(
            (bool) app(ParcelInterface::class)->returnReceivedByMerchant($sien->id, new Request(['note' => 'S53'])),
            'le retour d\'une autre societe a ete rendu a son marchand',
        );

        $this->assertSame(ParcelStatus::RETURN_TO_COURIER, (int) $sien->fresh()->status);
        $this->assertSame(0, ParcelEvent::where([
            'parcel_id' => $sien->id, 'parcel_status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ])->count(), 'un evenement de retour recu a ete ecrit sur le colis d\'en face');
        $this->assertSame(0, MerchantStatement::where('parcel_id', $sien->id)->count());

        // ⚠️ Contrôle positif, même raison que ci-dessus.
        $mien = $this->colisComplet((int) settings()->id, ParcelStatus::RETURN_TO_COURIER);
        $this->assertTrue(
            (bool) app(ParcelInterface::class)->returnReceivedByMerchant($mien->id, new Request(['note' => 'S53'])),
            'le chemin legitime ne passe pas : le cas negatif ne prouverait rien',
        );
        $this->assertSame(1, ParcelEvent::where([
            'parcel_id' => $mien->id, 'parcel_status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ])->count());
        // Deux lignes depuis S73 (D2 q.6) : le frais de retour, et sa TVA.
        $this->assertSame(2, MerchantStatement::where('parcel_id', $mien->id)->count());
    }

    /* ────────────────────────────── fixtures ───────────────────────────────── */

    /**
     * Un colis **complet** : c'est tout l'objet de ce fichier.
     *
     * Il porte un livreur assigné et son évènement `DELIVERY_MAN_ASSIGN` (que
     * les deux méthodes lisent dans leur branche `else`), des montants
     * numériques (les calculs de TVA et de charges en dépendent), et un
     * évènement au statut demandé. Sans tout cela, le chemin non gardé lève au
     * lieu d'aboutir, et le test ne mesure plus la garde.
     */
    private function colisComplet(int $societe, int $statut): Parcel
    {
        $marchand = $this->marchandDe($societe);
        $livreur  = $this->livreurDe($societe);

        $colis = Parcel::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-S53-' . $societe . '-' . uniqid(),
            'customer_name' => 'Client ' . $societe,
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 5000,
            'old_cash_collection' => 10000,
            'current_payable' => 9000,
            'cod_charge' => 2,
            'delivery_charge' => 1000,
            'liquid_fragile_amount' => 0,
            'packaging_amount' => 0,
            'vat' => 18,
            'vat_amount' => 180,
            'cod_amount' => 200,
            'total_delivery_amount' => 1200,
            'partial_delivered' => BooleanStatus::YES,
            'status' => $statut,
            'priority_type_id' => 1,
        ]);

        // L'evenement que la branche `else` des deux methodes exige.
        $this->evenement($colis, ParcelStatus::DELIVERY_MAN_ASSIGN, $livreur->id);
        // Et l'evenement du statut courant, que l'annulation supprime.
        $this->evenement($colis, $statut, $livreur->id);

        return $colis;
    }

    private function evenement(Parcel $colis, int $statut, int $livreur): void
    {
        ParcelEvent::forceCreate([
            'parcel_id' => $colis->id,
            'delivery_man_id' => $livreur,
            'parcel_status' => $statut,
            'note' => 'S53',
            'created_by' => Auth()->id() ?? 1,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S53';
        $agent->email = 'agent.s53.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229974' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe): Merchant
    {
        $n = User::count();
        $u = new User();
        $u->company_id = $societe;
        $u->name = 'Marchand S53';
        $u->email = 'm.s53.' . $societe . '.' . $n . '@example.test';
        $u->mobile = '00229973' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME S53 ' . $societe,
            'current_balance' => 100000, 'opening_balance' => 100000,
            'return_charges' => 100, 'status' => Status::ACTIVE,
        ]);
    }

    private function livreurDe(int $societe): DeliveryMan
    {
        $n = User::count();
        $u = new User();
        $u->company_id = $societe;
        $u->name = 'Livreur S53';
        $u->email = 'l.s53.' . $societe . '.' . $n . '@example.test';
        $u->mobile = '00229972' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::DELIVERYMAN;
        $u->unique_id = 'L-S53-' . $societe . '-' . $n;
        $u->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id, 'status' => Status::ACTIVE,
            'delivery_charge' => 500, 'pickup_charge' => 200, 'return_charge' => 300,
            'opening_balance' => 50000, 'current_balance' => 50000,
        ]);
    }
}
