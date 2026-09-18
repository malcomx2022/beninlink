<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\MapParcelController;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S28 — la carte des courses du livreur répondait **200 sans authentification**.
 *
 * `GET /deliveryMan/parcel/map/{id}/{lat}/{long}/{status}` était déclarée dans
 * `routes/web.php` **hors du groupe `auth`** — juste après la fermeture d'un
 * groupe, à la même indentation, ce qui la faisait passer pour dedans. Mesuré
 * avant correction, sa pile de middlewares s'arrêtait à la tenancy :
 *
 *     web, XSS, IsInstalled, PreventAccessFromCentralDomains,
 *     InitializeTenancyByDomain, CompanyActivationMiddleware
 *
 * Aucun `auth`, aucun `hasPermission`. Et `User::find($id)` était **nu** :
 * n'importe quel identifiant d'utilisateur, de n'importe quelle société.
 *
 * Ce que la page rendait : `parcel-map.blade.php` écrit `@json($mapParcels)`
 * dans le source, et la charge utile porte, pour chaque colis, le **nom, le
 * téléphone et l'adresse du client**, le nom, le téléphone et l'adresse du
 * marchand, le montant à encaisser et le numéro de suivi. Prouvé par appel HTTP
 * anonyme : **200**, avec le téléphone et l'adresse du client dans le corps.
 *
 * Ce sont les données personnelles des clients finals, celles d'un tiers qui n'a
 * jamais eu de compte chez nous — d'où la gravité, au-delà du cloisonnement.
 *
 * La route est retirée : aucune vue, aucun script ne l'appelait et elle ne
 * portait même pas de nom de route, donc la retirer ne retire l'accès à
 * personne. Le contrôleur et la vue restent (0 fichier supprimé du socle), et la
 * portée est posée dans le contrôleur : la remonter ne rouvrirait pas la fuite.
 *
 * Relevée en inventoriant les routes web à paramètre pour
 * `WebIsolationCoverageTest`, dont l'invariant « une route de locataire est
 * authentifiée » l'aurait attrapée le jour où elle a été écrite.
 */
class DeliverymanMapLeakTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const CHEMIN = '/deliveryMan/parcel/map/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    /** La porte est fermée : plus aucune route ne mène à ce contrôleur. */
    public function test_no_route_leads_to_the_map_controller_any_more(): void
    {
        $restantes = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if (Str::contains($route->getActionName(), 'MapParcelController')) {
                $restantes[] = implode('|', $route->methods()) . ' ' . $route->uri();
            }
        }

        $this->assertSame([], $restantes, "la carte des courses est de retour, et elle verse les données "
            . "personnelles des clients dans la page :\n - " . implode("\n - ", $restantes));
    }

    /**
     * Et l'URL qui répondait 200 répond 404. C'est l'appel HTTP qui avait servi
     * à constater la fuite, rejoué à l'envers.
     */
    public function test_the_url_that_answered_two_hundred_is_now_a_not_found(): void
    {
        $livreur = $this->livreurAvecUneCourse('0022997123456', 'Rue 12, Cotonou');

        $reponse = $this->get(self::HOTE . self::CHEMIN . "{$livreur->id}/6.36/2.42/4");

        $this->assertSame(404, $reponse->getStatusCode());
        $this->assertStringNotContainsString('0022997123456', $reponse->getContent());
        $this->assertStringNotContainsString('Rue 12, Cotonou', $reponse->getContent());
    }

    /**
     * ⚠️ Le test précédent ne suffit pas seul : un 404 peut venir de la tenancy
     * plutôt que de l'absence de route. On vérifie donc que l'hôte utilisé sert
     * bien les routes du locataire — sinon tout répondrait 404 et le test
     * passerait sans rien prouver.
     */
    public function test_the_host_used_really_serves_tenant_routes(): void
    {
        $reponse = $this->get(self::HOTE . '/tracking');

        $this->assertSame(200, $reponse->getStatusCode(),
            'l\'hôte du test ne sert pas les routes du locataire : le 404 ci-dessus ne prouverait rien');
    }

    /** La portée posée dans le contrôleur : un utilisateur d'une autre société. */
    public function test_the_controller_refuses_a_user_of_another_company(): void
    {
        $etranger = User::where('company_id', 2)->where('user_type', UserType::DELIVERYMAN)->first()
            ?? User::where('company_id', 2)->firstOrFail();

        $this->expectException(NotFoundHttpException::class);

        app(MapParcelController::class)->parcelMap($etranger->id, '6.36', '2.42', 4);
    }

    /**
     * Un utilisateur de la bonne société qui n'est pas livreur : le socle faisait
     * `User::find($id)->deliveryman->id`, donc une erreur fatale sur `null`.
     * C'est un 404 maintenant.
     */
    public function test_a_user_who_is_not_a_deliveryman_is_a_not_found_not_a_crash(): void
    {
        // Repère : les utilisateurs semés appartiennent à la société 2, et
        // `settings()` vaut la société 1 dans un test — le nôtre est à créer.
        $agent = new User();
        $agent->company_id = settings()->id;
        $agent->name = 'Agent S28';
        $agent->email = 'agent.s28@example.test';
        $agent->mobile = '0022997000888';
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        $this->expectException(NotFoundHttpException::class);

        app(MapParcelController::class)->parcelMap($agent->id, '6.36', '2.42', 4);
    }

    /** Et le chemin normal répond toujours, pour son propre livreur. */
    public function test_its_own_deliveryman_is_still_served(): void
    {
        $livreur = $this->livreurAvecUneCourse('0022997000777', 'Rue 5, Porto-Novo');

        $vue = app(MapParcelController::class)->parcelMap($livreur->id, '6.36', '2.42', 4);

        $this->assertNotEmpty($vue->getData()['mapParcels']);
    }

    /**
     * Ce que l'écran exposait, noir sur blanc. Ce test ne protège pas un
     * comportement : il tient l'ENJEU, pour qu'on ne remonte pas la route en
     * croyant qu'il ne s'agissait que d'une carte.
     */
    public function test_the_screen_really_pours_personal_data_into_the_page(): void
    {
        $vue = file_get_contents(resource_path('views/backend/deliveryman/parcel/parcel-map.blade.php'));

        $this->assertStringContainsString('@json($mapParcels)', $vue,
            'la vue verse la charge utile telle quelle dans le source de la page');

        $controleur = file_get_contents(app_path('Http/Controllers/MapParcelController.php'));

        foreach (['customer_name', 'customer_phone', 'customer_address', 'current_payable'] as $champ) {
            $this->assertStringContainsString($champ, $controleur,
                "la charge utile porte {$champ} : ce sont des données personnelles de clients finals");
        }
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function livreurAvecUneCourse(string $telephone, string $adresse): User
    {
        $utilisateur = new User();
        $utilisateur->company_id = settings()->id;
        $utilisateur->name = 'Livreur S28';
        $utilisateur->email = 'livreur.s28.' . $telephone . '@example.test';
        $utilisateur->mobile = $telephone;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::DELIVERYMAN;
        $utilisateur->save();

        $livreur = new DeliveryMan();
        $livreur->company_id = settings()->id;
        $livreur->user_id = $utilisateur->id;
        $livreur->save();

        $colis = new Parcel();
        $colis->company_id = settings()->id;
        $colis->merchant_id = Merchant::firstOrFail()->id;
        $colis->tracking_id = 'S28-' . $telephone;
        $colis->customer_name = 'Aicha Cliente';
        $colis->customer_phone = $telephone;
        $colis->customer_address = $adresse;
        $colis->customer_lat = '6.36';
        $colis->customer_long = '2.42';
        $colis->status = 4;
        $colis->cash_collection = 10000;
        $colis->current_payable = 9000;
        $colis->save();

        $evenement = new ParcelEvent();
        $evenement->parcel_id = $colis->id;
        $evenement->delivery_man_id = $livreur->id;
        $evenement->parcel_status = 4;
        $evenement->save();

        return $utilisateur;
    }
}
