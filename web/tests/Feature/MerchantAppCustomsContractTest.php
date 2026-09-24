<?php

namespace Tests\Feature;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use Tests\TestCase;

/**
 * L'écran « Alertes douanières » de `mobile/` contre le contrat de `web/`.
 *
 * Ce filet suit le précédent du dépôt — `OpenApiSpecTest` lit
 * `mobile/src/api/endpoints.ts`, `ParcelStageTest` lit
 * `mobile/src/domain/parcelStatus.ts`. `mobile/` n'a **pas** de lanceur de
 * tests : PHPUnit est le seul endroit d'où un invariant de l'app peut être
 * mesuré, et la règle d'or (« `web/` est le contrat ») veut de toute façon que
 * ce soit le backend qui arbitre.
 *
 * Trois propriétés, et chacune vient d'un défaut CONSTATÉ dans l'écran :
 *
 *  1. la taille de page que l'app suppose est celle que le serveur sert ;
 *  2. l'écran PARCOURT les pages — il n'en lisait qu'une ;
 *  3. les niveaux et leurs couleurs sont ceux de `CustomsLevel` et de la charte
 *     — l'écran en peignait deux sur trois avec des couleurs réservées à autre
 *     chose.
 */
class MerchantAppCustomsContractTest extends TestCase
{
    /** Racine de l'app marchand, hors du projet Laravel. */
    private function app(string $chemin): string
    {
        return base_path('../mobile/' . $chemin);
    }

    private function source(string $chemin): string
    {
        $fichier = $this->app($chemin);
        $this->assertFileExists($fichier, "mobile/{$chemin}");

        return file_get_contents($fichier);
    }

    /** La taille de page d'une méthode de contrôleur ou de dépôt. */
    private function paginationDe(string $fichierPhp, string $methode): int
    {
        $src = file_get_contents(base_path($fichierPhp));
        $depart = strpos($src, "function {$methode}(");
        $this->assertNotFalse($depart, "{$fichierPhp}::{$methode}() est introuvable");

        // La méthode s'arrête à la suivante : on ne lit pas la pagination du voisin.
        $suivante = strpos($src, 'function ', $depart + 9);
        $corps = substr($src, $depart, $suivante === false ? null : $suivante - $depart);

        $this->assertSame(1, preg_match('/paginate\(\s*(\d+)/', $corps, $m),
            "{$methode}() ne pagine plus, ou pagine plusieurs fois : ce test ne la lit plus");

        return (int) $m[1];
    }

    /* ─────────── 1. l'app et le serveur comptent la même page ─────────── */

    /**
     * ⚠️ Une taille de page est un CONTRAT IMPLICITE, et c'est ce qui le rend
     * dangereux : rien dans la réponse HTTP ne la porte (l'enveloppe du projet
     * sert la collection sans les compteurs du paginateur). L'app en déduit
     * « il en reste » d'une page pleine. Si un côté bouge seul, l'app s'arrête
     * trop tôt — ou boucle sur une page vide — **sans erreur**.
     *
     * Les trois paires concordent aujourd'hui ; ce test existe pour le jour où
     * l'une bougera.
     */
    public function test_the_page_sizes_the_app_assumes_are_the_ones_the_server_serves(): void
    {
        $paires = [
            'douane' => [
                'CUSTOMS_ALERTS_PER_PAGE', 'src/api/customs.ts',
                'app/Http/Controllers/Api/V10/CustomsController.php', 'alerts',
            ],
            'notifications' => [
                'NOTIFICATIONS_PER_PAGE', 'src/api/notifications.ts',
                'app/Http/Controllers/Api/V10/NotificationController.php', 'index',
            ],
            'relevés' => [
                'INVOICES_PER_PAGE', 'src/api/merchant.ts',
                // `invoiceLists()` délègue à `get()` : c'est `get()` qui pagine.
                'app/Repositories/Invoice/InvoiceRepository.php', 'get',
            ],
        ];

        foreach ($paires as $nom => [$constante, $fichierTs, $fichierPhp, $methode]) {
            $this->assertSame(1,
                preg_match("/{$constante}\s*=\s*(\d+)/", $this->source($fichierTs), $m),
                "{$constante} est introuvable dans mobile/{$fichierTs}");

            $this->assertSame(
                $this->paginationDe($fichierPhp, $methode),
                (int) $m[1],
                "{$nom} : l'app suppose une page de {$m[1]}, le serveur en sert une autre",
            );
        }
    }

    /* ─────────── 2. l'écran parcourt les pages ─────────── */

    /**
     * ⚠️ LE test de ce lot, parce qu'il porte le défaut qui l'a ouvert.
     *
     * `customs/alerts` répond en `paginate(20)`, et l'écran appelait
     * `fetchCustomsAlerts(tab)` — page 1, et rien d'autre. `CUSTOMS_ALERTS_PER_PAGE`
     * était exporté depuis l'origine **sans être utilisé nulle part** : le
     * module d'API avait prévu la pagination, l'écran ne l'avait jamais prise.
     *
     * Un marchand à plus de vingt alertes en voyait vingt, sans rien qui le lui
     * dise — ni compteur, ni bouton, ni fin de liste. La perte était
     * **silencieuse**, sur un écran de conformité douanière.
     */
    public function test_the_customs_screen_pages_through_the_alerts(): void
    {
        $ecran = $this->source('app/(app)/customs.tsx');

        $this->assertStringContainsString('CUSTOMS_ALERTS_PER_PAGE', $ecran,
            "l'écran ne compare plus sa page à la taille servie : il ne peut plus savoir s'il en reste");
        $this->assertStringContainsString('onEndReached', $ecran,
            "l'écran ne demande plus la page suivante : au-delà de la première, les alertes sont perdues en silence");

        // Et la page demandée doit VARIER : un `onEndReached` qui redemande la
        // page 1 boucle sans rien ajouter.
        $this->assertMatchesRegularExpression('/fetchCustomsAlerts\([^)]*page \+ 1\)/', $ecran,
            "l'écran ne demande jamais une page au-delà de la première");
    }

    /* ─────────── 3. niveaux et couleurs ─────────── */

    /**
     * Les valeurs numériques appartiennent au contrat : l'app les recopie, elle
     * ne les choisit pas. `CustomsLevel` prévient d'ailleurs qu'on ne renumérote
     * pas — les alertes déjà émises portent la valeur en base.
     */
    public function test_the_app_copies_the_backend_levels_and_statuses(): void
    {
        $domaine = $this->source('src/domain/customsLevel.ts');

        $attendus = [
            'INFO' => CustomsLevel::INFO,
            'WARNING' => CustomsLevel::WARNING,
            'BLOCKING' => CustomsLevel::BLOCKING,
            'PENDING' => CustomsAlertStatus::PENDING,
            'RESOLVED' => CustomsAlertStatus::RESOLVED,
        ];

        foreach ($attendus as $nom => $valeur) {
            $this->assertSame(1, preg_match("/{$nom}:\s*(\d+)/", $domaine, $m),
                "l'app ne déclare plus {$nom}");
            $this->assertSame($valeur, (int) $m[1],
                "{$nom} : l'app dit {$m[1]}, le backend dit {$valeur}");
        }
    }

    /**
     * ⚠️ La charte NOMME ses trois couleurs douanières, et l'écran en utilisait
     * deux autres.
     *
     * `mobile/src/theme/colors.ts` écrit noir sur blanc : `danger` « réservé aux
     * erreurs et au niveau douanier BLOQUANT », `warning` « AVERTISSEMENT
     * douanier », `info` « INFO douanier ». L'écran peignait l'avertissement en
     * **ocre** — que la même charte réserve aux « actions clés uniquement
     * (bouton principal, montant à retenir) » — et l'info en **vert primaire**,
     * qui se lit « tout va bien » au lieu de « information ».
     *
     * Une couleur qui ment sur la gravité coûte plus cher qu'une couleur laide :
     * c'est un colis bloqué à la frontière qui ne se distingue pas d'un conseil.
     */
    public function test_a_customs_level_is_painted_with_its_charter_colour(): void
    {
        $domaine = $this->source('src/domain/customsLevel.ts');

        $this->assertSame(1, preg_match(
            '/function customsLevelColorName\([^)]*\):\s*([^{]+)\{(.+?)\n\}/s',
            $domaine, $m,
        ), 'la table des couleurs a changé de forme : ce test ne la lit plus');

        [$signature, $corps] = [$m[1], $m[2]];

        // La signature énumère les trois noms de charte, et rien d'autre.
        foreach (['danger', 'warning', 'info'] as $nom) {
            $this->assertStringContainsString("'{$nom}'", $signature,
                "le niveau « {$nom} » n'est plus dans les couleurs possibles");
        }
        foreach (['accent', 'primary', 'success'] as $interdit) {
            $this->assertStringNotContainsString("'{$interdit}'", $signature,
                "« {$interdit} » n'est pas une couleur de gravité douanière (voir colors.ts)");
        }

        // Et les trois noms existent vraiment dans la charte.
        $charte = $this->source('src/theme/colors.ts');
        foreach (['danger', 'warning', 'info'] as $nom) {
            $this->assertMatchesRegularExpression("/^\s*{$nom}:\s*'#/m", $charte,
                "la charte ne déclare plus « {$nom} »");
        }

        // Le plus grave l'emporte : un niveau inconnu n'est jamais atténué.
        $this->assertStringContainsString('>= CustomsLevel.BLOCKING', $corps,
            'un niveau au-dessus de BLOQUANT doit rester traité comme bloquant');
    }

    /**
     * ⚠️ CE TEST EXISTE PARCE QUE LE PRÉCÉDENT NE SUFFISAIT PAS, et c'est un
     * sabotage qui l'a montré.
     *
     * Le test ci-dessus lit `src/domain/customsLevel.ts`. J'y ai remis le
     * défaut d'origine — l'écran repeignant les niveaux en ocre et en vert,
     * table de domaine intacte — et la suite est restée **VERTE** : elle
     * mesurait le bon principe dans le mauvais fichier.
     *
     * Une table de correspondance juste ne protège rien tant qu'un écran peut
     * la contourner. Celui-ci doit donc la TRAVERSER.
     */
    public function test_the_screen_goes_through_that_table_instead_of_painting_levels_itself(): void
    {
        $ecran = $this->source('app/(app)/customs.tsx');

        $this->assertSame(1, preg_match('/function levelColor\([^)]*\)[^{]*\{(.+?)
\}/s', $ecran, $m),
            "la fonction de couleur de l'écran a changé de forme : ce test ne la lit plus");
        $corps = $m[1];

        $this->assertStringContainsString('customsLevelColorName', $corps,
            "l'écran choisit ses couleurs de gravité lui-même, hors de la table du domaine");

        // Restreint au corps de `levelColor` : `colors.primary` sert ailleurs
        // dans l'écran (l'onglet actif), légitimement.
        foreach (['colors.accent', 'colors.primary', 'colors.success'] as $interdit) {
            $this->assertStringNotContainsString($interdit, $corps,
                "« {$interdit} » ne dit pas une gravité douanière (voir colors.ts)");
        }
    }

    /**
     * Le tableau de bord signale les alertes EN COURS.
     *
     * `mobile/CLAUDE.md` les liste parmi son contenu (« KPIs, solde wallet,
     * **alerte douane**, colis récents ») ; seul un lien sans compteur y menait,
     * à côté d'un lien Notifications qui, lui, porte le sien.
     *
     * ⚠️ Honnêtement : c'est un contrôle de PRÉSENCE, pas une preuve de
     * comportement. Il mord si quelqu'un retire le compteur, pas si le compteur
     * se trompe — `mobile/` n'a pas de lanceur de tests qui puisse rendre
     * l'écran et le lire.
     */
    public function test_the_dashboard_surfaces_the_pending_customs_alerts(): void
    {
        $tableau = $this->source('app/(app)/index.tsx');

        $this->assertStringContainsString('fetchCustomsAlerts', $tableau,
            'le tableau de bord ne compte plus les alertes douanières en cours');
        $this->assertStringContainsString('CustomsAlertStatus.PENDING', $tableau,
            'le tableau de bord ne se limite plus aux alertes EN COURS : il compterait les traitées');
        $this->assertStringContainsString('customsLevelColorName', $tableau,
            'le tableau de bord ne dit plus la gravité — un blocage se lirait comme un conseil');
    }
}
