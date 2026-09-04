<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S5 — cloisonnement marchand / livreur de l'API.
 *
 * Avant : un seul groupe `auth:sanctum`, aucun test sur `user_type`, jetons
 * sans abilities. Un jeton marchand appelait `deliveryman/*` ; un jeton
 * livreur sur une route marchand déclenchait un 500 (pas un refus).
 * Après : `userType:merchant` / `userType:deliveryman` → 403, et le jeton
 * porte l'ability de son type dès la connexion.
 */
class ApiUserTypeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    private User $livreur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();

        // Un livreur de la même société que le marchand.
        $this->livreur = $this->merchant->user->replicate();
        $this->livreur->name = 'Livreur test';
        $this->livreur->email = 'livreur@example.test';
        $this->livreur->mobile = '0022997000010';
        $this->livreur->unique_id = 'L-0001';
        $this->livreur->user_type = UserType::DELIVERYMAN;
        $this->livreur->password = Hash::make('secret123');
        $this->livreur->save();

        DeliveryMan::forceCreate([
            'company_id' => $this->livreur->company_id,
            'user_id' => $this->livreur->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 0,
            'pickup_charge' => 0,
            'return_charge' => 0,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    public function test_a_merchant_token_cannot_reach_deliveryman_routes(): void
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        $this->getJson('/api/v10/deliveryman/parcel/index', $this->entetes())
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('auth.forbidden_user_type'));

        $this->postJson('/api/v10/deliveryman/parcel-location-update', [], $this->entetes())
            ->assertStatus(403);
    }

    public function test_a_deliveryman_token_cannot_reach_merchant_routes(): void
    {
        Sanctum::actingAs($this->livreur->fresh(), ['deliveryman']);

        // Avant S5 : 500 (le contrôleur cherchait un marchand inexistant).
        $this->getJson('/api/v10/parcel/index', $this->entetes())->assertStatus(403);
        $this->getJson('/api/v10/dashboard', $this->entetes())->assertStatus(403);
        $this->getJson('/api/v10/wallet/history', $this->entetes())->assertStatus(403);
        $this->postJson('/api/v10/fedapay/initiate', [], $this->entetes())->assertStatus(403);
    }

    public function test_each_type_reaches_its_own_routes_and_the_shared_ones(): void
    {
        Sanctum::actingAs($this->livreur->fresh(), ['deliveryman']);
        $this->getJson('/api/v10/deliveryman/parcel/index', $this->entetes())->assertOk();
        $this->getJson('/api/v10/refresh', $this->entetes())->assertOk();

        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);
        $this->getJson('/api/v10/parcel/index', $this->entetes())->assertOk();
        $this->getJson('/api/v10/refresh', $this->entetes())->assertOk();
    }

    public function test_legacy_tokens_without_abilities_are_still_fenced_by_user_type(): void
    {
        // Jeton émis avant S5 : abilities ['*']. Le type de compte suffit.
        Sanctum::actingAs($this->merchant->user->fresh(), ['*']);
        $this->getJson('/api/v10/parcel/index', $this->entetes())->assertOk();
        $this->getJson('/api/v10/deliveryman/parcel/index', $this->entetes())->assertStatus(403);
    }

    public function test_a_token_scoped_for_another_type_is_refused_even_if_the_account_matches(): void
    {
        // Jeton portant l'ability livreur, présenté par un compte marchand :
        // l'ability ne correspond pas au type du compte → refus.
        Sanctum::actingAs($this->merchant->user->fresh(), ['deliveryman']);
        $this->getJson('/api/v10/parcel/index', $this->entetes())->assertStatus(403);
    }

    public function test_login_issues_a_token_scoped_to_the_account_type(): void
    {
        $reponse = $this->postJson('/api/v10/deliveryman/login', [
            'driver_id' => 'L-0001',
            'password' => 'secret123',
        ], $this->entetes())->assertOk();

        $jeton = $reponse->json('data.token');
        $this->assertNotEmpty($jeton);
        $this->assertSame(['deliveryman'], PersonalAccessToken::latest('id')->first()->abilities);

        $entetes = $this->entetes() + ['Authorization' => 'Bearer ' . $jeton];
        $this->getJson('/api/v10/deliveryman/parcel/index', $entetes)->assertOk();
        $this->getJson('/api/v10/parcel/index', $entetes)->assertStatus(403);

        // Le renouvellement conserve la portée.
        $nouveau = $this->getJson('/api/v10/refresh', $entetes)->assertOk()->json('data.token');
        $this->assertSame(['deliveryman'], PersonalAccessToken::latest('id')->first()->abilities);
        $this->assertNotSame($jeton, $nouveau);
    }

    public function test_merchant_login_issues_a_merchant_scoped_token(): void
    {
        $utilisateur = $this->merchant->user;
        $utilisateur->password = Hash::make('secret123');
        $utilisateur->save();

        $this->postJson('/api/v10/signin', [
            'merchant_id' => $utilisateur->unique_id,
            'password' => 'secret123',
        ], $this->entetes())->assertOk();

        $this->assertSame(['merchant'], PersonalAccessToken::latest('id')->first()->abilities);
    }
}
