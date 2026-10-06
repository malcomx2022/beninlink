<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **R3 (S75)** — pas de prorata au renouvellement, et l'acheteur le sait AVANT.
 *
 * `switchPlan()` repart de `now()` : renouveler avant l'échéance perd le
 * reliquat. Le porteur garde cette règle (aucun calcul de crédit, aucune
 * migration) et demande qu'elle soit **dite** sur les deux écrans de changement
 * de plan — celui du locataire et celui du super-administrateur — avant la
 * confirmation. C'est tout ce que ce fichier vérifie.
 */
class PlanSwitchNoticeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    public function test_le_locataire_lit_la_regle_sur_la_page_des_plans_avant_de_payer(): void
    {
        $admin = User::where('user_type', UserType::ADMIN)->firstOrFail();

        $this->actingAs($admin)->get(self::HOTE . '/subscription')
            ->assertOk()
            ->assertSee(__('levels.plan_switch_notice'))
            ->assertSee('plan-switch-notice', false);
    }

    public function test_le_super_administrateur_lit_la_meme_regle_avant_d_enregistrer(): void
    {
        $superAdmin = User::where('user_type', UserType::SUPER_ADMIN)->firstOrFail();
        $admin = User::where('user_type', UserType::ADMIN)->firstOrFail();

        $this->actingAs($superAdmin)->get(self::HOTE . '/super-admin/company/subscription/switch/' . $admin->id)
            ->assertOk()
            ->assertSee(__('levels.plan_switch_notice'));
    }

    /** La règle est dite, pas changée : le reliquat n'est toujours pas calculé. */
    public function test_switch_plan_repart_toujours_de_la_date_du_jour(): void
    {
        $source = file_get_contents(app_path('Repositories/Superadmin/Company/CompanyRepository.php'));
        $this->assertStringContainsString("Carbon::now()->addDays(\$plan->days_count)", $source,
            'R3 : pas de prorata — si l’échéance se calcule autrement, c’est une décision à reprendre avec le porteur');
    }
}
