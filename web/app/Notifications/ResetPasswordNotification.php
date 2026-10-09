<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * **S143** — le courriel de réinitialisation part **en file** (D13), en français, avec le lien du site demandé.
 *
 * La notification de Laravel partait dans la requête (envoi SMTP pendant la réponse) et en anglais :
 * `lang/fr.json` ne traduisait aucune de ses phrases. En file, elle est bâtie par le worker, où il n'y a
 * ni hôte ni société (F4) : le lien, la marque et la langue sont donc **figés à la demande**, par
 * `User::sendPasswordResetNotification()`.
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token, public string $lien, public string $marque)
    {
        parent::__construct($token);
    }

    protected function resetUrl($notifiable)
    {
        return $this->lien;
    }

    public function toMail($notifiable)
    {
        return $this->buildMailMessage($this->lien)->salutation(__('Regards') . ', ' . $this->marque);
    }
}
