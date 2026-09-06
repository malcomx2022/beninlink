<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Les seeders se compilent — même ceux qu'aucun test n'exécute.
 *
 * Deux d'entre eux étaient **inutilisables** depuis le durcissement S21 : le
 * commentaire « passerelle désactivée » avait été inséré à l'intérieur de
 * l'appel, avant le `);`, et commentait donc la fin de l'instruction.
 *
 *     Setting::create([... 'value' => 0] // S21 : passerelle désactivée);
 *
 * Conséquence : `php artisan db:seed` échouait — la troisième commande du guide
 * d'installation de la recette. Rien ne l'a signalé parce que la liste des
 * seeders utilisée par les tests (`Tests\Concerns\SeedsTenant`) ne contient ni
 * `SettingSeeder` ni `MerchantSettingSeeder`.
 *
 * Ce test ne les exécute pas — certains écrivent des dizaines de lignes et ont
 * leurs propres dépendances — il vérifie seulement qu'ils **compilent**. C'est
 * exactement ce qui manquait.
 */
class SeedersParseTest extends TestCase
{
    public function test_every_seeder_compiles(): void
    {
        $binaire = PHP_BINARY;
        $fichiers = glob(database_path('seeders/*.php')) ?: [];
        $fichiers = array_merge($fichiers, glob(database_path('seeders/*/*.php')) ?: []);
        $fichiers = array_merge($fichiers, glob(database_path('seeders/*/*/*.php')) ?: []);

        $this->assertNotEmpty($fichiers, 'aucun seeder trouvé — le chemin a changé ?');

        $casses = [];
        foreach ($fichiers as $fichier) {
            $sortie = [];
            $code = 0;
            exec(escapeshellarg($binaire) . ' -l ' . escapeshellarg($fichier) . ' 2>&1', $sortie, $code);
            if ($code !== 0) {
                $casses[] = basename($fichier) . ' : ' . trim(implode(' ', $sortie));
            }
        }

        $this->assertSame([], $casses, "seeders non compilables :\n" . implode("\n", $casses));
    }
}
