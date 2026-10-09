<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\Superadmin\Company\CompanyRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S137** — un code SMS ne sert qu'une fois, et pas longtemps.
 *
 * Par l'API, `otp-verification` ouvre une session (un jeton) à qui présente le numéro et le code.
 * Le socle ne l'effaçait jamais et ne lui donnait aucune échéance : le code d'inscription d'une PME
 * restait une clé de son compte, rejouable à vie. Désormais un code vaut `User::OTP_TTL_MINUTES`
 * (posé par le modèle, quel que soit le chemin qui l'écrit) et s'efface dès qu'il a servi. Le code
 * par courriel de l'inscription d'une société suit la même échéance.
 */
class OtpSingleUseTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    private function inscrire(string $mobile): User
    {
        Queue::fake();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $this->postJson('/api/v10/register', [
            'business_name' => 'Boutique Adjovi', 'full_name' => 'Sêmévo Adjovi', 'address' => 'Fidjrossè, Cotonou',
            'mobile' => $mobile, 'password' => 'pilote2026', 'policy' => 1, 'hub_id' => 1,
            'ifu' => '3202600010137', 'rccm' => 'RB/COT/26 B 10137',
        ], ['apiKey' => self::API_KEY])->assertOk();

        return User::where('mobile', \App\Support\BeninPhone::normalize($mobile))->firstOrFail();
    }

    public function test_a_code_opens_one_session_then_is_gone(): void
    {
        $compte = $this->inscrire('0197010137');
        $code = $compte->otp;
        $this->assertNotNull($compte->otp_expires_at, 'le code reçoit son échéance à l\'écriture');

        $this->postJson('/api/v10/otp-verification', ['mobile' => '0197010137', 'otp' => $code], ['apiKey' => self::API_KEY])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
        $this->assertNull($compte->fresh()->otp, 'le code est effacé dès qu\'il a servi');
        $this->assertNull($compte->fresh()->otp_expires_at);

        $this->postJson('/api/v10/otp-verification', ['mobile' => '0197010137', 'otp' => $code], ['apiKey' => self::API_KEY])
            ->assertStatus(401);
    }

    public function test_an_expired_code_is_refused_and_a_new_one_works(): void
    {
        $compte = $this->inscrire('0197010138');
        $code = $compte->otp;

        $this->travel(User::OTP_TTL_MINUTES + 1)->minutes();
        $this->postJson('/api/v10/otp-verification', ['mobile' => '0197010138', 'otp' => $code], ['apiKey' => self::API_KEY])
            ->assertStatus(401);

        $this->postJson('/api/v10/resend-otp', ['mobile' => '0197010138'], ['apiKey' => self::API_KEY])->assertOk();
        $nouveau = $compte->fresh()->otp;
        $this->postJson('/api/v10/otp-verification', ['mobile' => '0197010138', 'otp' => $nouveau], ['apiKey' => self::API_KEY])
            ->assertOk();
    }

    public function test_the_company_signup_code_expires_too(): void
    {
        $this->seedTenant();
        $compte = User::firstOrFail();
        $compte->otp = 54321;
        $compte->save();
        $demande = Request::create('/x', 'POST', ['email' => $compte->email, 'otp' => 54321]);

        $this->travel(User::OTP_TTL_MINUTES + 1)->minutes();
        $this->assertSame(0, app(CompanyRepository::class)->otpVerification($demande));

        $compte->otp = 54322; // un renvoi tire un nouveau code : l'échéance repart
        $compte->save();
        $demande = Request::create('/x', 'POST', ['email' => $compte->email, 'otp' => 54322]);
        $this->assertInstanceOf(User::class, app(CompanyRepository::class)->otpVerification($demande));
        $this->assertNull($compte->fresh()->otp);
    }
}
