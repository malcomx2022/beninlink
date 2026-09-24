<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Le courriel d'une entrée du fil marchand.
 *
 * Une seule classe pour toutes les familles, comme `MerchantNotification` :
 * ce qui distingue un message est son `kind`, son titre et son corps, déjà
 * rédigés en français par `MerchantFeed`. Le gabarit n'a rien à traduire.
 *
 * **D13** — `ShouldQueue` : l'envoi quitte la requête. En `sync` (installation
 * sans worker) il s'exécute immédiatement, comme `SendPush`.
 *
 * ⚠️ **F4 — aucune lecture de `settings()` ici.** Les autres `Mailable` du
 * dépôt figent la marque dans leur constructeur parce qu'ils sont construits
 * *dans la requête* ; celui-ci peut naître d'un import ou d'une commande, où
 * `settings()` retombe sur la société 1. La marque lui est donc **passée**,
 * résolue par `EmailChannel` sur la société du destinataire.
 */
class MerchantFeedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param array{kind:string,title:string,body:string,extra?:array} $contenu
     * @param array{nom:?string,logo:mixed,courriel:?string,telephone:?string,mentions:?string} $marque
     */
    public function __construct(
        public readonly array $contenu,
        public readonly array $marque,
        public readonly string $destinataire,
    ) {
    }

    public function build()
    {
        $message = $this->subject((string) $this->contenu['title']);

        // `from()` refuse une adresse vide : une société dont le courriel de
        // contact n'est pas renseigné part avec l'expéditeur de l'application.
        if (filled($this->marque['courriel'] ?? null)) {
            $message = $message->from($this->marque['courriel']);
        }

        return $message->view('backend.merchant.mail.feed', [
            'destinataire' => $this->destinataire,
            'kind' => (string) $this->contenu['kind'],
            'titre' => (string) $this->contenu['title'],
            'corps' => (string) $this->contenu['body'],
            'extra' => (array) ($this->contenu['extra'] ?? []),
            'companyName' => $this->marque['nom'],
            'companyLogo' => $this->marque['logo'],
            'courriel' => $this->marque['courriel'],
            'telephone' => $this->marque['telephone'],
            'mentions' => $this->marque['mentions'],
        ]);
    }
}
