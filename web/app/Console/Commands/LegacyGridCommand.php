<?php

namespace App\Console\Commands;

use App\Services\Pricing\LegacyGridAudit;
use Illuminate\Console\Command;

/**
 * `php artisan beninlink:bareme-herite` — l'étape 6 est-elle sûre ? (**D4**)
 *
 * L'étape 6 supprime les quatre colonnes du barème hérité. Elle est
 * **irréversible** et **sèche** : ces colonnes parties, `ChargeCalculator` n'a
 * plus de repli, et une société qui n'a pas converti son barème ne peut plus
 * créer un colis — silencieusement, du point de vue du code, bruyamment du
 * point de vue de l'exploitation.
 *
 * Le plan disait « une fois les apps déployées ». Cette commande remplace
 * l'intention par une **vérification**, société par société, et sort en erreur
 * tant qu'un blocage subsiste. À lancer avant d'envisager la migration de
 * suppression, et à relancer après chaque conversion.
 *
 * Elle ne corrige rien. La conversion reste le travail de
 * `beninlink:zones-tarifaires`, qui montre le tableau des écarts avant
 * d'écrire : un tarif qui se déplace doit être lu, pas subi.
 */
class LegacyGridCommand extends Command
{
    protected $signature = 'beninlink:bareme-herite {--societe= : société à examiner ; par défaut toutes}';

    protected $description = 'Dit ce qui dépend encore des quatre colonnes héritées (D4, préalable à l\'étape 6)';

    public function handle(LegacyGridAudit $audit): int
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
                // utilise encore le chemin hérité.
                $this->line(sprintf(
                    '  <fg=red>✗</> %d colis créé(s) sans zone sur les %d derniers jours',
                    $rapport['colis_sans_zone'],
                    LegacyGridAudit::FENETRE_JOURS
                ));
            }

            foreach ($rapport['avertissements'] as $avertissement) {
                $this->line("  <fg=yellow>!</> {$avertissement}");
            }
        }

        $this->newLine();

        if ($bloquees > 0) {
            $this->error(sprintf(
                '%d société(s) dépendent encore du barème hérité. Convertir avec '
                . '« php artisan beninlink:zones-tarifaires --societe=N --appliquer » '
                . '(lire le tableau des écarts avant d\'appliquer), puis relancer ce constat.',
                $bloquees
            ));

            return self::FAILURE;
        }

        $this->info('Aucune dépendance au barème hérité : l\'étape 6 peut être envisagée.');
        $this->comment(
            'Rappel : la suppression des colonnes est irréversible, et le code qui les lit '
            . 'part avec elles. Ne la déployer qu\'après avoir vérifié que les applications '
            . 'en circulation envoient bien une zone.'
        );

        return self::SUCCESS;
    }
}
