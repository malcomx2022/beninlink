<?php

namespace App\Http\Services;

use App\Enums\UserType;
use App\Models\User;
use App\Services\Push\PushGateway;
use App\Services\Push\PushMessage;
use Illuminate\Support\Facades\Log;


class PushNotificationService
{
    /**
     * S11 — Nom du topic FCM d'un destinataire.
     *
     * Le socle le dérivait de l'ADRESSE E-MAIL (« @ » et « . » remplacés) :
     * connaître l'adresse d'un marchand suffisait donc à s'abonner à ses
     * notifications via `fcmSubscribe`. Le topic est désormais un HMAC de
     * l'identifiant de l'utilisateur avec la clé applicative : il n'est pas
     * devinable depuis une information publique.
     *
     * L'argument reste une adresse e-mail pour ne pas toucher aux ~14 appels
     * existants ; la conversion se fait ici, des deux côtés (abonnement et
     * envoi), ce qui garantit qu'ils coïncident.
     *
     * ⚠️ Ne concerne plus que les topics FCM des apps Flutter dépréciées
     * (`fcmSubscribe`) : depuis D11, le push des apps React Native s'adresse
     * par appareil, sans topic. Changer `APP_KEY` change tous les topics.
     */
    public function topicFor($identifier): string
    {
        $base = notificationSettings()->fcm_topic;

        if (blank($identifier)) {
            return $base;
        }

        $userId = null;
        if ($identifier instanceof User) {
            $userId = $identifier->id;
        } elseif (is_numeric($identifier)) {
            $userId = (int) $identifier;
        } else {
            $userId = User::where('email', $identifier)->value('id');
        }

        if (blank($userId)) {
            return $base;
        }

        return $base . '_' . substr(hash_hmac('sha256', 'user:' . $userId, config('app.key')), 0, 32);
    }

    /**
     * Message rédigé par l'administration (table `push_notifications`).
     *
     * Le transport a changé (D11) : l'API FCM « legacy » que ce service
     * appelait — `fcm.googleapis.com/fcm/send`, en-tête `key=…` — est arrêtée
     * par Google depuis juin 2024. Chaque appel était donc un aller-retour
     * HTTPS bloquant pour rien, et l'échec cURL tuait la requête en cours
     * (`die()`). On passe désormais par les appareils abonnés.
     *
     * Un MARCHAND n'est pas servi ici : son message lui arrive par le fil
     * (`MerchantFeed::adminMessage`), qui écrit l'entrée en base ET pousse.
     * Le servir des deux côtés lui vaudrait deux notifications pour un
     * message. Ce chemin ne sert donc plus que les autres comptes — livreur,
     * ramasseur, personnel du hub.
     */
    public function sendPushNotification($data, $topicName, $type)
    {
        return $this->pousserAuTiers($topicName, $data->company_id ?? null, fn () => new PushMessage(
            (string) ($data->title ?? ''),
            (string) ($data->description ?? ''),
            ['kind' => 'message', 'message_id' => $data->id ?? null],
        ));
    }

    /**
     * Changement de statut d'un colis, poussé au tiers concerné.
     *
     * Les quinze appels de `ParcelRepository` n'ont pas bougé : c'est le
     * transport dessous qui change (D11). Comme ci-dessus, le marchand est
     * servi par son fil et non ici.
     *
     * Le texte est recomposé en français à partir du colis. Le `$msg` du socle
     * est une phrase anglaise bâtie pour le SMS (« Dear …, Please pickup
     * parcel with ID … ») : la pousser telle quelle à un livreur béninois
     * n'aurait servi personne. Il reste le texte du SMS, que ce service
     * n'écrit pas.
     */
    public function sendStatusPushNotification($parcel, $topicName, $msg, $type)
    {
        return $this->pousserAuTiers($topicName, $parcel->company_id ?? null, function () use ($parcel) {
            $statut = trans('parcelStatus.' . $parcel->status);

            return new PushMessage(
                __('push.parcel_title', ['tracking' => $parcel->tracking_id]),
                trim($statut . ' — ' . __('push.parcel_body', [
                    'customer' => $parcel->customer_name,
                    'address' => $parcel->customer_address,
                ])),
                [
                    'kind' => 'parcel_status',
                    'parcel_id' => $parcel->id,
                    'tracking_id' => $parcel->tracking_id,
                    'status' => (int) $parcel->status,
                ],
            );
        });
    }

    /**
     * Livre à un compte désigné par son adresse, sauf si c'est un marchand.
     *
     * L'adresse reste l'identifiant d'entrée pour ne pas toucher aux appels
     * existants. Le périmètre vient de **l'objet notifié** (le colis, le
     * message), jamais de `settings()` : les adresses ne sont pas uniques en
     * base, et `settings()` retombe sur la société 1 hors requête locataire —
     * une commande console aurait poussé au mauvais compte, ou à personne.
     * Sans société connue, on n'accepte qu'une correspondance unique.
     *
     * Rien ne remonte : un push est un confort, jamais une condition de
     * l'écriture métier qui l'a déclenché.
     *
     * @param callable():PushMessage $construire
     */
    private function pousserAuTiers($identifiant, ?int $societe, callable $construire): int
    {
        try {
            if (blank($identifiant)) {
                return 0;
            }

            if ($identifiant instanceof User) {
                $user = $identifiant;
            } else {
                $candidats = User::where('email', $identifiant)
                    ->when($societe, fn ($q) => $q->where('company_id', $societe))
                    ->limit(2)
                    ->get();

                $user = $candidats->count() === 1 ? $candidats->first() : null;
            }

            if (blank($user) || (int) $user->user_type === UserType::MERCHANT) {
                return 0; // inconnu, ambigu, ou marchand déjà servi par son fil
            }

            return app(PushGateway::class)->toUser($user, $construire());
        } catch (\Throwable $e) {
            Log::warning('Push statut non émis', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    public function fcmSubscribe($request)
    {

        $deviceToken = $request->device_token;
        // S11 — le topic demande par l'appelant est IGNORE : il permettait de
        // s'abonner aux notifications d'autrui en connaissant son adresse.
        // Le topic est celui de l'utilisateur AUTHENTIFIE, personne d'autre.
        $topic = $this->topicFor(\Illuminate\Support\Facades\Auth::user());


        $headers = array(
            'Authorization: key=' .notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );
        $this->fcmGlobalSubscribe($request);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            return response()->json([
                'status' => 200,
                'message' => 'Subscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }

    
    
    public function fcmGlobalSubscribe($request)
    {
        $deviceToken = $request->device_token;
        $topic       = notificationSettings()->fcm_topic;

        $headers = array(
            'Authorization: key=' .notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            return response()->json([
                'status' => 200,
                'message' => 'Global Subscription',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }

 


    public function fcmUnsubscribe($request)
    {
        $request->validate([
            'device_token' => 'required',
            'topic' => 'nullable',
        ]);

        $deviceToken = $request->token;

        $headers = array(
            'Authorization: key=' .notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/v1/web/iid/$deviceToken");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            return response()->json([
                'status' => 200,
                'message' => 'Unsubscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    } 
    
    /**
     * Push **navigateur** du back-office — retiré (S22, décision D12).
     *
     * La méthode garde sa signature et ses trois appels dans
     * `PushNotificationRepository` : c'est le canal qui disparaît, pas la
     * façon dont le socle écrit ses messages. Elle n'ouvre plus aucune
     * connexion sortante.
     *
     * Ce qu'elle faisait : un POST à l'API FCM « legacy » (arrêtée par Google
     * en juin 2024) avec les jetons `users.web_token`, émis par le projet
     * Firebase **de l'éditeur**. Rien n'arrivait, et la page de connexion
     * réclamait malgré tout la permission de notifier.
     *
     * Pour rouvrir, il faut un transport vivant ET un projet maîtrisé par le
     * transporteur : FCM HTTP v1, ou du Web Push standard (VAPID) — voir D12.
     * L'app marchand et l'app livreur, elles, sont servies depuis D11.
     */
    public function sendWebNotification($data, $notification, $type, $FcmToken)
    {
        return true;
    }
}
