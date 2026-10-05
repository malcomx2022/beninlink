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
        // S40 — les aides AJAX des colis (1re passe sur l'arriere de S38)
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

        // S45 — les seize etapes de statut dont le perimetre est MESURE. Chaque
        // ligne a ete etablie de la meme facon : on retire la garde
        // `companywise()` de la methode, on relance le fichier nomme ici, et on
        // exige qu'il tombe. Un nom de test n'a jamais suffi — deux
        // attributions plausibles ont ete refusees parce qu'elles restaient
        // vertes sans la garde.
        'POST admin/parcel/pickup-man/assigned' => ParcelLifecycleTest::class,
        'POST admin/parcel/pickup/re-schedule' => ParcelLifecycleTest::class,
        'POST admin/parcel/pickup/received' => ParcelLifecycleTest::class,
        'POST admin/parcel/received-by-hub' => ParcelLifecycleTest::class,
        'POST admin/parcel/delivery-man-assign' => ParcelLifecycleTest::class,
        'POST admin/parcel/delivery-reschedule' => ParcelLifecycleTest::class,
        'POST admin/parcel/return-to-qourier' => ParcelLifecycleTest::class,
        'POST admin/parcel/transfer-to-hub' => ParcelLifecycleTest::class,
        'POST admin/parcel/received-warehouse' => ParcelAgentScopeTest::class,
        'POST admin/parcel/return-assign-to-merchant-reschedule' => ParcelAgentScopeTest::class,
        'POST admin/parcel/delivered' => DeliveryAccountingTest::class,
        'POST admin/parcel/partial-delivered' => PartialDeliveryAccountingTest::class,
        'POST admin/parcel/delivered/cancel' => DeliveryCancellationAccountingTest::class,
        'POST admin/parcel/received-warehouse/cancel' => DeliveryCancellationAccountingTest::class,
        'POST admin/parcel/return-assign-to-merchant/cancel' => DeliveryCancellationAccountingTest::class,
        'POST admin/parcel/return-received-by-merchant/cancel' => DeliveryCancellationAccountingTest::class,

        // S46 — les quatre portes de CREATION d'un colis. Leur identifiant de
        // corps n'est pas un, c'est une famille : le marchand facture, et les
        // catalogues qu'on applique — categorie, boutique, emballage. Chacune a
        // ete etablie par sabotage contre ce seul fichier : trois points
        // d'appel et quatre branches de garde, de chaque cote.
        'POST admin/parcel/store' => ParcelCatalogScopeTest::class,
        'POST admin/parcel/clone-store' => ParcelCatalogScopeTest::class,
        'POST merchant/parcel/store' => ParcelCatalogScopeTest::class,
        'POST merchant/parcel/clone-store' => ParcelCatalogScopeTest::class,

        // S49 — quatorze lignes sortent, et la moitie parce que les lots
        // precedents les avaient DEJA fermees sans que personne l'ait mesure.
        // Chaque attribution est etablie par sabotage contre le seul fichier
        // nomme : on retire la garde, on relance ce fichier, on exige qu'il
        // tombe. Deux sabotages ont d'abord menti — un bloc supprime laissait
        // une variable indefinie, et le `catch` rendait `false` pour la
        // mauvaise raison. C'est la GARDE qu'on sabote, pas le bloc autour.
        'POST admin/income/store' => AccountingCounterpartyScopeTest::class,
        'POST admin/expense/store' => AccountingCounterpartyScopeTest::class,
        'POST admin/salary/store' => AccountingCounterpartyScopeTest::class,
        'POST admin/salary/salary-generate/store' => AccountingCounterpartyScopeTest::class,
        'PUT admin/salary/salary-generate/update' => AccountingCounterpartyScopeTest::class,
        'POST admin/support/store' => CompanyCatalogScopeTest::class,
        'PUT admin/support/update' => CompanyCatalogScopeTest::class,
        'POST merchant/support/store' => CompanyCatalogScopeTest::class,
        'POST admin/merchant/paymentinfo/bank/store' => MerchantFamilyScopeTest::class,
        'POST admin/merchant/paymentinfo/mobile/store' => MerchantFamilyScopeTest::class,
        'PUT admin/merchant/paymentinfo/bank/update' => MerchantFamilyScopeTest::class,
        'PUT admin/merchant/paymentinfo/mobile/update' => MerchantFamilyScopeTest::class,
        'POST admin/merchant/shops/store' => MerchantFamilyScopeTest::class,
        'PUT admin/merchant/shops/update' => MerchantFamilyScopeTest::class,

        // ⚠️ S45 — CE QUE CETTE COLONNE NE DIT PAS. Elle nomme le test qui tient
        // l'identifiant de COLIS. Elle ne dit rien du SECOND identifiant que la
        // meme requete transporte — le livreur ou l'entrepot qu'on nomme au
        // passage. S38 avait inscrit cinq routes ci-dessus (`assign-pickup/bulk`,
        // `assign-return-to-merchant-bulk`, `return-assign-to-merchant`,
        // `delivery-man-assign-multiple-parcel`, `transfer-to-hub-multiple-parcel`)
        // alors que leur `delivery_man_id` n'avait AUCUN perimetre : on creditait
        // le solde d'un livreur d'une autre societe. `ParcelAgentScopeTest` ferme
        // cet axe-la pour les huit methodes concernees. Une route « prouvee »
        // l'est SUR L'AXE QUE SON TEST MESURE, pas dans l'absolu.

        // S50 — l'arriere relu : dix-huit routes deja closes par les lots precedents,
        // chacune mesuree par sabotage de sa propre garde (rouge = le test la tient)
        'PUT admin/asset-category/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/assets/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/departments/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/designations/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/hubs/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/packaging/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/roles/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/todo/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/delivery-charge/update' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/delivery-category/update' => UserAndSettingsScopeTest::class,
        'PUT admin/payment/update' => BackOfficeMoneyScopeTest::class,
        'PUT admin/payment/processed' => BackOfficeMoneyScopeTest::class,
        'PUT admin/hub-payment/processed' => BackOfficeRecordTakeoverTest::class,
        'POST admin/hub/cash-received-deliveryman/store' => CashHandoverAccountingTest::class,
        'PUT admin/hub/cash-received-deliveryman/update' => CashHandoverAccountingTest::class,
        'PUT merchant/fraud/update' => MerchantPanelWebScopeTest::class,
        'PUT merchant/accounts/payment-account/update' => MerchantPanelWebScopeTest::class,
        'PUT merchant/payment-request/update' => MerchantPanelWebScopeTest::class,
    
        // S51 — les portes de CREATION : le second identifiant, septieme fois
        'POST admin/assets/store' => CreationDoorScopeTest::class,
        'POST admin/deliveryman/store' => CreationDoorScopeTest::class,
        'POST admin/todo/todo_add' => CreationDoorScopeTest::class,
        'POST admin/todo/completed' => CreationDoorScopeTest::class,
        'POST admin/todo/processing' => CreationDoorScopeTest::class,
        'POST admin/payment/store' => CreationDoorScopeTest::class,
        'POST admin/request/hub/payment/store' => CreationDoorScopeTest::class,
        'POST admin/support/reply' => CreationDoorScopeTest::class,
        'POST merchant/support/reply' => CreationDoorScopeTest::class,

        // S52 — le reste de l'arriere : les aides AJAX qui RENSEIGNAIENT, et trois
        // ecritures qui atteignaient un tiers d'une autre societe
        'POST admin/assign-pickup/parcel/search' => ArrearsRemainderScopeTest::class,
        'POST admin/assign-return-to-merchant/parcel/search' => ArrearsRemainderScopeTest::class,
        'POST admin/get-merchant-cod' => ArrearsRemainderScopeTest::class,
        'POST admin/income/hub-user-accounts' => ArrearsRemainderScopeTest::class,
        'POST admin/merchant/account' => ArrearsRemainderScopeTest::class,
        'POST admin/merchant/delivery-charge/info' => ArrearsRemainderScopeTest::class,
        'POST admin/merchant/paymentmethod/change' => ArrearsRemainderScopeTest::class,
        'POST admin/merchant/store' => ArrearsRemainderScopeTest::class,
        'POST admin/parcel/delivery-category' => ArrearsRemainderScopeTest::class,
        'POST admin/parcel/recived-by-hub/search' => ArrearsRemainderScopeTest::class,
        'POST admin/push-notification/store' => ArrearsRemainderScopeTest::class,
        'POST admin/salary/search-account' => ArrearsRemainderScopeTest::class,
        'POST admin/sms-send-settings/status' => ArrearsRemainderScopeTest::class,
        'POST admin/wallet-request/recharge' => ArrearsRemainderScopeTest::class,
        'POST merchant/parcel/delivery-category' => ArrearsRemainderScopeTest::class,
        'POST merchant/sign-up-store' => ArrearsRemainderScopeTest::class,

        // S53 — les deux annulations : gardees depuis S45, mais il aura fallu un
        // colis d'en face COMPLET pour que le chemin non garde reussisse, et donc
        // que la garde devienne mesurable. Deux tentatives precedentes etaient creuses.
        // S65 — les deux routes que l'elargissement de `estIdentifiant()` fait
        // entrer. `delivery-type/status` est le defaut que ce filet avait laisse
        // passer : il BASCULAIT le reglage d'une autre societe (corrige en S64).
        'POST admin/delivery-type/status' => NakedReadRemainderScopeTest::class,

        'POST admin/parcel/partial-delivered/cancel' => ParcelCancelScopeTest::class,
        'POST admin/parcel/return-received-by-merchant' => ParcelCancelScopeTest::class,

        // S81 (T5) — les cinq routes que les dix NOMS LIBRES de `estIdentifiant()`
        // font entrer. Deux etaient deja prouvees par S64 sans etre inscrites
        // (le filet ne les voyait pas) ; trois ne l'etaient par rien, et l'une
        // d'elles ecrivait le compte de versement d'un AUTRE marchand.
        'POST admin/fund-transfer/store' => NakedReadRemainderScopeTest::class,
        'POST admin/income/balance-check' => NakedReadRemainderScopeTest::class,
        'POST admin/bank-transaction/filter' => FreeNamedIdentifierScopeTest::class,
        'POST merchant/accounts/account-transaction-filter' => FreeNamedIdentifierScopeTest::class,
        'POST merchant/payment-request/store' => FreeNamedIdentifierScopeTest::class,
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
        'POST super-admin/category/store' => 'S65 — même motif que `PUT category/update` juste en dessous : le catalogue des catégories ne porte AUCUNE colonne `company_id`, il est commun à toutes les sociétés. Entrée dans le filet par l\'élargissement de `estIdentifiant()` à `slug`',
                'PUT super-admin/category/update' => 'S35 — le catalogue des catégories ne porte AUCUNE colonne `company_id` : il est commun à toutes les sociétés, et `UserAndSettingsScopeTest` l\'inscrit',
            'POST admin/fraud/store' => 'S51 — la fiche de fraude ne porte AUCUN identifiant de locataire : `phone`, `name`, `details` et `tracking_id` sont des chaines (voir la migration : `tracking_id` est un `string`, pas une cle etrangere). `company_id` vient de `settings()` et `created_by` de la session',
        'POST merchant/fraud/store' => 'S51 — idem cote panneau marchand : meme depot, memes champs, aucun identifiant a garder',
        'POST merchant/accounts/statements-filter' => 'S52 — le `parcel_tracking_id` ne peut RIEN atteindre : les releves sont bornes au marchand authentifie, et le socle porte deja `if (tracking_id && blank(parcel)) parcel_id = 0`, donc un numero inconnu et un numero du voisin rendent tous deux un ensemble VIDE. J\'avais d\'abord diagnostique un oracle d\'existence ; c\'est le test qui m\'a corrige',
        'PUT super-admin/currency/update' => 'S55 — surface SUPER-ADMINISTRATEUR depuis la decision du 24/09 : le catalogue des devises est celui de la PLATEFORME. La route vit sous `super-admin/` avec `panel:super-admin`, qui n\'admet que le SUPER_ADMIN ; son identifiant de corps ne designe donc pas une ressource de locataire, et `currencies` ne porte de toute facon aucune `company_id` (constat S32). `CurrencyPanelScopeTest` prouve que le locataire est refuse par le PANNEAU et non par la seule donnee des permissions, et `UserAndSettingsScopeTest::test_the_shared_currency_catalogue_carries_no_company_at_all` mord si la table gagne un jour une societe',
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
     * dans `PROUVEES` avec le test qui l'établit — ou dans `EXEMPTEES` avec son
     * motif — en baissant le plafond d'autant. C'est le chemin qui a mené
     * l'arriéré de l'autre filet de 171 à 0.
     *
     * ✅ **CLOS depuis S53 (90 → 0, en treize passes).** Cette liste doit rester
     * **vide** : il n'y a plus de file d'attente. Une route d'écriture nouvelle
     * se prouve (`PROUVEES`) ou se motive (`EXEMPTEES`) — elle ne se range plus
     * ici, et le plafond à `0` l'interdit.
     */
    private const HERITAGE = [
    ];

    /** Le cliquet. Ne monte jamais. */
    private const PLAFOND_HERITAGE = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    /* ────────────────────────── l'énumération ───────────────────────────── */

    /**
     * Un nom de champ qui désigne un identifiant.
     *
     * `id`, `ids`, `*_id`, `*_ids`, `*_ids_` — et depuis **S65** `key` et `slug`.
     *
     * ⚠️ **Cet élargissement vient d'un défaut que ce filet avait laissé passer.**
     * `POST admin/delivery-type/status` est une écriture, donc dans son domaine :
     * elle basculait le réglage d'une AUTRE société (corrigé en S64). Elle lui a
     * échappé parce que son identifiant s'appelle `key`, pas `*_id`.
     *
     * Mesuré avant d'élargir : `key` et `slug` ajoutent **deux** routes, toutes
     * deux classées ci-dessous. Une version plus large (`_key$`, `_code$`,
     * `reference`) en ajoutait six, dont quatre FAUX POSITIFS — `map_key` et
     * `fcm_secret_key` sont des **valeurs**, pas des identifiants. On n'élargit
     * que de ce que la preuve justifie.
     *
     * **S81 (T5) — les noms libres, en liste explicite.** Ce filet reconnaît
     * toujours une CONVENTION DE NOM, pas un rôle ; mais le socle appelle ses
     * ressources par d'autres noms que `*_id`, et la liste ci-dessous les tient.
     * Elle a été établie en lisant chaque `Model::find($request->x)` et
     * `where('…', $request->x)` de `app/` dont le `x` n'est pas de la convention :
     * **dix noms**, qui font entrer **cinq** routes, toutes classées ci-dessus.
     * Mesuré en les ajoutant : `FundTransferRepository::update()` lisait ses deux
     * comptes nus, et le panneau marchand écrivait le compte de versement d'un
     * autre marchand. Un nom qui apparaît ici s'ajoute avec sa ressource ; un
     * nom qu'on retire doit avoir disparu de `app/`.
     */
    private const NOMS_LIBRES = [
        'account', 'from_account', 'to_account',   // un compte bancaire (`accounts`)
        'merchant', 'merchantId',                   // un marchand
        'merchant_account', 'editid',               // un compte de versement d'un marchand (`merchant_payments`)
        'accountId',                                // un compte bancaire, dans le module de paiement en ligne (coupe, D10)
        'hub',                                      // un entrepot
        'account_head',                             // un poste comptable (catalogue de PLATEFORME, sans societe)
    ];

    private function estIdentifiant(string $nom): bool
    {
        return (bool) preg_match('/^(id|ids|key|slug)$|_ids?_?$/i', $nom)
            || in_array($nom, self::NOMS_LIBRES, true);
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
