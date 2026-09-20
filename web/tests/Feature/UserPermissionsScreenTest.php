<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Role;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S35, complément — l'écran des droits, prouvé **par un appel HTTP**.
 *
 * `UserAndSettingsScopeTest` prouve le périmètre de `UserRepository::get()`, et
 * `UserController::permission()` l'appelle désormais. Mais la preuve s'arrête au
 * **dépôt** : rien n'exerce la route elle-même.
 *
 * C'est exactement la leçon que le filet inscrit sous le nom **F6** — « une
 * déclaration qui pointe un test incapable de toucher la route ne prouve rien ».
 * `GET admin/users/permissions/{id}` est déclarée prouvée ; ce fichier la touche.
 *
 * Ce que l'appel ajoute au test de dépôt, et qu'aucune assertion sur `get()` ne
 * peut donner : l'`abort_if` du contrôleur est bien **atteint**, et la chaîne
 * complète — hôte de locataire, `auth`, permission `permission_update`,
 * abonnement — laisse passer la requête jusqu'à lui.
 *
 * Le sabotage le mesure : garde retiré, l'écran ne rend pas une erreur, il rend
 * **200 avec la page de l'agent d'en face**. La différence compte — un 500 se
 * voit dans les journaux, un 200 ne se voit nulle part.
 *
 * ⚠️ **La fixture qui rend ce test possible, et le piège qu'elle évite.** Sans
 * abonnement en cours, `subscriptionCheckMiddleware` renvoie **302 vers
 * `/subscription`** avant d'atteindre le contrôleur. Un test qui attend un refus
 * lirait ce 302 comme une preuve et passerait **sans jamais exécuter la ligne
 * qu'il prétend couvrir** — y compris avec le garde retiré. C'est pourquoi le
 * contrôle négatif ci-dessous attend un **200**, et pas seulement « autre chose
 * qu'un 404 » : c'est lui qui atteste que la requête va au bout.
 */
class UserPermissionsScreenTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->abonnementActifPour(settings()->id);
    }

    public function test_the_permissions_screen_of_another_companys_agent_answers_404(): void
    {
        $monAgent = $this->agentDe(settings()->id, 'mien');
        $sonAgent = $this->agentDe(self::AUTRE, 'sien');

        $this->actingAs($this->agentDe(settings()->id, 'connecte'));

        $this->get(self::HOTE . '/admin/users/permissions/' . $sonAgent->id)
            ->assertNotFound();

        // Contrôle négatif : sur mon agent, l'écran s'ouvre vraiment. Sans cette
        // moitié, un 404 rendu par la tenancy ou par une permission manquante
        // passerait pour la preuve du périmètre.
        $this->get(self::HOTE . '/admin/users/permissions/' . $monAgent->id)
            ->assertOk();
    }

    /**
     * Le même écran pour un compte qui n'est **pas** un agent de back-office :
     * le périmètre du dépôt filtre sur `user_type` autant que sur la société.
     * Un marchand de ma propre société n'a donc pas de fiche d'agent.
     */
    public function test_the_permissions_screen_refuses_a_non_agent_of_my_own_company(): void
    {
        $marchand = $this->agentDe(settings()->id, 'marchand');
        $marchand->user_type = UserType::MERCHANT;
        $marchand->save();

        $this->actingAs($this->agentDe(settings()->id, 'connecte2'));

        $this->get(self::HOTE . '/admin/users/permissions/' . $marchand->id)
            ->assertNotFound();
    }

    private function agentDe(int $societe, string $suffixe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent ' . $suffixe;
        $agent->email = 'agent.' . $suffixe . '.' . $societe . '@example.test';
        $agent->mobile = '0022994' . $societe . str_pad((string) strlen($suffixe), 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->role_id = Role::where('company_id', $societe)->value('id');
        $agent->permissions = ['permission_update', 'user_read', 'user_update'];
        $agent->save();

        return $agent;
    }

    /** La porte SaaS : le back-office redirige tant que la société n'a pas de plan en cours. */
    private function abonnementActifPour(int $societe): void
    {
        Subscription::forceCreate([
            'company_id' => $societe,
            'plan_id' => Plan::value('id'),
            'price' => 0,
            'start_date' => now()->subDay(),
            'expired_date' => now()->addYear(),
        ]);
    }
}
