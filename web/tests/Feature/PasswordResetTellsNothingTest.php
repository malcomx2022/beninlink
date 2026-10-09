<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S147** — « mot de passe oublié » répond pareil, que l'adresse ait un compte ou non.
 *
 * L'API rendait un 422 « Nous ne pouvons pas trouver un utilisateur avec cette adresse e-mail. » à une adresse
 * inconnue, et la page web la même erreur sous le champ : n'importe qui pouvait vérifier quelles adresses sont
 * inscrites (contraire à S142). Un second envoi trop rapproché (« throttled ») ne visait lui aussi qu'un compte
 * existant. Désormais une seule réponse ; le lien ne part toujours qu'au compte qui existe.
 */
class PasswordResetTellsNothingTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    private function compte(): User
    {
        $u = new User();
        $u->forceFill([
            'company_id' => settings()->id, 'name' => 'PME S147', 'email' => 'pme.s147@example.test',
            'password' => Hash::make('ancien-secret'), 'user_type' => UserType::MERCHANT, 'permissions' => [],
            'status' => Status::ACTIVE, 'verification_status' => Status::ACTIVE,
        ])->save();

        return $u;
    }

    public function test_the_api_answers_the_same_for_a_known_an_unknown_and_a_repeated_address(): void
    {
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $compte = $this->compte();
        Notification::fake();
        $demander = fn (string $email) => $this->postJson('/api/v10/password/email', ['email' => $email], ['apiKey' => self::API_KEY]);

        $connue = $demander('pme.s147@example.test')->assertOk();
        $repetee = $demander('pme.s147@example.test')->assertOk(); // « throttled » côté broker
        $inconnue = $demander('personne.s147@example.test')->assertOk();

        $this->assertSame($connue->json(), $inconnue->json());
        $this->assertSame($connue->json(), $repetee->json());
        $this->assertSame(__('passwords.sent_neutral'), $inconnue->json('data.message'));
        $this->assertStringNotContainsString(__('passwords.user'), $inconnue->getContent());
        Notification::assertSentToTimes($compte, ResetPasswordNotification::class, 1);
    }

    public function test_the_site_answers_the_same_for_a_known_and_an_unknown_address(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $compte = $this->compte();
        Notification::fake();

        $this->from(self::HOTE . '/password/reset')->post(self::HOTE . '/password/email', ['email' => 'personne.s147@example.test'])
            ->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.sent_neutral'));
        $this->from(self::HOTE . '/password/reset')->post(self::HOTE . '/password/email', ['email' => 'pme.s147@example.test'])
            ->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.sent_neutral'));

        Notification::assertSentTo($compte, ResetPasswordNotification::class);
    }
}
