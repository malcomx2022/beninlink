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
 * Le filet d'isolation du WEB — pendant de `IsolationCoverageTest`, qui ne
 * couvre que `/api/v10`.
 *
 * Le socle n'a aucun garde-fou framework contre une lecture `find($id)` nue :
 * l'isolation repose sur chaque dépôt, écran par écran. Le filet de l'API rend
 * l'oubli impossible à ignorer côté mobile ; le back-office n'avait pas
 * d'équivalent, et c'est exactement ce qui a laissé passer **S22 à S26**, puis
 * **S28**.
 *
 * Ce test ne prétend pas que le back-office est isolé — il ne l'est pas. Il
 * pose quatre choses qui, elles, sont vraies et vérifiables :
 *
 * 1. **Tout est classé.** Chaque route d'application à paramètre est déclarée
 *    prouvée, exemptée avec un motif, ou héritée. Une route nouvelle qui n'est
 *    dans aucune liste fait échouer la suite : on ne peut plus en ajouter une
 *    sans dire ce qu'on a fait de sa portée.
 * 2. **L'héritage ne peut que rétrécir.** Son compte est plafonné. Passer une
 *    route d'`HERITAGE` à `PROUVEES` est le seul mouvement autorisé.
 * 3. **Une route de locataire est authentifiée**, ou déclarée publique avec un
 *    motif. C'est l'invariant qui aurait attrapé **S28** — une carte des
 *    courses déclarée hors du groupe `auth`, qui versait dans la page le nom,
 *    le téléphone et l'adresse des clients — le jour où elle a été écrite.
 * 4. **Aucun paquet ne monte de routes web en silence.** La liste des préfixes
 *    est fermée. C'est l'invariant qui aurait attrapé **S27** — onze routes
 *    d'édition du fichier `.env` montées par un fournisseur auto-découvert,
 *    sans authentification.
 *
 * Les deux dernières ne sont pas des précautions théoriques : chacune nomme une
 * faille trouvée en inventoriant cette surface, et la referme pour de bon.
 */
class WebIsolationCoverageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    /**
     * Préfixes de premier niveau montés par un PAQUET, pas par l'application.
     *
     * S27 : `geo-sot/laravel-env-editor` montait onze routes `/env-editor` avec
     * le seul middleware `['web']`, depuis un fournisseur auto-découvert. Rien
     * dans `routes/` ne le disait. Un préfixe qui apparaît ici sans être déclaré
     * est donc, jusqu'à preuve du contraire, une surface d'attaque que personne
     * n'a choisie : le test échoue et demande qu'on la regarde.
     */
    private const PAQUETS = [
        'Barryvdh\\Debugbar' => 'barryvdh/laravel-debugbar — outil de développement, absent en production',
        'Stancl\\Tenancy' => 'stancl/tenancy — service des fichiers du locataire',
        'GeoSot\\EnvEditor' => 'geo-sot/laravel-env-editor — S27 : les onze routes répondent 404 (BlockEnvEditorRoutes)',
        'Laravel\\Sanctum' => 'laravel/sanctum — le cookie CSRF de l\'authentification de l\'API',
        'Spatie\\LaravelIgnition' => 'spatie/laravel-ignition — page d\'erreur de développement',
    ];

    /** Routes à paramètre dont l'isolation est PROUVÉE par un test. */
    private const PROUVEES = [
        // S22 — le journal d'activité
        'GET admin/log-activity-view/{id}' => ActivityLogAccessTest::class,
        // S23 — le ticket de support et son fil
        'GET admin/support/view/{id}' => BackOfficeScopingTest::class,
        'GET admin/support/edit/{id}' => BackOfficeScopingTest::class,
        // S24 — le compte financier
        'POST admin/expense/search-account/{id}' => BackOfficeScopingTest::class,
        // S25 — la chronologie du colis
        'GET admin/parcel/logs/{id}' => BackOfficeScopingTest::class,
        'GET admin/parcel/delivered/logs/info/{id}' => BackOfficeScopingTest::class,
        // S26 — la boutique par défaut
        'PUT admin/merchant/shops/default/{merchant_id}/{id}' => BackOfficeScopingTest::class,
        // S29 — la vitrine : la lecture etait scopee, la SUPPRESSION non (6 deportes)
        'DELETE admin/front-web/blogs/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/blogs/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/blogs/update/{id}' => BackOfficeWriteScopeTest::class,
        'DELETE admin/front-web/faq/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/faq/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/faq/update/{id}' => BackOfficeWriteScopeTest::class,
        'DELETE admin/front-web/partner/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/partner/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/partner/update/{id}' => BackOfficeWriteScopeTest::class,
        'DELETE admin/front-web/service/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/service/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/service/update/{id}' => BackOfficeWriteScopeTest::class,
        'DELETE admin/front-web/social-link/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/social-link/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/social-link/update/{id}' => BackOfficeWriteScopeTest::class,
        'DELETE admin/front-web/why-courier/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/why-courier/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/why-courier/update/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/front-web/pages/edit/{id}' => BackOfficeWriteScopeTest::class,
        'PUT admin/front-web/pages/update/{id}' => BackOfficeWriteScopeTest::class,
        // S29 — le trou de S23 : les ecritures du support restaient nues
        'DELETE admin/support/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/support/status-update/{id}' => BackOfficeWriteScopeTest::class,
        // S29 — le trou de S26 : les ecritures des boutiques restaient nues
        'GET admin/merchant/shops/create/{id}' => BackOfficeWriteScopeTest::class,
        'DELETE admin/merchant/shops/delete/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/merchant/shops/edit/{id}' => BackOfficeWriteScopeTest::class,
        'GET admin/merchant/{id}/shops/index' => BackOfficeWriteScopeTest::class,
        // S29, 2e passe — le panneau marchand web. Les depots etaient deja scopes
        // (S7, S17, S18) mais ce qui le prouvait appelait les routes de l'API : la
        // preuve manquait au point d'entree WEB, et le filet exige celui qu'il inscrit.
        // La frontiere ici est le MARCHAND, pas la societe — deux marchands de la meme
        // societe, le cas que la decision S7 nomme comme le plus frequent.
        'GET merchant/support/edit/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/support/view/{id}' => MerchantPanelWebScopeTest::class,
        'PUT merchant/support/update/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE merchant/support/delete/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/fraud/edit/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE merchant/fraud/delete/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/accounts/payment-account/edit/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE merchant/accounts/payment-account/delete/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/payment-request/edit/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE merchant/payment-request/delete/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/shops/edit/{id}' => MerchantPanelWebScopeTest::class,
        'PUT merchant/shops/update/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE merchant/shops/delete/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/parcel/details/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/parcel/edit/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/parcel/logs/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/parcel/clone/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/parcel/status-update/{id}/{status_id}' => MerchantPanelWebScopeTest::class,
        'PUT merchant/parcel/update/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE merchant/parcel/delete/{id}' => MerchantPanelWebScopeTest::class,
        'GET merchant/invoice/{invoice_id}' => MerchantPanelWebScopeTest::class,
        // Le portefeuille : trois ecrans d'ADMINISTRATION qui deplacent de l'argent,
        // donc scopes par societe, via `proprieteVerifiee()` dans le controleur.
        'PUT admin/wallet-request/approve/{id}' => MerchantPanelWebScopeTest::class,
        'PUT admin/wallet-request/reject/{id}' => MerchantPanelWebScopeTest::class,
        'DELETE admin/wallet-request/delete/{id}' => MerchantPanelWebScopeTest::class,
        // S29, 3e passe — LE VOL DE LIGNE. Onze depots faisaient `Modele::find($id)`
        // puis ecrasaient `company_id` avec la societe connectee : la ligne d'une autre
        // societe n'etait pas seulement lue, elle etait TRANSFEREE — elle disparaissait
        // des ecrans de son proprietaire, dont les listes sont `companywise()`.
        'GET admin/roles/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/role/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/assets/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/assets/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/asset-category/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/asset-category/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/designations/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/designation/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/packaging/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/packaging/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/hubs/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/hub/view/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/hub/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/delivery-charge/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/delivery-charge/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/todo/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/news-offer/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/news-offer/update/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/news-offer/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/departments/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/department/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        // Les versements aux entrepots : trois chemins qui DEPLACENT DE L'ARGENT sur
        // une lecture nue. `cancelProcess()` creditait notre compte du montant lu.
        'GET admin/request/hub/payment/edit/{id}' => BackOfficeRecordTakeoverTest::class,
        'PUT admin/request/hub/payment/update/{id}' => BackOfficeRecordTakeoverTest::class,
        'DELETE admin/request/hub/payment/delete/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/hub-payment/process/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/hub-payment/reject/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/hub-payment/cancel-process/{id}' => BackOfficeRecordTakeoverTest::class,
        'GET admin/hub-payment/cancel-reject/{id}' => BackOfficeRecordTakeoverTest::class,
        // S29, 4e passe — LA PAIE. Le depot lisait nu partout, et deux de ses
        // methodes deplacaient de l'argent : `update()` creditait le compte bancaire
        // de l'AUTRE societe du montant lu, reecrivait sa ligne de paie, puis
        // debitait le notre. Un bulletin de paie est une donnee personnelle.
        'GET admin/salarys/edit/{id}' => SalaryScopeTest::class,
        'GET admin/salary/pay-slip/{id}' => SalaryScopeTest::class,
        'DELETE admin/salary/delete/{id}' => SalaryScopeTest::class,
        'GET admin/salary/salary-generate/edit/{id}' => SalaryScopeTest::class,
        'DELETE admin/salary/salary-generate/delete/{id}' => SalaryScopeTest::class,
        // S30, 5e passe — L'ARGENT. Sept depots touchant a des comptes bancaires
        // lisaient nu, et pour six d'entre eux c'etait avant un mouvement d'argent :
        // rendre un solde, rejouer un virement, remettre un versement en attente de
        // paiement, reaffecter une demande a un autre marchand ou a un autre entrepot.
        'GET admin/income/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'PUT admin/income/update/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/income/delete/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/expense/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'PUT admin/expense/update/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/expense/delete/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/fund-transfer/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'PUT admin/fund-transfer/update/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/fund-transfer/delete/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/accounts/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'PUT admin/accounts/update/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/accounts/delete/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/payment/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/payment/process/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/payment/reject/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/payment/cancel-process/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/payment/cancel-reject/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/payment/delete/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/hub/payment-request/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'PUT admin/hub/payment-request/update/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/hub/payment-request/delete/{id}' => BackOfficeMoneyScopeTest::class,
        'GET admin/hub/cash-received-deliveryman/edit/{id}' => BackOfficeMoneyScopeTest::class,
        'DELETE admin/hub/cash-received-deliveryman/delete/{id}' => BackOfficeMoneyScopeTest::class,
    ];

    /**
     * Routes dont le paramètre ne désigne pas une ressource d'une autre société,
     * avec le motif. Un motif est une phrase vérifiable, pas « pas concerné ».
     */
    private const EXEMPTEES = [
        // Le panneau central : travailler au-dessus des sociétés EST son objet.
        'GET super-admin/company/edit/{id}' => 'panneau central : le super-administrateur travaille au-dessus des sociétés',
        'DELETE super-admin/company/delete/{id}' => 'panneau central',
        'GET super-admin/company/subscription/switch/{id}' => 'panneau central',
        'GET super-admin/plan/edit/{id}' => 'panneau central ; un plan est un objet de plateforme',
        'DELETE super-admin/plan/delete/{id}' => 'panneau central ; un plan est un objet de plateforme',
        'GET super-admin/plan/modules/{plan_id}' => 'panneau central ; un plan est un objet de plateforme',

        // Un module de la plateforme : la table `addons` ne porte pas de company_id.
        'GET admin/addons/{addon}' => 'un module de la plateforme : la table `addons` n\'a pas de `company_id`',
        'GET admin/addons/{addon}/edit' => 'un module de la plateforme',
        'PUT admin/addons/{addon}' => 'un module de la plateforme',
        'PATCH admin/addons/{addon}' => 'un module de la plateforme',
        'DELETE admin/addons/{addon}' => 'un module de la plateforme',

        // La section : le parametre nomme est un TYPE de section, pas un identifiant
        // de ressource — `SectionController::edit($type)` le dit, et la lecture est
        // `companyWise()`.
        'GET admin/front-web/section/edit/{id}' => 'un type de section, pas un identifiant ; la lecture est scopee',
        'PUT admin/front-web/section/update/{id}' => 'un type de section, pas un identifiant ; la lecture est scopee',

        // Un nom, pas un identifiant.
        'PUT admin/settings/pay-out/setup/update/{paymentmethod}' => 'un nom de passerelle, pas un identifiant de ressource',
        'PUT merchant/settings/online-payment-setup/update/{paymentmethod}' => 'un nom de passerelle, pas un identifiant de ressource',
        'PUT admin/social-login-settings/update/{social}' => 'un nom de fournisseur d\'identité, pas un identifiant de ressource',

        // Une charge utile de filtres, pas un identifiant.
        'GET admin/parcel-reports-print-page/{array}' => 'des filtres sérialisés ; la liste rendue est scopée par société',
        'GET admin/parcel-wise-profit-print-page/{array}' => 'des filtres sérialisés ; la liste rendue est scopée par société',
        'GET merchant/parcel-reports-print-page/{array}' => 'des filtres sérialisés ; la liste rendue est scopée par marchand',

        // Le profil : l'identifiant est DÉCORATIF, la méthode lit auth()->user()->id.
        'GET admin/profile/update/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'GET admin/profile/change-password/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'PUT admin/profile/update/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'PUT admin/profile/update-password/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'GET merchant/profile/update/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'GET merchant/profile/change-password/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'PUT merchant/profile/update/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        'PUT merchant/profile/update-password/{id}' => 'identifiant décoratif : la méthode lit `auth()->user()->id`',
        // ⚠️ Celles-ci COMPARENT à l'utilisateur connecté — mais répondent 500,
        // pas 403 ni 404. Un refus d'accès annoncé comme une panne serveur :
        // signalé, pas corrigé ici (ce lot ne touche pas au socle du profil).
        'GET admin/profile/{id}' => 'compare à `Auth::user()->id` — mais `abort(500)` au lieu de 403/404 : signalé',
        'GET merchant/profile/{id}' => 'compare à `Auth::user()->id` — mais `abort(500)` au lieu de 403/404 : signalé',
    ];

    /**
     * Routes PUBLIQUES à dessein : elles n'ont pas `auth`, et c'est voulu.
     * Toute autre route de locataire sans `auth` fait échouer le test — c'est
     * ainsi que **S28** se serait vu le premier jour.
     */
    private const PUBLIQUES = [
        'GET blog-details/{id}' => 'page publique du site vitrine',
        'GET service-details/{id}' => 'page publique du site vitrine',
        'GET password/reset/{token}' => 'réinitialisation de mot de passe : le jeton EST l\'authentification',
        'GET login/{social}' => 'redirection vers le fournisseur d\'identité, avant connexion',
        'GET localization/{language}' => 'choix de la langue de l\'interface, avant connexion',
        'GET invoice/statement/{invoice}/pdf' => 'lien signé (`signed`) : la signature EST l\'authentification',
    ];

    /**
     * L'HÉRITAGE — routes du socle dont la portée n'est **pas prouvée**.
     *
     * Cette liste est un ARRIÉRÉ, pas un permis. Elle dit la vérité sur l'état
     * du back-office : ces écrans suivent le même motif que S22 à S26 — liste
     * scopée, détail en `find($id)`. Certaines sont sûrement sans danger,
     * d'autres sûrement pas ; personne ne l'a vérifié, et c'est le point.
     *
     * ⚠️ `PLAFOND_HERITAGE` ne monte jamais. Une route retirée d'ici va dans
     * `PROUVEES` avec son test, et le plafond descend d'autant. C'est le cliquet.
     */
    private const HERITAGE = [
        'DELETE admin/currency/delete/{id}',
        'DELETE admin/delivery-category/delete/{id}',
        'DELETE admin/deliveryman/delete/{id}',
        'DELETE admin/fraud/delete/{id}',
        'DELETE admin/hub/incharge/{hubID}/delete/{id}',
        'DELETE admin/merchant/delete/{id}',
        'DELETE admin/merchant/paymentinfo/delete/{id}',
        'DELETE admin/merchant/{merchant}/delivery-charge/delete/{id}',
        'DELETE admin/parcel/delete/{id}',
        'DELETE admin/push-notification/delete/{id}',
        'DELETE admin/sms-settings/delete/{id}',
        'DELETE admin/user/delete/{id}',
        'DELETE category/delete/{id}',
        'GET admin/currency/edit/{id}',
        'GET admin/customs/rules/edit/{id}',
        'GET admin/delivery-category/edit/{id}',
        'GET admin/deliveryman/edit/{id}',
        'GET admin/fraud/edit/{id}',
        'GET admin/hub/incharge/{hubID}/assigned/{id}',
        'GET admin/hub/incharge/{hubID}/create',
        'GET admin/hub/incharge/{hubID}/edit/{id}',
        'GET admin/hub/incharge/{hubID}/index',
        'GET admin/merchant/edit/{id}',
        'GET admin/merchant/invoice-generate/{id}',
        'GET admin/merchant/view/{id}',
        'GET admin/merchant/{id}/payment/add',
        'GET admin/merchant/{id}/payment/index',
        'GET admin/merchant/{merchant_id}/invoice',
        'GET admin/merchant/{merchant_id}/invoice/csv/{invoice_id}',
        'GET admin/merchant/{merchant_id}/invoice/journal/{invoice_id}',
        'GET admin/merchant/{merchant_id}/invoice/pdf/{invoice_id}',
        'GET admin/merchant/{merchant_id}/invoice/status/update',
        'GET admin/merchant/{merchant_id}/invoice/{invoice_id}',
        'GET admin/merchant/{merchant}/delivery-charge/create',
        'GET admin/merchant/{merchant}/delivery-charge/edit/{id}',
        'GET admin/merchant/{merchant}/delivery-charge/index',
        'GET admin/merchant/{mid}/payment/edit/{id}',
        'GET admin/parcel/clone/{id}',
        'GET admin/parcel/details/{id}',
        'GET admin/parcel/edit/{id}',
        'GET admin/parcel/print/{id}',
        'GET admin/parcel/print/{id}/label',
        'GET admin/parcel/status-update/{id}/{status_id}',
        'GET admin/sms-settings/edit/{id}',
        'GET admin/users/edit/{id}',
        'GET admin/users/permissions/{id}',
        'GET category/edit/{id}',
        'GET merchant/invoice/csv/{merchant_id}/{invoice_id}',
        'GET merchant/invoice/journal/{merchant_id}/{invoice_id}',
        'GET merchant/invoice/pdf/{merchant_id}/{invoice_id}',
        'POST admin/hub/incharge/{hubID}/store',
        'POST admin/merchant/{merchant}/delivery-charge/store',
        'PUT admin/customs/alerts/{id}/resolve',
        'PUT admin/customs/rules/update/{id}',
        'PUT admin/delivery-zone/countries/{id}',
        'PUT admin/hub/incharge/{hubID}/update/{id}',
        'PUT admin/merchant/update/{id}',
        'PUT admin/merchant/{merchant}/delivery-charge/update/{id}',
        'PUT admin/parcel/update/{id}',
        'PUT admin/sms-settings/update/{id}',
    ];

    /** Le compte figé de l'arriéré. Il descend, il ne monte pas. */
    private const PLAFOND_HERITAGE = 60;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    /**
     * Toutes les routes d'application à paramètre, « METHODE chemin » en clé,
     * la route en valeur. `/api/v10` est exclu : il a son propre filet,
     * `IsolationCoverageTest` — et l'exclusion est vérifiée plus bas.
     */
    private function routesDApplication(): array
    {
        $trouvees = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if ($route->parameterNames() === []) {
                continue;
            }

            $uri = $route->uri();

            if (Str::startsWith($uri, 'api/')) {
                continue; // filet S7
            }
            if (!$this->estANous($route)) {
                continue; // monté par un paquet ; l'invariant 4 s'en occupe
            }

            foreach ($route->methods() as $methode) {
                if (in_array($methode, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $trouvees[$methode . ' ' . $uri] = $route;
            }
        }

        return $trouvees;
    }

    /* ─────────── 1. tout est classé : une route neuve ne passe pas ────────── */

    public function test_every_web_route_with_a_parameter_is_classified(): void
    {
        $routes = $this->routesDApplication();
        $this->assertGreaterThan(150, count($routes), 'les routes de locataire ne sont pas montées : le test ne mesure rien');

        $classees = array_merge(
            array_keys(self::PROUVEES),
            array_keys(self::EXEMPTEES),
            array_keys(self::PUBLIQUES),
            self::HERITAGE,
        );

        $nonClassees = array_values(array_diff(array_keys($routes), $classees));
        sort($nonClassees);

        $this->assertSame([], $nonClassees, "Routes à paramètre non classées. Pour chacune : prouver la portée "
            . "(un test dans PROUVEES), donner le motif pour lequel le paramètre ne désigne pas une ressource "
            . "(EXEMPTEES), ou dire qu'elle est publique à dessein (PUBLIQUES). L'HÉRITAGE est fermé :\n - "
            . implode("\n - ", $nonClassees));
    }

    /** Et l'inverse : une déclaration qui ne correspond plus à rien s'en va. */
    public function test_no_declaration_points_to_a_route_that_no_longer_exists(): void
    {
        $connues = array_keys($this->routesDApplication());

        foreach (['PROUVEES' => self::PROUVEES, 'EXEMPTEES' => self::EXEMPTEES, 'PUBLIQUES' => self::PUBLIQUES] as $nom => $liste) {
            foreach (array_keys($liste) as $cle) {
                $this->assertContains($cle, $connues, "{$nom} déclare une route qui n'existe plus : {$cle}");
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
            "L'arriéré a grossi. Une route nouvelle ne se range pas dans HERITAGE : "
            . 'elle se prouve (PROUVEES) ou se motive (EXEMPTEES / PUBLIQUES).',
        );

        $this->assertSame([], array_intersect(self::HERITAGE, array_keys(self::PROUVEES)),
            'une route prouvée reste inscrite dans l\'arriéré : retirer la ligne et baisser le plafond');
    }

    /**
     * F6 — le filet de l'API avait un trou : il vérifiait que la classe déclarée
     * **existe**, jamais qu'elle puisse prouver quoi que ce soit. Une preuve
     * d'isolation demande d'atteindre la ressource d'un autre compte, donc de
     * l'avoir écrite : un test sans base migrée ne peut pas la produire.
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

    /* ─── 3. l'invariant S28 : une route de locataire est authentifiée ────── */

    public function test_every_tenant_route_is_authenticated_or_declared_public(): void
    {
        $nues = [];

        foreach ($this->routesDApplication() as $cle => $route) {
            $middlewares = $route->gatherMiddleware();
            $authentifiee = false;

            foreach ($middlewares as $middleware) {
                if (!is_string($middleware)) {
                    continue;
                }
                if ($middleware === 'auth' || Str::startsWith($middleware, ['auth:', 'auth.'])) {
                    $authentifiee = true;
                }
                if (Str::startsWith($middleware, 'signed')) {
                    $authentifiee = true; // un lien signé porte sa propre preuve
                }
            }

            if (!$authentifiee && !array_key_exists($cle, self::PUBLIQUES)) {
                $nues[] = $cle . ' → ' . $route->getActionName();
            }
        }

        $this->assertSame([], $nues, "Routes à paramètre joignables SANS AUTHENTIFICATION et non déclarées "
            . "publiques. C'est la forme de S28 : une carte des courses hors du groupe `auth` qui versait dans "
            . "la page le nom, le téléphone et l'adresse des clients. Poser `auth`, ou inscrire la route dans "
            . "PUBLIQUES avec son motif :\n - " . implode("\n - ", $nues));
    }

    /* ─── 4. l'invariant S27 : aucun paquet ne monte de routes en silence ─── */

    /**
     * Une route est À NOUS si son action est une fermeture (elle ne peut alors
     * venir que de `routes/`) ou un contrôleur sous `App\`. Tout le reste est
     * monté par un paquet.
     *
     * Ce critère est volontairement l'espace de noms et non le préfixe d'URL :
     * un paquet peut monter ses routes n'importe où, y compris sous `admin/`,
     * et c'est le nom de sa classe qui le trahit.
     */
    private function estANous(Route $route): bool
    {
        // ⚠️ Une route du socle déclare son action avec un antislash de tête
        // (`\App\Http\Controllers\…`) là où toutes les autres n'en ont pas.
        // Sans ce `ltrim`, elle passait pour un paquet.
        $action = ltrim($route->getActionName(), '\\');

        return $action === 'Closure' || Str::startsWith($action, 'App\\');
    }

    public function test_no_package_mounts_web_routes_unnoticed(): void
    {
        $inconnus = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if ($this->estANous($route)) {
                continue;
            }

            $action = ltrim($route->getActionName(), '\\');
            $declare = false;

            foreach (array_keys(self::PAQUETS) as $espace) {
                if (Str::startsWith($action, $espace . '\\')) {
                    $declare = true;
                    break;
                }
            }

            if (!$declare) {
                $inconnus[$action] = $route->uri() . ' → ' . $action;
            }
        }

        $this->assertSame([], array_values($inconnus), "Un paquet monte des routes web qui ne sont déclarées "
            . "nulle part. C'est exactement S27 : `geo-sot/laravel-env-editor` montait onze routes d'édition du "
            . "fichier `.env`, sans authentification, depuis un fournisseur auto-découvert, et rien dans `routes/` "
            . "ne le disait. Regarder ce que ces routes exposent — qui les atteint, et sans quoi — puis les "
            . "déclarer dans PAQUETS :\n - " . implode("\n - ", $inconnus));
    }
}
