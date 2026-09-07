<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryZone;
use App\Services\Pricing\ZoneCatalog;
use Illuminate\Database\Seeder;

/**
 * Barème de démonstration — **par zones** depuis l'étape 6 (**D4**).
 *
 * Le seeder du socle écrivait les quatre colonnes héritées. Elles n'existent
 * plus, et poser une grille sans zones aurait été pire que de ne rien poser :
 * depuis l'étape 6, un colis sans zone n'a **pas de tarif**, et une
 * installation neuve n'aurait pas pu créer un seul colis.
 *
 * Il pose donc d'abord le cadre — zones, délais, forfaits CEDEAO, par
 * `ZoneCatalog`, le seul endroit qui les définit — puis une grille de
 * démonstration reprenant les montants d'origine, colonne par zone selon la
 * correspondance actée : Cotonou ← `next_day`, Périphérie ← `sub_city`,
 * Intérieur ← `outside_city`.
 */
class DeliveryChargeSeeder extends Seeder
{
    private const SOCIETE = 2;

    private const CATEGORIE = 1;

    /** [poids, Cotonou, Périphérie, Intérieur] — les montants du socle. */
    private const GRILLE = [
        [1, 60, 70, 80],
        [2, 100, 110, 120],
        [3, 140, 150, 160],
        [4, 180, 190, 200],
        [5, 220, 230, 240],
        [6, 260, 270, 280],
        [7, 300, 310, 320],
        [8, 350, 360, 370],
        [9, 390, 400, 410],
        [10, 430, 440, 450],
    ];

    public function run(): void
    {
        $zones = app(ZoneCatalog::class)->installer(self::SOCIETE);

        $nationales = [
            DeliveryZone::COTONOU,
            DeliveryZone::PERIPHERIE,
            DeliveryZone::INTERIEUR,
        ];

        foreach (self::GRILLE as $position => $ligne) {
            $poids = array_shift($ligne);

            foreach ($nationales as $rang => $code) {
                // Pas d'`updateOrCreate` : `company_id` n'est pas assignable en
                // masse sur ce modèle du socle.
                $tarif = new DeliveryCharge();
                $tarif->company_id = self::SOCIETE;
                $tarif->category_id = self::CATEGORIE;
                $tarif->zone_id = $zones[$code]->id;
                $tarif->weight = $poids;
                $tarif->amount = $ligne[$rang];
                $tarif->position = $position + 1;
                $tarif->status = Status::ACTIVE;
                $tarif->save();
            }
        }
    }
}
