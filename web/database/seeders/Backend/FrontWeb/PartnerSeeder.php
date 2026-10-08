<?php

namespace Database\Seeders\Backend\FrontWeb;

use Illuminate\Database\Seeder;

/**
 * Partenaires de la **société 1** (la plateforme) : **aucun** depuis **S111**.
 *
 * Les semences du socle posaient six logos de vraies marques (Huawei, UPS, Digg,
 * Atom, 500px) sous « Nos partenaires », sans aucune relation avec elles. Un
 * partenaire se saisit dans le back-office (menu « Web avant » → « Partner »), avec son vrai
 * logo ; tant qu'il n'y en a pas, la page d'accueil n'affiche pas la section.
 * La semence reste appelée par `DatabaseSeeder` : elle ne pose rien, à dessein.
 */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
    }
}
