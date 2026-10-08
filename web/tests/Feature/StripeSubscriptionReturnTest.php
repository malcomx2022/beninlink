<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Role;
use App\Models\Backend\Setting;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S129** — le retour Stripe n'active qu'un abonnement, pour le compte qui a payé.
 *
 * S1 vérifiait la session auprès de Stripe (payée, au nom du compte connecté, au bon montant), mais :
 * - le plan s'appliquait à `user_id` lu dans l'URL — `switchPlan()` remplaçait les permissions du
 *   compte qu'on lui nommait, de n'importe quelle société ;
 * - une session payée se rejouait : rappeler la même URL réactivait le plan pour une nouvelle période.
 */
class StripeSubscriptionReturnTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private User $payeur;

    private User $victime;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();

        $societe = settings()->id;
        Setting::query()->updateOrCreate(['company_id' => 1, 'key' => 'stripe_secret_key'], ['value' => 'sk_test_beninlink']);
        $this->plan = Plan::query()->first() ?? tap(new Plan(), fn ($p) => $p->forceFill([
            'name' => 'Mensuel', 'days_count' => 30, 'price' => 25, 'modules' => json_encode([]),
        ])->save());

        $this->payeur = new User();
        $this->payeur->forceFill([
            'company_id' => $societe, 'name' => 'Transporteur payeur', 'email' => 'payeur@example.test',
            'mobile' => '0022997999101', 'password' => bcrypt('secret'), 'user_type' => UserType::ADMIN,
            'role_id' => Role::where('company_id', $societe)->value('id'), 'permissions' => ['dashboard_read'],
        ])->save();

        $this->victime = User::where('company_id', '!=', $societe)->where('user_type', UserType::ADMIN)->firstOrFail();
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    /** Stripe répond par cette session, quel que soit l'identifiant demandé. */
    private function stripeRepond(array $session): void
    {
        ApiRequestor::setHttpClient(new class($session) implements ClientInterface {
            public function __construct(private array $session)
            {
            }

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                return [json_encode($this->session + ['object' => 'checkout.session']), 200, []];
            }
        });
    }

    private function retour(string $session, int $userId): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->payeur)->get(self::HOTE . '/subscription/success?' . http_build_query([
            'plan_id' => $this->plan->id, 'user_id' => $userId, 'session_id' => $session,
        ]));
    }

    private function sessionPayee(string $id): array
    {
        return [
            'id' => $id, 'payment_status' => 'paid', 'client_reference_id' => (string) $this->payeur->id,
            'amount_total' => (int) round(((float) $this->plan->price) * 100),
        ];
    }

    public function test_the_plan_goes_to_the_payer_never_to_the_user_named_in_the_url(): void
    {
        $permissionsAvant = $this->victime->fresh()->permissions;
        $this->stripeRepond($this->sessionPayee('cs_test_s129_a'));

        $this->retour('cs_test_s129_a', $this->victime->id);

        $this->assertSame($permissionsAvant, $this->victime->fresh()->permissions, 'les droits de la victime ont été réécrits');
        $abonnement = Subscription::where('stripe_session_id', 'cs_test_s129_a')->first();
        $this->assertNotNull($abonnement, 'la session payée active bien un abonnement');
        $this->assertSame($this->payeur->id, (int) $abonnement->user_id);
        $this->assertSame((int) $this->payeur->company_id, (int) $abonnement->company_id);
    }

    public function test_a_paid_session_activates_one_subscription_only(): void
    {
        $this->stripeRepond($this->sessionPayee('cs_test_s129_b'));

        $this->retour('cs_test_s129_b', $this->payeur->id);
        $this->app['auth']->forgetGuards();
        $this->retour('cs_test_s129_b', $this->payeur->id);

        $this->assertSame(1, Subscription::where('stripe_session_id', 'cs_test_s129_b')->count(), 'la session a été rejouée');
    }

    public function test_an_unpaid_session_activates_nothing(): void
    {
        $this->stripeRepond(['payment_status' => 'unpaid'] + $this->sessionPayee('cs_test_s129_c'));
        $avant = Subscription::count();

        $this->retour('cs_test_s129_c', $this->payeur->id);

        $this->assertSame($avant, Subscription::count());
    }
}
