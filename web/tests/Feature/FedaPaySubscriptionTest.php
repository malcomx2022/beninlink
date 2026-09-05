<?php

namespace Tests\Feature;

use App\Http\Controllers\Payment\FedaPayController;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use App\Services\Payments\FedaPayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Chantier 3, seconde moitie : l'abonnement SaaS par FedaPay.
 *
 * Les regles de `.claude/rules/payments.md` sont testees une a une :
 *   - le webhook signe est la seule source de verite (le retour du navigateur
 *     n'active rien) ;
 *   - le traitement est idempotent (deux webhooks identiques = une activation) ;
 *   - l'activation passe par le service existant du socle (`switchPlan()`).
 *
 * Aucun appel reseau : la passerelle est remplacee par un double.
 */
class FedaPaySubscriptionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const WEBHOOK_SECRET = 'wh_test_secret';

    private User $admin;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config([
            'fedapay.webhook_secret' => self::WEBHOOK_SECRET,
            'fedapay.webhook_tolerance' => 300,
        ]);

        // L'administrateur de la societe 2 (locataire seede), pas le superadmin.
        $this->admin = User::where('email', 'company@wemaxdevs.com')->firstOrFail();
        $this->plan = Plan::where('price', '>', 0)->orderBy('id')->firstOrFail();
    }

    private function signature(string $payload): string
    {
        $timestamp = time();

        return "t={$timestamp},s=" . hash_hmac('sha256', $timestamp . '.' . $payload, self::WEBHOOK_SECRET);
    }

    private function webhook(string $event, string $providerId, int $amount)
    {
        $payload = json_encode([
            'name' => $event,
            'entity' => ['id' => $providerId, 'amount' => $amount],
        ]);

        return $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => $this->signature($payload),
        ], $payload);
    }

    private function transactionEnAttente(string $providerId): FedaPayTransaction
    {
        return FedaPayTransaction::create([
            'company_id' => $this->admin->company_id,
            'reference' => 'BL-SUB-' . $providerId,
            'provider_transaction_id' => $providerId,
            'purpose' => FedaPayTransaction::PURPOSE_SUBSCRIPTION,
            'amount' => (int) round((float) $this->plan->price),
            'status' => FedaPayTransaction::STATUS_PENDING,
            'plan_id' => $this->plan->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_l_initiation_cree_une_transaction_en_attente_et_redirige_vers_fedapay(): void
    {
        $attendu = (int) round((float) $this->plan->price);

        $this->mock(FedaPayGateway::class, function ($mock) use ($attendu) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('initialize')
                ->once()
                // Cles PLATEFORME (company_id nul), montant entier du plan.
                ->withArgs(fn (array $p) => $p['company_id'] === null
                    && $p['amount'] === $attendu
                    && $p['purpose'] === FedaPayTransaction::PURPOSE_SUBSCRIPTION)
                ->andReturn([
                    'success' => true,
                    'payment_url' => 'https://sandbox-checkout.fedapay.com/x',
                    'provider_transaction_id' => 'FP-SUB-1',
                ]);
        });

        Auth::login($this->admin);
        $reponse = app(FedaPayController::class)->subscribe(
            Request::create('/subscription/fedapay', 'POST', ['plan_id' => $this->plan->id])
        );

        $this->assertSame('https://sandbox-checkout.fedapay.com/x', $reponse->getTargetUrl());

        $record = FedaPayTransaction::where('provider_transaction_id', 'FP-SUB-1')->firstOrFail();
        $this->assertSame(FedaPayTransaction::PURPOSE_SUBSCRIPTION, $record->purpose);
        $this->assertSame(FedaPayTransaction::STATUS_PENDING, $record->status);
        $this->assertSame($this->plan->id, (int) $record->plan_id);
        $this->assertSame($this->admin->id, (int) $record->user_id);
        $this->assertSame($this->admin->company_id, (int) $record->company_id);
        $this->assertNull($record->merchant_id);

        // Rien n'est active a l'initiation.
        $this->assertSame(0, Subscription::where('plan_id', $this->plan->id)->count());
    }

    public function test_un_plan_gratuit_ne_passe_pas_par_fedapay(): void
    {
        $gratuit = Plan::where('price', 0)->firstOrFail();

        $this->mock(FedaPayGateway::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('initialize')->never();
        });

        Auth::login($this->admin);
        app(FedaPayController::class)->subscribe(
            Request::create('/subscription/fedapay', 'POST', ['plan_id' => $gratuit->id])
        );

        $this->assertSame(0, FedaPayTransaction::count());
    }

    public function test_le_webhook_approuve_active_le_plan_une_seule_fois(): void
    {
        $this->transactionEnAttente('FP-SUB-2');
        $avant = Subscription::count();
        $montant = (int) round((float) $this->plan->price);

        $this->webhook('transaction.approved', 'FP-SUB-2', $montant)->assertOk();
        // Rejeu identique : FedaPay reessaie, un reseau duplique...
        $this->webhook('transaction.approved', 'FP-SUB-2', $montant)->assertOk();

        $this->assertSame($avant + 1, Subscription::count());

        $abonnement = Subscription::orderByDesc('id')->firstOrFail();
        $this->assertSame($this->plan->id, (int) $abonnement->plan_id);
        $this->assertSame($this->admin->company_id, (int) $abonnement->company_id);
        $this->assertSame($this->admin->id, (int) $abonnement->user_id);

        $societe = GeneralSettings::findOrFail($this->admin->company_id);
        $this->assertSame($this->plan->id, (int) $societe->plan_id);
        $this->assertSame($abonnement->id, (int) $societe->subscription_id);

        $record = FedaPayTransaction::where('provider_transaction_id', 'FP-SUB-2')->firstOrFail();
        $this->assertTrue($record->isApproved());
        $this->assertNotNull($record->approved_at);
    }

    public function test_un_webhook_refuse_n_active_rien(): void
    {
        $this->transactionEnAttente('FP-SUB-3');
        $avant = Subscription::count();

        $this->webhook('transaction.declined', 'FP-SUB-3', 0)->assertOk();

        $this->assertSame($avant, Subscription::count());
        $this->assertSame(
            FedaPayTransaction::STATUS_DECLINED,
            FedaPayTransaction::where('provider_transaction_id', 'FP-SUB-3')->value('status')
        );
    }

    public function test_un_webhook_non_signe_est_rejete(): void
    {
        $this->transactionEnAttente('FP-SUB-4');
        $avant = Subscription::count();

        $payload = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => 'FP-SUB-4']]);
        $this->call('POST', '/fedapay/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload)
            ->assertStatus(400);

        $this->assertSame($avant, Subscription::count());
    }

    public function test_le_retour_du_navigateur_n_active_rien(): void
    {
        $record = $this->transactionEnAttente('FP-SUB-5');
        $avant = Subscription::count();

        // C'est l'URL que FedaPay rappelle : l'utilisateur peut aussi l'ouvrir
        // a la main. Elle doit renvoyer vers la page des plans sans rien activer.
        $this->get('/fedapay/callback?reference=' . $record->reference)
            ->assertRedirect(route('subscription.index'));

        $this->assertSame($avant, Subscription::count());
        $this->assertSame(FedaPayTransaction::STATUS_PENDING, $record->fresh()->status);
    }
}
