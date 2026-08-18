<?php

namespace App\Services\Payments;

use App\Models\Backend\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Passerelle FedaPay — MTN MoMo et Moov Money Bénin.
 *
 * Intégration **HTTP directe** via Guzzle, sans le SDK `fedapay/fedapay-php` :
 * le socle n'a pas d'abstraction de passerelle (bloc C — chaque gateway a son
 * propre contrôleur), ajouter une dépendance n'apporterait rien et coûterait une
 * mise à jour de plus. Les routes suivent la documentation officielle.
 *
 * Deux environnements, choisis par `config('fedapay.environment')` :
 *   sandbox → https://sandbox-api.fedapay.com/v1  (aucun argent réel)
 *   live    → https://api.fedapay.com/v1
 * Passer de l'un à l'autre ne demande que de changer le .env.
 *
 * ⚠️ Cette classe n'écrit **rien** en base et ne crédite aucun wallet : elle
 * parle à FedaPay, rien de plus. La décision de créditer appartient au webhook
 * (`.claude/rules/payments.md` : le webhook signé est la seule source de vérité).
 */
class FedaPayGateway
{
    public function __construct(private ?Client $http = null)
    {
    }

    /** Environnement effectif — toute valeur inconnue retombe sur `sandbox`. */
    public function environment(): string
    {
        $env = (string) config('fedapay.environment', 'sandbox');

        return $env === 'live' ? 'live' : 'sandbox';
    }

    public function isLive(): bool
    {
        return $this->environment() === 'live';
    }

    public function baseUrl(): string
    {
        return (string) config('fedapay.base_urls.' . $this->environment());
    }

    /**
     * Clés à utiliser pour une société donnée.
     *
     * Les clés « plateforme » du .env servent aux abonnements SaaS. Un locataire
     * peut brancher son propre compte FedaPay pour son encaissement marchand :
     * ses clés vivent alors dans la table `settings` (`company_id`/`key`/`value`),
     * comme les autres passerelles du socle.
     *
     * ⚠️ Lecture scopée explicitement par `company_id` : `globalSettings()` du
     * socle ne l'est pas (constat S6) et lirait les clés d'une autre société.
     */
    public function credentialsFor(?int $companyId = null): array
    {
        $secret = null;

        if ($companyId !== null) {
            $secret = Setting::where('company_id', $companyId)
                ->where('key', 'fedapay_secret_key')
                ->value('value');
        }

        return [
            'secret_key' => $secret ?: config('fedapay.secret_key'),
        ];
    }

    /** Les clés sont-elles renseignées ? Permet d'échouer avec un message clair. */
    public function isConfigured(?int $companyId = null): bool
    {
        return !blank($this->credentialsFor($companyId)['secret_key']);
    }

    /**
     * Crée une transaction puis génère le lien de paiement hébergé.
     *
     * @param array{amount:int|float, description?:string, callback_url:string,
     *              reference:string, purpose?:string, company_id?:int,
     *              customer?:array} $payload
     * @return array{success:bool, payment_url?:string, token?:string,
     *               provider_transaction_id?:string, message?:string}
     */
    public function initialize(array $payload): array
    {
        $companyId = $payload['company_id'] ?? null;

        if (!$this->isConfigured($companyId)) {
            return [
                'success' => false,
                'message' => __('fedapay.not_configured'),
            ];
        }

        // FCFA : entier. Un centime résiduel serait refusé par FedaPay.
        $amount = (int) round((float) $payload['amount']);
        if ($amount <= 0) {
            return ['success' => false, 'message' => __('fedapay.invalid_amount')];
        }

        try {
            $transaction = $this->post('/transactions', [
                'description' => $payload['description'] ?? 'BeninLink',
                'amount' => $amount,
                'currency' => ['iso' => config('fedapay.currency')],
                'callback_url' => $payload['callback_url'],
                'customer' => $this->customerPayload($payload['customer'] ?? []),
                'custom_metadata' => [
                    'reference' => $payload['reference'],
                    'company_id' => $companyId,
                    'purpose' => $payload['purpose'] ?? 'wallet_recharge',
                ],
            ], $companyId);

            $id = $transaction['v1/transaction']['id'] ?? $transaction['id'] ?? null;
            if (!$id) {
                return ['success' => false, 'message' => __('fedapay.error')];
            }

            // Le lien de paiement se génère dans un second appel.
            $token = $this->post("/transactions/{$id}/token", [], $companyId);

            return [
                'success' => true,
                'payment_url' => $token['url'] ?? null,
                'token' => $token['token'] ?? null,
                'provider_transaction_id' => (string) $id,
            ];
        } catch (\Throwable $e) {
            // Le détail part au journal, jamais à l'utilisateur : il pourrait
            // contenir des éléments de la requête.
            Log::error('FedaPay : initialisation impossible', [
                'reference' => $payload['reference'] ?? null,
                'environment' => $this->environment(),
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => __('fedapay.error')];
        }
    }

    /** Relit une transaction chez FedaPay — utilisé pour un rapprochement manuel. */
    public function retrieve(string $providerTransactionId, ?int $companyId = null): ?array
    {
        try {
            $response = $this->get("/transactions/{$providerTransactionId}", $companyId);

            return $response['v1/transaction'] ?? $response;
        } catch (\Throwable $e) {
            Log::warning('FedaPay : lecture de transaction impossible', [
                'id' => $providerTransactionId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Vérifie l'en-tête `X-FEDAPAY-SIGNATURE` d'un webhook.
     *
     * Format `t=<horodatage>,s=<signature>` — même schéma que Stripe, dont
     * FedaPay reprend la convention. La signature est un HMAC-SHA256 de
     * « horodatage.charge utile » avec le secret du webhook.
     *
     * Deux protections, toutes deux nécessaires :
     *   - comparaison en **temps constant** (`hash_equals`), sans quoi le temps
     *     de réponse laisse deviner la signature octet par octet ;
     *   - **tolérance d'horodatage**, sans quoi un webhook intercepté peut être
     *     rejoué indéfiniment.
     */
    public function verifySignature(string $payload, ?string $signatureHeader): bool
    {
        $secret = (string) config('fedapay.webhook_secret');
        if (blank($secret) || blank($signatureHeader)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $piece = explode('=', trim($part), 2);
            if (count($piece) !== 2) {
                continue;
            }
            [$key, $value] = $piece;
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 's') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        $tolerance = (int) config('fedapay.webhook_tolerance', 300);
        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            Log::warning('FedaPay : webhook hors tolérance d\'horodatage');

            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        Log::warning('FedaPay : signature de webhook invalide');

        return false;
    }

    // — Transport ------------------------------------------------------------

    private function client(?int $companyId): Client
    {
        if ($this->http !== null) {
            return $this->http;
        }

        return new Client([
            'base_uri' => $this->baseUrl(),
            'timeout' => (int) config('fedapay.timeout', 30),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->credentialsFor($companyId)['secret_key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    private function post(string $path, array $body, ?int $companyId): array
    {
        return $this->send('POST', $path, $companyId, $body);
    }

    private function get(string $path, ?int $companyId): array
    {
        return $this->send('GET', $path, $companyId);
    }

    private function send(string $method, string $path, ?int $companyId, ?array $body = null): array
    {
        $options = $body === null ? [] : ['json' => $body];

        try {
            $response = $this->client($companyId)->request($method, $this->baseUrl() . $path, $options);
        } catch (RequestException $e) {
            $detail = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            throw new \RuntimeException('FedaPay ' . $method . ' ' . $path . ' : ' . $detail, 0, $e);
        }

        return json_decode((string) $response->getBody(), true) ?: [];
    }

    private function customerPayload(array $customer): array
    {
        return [
            'firstname' => $customer['firstname'] ?? '',
            'lastname' => $customer['lastname'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone_number' => [
                'number' => $this->normalizePhone($customer['phone'] ?? ''),
                'country' => config('fedapay.country'),
            ],
        ];
    }

    /**
     * Normalise un numéro béninois au format attendu : « 22961000000 ».
     * Accepte « 0161000000 », « +229 61 00 00 00 », « 61000000 ».
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '229')) {
            return $digits;
        }

        return '229' . ltrim($digits, '0');
    }
}
