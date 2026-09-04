<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Enums\ParcelStatus;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\PushNotification;
use App\Models\Backend\Wallet;
use App\Models\MerchantShops;
use App\Notifications\MerchantNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Fil de notifications du marchand — source de l'ecran `notifications` de
 * mobile/.
 *
 * Chaque evenement metier est rejoue par son ecriture Eloquent reelle (c'est
 * l'observer qui doit reagir, pas un appel direct au service), puis l'API est
 * exercee : liste scopee a l'utilisateur, lecture unitaire, tout marquer lu.
 */
class MerchantNotificationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();
    }

    private function colis(string $tracking = 'BL-TEST-1'): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha K.',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => $tracking,
            'status' => ParcelStatus::PENDING,
        ])->save();

        return $parcel;
    }

    private function kinds(): array
    {
        return $this->merchant->user->notifications()->orderBy('created_at')->get()
            ->map(fn ($n) => $n->data['kind'])->all();
    }

    public function test_un_changement_de_statut_de_colis_notifie_le_marchand(): void
    {
        $parcel = $this->colis();
        $this->assertSame([], $this->kinds(), 'la creation seule ne notifie pas');

        $parcel->status = ParcelStatus::PICKUP_ASSIGN;
        $parcel->save();

        // Un enregistrement sans changement de statut reste muet.
        $parcel->customer_address = 'Porto-Novo';
        $parcel->save();

        $this->assertSame([MerchantNotification::KIND_PARCEL_STATUS], $this->kinds());

        $data = $this->merchant->user->notifications()->first()->data;
        $this->assertSame(trans('parcelStatus.' . ParcelStatus::PICKUP_ASSIGN), $data['title']);
        $this->assertSame('BL-TEST-1', $data['tracking_id']);
        $this->assertSame($parcel->id, $data['parcel_id']);
        $this->assertStringContainsString('Aicha K.', $data['body']);
    }

    public function test_un_credit_de_wallet_approuve_notifie_le_marchand(): void
    {
        $wallet = new Wallet();
        $wallet->forceFill([
            'company_id' => $this->merchant->company_id,
            'source' => 'FedaPay',
            'user_id' => $this->merchant->user_id,
            'merchant_id' => $this->merchant->id,
            'transaction_id' => 'BL-RECH-1',
            'amount' => 50000,
            'type' => WalletType::INCOME,
            'payment_method' => WalletPaymentMethod::OFFLINE,
            'status' => WalletStatus::PENDING,
        ])->save();
        $this->assertSame([], $this->kinds(), 'une recharge en attente ne notifie pas');

        $wallet->status = WalletStatus::APPROVED;
        $wallet->save();

        $this->assertSame([MerchantNotification::KIND_WALLET_CREDIT], $this->kinds());
        $data = $this->merchant->user->notifications()->first()->data;
        $this->assertSame(50000, $data['amount']);
        $this->assertStringContainsString('FedaPay', $data['body']);
    }

    public function test_un_releve_emis_et_une_alerte_douaniere_notifient_le_marchand(): void
    {
        $invoice = new Invoice();
        $invoice->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'invoice_id' => 'FAC-2026-118',
            'invoice_date' => now(),
            'total_charge' => 0,
            'cash_collection' => 0,
            'current_payable' => 123000,
            'parcels_id' => [],
            'status' => \App\Enums\InvoiceStatus::UNPAID,
        ])->save();

        $parcel = $this->colis('BL-EXPORT-1');
        CustomsAlert::create([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'parcel_id' => $parcel->id,
            'customs_rule_id' => null,
            'country_code' => 'NG',
            'country_name' => 'Nigeria',
            'goods_category' => 'textile',
            'level' => CustomsLevel::WARNING,
            'required_document' => 'Certificat d\'origine',
            'message' => 'Certificat d\'origine exige.',
            'status' => CustomsAlertStatus::PENDING,
        ]);

        $this->assertSame(
            [MerchantNotification::KIND_INVOICE, MerchantNotification::KIND_CUSTOMS],
            $this->kinds()
        );
    }

    public function test_un_message_admin_et_un_retrait_traite_notifient_le_marchand(): void
    {
        $message = new PushNotification();
        $message->forceFill([
            'company_id' => $this->merchant->company_id,
            'title' => 'Maintenance samedi',
            'description' => 'Le service sera interrompu de 2h a 4h.',
            'type' => 'all',
        ])->save();

        $payment = new Payment();
        $payment->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'amount' => 25000,
            'merchant_account' => 1,
            'status' => ApprovalStatus::PENDING,
        ])->save();
        $payment->status = ApprovalStatus::PROCESSED;
        $payment->save();

        $this->assertSame(
            [MerchantNotification::KIND_MESSAGE, MerchantNotification::KIND_PAYOUT],
            $this->kinds()
        );
    }

    public function test_l_api_liste_les_notifications_du_marchand_et_les_marque_lues(): void
    {
        $parcel = $this->colis();
        $parcel->status = ParcelStatus::DELIVERED;
        $parcel->save();

        // Une notification d'un autre utilisateur ne doit jamais apparaitre.
        $autre = $this->merchant->user->replicate();
        $autre->email = 'voisin@example.test';
        $autre->mobile = '0022997000009';
        $autre->unique_id = 'U-VOISIN';
        $autre->save();
        $autre->notify(new MerchantNotification(MerchantNotification::KIND_MESSAGE, 'Secret', 'du voisin'));

        Sanctum::actingAs($this->merchant->user);
        $entetes = ['apiKey' => self::API_KEY];

        $liste = $this->getJson('/api/v10/notifications/index', $entetes)->assertOk();
        $this->assertSame(1, $liste->json('data.unread_count'));
        $this->assertCount(1, $liste->json('data.notifications'));
        $this->assertSame(MerchantNotification::KIND_PARCEL_STATUS, $liste->json('data.notifications.0.kind'));
        $this->assertFalse($liste->json('data.notifications.0.read'));

        $id = $liste->json('data.notifications.0.id');
        $this->putJson('/api/v10/notifications/' . $id . '/read', [], $entetes)
            ->assertOk()
            ->assertJsonPath('data.notification.read', true);

        $this->getJson('/api/v10/notifications/unread-count', $entetes)
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        // Celle du voisin est introuvable, pas « interdite ».
        $idVoisin = $autre->notifications()->first()->id;
        $this->putJson('/api/v10/notifications/' . $idVoisin . '/read', [], $entetes)->assertNotFound();

        $parcel->status = ParcelStatus::RETURNED_MERCHANT;
        $parcel->save();
        $this->putJson('/api/v10/notifications/read-all', [], $entetes)
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
        $this->assertSame(0, $this->merchant->user->unreadNotifications()->count());
    }
}
