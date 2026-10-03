<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **R5 (S75)** — le menu Réglages s'affiche dès qu'UNE de ses entrées est accessible.
 *
 * La garde du menu ne listait que cinq droits sur les quinze que ses entrées
 * lisent : un agent à qui l'on n'accordait que `general_settings_read` ne
 * voyait pas le menu (charte-web §13.7, signalé le 2026-09-18, tranché le
 * 2026-10-03). La règle devient : **visibilité en OU** sur toutes les entrées.
 *
 * Et le risque nommé par le porteur : élargir la visibilité **sans** assouplir
 * les gardes d'écriture. Les deux derniers tests le tiennent.
 */
class SettingsMenuGuardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const SIDEBAR = 'resources/views/backend/partials/sidebar.blade.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    /** @return string[] les droits lus par `hasPermission('…')` dans un fragment */
    private function droits(string $fragment): array
    {
        preg_match_all("/hasPermission\('([^']+)'\)/", $fragment, $m);
        $droits = array_values(array_unique($m[1]));
        sort($droits);

        return $droits;
    }

    /**
     * La garde du menu lit exactement les droits que ses entrées lisent — ni
     * plus (elle ouvrirait un menu vide), ni moins (le défaut de §13.7).
     */
    public function test_la_garde_du_menu_liste_tous_les_droits_de_ses_entrees_et_eux_seuls(): void
    {
        $source = file_get_contents(base_path(self::SIDEBAR));
        $marqueur = '<!---for setting--->';
        $this->assertStringContainsString($marqueur, $source);

        [$avant, $apres] = explode($marqueur, $source, 2);
        // La garde : le dernier `@if (` avant le marqueur.
        $garde = substr($avant, strrpos($avant, '@if ('));
        // Le sous-menu : jusqu'à la fermeture de sa liste.
        $sousMenu = substr($apres, 0, strpos($apres, '</ul>'));

        $this->assertNotEmpty($this->droits($sousMenu));
        $this->assertSame($this->droits($sousMenu), $this->droits($garde),
            'la garde du menu Réglages et les droits lus par ses entrées ne disent pas la même chose (R5)');
        $this->assertContains('general_settings_read', $this->droits($garde), 'le défaut de §13.7 : ce droit seul doit ouvrir le menu');
    }

    private function agent(array $droits): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent R5';
        $u->email = 'agent.r5.' . count($droits) . '@example.test';
        $u->mobile = '00229970' . str_pad((string) User::count(), 5, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::ADMIN;
        $u->permissions = $droits;
        $u->save();

        return $u;
    }

    public function test_un_agent_avec_le_seul_droit_de_lecture_des_reglages_voit_le_menu(): void
    {
        $agent = $this->agent(['dashboard_read', 'general_settings_read']);

        $this->actingAs($agent)->get(self::HOTE . '/admin/general-settings/index')
            ->assertOk()
            ->assertSee(route('general-settings.index'), false);
    }

    public function test_un_agent_sans_aucun_droit_de_reglages_ne_voit_pas_le_menu(): void
    {
        $agent = $this->agent(['dashboard_read', 'parcel_read']);

        $reponse = $this->actingAs($agent)->get(self::HOTE . '/dashboard')->assertOk();
        $reponse->assertDontSee(route('general-settings.index'), false);
        $reponse->assertDontSee('<!---for setting--->', false);
    }

    /** Le risque de R5 : voir le menu n'est pas pouvoir écrire. */
    public function test_le_meme_agent_ne_peut_toujours_pas_modifier_les_reglages(): void
    {
        $agent = $this->agent(['dashboard_read', 'general_settings_read']);

        $this->actingAs($agent)
            ->put(self::HOTE . '/admin/general-settings/update', ['name' => 'Tentative'], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    /** Et les gardes d'écriture des routes n'ont pas bougé d'une ligne. */
    public function test_les_gardes_d_ecriture_des_routes_de_reglages_sont_intactes(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("'general-settings.update')->middleware('hasPermission:general_settings_update')", preg_replace('/\s+/', '', $routes) !== '' ? $routes : '',
            'la garde d’écriture des réglages généraux a changé : R5 ne touche qu’à la visibilité du menu');
    }
}
