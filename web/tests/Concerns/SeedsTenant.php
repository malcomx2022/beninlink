<?php

namespace Tests\Concerns;

use Database\Seeders\Backend\SuperAdmin\PlanSeeder;
use Database\Seeders\ConfigSeeder;
use Database\Seeders\DeliveryChargeSeeder;
use Database\Seeders\DeliverycategorySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\HubSeeder;
use Database\Seeders\MerchantSeeder;
use Database\Seeders\MerchantshopsSeeder;
use Database\Seeders\PackagingSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UploadSeeder;
use Database\Seeders\UserSeeder;

/**
 * Installe le locataire minimal dont un test a besoin : societe, hub, roles,
 * utilisateurs, categories, bareme, un marchand et sa boutique.
 *
 * On n'appelle PAS `DatabaseSeeder` en entier : `CurrencySeeder` envoie du SQL
 * MySQL brut que SQLite refuse. Cette liste est son prefixe utile, dans le meme
 * ordre — les dependances de cles etrangeres en dependent (les plans avant les
 * utilisateurs, les uploads avant les marchands).
 */
trait SeedsTenant
{
    protected function seedTenant(): void
    {
        $this->seed([
            PermissionSeeder::class,
            GeneralSettingsSeeder::class,
            PlanSeeder::class,
            UploadSeeder::class,
            HubSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            DeliverycategorySeeder::class,
            DeliveryChargeSeeder::class,
            MerchantSeeder::class,
            MerchantshopsSeeder::class,
            ConfigSeeder::class,
            PackagingSeeder::class,
        ]);
    }
}
