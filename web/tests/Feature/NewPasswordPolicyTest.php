<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S134** — un mot de passe neuf a huit caractères au moins, quel que soit l'écran.
 *
 * Le socle en exigeait six au changement de mot de passe, à l'inscription d'un marchand et à la
 * création d'un livreur, huit à la réinitialisation, et **aucun** à la création d'une société, d'un
 * agent du back-office, ni à la modification d'un marchand ou d'une société (où `update()` écrit
 * pourtant le mot de passe saisi). Une seule règle désormais : `Password::defaults()` (huit
 * caractères, `AppServiceProvider`). La connexion ne l'exige pas : un compte plus ancien à six
 * caractères entre encore, et peut en changer.
 */
class NewPasswordPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    /** Tout champ de mot de passe d'une requête de formulaire passe par la règle commune. */
    public function test_every_new_password_field_uses_the_shared_rule(): void
    {
        $fichiers = Finder::create()->files()->in(app_path('Http/Requests'))->name('*.php')->sortByName();
        $vus = [];
        $fautifs = [];
        foreach ($fichiers as $fichier) {
            foreach (explode("\n", $fichier->getContents()) as $n => $ligne) {
                $champ = preg_match("/'(password|new_password)'\s*=>/", $ligne) && ! str_contains($ligne, '=> $password');
                $variable = preg_match('/\$password\s*=\s*\[/', $ligne);
                if (! $champ && ! $variable) {
                    continue;
                }
                $vus[] = $fichier->getFilename();
                if (! str_contains($ligne, 'Password::defaults()')) {
                    $fautifs[] = $fichier->getRelativePathname() . ':' . ($n + 1) . ' ' . trim($ligne);
                }
            }
        }

        $this->assertGreaterThanOrEqual(12, count($vus), 'le parcours a bien trouvé les champs de mot de passe');
        $this->assertSame([], $fautifs, "mot de passe neuf sans la règle commune :\n" . implode("\n", $fautifs));
        $this->assertStringContainsString('PasswordRule::defaults()',
            file_get_contents(app_path('Http/Controllers/Api/V10/AuthController.php')), 'la réinitialisation par l\'API aussi');
    }

    public function test_the_shared_rule_asks_for_eight_characters(): void
    {
        $this->assertTrue(validator(['p' => 'abcdefgh'], ['p' => \Illuminate\Validation\Rules\Password::defaults()])->passes());
        $this->assertTrue(validator(['p' => 'abcdefg'], ['p' => \Illuminate\Validation\Rules\Password::defaults()])->fails());
    }

    public function test_the_apps_refuse_a_seven_character_password_and_accept_eight(): void
    {
        Queue::fake();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        app(PiloteDataset::class)->seed((int) \App\Models\Backend\Merchant::firstOrFail()->company_id);
        $entetes = ['apiKey' => self::API_KEY];

        $pme = [
            'business_name' => 'Boutique Agbo', 'full_name' => 'Edwige Agbo', 'address' => 'Gbégamey, Cotonou',
            'mobile' => '0197010011', 'policy' => 1, 'hub_id' => 1, 'ifu' => '3202600010011', 'rccm' => 'RB/COT/26 B 10011',
        ];
        $this->postJson('/api/v10/register', $pme + ['password' => 'court07'], $entetes)
            ->assertStatus(422)->assertJsonStructure(['data' => ['message' => ['password']]]);
        $this->postJson('/api/v10/register', $pme + ['password' => 'assez-long'], $entetes)->assertOk();

        Sanctum::actingAs(User::where('unique_id', 'LIV-001')->firstOrFail(), ['deliveryman']);
        $this->putJson('/api/v10/update-password', [
            'old_password' => PiloteDataset::PASSWORD, 'new_password' => 'court07', 'confirm_password' => 'court07',
        ], $entetes)->assertStatus(422);
        $this->putJson('/api/v10/update-password', [
            'old_password' => PiloteDataset::PASSWORD, 'new_password' => 'kossi-2026', 'confirm_password' => 'kossi-2026',
        ], $entetes)->assertOk();
    }

    public function test_an_older_six_character_password_still_logs_in(): void
    {
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        app(PiloteDataset::class)->seed((int) \App\Models\Backend\Merchant::firstOrFail()->company_id);
        User::where('unique_id', 'PIL-001')->update(['password' => Hash::make('abc123')]);

        $this->postJson('/api/v10/signin', ['merchant_id' => 'PIL-001', 'password' => 'abc123'], ['apiKey' => self::API_KEY])
            ->assertOk();
    }
}
