<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S88** — l'installateur du socle n'est pas joignable sur une base installée.
 *
 * Relu en préparant S87 : `routes/web.php` monte trois routes d'installation et
 * seule `GET /install` portait le garde `IsNotInstalled`. `POST /installing` et
 * **`GET /finish`** ne portaient que `XSS`, et `InstallerController::finish()`
 * fait, sans authentification : `SHOW TABLES` et `Schema::drop` de chaque table,
 * `migrate:refresh`, `db:seed`, puis écrit le nom, le courriel et le mot de
 * passe du compte n° 1 depuis la requête, et réécrit `APP_INSTALLED` et
 * `APP_URL` dans le `.env`. Une requête anonyme vidait la base d'une installation
 * terminée. Et le garde ne reconnaissait une installation que par
 * `APP_INSTALLED=yes`, que la première installation prescrite par le guide
 * (`migrate` + `db:seed`) n'écrit pas.
 *
 * Ce que ces tests fixent : les trois routes portent le garde ; sur une base
 * installée — par le drapeau ou parce qu'elle porte des utilisateurs — les deux
 * actions répondent 404 et **rien ne bouge** ; sur une base vierge, l'écran
 * d'installation reste joignable (le flux du socle survit).
 */
class InstallerLockTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const ROUTES = ['install', 'installing', 'finish'];

    /** Ce qu'une installation réussie aurait détruit : le nombre de tables et d'utilisateurs. */
    private function empreinte(): array
    {
        return [count(Schema::getAllTables()), User::count(), DB::table('general_settings')->count()];
    }

    public function test_the_three_installer_routes_carry_the_guard(): void
    {
        $vues = [];
        foreach (Route::getRoutes() as $route) {
            if (in_array($route->uri(), self::ROUTES, true)) {
                $vues[$route->uri()] = $route->gatherMiddleware();
            }
        }

        $this->assertEqualsCanonicalizing(self::ROUTES, array_keys($vues), 'les trois routes de l\'installateur sont montées');
        foreach ($vues as $uri => $middleware) {
            $this->assertContains('IsNotInstalled', $middleware, "/{$uri} : sans le garde, la route est joignable sur une base installée");
        }
    }

    public function test_on_an_installed_base_the_two_writing_actions_do_not_exist_and_nothing_moves(): void
    {
        $this->seedTenant();
        config(['app.app_installed' => 'yes']);
        $avant = $this->empreinte();

        $this->get('/finish?user_name=Pirate&email=pirate@example.test&login_password=secret')->assertNotFound();
        $this->post('/installing', ['host' => '127.0.0.1', 'dbname' => 'x', 'dbuser' => 'x', 'dbpassword' => 'x'])->assertNotFound();
        $this->get('/install')->assertRedirect('/');

        $this->assertSame($avant, $this->empreinte(), 'la base a bougé : l\'installateur a été joué');
        $this->assertNull(User::where('email', 'pirate@example.test')->first());
    }

    /** Le drapeau n'est pas la seule serrure : une base qui porte des utilisateurs est installée. */
    public function test_without_the_flag_a_populated_base_is_still_installed(): void
    {
        $this->seedTenant();
        config(['app.app_installed' => null]);
        $avant = $this->empreinte();

        $this->get('/finish')->assertNotFound();
        $this->post('/installing', [])->assertNotFound();
        $this->get('/install')->assertForbidden()->assertSee('APP_INSTALLED=yes');

        $this->assertSame($avant, $this->empreinte());
    }

    /** Le flux du socle survit : sur une base vierge, l'écran d'installation répond. */
    public function test_on_an_empty_base_the_installer_screen_is_reachable(): void
    {
        config(['app.app_installed' => null]);
        $this->assertSame(0, User::count(), 'fixture : base vierge');

        // La vue du socle lit `$_SERVER['HTTP_HOST']` et `SCRIPT_NAME` directement,
        // pas la requête : un test les pose comme nginx le ferait.
        $serveur = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        try {
            $this->get('/install')->assertOk()->assertSee('installing');
        } finally {
            $_SERVER = $serveur;
        }
    }
}
