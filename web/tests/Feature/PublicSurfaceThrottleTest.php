<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S131** — la surface publique, sans compte, a une cadence bornée.
 *
 * Le suivi d'un colis montre les téléphones du marchand et des livreurs, et un numéro de suivi n'est
 * qu'un préfixe et huit chiffres : sans borne, on les énumérait. Les formulaires de contact et de
 * lettre d'information envoient un courriel, l'inscription marchand un SMS : sans borne, chacun servait
 * à faire envoyer en masse aux frais du transporteur.
 */
class PublicSurfaceThrottleTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const WEB = ['tracking.index', 'subscribe.store', 'contact.message.send', 'merchant.sign-up-store'];

    private const API = ['GET api/v10/parcel/tracking/{tracking_id}', 'POST api/v10/contact-us', 'POST api/v10/subscribe'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        Cache::flush();
    }

    public function test_every_public_entry_carries_a_limiter(): void
    {
        foreach (self::WEB as $nom) {
            $this->assertContains('throttle:public-web', Route::getRoutes()->getByName($nom)->gatherMiddleware(), "{$nom} sans limite");
        }

        $api = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($route->methods() as $verbe) {
                $api[$verbe . ' ' . $route->uri()] = $route;
            }
        }
        foreach (self::API as $cle) {
            $this->assertArrayHasKey($cle, $api, "{$cle} : route introuvable");
            $this->assertContains('throttle:public-api', $api[$cle]->gatherMiddleware(), "{$cle} sans limite");
        }
    }

    public function test_the_twenty_first_tracking_lookup_from_one_address_is_refused(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get(self::HOTE . '/tracking?tracking_id=BL' . (10000000 + $i));
            $this->assertStringNotContainsString('Trop de tentatives', (string) session('warning'), 'consultation ' . ($i + 1) . ' déjà refusée');
        }

        $this->get(self::HOTE . '/tracking?tracking_id=BL10000099')->assertRedirect();
        $this->assertStringContainsString('Trop de tentatives', (string) session('warning'));
    }
}
