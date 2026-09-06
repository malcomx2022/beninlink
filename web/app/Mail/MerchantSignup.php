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

    public function __construct($data=null)
    {
        $this->expediteur = settings()?->email;
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
        return $this->from($courier_email)->subject('Welcome to new merchant')->view('backend.merchant.mail.signup',compact('data'));
    }
}
