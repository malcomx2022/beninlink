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
     * ⚠️ Une taille de page était un CONTRAT IMPLICITE, et c'est ce qui le
     * rendait dangereux : rien dans la réponse HTTP ne la portait. L'app en
     * déduisait « il en reste » d'une page pleine. Si un côté bougeait seul,
     * l'app s'arrêtait trop tôt — ou bouclait sur une page vide — **sans erreur**.
     *
     * Depuis **S78** la réponse porte `page` (`ApiPaginationContractTest`), et la
     * constante n'est plus que le **repli** d'un serveur d'avant S78. Elle doit
     * donc rester juste : les quatre paires concordent, ce test existe pour le
     * jour où l'une bougera.
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
            // S78 : la quatrième liste de l'app, qui écrivait son 10 en dur dans l'écran.
            'portefeuille' => [
                'WALLET_HISTORY_PER_PAGE', 'src/api/wallet.ts',
                'app/Repositories/Wallet/WalletRepository.php', 'get',
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

        // S78 : l'écran ne compare plus une longueur à une constante — il lit le
        // `hasMore` que le module d'API tire du bloc `page` du serveur.
        $this->assertMatchesRegularExpression('/setHasMore\((first|next)\.hasMore\)/', $ecran,
            "l'écran ne lit plus `hasMore` rendu par fetchCustomsAlerts() : il ne peut plus savoir s'il en reste");
        $this->assertDoesNotMatchRegularExpression('/length\s*>=\s*CUSTOMS_ALERTS_PER_PAGE/', $ecran,
            "l'écran redevine la fin de liste d'une constante : c'est le module d'API qui lit `page` (S78)");
        $this->assertStringContainsString('onEndReached', $ecran,
            "l'écran ne demande plus la page suivante : au-delà de la première, les alertes sont perdues en silence");

        // Et la page demandée doit VARIER : un `onEndReached` qui redemande la
        // page 1 boucle sans rien ajouter.
        $this->assertMatchesRegularExpression('/fetchCustomsAlerts\([^)]*page \+ 1\)/', $ecran,
            "l'écran ne demande jamais une page au-delà de la première");
    }

    /* ─────────── 2 bis. S78 : l'app lit `page` et garde le repli ─────────── */

    /**
     * Depuis S78 le serveur dit où finit la liste (`page` à la racine de
     * l'enveloppe). Les quatre modules d'API de l'app le lisent par `getPaged()`
     * et le réduisent par `src/api/pagination.ts`, qui préfère `current < last`
     * et retombe sur « page pleine » quand `page` manque. Un module qui
     * repasserait par `api.get()` perdrait le compteur sans qu'aucun écran ne
     * s'en aperçoive.
     */
    public function test_the_app_reads_the_page_block_and_keeps_the_full_page_fallback(): void
    {
        $regle = $this->source('src/api/pagination.ts');
        $this->assertStringContainsString('page.current < page.last', $regle, 'la règle S78');
        $this->assertStringContainsString('received >= perPage', $regle, 'le repli d’avant S78');

        $modules = [
            'src/api/customs.ts' => 'fetchCustomsAlerts',
            'src/api/notifications.ts' => 'fetchNotifications',
            'src/api/merchant.ts' => 'fetchInvoices',
            'src/api/wallet.ts' => 'fetchWalletHistory',
            // S78 : les boutiques paginaient par leur dépôt sans le dire ; l'app lit toutes les pages.
            'src/api/shops.ts' => 'fetchShopsPage',
        ];
        foreach ($modules as $fichier => $fonction) {
            $source = $this->source($fichier);
            $depart = strpos($source, "function {$fonction}(");
            $this->assertNotFalse($depart, "{$fichier} : {$fonction}() introuvable");
            $fin = strpos($source, "\n}", $depart);
            $corps = substr($source, $depart, $fin - $depart);

            $this->assertStringContainsString('api.getPaged<', $corps,
                "{$fonction}() ne lit plus le bloc `page` : repasser par api.getPaged()");
            $this->assertMatchesRegularExpression('/toPaged\(|hasNextPage\(/', $corps,
                "{$fonction}() n'applique plus la règle de src/api/pagination.ts");
        }

        // Les boutiques s'affichent entières : la liste parcourt toutes les pages.
        $this->assertStringContainsString('fetchAllPages(fetchShopsPage)', $this->source('src/api/shops.ts'),
            'fetchShops() ne lit plus toutes les pages : un marchand à onze boutiques en verrait dix');

        // Et le client garde `page` seulement s'il a la forme attendue.
        $client = $this->source('src/api/client.ts');
        $this->assertStringContainsString('export type ApiPage', $client);
        $this->assertStringContainsString("[p.current, p.per_page, p.last, p.total]", $client,
            'readPage() valide les quatre entiers avant de se fier au bloc');
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
