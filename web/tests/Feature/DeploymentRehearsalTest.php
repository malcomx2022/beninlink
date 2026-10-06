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

    /** @return array<int, string> les lignes de deploy.sh hors du corps de `remonter_le_site()` */
    private function lignesDuCheminNominal(): array
    {
        $lignes = [];
        $dansLeFilet = false;
        foreach (file($this->racine(self::DEPLOY)) as $ligne) {
            if (preg_match('/^remonter_le_site\(\) \{/', $ligne)) {
                $dansLeFilet = true;
                continue;
            }
            if ($dansLeFilet) {
                if (preg_match('/^\}/', $ligne)) {
                    $dansLeFilet = false;
                }
                continue;
            }
            $lignes[] = $ligne;
        }

        return $lignes;
    }

    /** Le corps de `remonter_le_site()`, tel qu'écrit. */
    private function corpsDuFilet(): string
    {
        $script = file_get_contents($this->racine(self::DEPLOY));
        $this->assertSame(1, preg_match('/^remonter_le_site\(\) \{\n(.*?)^\}/ms', $script, $m), 'deploy.sh définit le filet remonter_le_site()');

        return $m[1];
    }

    /**
     * Les commandes `php artisan …` du **chemin nominal** de deploy.sh, dans l'ordre,
     * commentaires exclus. Le corps du filet `remonter_le_site()` (S89) est le chemin de
     * secours : il n'est pas à rejouer par le job, il a son propre test.
     */
    private function commandesDuScript(): array
    {
        $commandes = [];
        foreach ($this->lignesDuCheminNominal() as $ligne) {
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
            ['down', 'optimize:clear', 'beninlink:comptes-amorcage', 'beninlink:tarification-prete', 'migrate', 'config:cache', 'route:cache', 'view:cache', 'queue:restart', 'up'],
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

    /**
     * **S88** — une commande `beninlink:*` de `deploy.sh` vit dans la version qu'on
     * déploie, pas dans celle du serveur : elle se joue APRÈS `git pull`,
     * `composer install` et `optimize:clear`. Le premier déploiement qui a atteint le
     * serveur (fusion de S87) est tombé sur « Command "beninlink:comptes-amorcage" is
     * not defined » : la commande était appelée avant la mise à jour du code. Le job
     * de répétition ne peut pas le voir — il tourne sur le code neuf.
     */
    public function test_les_commandes_du_depot_suivent_la_mise_a_jour_du_code(): void
    {
        $script = file_get_contents($this->racine(self::DEPLOY));
        $miseAJour = max(strpos($script, 'git pull origin main'), strpos($script, 'composer install'), strpos($script, 'php artisan optimize:clear'));
        $this->assertNotFalse($miseAJour);

        preg_match_all('/^php artisan (beninlink:[a-z-]+)/m', $script, $m, PREG_OFFSET_CAPTURE);
        $this->assertNotEmpty($m[1], 'deploy.sh joue au moins une commande du dépôt');
        foreach ($m[1] as [$commande, $position]) {
            $this->assertGreaterThan($miseAJour, $position,
                "`{$commande}` est appelée avant la mise à jour du code : le serveur exécute l'ancienne version, qui peut ne pas la connaître");
        }
    }

    /**
     * **S89** — un déploiement refusé remet l'ancien code, pas seulement le site.
     *
     * Les gardes qui peuvent refuser (`comptes-amorcage`, `tarification-prete`, `migrate`)
     * tournent après `git pull` : sur refus, remonter le site servirait le NOUVEAU code sur
     * l'ANCIEN schéma. Le filet note la révision servie avant `git pull`, et y revient —
     * code, dépendances, caches — tant que la migration n'a pas été appliquée.
     */
    public function test_un_deploiement_refuse_remet_la_revision_servie(): void
    {
        $script = file_get_contents($this->racine(self::DEPLOY));

        $revision = strpos($script, 'REVISION_SERVIE="$(git rev-parse HEAD)"');
        $pull = strpos($script, 'git pull origin main');
        $this->assertNotFalse($revision, 'deploy.sh note la révision servie');
        $this->assertLessThan($pull, $revision, 'la révision se note AVANT git pull, sinon c\'est déjà la nouvelle');

        $filet = $this->corpsDuFilet();
        $reset = strpos($filet, 'git reset --hard "$REVISION_SERVIE"');
        $composer = strpos($filet, 'composer install --no-dev');
        $up = strpos($filet, 'php artisan up');
        $this->assertNotFalse($reset, 'le filet revient à la révision servie');
        $this->assertNotFalse($composer, 'le filet réinstalle les dépendances de cette révision');
        $this->assertNotFalse($up, 'le filet remonte le site');
        $this->assertLessThan($composer, $reset, 'le code avant ses dépendances');
        $this->assertLessThan($up, $composer, 'le site remonte en dernier, sur l\'ancien code');
        $this->assertStringContainsString('"$MIGRE" = 0', $filet, 'le retour en arrière ne vaut que tant que la migration n\'a pas été appliquée');

        $migration = strpos($script, 'php artisan migrate --force');
        $drapeau = strpos($script, 'MIGRE=1');
        $this->assertNotFalse($drapeau);
        $this->assertGreaterThan($migration, $drapeau, 'le drapeau se lève APRÈS la migration');
        $this->assertLessThan(strpos($script, 'php artisan config:cache', $migration), $drapeau, '… et avant la commande suivante du chemin nominal');
    }

    /** `appleboy/ssh-action@v1` ne connaît plus `script_stop` (avertissement dans chaque journal) : `set -euo pipefail` est dans le script. */
    public function test_les_etapes_ssh_ne_passent_pas_d_entree_inconnue(): void
    {
        $jobs = Yaml::parseFile($this->racine(self::WORKFLOW))['jobs'];
        $etapesSsh = 0;
        foreach ($jobs as $nom => $job) {
            foreach ($job['steps'] ?? [] as $etape) {
                if (str_starts_with($etape['uses'] ?? '', 'appleboy/ssh-action')) {
                    $etapesSsh++;
                    $this->assertArrayNotHasKey('script_stop', $etape['with'] ?? [], "{$nom} : `script_stop` n'est pas une entrée de ssh-action@v1");
                    $this->assertStringContainsString('set -euo pipefail', $etape['with']['script'], "{$nom} : le script s'arrête lui-même à la première erreur");
                }
            }
        }
        $this->assertSame(2, $etapesSsh, 'les deux déploiements (production, recette) passent par SSH');
    }

    /**
     * S100 — la sérialisation des déploiements est sur les jobs de déploiement, pas sur le
     * workflow : posée au niveau du workflow, elle retenait la suite de tests d'une pull request
     * derrière le déploiement de `main` en cours (huit à quinze minutes par lot, constaté sur
     * les PR #161 à #166). Chaque serveur a son groupe, et rien ne s'annule en cours de route.
     */
    public function test_la_serialisation_des_deploiements_ne_retient_pas_la_ci_des_pull_requests(): void
    {
        $workflow = Yaml::parseFile($this->racine(self::WORKFLOW));
        $this->assertArrayNotHasKey('concurrency', $workflow, 'au niveau du workflow, le groupe retiendrait aussi les tests des pull requests');

        $groupes = [];
        foreach (['deploy', 'deploy-recette'] as $nom) {
            $job = $workflow['jobs'][$nom];
            $this->assertArrayHasKey('concurrency', $job, "$nom : deux déploiements simultanés couperaient le site deux fois");
            $this->assertFalse($job['concurrency']['cancel-in-progress'], "$nom : interrompre entre down et up laisserait le site en maintenance");
            $groupes[] = $job['concurrency']['group'];
        }
        $this->assertCount(2, array_unique($groupes), 'un groupe par serveur : la recette n\'attend pas la production');

        foreach (['tests', 'apps', 'repetition'] as $nom) {
            $this->assertArrayNotHasKey('concurrency', $workflow['jobs'][$nom], "$nom : la CI d'une pull request ne se sérialise pas");
        }
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
