<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S139** — « se souvenir de moi » ne garde pas le mot de passe.
 *
 * Le socle rangeait le mot de passe saisi dans un cookie `userpassword` de 24 heures, et la page de
 * connexion le rendait **en clair** dans le `value` de son champ : quiconque ouvrait `/login` sur ce
 * navigateur, déconnecté ou pas, le lisait dans la source de la page. Désormais seul l'identifiant
 * est gardé ; le retour sans mot de passe est le jeton de rappel de Laravel, et ce jeton change avec
 * le mot de passe : un cookie de rappel ne rouvre pas une session que S135 a fermée.
 */
class RememberMeKeepsNoPasswordTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const SECRET = 'secret-du-poste-s139';

    private function agent(): User
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $agent = new User();
        $agent->forceFill([
            'company_id' => settings()->id, 'name' => 'Agent S139', 'email' => 'agent.s139@example.test',
            'mobile' => '2290197920139', 'password' => Hash::make(self::SECRET), 'user_type' => UserType::ADMIN,
            'role_id' => 1, 'permissions' => ['dashboard_read'], 'status' => 1, 'verification_status' => 1,
        ])->save();

        return $agent;
    }

    public function test_remember_me_keeps_the_identifier_never_the_password(): void
    {
        $this->agent();

        $reponse = $this->post(self::HOTE . '/login', [
            'email' => 'agent.s139@example.test', 'password' => self::SECRET, 'remember' => 'on',
        ])->assertRedirect(self::HOTE . '/dashboard');

        $reponse->assertCookie('useremail', 'agent.s139@example.test');
        $reponse->assertCookieExpired('userpassword'); // le cookie d'un navigateur servi avant S139 s'efface
        $this->assertNotNull($reponse->getCookie(auth()->guard('web')->getRecallerName(), false),
            'le retour sans mot de passe passe par le jeton de rappel de Laravel');
    }

    public function test_the_login_page_never_renders_a_password(): void
    {
        $this->agent();

        $page = $this->withCookie('useremail', 'agent.s139@example.test')
            ->withCookie('userpassword', self::SECRET) // cookie posé par le socle avant S139
            ->get(self::HOTE . '/login')->assertOk();

        $page->assertDontSee(self::SECRET);
        $page->assertSee('value="agent.s139@example.test"', false);
        $this->assertDoesNotMatchRegularExpression('/id="password"[^>]*value=/', $page->getContent());
    }

    public function test_a_remember_cookie_does_not_outlive_a_password_change(): void
    {
        $agent = $this->agent();
        $rappel = auth()->guard('web')->getRecallerName();

        $cookie = $this->post(self::HOTE . '/login', [
            'email' => 'agent.s139@example.test', 'password' => self::SECRET, 'remember' => 'on',
        ])->getCookie($rappel)->getValue();

        $fiche = User::findOrFail($agent->id); // changé ailleurs : par un agent, ou depuis un autre poste
        $fiche->password = Hash::make('nouveau-secret-s139');
        $fiche->save();

        // Ce navigateur revient plus tard : sa session a expiré, il ne lui reste que le cookie de rappel.
        $this->flushSession();
        app('auth')->forgetGuards();
        $this->withCookie($rappel, $cookie)->get(self::HOTE . '/dashboard')->assertRedirect();
        $this->assertGuest();
    }

    public function test_a_remember_cookie_still_works_while_the_password_stands(): void
    {
        $agent = $this->agent();
        $rappel = auth()->guard('web')->getRecallerName();

        $cookie = $this->post(self::HOTE . '/login', [
            'email' => 'agent.s139@example.test', 'password' => self::SECRET, 'remember' => 'on',
        ])->getCookie($rappel)->getValue();

        $fiche = User::findOrFail($agent->id);
        $fiche->name = 'Agent S139 renommé';
        $fiche->save();

        $this->flushSession();
        app('auth')->forgetGuards();
        $this->withCookie($rappel, $cookie)->get(self::HOTE . '/dashboard')->assertOk();
        $this->assertAuthenticatedAs($agent);
    }
}
