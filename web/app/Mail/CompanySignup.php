<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels; 
class CompanySignup extends Mailable implements ShouldQueue
{
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
    public function build()
    {
        $data          = $this->data;
        $courier_email = $this->expediteur;
        return $this->from($courier_email)->subject('Welcome to new company')->view('backend.super-admin.company.mail.signup',compact('data') + ['companyName' => $this->marque, 'companyLogo' => $this->logo] + $this->societe);
    }
}
