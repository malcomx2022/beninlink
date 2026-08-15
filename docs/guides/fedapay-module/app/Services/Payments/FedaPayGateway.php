<?php

namespace App\Services\Payments;

use FedaPay\FedaPay;
use FedaPay\Transaction;
use FedaPay\Webhook;
use Illuminate\Support\Facades\Log;

/**
 * Passerelle FedaPay (MTN MoMo / Moov Money Bénin) pour BeninLink.
 *
 * PRINCIPE : We Courier possède déjà une abstraction de paiement (chaque gateway
 * implémente un contrat commun). On AJOUTE FedaPay comme implémentation.
 * >>> Étape 0 : relever l'interface réelle (via la classe Paystack) et renommer
 *     initialize()/verifyWebhook() pour coller à ce contrat. Le corps reste valable.
 */
class FedaPayGateway // implements \App\Contracts\PaymentGatewayContract
{
    protected function boot(?array $tenantCredentials = null): void
    {
        $secret = $tenantCredentials['secret_key'] ?? config('fedapay.secret_key');
        FedaPay::setApiKey($secret);
        FedaPay::setEnvironment(config('fedapay.environment'));
    }

    /**
     * Initialise une transaction et renvoie l'URL de paiement hébergée.
     * Web = redirection ; app React Native = WebView sur payment_url.
     */
    public function initialize(array $payload, ?array $tenantCredentials = null): array
    {
        try {
            $this->boot($tenantCredentials);

            // FCFA = entiers. On sécurise contre tout centime résiduel.
            $amount = (int) round($payload['amount']);

            $transaction = Transaction::create([
                'description'  => $payload['description'] ?? 'Paiement BeninLink',
                'amount'       => $amount,
                'currency'     => ['iso' => config('fedapay.currency')],
                'callback_url' => $payload['callback_url'],
                'customer'     => [
                    'firstname'    => $payload['customer']['firstname'] ?? '',
                    'lastname'     => $payload['customer']['lastname'] ?? '',
                    'email'        => $payload['customer']['email'] ?? '',
                    'phone_number' => [
                        'number'  => $this->normalizePhone($payload['customer']['phone'] ?? ''),
                        'country' => config('fedapay.country'),
                    ],
                ],
                'custom_metadata' => [
                    'reference' => $payload['reference'],
                    'tenant_id' => $payload['tenant_id'] ?? null,
                    'purpose'   => $payload['purpose'] ?? 'wallet_recharge',
                ],
            ]);

            $token = $transaction->generateToken();

            return [
                'success'            => true,
                'payment_url'        => $token->url,
                'token'              => $token->token,
                'provider_reference' => $transaction->id,
            ];
        } catch (\Throwable $e) {
            Log::error('FedaPay initialize failed', [
                'reference' => $payload['reference'] ?? null,
                'message'   => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Vérifie et décode un événement webhook FedaPay (null si signature invalide). */
    public function verifyWebhook(string $payload, string $signatureHeader): ?object
    {
        try {
            return Webhook::constructEvent($payload, $signatureHeader, config('fedapay.webhook_secret'));
        } catch (\UnexpectedValueException $e) {
            Log::warning('FedaPay webhook : payload invalide'); return null;
        } catch (\FedaPay\Error\SignatureVerification $e) {
            Log::warning('FedaPay webhook : signature invalide'); return null;
        }
    }

    /** Normalise un numéro béninois : "0161000000"/"+22961000000" -> "22961000000". */
    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '229')) return $digits;
        return '229' . ltrim($digits, '0');
    }
}
