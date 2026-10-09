<?php

namespace Tests\Feature;

use App\Models\Backend\Merchant;
use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/** Lot 10 : les refus de compte disent de corriger, sans annoncer une écriture. */
class AccountValidationMessageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => 'cle-compte-test']);
        app(PiloteDataset::class)->seed((int) Merchant::firstOrFail()->company_id);
    }

    public static function langues(): array
    {
        return [
            'français' => ['fr', 'Veuillez corriger les champs du formulaire.'],
            'anglais' => ['en', 'Please correct the form fields.'],
        ];
    }

    public static function comptesEtLangues(): array
    {
        $cases = [];
        foreach (['PIL-001', 'LIV-001'] as $code) {
            foreach (self::langues() as $langue => $values) {
                $cases[$code . ' ' . $langue] = [$code, ...$values];
            }
        }
        return $cases;
    }

    private function entetes(User $user, string $langue): array
    {
        $ability = $user->unique_id === 'LIV-001' ? 'deliveryman' : 'merchant';
        $token = $user->createToken('telephone-test', [$ability])->plainTextToken;
        $user->createToken('autre-telephone', [$ability]);
        return ['apiKey' => 'cle-compte-test', 'Authorization' => 'Bearer ' . $token, 'Accept-Language' => $langue];
    }

    /** @dataProvider langues */
    public function test_invalid_profile_has_a_correction_message_and_changes_nothing(string $langue, string $message): void
    {
        $user = User::where('unique_id', 'PIL-001')->firstOrFail();
        $headers = $this->entetes($user, $langue);
        $before = $user->getAttributes();
        $merchant = $user->merchant->getAttributes();
        $tokens = $user->tokens()->pluck('id')->all();

        $this->postJson('/api/v10/profile/update', [
            'name' => '', 'address' => '', 'business_name' => 'Ne doit pas être enregistré',
        ], $headers)->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonPath('message', $message)
            ->assertJsonStructure(['data' => ['message' => ['name', 'address']]]);

        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertSame($merchant, $user->merchant->fresh()->getAttributes());
        $this->assertSame($tokens, $user->tokens()->pluck('id')->all());
    }

    /** @dataProvider comptesEtLangues */
    public function test_invalid_password_has_a_correction_message_and_preserves_password_and_tokens(string $code, string $langue, string $message): void
    {
        $user = User::where('unique_id', $code)->firstOrFail();
        $headers = $this->entetes($user, $langue);
        $password = $user->getRawOriginal('password');
        $tokens = $user->tokens()->pluck('id')->all();

        $this->putJson('/api/v10/update-password', [
            'old_password' => PiloteDataset::PASSWORD,
            'new_password' => 'court', 'confirm_password' => 'autre',
        ], $headers)->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonPath('message', $message)
            ->assertJsonStructure(['data' => ['message' => ['new_password', 'confirm_password']]]);

        $this->assertSame($password, $user->fresh()->getRawOriginal('password'));
        $this->assertSame($tokens, $user->tokens()->pluck('id')->all());
    }

    /** @dataProvider langues */
    public function test_valid_profile_keeps_the_success_message_and_is_saved(string $langue): void
    {
        $user = User::where('unique_id', 'PIL-001')->firstOrFail();
        $headers = $this->entetes($user, $langue);
        $tokens = $user->tokens()->pluck('id')->all();
        $this->postJson('/api/v10/profile/update', [
            'name' => 'Gérant témoin', 'address' => 'Domiciliation recette',
            'business_name' => 'Boutique témoin', 'email' => $user->email, 'mobile' => $user->mobile,
        ], $headers)->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('message', __('auth.profile_update', [], $langue));
        $this->assertSame('Gérant témoin', $user->fresh()->name);
        $this->assertSame('Boutique témoin', $user->merchant->fresh()->business_name);
        $this->assertSame($tokens, $user->tokens()->pluck('id')->all());
    }
}
