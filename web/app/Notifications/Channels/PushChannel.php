<?php

namespace App\Notifications\Channels;

use App\Jobs\SendPush;
use App\Models\User;
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
    public function send($notifiable, Notification $notification): void
    {
        if (!$notifiable instanceof User || !method_exists($notification, 'toPush')) {
            return;
        }

        $message = $notification->toPush($notifiable);
        if (!$message instanceof PushMessage) {
            return;
        }

        // D13 — l'appel au service de push quitte la requête : le fil en base
        // est déjà écrit quand on arrive ici, l'agent ou le webhook qui a
        // déclenché l'événement n'a pas à attendre un aller-retour réseau.
        // En `sync` (installation sans worker), le job s'exécute immédiatement.
        try {
            dispatch(SendPush::pour($notifiable, $message));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Push non émis', [
                'user_id' => $notifiable->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
