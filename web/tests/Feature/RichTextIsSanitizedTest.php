<?php

namespace Tests\Feature;

use App\Models\Backend\Department;
use App\Models\Backend\Support;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S127** — un texte riche écrit par un autre que le lecteur est nettoyé avant d'être rendu.
 *
 * Un marchand écrivait `<img src=x onerror=…>` dans la description d'un ticket de support (le
 * middleware `XSS` exempte `description`, et l'API des apps ne passe pas par lui) : le back-office
 * la rendait par `{!! !!}`, le script tournait dans la session de l'agent.
 */
class RichTextIsSanitizedTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const PIEGE = '<p><strong>Colis abîmé</strong></p><img src=x onerror="alert(document.cookie)">'
        . '<script>alert(1)</script><a href="javascript:alert(2)">lien</a><a href="https://beninlink.app">ok</a>';

    public function test_the_cleaner_keeps_formatting_and_drops_scripts(): void
    {
        $propre = SafeHtml::clean(self::PIEGE);

        $this->assertStringContainsString('<strong>Colis abîmé</strong>', $propre);
        $this->assertStringContainsString('href="https://beninlink.app"', $propre);
        foreach (['onerror', '<script', 'javascript:', 'alert('] as $danger) {
            $this->assertStringNotContainsString($danger, $propre);
        }
        $this->assertSame('', SafeHtml::clean(null));
    }

    public function test_a_merchant_ticket_cannot_run_a_script_in_the_back_office(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        // L'hôte de test sert la société de `settings()` : le ticket est écrit par un compte de cette société.
        $societe = settings()->id;
        $auteur = new \App\Models\User();
        $auteur->forceFill([
            'company_id' => $societe, 'name' => 'PME pilote', 'email' => 'pme.piege@example.test', 'mobile' => '0022997999002',
            'password' => bcrypt('secret'), 'user_type' => \App\Enums\UserType::MERCHANT,
        ])->save();
        $departement = Department::query()->first() ?? tap(new Department(), function ($d) use ($societe) {
            $d->forceFill(['title' => 'Support', 'company_id' => $societe, 'status' => 1])->save();
        });
        $ticket = new Support();
        $ticket->forceFill([
            'user_id' => $auteur->id, 'department_id' => $departement->id,
            'service' => 'parcel', 'priority' => 'high', 'subject' => 'Test', 'description' => self::PIEGE,
            'date' => '2026-10-08',
        ])->save();

        $agent = new \App\Models\User();
        $agent->forceFill([
            'company_id' => $societe, 'name' => 'Agent support', 'email' => 'agent.support@example.test',
            'mobile' => '0022997999001', 'password' => bcrypt('secret'), 'user_type' => \App\Enums\UserType::ADMIN,
            'role_id' => \App\Models\Backend\Role::where('company_id', $societe)->value('id'), 'permissions' => ['support_read'],
        ])->save();
        $page = $this->actingAs($agent)->get(self::HOTE . '/admin/support/view/' . $ticket->id)->assertOk()->getContent();

        $this->assertStringContainsString('<strong>Colis abîmé</strong>', $page);
        $this->assertStringNotContainsString('onerror="alert(document.cookie)"', $page);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $page);
    }

    /** Aucune vue ne rend brut un champ de texte libre : description, message, réponse de FAQ. */
    public function test_no_view_renders_free_text_raw(): void
    {
        $bruts = [];
        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php')->notPath('installer') as $vue) {
            if (preg_match_all('/\{!!\s*@?\$[\w>\-\[\]\'"]*(description|message|answer|details|note)\b[^!]*!!\}/', $vue->getContents(), $m)) {
                foreach ($m[0] as $echo) {
                    $bruts[] = $vue->getRelativePathname() . ' : ' . $echo;
                }
            }
        }
        $this->assertSame([], $bruts, "texte libre rendu sans safeHtml() :\n" . implode("\n", $bruts));
    }
}
