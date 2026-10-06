<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S104 — un compte livreur n'entre pas au back-office web, et il l'apprend.
 *
 * Mesuré avant le correctif, sur le site d'une société : un livreur qui tape
 * son e-mail et son mot de passe sur `/login` est **accepté** (302 vers
 * `/dashboard`), puis `/dashboard` lui répond **500** — `backend.dashboard`
 * fait `in_array()` sur ses droits, qui n'existent pas. Un refus annoncé comme
 * une panne, la famille de S37. Le livreur, lui, n'a aucun écran web : son
 * outil est l'app livreur (S41 l'avait mesuré, S85 le tient en contrat).
 *
 * Le lot ferme les deux portes : la connexion web refuse un compte livreur
 * avec le mot qui dit où aller, et `/dashboard` répond 403 à une session
 * livreur qui existerait encore. Il retire aussi huit routes « Theme Pages »
 * du socle qui rendaient des vues inexistantes (500 pour tout compte).
 */
class CourierWebAccessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const THEME = [
        'dashboard.finance', 'dashboard.influencer', 'dashboard.sales',
        'ecommerce.product.checkout', 'ecommerce.product.single', 'ecommerce.product',
        'influencer.finder', 'influencer.profile',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    public function test_the_web_login_refuses_a_courier_account_and_says_where_to_go(): void
    {
        $this->livreur();

        $reponse = $this->from(self::HOTE . '/login')
            ->post(self::HOTE . '/login', ['email' => 'livreur.web@example.test', 'password' => 'secret']);

        $reponse->assertRedirect(self::HOTE . '/login');
        $reponse->assertSessionHasErrors(['email' => __('auth.courier_app_only')]);
        $this->assertGuest();
        $this->assertStringContainsString('application livreur', __('auth.courier_app_only'),
            'le message dit au livreur où se connecter');
    }

    public function test_the_login_still_works_for_an_agent_by_the_same_form(): void
    {
        $this->agent();

        $this->post(self::HOTE . '/login', ['email' => 'agent.web@example.test', 'password' => 'secret'])
            ->assertRedirect(self::HOTE . '/dashboard');
        $this->assertAuthenticated();
    }

    public function test_a_courier_session_gets_a_refusal_on_the_dashboard_not_a_crash(): void
    {
        $this->actingAs($this->livreur())
            ->get(self::HOTE . '/dashboard')
            ->assertForbidden();
    }

    public function test_the_agent_dashboard_is_untouched(): void
    {
        $this->actingAs($this->agent())
            ->get(self::HOTE . '/dashboard')
            ->assertOk();
    }

    /**
     * Les huit « Theme Pages » rendaient `view('theme.…')` : aucun fichier sous
     * `resources/views/theme/`, aucune vue ni script ne nommait ces routes.
     */
    public function test_the_eight_theme_routes_are_gone_and_nothing_names_them(): void
    {
        $noms = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->getName())->filter()->all();
        foreach (self::THEME as $nom) {
            $this->assertNotContains($nom, $noms, "la route de thème `$nom` est revenue");
        }
        $this->assertDirectoryDoesNotExist(resource_path('views/theme'),
            'si des vues de thème apparaissent, les routes se discutent à nouveau');

        $sources = '';
        foreach ((new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')))) as $f) {
            if ($f->isFile()) {
                $sources .= file_get_contents($f->getPathname());
            }
        }
        foreach (self::THEME as $nom) {
            $this->assertStringNotContainsString("route('$nom')", $sources, "une vue nomme `$nom`");
        }
    }

    private function livreur(): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Livreur web';
        $u->email = 'livreur.web@example.test';
        $u->mobile = '0022997920001';
        $u->password = bcrypt('secret');
        $u->user_type = UserType::DELIVERYMAN;
        $u->unique_id = 'L-WEB-1';
        $u->status = Status::ACTIVE;
        $u->save();

        DeliveryMan::forceCreate([
            'company_id' => settings()->id, 'user_id' => $u->id, 'status' => Status::ACTIVE,
            'delivery_charge' => 500, 'pickup_charge' => 200, 'return_charge' => 300,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);

        return $u;
    }

    private function agent(): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent web';
        $u->email = 'agent.web@example.test';
        $u->mobile = '0022997920002';
        $u->password = bcrypt('secret');
        $u->user_type = UserType::ADMIN;
        $u->role_id = Role::where('company_id', settings()->id)->value('id');
        $u->permissions = [];
        $u->status = Status::ACTIVE;
        $u->save();

        return $u;
    }
}
