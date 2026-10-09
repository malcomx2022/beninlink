<?php

namespace Tests\Feature;

use App\Services\OpenApi\SpecGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chantier 7 — la spécification OpenAPI est générée depuis le routeur et ne
 * peut donc pas dériver de `routes/api.php`. Ce test verrouille les trois
 * contrats : le routeur (source), l'overlay (documentation) et l'inventaire
 * des endpoints de l'app marchand (consommateur).
 */
class OpenApiSpecTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_otp_contract_requires_the_phone_as_well_as_the_code(): void
    {
        $spec = (new SpecGenerator())->generate();
        $schema = $spec['paths']['/otp-verification']['post']['requestBody']['content']['application/json']['schema'];
        $this->assertSame(['mobile', 'otp'], $schema['required']);
        $this->assertArrayHasKey('mobile', $schema['properties']);
    }

    public function test_every_api_route_is_in_the_spec(): void
    {
        $generator = new SpecGenerator();
        $spec = $generator->generate();

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertNotEmpty($spec['paths']);

        foreach ($generator->apiRoutes() as $route) {
            $path = '/' . $generator->relativePath($route);
            $this->assertArrayHasKey($path, $spec['paths'], "Route absente du spec : {$path}");

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $this->assertArrayHasKey(strtolower($method), $spec['paths'][$path], "{$method} {$path} absent du spec");
            }
        }
    }

    public function test_overlay_only_describes_existing_routes(): void
    {
        $generator = new SpecGenerator();

        $this->assertSame([], $generator->orphans(), 'Entrées d\'overlay sans route correspondante');
        $this->assertSame([], $generator->undocumented(), 'Routes sans entrée d\'overlay');
    }

    public function test_security_follows_the_real_middlewares(): void
    {
        $spec = (new SpecGenerator())->generate();

        // Publique : ni apiKey ni bearer (hors du groupe CheckApiKey).
        $this->assertSame([], $spec['paths']['/openapi.json']['get']['security']);

        // Connexion : apiKey seul, sans jeton.
        $signin = $spec['paths']['/signin']['post'];
        $this->assertSame([['apiKey' => []]], $signin['security']);
        $this->assertSame('Identifiants invalides', $signin['responses']['401']['description']);

        // Route limitée (throttle:5,1) : le 429 est documenté.
        $reset = $spec['paths']['/password/email']['post'];
        $this->assertArrayHasKey('429', $reset['responses']);

        // Route protégée : apiKey + bearer, 401 documenté.
        $profile = $spec['paths']['/profile']['get'];
        $this->assertSame([['apiKey' => [], 'bearer' => []]], $profile['security']);
        $this->assertArrayHasKey('401', $profile['responses']);

        // Paramètre de chemin déduit du routeur.
        $details = $spec['paths']['/parcel/details/{id}']['get'];
        $this->assertSame('id', $details['parameters'][0]['name']);
        $this->assertArrayHasKey('404', $details['responses']);
    }

    public function test_merchant_app_inventory_matches_the_spec(): void
    {
        $file = base_path('../mobile/src/api/endpoints.ts');
        if (!is_file($file)) {
            $this->markTestSkipped('mobile/src/api/endpoints.ts absent');
        }

        $source = file_get_contents($file);
        // On ne lit que le bloc `endpoints` (les MISSING sont, par définition, hors backend).
        $block = substr($source, strpos($source, 'export const endpoints'));
        $block = substr($block, 0, strpos($block, '} as const;'));

        preg_match_all("/:\s*'([^']+)'/", $block, $plain);
        preg_match_all('/`([^`]+)`/', $block, $templated);

        $paths = array_merge(
            $plain[1],
            array_map(fn ($p) => preg_replace('/\$\{[^}]+\}/', '{param}', $p), $templated[1])
        );
        $this->assertNotEmpty($paths);

        $spec = (new SpecGenerator())->generate();
        $known = array_map(fn ($p) => preg_replace('/\{[^}]+\}/', '{param}', $p), array_keys($spec['paths']));

        foreach ($paths as $path) {
            $this->assertContains('/' . $path, $known, "L'app marchand appelle {$path}, absent de l'API");
        }
    }

    public function test_spec_and_docs_are_served(): void
    {
        $this->getJson('/api/v10/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', config('openapi.title'));

        config(['app.app_installed' => 'yes']);
        $this->get('/api/docs')
            ->assertOk()
            ->assertSee('swagger-ui', false)
            ->assertSee(json_encode(route('openapi.spec')), false);
    }

    public function test_generate_command_checks_drift(): void
    {
        $this->artisan('openapi:generate', ['--check' => true])->assertSuccessful();
    }
}
