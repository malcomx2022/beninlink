<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S98** — le livreur a « mot de passe oublié » dans son app.
 *
 * L'app marchand l'avait (deux écrans sur `password/email` et `password/reset`) ;
 * l'app livreur n'avait que la connexion : un livreur qui oubliait son mot de
 * passe devait attendre que le transporteur le change au back-office. Les deux
 * routes sont communes (le courtier `users`), limitées depuis S96 : l'app
 * livreur les consomme telles quelles, dans l'ordre `web/` → app. Rien ne
 * change côté `web/` ; ce filet prouve que le chemin existe pour un livreur et
 * que l'app le lit.
 */
class CourierPasswordResetTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const CLE = 'cle-de-test';

    private User $livreur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::CLE, 'app.app_installed' => 'yes']);
        $this->livreur = $this->livreur();
    }

    private function entetes(): array
    {
        return ['apiKey' => self::CLE, 'Accept-Language' => ''];
    }

    public function test_a_courier_receives_the_reset_link_by_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v10/password/email', ['email' => $this->livreur->email], $this->entetes())
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo($this->livreur, ResetPassword::class);
    }

    public function test_a_courier_sets_a_new_password_with_the_token_and_signs_in_with_it(): void
    {
        $jeton = Password::createToken($this->livreur);

        $this->postJson('/api/v10/password/reset', [
            'token' => $jeton,
            'email' => $this->livreur->email,
            'password' => 'nouveau-secret-9',
            'password_confirmation' => 'nouveau-secret-9',
        ], $this->entetes())->assertOk();

        $this->assertTrue(Hash::check('nouveau-secret-9', $this->livreur->fresh()->password));

        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => $this->livreur->unique_id, 'password' => 'nouveau-secret-9'], $this->entetes())
            ->assertOk();
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => $this->livreur->unique_id, 'password' => 'secret123'], $this->entetes())
            ->assertStatus(401, 'l\'ancien mot de passe ne vaut plus');
    }

    /** Le contrat côté app : l'inventaire, le lien sur la connexion, les deux écrans, les appels sans jeton. */
    public function test_the_courier_app_offers_the_two_steps(): void
    {
        $racine = dirname(base_path()) . '/mobile-livreur/';

        $inventaire = file_get_contents($racine . 'src/api/endpoints.ts');
        $this->assertStringContainsString("passwordEmail: 'password/email'", $inventaire);
        $this->assertStringContainsString("passwordReset: 'password/reset'", $inventaire);

        $module = file_get_contents($racine . 'src/api/auth.ts');
        // Chaque fonction séparément : une expression paresseuse sur tout le module
        // irait chercher le `authenticated: false` de la fonction voisine.
        $fonctions = preg_split('/(?=export async function )/', $module);
        foreach (['requestPasswordReset', 'resetPassword'] as $fonction) {
            $corps = collect($fonctions)->first(fn ($f) => str_starts_with($f, 'export async function ' . $fonction . '('));
            $this->assertNotNull($corps, $fonction . ' existe');
            $this->assertStringContainsString('{ authenticated: false }', $corps, $fonction . ' : l\'appel se fait sans jeton, personne n\'est connecté');
        }

        $connexion = file_get_contents($racine . 'app/(auth)/login.tsx');
        $this->assertStringContainsString("href=\"/(auth)/forgot-password\"", $connexion, 'le lien sur l\'écran de connexion');

        $this->assertFileExists($racine . 'app/(auth)/forgot-password.tsx');
        $this->assertFileExists($racine . 'app/(auth)/reset-password.tsx');
        $this->assertStringContainsString('name="forgot-password"', file_get_contents($racine . 'app/(auth)/_layout.tsx'));
        $this->assertStringContainsString("resetTokenHint:", file_get_contents($racine . 'src/i18n/fr.ts'));
    }

    private function livreur(): User
    {
        $user = Merchant::firstOrFail()->user->replicate();
        $user->name = 'Livreur oublieux';
        $user->email = 'livreur-oublieux@example.test';
        $user->mobile = '0197080001';
        $user->unique_id = 'L-OUBLI';
        $user->user_type = UserType::DELIVERYMAN;
        $user->password = Hash::make('secret123');
        $user->save();

        DeliveryMan::forceCreate([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 0, 'pickup_charge' => 0, 'return_charge' => 0,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);

        return $user->fresh();
    }
}
