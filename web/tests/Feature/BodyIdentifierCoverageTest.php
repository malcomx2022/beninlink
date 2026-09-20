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
 * S38 — le filet des écritures dont l'identifiant vit dans le CORPS.
 *
 * `WebIsolationCoverageTest` énumère les routes **à paramètre d'URL**. C'est ce
 * qui lui donne sa liste de travail, et c'est aussi sa limite : une route comme
 * `POST admin/parcel/delivery-man/assign/cancel` ne porte aucun paramètre —
 * l'identifiant du colis arrive dans `$request->parcel_id`. Elle n'apparaît
 * dans aucune des quatre listes de l'autre filet, et n'y apparaîtra jamais.
 *
 * Ce n'était pas une hypothèse. Sur dix passes d'arriéré, cinq constats sont
 * tombés sur cette forme — `UserRepository::update`, `permissionUpdate`,
 * `DeliveryManRepository::update`, la création d'un compte avec le rôle d'un
 * autre, la reprise d'un bulletin de paie — et chaque fois **par hasard**, en
 * suivant un appel depuis une autre piste. Aucun mécanisme ne les cherchait.
 *
 * Ce filet-ci les cherche. Il énumère les **contrôleurs**, pas les routes :
 *
 * 1. il retient les routes d'écriture (`POST`, `PUT`, `PATCH`, `DELETE`) **sans
 *    paramètre d'URL** — le domaine exact que l'autre filet ne voit pas ;
 * 2. il lit la source de la méthode de contrôleur, **et celle de la méthode de
 *    dépôt qu'elle appelle** — résolue sur la propriété réelle de l'instance,
 *    pas devinée par nom ;
 * 3. il retient celles qui lisent un identifiant dans la requête ;
 * 4. il exige que chacune soit classée : prouvée, exemptée avec motif, ou
 *    inscrite à l'arriéré — dont le compte est plafonné et ne peut que baisser.
 *
 * **Le second niveau n'est pas un raffinement.** Sans lui, `POST
 * admin/assign-pickup/bulk` sort de l'énumération : son contrôleur passe
 * `$request` tel quel au dépôt sans jamais lire d'identifiant lui-même. C'est
 * précisément une des sept méthodes en lot corrigées par S38 — celle qui
 * acceptait une liste d'identifiants arbitraires. Un filet qui ne regarderait
 * que le contrôleur aurait reconduit l'angle mort qu'il prétend fermer.
 *
 * ⚠️ **Ce que ce filet ne dit pas.** Il ne dit pas que ces routes sont isolées.
 * Il dit qu'aucune ne peut plus être ajoutée sans qu'on ait écrit ce qu'on a
 * fait de sa portée. C'est la même promesse que l'autre filet, sur la moitié de
 * la surface qui lui échappait.
 */
class BodyIdentifierCoverageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    /** Les verbes qui écrivent. Une lecture hors périmètre est grave ; une écriture l'est plus. */
    private const ECRITURES = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** Routes dont la portée est PROUVÉE par un test qui atteint la ressource d'en face. */
    private const PROUVEES = [
        // S38 — les sept chemins en lot et les neuf annulations de statut
        'POST admin/assign-pickup/bulk' => ParcelBulkScopeTest::class,
        'POST admin/parcel/assign-return-to-merchant-bulk' => ParcelBulkScopeTest::class,
        'POST admin/parcel/transfer-to-hub-multiple-parcel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/delivery-man-assign-multiple-parcel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/received-by-multiple-hub' => ParcelBulkScopeTest::class,
        'POST admin/parcel/return-assign-to-merchant' => ParcelBulkScopeTest::class,
        'POST admin/parcel/pickup-man/assigned/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/pickup-reschedule/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/pickup/received/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/transfer-to-hub/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/received-by-hub/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/delivery-man/assign/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/delivery-re-scheule/cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/return-to-qourier-cancel' => ParcelBulkScopeTest::class,
        'POST admin/parcel/return-assign-re-schedule-to-merchant/cancel' => ParcelBulkScopeTest::class,
        // S39 — les aides AJAX des colis (1re passe sur l'arriere de S38)
        'POST admin/parcel/merchant/shops' => ParcelAjaxHelpersScopeTest::class,
        'POST merchant/parcel/merchant/shops' => ParcelAjaxHelpersScopeTest::class,
        'POST admin/parcel/priority/update' => ParcelAjaxHelpersScopeTest::class,
        'POST admin/parcel/received-warehouse-hub-selected' => ParcelAjaxHelpersScopeTest::class,
        'POST admin/parcel/transfer-hub' => ParcelAjaxHelpersScopeTest::class,
        'POST admin/transertohub-selected-hub' => ParcelAjaxHelpersScopeTest::class,
        'POST admin/parcel/deliveryman/search' => ParcelAjaxHelpersScopeTest::class,
        // S35 — les comptes, le livreur, la fraude
        'POST admin/users/store' => UserAndSettingsScopeTest::class,
        'PUT admin/users/update' => UserAndSettingsScopeTest::class,
        'PUT admin/users/permissions/update' => UserAndSettingsScopeTest::class,
        'PUT admin/deliveryman/update' => UserAndSettingsScopeTest::class,
        'PUT admin/fraud/update' => UserAndSettingsScopeTest::class,
        // S31 — la paie
        'PUT admin/salary/update' => SalaryScopeTest::class,
    ];

    /**
     * Routes dont l'identifiant de corps ne désigne PAS une ressource de
     * locataire. Chacune porte son motif : c'est lui qu'on relit quand la route
     * change.
     */
    private const EXEMPTEES = [
        'POST super-admin/company/store' => 'surface super-administrateur : `plan_id` désigne un plan de la PLATEFORME, dont le périmètre est la plateforme elle-même',
        'PUT super-admin/company/update' => 'idem — le super-administrateur administre les sociétés, il n\'est pas dans l\'une d\'elles',
        'POST super-admin/company/subscription/switch/store' => 'idem — changement de plan d\'une société, acte de plateforme',
        'PUT super-admin/plan/update' => 'idem — le catalogue des plans est celui de la plateforme',
        'POST subscription/fedapay' => 'S12 — `plan_id` désigne un plan de la plateforme ; le montant et le compte d\'encaissement sont résolus côté serveur (FedaPaySubscriptionTest)',
        'POST subscription/success' => 'retour de paiement d\'abonnement : plan de plateforme, société déduite de la session',
        'PUT subscription/success' => 'même route, autre verbe',
        'PATCH subscription/success' => 'même route, autre verbe',
        'DELETE subscription/success' => 'même route, autre verbe',
        'POST admin/addons/activation' => 'bascule d\'un module de la plateforme : l\'identifiant désigne un module, pas une ressource de société',
        'PUT category/update' => 'S35 — le catalogue des catégories ne porte AUCUNE colonne `company_id` : il est commun à toutes les sociétés, et `UserAndSettingsScopeTest` l\'inscrit',
    ];

    /**
     * L'arriéré de ce filet, à son ouverture.
     *
     * Il n'est pas une liste de failles : c'est la liste des routes dont
     * personne n'a encore écrit ce qu'il advient d'un identifiant étranger.
     * Certaines sont sûrement correctes. Le point est qu'on ne le sait pas, et
     * que jusqu'ici rien ne le demandait.
     *
     * Le mouvement autorisé est un seul : retirer une ligne d'ici et l'inscrire
     * dans `PROUVEES` avec le test qui l'établit, en baissant le plafond
     * d'autant. C'est le chemin qui a mené l'arriéré de l'autre filet de 171 à 0.
     */
    private const HERITAGE = [
            'POST admin/assets/store',
            'POST admin/assign-pickup/parcel/search',
            'POST admin/assign-return-to-merchant/parcel/search',
            'POST admin/deliveryman/store',
            'POST admin/expense/store',
            'POST admin/fraud/store',
            'POST admin/get-merchant-cod',
            'POST admin/hub/cash-received-deliveryman/store',
            'POST admin/income/hub-user-accounts',
            'POST admin/income/store',
            'POST admin/merchant/account',
            'POST admin/merchant/delivery-charge/info',
            'POST admin/merchant/paymentinfo/bank/store',
            'POST admin/merchant/paymentinfo/mobile/store',
            'POST admin/merchant/paymentmethod/change',
            'POST admin/merchant/shops/store',
            'POST admin/merchant/store',
            'POST admin/parcel/clone-store',
            'POST admin/parcel/delivered',
            'POST admin/parcel/delivered/cancel',
            'POST admin/parcel/delivery-category',
            'POST admin/parcel/delivery-man-assign',
            'POST admin/parcel/delivery-reschedule',
            'POST admin/parcel/partial-delivered',
            'POST admin/parcel/partial-delivered/cancel',
            'POST admin/parcel/pickup-man/assigned',
            'POST admin/parcel/pickup/re-schedule',
            'POST admin/parcel/pickup/received',
            'POST admin/parcel/received-by-hub',
            'POST admin/parcel/received-warehouse',
            'POST admin/parcel/received-warehouse/cancel',
            'POST admin/parcel/recived-by-hub/search',
            'POST admin/parcel/return-assign-to-merchant-reschedule',
            'POST admin/parcel/return-assign-to-merchant/cancel',
            'POST admin/parcel/return-received-by-merchant',
            'POST admin/parcel/return-received-by-merchant/cancel',
            'POST admin/parcel/return-to-qourier',
            'POST admin/parcel/store',
            'POST admin/parcel/transfer-to-hub',
            'POST admin/payment/store',
            'POST admin/push-notification/store',
            'POST admin/request/hub/payment/store',
            'POST admin/salary/salary-generate/store',
            'POST admin/salary/search-account',
            'POST admin/salary/store',
            'POST admin/sms-send-settings/status',
            'POST admin/support/reply',
            'POST admin/support/store',
            'POST admin/todo/completed',
            'POST admin/todo/processing',
            'POST admin/todo/todo_add',
            'POST admin/wallet-request/recharge',
            'POST merchant/accounts/statements-filter',
            'POST merchant/fraud/store',
            'POST merchant/parcel/clone-store',
            'POST merchant/parcel/delivery-category',
            'POST merchant/parcel/store',
            'POST merchant/sign-up-store',
            'POST merchant/support/reply',
            'POST merchant/support/store',
            'PUT admin/asset-category/update',
            'PUT admin/assets/update',
            'PUT admin/currency/update',
            'PUT admin/delivery-category/update',
            'PUT admin/delivery-charge/update',
            'PUT admin/departments/update',
            'PUT admin/designations/update',
            'PUT admin/hub-payment/processed',
            'PUT admin/hub/cash-received-deliveryman/update',
            'PUT admin/hubs/update',
            'PUT admin/merchant/paymentinfo/bank/update',
            'PUT admin/merchant/paymentinfo/mobile/update',
            'PUT admin/merchant/shops/update',
            'PUT admin/packaging/update',
            'PUT admin/payment/processed',
            'PUT admin/payment/update',
            'PUT admin/roles/update',
            'PUT admin/salary/salary-generate/update',
            'PUT admin/support/update',
            'PUT admin/todo/update',
            'PUT merchant/accounts/payment-account/update',
            'PUT merchant/fraud/update',
            'PUT merchant/payment-request/update',
    ];

    /** Le cliquet. Ne monte jamais. */
    private const PLAFOND_HERITAGE = 83;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    /* ────────────────────────── l'énumération ───────────────────────────── */

    /** Un nom de champ qui désigne un identifiant : `id`, `ids`, `*_id`, `*_ids`, `*_ids_`. */
    private function estIdentifiant(string $nom): bool
    {
        return (bool) preg_match('/^(id|ids)$|_ids?_?$/i', $nom);
    }

    /** Les identifiants qu'un morceau de source lit dans la requête. */
    private function lecturesDidentifiant(string $source): array
    {
        $noms = [];

        if (preg_match_all('/(?:\$request|request\(\))\s*->\s*([A-Za-z_][A-Za-z0-9_]*)/', $source, $m)) {
            $noms = array_merge($noms, $m[1]);
        }
        if (preg_match_all('/(?:\$request|request\(\))\s*->\s*(?:input|get|post)\(\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]/', $source, $m)) {
            $noms = array_merge($noms, $m[1]);
        }

        return array_values(array_unique(array_filter($noms, fn ($n) => $this->estIdentifiant($n))));
    }

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
     * Les routes d'écriture SANS paramètre d'URL qui lisent un identifiant dans
     * la requête, « METHODE chemin » en clé.
     *
     * Le second niveau résout `$this->prop->methode()` sur la **propriété réelle**
     * de l'instance du contrôleur. Une résolution par nom de méthode — chercher
     * `store()` dans tous les dépôts injectés — rapprocherait n'importe quoi de
     * n'importe quoi et rendrait la liste illisible.
     */
    private function routesAIdentifiantDeCorps(): array
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

            $action = ltrim($route->getActionName(), '\\');

            if (!Str::startsWith($action, 'App\\') || !str_contains($action, '@')) {
                continue; // fermeture, ou route montée par un paquet
            }

            $verbes = array_intersect($route->methods(), self::ECRITURES);

            if ($verbes === []) {
                continue;
            }

            [$classe, $methode] = explode('@', $action);

            if (!class_exists($classe) || !method_exists($classe, $methode)) {
                continue;
            }

            $source = $this->source(new \ReflectionMethod($classe, $methode));
            $noms = $this->lecturesDidentifiant($source);
            $noms = array_merge($noms, $this->lecturesDesDepots($classe, $source));

            if ($noms === []) {
                continue;
            }

            foreach ($verbes as $verbe) {
                $trouvees[$verbe . ' ' . $uri] = $route;
            }
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

            $noms = array_merge($noms, $this->lecturesDidentifiant(
                $this->source(new \ReflectionMethod($valeur, $methode)),
            ));
        }

        return $noms;
    }

    /* ─────────── 1. tout est classé : une route neuve ne passe pas ────────── */

    public function test_every_write_route_with_a_body_identifier_is_classified(): void
    {
        $routes = $this->routesAIdentifiantDeCorps();

        $this->assertGreaterThan(
            80,
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

        $this->assertSame([], $nonClassees, "Routes d'écriture lisant un identifiant dans le CORPS de la requête "
            . "et non classées. Pour chacune : prouver la portée par un test qui atteint la ressource d'une autre "
            . "société (PROUVEES), ou dire pourquoi l'identifiant ne désigne pas une ressource de locataire "
            . "(EXEMPTEES). L'arriéré est fermé aux nouvelles venues :\n - " . implode("\n - ", $nonClassees));
    }

    /** Et l'inverse : une déclaration qui ne correspond plus à rien s'en va. */
    public function test_no_declaration_points_to_a_route_that_no_longer_exists(): void
    {
        $connues = array_keys($this->routesAIdentifiantDeCorps());

        foreach (['PROUVEES' => self::PROUVEES, 'EXEMPTEES' => self::EXEMPTEES] as $nom => $liste) {
            foreach (array_keys($liste) as $cle) {
                $this->assertContains($cle, $connues, "{$nom} déclare une route qui n'existe plus, ou qui ne lit "
                    . "plus d'identifiant dans le corps : {$cle}");
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
     * F6, repris de l'autre filet : une déclaration qui pointe un test incapable
     * d'atteindre la ressource d'un autre compte ne prouve rien. Il faut une base
     * migrée pour écrire cette ressource.
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
     * `POST admin/assign-pickup/bulk` est le témoin : son contrôleur ne lit aucun
     * identifiant, il passe `$request` au dépôt. Si la résolution des dépôts
     * casse — un contrôleur que le conteneur ne monte plus, une propriété
     * renommée — cette route disparaît silencieusement de l'énumération, et le
     * filet se remet à ne voir que ce que voyait déjà l'autre.
     */
    public function test_the_second_level_still_sees_a_controller_that_reads_no_identifier(): void
    {
        $temoin = 'POST admin/assign-pickup/bulk';

        $controleur = new \ReflectionMethod(
            \App\Http\Controllers\Backend\ParcelController::class,
            'AssignPickupBulk',
        );

        $this->assertSame(
            [],
            $this->lecturesDidentifiant($this->source($controleur)),
            'le contrôleur témoin lit maintenant un identifiant : en choisir un autre qui n\'en lit pas',
        );

        $this->assertArrayHasKey($temoin, $this->routesAIdentifiantDeCorps(),
            'la résolution des dépôts est cassée : le filet a perdu le domaine que son contrôleur ne montre pas');
    }
}
