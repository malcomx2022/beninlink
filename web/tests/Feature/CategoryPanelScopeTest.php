<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Categorys;
use App\Models\SuperAdminPermission;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **R6 (S75)** — le catalogue des catégories est réservé au super-administrateur.
 *
 * `categorys` n'a aucun `company_id` : c'est un catalogue de **plateforme**,
 * comme `currencies`. Ses six routes vivaient pourtant dans le groupe du
 * locataire, et le droit `category_*` était offert à ses administrateurs : une
 * société qui renommait une catégorie la renommait pour tout le monde. Même
 * décision que S55 pour les devises : sous `super-admin/`, derrière
 * `panel:super-admin` — la garantie vient de la **structure**, pas de la donnée.
 *
 * Ce qui ne bouge pas : les six catégories de **livraison** d'amorçage
 * (`Deliverycategory`, D4), qui sont un autre catalogue.
 */
class CategoryPanelScopeTest extends TestCase
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

        Categorys::forceCreate(['id' => 1, 'name' => 'Électronique', 'slug' => 'electronique', 'description' => 'd']);
    }

    private function champs(): array
    {
        return ['id' => 1, 'name' => 'Renommée', 'slug' => 'renommee', 'description' => 'x'];
    }

    private function agent(int $type, array $droits): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent R6';
        $u->email = 'agent.r6.' . $type . '.' . User::count() . '@example.test';
        $u->mobile = '00229971' . $type . str_pad((string) User::count(), 4, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = $type;
        $u->permissions = $droits;
        $u->save();

        return $u;
    }

    /** Le critère du porteur : un administrateur de société reçoit 403, même avec le droit en poche. */
    public function test_un_administrateur_de_societe_recoit_403_sur_une_categorie_partagee(): void
    {
        $tousLesDroits = (new \ReflectionMethod(RoleSeeder::class, 'AdminPermissions'))->invoke(new RoleSeeder());
        $this->assertNotContains('category_update', $tousLesDroits, 'le droit a quitté la liste du locataire (R6)');

        $agent = $this->agent(UserType::ADMIN, array_merge($tousLesDroits, ['category_update']));

        $this->actingAs($agent)
            ->put(self::HOTE . '/super-admin/category/update', $this->champs())
            ->assertForbidden();

        $this->assertSame('Électronique', Categorys::find(1)->name, 'rien n’a été écrit');
    }

    public function test_un_marchand_est_refuse_aussi(): void
    {
        $this->actingAs($this->agent(UserType::MERCHANT, ['category_update']))
            ->put(self::HOTE . '/super-admin/category/update', $this->champs())
            ->assertForbidden();
    }

    public function test_le_super_administrateur_atteint_toujours_le_catalogue(): void
    {
        $this->actingAs($this->agent(UserType::SUPER_ADMIN, ['category_read', 'category_update']))
            ->put(self::HOTE . '/super-admin/category/update', $this->champs())
            ->assertRedirect(route('category.index'));

        $this->assertSame('Renommée', Categorys::find(1)->name);
    }

    public function test_l_ancienne_uri_du_locataire_n_existe_plus(): void
    {
        $this->actingAs($this->agent(UserType::ADMIN, ['category_update']))
            ->put(self::HOTE . '/category/update', $this->champs())
            ->assertNotFound();
    }

    /** Le droit a changé de camp dans les semences, et la migration le porte aux super-admins existants. */
    public function test_le_droit_est_offert_au_super_administrateur_et_plus_au_locataire(): void
    {
        $this->assertTrue(SuperAdminPermission::where('attribute', 'category')->exists());
        $this->assertContains('category_update', User::where('user_type', UserType::SUPER_ADMIN)->firstOrFail()->permissions);
        $this->assertNotContains('category_update', User::where('user_type', UserType::ADMIN)->firstOrFail()->permissions);
    }

    /** Les six catégories de livraison d'amorçage, elles, restent (R6, seconde moitié). */
    public function test_les_six_categories_de_livraison_d_amorcage_restent(): void
    {
        $this->assertSame(6, \App\Models\Backend\Deliverycategory::whereNull('company_id')->count());
    }
}
