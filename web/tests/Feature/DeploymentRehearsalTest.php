<?php

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * **S80** — la répétition de déploiement : ce que `deploy.sh` fait sur le serveur
 * est rejoué dans l'intégration continue, avant, sur une pull request comme sur `main`.
 *
 * Le constat qui l'a ouverte (S79) : la suite tourne **avec** les dépendances de dev
 * et sur SQLite ; `deploy.sh` installe `--no-dev` et parle à MySQL. Deux fuites de
 * paquets de dev n'ont mordu que sur le serveur. Et `route:cache`, `view:cache`,
 * `config:cache`, `beninlink:tarification-prete` ne tournaient nulle part avant lui.
 *
 * Ce test tient les deux fichiers d'accord : les commandes artisan de `deploy.sh`
 * (hors `down` / `up`, qui pilotent le site vivant) sont rejouées par le job
 * `repetition`, dans le même ordre, après un `composer install --no-dev` ; et les
 * deux déploiements attendent la répétition. Ajouter une étape au script sans la
 * répéter ici est rouge.
 */
class DeploymentRehearsalTest extends TestCase
{
    private const WORKFLOW = '.github/workflows/deploy.yml';
    private const DEPLOY = 'docs/guides/infra/deploy/deploy.sh';

    /** Les commandes qui pilotent le site vivant : sans objet dans un runner. */
    private const HORS_REPETITION = ['down', 'up'];

    private function racine(string $relatif): string
    {
        return dirname(base_path()) . '/' . $relatif;
    }

    private function job(): array
    {
        $jobs = Yaml::parseFile($this->racine(self::WORKFLOW))['jobs'];
        $this->assertArrayHasKey('repetition', $jobs, 'le job de répétition de déploiement (S80)');

        return $jobs['repetition'];
    }

    /** Les commandes `php artisan …` de deploy.sh, dans l'ordre, commentaires exclus. */
    private function commandesDuScript(): array
    {
        $commandes = [];
        foreach (file($this->racine(self::DEPLOY)) as $ligne) {
            $ligne = trim(preg_replace('/#.*$/', '', $ligne));
            // Plusieurs commandes sur une ligne (`a && b && c`).
            foreach (preg_split('/\s*(?:&&|\|\|)\s*/', $ligne) as $morceau) {
                if (preg_match('/^php artisan ([a-z:-]+)/', trim($morceau), $m)) {
                    $commandes[] = $m[1];
                }
            }
        }

        return $commandes;
    }

    /** Tout ce que le job exécute (`run:`), étape après étape, en un seul texte. */
    private function scriptDuJob(): string
    {
        return implode("\n", array_map(fn ($etape) => (string) ($etape['run'] ?? ''), $this->job()['steps']));
    }

    // ---- l'inventaire ------------------------------------------------------------------------

    /** Un déploiement est cette liste, et pas une autre : une étape ajoutée au script s'inscrit ici. */
    public function test_les_commandes_artisan_de_deploy_sh_sont_celles_attendues(): void
    {
        $this->assertSame(
            ['up', 'down', 'optimize:clear', 'beninlink:tarification-prete', 'migrate', 'config:cache', 'route:cache', 'view:cache', 'queue:restart', 'up'],
            $this->commandesDuScript(),
            'deploy.sh a changé : mettre à jour cette liste ET le job de répétition'
        );
    }

    // ---- le job ---------------------------------------------------------------------------------

    public function test_le_job_rejoue_les_commandes_de_deploy_sh_dans_le_meme_ordre(): void
    {
        $script = $this->scriptDuJob();
        $position = -1;
        $deja = [];

        foreach ($this->commandesDuScript() as $commande) {
            if (in_array($commande, self::HORS_REPETITION, true) || isset($deja[$commande])) {
                continue;
            }
            $deja[$commande] = true;

            // La commande cherchée APRÈS la précédente : l'ordre compte (tarification-prete
            // avant migrate, les caches après, comme sur le serveur).
            $trouvee = preg_match('/php artisan ' . preg_quote($commande, '/') . '(\s|$)/', $script, $m, PREG_OFFSET_CAPTURE, $position + 1);
            $this->assertSame(1, $trouvee, "deploy.sh exécute `php artisan {$commande}` ; le job de répétition ne le rejoue pas (ou pas dans l'ordre du script)");
            $position = $m[0][1];
        }
    }

    public function test_le_job_installe_sans_les_dependances_de_dev_et_contre_mysql(): void
    {
        $job = $this->job();

        $this->assertSame('tests', $job['needs']);
        $this->assertArrayNotHasKey('if', $job, 'la répétition tourne sur une pull request aussi : c\'est là qu\'elle sert');
        $this->assertArrayHasKey('mysql', $job['services'] ?? [], 'la vraie base : les semences du socle sont du SQL MySQL');
        $this->assertStringStartsWith('mysql:', $job['services']['mysql']['image']);

        $script = $this->scriptDuJob();
        $this->assertSame(1, preg_match('/composer install[^\n]*/', $script, $m));
        $this->assertStringContainsString('--no-dev', $m[0], 'tout l\'objet du job : installer comme deploy.sh');
        $this->assertLessThan(strpos($script, 'php artisan'), strpos($script, 'composer install'), 'composer avant artisan — `package:discover` est le premier à tomber');

        $this->assertStringContainsString('verifier-env.sh .env', $script, 'le garde du .env de deploy.sh est répété aussi');
        foreach (['migrate --force', 'db:seed --force', 'beninlink:pilote'] as $installation) {
            $this->assertStringContainsString($installation, $script, "l'installation d'une recette, telle que le guide la décrit : {$installation}");
        }
        $this->assertStringContainsString('api/v10/general-settings', $script, 'et l\'application répond, caches en place');
    }

    /**
     * **S86** — les semences posent le cadre de zones de CHAQUE société qu'elles créent ;
     * le constat de tarification les suit, et rien entre les deux ne pose de zone à la main.
     *
     * Jusqu'à S86 le job répétait le contournement du guide
     * (`zones-tarifaires --societe=1 --installer` après `db:seed`) : il rejouait ainsi une
     * installation que personne ne fait par l'installateur web, et aurait caché une
     * régression du seeder — précisément ce que `tarification-prete` doit mesurer ici.
     */
    public function test_le_constat_de_tarification_suit_les_semences_sans_contournement(): void
    {
        $script = $this->scriptDuJob();
        $semences = strpos($script, 'php artisan db:seed --force');
        $constat = strpos($script, 'php artisan beninlink:tarification-prete');

        $this->assertNotFalse($semences);
        $this->assertNotFalse($constat);
        $this->assertLessThan($constat, $semences, 'le constat se joue sur une base amorcée');
        $this->assertStringNotContainsString(
            'beninlink:zones-tarifaires',
            $script,
            'le job pose des zones à la main : il ne mesure plus que les semences suffisent (S86, FreshInstallReadinessTest)'
        );
    }

    public function test_rien_ne_part_sur_un_serveur_sans_la_repetition(): void
    {
        $jobs = Yaml::parseFile($this->racine(self::WORKFLOW))['jobs'];

        foreach (['deploy', 'deploy-recette'] as $nom) {
            $this->assertContains('repetition', (array) $jobs[$nom]['needs'], "{$nom} attend la répétition de déploiement");
            $this->assertContains('tests', (array) $jobs[$nom]['needs'], "{$nom} attend toujours la suite");
        }
    }
}
