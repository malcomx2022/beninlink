<?php

namespace App\Http\Controllers\Payment;

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Controllers\Controller;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use App\Repositories\Wallet\WalletInterface;
use App\Services\Payments\FedaPayGateway;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Recharge de wallet marchand par Mobile Money (FedaPay).
 *
 * Principe non négociable (`.claude/rules/payments.md`) : **le webhook signé est
 * la seule source de vérité**. Le retour du navigateur ou de la WebView ne
 * crédite jamais — un utilisateur peut l'ouvrir à la main.
 *
 * Parcours :
 *   1. `initiate`  → crée une ligne `wallets` en PENDING + une transaction
 *                    FedaPay, renvoie `payment_url` ;
 *   2. le client   → paie sur la page FedaPay (choix MTN/Moov, validation USSD) ;
 *   3. `webhook`   → vérifie la signature, puis crédite **une seule fois**.
 */
class FedaPayController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(
        private FedaPayGateway $gateway,
        private WalletInterface $walletRepo,
    ) {
    }

    /**
     * Initie une recharge. Appelé par l'app mobile (Bearer Sanctum) ou le
     * panneau marchand. Ne crédite rien.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            // Entier : le franc CFA n'a pas de subdivision.
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $merchant = Auth::user()?->merchant;
        if (blank($merchant)) {
            return $this->responseWithError(__('fedapay.merchant_only'), [], 403);
        }

        $amount = (int) $request->amount;
        $companyId = $merchant->company_id;

        if (!$this->gateway->isConfigured($companyId)) {
            return $this->responseWithError(__('fedapay.not_configured'), [], 503);
        }

        $reference = 'BL-' . Str::upper(Str::random(10));

        // La ligne `wallets` naît en PENDING : elle matérialise la demande sans
        // toucher au solde. Seul le webhook la fera passer en APPROVED.
        $wallet = new Wallet();
        $wallet->company_id = $companyId;
        $wallet->user_id = Auth::id();
        $wallet->merchant_id = $merchant->id;
        $wallet->source = 'FedaPay';
        $wallet->transaction_id = $reference;
        $wallet->amount = $amount;
        $wallet->type = WalletType::INCOME;
        $wallet->payment_method = WalletPaymentMethod::OFFLINE;
        $wallet->status = WalletStatus::PENDING;
        $wallet->save();

        $record = FedaPayTransaction::create([
            'company_id' => $companyId,
            'merchant_id' => $merchant->id,
            'reference' => $reference,
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'amount' => $amount,
            'status' => FedaPayTransaction::STATUS_PENDING,
            'wallet_id' => $wallet->id,
            'customer_phone' => Auth::user()->mobile,
        ]);

        $result = $this->gateway->initialize([
            'amount' => $amount,
            'description' => __('fedapay.wallet_description', ['name' => $merchant->business_name]),
            'callback_url' => route('fedapay.callback'),
            'reference' => $reference,
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'company_id' => $companyId,
            'customer' => [
                'firstname' => Auth::user()->name,
                'email' => Auth::user()->email,
                'phone' => Auth::user()->mobile,
            ],
        ]);

        if (!($result['success'] ?? false)) {
            $record->update(['status' => FedaPayTransaction::STATUS_DECLINED]);
            $wallet->status = WalletStatus::REJECTED;
            $wallet->save();

            return $this->responseWithError($result['message'] ?? __('fedapay.error'), [], 502);
        }

        $record->update([
            'provider_transaction_id' => $result['provider_transaction_id'],
            'payment_url' => $result['payment_url'],
        ]);

        return $this->responseWithSuccess(__('fedapay.initiated'), [
            'reference' => $reference,
            // L'app ouvre cette URL en WebView ; elle ne voit jamais les clés.
            'payment_url' => $result['payment_url'],
        ], 200);
    }

    /**
     * Webhook FedaPay — **seul endroit qui crédite**.
     *
     * Renvoie 200 même sur un événement ignoré : un code d'erreur ferait
     * réessayer FedaPay indéfiniment pour un événement qui ne nous concerne pas.
     * Seule une signature invalide donne 400.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-FEDAPAY-SIGNATURE');

        if (!$this->gateway->verifySignature($payload, $signature)) {
            // Ni détail ni indice : la réponse ne doit pas aider à forger une signature.
            return response()->json(['message' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true) ?: [];
        $name = $event['name'] ?? $event['event'] ?? null;
        $entity = $event['entity'] ?? $event['data']['object'] ?? [];
        $providerId = (string) ($entity['id'] ?? '');

        if ($providerId === '') {
            return response()->json(['message' => 'ignored'], 200);
        }

        // ⚠️ Recherche SANS `companywise()` : le webhook n'a pas de session, et
        // `settings()` retomberait sur la société 1 (bloc A). Le locataire vient
        // de la transaction elle-même.
        $record = FedaPayTransaction::where('provider_transaction_id', $providerId)->first();
        if (blank($record)) {
            Log::warning('FedaPay : webhook pour une transaction inconnue', ['id' => $providerId]);

            return response()->json(['message' => 'unknown transaction'], 200);
        }

        $record->update(['last_event' => $event]);

        return match ($name) {
            'transaction.approved' => $this->approve($record, $entity),
            'transaction.declined' => $this->close($record, FedaPayTransaction::STATUS_DECLINED),
            'transaction.canceled' => $this->close($record, FedaPayTransaction::STATUS_CANCELED),
            default => response()->json(['message' => 'ignored'], 200),
        };
    }

    /**
     * Crédite le wallet, **une seule fois**.
     *
     * L'idempotence repose sur un verrou de ligne : `lockForUpdate()` sérialise
     * deux webhooks simultanés, et le statut déjà `approved` fait ressortir le
     * second sans créditer. La cartographie (bloc C) a établi que
     * `WalletRepository::approved()` n'est ni transactionnelle ni idempotente :
     * le garde-fou doit donc être ici.
     */
    private function approve(FedaPayTransaction $record, array $entity)
    {
        $credited = DB::transaction(function () use ($record) {
            $fresh = FedaPayTransaction::whereKey($record->id)->lockForUpdate()->first();

            if ($fresh->isApproved()) {
                return false; // déjà traité : rejeu
            }

            $fresh->update([
                'status' => FedaPayTransaction::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            return true;
        });

        if (!$credited) {
            return response()->json(['message' => 'already processed'], 200);
        }

        // Contrôle du montant : un écart signale un rapprochement à faire à la
        // main. On crédite le montant ATTENDU, jamais celui annoncé par l'événement.
        $paid = (int) round((float) ($entity['amount'] ?? 0));
        if ($paid > 0 && $paid !== (int) $record->amount) {
            Log::warning('FedaPay : montant payé différent du montant attendu', [
                'reference' => $record->reference,
                'attendu' => $record->amount,
                'payé' => $paid,
            ]);
        }

        // Crédit par le service existant du socle : ne pas dupliquer l'incrément
        // du solde (`merchants.wallet_balance`) ni l'envoi du SMS.
        if ($record->wallet_id && !$this->walletRepo->approved($record->wallet_id)) {
            Log::error('FedaPay : crédit du wallet en échec', [
                'reference' => $record->reference,
                'wallet_id' => $record->wallet_id,
            ]);
        }

        return response()->json(['message' => 'approved'], 200);
    }

    private function close(FedaPayTransaction $record, string $status)
    {
        if ($record->isApproved()) {
            // Un refus après approbation ne défait pas un crédit : cela relève
            // d'un remboursement, décidé à la main.
            return response()->json(['message' => 'already approved'], 200);
        }

        $record->update(['status' => $status]);

        if ($record->wallet_id) {
            $wallet = Wallet::find($record->wallet_id);
            if ($wallet && $wallet->status == WalletStatus::PENDING) {
                $wallet->status = WalletStatus::REJECTED;
                $wallet->save();
            }
        }

        return response()->json(['message' => $status], 200);
    }

    /**
     * Retour du navigateur / de la WebView après paiement.
     * **N'accorde rien** : il informe, le webhook décide.
     */
    public function callback(Request $request)
    {
        return view('backend.payment.fedapay_callback', [
            'reference' => $request->query('reference'),
        ]);
    }

    /** État d'une recharge, interrogé par l'app après la fermeture de la WebView. */
    public function status(Request $request, string $reference)
    {
        $merchant = Auth::user()?->merchant;
        if (blank($merchant)) {
            return $this->responseWithError(__('fedapay.merchant_only'), [], 403);
        }

        $record = FedaPayTransaction::where('reference', $reference)
            ->where('merchant_id', $merchant->id)
            ->first();

        if (blank($record)) {
            return $this->responseWithError(__('fedapay.unknown_reference'), [], 404);
        }

        return $this->responseWithSuccess(__('fedapay.status'), [
            'reference' => $record->reference,
            'status' => $record->status,
            'amount' => (int) $record->amount,
            // Le solde fait foi : c'est lui que l'app doit afficher.
            'wallet_balance' => (int) round((float) Merchant::find($merchant->id)->wallet_balance),
        ], 200);
    }
}
