<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        $logoImage = settings()->LogoImage;
        // S13 — l'expéditeur était l'adresse SAISIE PAR LE VISITEUR : usurpation
        // possible, et rejets SPF/DKIM puisque le serveur n'est pas autorisé à
        // écrire au nom d'un domaine tiers.
        // L'expéditeur est désormais celui de la plateforme ; l'adresse du
        // visiteur devient l'adresse de réponse, ce qui préserve l'usage
        // (répondre au message) sans mentir sur l'origine.
        return $this->view('backend.contact.contact_mail',compact('data','logoImage'))
            ->from(settings()->email, settings()->name)
            ->replyTo($data['email'], $data['name'] ?? null)
            ->to(settings()->email)
            ->subject($data['subject']);
    }
}
