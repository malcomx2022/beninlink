<?php

namespace App\Services\Push;

use App\Models\Backend\DeviceToken;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Livraison par le service de push d'Expo.
 *
 * Pourquoi Expo et pas FCM en direct : les deux apps du projet sont des apps
 * Expo, et Expo relaie vers FCM (Android) et APNs (iOS) avec ses propres
 * identifiants. Le backend ne porte donc **aucun secret de push** — en
 * multi-tenant, la variante FCM aurait demandé un compte de service par
 * société, à saisir, à stocker et à faire tourner. Voir décision D11.
 *
 * Ce que cette classe ne fait pas : décider qu'il faut notifier (c'est
 * `MerchantFeed` et les observers), et lever une exception (un push est un
 * confort, jamais une condition de l'écriture métier).
 *
 * Deux effets de bord assumés, et c'est tout :
 *   - un appareil que le service déclare inconnu (`DeviceNotRegistered`,
 *     app désinstallée) est **supprimé** — sans quoi la table grossit
 *     indéfiniment d'adresses mortes ;
 *   - un appareil servi voit son `last_used_at` daté.
 */
class ExpoPushGateway implements PushGateway
{
    /** Forme documentée d'un jeton Expo. Un autre jeton n'est pas envoyé. */
    private const TOKEN_PATTERN = '/^Expo(nent)?PushToken\[[^\]]+\]$/';

    public function __construct(private ?Client $http = null)
    {
    }

    public function toUser(User $user, PushMessage $message): int
    {
        $appareils = $user->deviceTokens()->get()
            ->filter(fn (DeviceToken $appareil) => $this->accepte($appareil->token));

        if ($appareils->isEmpty()) {
            return 0;
        }

        $servis = 0;
        foreach ($appareils->chunk((int) config('push.expo.chunk', 100)) as $lot) {
            $servis += $this->livrer($lot->values(), $message);
        }

        return $servis;
    }

    public function accepte(?string $token): bool
    {
        return filled($token) && (bool) preg_match(self::TOKEN_PATTERN, $token);
    }

    /** @param \Illuminate\Support\Collection<int,DeviceToken> $lot */
    private function livrer($lot, PushMessage $message): int
    {
        $charge = $lot->map(fn (DeviceToken $appareil) => array_filter([
            'to' => $appareil->token,
            'title' => $message->title,
            'body' => $message->body,
            'data' => $message->data,
            'sound' => 'default',
            'priority' => 'high',
            // Canal Android créé par les apps ; sans lui, Android range la
            // notification dans le canal par défaut, muet sur certains OEM.
            'channelId' => 'default',
        ]))->all();

        try {
            $reponse = $this->client()->post((string) config('push.expo.endpoint'), [
                'headers' => array_filter([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => ($jeton = config('push.expo.access_token')) ? 'Bearer ' . $jeton : null,
                ]),
                'json' => $charge,
                'timeout' => (float) config('push.expo.timeout', 5),
            ]);

            $tickets = json_decode((string) $reponse->getBody(), true)['data'] ?? [];
        } catch (GuzzleException|\Throwable $e) {
            // Service injoignable : les appareils restent, le prochain
            // événement retentera. Rien à défaire.
            Log::warning('Push non livré', ['message' => $e->getMessage(), 'appareils' => $lot->count()]);

            return 0;
        }

        return $this->depouiller($lot, is_array($tickets) ? $tickets : []);
    }

    /**
     * Les tickets reviennent dans l'ordre des messages envoyés : le ticket i
     * concerne l'appareil i.
     *
     * @param \Illuminate\Support\Collection<int,DeviceToken> $lot
     */
    private function depouiller($lot, array $tickets): int
    {
        $servis = 0;
        $morts = [];

        foreach ($lot as $index => $appareil) {
            $ticket = $tickets[$index] ?? null;
            $statut = is_array($ticket) ? ($ticket['status'] ?? null) : null;

            if ($statut === 'ok') {
                $servis++;
                continue;
            }

            if (is_array($ticket) && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                $morts[] = $appareil->id;
                continue;
            }

            Log::warning('Push refusé par le service', [
                'device_token_id' => $appareil->id,
                'raison' => is_array($ticket) ? ($ticket['message'] ?? 'inconnue') : 'aucun ticket',
            ]);
        }

        if ($morts !== []) {
            DeviceToken::whereIn('id', $morts)->delete();
        }

        if ($servis > 0) {
            DeviceToken::whereIn('id', $lot->pluck('id')->diff($morts)->all())->update(['last_used_at' => now()]);
        }

        return $servis;
    }

    private function client(): Client
    {
        return $this->http ??= new Client();
    }
}
