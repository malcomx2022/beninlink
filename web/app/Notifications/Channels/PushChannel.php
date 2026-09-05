<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Push\PushGateway;
use App\Services\Push\PushMessage;
use Illuminate\Notifications\Notification;

/**
 * Canal « push » du système de notifications de Laravel.
 *
 * Il existe pour une raison : le fil marchand était déjà écrit par six
 * observers passant tous par `MerchantFeed`. Brancher le push au niveau du
 * canal fait pousser **les six familles d'événements** sans qu'aucun émetteur
 * change — c'est exactement ce que la classe `MerchantNotification` annonçait
 * (« Les ajouter reviendra à compléter `via()` »).
 *
 * Une notification qui n'expose pas `toPush()` n'est pas poussée : le canal ne
 * devine pas de texte.
 */
class PushChannel
{
    public function __construct(private PushGateway $gateway)
    {
    }

    public function send($notifiable, Notification $notification): void
    {
        if (!$notifiable instanceof User || !method_exists($notification, 'toPush')) {
            return;
        }

        $message = $notification->toPush($notifiable);
        if (!$message instanceof PushMessage) {
            return;
        }

        // La passerelle n'émet pas d'exception ; ce garde-fou couvre le cas où
        // une future implémentation en laisserait passer une : le fil en base
        // est déjà écrit, il ne doit pas être perdu pour un push.
        try {
            $this->gateway->toUser($notifiable, $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Push non émis', [
                'user_id' => $notifiable->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
