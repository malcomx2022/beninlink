<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S58 — le SIXIÈME filet : la surface de recherche.
 *
 * Les cinq filets précédents laissaient passer la même forme, et le trou était
 * structurel — chacun l'excluait par sa propre définition :
 *
 * | filet | ce qu'il énumère | pourquoi il ne voyait pas cette forme |
 * |---|---|---|
 * | `IsolationCoverageTest` (S7) | l'API v10 | ce sont des routes web |
 * | `WebIsolationCoverageTest` (S28) | les routes **à paramètre d'URL** | celles-ci n'en ont aucun |
 * | `BodyIdentifierCoverageTest` (S38) | `POST`/`PUT`/`PATCH`/`DELETE` | celles-ci sont des `GET` |
 * | `WebAdminPermissionCoverageTest` (S44) | les droits exigés sous `admin/*` | le droit était bien exigé |
 * | `OffRequestScopeCoverageTest` (S56) | la surface **hors** requête | celles-ci sont dans une requête |
 *
 * Un `GET` **sans paramètre d'URL** dont l'identifiant voyage dans la chaîne de
 * requête tombait donc entre les six mailles. Ce n'était pas une hypothèse : le
 * constat a été posé et **mesuré** à la fin de S57, à partir de
 * `ParcelRepository::parcelSearchs()` — qui rendait le nom, le téléphone et
 * l'adresse des clients de toutes les sociétés.
 *
 * La mesure d'ouverture : **203** routes `GET` sans paramètre d'URL servies par
 * un contrôleur de l'application, dont **46** lisent un champ de la requête.
 *
 * ⚠️ **Ce filet ne juge pas.** Il ne dit pas qu'une route est sûre, et il ne
 * cherche aucune garde — un marqueur de garde qui se trompe ne fait pas du
 * bruit, il fait **silence**. Il dit seulement qu'aucune route de cette forme
 * ne peut être ajoutée sans qu'on ait écrit ce qu'on fait de sa portée.
 *
 * ⚠️ **Le niveau 3 a été construit puis ABANDONNÉ.** J'ai outillé la résolution
 * des fonctions d'aide globales de `app/Http/Helper/Helper.php`, en pensant
 * qu'un contrôleur pouvait y cacher ses lectures. Mesure : **0** route n'est vue
 * par ce niveau seul. Il n'est donc pas ici. Un mécanisme qui ne change rien ne
 * se garde pas au motif qu'il a coûté du travail.
 */
class SearchSurfaceCoverageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    /** Routes dont la portée est PROUVÉE par un test qui atteint la ressource d'en face. */
    private const PROUVEES = [
        // S58 — le seul vrai défaut des 46, et il tenait en un mot. L'écran
        // d'impression des virements lisait `whereIn('id', $request->ids)` sans
        // périmètre quand son jumeau `bankTransactionPrint()` écrit
        // `BankTransaction::companywise()->whereIn(...)`. La vue rend, pour le
        // compte source ET le compte destinataire, le numéro de compte, la
        // banque, l'agence, le mobile, plus le nom et l'e-mail du titulaire.
        'GET admin/fund-transfer/search/flter/print' => SearchSurfaceScopeTest::class,

        // S59 — quatre lignes sortent de l'arriere, chacune par sabotage de sa
        // propre garde (rouge = le test la tient).
        //
        // `mhd-reports` portait DEUX lectures nues que l'instrument de S57 ne
        // pouvait pas voir : `Hub::find($request->hub_id)` et
        // `DeliveryMan::find($request->delivery_man_id)`, chacune juste au-dessus
        // d'une soeur `companywise()` portant sur le MEME identifiant.
        //
        // Les trois `filter` avaient ete LUS en S58 et juges bornes — ils ecrivent
        // `where('company_id', settings()->id)` a la main. Ils restaient pourtant
        // a l'arriere, parce que lu n'est pas prouve. Ils le sont maintenant.
        'GET admin/reports/mhd-reports' => NeighbouringGuardScopeTest::class,
        'GET admin/users/filter' => NeighbouringGuardScopeTest::class,
        'GET admin/hubs/filter' => NeighbouringGuardScopeTest::class,
        'GET admin/deliveryman/filter' => NeighbouringGuardScopeTest::class,
    ];

    /**
     * Routes dont le champ lu ne désigne PAS une ressource de locataire.
     * Chacune porte son motif : c'est lui qu'on relit quand la route change.
     */
    private const EXEMPTEES = [
        'GET finish' => 'installateur : `user_name`, `email`, `login_password` et `purchase_code` créent la PREMIÈRE société ; il n\'y a pas encore de locataire dont sortir',
        'GET subscription/payment' => 'S12 — `plan_id` désigne un plan de la PLATEFORME, dont le périmètre est la plateforme',
        'GET subscription/success' => 'idem — retour de paiement d\'abonnement ; la société vient de la session, pas de la requête',
        'GET super-admin/subscription/history' => 'surface SUPER-ADMINISTRATEUR : il administre les sociétés, il n\'est pas dans l\'une d\'elles',
        'GET admin/subscription/history' => 'S58 — les deux `where` vivent dans la MÊME fermeture, donc ils se conjuguent : `company_id = settings()->id AND company_id = $request->company_id` rend un ensemble VIDE si la société demandée n\'est pas la sienne. Aucun oracle : société étrangère et société inexistante rendent toutes deux le vide. Et la vue réserve le sélecteur de sociétés au `SUPER_ADMIN` (`subscription_history.blade.php`, ligne 28)',
        'GET tracking' => 'S58 — suivi PUBLIC. `ParcelRepository::parcelTracking()` borne par `if(tenant()): where(company_id, settings()->id)`. Hors locataire c\'est le site central, et l\'absence de portée y est le comportement voulu',
        'GET fedapay/callback' => 'S58 — retour de la passerelle. La `reference` est un jeton OPAQUE émis par FedaPay, et la méthode ne rend RIEN du dossier : un message et une redirection, puis la vue reçoit la référence que l\'appelant a lui-même fournie. L\'API compagnonne `status()`, juste en dessous, est bornée par marchand',
        'GET admin/payout/merchant/payout' => 'S52 (règle filtre/fetch) — `companywise()->where(\'merchant_id\', $merchant_id)` : l\'identifiant étranger FILTRE une requête DÉJÀ bornée, il ne sert pas à aller chercher. Un marchand d\'en face rend l\'ensemble vide',
        'GET admin/reports/merchnat-hub-delivery-reports-print-page' => 'S58 — les identifiants de colis ne sont pas lus par le contrôleur mais par les aides globales `parcelsStatus()` et `idWiseParcels()`, qui écrivent toutes deux `Parcel::companywise()->whereIn(\'id\', ...)` (app/Http/Helper/Helper.php)',
    ];

    /**
     * L'arriéré de ce filet, à son ouverture.
     *
     * Ce n'est PAS une liste de failles. C'est la liste des routes dont personne
     * n'a encore écrit ce qu'il advient d'un champ étranger. Plusieurs ont été
     * LUES pendant S58 et paraissent bornées — les trois `filter` des comptes,
     * des entrepôts et des livreurs écrivent `where('company_id', settings()->id)`
     * à la main ; `bulkParcels()` a été fermée en S38. Mais **lu n'est pas
     * prouvé**, et ce filet ne connaît que la preuve.
     *
     * Le mouvement autorisé est un seul : retirer une ligne d'ici et l'inscrire
     * dans `PROUVEES` avec le test qui l'établit — ou dans `EXEMPTEES` avec son
     * motif — en baissant le plafond d'autant.
     *
     * **S59 : 36 → 32.** Les trois `filter` mentionnés ci-dessus sont sortis —
     * non pas parce qu'on les avait relus, mais parce qu'un test atteint
     * désormais la ligne d'en face et échoue si la garde saute. C'est le chemin qui a mené
     * l'arriéré de `WebIsolationCoverageTest` de 171 à 0, et celui de
     * `BodyIdentifierCoverageTest` de 90 à 0.
     */
    private const HERITAGE = [
        'GET admin/accounts/filter',
        'GET admin/bank-transaction/filter/print',
        'GET admin/bank-transaction/specific/search',
        'GET admin/customs/alerts',
        'GET admin/delivery-charge/filter',
        'GET admin/delivery-zone/grid',
        'GET admin/expense/filter',
        'GET admin/fund-transfer/filter',
        'GET admin/fund-transfer/specific/search',
        'GET admin/income/filter',
        'GET admin/paid/invoice/syscohada-journal',
        'GET admin/parcel/bulkassign/print',
        'GET admin/parcel/filter',
        'GET admin/parcel/multiple/print/label',
        'GET admin/parcel/specific/search',
        'GET admin/payment/merchant/filter',
        'GET admin/reports/parcel-filter-reports',
        'GET admin/reports/parcel-filter-total-summery',
        'GET admin/reports/parcel-wise-profit-reports',
        'GET admin/reports/reports-salary-reports',
        'GET admin/reports/salary-report-print',
        'GET admin/salarys/filter',
        'GET admin/wallet-request',
        'GET dashboard',
        'GET facebook/login',
        'GET google/login',
        'GET merchant/my-wallet',
        'GET merchant/parcel/file-export',
        'GET merchant/parcel/filter',
        'GET merchant/reports/parcel-filter-reports',
        'GET merchant/reports/total-summery-filter',
        'GET super-admin/reporting',
    ];

    /** Le cliquet. Ne monte jamais. */
    private const PLAFOND_HERITAGE = 32;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    /* ────────────────────────── l'énumération ───────────────────────────── */

    /** Les champs qu'un morceau de source lit dans la requête. */
    private function champsLus(string $source): array
    {
        $noms = [];

        if (preg_match_all('/(?:\$request|request\(\))\s*->\s*([A-Za-z_][A-Za-z0-9_]*)/', $source, $m)) {
            $noms = array_merge($noms, $m[1]);
        }
        if (preg_match_all('/(?:\$request|request\(\))\s*->\s*(?:input|get|post|query)\(\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]/', $source, $m)) {
            $noms = array_merge($noms, $m[1]);
        }

        return array_values(array_unique(array_diff($noms, self::BRUIT)));
    }

    /**
     * Ce qui n'est pas un champ mais une méthode de l'objet `Request`.
     *
     * Sans cette liste, `$request->ajax()`, `$request->all()` ou `$request->user()`
     * feraient entrer dans le filet la quasi-totalité des 203 routes, et le
     * recensement deviendrait illisible — donc inutilisé.
     */
    private const BRUIT = [
        'all', 'only', 'except', 'has', 'filled', 'ajax', 'isMethod', 'method',
        'url', 'fullUrl', 'path', 'user', 'file', 'hasFile', 'validate',
        'validated', 'merge', 'session', 'route', 'input', 'get', 'post',
        'query', 'wantsJson', 'expectsJson', 'header', 'ip', 'bearerToken',
    ];

    private function source(\ReflectionMethod $methode): string
    {
        $fichier = $methode->getFileName();

        if (!$fichier || !is_readable($fichier)) {
            return '';
        }

        $lignes = file($fichier);

        return implode('', array_slice(
            $lignes,
            $methode->getStartLine() - 1,
            $methode->getEndLine() - $methode->getStartLine() + 1,
        ));
    }

    /**
     * Les routes `GET` SANS paramètre d'URL qui lisent un champ de la requête.
     *
     * Le second niveau résout `$this->prop->methode()` sur la **propriété réelle**
     * de l'instance du contrôleur, comme le filet de S38 — et pour la même
     * raison : sans lui, `GET admin/users/filter` sort de l'énumération, son
     * contrôleur se contentant de passer `$request` au dépôt.
     */
    private function routesDeRecherche(): array
    {
        $trouvees = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if ($route->parameterNames() !== []) {
                continue; // domaine de WebIsolationCoverageTest
            }

            $uri = $route->uri();

            if (Str::startsWith($uri, 'api/')) {
                continue; // filet S7
            }

            if (!in_array('GET', $route->methods(), true)) {
                continue; // domaine de BodyIdentifierCoverageTest
            }

            $action = ltrim($route->getActionName(), '\\');

            if (!Str::startsWith($action, 'App\\') || !str_contains($action, '@')) {
                continue; // fermeture, ou route montée par un paquet
            }

            [$classe, $methode] = explode('@', $action);

            if (!class_exists($classe) || !method_exists($classe, $methode)) {
                continue;
            }

            $source = $this->source(new \ReflectionMethod($classe, $methode));
            $champs = array_merge(
                $this->champsLus($source),
                $this->lecturesDesDepots($classe, $source),
            );

            if ($champs === []) {
                continue;
            }

            $trouvees['GET ' . $uri] = $route;
        }

        return $trouvees;
    }

    /** Ce que lisent les méthodes de dépôt appelées depuis cette méthode de contrôleur. */
    private function lecturesDesDepots(string $classe, string $source): array
    {
        try {
            $controleur = app($classe);
        } catch (\Throwable) {
            return []; // un contrôleur que le conteneur ne sait pas monter : niveau 1 seul
        }

        if (!preg_match_all('/\$this->(\w+)->(\w+)\s*\(/', $source, $appels, PREG_SET_ORDER)) {
            return [];
        }

        $reflet = new \ReflectionObject($controleur);
        $noms = [];

        foreach ($appels as [, $propriete, $methode]) {
            if (!$reflet->hasProperty($propriete)) {
                continue;
            }

            $valeur = $reflet->getProperty($propriete)->getValue($controleur);

            if (!is_object($valeur) || !method_exists($valeur, $methode)) {
                continue;
            }

            $noms = array_merge($noms, $this->champsLus(
                $this->source(new \ReflectionMethod($valeur, $methode)),
            ));
        }

        return $noms;
    }

    /* ─────────── 1. tout est classé : une route neuve ne passe pas ────────── */

    public function test_every_search_route_reading_a_request_field_is_classified(): void
    {
        $routes = $this->routesDeRecherche();

        $this->assertGreaterThan(
            30,
            count($routes),
            'les routes de locataire ne sont pas montées, ou la détection ne trouve plus rien : le test ne mesure rien',
        );

        $classees = array_merge(
            array_keys(self::PROUVEES),
            array_keys(self::EXEMPTEES),
            self::HERITAGE,
        );

        $nonClassees = array_values(array_diff(array_keys($routes), $classees));
        sort($nonClassees);

        $this->assertSame([], $nonClassees, "Routes GET sans paramètre d'URL lisant un champ de la requête "
            . "et non classées. Pour chacune : prouver la portée par un test qui atteint la ressource d'une "
            . "autre société (PROUVEES), ou dire pourquoi le champ ne désigne pas une ressource de locataire "
            . "(EXEMPTEES). L'arriéré est fermé aux nouvelles venues :\n - " . implode("\n - ", $nonClassees));
    }

    /** Et l'inverse : une déclaration qui ne correspond plus à rien s'en va. */
    public function test_no_declaration_points_to_a_route_that_no_longer_exists(): void
    {
        $connues = array_keys($this->routesDeRecherche());

        foreach (['PROUVEES' => self::PROUVEES, 'EXEMPTEES' => self::EXEMPTEES] as $nom => $liste) {
            foreach (array_keys($liste) as $cle) {
                $this->assertContains($cle, $connues, "{$nom} déclare une route qui n'existe plus, ou qui ne lit "
                    . "plus de champ de requête : {$cle}");
            }
        }

        $fantomes = array_values(array_diff(self::HERITAGE, $connues));
        $this->assertSame([], $fantomes, "HERITAGE déclare des routes qui n'existent plus :\n - " . implode("\n - ", $fantomes));
    }

    /* ─────────── 2. le cliquet : l'arriéré ne peut que rétrécir ──────────── */

    public function test_the_inherited_backlog_can_only_shrink(): void
    {
        $this->assertLessThanOrEqual(
            self::PLAFOND_HERITAGE,
            count(self::HERITAGE),
            "L'arriéré de ce filet ne remonte pas. Une route nouvelle ne s'y range pas : elle se prouve "
            . '(PROUVEES) ou se motive (EXEMPTEES).',
        );

        $this->assertSame([], array_intersect(self::HERITAGE, array_keys(self::PROUVEES)),
            'une route prouvée reste inscrite dans l\'arriéré : retirer la ligne et baisser le plafond');
    }

    /**
     * F6, repris des deux autres filets : une déclaration qui pointe un test
     * incapable d'atteindre la ressource d'un autre compte ne prouve rien.
     */
    public function test_every_declared_test_can_actually_reach_a_resource(): void
    {
        $incapables = [];

        foreach (self::PROUVEES as $cle => $couvrant) {
            if (!class_exists($couvrant)) {
                $incapables[] = $cle . ' → classe absente : ' . $couvrant;
                continue;
            }
            if (!in_array(RefreshDatabase::class, class_uses_recursive($couvrant), true)) {
                $incapables[] = $cle . ' → ' . class_basename($couvrant) . ' n\'a pas de base migrée';
            }
        }

        $this->assertSame([], $incapables, "Couverture déclarée par un test incapable d'atteindre la "
            . "ressource d'une autre société :\n - " . implode("\n - ", $incapables));
    }

    /**
     * Le second niveau doit rester allumé.
     *
     * `GET admin/users/filter` est le témoin : son contrôleur ne lit AUCUN champ,
     * il passe `$request` au dépôt, et c'est `UserRepository::filter()` qui lit
     * `name`, `email` et `phone`. Si la résolution des dépôts casse — un
     * contrôleur que le conteneur ne monte plus, une propriété renommée — cette
     * route disparaît silencieusement, et le filet se remet à ne voir que ce que
     * son premier niveau montre.
     */
    public function test_the_second_level_still_sees_a_controller_that_reads_no_field(): void
    {
        $temoin = 'GET admin/users/filter';

        $controleur = new \ReflectionMethod(
            \App\Http\Controllers\Backend\UserController::class,
            'filter',
        );

        $this->assertSame(
            [],
            $this->champsLus($this->source($controleur)),
            'le contrôleur témoin lit maintenant un champ : en choisir un autre qui n\'en lit pas',
        );

        $this->assertArrayHasKey($temoin, $this->routesDeRecherche(),
            'la résolution des dépôts est cassée : le filet a perdu le domaine que son contrôleur ne montre pas');
    }

    /**
     * Le filet doit voir la forme pour laquelle il a été construit.
     *
     * `GET admin/fund-transfer/search/flter/print` est le défaut fondateur : un
     * `GET`, aucun paramètre d'URL, l'identifiant dans la chaîne de requête. Si
     * cette route sort de l'énumération, le filet a perdu sa raison d'être — et
     * il le dirait ici avant de le taire ailleurs.
     */
    public function test_the_founding_shape_is_still_enumerated(): void
    {
        $this->assertArrayHasKey('GET admin/fund-transfer/search/flter/print', $this->routesDeRecherche(),
            'le filet ne voit plus la route qui l\'a fait naître');
    }
}
