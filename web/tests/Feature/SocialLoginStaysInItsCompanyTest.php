<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProfilSocial;
use Mockery;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S140** — la connexion Google ou Facebook reste dans sa société.
 *
 * Le retour du fournisseur cherchait le compte par son identifiant social **sur toutes les sociétés** et
 * ouvrait sa session sans autre contrôle : la PME d'un transporteur entrait sur le site d'un autre, un compte
 * désactivé par son transporteur entrait encore, un agent du back-office aussi. Désormais le compte est de la
 * société du site, marchand, actif et vérifié — les mêmes conditions que la connexion par mot de passe.
 */
class SocialLoginStaysInItsCompanyTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    private function fournisseur(string $social, string $id, string $email = 'pme.sociale@example.test'): void
    {
        $profil = (new ProfilSocial())->map(['id' => $id, 'name' => 'Boutique Sociale', 'email' => $email, 'avatar_original' => null]);
        $pilote = Mockery::mock();
        $pilote->shouldReceive('user')->andReturn($profil);
        Socialite::shouldReceive('driver')->with($social)->andReturn($pilote);
    }

    private function compte(array $champs): User
    {
        $u = new User();
        $u->forceFill($champs + [
            'company_id' => settings()->id, 'name' => 'Boutique S140', 'email' => uniqid('s140.') . '@example.test',
            'password' => Hash::make('secret-s140'), 'user_type' => UserType::MERCHANT, 'permissions' => [],
            'status' => Status::ACTIVE, 'verification_status' => Status::ACTIVE,
        ])->save();

        return $u;
    }

    public function test_a_merchant_of_this_site_signs_in_and_gets_a_fresh_session(): void
    {
        $pme = $this->compte(['google_id' => 'g-ici']);
        $this->fournisseur('google', 'g-ici');
        $this->withSession(['avant' => 1]);
        $avant = session()->getId();

        $this->get(self::HOTE . '/google/login')->assertRedirect('/');

        $this->assertAuthenticatedAs($pme);
        $this->assertNotSame($avant, session()->getId(), 'Auth::login() régénère l\'identifiant de session');
    }

    public function test_an_account_of_another_company_does_not_enter_this_site(): void
    {
        $ailleurs = (int) DB::table('general_settings')->insertGetId(['name' => 'Autre transporteur S140']);
        $this->compte(['google_id' => 'g-ailleurs', 'company_id' => $ailleurs]);
        $this->fournisseur('google', 'g-ailleurs');

        $this->get(self::HOTE . '/google/login')->assertRedirect(self::HOTE . '/login');
        $this->assertGuest();

        $this->compte(['facebook_id' => 'f-ailleurs', 'company_id' => $ailleurs]);
        $this->fournisseur('facebook', 'f-ailleurs');
        $this->get(self::HOTE . '/facebook/login')->assertRedirect(self::HOTE . '/login');
        $this->assertGuest();
        $this->assertSame(1, User::where('facebook_id', 'f-ailleurs')->count(), 'aucun second compte n\'est créé ici');
    }

    public function test_a_disabled_account_does_not_enter(): void
    {
        $this->compte(['google_id' => 'g-inactif', 'status' => Status::INACTIVE]);
        $this->fournisseur('google', 'g-inactif');
        $this->get(self::HOTE . '/google/login')->assertRedirect(self::HOTE . '/login');
        $this->assertGuest();
    }

    public function test_a_back_office_agent_does_not_enter_through_the_merchant_social_login(): void
    {
        $this->compte(['google_id' => 'g-agent', 'user_type' => UserType::ADMIN, 'role_id' => 1]);
        $this->fournisseur('google', 'g-agent');
        $this->get(self::HOTE . '/google/login')->assertRedirect(self::HOTE . '/login');
        $this->assertGuest();
    }

    public function test_a_new_social_account_registers_in_this_site_company(): void
    {
        $this->fournisseur('google', 'g-nouveau', 'nouvelle.pme@example.test');

        $this->get(self::HOTE . '/google/login')->assertRedirect('/');

        $pme = User::where('google_id', 'g-nouveau')->firstOrFail();
        $this->assertSame((int) settings()->id, (int) $pme->company_id);
        $this->assertSame(UserType::MERCHANT, (int) $pme->user_type);
        $this->assertAuthenticatedAs($pme);
    }
}
