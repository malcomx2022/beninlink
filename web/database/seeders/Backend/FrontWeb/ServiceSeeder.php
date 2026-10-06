<?php

namespace Database\Seeders\Backend\FrontWeb;

use Database\Seeders\CompanyFrontendDataSeeder;
use Illuminate\Database\Seeder;

/**
 * Vitrine de la **société 1** (la plateforme). Depuis **S93** le contenu vit dans
 * `CompanyFrontendDataSeeder::services()`, la même source que la vitrine de chaque
 * société créée par le super-admin : une seule version, en français.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        (new CompanyFrontendDataSeeder)->services(1);
    }
}
