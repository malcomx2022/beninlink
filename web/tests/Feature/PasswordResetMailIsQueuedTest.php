<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S143** — le courriel de réinitialisation part en file, en français, avec le lien du site demandé.
 *
 * La notification de Laravel partait **dans la requête** (D13 : un SMTP lent ou en panne faisait échouer
 * « mot de passe oublié ») et **en anglais** : aucune de ses phrases n'était dans `lang/fr.json`. En file,
 * elle est bâtie par le worker, sans hôte ni société : le lien, la langue et la marque sont figés à la
 * demande, et la marque est celle de la société du compte (F4), y compris par l'API.
 */
class PasswordResetMailIsQueuedTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    private function compte(array $champs = []): User
    {
        $u = new User();
        $u->forceFill($champs + [
            'company_id' => settings()->id, 'name' => 'PME S143', 'email' => 'pme.s143@example.test',
            'password' => Hash::make('ancien-secret'), 'user_type' => UserType::MERCHANT, 'permissions' => [],
            'status' => Status::ACTIVE, 'verification_status' => Status::ACTIVE,
        ])->save();

        return $u;
    }

    public function test_the_site_queues_the_mail_with_its_own_link(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $compte = $this->compte();
        Queue::fake();

        $this->post(self::HOTE . '/password/email', ['email' => 'pme.s143@example.test'])->assertSessionHasNoErrors();

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) {
            return $job->notification instanceof ResetPasswordNotification
                && str_starts_with($job->notification->lien, self::HOTE . '/password/reset/')
                && $job->notification->locale === 'fr';
        });
        $this->assertTrue(is_a(ResetPasswordNotification::class, ShouldQueue::class, true));
    }

    public function test_the_mail_speaks_french_and_signs_with_the_account_company(): void
    {
        $this->seedTenant();
        $ailleurs = (int) DB::table('general_settings')->insertGetId(['name' => 'Kola Express']);
        $compte = $this->compte(['company_id' => $ailleurs]);
        config(['rxcourier.api_key' => self::API_KEY]);
        Notification::fake();

        $this->postJson('/api/v10/password/email', ['email' => 'pme.s143@example.test'], ['apiKey' => self::API_KEY])->assertOk();

        Notification::assertSentTo($compte, ResetPasswordNotification::class, function (ResetPasswordNotification $n) use ($compte) {
            $courriel = (string) $n->toMail($compte)->render();

            return $n->marque === 'Kola Express'
                && str_contains($courriel, 'Réinitialiser le mot de passe')
                && str_contains($courriel, 'Cordialement, Kola Express')
                && ! str_contains($courriel, 'You are receiving this email')
                && $n->toMail($compte)->subject === 'Réinitialisation de votre mot de passe';
        });
    }
}
