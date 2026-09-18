<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use GeoSot\EnvEditor\Facades\EnvEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S27 — l'éditeur de `.env` n'est plus joignable par le web.
 *
 * `geo-sot/laravel-env-editor` monte onze routes sous `/env-editor` avec le seul
 * middleware `['web']`, depuis un fournisseur **auto-découvert** qui ne teste
 * aucun environnement. Constaté avant correction : `GET /env-editor` répondait
 * **200 sans authentification**. Un visiteur lisait et écrivait le fichier
 * d'environnement — identifiants de base, `APP_KEY`, clés FedaPay, messagerie —
 * et pouvait régénérer la clé d'application ou restaurer une sauvegarde.
 *
 * Relevé en inventoriant les routes web à paramètre pour le filet d'isolation :
 * `env-editor/files/download/{filename?}` figurait dans la liste.
 */
class EnvEditorClosedTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    /** Les onze routes du paquet, telles que le routeur les connaît. */
    private function routesDeLEditeur(): array
    {
        $trouvees = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if (!Str::startsWith($route->uri(), 'env-editor')) {
                continue;
            }
            foreach ($route->methods() as $methode) {
                if (in_array($methode, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $trouvees[] = [$methode, $route->uri()];
            }
        }

        return $trouvees;
    }

    /** Le constat qui a déclenché ce correctif : elles existent bien. */
    public function test_the_package_really_registers_its_routes_here(): void
    {
        $routes = $this->routesDeLEditeur();

        $this->assertNotEmpty($routes, 'le paquet ne monte plus ses routes : ce test n a plus d objet');
        $this->assertGreaterThanOrEqual(10, count($routes));
    }

    /** Et aucune ne répond, sans authentification. */
    public function test_no_route_answers_to_an_anonymous_visitor(): void
    {
        $ouvertes = [];

        foreach ($this->routesDeLEditeur() as [$methode, $uri]) {
            $reponse = $this->call($methode, '/' . str_replace(['{filename?}', '{filename}'], 'sauvegarde.env', $uri));

            if ($reponse->getStatusCode() !== 404) {
                $ouvertes[] = "{$methode} /{$uri} → " . $reponse->getStatusCode();
            }
        }

        $this->assertSame([], $ouvertes, "routes de l'éditeur .env encore joignables :\n - "
            . implode("\n - ", $ouvertes));
    }

    /**
     * Ni pour un administrateur connecté. Éditer le fichier d'environnement
     * depuis le web n'est pas un droit à distribuer : ce sont les secrets de la
     * plateforme, et aucune trace ne dirait qui a changé quoi.
     */
    public function test_no_route_answers_to_a_signed_in_administrator_either(): void
    {
        $admin = new User();
        $admin->company_id = settings()->id;
        $admin->name = 'Administrateur';
        $admin->email = 'admin.s27@example.test';
        $admin->mobile = '0022997000400';
        $admin->password = bcrypt('secret');
        $admin->user_type = UserType::SUPER_ADMIN;
        $admin->save();

        $this->actingAs($admin);

        foreach ($this->routesDeLEditeur() as [$methode, $uri]) {
            $reponse = $this->call($methode, '/' . str_replace(['{filename?}', '{filename}'], 'sauvegarde.env', $uri));
            $this->assertSame(404, $reponse->getStatusCode(), "{$methode} /{$uri}");
        }
    }

    /** Le garde est bien celui de la configuration, pas un effet de bord. */
    public function test_the_guard_is_declared_in_the_published_config(): void
    {
        $this->assertContains(
            \App\Http\Middleware\BlockEnvEditorRoutes::class,
            config('env-editor.route.middleware'),
            'le garde doit rester déclaré dans config/env-editor.php',
        );
    }

    /**
     * ⚠️ La BIBLIOTHÈQUE reste utilisable, et c'est voulu :
     * `InstallerController` écrit `.env` par la façade pendant l'installation.
     * On coupe l'interface web, pas le paquet — même doctrine que S21 et D10.
     */
    public function test_the_library_itself_still_works_because_the_installer_needs_it(): void
    {
        $this->assertTrue(EnvEditor::keyExists('APP_KEY'));
        $this->assertStringContainsString(
            'EnvEditor::',
            file_get_contents(app_path('Http/Controllers/InstallerController.php')),
            'l installeur doit continuer à passer par la façade',
        );
    }

    /**
     * Le second versant de S27, relevé en préparant le commit du premier : le
     * paquet écrit ses sauvegardes dans `storage/env-editor`, et ce sont des
     * **copies intégrales de `.env`** — `APP_KEY` en clair y figurait. Le
     * répertoire n'était pas ignoré par git : un `git add -A` versionnait le
     * fichier d'environnement, et le dépôt est distant.
     *
     * `.env` lui-même est ignoré depuis toujours ; c'est sa copie sous un autre
     * nom qui ne l'était pas.
     */
    public function test_the_backup_directory_is_ignored_by_git(): void
    {
        $ignore = file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('/storage/env-editor', $ignore,
            'les sauvegardes du paquet sont des copies de .env : le répertoire doit rester ignoré');

        $sauvegardes = glob(config('env-editor.paths.backupDirectory') . '/*');

        $this->assertSame([], array_values(array_filter($sauvegardes ?: [], 'is_file')),
            "une copie de .env traîne dans storage/env-editor : à retirer avant tout commit");
    }
}
