<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Install\SeedAccounts;
use Database\Seeders\DeliveryManSeeder;
use Database\Seeders\DeliverycategorySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\HubSeeder;
use Database\Seeders\MerchantSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\Backend\SuperAdmin\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UploadSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S87** — les comptes d'amorçage n'ont plus de mot de passe public en production.
 *
 * Cinq semences du socle créent des comptes au mot de passe `12345678`, écrit
 * dans le code source que toutes les installations We Courier partagent. Le
 * guide de mise en service demandait de « les changer avant que le site soit
 * joignable » — une consigne, rien ne la mesurait, et `db:seed --force` est la
 * deuxième commande de la première installation.
 *
 * Deux choses sont fixées ici :
 *
 *  1. hors `local` et `testing`, les semences **tirent** un mot de passe par
 *     compte et l'affichent **une fois** ; en `testing`, le socle est inchangé
 *     (la suite et le jeu pilote n'ont rien à apprendre) ;
 *  2. `beninlink:comptes-amorcage`, que `deploy.sh` exécute **avant** de couper
 *     le site, sort en erreur tant qu'un compte d'amorçage porte le mot de passe
 *     public, et en succès dès qu'ils sont changés ou supprimés.
 */
class SeedAccountsPasswordTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const DEPLOY = 'docs/guides/infra/deploy/deploy.sh';

    /** Ce que les trois semences de comptes attendent déjà en base. */
    private const PREREQUIS = [
        PermissionSeeder::class, GeneralSettingsSeeder::class, PlanSeeder::class, UploadSeeder::class,
        HubSeeder::class, DepartmentSeeder::class, DesignationSeeder::class, RoleSeeder::class,
        DeliverycategorySeeder::class,
    ];

    protected function tearDown(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');
        SeedAccounts::oublier();
        parent::tearDown();
    }

    /** La liste de `SeedAccounts` est bien celle des semences : cinq comptes, tous au mot de passe public en test. */
    public function test_in_testing_the_five_seeded_accounts_keep_the_public_password(): void
    {
        $this->seedTenant();
        $this->seed(DeliveryManSeeder::class);

        foreach (SeedAccounts::COMPTES as $email) {
            $compte = User::where('email', $email)->first();
            $this->assertNotNull($compte, "la semence crée bien {$email}");
            $this->assertTrue(Hash::check(SeedAccounts::MOT_DE_PASSE_PUBLIC, $compte->password),
                "{$email} : en test, le mot de passe du socle est conservé");
        }

        $this->assertSame([], SeedAccounts::tires(), 'rien n\'est tiré au sort en test');
    }

    public function test_outside_local_and_testing_each_seeded_account_gets_its_own_password_shown_once(): void
    {
        $this->seed(self::PREREQUIS);
        $this->app->detectEnvironment(fn () => 'production');
        SeedAccounts::oublier();

        foreach ([UserSeeder::class, MerchantSeeder::class, DeliveryManSeeder::class] as $semence) {
            $this->artisan('db:seed', ['--class' => $semence, '--force' => true])
                ->expectsOutputToContain('affiché une seule fois')
                ->assertExitCode(0);
        }

        $tires = SeedAccounts::tires();
        $this->assertEqualsCanonicalizing(SeedAccounts::COMPTES, array_keys($tires), 'un mot de passe tiré par compte d\'amorçage');
        $this->assertCount(count(SeedAccounts::COMPTES), array_unique($tires), 'tous distincts');

        foreach (SeedAccounts::COMPTES as $email) {
            $compte = User::where('email', $email)->firstOrFail();
            $this->assertFalse(Hash::check(SeedAccounts::MOT_DE_PASSE_PUBLIC, $compte->password), "{$email} garde le mot de passe public");
            $this->assertGreaterThanOrEqual(20, strlen($tires[$email]));
            $this->assertTrue(Hash::check($tires[$email], $compte->password), "{$email} : le mot de passe affiché est celui posé");
        }
    }

    public function test_the_check_fails_while_a_seeded_account_keeps_the_public_password(): void
    {
        $this->seedTenant();
        $this->seed(DeliveryManSeeder::class);

        $constat = $this->artisan('beninlink:comptes-amorcage');
        foreach (SeedAccounts::COMPTES as $email) {
            $constat->expectsOutputToContain($email);
        }
        // Une commande en attente ne s'exécute qu'à `run()` ou à sa destruction :
        // sans ce `run()`, elle tournerait APRÈS les changements ci-dessous.
        $constat->assertExitCode(1)->run();

        // Quatre changés, un oublié : c'est encore rouge, et il est nommé.
        foreach (array_slice(SeedAccounts::COMPTES, 1) as $email) {
            User::where('email', $email)->update(['password' => Hash::make(Str::password(20))]);
        }
        $this->artisan('beninlink:comptes-amorcage')
            ->expectsOutputToContain(SeedAccounts::COMPTES[0])
            ->doesntExpectOutputToContain(SeedAccounts::COMPTES[1])
            ->assertExitCode(1);
    }

    public function test_the_check_passes_once_the_accounts_are_changed_or_deleted(): void
    {
        $this->seedTenant();
        $this->seed(DeliveryManSeeder::class);

        User::where('email', SeedAccounts::COMPTES[0])->update(['password' => Hash::make(Str::password(20))]);
        User::whereIn('email', array_slice(SeedAccounts::COMPTES, 1))->delete();

        $this->artisan('beninlink:comptes-amorcage')
            ->expectsOutputToContain('Aucun compte d\'amorçage')
            ->assertExitCode(0);
    }

    /** Un compte renommé n'échappe pas au constat : c'est le mot de passe qui compte, pas le nom. */
    public function test_the_check_reads_the_password_not_the_name(): void
    {
        $this->seedTenant();
        User::where('email', SeedAccounts::COMPTES[0])->update(['name' => 'Direction BeninLink']);

        $this->artisan('beninlink:comptes-amorcage')
            ->expectsOutputToContain('Direction BeninLink')
            ->assertExitCode(1);
    }

    /** Après la mise à jour du code (la commande vit dans la version déployée — S88), avant de migrer. */
    public function test_deploy_sh_checks_the_seeded_accounts_once_the_code_is_updated_and_before_migrating(): void
    {
        $script = file_get_contents(dirname(base_path()) . '/' . self::DEPLOY);

        $constat = strpos($script, 'php artisan beninlink:comptes-amorcage');
        $caches = strpos($script, 'php artisan optimize:clear');
        $migration = strpos($script, 'php artisan migrate');

        $this->assertNotFalse($constat, 'deploy.sh exécute le constat des comptes d\'amorçage');
        $this->assertNotFalse($caches);
        $this->assertNotFalse($migration);
        $this->assertGreaterThan($caches, $constat, 'le constat suit `optimize:clear` : avant, le serveur exécute l\'ancien code, qui ne connaît pas la commande');
        $this->assertLessThan($migration, $constat, 'le constat précède la migration : une base refusée reste intacte');
    }
}
