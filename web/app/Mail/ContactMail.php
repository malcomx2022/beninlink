<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;

    /**
     * D13 — l'expéditeur est figé **ici**, dans la requête.
     *
     * Le mail part désormais en file : à l'exécution, `settings()` retomberait
     * sur la société 1 et le message serait envoyé au nom d'un autre
     * transporteur (constat F4, déjà fermé côté SMS).
     */
    protected array $societe = [];

    public function __construct($data = null)
    {
        $this->data = $data;
        $this->societe = [
            'email' => settings()?->email,
            'name' => settings()?->name,
            'logo' => settings()?->LogoImage,
            'mentions' => settings()?->copyright,
        ];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        $logoImage = $this->societe['logo'];
        // Le logo est le seul contenu de son lien : son alternative textuelle
        // porte le nom du transporteur. Il est déjà figé ci-dessus — le lire
        // avec `settings()` au rendu le ferait retomber sur la société 1 (F4),
        // le gabarit étant bâti par le worker.
        $companyName = $this->societe['name'];
        $mentions = $this->societe['mentions'];
        // S13 — l'expéditeur était l'adresse SAISIE PAR LE VISITEUR : usurpation
        // possible, et rejets SPF/DKIM puisque le serveur n'est pas autorisé à
        // écrire au nom d'un domaine tiers.
        // L'expéditeur est désormais celui de la plateforme ; l'adresse du
        // visiteur devient l'adresse de réponse, ce qui préserve l'usage
        // (répondre au message) sans mentir sur l'origine.
        return $this->view('backend.contact.contact_mail',compact('data','logoImage','companyName','mentions'))
            ->from($this->societe['email'], $this->societe['name'])
            ->replyTo($data['email'], $data['name'] ?? null)
            ->to($this->societe['email'])
            ->subject($data['subject']);
    }
}
