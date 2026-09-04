<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\Merchant_panel\PaymentMethod;
use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Department;
use App\Models\Backend\Fraud;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Payment;
use App\Models\Backend\Support;
use App\Models\MerchantPayment;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S7 — les ressources à identifiant que la cartographie n'avait pas encore
 * passées au crible : fiches de fraude, tickets de support, comptes de
 * versement, demandes de retrait (côté marchand) et colis confiés (côté
 * livreur). Le socle lisait chacune par `find($id)` nu.
 *
 * Toutes les requêtes partent d'un compte A contre une ressource du compte B
 * de la MÊME société : un scoping par `company_id` seul ne suffirait pas.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;
    private Merchant $voisin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();
        $this->merchant->current_balance = 100000;
        $this->merchant->save();

        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin@example.test';
        $autreUtilisateur->mobile = '0022997000009';
        $autreUtilisateur->unique_id = 'U-VOISIN';
        $autreUtilisateur->save();

        $this->voisin = $this->merchant->replicate();
        $this->voisin->user_id = $autreUtilisateur->id;
        $this->voisin->merchant_unique_id = 'M-VOISIN';
        $this->voisin->save();
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function connecterMarchand(): void
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);
    }

    // ---- fixtures --------------------------------------------------------

    private function fraudeDe(Merchant $merchant): Fraud
    {
        return Fraud::forceCreate([
            'company_id' => $merchant->company_id,
            'created_by' => $merchant->user_id,
            'phone' => '0022997' . str_pad((string) $merchant->id, 6, '0', STR_PAD_LEFT),
            'name' => 'Fraudeur de ' . $merchant->merchant_unique_id,
            'details' => 'Colis refusés à répétition',
        ]);
    }

    private function ticketDe(Merchant $merchant): Support
    {
        return Support::forceCreate([
            'user_id' => $merchant->user_id,
            'department_id' => Department::first()?->id,
            'service' => 'Livraison',
            'priority' => 'high',
            'subject' => 'Ticket de ' . $merchant->merchant_unique_id,
            'description' => 'Contenu confidentiel du ticket',
            'date' => now()->toDateString(),
        ]);
    }

    private function compteDe(Merchant $merchant): MerchantPayment
    {
        return MerchantPayment::forceCreate([
            'merchant_id' => $merchant->id,
            'payment_method' => PaymentMethod::mobile,
            'holder_name' => 'Titulaire ' . $merchant->merchant_unique_id,
            'mobile_company' => 'MTN MoMo',
            'mobile_no' => '2299700' . str_pad((string) $merchant->id, 4, '0', STR_PAD_LEFT),
            'account_type' => 'personal',
        ]);
    }

    private function retraitDe(Merchant $merchant): Payment
    {
        return Payment::forceCreate([
            'company_id' => $merchant->company_id,
            'merchant_id' => $merchant->id,
            'merchant_account' => $this->compteDe($merchant)->id,
            'amount' => 5000,
            'description' => 'Retrait de ' . $merchant->merchant_unique_id,
            'status' => ApprovalStatus::PENDING,
            'created_by' => UserType::MERCHANT,
        ]);
    }

    private function livreur(string $suffixe): User
    {
        $user = $this->merchant->user->replicate();
        $user->name = 'Livreur ' . $suffixe;
        $user->email = 'livreur-' . $suffixe . '@example.test';
        $user->mobile = '00229980000' . strlen($suffixe);
        $user->unique_id = 'L-' . $suffixe;
        $user->user_type = UserType::DELIVERYMAN;
        $user->password = Hash::make('secret123');
        $user->save();

        DeliveryMan::forceCreate([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 0, 'pickup_charge' => 0, 'return_charge' => 0,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);

        return $user->fresh();
    }

    private function colisConfieA(User $livreur): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            // `details()` filtre sur le hub du compte connecté : le colis est au sien.
            'hub_id' => $livreur->hub_id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Porto-Novo',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => 'TEST-' . $livreur->unique_id,
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ])->save();

        $event = new ParcelEvent();
        $event->forceFill([
            'parcel_id' => $parcel->id,
            'delivery_man_id' => $livreur->deliveryman->id,
            'parcel_status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
            'created_by' => $livreur->id,
        ])->save();

        return $parcel;
    }

    // ---- marchand --------------------------------------------------------

    public function test_a_neighbours_fraud_report_is_out_of_reach(): void
    {
        $sienne = $this->fraudeDe($this->merchant);
        $duVoisin = $this->fraudeDe($this->voisin);
        $this->connecterMarchand();

        $this->getJson("/api/v10/fraud/edit/{$duVoisin->id}", $this->entetes())->assertStatus(404);
        $this->putJson("/api/v10/fraud/update/{$duVoisin->id}", ['phone' => '0022990000000', 'name' => 'X', 'details' => 'x'], $this->entetes())->assertStatus(404);
        $this->deleteJson("/api/v10/fraud/delete/{$duVoisin->id}", [], $this->entetes())->assertStatus(404);
        $this->assertDatabaseHas('frauds', ['id' => $duVoisin->id, 'name' => $duVoisin->name]);

        // La sienne reste lisible, et la liste noire de la société contient les deux.
        $this->getJson("/api/v10/fraud/edit/{$sienne->id}", $this->entetes())->assertOk();
        $this->getJson('/api/v10/fraud/index', $this->entetes())->assertOk()
            ->assertJsonFragment(['phone' => $sienne->phone])
            ->assertJsonFragment(['phone' => $duVoisin->phone]);
    }

    public function test_a_neighbours_support_ticket_is_out_of_reach(): void
    {
        $sien = $this->ticketDe($this->merchant);
        $duVoisin = $this->ticketDe($this->voisin);
        $this->connecterMarchand();

        $this->getJson("/api/v10/support/edit/{$duVoisin->id}", $this->entetes())->assertStatus(404);
        $this->getJson("/api/v10/support/view/{$duVoisin->id}", $this->entetes())->assertStatus(404);
        $this->deleteJson("/api/v10/support/delete/{$duVoisin->id}", [], $this->entetes())->assertStatus(404);
        $this->postJson('/api/v10/support/reply', ['support_id' => $duVoisin->id, 'message' => 'Intrusion'], $this->entetes())->assertStatus(404);
        $this->assertDatabaseMissing('support_chats', ['support_id' => $duVoisin->id]);
        $this->assertDatabaseHas('supports', ['id' => $duVoisin->id]);

        $this->getJson("/api/v10/support/view/{$sien->id}", $this->entetes())->assertOk();
        $this->postJson('/api/v10/support/reply', ['support_id' => $sien->id, 'message' => 'Merci'], $this->entetes())->assertOk();
    }

    public function test_a_neighbours_payout_account_is_out_of_reach(): void
    {
        $sien = $this->compteDe($this->merchant);
        $duVoisin = $this->compteDe($this->voisin);
        $this->connecterMarchand();

        $this->getJson("/api/v10/payment-account/edit/{$duVoisin->id}", $this->entetes())->assertStatus(404);
        $this->putJson('/api/v10/payment-account/update', [
            'id' => $duVoisin->id,
            'payment_method' => PaymentMethod::mobile,
            'mobile_holder_name' => 'Pirate', 'mobile_company' => 'MTN MoMo', 'mobile_no' => '22990000000', 'account_type' => 'personal',
        ], $this->entetes())->assertStatus(404);
        $this->deleteJson("/api/v10/payment-account/delete/{$duVoisin->id}", [], $this->entetes())->assertStatus(404);
        $this->assertDatabaseHas('merchant_payments', ['id' => $duVoisin->id, 'holder_name' => $duVoisin->holder_name]);

        $this->getJson("/api/v10/payment-account/edit/{$sien->id}", $this->entetes())->assertOk();
    }

    public function test_a_neighbours_payout_request_is_out_of_reach(): void
    {
        $sien = $this->retraitDe($this->merchant);
        $duVoisin = $this->retraitDe($this->voisin);
        $this->connecterMarchand();

        $this->getJson("/api/v10/payment-request/edit/{$duVoisin->id}", $this->entetes())->assertStatus(404);
        $this->putJson("/api/v10/payment-request/update/{$duVoisin->id}", [
            'amount' => 1000, 'merchant_account' => $sien->merchant_account, 'description' => 'x',
        ], $this->entetes())->assertStatus(404);
        $this->deleteJson("/api/v10/payment-request/delete/{$duVoisin->id}", [], $this->entetes())->assertStatus(404);
        $this->assertDatabaseHas('payments', ['id' => $duVoisin->id, 'merchant_id' => $this->voisin->id, 'amount' => 5000]);

        $this->getJson("/api/v10/payment-request/edit/{$sien->id}", $this->entetes())->assertOk();
        $this->deleteJson("/api/v10/payment-request/delete/{$sien->id}", [], $this->entetes())->assertOk();
        $this->assertDatabaseMissing('payments', ['id' => $sien->id]);
    }

    // ---- livreur ---------------------------------------------------------

    public function test_a_deliveryman_only_acts_on_parcels_assigned_to_him(): void
    {
        $moi = $this->livreur('A');
        $collegue = $this->livreur('BB');
        $monColis = $this->colisConfieA($moi);
        $sonColis = $this->colisConfieA($collegue);

        Sanctum::actingAs($moi, ['deliveryman']);

        $this->getJson("/api/v10/deliveryman/parcel/details/{$sonColis->id}", $this->entetes())->assertStatus(404);
        $this->postJson("/api/v10/deliveryman/parcel/delivered/{$sonColis->id}", [], $this->entetes())->assertStatus(404);
        $this->postJson("/api/v10/deliveryman/parcel/partial-delivered/{$sonColis->id}", ['cash_collection' => 1000], $this->entetes())->assertStatus(404);
        $this->postJson('/api/v10/deliveryman/parcel-status-update', [
            'parcel_id' => $sonColis->id, 'status_action' => ParcelStatus::DELIVERED,
        ], $this->entetes())->assertStatus(404);
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, (int) $sonColis->fresh()->status);

        $this->getJson("/api/v10/deliveryman/parcel/details/{$monColis->id}", $this->entetes())->assertOk()
            ->assertJsonPath('data.parcel.id', $monColis->id);
        $this->getJson('/api/v10/deliveryman/parcel/index', $this->entetes())->assertOk()
            ->assertJsonFragment(['id' => $monColis->id])
            ->assertJsonMissing(['id' => $sonColis->id]);
    }

    public function test_a_deliveryman_only_updates_his_own_positions(): void
    {
        $moi = $this->livreur('A');
        $collegue = $this->livreur('BB');
        $this->colisConfieA($moi);
        $sonColis = $this->colisConfieA($collegue);

        Sanctum::actingAs($moi, ['deliveryman']);

        // `deliveryID` désigne le collègue : il doit être ignoré.
        $this->postJson('/api/v10/deliveryman/parcel-location-update', [
            'deliveryID' => $collegue->id, 'lat' => '6.36', 'long' => '2.42',
        ], $this->entetes())->assertOk();

        $this->assertNull(ParcelEvent::where('parcel_id', $sonColis->id)->first()->delivery_lat);
        $this->assertNotNull(ParcelEvent::where('delivery_man_id', $moi->deliveryman->id)->first()->delivery_lat);
    }
}
