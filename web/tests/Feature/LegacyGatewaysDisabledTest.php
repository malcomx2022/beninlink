<?php

namespace Tests\Feature;

use App\Enums\PayoutSetup;
use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantSetting;
use App\Models\Backend\Setting;
use App\Repositories\MerchantOnlinePaymentSetup\PaymentSetupRepository;
use App\Repositories\PayoutSetup\PayoutSetupRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S21 — Aamarpay et SSLCommerz sont désactivées (décision du 2026-09-05) :
 * TLS non vérifié dans leur code, aucun usage au Bénin. Leur code reste,
 * mais rien ne doit plus permettre de les activer ni de les appeler.
 */
class LegacyGatewaysDisabledTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        Sanctum::actingAs(Merchant::firstOrFail()->user, ['merchant']);
    }

    public function test_the_two_gateways_are_disabled_and_the_others_are_not(): void
    {
        $this->assertFalse(gatewayEnabled(PayoutSetup::AAMARPAY));
        $this->assertFalse(gatewayEnabled(PayoutSetup::SSL_COMMERZ));
        foreach ([PayoutSetup::STRIPE, PayoutSetup::PAYPAL, PayoutSetup::RAZORPAY, PayoutSetup::SKRILL, PayoutSetup::BKASH] as $autre) {
            $this->assertTrue(gatewayEnabled($autre), "passerelle {$autre}");
        }
    }

    public function test_company_settings_refuse_to_enable_a_disabled_gateway(): void
    {
        $request = new Request([
            'aamarpay_store_id' => 'boutique', 'aamarpay_signature_key' => 'secret', 'aamarpay_status' => 'on',
        ]);

        $this->assertFalse(app(PayoutSetupRepository::class)->update(PayoutSetup::AAMARPAY, $request));
        $this->assertSame(0, Setting::where('key', 'like', 'aamarpay_%')->count());

        // Une passerelle restée active s'enregistre toujours.
        $this->assertTrue(app(PayoutSetupRepository::class)->update(PayoutSetup::STRIPE, new Request([
            'stripe_publishable_key' => 'pk', 'stripe_secret_key' => 'sk', 'stripe_status' => 'on',
        ])));
        $this->assertEquals(Status::ACTIVE, Setting::where('key', 'stripe_status')->value('value'));
    }

    public function test_merchant_settings_refuse_to_enable_a_disabled_gateway(): void
    {
        $request = new Request([
            'sslcommerz_store_id' => 'boutique', 'sslcommerz_store_password' => 'secret', 'sslcommerz_status' => 'on',
        ]);

        $this->assertFalse(app(PaymentSetupRepository::class)->update(PayoutSetup::SSL_COMMERZ, $request));
        $this->assertSame(0, MerchantSetting::where('key', 'like', 'sslcommerz_%')->count());
    }

    public function test_the_migration_turns_existing_statuses_off_and_keeps_the_keys(): void
    {
        $merchant = Merchant::firstOrFail();
        DB::table('settings')->insert([
            ['company_id' => $merchant->company_id, 'key' => 'aamarpay_status', 'value' => 1],
            ['company_id' => $merchant->company_id, 'key' => 'aamarpay_store_id', 'value' => 'boutique'],
            ['company_id' => $merchant->company_id, 'key' => 'stripe_status', 'value' => 1],
        ]);
        DB::table('merchant_settings')->insert([
            ['merchant_id' => $merchant->id, 'key' => 'sslcommerz_status', 'value' => 1],
        ]);

        (require database_path('migrations/2026_09_05_100000_disable_legacy_gateways.php'))->up();

        $this->assertEquals(0, DB::table('settings')->where('key', 'aamarpay_status')->value('value'));
        $this->assertSame('boutique', DB::table('settings')->where('key', 'aamarpay_store_id')->value('value'));
        $this->assertEquals(1, DB::table('settings')->where('key', 'stripe_status')->value('value'));
        $this->assertEquals(0, DB::table('merchant_settings')->where('key', 'sslcommerz_status')->value('value'));
    }

    public function test_the_gated_views_still_compile(): void
    {
        $vues = [
            'backend/payout/index.blade.php',
            'backend/setting/payout_setup/index.blade.php',
            'backend/merchant_panel/onlinepayment/index.blade.php',
            'backend/merchant_panel/settings/online_payment_setup/index.blade.php',
        ];
        foreach ($vues as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));
            $this->assertStringContainsString('gatewayEnabled', $source, $vue);
            $compiled = Blade::compileString($source);
            $this->assertSame(substr_count($compiled, '<?php if('), substr_count($compiled, '<?php endif; ?>'), "@if/@endif déséquilibrés dans {$vue}");
        }
    }
}
