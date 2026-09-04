<?php

namespace App\Services\Notifications;

use App\Enums\ApprovalStatus;
use App\Enums\UserType;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\PushNotification;
use App\Models\Backend\Wallet;
use App\Models\User;
use App\Notifications\MerchantNotification;
use Illuminate\Support\Facades\Log;

/**
 * Émetteur unique du fil de notifications marchand.
 *
 * Les observers (app/Observers/Feed) ne font qu'appeler une méthode d'ici :
 * les textes, les destinataires et la tolérance aux pannes vivent en un seul
 * endroit. Une notification qui échoue **ne doit jamais** faire échouer
 * l'écriture métier qui l'a déclenchée (changement de statut, crédit de
 * wallet…) : chaque méthode avale et journalise ses erreurs.
 *
 * Les textes sont rédigés à l'émission, dans la locale du serveur (FR) : l'app
 * les affiche tels quels, comme les libellés de statut.
 */
class MerchantFeed
{
    /** Un colis vient de changer de statut. */
    public function parcelStatusChanged(Parcel $parcel): void
    {
        $this->deliver($this->merchantUser($parcel->merchant_id), function () use ($parcel) {
            $status = trans('parcelStatus.' . $parcel->status);
            $body = __('notification.parcel_status_body', [
                'tracking' => $parcel->tracking_id,
                'customer' => $parcel->customer_name,
            ]);

            $deliveryMan = $parcel->delivery_man_id ? DeliveryMan::find($parcel->delivery_man_id) : null;
            if ($deliveryMan && $deliveryMan->user) {
                $body .= ' ' . __('notification.parcel_status_courier', ['name' => $deliveryMan->user->name]);
            }

            return new MerchantNotification(MerchantNotification::KIND_PARCEL_STATUS, $status, $body, [
                'parcel_id' => $parcel->id,
                'tracking_id' => $parcel->tracking_id,
                'status' => (int) $parcel->status,
            ]);
        });
    }

    /** Un mouvement de wallet vient d'être approuvé (FedaPay ou crédit manuel). */
    public function walletCredited(Wallet $wallet): void
    {
        if ((int) $wallet->status !== WalletStatus::APPROVED || (int) $wallet->type !== WalletType::INCOME) {
            return;
        }

        $this->deliver($this->merchantUser($wallet->merchant_id), function () use ($wallet) {
            $amount = (int) round((float) $wallet->amount);

            return new MerchantNotification(
                MerchantNotification::KIND_WALLET_CREDIT,
                __('notification.wallet_credit_title'),
                __('notification.wallet_credit_body', [
                    'amount' => formatAmount($amount),
                    'source' => $wallet->source ?: __('notification.wallet_source_default'),
                ]),
                ['amount' => $amount, 'reference' => (string) $wallet->transaction_id],
            );
        });
    }

    /** Un relevé de règlement (facture) vient d'être émis pour le marchand. */
    public function invoiceIssued(Invoice $invoice): void
    {
        $this->deliver($this->merchantUser($invoice->merchant_id), function () use ($invoice) {
            $amount = (int) round((float) $invoice->current_payable);

            return new MerchantNotification(
                MerchantNotification::KIND_INVOICE,
                __('notification.invoice_title', ['invoice' => $invoice->invoice_id]),
                __('notification.invoice_body', ['amount' => formatAmount($amount)]),
                ['invoice_id' => $invoice->id, 'reference' => (string) $invoice->invoice_id, 'amount' => $amount],
            );
        });
    }

    /** Une alerte douanière vient d'être émise sur un colis d'export. */
    public function customsAlertRaised(CustomsAlert $alert): void
    {
        $this->deliver($this->merchantUser($alert->merchant_id), function () use ($alert) {
            $parcel = $alert->parcel;

            return new MerchantNotification(
                MerchantNotification::KIND_CUSTOMS,
                __('notification.customs_title', [
                    'level' => $alert->level_name,
                    'country' => $alert->country_name,
                ]),
                trim(($parcel ? $parcel->tracking_id . ' — ' : '') . $alert->message),
                [
                    'parcel_id' => $alert->parcel_id,
                    'tracking_id' => $parcel?->tracking_id,
                    'customs_alert_id' => $alert->id,
                    'level' => (int) $alert->level,
                ],
            );
        });
    }

    /** L'administration a rédigé un message (table `push_notifications`). */
    public function adminMessage(PushNotification $message): void
    {
        foreach ($this->recipientsOf($message) as $user) {
            $this->deliver($user, fn () => new MerchantNotification(
                MerchantNotification::KIND_MESSAGE,
                (string) $message->title,
                (string) $message->description,
                ['message_id' => $message->id],
            ));
        }
    }

    /** Une demande de retrait vient de changer d'état (approuvée, traitée, rejetée). */
    public function payoutUpdated(Payment $payment): void
    {
        if ((int) $payment->status === ApprovalStatus::PENDING) {
            return; // retour en attente : rien à annoncer
        }

        $this->deliver($this->merchantUser($payment->merchant_id), function () use ($payment) {
            $amount = (int) round((float) $payment->amount);

            return new MerchantNotification(
                MerchantNotification::KIND_PAYOUT,
                __('notification.payout_title', ['status' => trans('approvalstatus.' . $payment->status)]),
                __('notification.payout_body', ['amount' => formatAmount($amount)]),
                ['payment_id' => $payment->id, 'amount' => $amount, 'status' => (int) $payment->status],
            );
        });
    }

    // — Destinataires et envoi ---------------------------------------------

    private function merchantUser(?int $merchantId): ?User
    {
        if (!$merchantId) {
            return null;
        }

        return Merchant::find($merchantId)?->user;
    }

    /**
     * Destinataires marchands d'un message admin : un marchand désigné, un
     * utilisateur désigné (s'il est marchand), ou tous les marchands de la
     * société quand le message vise « tous » ou le rôle marchand.
     *
     * @return iterable<User>
     */
    private function recipientsOf(PushNotification $message): iterable
    {
        if ($message->merchant_id) {
            $user = $this->merchantUser((int) $message->merchant_id);

            return $user ? [$user] : [];
        }

        if ($message->user_id) {
            $user = User::find($message->user_id);

            return $user && (int) $user->user_type === UserType::MERCHANT ? [$user] : [];
        }

        $type = (string) $message->type;
        if ($type === 'all' || $type === (string) UserType::MERCHANT) {
            return User::where('company_id', $message->company_id)
                ->where('user_type', UserType::MERCHANT)
                ->get();
        }

        return [];
    }

    /** @param callable():MerchantNotification $build */
    private function deliver(?User $user, callable $build): void
    {
        if (!$user) {
            return;
        }

        try {
            $user->notify($build());
        } catch (\Throwable $e) {
            // Le fil est un confort ; l'écriture métier qui l'a déclenché ne
            // doit pas en dépendre.
            Log::warning('Notification marchand non émise', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
