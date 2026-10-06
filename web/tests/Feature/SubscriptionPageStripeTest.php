<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S105 — la page des plans ne dépend plus d'une ligne de réglage qui peut manquer.
 *
 * Mesuré avant le correctif : `GET /subscription` répondait **500 à tout compte**
 * dès que la plateforme (société 1) n'avait pas de ligne `stripe_status` — la vue
 * faisait `$stripe_status->value` sur `null`. Or c'est la page où le middleware
 * `subscriptionCheck` renvoie un locataire dont le plan a expiré : il ne pouvait
 * donc ni voir les plans ni payer par Mobile Money. Et `GET /subscription/payment`
 * lisait la clé Stripe de la même façon : 500 sans clé.
 *
 * Désormais le contrôleur calcule un seul drapeau — interrupteur actif ET clé
 * posée — ; sans lui, pas de bouton Stripe et un départ refusé avec un message.
 */
class SubscriptionPageStripeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        Setting::where('company_id', 1)->whereIn('key', ['stripe_status', 'stripe_secret_key'])->delete();
    }

    public function test_the_plans_page_renders_without_any_stripe_setting_and_offers_no_card_button(): void
    {
        $this->actingAs($this->agent())->get(self::HOTE . '/subscription')
            ->assertOk()
            ->assertSee(__('levels.plan_switch_notice'))
            ->assertDontSee('subscription/payment');
    }

    public function test_a_switch_without_a_key_is_not_enough_for_the_card_button(): void
    {
        Setting::forceCreate(['company_id' => 1, 'key' => 'stripe_status', 'value' => Status::ACTIVE]);

        $this->actingAs($this->agent())->get(self::HOTE . '/subscription')
            ->assertOk()
            ->assertDontSee('subscription/payment');
    }

    public function test_the_card_button_appears_once_the_platform_has_switch_and_key(): void
    {
        Setting::forceCreate(['company_id' => 1, 'key' => 'stripe_status', 'value' => Status::ACTIVE]);
        Setting::forceCreate(['company_id' => 1, 'key' => 'stripe_secret_key', 'value' => 'sk_test_x']);

        $this->actingAs($this->agent())->get(self::HOTE . '/subscription')
            ->assertOk()
            ->assertSee('subscription/payment');
    }

    public function test_leaving_for_stripe_without_a_key_is_refused_not_crashed(): void
    {
        $this->actingAs($this->agent())
            ->get(self::HOTE . '/subscription/payment?plan_id=1')
            ->assertRedirect(self::HOTE . '/subscription');
    }

    private function agent(): User
    {
        return User::where('user_type', UserType::ADMIN)->firstOrFail();
    }
}
