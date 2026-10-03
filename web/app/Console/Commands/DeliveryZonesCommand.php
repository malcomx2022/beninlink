<?php

namespace App\Console\Commands;

use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Services\Pricing\GridFile;
use App\Services\Pricing\ZoneCatalog;
use Illuminate\Console\Command;
use InvalidArgumentException;

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
 * ## Ce qu'elle ne fait pas sans qu'on le lui demande
 *
 * Sans `--grille`, elle n'écrit **aucun montant de grille**. Les tranches × zones
 * se saisissent dans *Réglages → Zones et barème* : ces montants appartiennent au
 * transporteur, pas au logiciel. La commande pose le cadre, pas les prix.
 *
 * Avec `--grille=<csv>` (**S72**), elle pose aussi la grille lue dans le fichier —
 * `database/bareme/grille-nationale.csv` est celui que le jeu pilote utilise, pour
 * que recette et production partent de la même grille sans ressaisie. Même règle
 * que les forfaits CEDEAO : une ligne **déjà présente n'est jamais réécrite**, le
 * montant ajusté à l'écran fait foi. Un fichier fautif refuse tout, n'écrit rien.
 *
 * Les forfaits CEDEAO sont l'exception assumée — ils ont été tranchés par le
 * métier — et restent **créés s'ils manquent, jamais réécrits**.
 */
class DeliveryZonesCommand extends Command
{
    protected $signature = 'beninlink:zones-tarifaires
        {--societe= : société à traiter ; par défaut toutes}
        {--supplement= : supplément « jour même », en FCFA entiers (défaut : 300, décision du métier)}
        {--installer : écrire les zones, les délais et les forfaits}
        {--grille= : CSV de la grille nationale (categorie;poids_max;cotonou;peripherie;interieur) ; lignes créées si absentes, jamais réécrites}';

    protected $description = 'Installe les zones, délais et forfaits CEDEAO d\'une société (D4)';

    /** @var list<array{categorie:string, poids_max:int, montants:array<string,int>}>|null */
    private ?array $grille = null;

    public function handle(ZoneCatalog $catalogue, GridFile $fichier): int
    {
        $supplement = (float) ($this->option('supplement') ?? ZoneCatalog::SAME_DAY_SURCHARGE);

        if ($chemin = $this->option('grille')) {
            // Lu et validé AVANT la première écriture : un fichier fautif n'écrit rien.
            try {
                $this->grille = $fichier->lire($chemin);
            } catch (InvalidArgumentException $e) {
                $this->error('Grille refusée, rien n\'a été écrit.');
                $this->line($e->getMessage());

                return self::FAILURE;
            }
            $this->line(sprintf('Grille « %s » : %d tranche(s), %d catégorie(s).', $chemin, count($this->grille), count(array_unique(array_column($this->grille, 'categorie')))));
        }

        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::all();

        if ($societes->isEmpty()) {
            $this->error('Aucune société.');

            return self::FAILURE;
        }

        foreach ($societes as $societe) {
            $this->traiter($catalogue, $fichier, $societe, $supplement);
        }

        if (!$this->option('installer')) {
            $this->newLine();
            $this->comment('Constat seul. Ajouter --installer pour écrire.');
        }

        return self::SUCCESS;
    }

    private function traiter(ZoneCatalog $catalogue, GridFile $fichier, GeneralSettings $societe, float $supplement): void
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
            if ($this->grille !== null && $existantes !== []) {
                $this->line(sprintf('Grille : %d ligne(s) à créer (les autres existent déjà et seraient conservées).', $fichier->manquantes($societe->id, $this->grille)));
            }

            return;
        }

        $catalogue->installer($societe->id, $supplement);

        if ($this->grille === null) {
            $this->info('Zones, délais et forfaits CEDEAO en place. La grille se saisit à l\'écran.');

            return;
        }

        $resultat = $fichier->installer($societe->id, $this->grille);
        $this->info(sprintf(
            'Zones, délais et forfaits CEDEAO en place. Grille : %d ligne(s) créée(s), %d conservée(s) telle(s) qu\'à l\'écran.',
            $resultat['creees'],
            $resultat['conservees'],
        ));
    }
}
