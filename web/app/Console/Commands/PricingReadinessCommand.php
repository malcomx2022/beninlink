<?php

namespace App\Console\Commands;

use App\Services\Pricing\PricingReadinessAudit;
use Illuminate\Console\Command;

/**
 * `php artisan beninlink:tarification-prete` — cette société peut-elle
 * facturer ? (**D4**)
 *
 * Elle s'appelait `beninlink:bareme-herite` et gardait la porte de l'étape 6 :
 * elle disait ce qui dépendait encore des quatre colonnes avant qu'on ne les
 * retire. Elles sont parties le 2026-09-07, et la question qu'elle pose n'a pas
 * disparu — elle est devenue **permanente**.
 *
 * Depuis l'étape 6, il n'y a plus qu'un axe de tarification : la route. Une
 * société sans zones ne facture plus rien, et ne l'apprend qu'au premier colis
 * refusé. Une ligne de barème ou un barème négocié resté sans zone est une
 * impasse, pas un vestige. Cette commande les nomme, société par société, et
 * **sort en erreur** tant qu'un blocage subsiste — pour qu'un déploiement
 * s'arrête là plutôt que de livrer une installation qui ne peut pas travailler.
 *
 * Elle ne corrige rien : `beninlink:zones-tarifaires` pose les zones, la
 * grille se saisit dans *Réglages → Zones et barème*.
 */
class PricingReadinessCommand extends Command
{
    protected $signature = 'beninlink:tarification-prete {--societe= : société à examiner ; par défaut toutes}';

    protected $description = 'Dit, société par société, si la tarification par zones est en état de facturer (D4)';

    public function handle(PricingReadinessAudit $audit): int
    {
        $societe = $this->option('societe') ? (int) $this->option('societe') : null;
        $rapports = $audit->auditer($societe);

        if ($rapports === []) {
            $this->error('Aucune société.');

            return self::FAILURE;
        }

        $bloquees = 0;

        foreach ($rapports as $rapport) {
            $prete = $audit->estPrete($rapport);
            $bloquees += $prete ? 0 : 1;

            $this->newLine();
            $this->line(sprintf(
                '<options=bold>%s</> — %s',
                $rapport['societe']->name,
                $prete ? '<fg=green>prête</>' : '<fg=red>pas prête</>'
            ));

            foreach ($rapport['blocages'] as $blocage) {
                $this->line("  <fg=red>✗</> {$blocage}");
            }

            if ($rapport['colis_sans_zone'] > 0) {
                // Le signal le plus honnête sur l'état du parc : tant que des
                // colis arrivent sans zone, un écran ou une app en circulation
                // contourne le calculateur.
                $this->line(sprintf(
                    '  <fg=red>✗</> %d colis créé(s) sans zone sur les %d derniers jours',
                    $rapport['colis_sans_zone'],
                    PricingReadinessAudit::FENETRE_JOURS
                ));
            }

            foreach ($rapport['avertissements'] as $avertissement) {
                $this->line("  <fg=yellow>!</> {$avertissement}");
            }
        }

        $this->newLine();

        if ($bloquees > 0) {
            $this->error(sprintf(
                '%d société(s) ne sont pas en état de facturer. Poser les zones avec '
                . '« php artisan beninlink:zones-tarifaires --societe=N --installer », '
                . 'puis saisir la grille dans Réglages → Zones et barème, et relancer ce constat.',
                $bloquees
            ));

            return self::FAILURE;
        }

        $this->info('Chaque société tarife par zones : rien ne dépend d\'une route absente.');


        return self::SUCCESS;
    }
}
