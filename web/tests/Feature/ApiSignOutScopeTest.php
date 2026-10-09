<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S136** — se déconnecter d'un appareil ne déconnecte pas les autres.
 *
 * `sign-out` et `refresh` supprimaient **tous** les jetons du compte : un marchand qui fermait la
 * session de son téléphone perdait celle de sa tablette, un livreur sa seconde app. Ils ne touchent
 * plus que le jeton de l'appareil qui appelle ; fermer tous les accès reste le rôle du changement de
 * mot de passe (S135).
 */
class ApiSignOutScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    private function entetes(string $jeton): array
    {
        return ['apiKey' => self::API_KEY, 'Authorization' => 'Bearer ' . $jeton];
    }

    private function compte(): User
    {
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        app(PiloteDataset::class)->seed((int) \App\Models\Backend\Merchant::firstOrFail()->company_id);

        return User::where('unique_id', 'PIL-001')->firstOrFail();
    }

    public function test_signing_out_closes_this_device_only(): void
    {
        $compte = $this->compte();
        $telephone = $compte->createToken('telephone', ['merchant'])->plainTextToken;
        $tablette = $compte->createToken('tablette', ['merchant'])->plainTextToken;

        $this->postJson('/api/v10/sign-out', [], $this->entetes($telephone))->assertOk();

        $this->assertSame(['tablette'], $compte->tokens()->pluck('name')->all());
        app('auth')->forgetGuards();
        $this->getJson('/api/v10/profile', $this->entetes($telephone))->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->getJson('/api/v10/profile', $this->entetes($tablette))->assertOk();
    }

    public function test_refreshing_replaces_this_device_token_only(): void
    {
        $compte = $this->compte();
        $telephone = $compte->createToken('telephone', ['merchant'])->plainTextToken;
        $compte->createToken('tablette', ['merchant']);

        $nouveau = $this->getJson('/api/v10/refresh', $this->entetes($telephone))->assertOk()->json('data.token');

        $this->assertEqualsCanonicalizing(['telephone', 'tablette'], $compte->tokens()->pluck('name')->all(),
            'le jeton du téléphone est remplacé (même nom), la tablette garde le sien');
        $this->assertSame(['merchant'], $compte->tokens()->where('name', 'telephone')->value('abilities'),
            'le jeton renouvelé garde les droits du type de compte');
        app('auth')->forgetGuards();
        $this->getJson('/api/v10/profile', $this->entetes($telephone))->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->getJson('/api/v10/profile', $this->entetes($nouveau))->assertOk();
    }
}
