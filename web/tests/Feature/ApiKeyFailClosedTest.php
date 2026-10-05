<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S77 (T7)** — la clé d'API n'a plus de valeur de repli, et la porte refuse fermé.
 *
 * Le socle We Courier livrait `'api_key' => '123456rx-ecourier123456'` : une
 * installation sans variable `API_KEY` répondait à la clé publique de l'éditeur,
 * identique pour toutes les installations du produit et lisible dans ses APK.
 * Et `CheckApiKeyMiddleware` comparait avec `==` : clé configurée vide, en-tête
 * `apiKey` vide, requête acceptée.
 *
 * Trois cas figés ici, plus la forme de la config et du middleware — parce que
 * les tests posent la clé par `config()`, un repli remis dans `rxcourier.php`
 * resterait invisible au seul comportement.
 */
class ApiKeyFailClosedTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const CLE_DU_SOCLE = '123456rx-ecourier123456';
    private const CLE = 'blk_cle-de-test-s77';

    /** Une route derrière `CheckApiKey` seul (sans Sanctum) : la réponse ne dépend que de la clé. */
    private const ROUTE = '/api/v10/general-settings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    // ---- la config ----------------------------------------------------------------

    public function test_la_config_ne_porte_plus_la_cle_publique_du_socle(): void
    {
        $source = file_get_contents(config_path('rxcourier.php'));

        $this->assertStringNotContainsString(self::CLE_DU_SOCLE, $source, 'le repli du socle est retiré');
        $this->assertStringContainsString("'api_key' => env('API_KEY'),", $source, 'env() sans second argument : sans variable, null');
    }

    // ---- le middleware, exécuté -------------------------------------------------------

    /** Serveur sans clé : même la clé publique du socle (celle des anciennes APK) est refusée. */
    public function test_sans_cle_configuree_tout_est_refuse_y_compris_la_cle_du_socle(): void
    {
        foreach ([null, ''] as $absente) {
            config(['rxcourier.api_key' => $absente]);

            $this->getJson(self::ROUTE, ['apiKey' => self::CLE_DU_SOCLE])
                ->assertStatus(400)->assertJsonPath('success', false)->assertJsonPath('message', 'Invalid Api Key');
            $this->getJson(self::ROUTE, ['apiKey' => ''])
                ->assertStatus(400, 'vide contre vide : le == du socle laissait passer');
            $this->getJson(self::ROUTE)->assertStatus(400);
        }
    }

    public function test_un_en_tete_vide_ou_absent_est_refuse_meme_avec_une_cle_configuree(): void
    {
        config(['rxcourier.api_key' => self::CLE]);

        $this->getJson(self::ROUTE, ['apiKey' => ''])->assertStatus(400);
        $this->getJson(self::ROUTE)->assertStatus(400);
        $this->getJson(self::ROUTE, ['apiKey' => self::CLE_DU_SOCLE])->assertStatus(400, 'la clé du socle n’est pas la nôtre');
        $this->getJson(self::ROUTE, ['apiKey' => strtoupper(self::CLE)])->assertStatus(400, 'comparaison stricte, casse comprise');
        $this->getJson(self::ROUTE, ['apiKey' => self::CLE . ' '])->assertStatus(400, 'pas de tolérance sur la longueur');
    }

    public function test_la_bonne_cle_passe(): void
    {
        config(['rxcourier.api_key' => self::CLE]);

        $this->getJson(self::ROUTE, ['apiKey' => self::CLE])->assertOk()->assertJsonPath('success', true);
    }

    // ---- la forme du middleware ---------------------------------------------------------

    public function test_le_middleware_compare_a_temps_constant_et_sans_conversion(): void
    {
        // Sans commentaires : le docbloc raconte le `==` du socle, le code ne doit plus le porter.
        $code = php_strip_whitespace(app_path('Http/Middleware/CheckApiKeyMiddleware.php'));

        $this->assertStringContainsString('hash_equals(', $code);
        $this->assertDoesNotMatchRegularExpression('/[^=!]==[^=]/', $code, 'plus de comparaison lâche (==) dans le middleware');
    }
}
