<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S145** — un compte désactivé par son transporteur n'obtient plus de jeton, et perd les siens.
 *
 * `signin` et `deliveryman/login` ne vérifiaient que l'identifiant et le mot de passe : la connexion web exige un
 * compte actif (`LoginController::credentials()`), l'API non. Un marchand ou un livreur désactivé gardait l'app,
 * avec ses jetons déjà ouverts comme avec un nouveau.
 */
class DisabledAccountGetsNoTokenTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        app(PiloteDataset::class)->seed((int) \App\Models\Backend\Merchant::firstOrFail()->company_id);
    }

    private function desactiver(string $identifiant): User
    {
        $compte = User::where('unique_id', $identifiant)->firstOrFail();
        $compte->status = Status::INACTIVE;
        $compte->save();

        return $compte;
    }

    public function test_a_disabled_merchant_or_courier_gets_no_token(): void
    {
        $this->desactiver('PIL-001');
        $this->desactiver('LIV-001');

        $this->postJson('/api/v10/signin', ['merchant_id' => 'PIL-001', 'password' => PiloteDataset::PASSWORD], ['apiKey' => self::API_KEY])
            ->assertStatus(403)->assertJsonMissingPath('data.token');
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'LIV-001', 'password' => PiloteDataset::PASSWORD], ['apiKey' => self::API_KEY])
            ->assertStatus(403)->assertJsonMissingPath('data.token');

        $this->assertSame(0, User::where('unique_id', 'PIL-001')->firstOrFail()->tokens()->count());
    }

    public function test_an_active_account_still_signs_in(): void
    {
        $this->postJson('/api/v10/signin', ['merchant_id' => 'PIL-001', 'password' => PiloteDataset::PASSWORD], ['apiKey' => self::API_KEY])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'LIV-001', 'password' => PiloteDataset::PASSWORD], ['apiKey' => self::API_KEY])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_disabling_an_account_revokes_its_open_tokens(): void
    {
        $compte = User::where('unique_id', 'PIL-001')->firstOrFail();
        $jeton = $compte->createToken('telephone', ['merchant'])->plainTextToken;
        $this->getJson('/api/v10/profile', ['apiKey' => self::API_KEY, 'Authorization' => 'Bearer ' . $jeton])->assertOk();

        $this->desactiver('PIL-001');
        app('auth')->forgetGuards();

        $this->getJson('/api/v10/profile', ['apiKey' => self::API_KEY, 'Authorization' => 'Bearer ' . $jeton])->assertUnauthorized();

        $compte->refresh()->forceFill(['name' => 'Renommé'])->save();
        $this->assertSame(0, $compte->tokens()->count(), 'une autre modification ne recrée rien');
    }
}
