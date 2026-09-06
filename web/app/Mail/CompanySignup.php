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

    public function __construct($data=null)
    {
        $this->expediteur = settings()?->email;
        $this->data = $data;
    }
    public function build()
    {
        $data          = $this->data;
        $courier_email = $this->expediteur;
        return $this->from($courier_email)->subject('Welcome to new company')->view('backend.super-admin.company.mail.signup',compact('data'));
    }
}
