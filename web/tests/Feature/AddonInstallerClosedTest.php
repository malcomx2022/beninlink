<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S124** — l'installateur de modules du socle n'est plus routé.
 *
 * `AddonController::store()` dépliait une archive téléversée, copiait ses fichiers dans
 * `base_path()` et exécutait son `sql/update.sql` par `DB::unprepared`. Ses six routes vivaient
 * sous `admin/` avec la seule garde `panel:back-office` : tout compte du back-office de tout
 * transporteur, sans aucun droit, pouvait installer du code sur la plateforme commune. Aucun menu
 * ne les liait, et BeninLink se déploie par git. Le contrôleur reste (code du socle neutralisé,
 * jamais effacé) ; aucune route ne l'atteint.
 */
class AddonInstallerClosedTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    public function test_no_route_reaches_the_addon_controller(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();

        $atteintes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_contains((string) $route->getActionName(), 'AddonController')
                || str_contains($route->uri(), 'addons'))
            ->map(fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->values()->all();

        $this->assertSame([], $atteintes, 'une route atteint de nouveau l\'installateur de modules');
    }

    public function test_a_back_office_account_gets_a_404_on_the_old_addresses(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $this->actingAs(\App\Models\User::where('user_type', \App\Enums\UserType::ADMIN)->firstOrFail());

        $this->get(self::HOTE . '/admin/addons')->assertNotFound();
        $this->post(self::HOTE . '/admin/addons', [])->assertNotFound();
        $this->post(self::HOTE . '/admin/addons/activation', ['id' => 1])->assertNotFound();
    }
}
