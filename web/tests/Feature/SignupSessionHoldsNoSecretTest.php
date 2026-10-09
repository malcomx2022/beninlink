<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S138** — la session d'une inscription ne garde ni mot de passe ni code.
 *
 * Le socle rangeait dans la session, **en clair**, le mot de passe tapé à l'inscription (marchand et
 * société) et le code OTP envoyé : toute fuite de session (fichier du serveur, sauvegarde, débogage)
 * donnait les deux. Le mot de passe ne servait qu'à rejouer la connexion après le code ; le code n'était
 * relu nulle part. La session garde désormais le numéro (ou le courriel) et l'identifiant marchand ; le
 * code vérifié ouvre lui-même la session du compte qu'il vérifie, sur le site de sa société.
 */
class SignupSessionHoldsNoSecretTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    public function test_a_merchant_signs_up_then_its_code_logs_it_in_without_a_password_in_session(): void
    {
        Queue::fake();
        $this->seedTenant();
        $this->mountTenantRoutes();
        Cache::flush();

        $this->post(self::HOTE . '/merchant/sign-up-store', [
            'business_name' => 'Boutique Kpodji', 'full_name' => 'Romaric Kpodji', 'address' => 'Agla, Cotonou',
            'mobile' => '0197010138', 'password' => 'secret-de-pme', 'policy' => 1, 'hub_id' => 1,
            'ifu' => '3202600010138', 'rccm' => 'RB/COT/26 B 10138',
        ])->assertRedirect();

        $this->assertFalse(session()->has('password'), 'le mot de passe ne va pas dans la session');
        $this->assertFalse(session()->has('otp'), 'le code ne va pas dans la session');
        $this->assertSame('2290197010138', session('mobile'));

        $compte = User::where('mobile', '2290197010138')->firstOrFail();
        $this->post(self::HOTE . '/merchant/otp-verification', ['mobile' => session('mobile'), 'otp' => $compte->otp])
            ->assertRedirect();

        $this->assertAuthenticatedAs($compte->fresh());
        $this->assertFalse(session()->has('password'));
    }

    public function test_no_signup_path_writes_a_password_or_a_code_into_the_session(): void
    {
        foreach ([
            app_path('Repositories/Merchant/MerchantRepository.php'),
            app_path('Repositories/Superadmin/Company/CompanyRepository.php'),
            app_path('Http/Controllers/Backend/MerchantController.php'),
        ] as $fichier) {
            preg_match_all('/session\(\s*\[(.*?)\]\s*\)/s', file_get_contents($fichier), $appels);
            foreach ($appels[1] as $contenu) {
                $this->assertDoesNotMatchRegularExpression("/'(password|otp)'\s*=>/", $contenu, basename($fichier) . ' range un secret dans la session');
            }
            $this->assertStringNotContainsString("session('password')", file_get_contents($fichier));
        }
    }
}
