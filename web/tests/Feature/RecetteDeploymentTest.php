<?php

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * **S76 (E2)** — le serveur de recette se déploie par le même mécanisme que la
 * production, et un `.env` de recette ne peut pas porter de clé FedaPay live.
 *
 * Le constat du 2026-10-03 : tous les déploiements de `main` échouaient au garde
 * des secrets (SSH_HOST / SSH_USER / SSH_KEY vides), et le workflow ne connaissait
 * qu'un seul serveur. Ce lot ajoute un second job pour le vhost de recette
 * (`RECETTE_SSH_*`, qui se **saute** sans bloquer la production tant qu'il n'est
 * pas configuré), paramètre le chemin dans `deploy.sh`, et pose un garde du
 * `.env` **avant la coupure** du site. Trois pièces d'infrastructure, trois
 * familles d'assertions : la forme du workflow, la forme du script, et le
 * **comportement** du garde, exécuté sur des `.env` fabriqués.
 *
 * **S77 (T7)** : le même garde exige `API_KEY` — la config n'a plus de repli sur
 * la clé publique du socle, et un `.env` sans clé couperait les deux apps au
 * remontage du site. Les fixtures FedaPay portent donc toutes une clé ; deux
 * cas la font manquer ou valoir la clé publique.
 */
class RecetteDeploymentTest extends TestCase
{
    private const WORKFLOW = '.github/workflows/deploy.yml';
    private const DEPLOY = 'docs/guides/infra/deploy/deploy.sh';
    private const GARDE = 'docs/guides/infra/deploy/verifier-env.sh';

    /** Une clé d'installation valide et le drapeau d'installation : les cas FedaPay ne doivent tomber ni sur le garde S77 ni sur celui de S88. */
    private const CLE = "API_KEY=blk_0123456789abcdef\nAPP_INSTALLED=yes\n";

    private function racine(string $relatif): string
    {
        return dirname(base_path()) . '/' . $relatif;
    }

    private function workflow(): array
    {
        return Yaml::parseFile($this->racine(self::WORKFLOW));
    }

    // ---- le workflow -----------------------------------------------------------

    public function test_le_workflow_a_un_job_de_recette_qui_suit_les_tests_et_ignore_les_pull_requests(): void
    {
        $jobs = $this->workflow()['jobs'];

        $this->assertArrayHasKey('deploy', $jobs, 'le job de production');
        $this->assertArrayHasKey('deploy-recette', $jobs, 'le job de recette (S76)');

        $recette = $jobs['deploy-recette'];
        $this->assertContains('tests', (array) $recette['needs'], 'rien ne part en recette sans la suite verte');
        $this->assertContains('repetition', (array) $recette['needs'], 'ni sans la répétition de déploiement (S80)');
        $this->assertSame("github.event_name != 'pull_request'", $recette['if']);
    }

    /** Sans secrets de recette, le job se saute : la production ne dépend pas de la recette. */
    public function test_le_job_de_recette_se_saute_sans_ses_secrets_au_lieu_d_echouer(): void
    {
        $etapes = $this->workflow()['jobs']['deploy-recette']['steps'];
        $config = $etapes[0];
        $ssh = $etapes[1];

        $this->assertSame('config', $config['id']);
        foreach (['RECETTE_SSH_HOST', 'RECETTE_SSH_USER', 'RECETTE_SSH_KEY'] as $secret) {
            $this->assertStringContainsString('secrets.' . $secret, json_encode($config['env']), "le job lit $secret");
        }
        $this->assertStringContainsString('actif=false', $config['run']);
        $this->assertStringNotContainsString('exit 1', $config['run'], 'des secrets absents ne font pas échouer le job');
        $this->assertSame("steps.config.outputs.actif == 'true'", $ssh['if']);
        $this->assertSame('appleboy/ssh-action@v1', $ssh['uses']);
    }

    /** Les deux serveurs passent par le MÊME script, pris à la version déployée, dossier entier. */
    public function test_les_deux_jobs_deploient_par_le_meme_script_avec_leur_chemin(): void
    {
        $jobs = $this->workflow()['jobs'];
        $prod = end($jobs['deploy']['steps']);
        $recette = end($jobs['deploy-recette']['steps']);

        foreach ([['production', $prod, '/var/www/beninlink'], ['recette', $recette, '/var/www/beninlink-recette']] as [$nom, $etape, $chemin]) {
            $this->assertSame('DEPLOY_PATH', $etape['with']['envs'], "$nom : le chemin est passé au serveur");
            $this->assertStringContainsString($chemin, (string) $etape['env']['DEPLOY_PATH'], "$nom : son chemin");
            $this->assertStringContainsString('git checkout FETCH_HEAD -- docs/guides/infra/deploy/', $etape['with']['script'],
                "$nom : le dossier deploy/ entier, pas le seul deploy.sh — deploy.sh appelle verifier-env.sh");
            $this->assertStringContainsString('bash docs/guides/infra/deploy/deploy.sh', $etape['with']['script']);
        }
        $this->assertStringContainsString('RECETTE_SSH_HOST', (string) $recette['with']['host']);
        $this->assertStringContainsString('SSH_HOST', (string) $prod['with']['host']);
        $this->assertStringNotContainsString('RECETTE', (string) $prod['with']['host'], 'la production garde ses propres secrets');
    }

    // ---- deploy.sh -------------------------------------------------------------

    public function test_deploy_sh_lit_son_chemin_et_verifie_le_env_avant_de_couper_le_site(): void
    {
        $source = file_get_contents($this->racine(self::DEPLOY));

        $this->assertStringContainsString('DEPLOY_PATH="${DEPLOY_PATH:-/var/www/beninlink}"', $source, 'sans variable, la production — comme avant');
        $this->assertStringContainsString('cd "$DEPLOY_PATH/web"', $source);

        $garde = strpos($source, 'verifier-env.sh" .env');
        $coupure = strpos($source, 'php artisan down');
        $this->assertNotFalse($garde, 'deploy.sh appelle le garde du .env');
        $this->assertNotFalse($coupure);
        $this->assertLessThan($coupure, $garde, 'le garde passe AVANT la coupure : ne pas déployer vaut mieux que couper pour s’arrêter');
    }

    // ---- verifier-env.sh, exécuté ---------------------------------------------------

    /** @return array{0:int, 1:string} code de sortie et sortie (stdout + stderr) */
    private function garde(?string $contenu): array
    {
        $chemin = tempnam(sys_get_temp_dir(), 'env-') ;
        if ($contenu === null) {
            @unlink($chemin);
        } else {
            file_put_contents($chemin, $contenu);
        }

        exec(sprintf('bash %s %s 2>&1', escapeshellarg($this->racine(self::GARDE)), escapeshellarg($chemin)), $sortie, $code);
        @unlink($chemin);

        return [$code, implode("\n", $sortie)];
    }

    public function test_une_recette_avec_fedapay_en_live_est_refusee(): void
    {
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=staging\nFEDAPAY_ENVIRONMENT=live\nFEDAPAY_SECRET_KEY=sk_sandbox_x\n");

        $this->assertSame(1, $code);
        $this->assertStringContainsString('argent réel', $sortie);
    }

    /** L'environnement peut dire sandbox et une clé être live : la clé compte aussi. */
    public function test_une_recette_avec_une_cle_live_est_refusee_meme_en_environnement_sandbox(): void
    {
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=staging\nFEDAPAY_ENVIRONMENT=sandbox\nFEDAPAY_PUBLIC_KEY=\"pk_live_abc\"\n");

        $this->assertSame(1, $code);
        $this->assertStringContainsString('clé FedaPay « live »', $sortie);
    }

    public function test_une_recette_en_sandbox_passe(): void
    {
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=staging\nFEDAPAY_ENVIRONMENT=sandbox\nFEDAPAY_SECRET_KEY='sk_sandbox_x'\nFEDAPAY_PUBLIC_KEY=pk_sandbox_y\n");

        $this->assertSame(0, $code, $sortie);
        $this->assertStringContainsString('hors production (staging)', $sortie);
    }

    /** Une recette sans aucune clé FedaPay (pas encore configurée) passe aussi : l'absence n'est pas un danger. */
    public function test_une_recette_sans_cle_fedapay_passe(): void
    {
        [$code] = $this->garde(self::CLE . "APP_ENV=staging\n");

        $this->assertSame(0, $code);
    }

    public function test_la_production_en_live_passe_et_en_sandbox_avertit_sans_bloquer(): void
    {
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nFEDAPAY_SECRET_KEY=sk_live_x\n");
        $this->assertSame(0, $code, $sortie);
        $this->assertStringNotContainsString('⚠️', $sortie);

        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=sandbox\n");
        $this->assertSame(0, $code, 'une production en sandbox est un avertissement, pas un refus');
        $this->assertStringContainsString('ne seront pas réels', $sortie);
    }

    /** S77 (T7) — sans API_KEY, l'API refuserait tout : on s'arrête avant la coupure, en recette comme en production. */
    public function test_un_env_sans_api_key_est_refuse_dans_tous_les_environnements(): void
    {
        foreach (['staging', 'production'] as $env) {
            [$code, $sortie] = $this->garde("APP_ENV=$env\nFEDAPAY_ENVIRONMENT=sandbox\n");
            $this->assertSame(1, $code, "$env : $sortie");
            $this->assertStringContainsString('API_KEY absente', $sortie);
            $this->assertStringContainsString('random_bytes', $sortie, 'le message dit comment la générer');
        }

        [$code, $sortie] = $this->garde("APP_ENV=production\nAPI_KEY=\n");
        $this->assertSame(1, $code, 'une ligne vide vaut une absence');
        $this->assertStringContainsString('API_KEY absente', $sortie);
    }

    /** S77 (T7) — la clé publique du socle We Courier n'est pas une clé : toutes ses installations la connaissent. */
    public function test_la_cle_publique_du_socle_est_refusee(): void
    {
        [$code, $sortie] = $this->garde("APP_ENV=production\nAPI_KEY=\"123456rx-ecourier123456\"\nFEDAPAY_ENVIRONMENT=live\n");

        $this->assertSame(1, $code);
        $this->assertStringContainsString('clé publique du socle', $sortie);
    }

    /** S88 — sans APP_INSTALLED=yes le site ne sert que l'installateur, dont l'action finale recrée la base. */
    public function test_un_env_sans_app_installed_est_refuse_dans_tous_les_environnements(): void
    {
        foreach (['staging', 'production'] as $env) {
            [$code, $sortie] = $this->garde("APP_ENV=$env\nAPI_KEY=blk_0123456789abcdef\nFEDAPAY_ENVIRONMENT=sandbox\n");
            $this->assertSame(1, $code, "$env : $sortie");
            $this->assertStringContainsString('APP_INSTALLED', $sortie);
            $this->assertStringContainsString('recrée la base', $sortie);
        }

        [$code, $sortie] = $this->garde("APP_ENV=production\nAPI_KEY=blk_0123456789abcdef\nAPP_INSTALLED=no\n");
        $this->assertSame(1, $code, 'une autre valeur que « yes » vaut une absence');
        $this->assertStringContainsString('(no)', $sortie);
    }

    /** S97 — le limiteur de connexion (S96) compte dans le cache : un cache `array` ne compte rien, la force brute passerait. */
    public function test_un_cache_non_partage_est_refuse_le_limiteur_de_connexion_ne_compterait_rien(): void
    {
        foreach (['staging', 'production'] as $env) {
            foreach (['array', 'null'] as $pilote) {
                [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=$env\nFEDAPAY_ENVIRONMENT=sandbox\nCACHE_DRIVER=$pilote\n");
                $this->assertSame(1, $code, "$env / $pilote : $sortie");
                $this->assertStringContainsString("CACHE_DRIVER=$pilote", $sortie);
                $this->assertStringContainsString('limiteur de connexion', $sortie);
            }
        }

        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nCACHE_DRIVER=file\n");
        $this->assertSame(0, $code, "file : $sortie");
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nCACHE_DRIVER=database\n");
        $this->assertSame(0, $code, "database : $sortie");
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\n");
        $this->assertSame(0, $code, 'absent vaut file : ' . $sortie);
    }

    /** S99 — le mode debug montre la pile et l'environnement à qui provoque une erreur : refusé en production, dit en recette. */
    public function test_le_mode_debug_est_refuse_en_production_et_signale_en_recette(): void
    {
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nAPP_DEBUG=true\n");
        $this->assertSame(1, $code, $sortie);
        $this->assertStringContainsString('APP_DEBUG=true', $sortie);
        $this->assertStringContainsString('APP_DEBUG=false', $sortie, 'le message dit quoi faire');

        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nAPP_DEBUG=false\n");
        $this->assertSame(0, $code, "false : $sortie");
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\n");
        $this->assertSame(0, $code, 'absent vaut false : ' . $sortie);

        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=staging\nFEDAPAY_ENVIRONMENT=sandbox\nAPP_DEBUG=true\n");
        $this->assertSame(0, $code, 'une recette se débogue : ' . $sortie);
        $this->assertStringContainsString('⚠️', $sortie);
        $this->assertStringContainsString('APP_DEBUG=true', $sortie);
    }

    /** S132 — un cookie de session sans `secure` part en clair : `false` refusé en production. */
    public function test_le_cookie_de_session_non_securise_est_refuse_en_production(): void
    {
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nSESSION_SECURE_COOKIE=false\n");
        $this->assertSame(1, $code, $sortie);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=false', $sortie);

        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\nSESSION_SECURE_COOKIE=true\n");
        $this->assertSame(0, $code, "true : $sortie");
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=production\nFEDAPAY_ENVIRONMENT=live\n");
        $this->assertSame(0, $code, 'absent suit APP_URL : ' . $sortie);
        [$code, $sortie] = $this->garde(self::CLE . "APP_ENV=staging\nFEDAPAY_ENVIRONMENT=sandbox\nSESSION_SECURE_COOKIE=false\n");
        $this->assertSame(0, $code, 'une recette en http reste possible : ' . $sortie);
    }

    /** S132 — sans valeur, le cookie est `secure` dès que l'application se sert en https. */
    public function test_le_cookie_de_session_suit_le_https_de_app_url(): void
    {
        $avant = [$_ENV['APP_URL'] ?? null, $_SERVER['APP_URL'] ?? null];
        try {
            foreach (['https://beninlink.app' => true, 'http://localhost' => false] as $url => $attendu) {
                $_ENV['APP_URL'] = $_SERVER['APP_URL'] = $url;
                unset($_ENV['SESSION_SECURE_COOKIE'], $_SERVER['SESSION_SECURE_COOKIE']);
                $config = require config_path('session.php');
                $this->assertSame($attendu, $config['secure'], "APP_URL={$url}");
            }
        } finally {
            [$_ENV['APP_URL'], $_SERVER['APP_URL']] = $avant;
        }
    }

    public function test_un_env_absent_est_refuse(): void
    {
        [$code, $sortie] = $this->garde(null);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Aucun .env', $sortie);
    }
}
