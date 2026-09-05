<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\PushChannel;
use App\Services\Push\PushMessage;
use Illuminate\Notifications\Notification;

/**
 * Une entrée du fil de notifications d'un marchand.
 *
 * Une seule classe pour tous les événements : ce qui distingue une entrée est
 * son `kind`, lu par l'app pour choisir l'icône, et ses textes, déjà rédigés
 * en français par `MerchantFeed`. Une classe par événement n'apporterait ici
 * qu'un nom de type de plus en base.
 *
 * Deux canaux : `database` (le fil que lit l'app) et `push` (la notification
 * poussée sur l'appareil). Le second ne s'ajoute que si l'utilisateur a au
 * moins un appareil abonné — sinon on n'appelle rien. L'e-mail, lui, n'a
 * toujours pas de gabarit.
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
        $canaux = ['database'];

        // Interroger la table plutôt que de tenter un push à vide : la très
        // grande majorité des comptes du back-office n'a aucun appareil.
        if ($notifiable instanceof User && $notifiable->deviceTokens()->exists()) {
            $canaux[] = PushChannel::class;
        }

        return $canaux;
    }

    /**
     * Le même texte que le fil, et les mêmes clés en `data` : l'app ouvre
     * l'écran concerné au toucher sans avoir à traduire un second vocabulaire.
     */
    public function toPush($notifiable): PushMessage
    {
        return new PushMessage($this->title, $this->body, array_merge(
            ['kind' => $this->kind],
            array_map(fn ($valeur) => is_scalar($valeur) || $valeur === null ? $valeur : (string) $valeur, $this->extra),
        ));
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
