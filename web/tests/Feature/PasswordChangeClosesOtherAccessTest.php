<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S135** — un mot de passe changé ferme les autres accès.
 *
 * Changer son mot de passe est ce qu'on fait quand on craint qu'un autre le connaisse. Le socle le
 * changeait sans rien fermer : les jetons de l'API ouverts sur un autre téléphone restaient valides,
 * et une session web ouverte ailleurs aussi. Désormais un changement de mot de passe, par n'importe
 * quel chemin, révoque les jetons du compte (sauf celui de l'appareil qui le change, `User::booted`),
 * et `CloseSessionsOnPasswordChange` ferme les autres sessions web.
 */
class PasswordChangeClosesOtherAccessTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    private function pilote(): User
    {
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        app(PiloteDataset::class)->seed((int) \App\Models\Backend\Merchant::firstOrFail()->company_id);

        return User::where('unique_id', 'PIL-001')->firstOrFail();
    }

    private function entetes(string $jeton): array
    {
        return ['apiKey' => self::API_KEY, 'Authorization' => 'Bearer ' . $jeton];
    }

    public function test_changing_the_password_in_the_app_revokes_the_other_phones_only(): void
    {
        $compte = $this->pilote();
        $ici = $compte->createToken('ce-telephone', ['merchant'])->plainTextToken;
        $ailleurs = $compte->createToken('autre-telephone', ['merchant'])->plainTextToken;

        $this->putJson('/api/v10/update-password', [
            'old_password' => PiloteDataset::PASSWORD, 'new_password' => 'kora-2026-ok', 'confirm_password' => 'kora-2026-ok',
        ], $this->entetes($ici))->assertOk();

        $this->assertSame(['ce-telephone'], $compte->tokens()->pluck('name')->all(),
            'le jeton de l\'autre téléphone est révoqué, celui qui a changé le mot de passe reste');
        app('auth')->forgetGuards();
        $this->getJson('/api/v10/profile', $this->entetes($ailleurs))->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->getJson('/api/v10/profile', $this->entetes($ici))->assertOk();
    }

    public function test_a_password_reset_or_a_change_by_an_agent_revokes_every_token(): void
    {
        $compte = $this->pilote();
        $compte->createToken('un', ['merchant']);
        $compte->createToken('deux', ['merchant']);

        $this->postJson('/api/v10/password/reset', [
            'email' => $compte->email, 'token' => Password::broker()->createToken($compte),
            'password' => 'kora-2026-ok', 'password_confirmation' => 'kora-2026-ok',
        ], ['apiKey' => self::API_KEY])->assertOk();
        $this->assertSame(0, $compte->tokens()->count(), 'la réinitialisation se fait sans session : tout est révoqué');

        $compte->createToken('trois', ['merchant']);
        $fiche = User::findOrFail($compte->id);
        $fiche->password = Hash::make('change-par-agent');
        $fiche->save();
        $this->assertSame(0, $compte->tokens()->count(), 'un agent qui change le mot de passe ferme les accès du compte');

        $fiche->name = 'Autre nom';
        $compte->createToken('quatre', ['merchant']);
        $fiche->save();
        $this->assertSame(1, $compte->tokens()->count(), 'une modification sans mot de passe ne révoque rien');
    }

    public function test_a_web_session_opened_elsewhere_is_closed_by_a_password_change(): void
    {
        $this->assertContains(\App\Http\Middleware\CloseSessionsOnPasswordChange::class,
            app(\App\Http\Kernel::class)->getMiddlewareGroups()['web']);

        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $agent = new User();
        $agent->forceFill([
            'company_id' => settings()->id, 'name' => 'Agent S135', 'email' => 'agent.s135@example.test',
            'mobile' => '2290197920135', 'password' => Hash::make('ancien-secret'), 'user_type' => \App\Enums\UserType::ADMIN,
            'role_id' => 1, 'permissions' => ['dashboard_read'], 'status' => 1, 'verification_status' => 1,
        ])->save();

        $this->post(self::HOTE . '/login', ['email' => 'agent.s135@example.test', 'password' => 'ancien-secret'])
            ->assertRedirect(self::HOTE . '/dashboard');
        $this->get(self::HOTE . '/dashboard')->assertOk();

        $this->put(self::HOTE . "/admin/profile/update-password/{$agent->id}", [
            'old_password' => 'ancien-secret', 'new_password' => 'secret-de-ce-poste', 'confirm_password' => 'secret-de-ce-poste',
        ])->assertRedirect();
        app('auth')->forgetGuards();
        $this->get(self::HOTE . '/dashboard')->assertOk(); // la session qui a changé le mot de passe reste ouverte

        User::whereKey($agent->id)->update(['password' => Hash::make('nouveau-secret')]); // changé ailleurs
        app('auth')->forgetGuards(); // une requête neuve relit le compte, comme sur le serveur

        $this->get(self::HOTE . '/dashboard')->assertRedirect();
        $this->assertGuest();
    }
}
