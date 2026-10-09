<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\ResetPasswordNotification as ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S141** — réinitialiser son mot de passe sur le site n'ouvre pas de session, et ne vise que les comptes
 * de la société du site.
 *
 * Le contrôleur du socle (`ResetsPasswords` de Laravel, inchangé) connectait le compte dès le mot de passe
 * changé, sans aucune des conditions de `LoginController` : un marchand désactivé par son transporteur, un
 * livreur (S104) ou le compte d'une autre société entraient par « mot de passe oublié ». Et l'adresse se
 * cherchait sur toutes les sociétés : le site d'un transporteur envoyait son lien au compte d'un autre.
 */
class WebPasswordResetOpensNoSessionTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const NOUVEAU = 'nouveau-secret-s141';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    private function compte(string $email, array $champs = []): User
    {
        $u = new User();
        $u->forceFill($champs + [
            'company_id' => settings()->id, 'name' => 'Compte S141', 'email' => $email,
            'password' => Hash::make('ancien-secret'), 'user_type' => UserType::MERCHANT, 'permissions' => [],
            'status' => Status::ACTIVE, 'verification_status' => Status::ACTIVE,
        ])->save();

        return $u;
    }

    private function reinitialiser(User $compte, ?string $jeton = null)
    {
        return $this->post(self::HOTE . '/password/reset', [
            'token' => $jeton ?? Password::broker()->createToken($compte), 'email' => $compte->email,
            'password' => self::NOUVEAU, 'password_confirmation' => self::NOUVEAU,
        ]);
    }

    public function test_a_reset_changes_the_password_and_sends_to_the_login_page(): void
    {
        $compte = $this->compte('pme.s141@example.test');

        $this->reinitialiser($compte)->assertRedirect(self::HOTE . '/login');

        $this->assertGuest();
        $this->assertTrue(Hash::check(self::NOUVEAU, $compte->fresh()->password));
        $this->post(self::HOTE . '/login', ['email' => 'pme.s141@example.test', 'password' => self::NOUVEAU])
            ->assertRedirect(self::HOTE . '/dashboard');
        $this->assertAuthenticatedAs($compte);
    }

    public function test_a_disabled_account_or_a_courier_does_not_enter_by_resetting(): void
    {
        $desactive = $this->compte('desactive.s141@example.test', ['status' => Status::INACTIVE]);
        $this->reinitialiser($desactive)->assertRedirect(self::HOTE . '/login');
        $this->assertGuest();

        $livreur = $this->compte('livreur.s141@example.test', ['user_type' => UserType::DELIVERYMAN]);
        $this->reinitialiser($livreur)->assertRedirect(self::HOTE . '/login');
        $this->assertGuest();
    }

    public function test_the_site_neither_sends_a_link_to_nor_resets_an_account_of_another_company(): void
    {
        Notification::fake();
        $ailleurs = (int) DB::table('general_settings')->insertGetId(['name' => 'Autre transporteur S141']);
        $autre = $this->compte('autre.s141@example.test', ['company_id' => $ailleurs]);
        $ici = $this->compte('ici.s141@example.test');

        $this->post(self::HOTE . '/password/email', ['email' => 'autre.s141@example.test'])->assertSessionHasErrors('email');
        Notification::assertNotSentTo($autre, ResetPassword::class);

        $this->post(self::HOTE . '/password/email', ['email' => 'ici.s141@example.test']);
        Notification::assertSentTo($ici, ResetPassword::class);

        $this->reinitialiser($autre); // jeton valable, mais pas sur ce site
        $this->assertFalse(Hash::check(self::NOUVEAU, $autre->fresh()->password));
        $this->assertGuest();
    }
}
