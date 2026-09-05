<?php

namespace App\Http\Controllers\Payment;

use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Controllers\Controller;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\Superadmin\Plan;
use App\Models\Backend\Wallet;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Wallet\WalletInterface;
use App\Services\Payments\FedaPayGateway;
use App\Traits\ApiReturnFormatTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paiements Mobile Money (FedaPay) : recharge de wallet marchand et
 * abonnement SaaS d'une société.
 *
 * Principe non négociable (`.claude/rules/payments.md`) : **le webhook signé est
 * la seule source de vérité**. Le retour du navigateur ou de la WebView ne
 * crédite ni n'active jamais — un utilisateur peut l'ouvrir à la main.
 *
 * Parcours (recharge) :
 *   1. `initiate`  → crée une ligne `wallets` en PENDING + une transaction
 *                    FedaPay, renvoie `payment_url` ;
 *   2. le client   → paie sur la page FedaPay (choix MTN/Moov, validation USSD) ;
 *   3. `webhook`   → vérifie la signature, puis crédite **une seule fois**.
 *
 * Parcours (abonnement) : identique, `subscribe` remplaçant `initiate`, et le
 * webhook activant le plan par `CompanyRepository::switchPlan()` — l'unique
 * point d'activation du socle (bloc E), que le retour Stripe appelle aussi.
 * Les clés utilisées sont celles de la **plateforme** (`.env`), jamais celles
 * d'un locataire (`.claude/rules/multitenant.md`).
 */
class FedaPayController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(
        private FedaPayGateway $gateway,
        private WalletInterface $walletRepo,
        private CompanyInterface $companyRepo,
    ) {
    }

    /**
     * Paiement d'un abonnement SaaS par Mobile Money. Appelé depuis la page
     * des plans du panneau (session web). N'active rien : redirige vers la
     * page FedaPay, l'activation viendra du webhook.
     */
    public function subscribe(Request $request)
    {
        $request->validate(['plan_id' => ['required', 'integer']]);

        $plan = Plan::find($request->plan_id);
        if (blank($plan)) {
            Toastr::error(__('fedapay.plan_not_found'), __('message.error'));

            return redirect()->route('subscription.index');
        }

        // Prix en FCFA entier ; un plan gratuit n'a rien à payer ici.
        $amount = (int) round((float) $plan->price);
        if ($amount <= 0) {
            Toastr::error(__('fedapay.free_plan'), __('message.error'));

            return redirect()->route('subscription.index');
        }

        // Clés plateforme : `company_id` volontairement absent.
        if (!$this->gateway->isEnabled()) {
            Toastr::error(__('fedapay.not_configured'), __('message.error'));

            return redirect()->route('subscription.index');
        }

        $user = Auth::user();
        $reference = 'BL-SUB-' . Str::upper(Str::random(10));

        $record = FedaPayTransaction::create([
            'company_id' => $user->company_id,
            'reference' => $reference,
            'purpose' => FedaPayTransaction::PURPOSE_SUBSCRIPTION,
            'amount' => $amount,
            'status' => FedaPayTransaction::STATUS_PENDING,
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'customer_phone' => $user->mobile,
        ]);

        $result = $this->gateway->initialize([
            'amount' => $amount,
            'description' => __('fedapay.subscription_description', ['plan' => $plan->name]),
            // La référence dans l'URL de retour permet au rappel de retrouver
            // l'opération et de renvoyer vers la page des plans.
            'callback_url' => route('fedapay.callback', ['reference' => $reference]),
            'reference' => $reference,
            'purpose' => FedaPayTransaction::PURPOSE_SUBSCRIPTION,
            'company_id' => null,
            'customer' => [
                'firstname' => $user->name,
                'email' => $user->email,
                'phone' => $user->mobile,
            ],
        ]);

        if (!($result['success'] ?? false)) {
            $record->update(['status' => FedaPayTransaction::STATUS_DECLINED]);
            Toastr::error($result['message'] ?? __('fedapay.error'), __('message.error'));

            return redirect()->route('subscription.index');
        }

        $record->update([
            'provider_transaction_id' => $result['provider_transaction_id'],
            'payment_url' => $result['payment_url'],
        ]);

        return redirect()->away($result['payment_url']);
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

        if (blank(Auth::user()?->merchant)) {
            return $this->responseWithError(__('fedapay.merchant_only'), [], 403);
        }

        $result = $this->startWalletRecharge((int) $request->amount, 'app');

        if (!$result['success']) {
            return $this->responseWithError($result['message'], [], $result['http'] ?? 502);
        }

        return $this->responseWithSuccess(__('fedapay.initiated'), [
            'reference' => $result['reference'],
            // L'app ouvre cette URL en WebView ; elle ne voit jamais les clés.
            'payment_url' => $result['payment_url'],
        ], 200);
    }

    /**
     * Recharge depuis le panneau marchand web (même flux que l'app, même
     * webhook). Le formulaire manuel `recharge-add` (validation par
     * l'administrateur) reste disponible à côté.
     */
    public function rechargeWeb(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        if (blank(Auth::user()?->merchant)) {
            Toastr::error(__('fedapay.merchant_only'), __('message.error'));

            return redirect()->back();
        }

        $result = $this->startWalletRecharge((int) $request->amount, 'web');

        if (!$result['success']) {
            Toastr::error($result['message'], __('message.error'));

            return redirect()->back()->withInput();
        }

        return redirect()->away($result['payment_url']);
    }

    /**
     * Ouvre une recharge de wallet : ligne `wallets` en PENDING (la demande,
     * sans toucher au solde), enregistrement FedaPay, lien de paiement.
     * Seul le webhook signé fera passer la ligne en APPROVED.
     *
     * `$channel` (`app` ou `web`) voyage dans l'URL de retour : il dit au
     * `callback()` où renvoyer le marchand, rien de plus.
     *
     * @return array{success:bool, message?:string, http?:int, reference?:string, payment_url?:string}
     */
    private function startWalletRecharge(int $amount, string $channel): array
    {
        $merchant = Auth::user()->merchant;
        $companyId = $merchant->company_id;

        // Coupee depuis Reglages -> Pay-out, ou sans cles : on n'ouvre rien.
        if (!$this->gateway->isEnabled($companyId)) {
            return ['success' => false, 'message' => __('fedapay.not_configured'), 'http' => 503];
        }

        $reference = 'BL-' . Str::upper(Str::random(10));

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
            'callback_url' => route('fedapay.callback', ['reference' => $reference, 'channel' => $channel]),
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

            return ['success' => false, 'message' => $result['message'] ?? __('fedapay.error'), 'http' => 502];
        }

        $record->update([
            'provider_transaction_id' => $result['provider_transaction_id'],
            'payment_url' => $result['payment_url'],
        ]);

        return ['success' => true, 'reference' => $reference, 'payment_url' => $result['payment_url']];
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

        // ⚠️ Lecture SANS CONFIANCE, et sans le moindre effet de bord : elle ne
        // sert qu'à savoir **avec quel secret vérifier**. Depuis F3, un locataire
        // peut encaisser sur son propre compte FedaPay ; ses webhooks sont alors
        // signés par le secret de CE compte, pas par celui de la plateforme.
        // Désigner une transaction ne donne aucun pouvoir : il faut toujours
        // produire un HMAC valide avec le secret du compte concerné.
        $event = json_decode($payload, true) ?: [];
        $entity = $event['entity'] ?? $event['data']['object'] ?? [];
        $providerId = (string) ($entity['id'] ?? '');

        // ⚠️ Recherche SANS `companywise()` : le webhook n'a pas de session, et
        // `settings()` retomberait sur la société 1 (bloc A). Le locataire vient
        // de la transaction elle-même.
        $record = $providerId === ''
            ? null
            : FedaPayTransaction::where('provider_transaction_id', $providerId)->first();

        if (!$this->gateway->verifySignature($payload, $signature, $record?->gatewayCompanyId())) {
            // Ni détail ni indice : la réponse ne doit pas aider à forger une signature.
            return response()->json(['message' => 'invalid signature'], 400);
        }

        // À partir d'ici seulement, l'événement est authentique.
        $name = $event['name'] ?? $event['event'] ?? null;

        if ($providerId === '') {
            return response()->json(['message' => 'ignored'], 200);
        }

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
     * Crédite le wallet ou active l'abonnement, **une seule fois**.
     *
     * L'idempotence repose sur un verrou de ligne : `lockForUpdate()` sérialise
     * deux webhooks simultanés, et le statut déjà `approved` fait ressortir le
     * second sans agir. La cartographie a établi que ni
     * `WalletRepository::approved()` (bloc C) ni `switchPlan()` (bloc E) ne
     * sont idempotents : le garde-fou doit donc être ici.
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

        if ($record->purpose === FedaPayTransaction::PURPOSE_SUBSCRIPTION) {
            // Activation par le point unique du socle : même chemin que Stripe,
            // donc mêmes permissions, même ligne `subscriptions`, même
            // `general_settings.plan_id`. `switchPlan()` lit `plan_id` et
            // `user_id` sur une requête ; on lui en fabrique une.
            $activated = $record->plan_id && $record->user_id
                && $this->companyRepo->switchPlan(new Request([
                    'plan_id' => $record->plan_id,
                    'user_id' => $record->user_id,
                ]));

            if (!$activated) {
                Log::error('FedaPay : activation de l\'abonnement en échec', [
                    'reference' => $record->reference,
                    'plan_id' => $record->plan_id,
                    'user_id' => $record->user_id,
                ]);
            }

            return response()->json(['message' => 'approved'], 200);
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
     *
     * Pour un abonnement, on ramène l'utilisateur sur la page des plans : si
     * le webhook est déjà passé, elle montre le plan actif ; sinon un message
     * dit que la confirmation est en cours — sans jamais activer ici.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        $record = blank($reference) ? null : FedaPayTransaction::where('reference', $reference)->first();

        if ($record && $record->purpose === FedaPayTransaction::PURPOSE_SUBSCRIPTION) {
            if ($record->isApproved()) {
                Toastr::success(__('fedapay.subscription_activated'), __('message.success'));
            } else {
                Toastr::info(__('fedapay.subscription_pending'), __('fedapay.title'));
            }

            return redirect()->route('subscription.index');
        }

        // Recharge lancée depuis le panneau marchand web : retour à la page
        // du portefeuille. Le solde, lui, n'a bougé que si le webhook est passé.
        if ($record && $request->query('channel') === 'web') {
            if ($record->isApproved()) {
                Toastr::success(__('fedapay.wallet_credited'), __('message.success'));
            } else {
                Toastr::info(__('fedapay.pending_notice'), __('fedapay.title'));
            }

            return redirect()->route('merchant-panel.my.wallet.index');
        }

        return view('backend.payment.fedapay_callback', [
            'reference' => $reference,
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
