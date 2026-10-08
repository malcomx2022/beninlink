<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S130** — le parcours OTP du site marchand est limité comme celui de l'API (S96).
 *
 * `merchant/otp-verification` acceptait un code à cinq chiffres sans frein (90 000 essais suffisent),
 * et `merchant/resend-otp` envoyait un SMS payant à chaque appel.
 */
class WebOtpThrottleTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        Cache::flush();
    }

    public function test_both_otp_routes_carry_the_web_limiter(): void
    {
        foreach (['merchant.otp-verification', 'merchant.resend-otp'] as $nom) {
            $this->assertContains('throttle:connexion-web', Route::getRoutes()->getByName($nom)->gatherMiddleware(), "{$nom} sans limite");
        }
    }

    public function test_the_sixth_code_for_the_same_number_is_refused_with_a_french_message(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(self::HOTE . '/merchant/otp-verification', ['mobile' => '0197000001', 'otp' => (string) (10000 + $i)]);
            $this->assertStringNotContainsString('Trop de tentatives', (string) session('warning'), "l'essai " . ($i + 1) . ' est déjà refusé');
        }

        $sixieme = $this->post(self::HOTE . '/merchant/otp-verification', ['mobile' => '01 97 00 00 01', 'otp' => '10005']);

        // Réponse d'écran : retour au formulaire, avec le message du limiteur.
        $sixieme->assertRedirect(route('merchant.otp-verification-form'));
        $this->assertStringContainsString('Trop de tentatives', (string) session('warning'));

        // Un autre numéro, depuis la même adresse, garde ses essais.
        session()->forget('warning');
        $this->post(self::HOTE . '/merchant/otp-verification', ['mobile' => '0197000002', 'otp' => '10000']);
        $this->assertStringNotContainsString('Trop de tentatives', (string) session('warning'));
    }
}
