<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `database:autobackup` — NEUTRALISÉE le 2026-09-09.
 *
 * Cette commande du socle We Courier construisait un dump SQL à la main, en
 * PHP, et l'envoyait **par courriel**. Elle produisait des sauvegardes que l'on
 * ne découvre inutilisables qu'au moment de restaurer — c'est-à-dire au pire
 * moment possible :
 *
 *   $connect = new PDO("…;charset=utf8", …);            // la base est en utf8mb4
 *   $output .= "'" . implode("','", $valeurs) . "');";  // AUCUN échappement
 *
 *   - Aucun échappement : une seule apostrophe — « L'Express », « Cotonou
 *     l'Ancien » — produit un fichier SQL irrécupérable. Le français en est
 *     plein.
 *   - `charset=utf8` sur une base `utf8mb4` : les caractères sur quatre octets
 *     sont tronqués à la lecture.
 *   - `NULL` devient la chaîne vide : une date nulle restaurée en `''`.
 *   - Le dump entier est construit en mémoire PHP avant d'être expédié — et
 *     expédié EN CLAIR, avec les pièces d'identité des marchands.
 *
 * Elle était planifiée quotidiennement dans `Console\Kernel`. La planification
 * est retirée ; le corps refuse. La classe reste pour que
 * `php artisan database:autobackup` réponde à qui la connaît, au lieu de
 * disparaître sans explication.
 *
 * La sauvegarde du projet : docs/guides/infra/sauvegarde/
 */
class DatabaseAutoBackup extends Command
{
    protected $signature = 'database:autobackup';

    protected $description = 'Retirée : sauvegarde défectueuse du socle — voir docs/guides/infra/sauvegarde/';

    public function handle(): int
    {
        $this->error('Cette commande est retirée : ses sauvegardes sont inutilisables.');
        $this->newLine();
        $this->line('Elle assemblait le SQL sans échapper les valeurs : une apostrophe');
        $this->line('— « L\'Express » — suffit à rendre le fichier irrécupérable. Elle');
        $this->line('lisait en `utf8` une base `utf8mb4`, transformait les NULL en chaînes');
        $this->line('vides, et expédiait toute la base en clair par courriel.');
        $this->newLine();
        $this->line('La sauvegarde du projet : docs/guides/infra/sauvegarde/');
        $this->line('  script : /usr/local/bin/beninlink-sauvegarde');

        return self::FAILURE;
    }
}
