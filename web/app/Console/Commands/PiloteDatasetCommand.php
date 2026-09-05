<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\User;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Console\Command;

/**
 * `php artisan beninlink:pilote` — jeu de données béninois pour la recette.
 * Voir docs/guides/recette-pilote/README.md.
 */
class PiloteDatasetCommand extends Command
{
    protected $signature = 'beninlink:pilote
        {--company= : Identifiant de la société (general_settings.id) ; défaut : celle du premier administrateur}
        {--reset : Retirer le jeu existant avant de le recréer}';

    protected $description = 'Crée le jeu de données pilote (agences, barème FCFA, 5 PME, 3 livreurs, colis) — recette uniquement';

    public function handle(PiloteDataset $dataset): int
    {
        if (app()->environment('production')) {
            $this->error('Refusé en production : ce jeu de données est réservé à la recette.');

            return self::FAILURE;
        }

        $companyId = (int) ($this->option('company') ?: User::where('user_type', UserType::ADMIN)->orderBy('id')->value('company_id'));
        if ($companyId <= 0) {
            $this->error('Aucune société trouvée : préciser --company=<id>.');

            return self::FAILURE;
        }

        try {
            $summary = $dataset->seed($companyId, (bool) $this->option('reset'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Société « %s » : %d agences, %d PME, %d livreurs, %d colis.', $summary['company'], $summary['hubs'], count($summary['merchants']), count($summary['deliverymen']), $summary['parcels']));
        $this->line('');
        $this->line('Comptes marchands (app marchand — identifiant marchand + mot de passe « ' . $summary['password'] . ' ») :');
        $this->table(['Enseigne', 'Identifiant', 'Téléphone'], array_map(fn ($m) => [$m['business'], $m['driver_id'], $m['phone']], $summary['merchants']));
        $this->line('Comptes livreurs (app livreur — identifiant livreur + même mot de passe) :');
        $this->table(['Nom', 'Identifiant'], array_map(fn ($d) => [$d['name'], $d['driver_id']], $summary['deliverymen']));
        $this->warn('Recette uniquement : mots de passe connus. Relancer avec --reset pour repartir de zéro.');

        return self::SUCCESS;
    }
}
