<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * S7 — le filet. Le socle n'a aucun garde-fou framework contre une lecture
 * `find($id)` nue : l'isolation repose sur chaque repository. Ce test rend
 * l'oubli impossible à ignorer : **toute route `/api/v10` portant un
 * paramètre doit être inscrite ici**, avec le test qui prouve qu'un compte
 * n'atteint pas la ressource d'un autre, ou la raison pour laquelle le
 * paramètre n'est pas un identifiant de ressource.
 *
 * Ajouter une route à identifiant sans l'inscrire fait échouer la suite.
 */
class IsolationCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** « METHOD chemin » → test qui couvre l'isolation, ou motif d'exemption. */
    private const COVERAGE = [
        // Colis du marchand (S17)
        'GET parcel/details/{id}' => ParcelScopeTest::class,
        'GET parcel/edit/{id}' => ParcelScopeTest::class,
        'PUT parcel/update/{id}' => ParcelScopeTest::class,
        'GET parcel/logs/{id}' => ParcelScopeTest::class,
        'GET parcel/{id}/status/{statusId}' => ParcelScopeTest::class,
        'DELETE parcel/delete/{id}' => ParcelScopeTest::class,
        // Boutiques (S18)
        'GET shops/edit/{id}' => ShopScopeTest::class,
        'PUT shops/update/{id}' => ShopScopeTest::class,
        'DELETE shops/delete/{id}' => ShopScopeTest::class,
        // Factures (S14, chantier 4)
        'GET invoice-details/{id}' => InvoiceScopeTest::class,
        'GET invoice-pdf-link/{id}' => SettlementStatementTest::class,
        // Argent (S19, S7)
        'GET payment-account/edit/{id}' => TenantIsolationTest::class,
        'DELETE payment-account/delete/{id}' => TenantIsolationTest::class,
        'GET payment-request/edit/{id}' => TenantIsolationTest::class,
        'PUT payment-request/update/{id}' => TenantIsolationTest::class,
        'DELETE payment-request/delete/{id}' => TenantIsolationTest::class,
        // Relation (S7)
        'GET fraud/edit/{id}' => TenantIsolationTest::class,
        'PUT fraud/update/{id}' => TenantIsolationTest::class,
        'DELETE fraud/delete/{id}' => TenantIsolationTest::class,
        'GET support/edit/{id}' => TenantIsolationTest::class,
        'PUT support/update/{id}' => TenantIsolationTest::class,
        'DELETE support/delete/{id}' => TenantIsolationTest::class,
        'GET support/view/{id}' => TenantIsolationTest::class,
        // Douane, notifications, FedaPay (scopés à la création)
        'PUT customs/alerts/{id}/resolve' => CustomsAlertTest::class,
        'PUT notifications/{id}/read' => MerchantNotificationTest::class,
        'GET fedapay/status/{reference}' => FedaPayWebhookTest::class,
        // Livreur (S7)
        'GET deliveryman/parcel/details/{id}' => TenantIsolationTest::class,
        'POST deliveryman/parcel/delivered/{id}' => TenantIsolationTest::class,
        'POST deliveryman/parcel/partial-delivered/{id}' => TenantIsolationTest::class,
        // Paramètres qui ne désignent pas une ressource
        'GET status-wise/parcel/list/{status}' => 'un statut, pas un identifiant ; la liste est scopée par marchand',
        'GET parcel/tracking/{tracking_id}' => 'suivi public par numéro de suivi, sans compte : c\'est sa raison d\'être',
    ];

    public function test_every_api_route_with_a_parameter_declares_its_isolation(): void
    {
        $prefix = 'api/v10/';
        $routes = array_filter(
            Router::getRoutes()->getRoutes(),
            fn (Route $r) => Str::startsWith($r->uri(), $prefix) && $r->parameterNames() !== []
        );
        $this->assertNotEmpty($routes);

        $missing = [];
        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $key = $method . ' ' . Str::after($route->uri(), $prefix);
                if (!array_key_exists($key, self::COVERAGE)) {
                    $missing[] = $key;
                }
            }
        }

        $this->assertSame([], $missing, "Routes à identifiant sans test d'isolation déclaré :\n - " . implode("\n - ", $missing));
    }

    public function test_declared_coverage_points_to_existing_tests_and_routes(): void
    {
        $known = [];
        foreach (Router::getRoutes()->getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                $known[$method . ' ' . Str::after($route->uri(), 'api/v10/')] = true;
            }
        }

        foreach (self::COVERAGE as $key => $covering) {
            $this->assertArrayHasKey($key, $known, "Entrée de couverture sans route : {$key}");
            if (Str::endsWith($covering, 'Test')) {
                $this->assertTrue(class_exists($covering), "Test introuvable pour {$key} : {$covering}");
            }
        }
    }
}
