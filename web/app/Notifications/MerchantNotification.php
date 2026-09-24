<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\PushChannel;
use App\Services\Push\PushMessage;
use Illuminate\Notifications\Notification;

/**
 * Une entrée du fil de notifications d'un marchand.
 *
 * Une seule classe pour tous les événements : ce qui distingue une entrée est
 * son `kind`, lu par l'app pour choisir l'icône, et ses textes, déjà rédigés
 * en français par `MerchantFeed`. Une classe par événement n'apporterait ici
 * qu'un nom de type de plus en base.
 *
 * Trois canaux : `database` (le fil que lit l'app), `push` (la notification
 * poussée sur l'appareil) et `courriel`. Les deux derniers sont CONDITIONNELS,
 * et pour des raisons différentes :
 *
 *  - le push ne s'ajoute que si l'utilisateur a au moins un appareil abonné —
 *    sinon on n'appelle rien ;
 *  - le courriel ne s'ajoute que pour les familles de `COURRIEL`, parce qu'un
 *    courriel par changement de statut de colis serait du harcèlement.
 */
class MerchantNotification extends Notification
{
    public const KIND_PARCEL_STATUS = 'parcel_status';
    public const KIND_WALLET_CREDIT = 'wallet_credit';
    public const KIND_INVOICE = 'invoice';
    public const KIND_CUSTOMS = 'customs';
    public const KIND_MESSAGE = 'message';
    public const KIND_PAYOUT = 'payout';

    public function __construct(
        public readonly string $kind,
        public readonly string $title,
        public readonly string $body,
        public readonly array $extra = [],
    ) {
    }

    /**
     * Les familles pour lesquelles un COURRIEL part, en plus du fil.
     *
     * ⚠️ C'est une LISTE, pas un drapeau, et c'est le cœur de la décision.
     * `.claude/rules/customs.md` demande « notification (email/push) » pour
     * l'alerte douanière : un marchand dont le colis est bloqué à la frontière
     * doit l'apprendre sans ouvrir l'app. Étendre l'envoi aux six familles
     * aurait envoyé un courriel à CHAQUE changement de statut de colis — un
     * marchand à trente colis par jour en recevrait une centaine par semaine,
     * et les marquerait indésirables, ce qui coûterait aussi les alertes.
     *
     * Une famille s'ajoute ici quand le métier la décide, une ligne à la fois.
     * Un test garde ce choix (`test_un_changement_de_statut_de_colis_n_envoie_pas_de_courriel`) :
     * l'élargissement doit être voulu, pas subi.
     */
    private const COURRIEL = [self::KIND_CUSTOMS];

    public function via($notifiable): array
    {
        $canaux = ['database'];

        // Interroger la table plutôt que de tenter un push à vide : la très
        // grande majorité des comptes du back-office n'a aucun appareil.
        if ($notifiable instanceof User && $notifiable->deviceTokens()->exists()) {
            $canaux[] = PushChannel::class;
        }

        if (in_array($this->kind, self::COURRIEL, true)) {
            $canaux[] = EmailChannel::class;
        }

        return $canaux;
    }

    /**
     * Le contenu remis à `EmailChannel`.
     *
     * Le même triplet que le fil — le courriel ne raconte pas une autre
     * histoire que l'écran. `extra` sert au gabarit à rappeler le numéro de
     * suivi quand il y en a un ; il n'y compose rien.
     */
    public function toEmail($notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'extra' => $this->extra,
        ];
    }

    /**
     * Le même texte que le fil, et les mêmes clés en `data` : l'app ouvre
     * l'écran concerné au toucher sans avoir à traduire un second vocabulaire.
     */
    public function toPush($notifiable): PushMessage
    {
        return new PushMessage($this->title, $this->body, array_merge(
            ['kind' => $this->kind],
            array_map(fn ($valeur) => is_scalar($valeur) || $valeur === null ? $valeur : (string) $valeur, $this->extra),
        ));
    }

    public function toDatabase($notifiable): array
    {
        return array_merge([
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
        ], $this->extra);
    }
}
