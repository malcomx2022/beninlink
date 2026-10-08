<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantSignup extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;
    /** D13 — expéditeur figé dans la requête : voir ContactMail. */
    protected ?string $expediteur = null;

    /**
     * F4 — la société figée dans la requête, comme l'expéditeur.
     *
     * Ce gabarit affiche le logo du transporteur, son nom (en texte et en
     * alternative de l'image), son courriel et son téléphone de contact, et ses
     * mentions de bas de page : cinq lectures de `settings()`. Or il est bâti
     * **par le worker** (`ShouldQueue`), où `settings()` retombe sur la société 1.
     * Un marchand de « Kola Distribution » recevait un message signé du premier
     * transporteur de la base, logo compris.
     */
    protected ?string $marque = null;

    protected ?string $logo = null;

    protected array $societe = [];

    public function __construct($data=null)
    {
        $this->expediteur = settings()?->email;
        $this->marque = settings()?->name;
        $this->logo = settings()?->rxlogo?->original;
        $this->societe = [
            'titre' => settings()?->title,
            'courriel' => settings()?->email,
            'telephone' => settings()?->phone,
            'mentions' => settings()?->copyright,
        ];
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
        $courier_email = $this->expediteur;
        return $this->from($courier_email)->subject(__('Welcome to :name', ['name' => $this->marque]))->view('backend.merchant.mail.signup',compact('data') + ['companyName' => $this->marque, 'companyLogo' => $this->logo] + $this->societe);
    }
}
