<?php

namespace App\Notifications\Channels;

use App\Mail\MerchantFeedMail;
use App\Models\Backend\GeneralSettings;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Canal « courriel » du fil marchand — la moitié manquante de
 * `.claude/rules/customs.md` (« notification (email/push) »).
 *
 * Il existe pour la même raison que `PushChannel`, et il en est le jumeau :
 * brancher l'envoi au niveau du CANAL laisse les six observers et
 * `MerchantFeed` inchangés. On n'ajoute pas un émetteur, on complète `via()`.
 *
 * ⚠️ POURQUOI PAS LE CANAL `mail` DU SOCLE. Le canal de Laravel bâtit et
 * remet le message **dans la requête** tant que la notification n'est pas
 * `ShouldQueue` — et la rendre `ShouldQueue` déplacerait aussi l'écriture du
 * fil en base, qui doit rester synchrone (l'app la lit aussitôt). **D13** veut
 * l'inverse : le fil écrit tout de suite, l'envoi sortant part en file. D'où un
 * canal à nous, qui met en file un `Mailable` et rend la main.
 *
 * ⚠️ F4 — LA MARQUE VIENT DU DESTINATAIRE, PAS DE `settings()`. Ce canal
 * s'exécute là où l'observer a écrit : une création de colis par l'API, mais
 * aussi un import Excel ou une commande. Hors requête, `settings()` retombe sur
 * la société 1 et le marchand de « Kola Distribution » recevrait un message
 * signé du premier transporteur de la base — c'est le défaut déjà corrigé dans
 * `MerchantSignup`. La société est donc lue sur l'utilisateur notifié, qui la
 * porte toujours.
 */
class EmailChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!$notifiable instanceof User || !method_exists($notification, 'toEmail')) {
            return;
        }

        $adresse = trim((string) $notifiable->email);
        if ($adresse === '') {
            return; // un compte sans adresse : rien à tenter
        }

        $contenu = $notification->toEmail($notifiable);
        if (!is_array($contenu) || blank($contenu['title'] ?? null)) {
            return; // le canal ne devine pas de texte, comme `PushChannel`
        }

        try {
            Mail::to($adresse)->queue(
                new MerchantFeedMail($contenu, $this->marqueDe($notifiable), (string) $notifiable->name)
            );
        } catch (\Throwable $e) {
            // Même tolérance que le push : un envoi raté ne fait jamais échouer
            // l'écriture métier qui l'a déclenché (ici, l'alerte douanière).
            Log::warning('Courriel du fil non émis', [
                'user_id' => $notifiable->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Les éléments de marque du transporteur **du destinataire**.
     *
     * Les mêmes cinq lectures que le gabarit d'inscription : nom, logo,
     * courriel et téléphone de contact, mentions de bas de page.
     *
     * @return array{nom:?string,logo:mixed,courriel:?string,telephone:?string,mentions:?string}
     */
    private function marqueDe(User $user): array
    {
        $societe = GeneralSettings::with('rxlogo')->find($user->company_id);

        return [
            'nom' => $societe?->name,
            'logo' => $societe?->rxlogo?->original,
            'courriel' => $societe?->email,
            'telephone' => $societe?->phone,
            'mentions' => $societe?->copyright,
        ];
    }
}
