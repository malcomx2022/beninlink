<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payments\FedaPayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Point d'entrée HTTP pour FedaPay.
 *  1. initiate() : démarre un paiement (recharge wallet OU abonnement SaaS).
 *  2. callback() : retour navigateur/WebView (UX seulement).
 *  3. webhook()  : SOURCE DE VÉRITÉ. C'est ici, et seulement ici, qu'on crédite.
 *
 * >>> Étape 0 : brancher creditMerchantWallet()/activateSubscription() sur les
 *     services EXISTANTS de We Courier (les appeler, ne pas les réécrire).
 */
class FedaPayController extends Controller
{
    public function __construct(protected FedaPayGateway $gateway) {}

    public function initiate(Request $request)
    {
        $data = $request->validate([
            'amount'  => ['required', 'integer', 'min:1'],
            'purpose' => ['required', 'in:wallet_recharge,saas_subscription'],
        ]);

        $user = $request->user();
        $reference = 'BLK-' . strtoupper(Str::random(10));

        DB::table('fedapay_transactions')->insert([
            'reference' => $reference, 'tenant_id' => $this->currentTenantId(),
            'user_id' => $user->id, 'amount' => $data['amount'],
            'purpose' => $data['purpose'], 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $this->gateway->initialize([
            'amount'       => $data['amount'],
            'description'  => $data['purpose'] === 'saas_subscription' ? 'Abonnement BeninLink' : 'Recharge portefeuille',
            'callback_url' => route('fedapay.callback', ['reference' => $reference]),
            'reference'    => $reference,
            'tenant_id'    => $this->currentTenantId(),
            'purpose'      => $data['purpose'],
            'customer'     => [
                'firstname' => $user->name ?? $user->first_name ?? '',
                'lastname'  => $user->last_name ?? '',
                'email'     => $user->email,
                'phone'     => $user->phone ?? '',
            ],
        ], $this->resolveTenantCredentials($data['purpose']));

        if (! $result['success']) {
            return response()->json(['message' => 'Initialisation du paiement échouée'], 422);
        }

        DB::table('fedapay_transactions')->where('reference', $reference)
            ->update(['provider_reference' => $result['provider_reference'], 'updated_at' => now()]);

        return response()->json(['payment_url' => $result['payment_url'], 'reference' => $reference]);
    }

    public function callback(Request $request)
    {
        $tx = DB::table('fedapay_transactions')->where('reference', $request->query('reference'))->first();
        return view('payment.fedapay-result', ['paid' => $tx && $tx->status === 'paid', 'transaction' => $tx]);
    }

    /** Webhook — seule source de vérité. Route à EXCLURE du CSRF. */
    public function webhook(Request $request)
    {
        $event = $this->gateway->verifyWebhook($request->getContent(), $request->header('X-FEDAPAY-SIGNATURE', ''));
        if (! $event) return response()->json(['message' => 'Signature invalide'], 400);
        if ($event->name !== 'transaction.approved') return response()->json(['message' => 'ignored'], 200);

        $reference = $event->entity->custom_metadata->reference ?? null;
        if (! $reference) return response()->json(['message' => 'reference manquante'], 200);

        DB::transaction(function () use ($reference, $event) {
            $tx = DB::table('fedapay_transactions')->where('reference', $reference)->lockForUpdate()->first();
            if (! $tx || $tx->status === 'paid') return; // inconnue ou déjà traitée
            DB::table('fedapay_transactions')->where('reference', $reference)
                ->update(['status' => 'paid', 'paid_at' => now(), 'updated_at' => now()]);
            if ($tx->purpose === 'wallet_recharge') $this->creditMerchantWallet($tx);
            else $this->activateSubscription($tx);
        });

        Log::info('FedaPay paiement confirmé', ['reference' => $reference]);
        return response()->json(['message' => 'ok'], 200);
    }

    /* --- Points de branchement We Courier — à mapper à l'Étape 0 --- */
    protected function creditMerchantWallet(object $tx): void {
        // app(\App\Services\WalletService::class)->credit($tx->user_id, $tx->amount, "FedaPay {$tx->reference}");
    }
    protected function activateSubscription(object $tx): void {
        // app(\App\Services\SubscriptionService::class)->activateForTenant($tx->tenant_id, $tx->amount, $tx->reference);
    }
    protected function currentTenantId(): ?int { /* return optional(tenant())->id; */ return null; }
    protected function resolveTenantCredentials(string $purpose): ?array { return null; }
}
