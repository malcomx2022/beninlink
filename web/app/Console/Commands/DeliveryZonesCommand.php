<?php

namespace App\Console\Commands;

use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Services\Pricing\ZoneCatalog;
use Illuminate\Console\Command;

/**
 * `php artisan beninlink:zones-tarifaires` — installer le modèle par zones (**D4**).
 *
 * ## Ce qu'elle faisait, et ce qu'elle fait
 *
 * Jusqu'à l'étape 6, elle **convertissait** le barème hérité : elle lisait les
 * quatre colonnes et en tirait une ligne par zone. Ces colonnes ont disparu le
 * 2026-09-07 ; il n'y a plus rien à convertir, et une commande qui prétendrait
 * le faire mentirait.
 *
 * Ce qu'elle fait désormais est ce dont une installation neuve a besoin :
 * poser les **quatre zones**, les **trois délais** avec le supplément « jour
 * même », et les **forfaits CEDEAO**. Sans cela une société ne peut plus rien
 * facturer — depuis l'étape 6, un colis sans zone n'a pas de tarif.
 *
 * ## Ce qu'elle ne fait pas
 *
 * Elle n'écrit **aucun montant de grille**. Les tranches × zones se saisissent
 * dans *Réglages → Zones et barème* : ces montants appartiennent au
 * transporteur, pas au logiciel. La commande pose le cadre, pas les prix.
 *
 * Les forfaits CEDEAO sont l'exception assumée — ils ont été tranchés par le
 * métier — et restent **créés s'ils manquent, jamais réécrits**.
 */
class DeliveryZonesCommand extends Command
{
    protected $signature = 'beninlink:zones-tarifaires
        {--societe= : société à traiter ; par défaut toutes}
        {--supplement= : supplément « jour même », en FCFA entiers (défaut : 300, décision du métier)}
        {--installer : écrire les zones, les délais et les forfaits}';

    protected $description = 'Installe les zones, délais et forfaits CEDEAO d\'une société (D4)';

    public function handle(ZoneCatalog $catalogue): int
    {
        $supplement = (float) ($this->option('supplement') ?? ZoneCatalog::SAME_DAY_SURCHARGE);

        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::all();

        if ($societes->isEmpty()) {
            $this->error('Aucune société.');

            return self::FAILURE;
        }

        foreach ($societes as $societe) {
            $this->traiter($catalogue, $societe, $supplement);
        }

        if (!$this->option('installer')) {
            $this->newLine();
            $this->comment('Constat seul. Ajouter --installer pour écrire.');
        }

        return self::SUCCESS;
    }

    private function traiter(ZoneCatalog $catalogue, GeneralSettings $societe, float $supplement): void
    {
        $existantes = DeliveryZone::where('company_id', $societe->id)->pluck('code')->all();

        $this->newLine();
        $this->line("<options=bold>{$societe->name}</>");

        $this->table(
            ['Zone', 'État'],
            collect(ZoneCatalog::ZONES)->map(fn (array $zone) => [
                $zone[1],
                in_array($zone[0], $existantes, true) ? 'déjà posée' : 'à créer',
            ])->all(),
        );

        $this->line(sprintf('Supplément « jour même » : %s', formatAmount($supplement)));

        if (!$this->option('installer')) {
            return;
        }

        $catalogue->installer($societe->id, $supplement);
        $this->info('Zones, délais et forfaits CEDEAO en place. La grille se saisit à l\'écran.');
    }
}
