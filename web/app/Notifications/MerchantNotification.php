<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Une entrée du fil de notifications d'un marchand.
 *
 * Une seule classe pour tous les événements : ce qui distingue une entrée est
 * son `kind`, lu par l'app pour choisir l'icône, et ses textes, déjà rédigés
 * en français par `MerchantFeed`. Une classe par événement n'apporterait ici
 * qu'un nom de type de plus en base.
 *
 * Canal `database` seulement : le push FCM du socle est hors service (bloc K
 * de la cartographie) et l'e-mail n'a pas de gabarit. Les ajouter reviendra à
 * compléter `via()`, sans toucher aux émetteurs.
 */
class MerchantNotification extends Notification
{
    public const KIND_PARCEL_STATUS = 'parcel_status';
    public const KIND_WALLET_CREDIT = 'wallet_credit';
    public const KIND_INVOICE = 'invoice';
    public const KIND_CUSTOMS = 'customs';
    public const KIND_MESSAGE = 'message';
    public const KIND_PAYOUT = 'payout';

    public function __construct(
        public readonly string $kind,
        public readonly string $title,
        public readonly string $body,
        public readonly array $extra = [],
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge([
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
        ], $this->extra);
    }
}
