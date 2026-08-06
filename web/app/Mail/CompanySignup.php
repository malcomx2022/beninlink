<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels; 
class CompanySignup extends Mailable
{
    protected $data;
    public function __construct($data=null)
    {
        $this->data = $data;
    }
    public function build()
    {
        $data          = $this->data;
        $courier_email = settings()->email;
        return $this->from($courier_email)->subject('Welcome to new company')->view('backend.super-admin.company.mail.signup',compact('data'));
    }
}
