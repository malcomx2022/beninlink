<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Push\PushGateway;
use App\Services\Push\PushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Une notification poussée, envoyée hors de la requête HTTP (décision D13).
 *
 * Le destinataire voyage par son **identifiant**, pas par son modèle : le job
 * peut attendre en file quelques secondes, et c'est l'état du compte au moment
 * de l'envoi qui compte — ses appareils, notamment, qui peuvent avoir changé.
 *
 * Le texte, lui, voyage tel qu'il a été rédigé : il décrit un fait daté (« colis
 * BL-123 livré »), le relire plus tard ne le rendrait pas plus juste.
 */
class SendPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 120];

    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }

    public static function pour(User $user, PushMessage $message): self
    {
        return new self($user->id, $message->title, $message->body, $message->data);
    }

    public function handle(PushGateway $gateway): void
    {
        $user = User::find($this->userId);
        if (blank($user)) {
            return; // compte supprimé entre-temps
        }

        $gateway->toUser($user, new PushMessage($this->title, $this->body, $this->data));
    }
}
