# CARTOGRAPHIE.md — Étape 0 (socle We Courier, dossier web/)

> Relevé de l'existant AVANT toute modification. Lecture seule.
> Chaque bloc cite les fichiers réels du socle. Blocs **A-K** renseignés.
> Dernière mise à jour : 2026-08-16 (chantier 1 : volet langue **et** volet devise
> — helpers XOF, 278 affichages et 32 sorties d'API basculés en FCFA entier).
> Antérieurement : blocs J — tarifs et K — notifications ; `lang/fr/` complété.

---

## Bloc A — Vue d'ensemble & stack
- Version PHP requise : **`^8.1`** (`composer.json`) + extensions `curl`, `gd`,
  `json`, `mysqli`, `pdo`. ⇒ Le plancher réel est **8.1**, pas 8.3 : le « PHP 8.3 »
  du CLAUDE.md racine est un choix de déploiement, à répercuter tel quel dans le
  guide VPS / deploy.sh.
- Version Laravel : contrainte **`^10.10`**, verrouillée à **`v10.43.0`**
  (`composer.lock`). PHPUnit `^10.1`, Pint installé (sans config custom).
- Packages majeurs (require) :
  - Socle : `stancl/tenancy ^3.7` (multi-tenant), `laravel/sanctum ^3.2` (auth API
    Flutter), `laravel/socialite ^5.8`, `laravel/ui`.
  - Paiements — 7 passerelles, **un SDK chacune, aucune couche commune** :
    `stripe/stripe-php ^10.17` + `cartalyst/stripe-laravel ^15.0`,
    `srmklive/paypal ^3.0`, `razorpay/razorpay ^2.8`, `obydul/laraskrill ^1.2`,
    `anandsiddharth/laravel-paytm-wallet ^2.0`. SSLCommerz et Aamarpay n'ont pas
    de package : code recopié dans `app/Library/SslCommerz/` et `AamarpayController`.
  - SMS : `twilio/sdk ^8.2`, `vonage/client ^4.0`.
  - Métier/UI : `maatwebsite/excel ^3.1`, `milon/barcode ^10.0` (étiquettes colis),
    `spatie/laravel-activitylog ^4.7`, `brian2694/laravel-toastr`,
    `realrashid/sweet-alert`, `geo-sot/laravel-env-editor` (écrit `.env` depuis
    l'admin — sensible).
  - **Aucun package Mobile Money africain** : FedaPay sera à intégrer en HTTP
    direct via `guzzlehttp/guzzle ^7.2`, déjà présent. Paytm et Skrill sont morts
    au sens du projet.
  - Pas de `package.json` : `vite.config.js` est un vestige, les assets sont
    livrés compilés dans `public/`. Ne pas lancer `npm`.
  - 🚩 **Version de PHP : `composer.json` annonce `^8.1`, mais `composer.lock`
    n'accepte en pratique que PHP 8.2.** Vérifié le 2026-08-16 :
    | PHP | Résultat de `composer install` |
    |---|---|
    | 8.4 | refus — `htmlpurifier`, `laminas-diactoros`, `nette/schema`, `nette/utils`, `lcobucci/clock`, `league/config` |
    | 8.3 | refus — `lcobucci/clock 2.3.0` exige `~8.1.0 \|\| ~8.2.0` |
    | **8.2** | **installation complète** |
    ⚠️ **Le gabarit de production vise `php8.3-fpm`**
    (`docs/guides/infra/nginx/beninlink.conf:17`) : un `composer install` y échouerait
    comme ci-dessus. À trancher — aligner la production sur 8.2, ou lancer un
    `composer update` (qui fait bouger les versions du socle et demande une recette).
  - ⚠️ **Poste de développement** : WAMP sert Apache **et** le CLI en **8.4.0**
    (`wampmanager.conf`, `LoadModule php_module …/php8.4.0/…`). Les commandes
    `artisan`/`composer` doivent donc être lancées explicitement avec
    `bin/php/php8.2.26/php.exe` tant que WAMP n'est pas basculé sur 8.2.
  - 🐞 **Incident rencontré (pour mémoire)** : le cache Composer local était corrompu
    et produisait des paquets **extraits partiellement** — `nunomaduro/collision`
    (dossier `Subscribers/` amputé), `laravel/framework`
    (`ConsoleSupportServiceProvider.php` absent), `phpunit/phpunit`
    (`DataProviderMethodFinishedSubscriber` absent). Symptôme : `artisan` refuse de
    démarrer. Remède : `composer clear-cache`, puis suppression complète de `vendor/`
    et réinstallation.
  - ✅ **Tests isolés de la base de développement** (2026-08-16) : les lignes
    `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` de `phpunit.xml` (l.24-25),
    livrées **commentées**, sont désormais actives — les tests visaient sinon la vraie
    base MySQL `we-courier-saas` du `.env`. `pdo_sqlite` est présent en 8.2.
    ⚠️ Conséquence : la base de test est **vide**. Toute Feature test devra utiliser
    `RefreshDatabase` (commenté dans `tests/Feature/ExampleTest.php`) pour jouer les
    migrations — préalable à écrire avant les tests du webhook FedaPay.
- Organisation de app/ (notes) : découpage **par couche**, puis **par domaine
  métier** à l'intérieur de chaque couche.
  - `Http/Controllers/` : `Api/V10/` (21 contrôleurs, les 2 apps Flutter),
    `Backend/` (~60, avec `MerchantPanel/`, `HubPanel/`, `Superadmin/`, `FrontWeb/`),
    `Frontend/` (site vitrine), plus l'installeur à la racine.
  - `Http/Requests/` : 46 dossiers de FormRequest, un par domaine.
  - `Repositories/` : **52 domaines, `<X>Interface` + `<X>Repository` — c'est là
    que vit la logique métier**, pas dans `Http/Services/` (3 classes seulement :
    Sms, PushNotification, PurchaseVerify = infrastructure). Binding un par un
    dans `AppServiceProvider::register()`, injection par constructeur.
  - `Models/Backend/` : 51 modèles (+ `FrontWeb/`, `Merchantpanel/`, `Payroll/`,
    `Superadmin/`) ; `Models/` à la racine porte `User`, `Tenant`, `CustomerDomain`.
  - `Enums/` : **des `interface` remplies de `const`, pas des enums PHP 8.1**
    (`ParcelStatus`, `PayoutSetup`…). Style hérité, à suivre pour rester cohérent.
  - `Http/Helper/Helper.php` : helpers globaux chargés par l'autoload `files`
    (`settings()`, `globalSettings()`, `subscriptionCheck()`, `parcelStatus()`).
  - Aussi : `Traits/`, `Library/SslCommerz/`, `Exports/`, `Imports/`, `Mail/`,
    `Console/`, `Middleware/` (20), `ViewComposer/` (3).
  - **Conséquence pratique** : un même domaine est éclaté sur 5 dossiers
    (ex. wallet → `Backend/MerchantPanel/WalletController`, `Http/Requests/Wallet/`,
    `Repositories/Wallet/`, `Models/Backend/Wallet`, `Enums/Wallet/`). Ajouter une
    fonctionnalité veut dire toucher ces 5 endroits.

## Bloc B — Multi-tenancy  ⭐ (à ne jamais casser)
- Paquet de tenancy (ou « fait maison ») : **`stancl/tenancy ^3.7`** (Tenancy for
  Laravel v3), **utilisé en mode dégradé**. `TenancyServiceProvider` est le stub
  du package, quasi non personnalisé : tous les hooks d'événements sont vides.
- Résolution du locataire (middleware / sous-domaine) :
  - Middleware réel = **`InitializeTenancyByDomain`** (domaine **complet**, pas
    `…BySubdomain`), précédé de `PreventAccessFromCentralDomains` et suivi de
    `CompanyActivationMiddleware` — `routes/web.php:144-148`.
  - **Le choix se fait au chargement des routes, pas par requête** :
    `Domain::pluck('domain')` est exécuté à chaque boot pour décider quel bloc
    enregistrer — `routes/web.php` (locataire) si l'hôte est dans `domains`,
    `routes/superadmin.php` (central) sinon, condition inverse en `superadmin.php:36-40`.
    ⇒ une requête SQL avant tout routage ; **un cache de routes serait incorrect**.
  - `routes/tenant.php` est **vide** (tout commenté, l.22-29) ; `mapRoutes()` le
    charge sans effet.
  - ⚠️ **L'API n'initialise aucune tenancy** : 0 occurrence de `tenant`/`Tenancy`
    dans `routes/api.php`. Sur `/api/v10`, `tenant()` vaut **toujours `null`**.
  - Le vocabulaire « sous-domaine » du CLAUDE.md racine décrit l'usage, pas le
    mécanisme : techniquement n'importe quel domaine peut être rattaché.
- Modèle Tenant/Company : **deux entités distinctes**.
  - `app/Models/Tenant.php` étend le modèle du package (`HasDatabase`, `HasDomains`) ;
    sa seule colonne propre est **`company_id`** (`getCustomColumns()`). Il ne sert
    qu'à traduire *domaine → société*. `HasDatabase` est inerte (voir ci-dessous).
    L'`id` du tenant **est** le domaine.
  - La vraie « company » est **`GeneralSettings`**, résolue par le helper
    **`settings()`** (`Helper.php:105-118`) : `tenant()->company_id`, sinon
    `Auth::user()->company_id`, sinon `1` (société plateforme/superadmin).
  - Création : `Repositories/Superadmin/Company/CompanyRepository.php:208` et `:430`
    — `Tenant::create(['id' => $request->domain, 'company_id' => $company_id])`.
- Base centrale vs base locataire : **la distinction n'existe pas — une seule base.**
  - `config/tenancy.php:30-35` : **`DatabaseTenancyBootstrapper` est commenté**
    (seuls Cache, Filesystem, Queue restent actifs).
  - `TenancyServiceProvider::events()` l.28-30 : `Jobs\CreateDatabase`,
    `MigrateDatabase`, `SeedDatabase` **commentés** sur `TenantCreated`.
  - ⇒ `database.prefix = 'tenant'`, `central_connection`, `managers` sont du
    **code mort**. Pas de `database/migrations/tenant/`.
  - L'isolation réelle = colonne **`company_id`** + **`scopeCompanywise()`**
    (`where('company_id', settings()->id)`), redéfini dans **47 des 51 modèles**
    de `app/Models/Backend/` (ex. `Account.php:61`).
  - ⚠️ **Sécurité** : rien au niveau framework n'empêche une fuite inter-locataires.
    Aucun global scope, aucune contrainte de base. **Un oubli de `companywise()`
    = données d'autres sociétés exposées.**
- Exécuter du code dans le contexte d'un locataire : **aucun mécanisme maison**.
  `grep` sur `tenancy()->initialize`, `Tenancy::`, `->run(function` dans `app/` et
  `routes/` ne renvoie **rien**. Le code n'appelle `tenant()` qu'en lecture, à
  8 endroits (+ vues Blade).
  - Les API du package restent dispo (`tenancy()->initialize($tenant)`,
    `$tenant->run(fn() => …)`, `tenancy()->end()`) **mais ne scopent pas les
    requêtes** : sans `DatabaseTenancyBootstrapper`, initialiser la tenancy ne
    change que le préfixe de cache, le disque, la queue, et la valeur de `tenant()`.
  - ⇒ **Pour un job / une commande artisan / le webhook FedaPay** : résoudre
    `company_id` **explicitement** depuis la donnée traitée et filtrer dessus.
    **`settings()` est un piège** : hors contexte authentifié elle retombe
    silencieusement sur la société `1`, donc `companywise()` filtrerait sur la
    mauvaise société **sans lever d'erreur**.
- Fichiers cités : `config/tenancy.php` (l.9, 19-21, 30-35, 40-54) ·
  `app/Providers/TenancyServiceProvider.php` (l.25-38, 120-145) ·
  `routes/web.php` (l.120-121, 138-148) · `routes/superadmin.php` (l.32-40) ·
  `routes/tenant.php` (vide) · `routes/api.php` (aucune tenancy) ·
  `app/Models/Tenant.php` · `app/Models/CustomerDomain.php` ·
  `app/Http/Helper/Helper.php` (l.105-118 `settings()`, l.763 `static_asset()`) ·
  `app/Http/Middleware/CompanyActivationMiddleware.php:18` ·
  `app/Models/Backend/Account.php:61` (modèle de `scopeCompanywise`) ·
  `app/Repositories/Superadmin/Company/CompanyRepository.php:208,430` ·
  `app/Repositories/Parcel/ParcelRepository.php:291` ·
  `app/Http/Controllers/Auth/LoginController.php:54,116`

## Bloc C — Abstraction de paiement  ⭐ (pour FedaPay)
> ⚠️ **Les prémisses de ce bloc sont fausses.** Il n'existe **ni contrat de
> passerelle, ni classe Paystack**. Le chantier FedaPay n'est donc pas
> « implémenter une interface existante » mais **écrire un flux complet**.

- Interface / contrat des gateways : **AUCUN.**
  `find app -name "*Interface.php" -not -path "*/Repositories/*"` → un seul
  résultat, `app/Library/SslCommerz/SslCommerzInterface.php`, **spécifique à
  SSLCommerz** (importé avec le SDK recopié à la main), implémenté seulement par
  `AbstractSslCommerz` / `SslCommerzNotification`. Sa forme (`setShipmentInfo`,
  `setProductInfo`) est trop liée à SSLCommerz pour servir de contrat générique.
  Les 12 fichiers `*Payment*Interface.php` de `app/Repositories/` sont des
  contrats de **repository** (CRUD métier), pas de passerelle.
- Signatures des méthodes à implémenter : **aucune à implémenter.** Pour mémoire,
  `SslCommerzInterface` expose `makePayment(array $data)`,
  `orderValidate($requestData, $trxID, $amount, $currency)`, `setParams($data)`,
  `setRequiredInfo/setCustomerInfo/setShipmentInfo/setProductInfo/setAdditionalInfo(array $data)`,
  `callToApi($data, $header = [], $setLocalhost = false)`. Aucune autre passerelle
  ne s'y conforme.
- Enregistrement / sélection d'une gateway : **ni registry, ni factory, ni binding
  conditionnel.** Câblage en dur à trois niveaux :
  1. **Un contrôleur par passerelle, sans ancêtre commun** : Stripe →
     `Backend/MerchantPanel/OnlinePaymentController::stripe/stripePost` +
     `Backend/Superadmin/PlanController` · PayPal → `OnlinePaymentController::paypalIndex/paypalpayment` ·
     SSLCommerz → `Backend/SslCommerzPaymentController` + `Backend/AdminSslCommerzController` ·
     Aamarpay → `AamarpayController` + `Backend/AdminAamarpayController` ·
     bKash → `Backend/BkashController` / `AdminBkashController` ·
     Skrill → `Backend/SkrillController` / `AdminSkrillController` ·
     Razorpay → **aucun contrôleur** (clés stockées seulement) · Paystack → **rien**.
  2. **Routes en dur, nommage non uniforme** (`routes/web.php`) : l.673-676
     `POST /success · /cancel · /ipn` (AdminSslCommerz) · l.681 `GET payment-cancelled`
     (AdminSkrill) · l.685 `POST /aamarpay-success` · l.946-949 (mêmes routes
     dupliquées côté marchand) · l.187-188 `ANY /subscription/success · /cancel`
     (PlanController::StripePaymentSuccess).
     ⚠️ **Aucun webhook signé nulle part.** SSLCommerz a un `/ipn`, les autres se
     contentent d'une redirection de retour ; l'abonnement Stripe se valide sur un
     simple `GET /subscription/success`. C'est exactement ce que la décision
     « le webhook signé est la seule source de vérité » vise à ne pas reproduire.
  3. **Formulaires en dur dans les vues** : `resources/views/backend/setting/payout_setup/index.blade.php`
     et `.../merchant_panel/settings/online_payment_setup/index.blade.php` — un
     `<form>` par passerelle avec la constante dans l'URL
     (`route('payout.setup.settings.update', \App\Enums\PayoutSetup::STRIPE)`).
     7 blocs recopiés, aucun ne cible `PAYSTACK`.
  - La « sélection » se réduit à un `switch` sur la constante qui **normalise des
    cases à cocher**, sans rien instancier :
    `Repositories/PayoutSetup/PayoutSetupRepository.php:14-42`, dupliqué dans
    `Repositories/MerchantOnlinePaymentSetup/PaymentSetupRepository.php:15-38`.
    À l'exécution, c'est **la vue affichée** qui détermine la passerelle.
- Stockage config/identifiants gateway : table **`settings`** (`company_id`, `key`,
  `value`). Écriture : `PayoutSetupRepository::update()` l.44-53. Lecture : helper
  **`globalSettings('cle')`** (`Helper.php:809`), qui applique `Setting::companywise()`
  — sauf pour un superadmin où il force `company_id = 1`.
  Convention de clés `<gateway>_<champ>` : `stripe_key`, `stripe_secret`,
  `sslcommerz_store_id`, `bkash_app_secret`, `paystack_key`…
  **Rien en `.env`** : tout est en base, modifiable depuis l'admin.
  - 🐞 **Bug à connaître** : le `Setting::where('key', $key)` de la mise à jour
    **n'est pas filtré par `company_id`**. Une société qui enregistre ses clés
    **écrase la ligne d'une autre société** portant la même clé. Les identifiants
    sont censés être par locataire ; l'écriture ne l'est pas.
- Classe de référence (Paystack) — fichier : **INEXISTANTE.**
  `grep -ril "paystack"` (hors `vendor/`) → 3 fichiers, aucun n'est du code :
  `app/Enums/PayoutSetup.php:12` (`CONST PAYSTACK = 10;`) et
  `lang/{en,es,zh}/levels.php:236-237` (libellés `paystack_key`, `paystack_secret`).
  Pas de contrôleur, pas de service, pas de vue, pas de route, **pas même la
  traduction française**. Paystack est un moignon : une constante et deux libellés
  d'un formulaire jamais écrit.

### ⇒ Conséquences pour le chantier FedaPay
Écrire un flux complet : `FedaPayController` + service HTTP (Guzzle, déjà présent),
constante `FEDAPAY = 12` dans `PayoutSetup`, formulaire dans les **deux** vues de
réglages, routes, et un webhook **signé + idempotent** — le premier du dépôt,
aucune passerelle existante ne servant de modèle.

**Deux décisions à trancher avant de coder :**
1. Clés en base (convention du socle, mais bug de scoping ci-dessus) ou en `.env`
   (ce que prévoit `web/CLAUDE.md` avec `FEDAPAY_*`) ?
2. Corrige-t-on le `Setting::where('key')` non scopé, ou l'évite-t-on ?

## Bloc D — Wallet marchand  ⭐
- Modèle / table du wallet : **deux choses distinctes**.
  1. **Le solde** = une simple **colonne**, pas une table :
     `wallet_balance decimal(16,2) default 0` sur la table `merchants`
     (`database/migrations/2014_10_11_000001_create_merchants_table.php:25`),
     portée par `App\Models\Backend\Merchant`. **Seule source de vérité du solde,
     jamais recalculée depuis l'historique.**
  2. **L'historique** = table **`wallets`**
     (`database/migrations/2023_10_17_122352_create_wallets_table.php`) :
     `company_id` (FK `general_settings`), `source` (libellé humain),
     `user_id`, `merchant_id`, `transaction_id`, `amount decimal(22,2)`,
     `type`, `payment_method`, `status`, timestamps.
     Modèle `app/Models/Backend/Wallet.php` : `merchant()`, `user()`,
     `getMyStatusAttribute()`, `scopeCompanywise()`.
  - Enums `app/Enums/Wallet/` : `WalletType` (INCOME=1, EXPENSE=2) ·
    `WalletStatus` (PENDING=1, APPROVED=2, REJECTED=3) ·
    `WalletPaymentMethod` (**OFFLINE=1, WALLET=2** — `STRIPE`/`PAYPAL`/`SKRILL`
    sont **commentées** dans le fichier).
  - ⚠️ **Chantier XOF** : `amount decimal(22,2)` et `wallet_balance decimal(16,2)`
    = **2 décimales partout**, à traiter au passage au FCFA entier.
  - ⚠️ **`transaction_id` n'a aucune contrainte d'unicité** → pas de filet
    d'idempotence pour le futur webhook.
- Service + méthode de crédit (signature) : `app/Repositories/Wallet/WalletRepository.php`
  (lié à `WalletInterface` dans `AppServiceProvider:111`). **Trois méthodes écrivent
  le solde** :
  | Méthode | Signature | Effet sur `wallet_balance` |
  |---|---|---|
  | `approved` | `approved($id): bool` — **l.129** | `+= $wallet->amount` (l.135) |
  | `adminstore` | `adminstore($request): bool` — **l.178** | `+= $request->amount` (l.193) |
  | `delete` | `delete($id)` — l.207 | `-= $wallet->amount` si statut était APPROVED (l.212) |
  - Les **débits** (frais de livraison) n'utilisent pas ce repository :
    `ParcelRepository:449,654` et `MerchantParcelRepository:265,424`, soustraction directe.
  - **Chemin d'une recharge réussie — deux parcours, aucun n'est un paiement en ligne :**
    - **A — demande marchand + validation manuelle admin** (seul parcours marchand) :
      `GET /my-wallet/recharge` → `WalletController::recharge` (`routes/web.php:939`) ·
      `POST /my-wallet/recharge-add` → `WalletController::rechargeAdd:28` (l.940),
      valide `amount` (`required|numeric|gt:0`) puis `WalletRepository::store()` (l.95)
      qui crée la ligne en **PENDING / OFFLINE / INCOME — rien n'est crédité** ;
      **`// $this->repo->approved($wallet->id);` est COMMENTÉ** (`WalletController:39`).
      Puis admin : `GET /wallet-request/` → `requestIndex` (l.703) ·
      `PUT /wallet-request/approve/{id}` → `approve:56` (l.706, permission
      `wallet_request_approve`) → **`WalletRepository::approved($id)`** = le crédit.
    - **B — recharge saisie par l'admin** : `POST /wallet-request/recharge` →
      `adminstore:79` (l.704, permission `wallet_request_create`, FormRequest
      `App\Http\Requests\Wallet\StoreRequest`) → ligne créée **directement en
      APPROVED** + crédit dans la foulée.
- Fichiers cités : `app/Repositories/Wallet/WalletRepository.php` (l.95 `store`,
  l.118 `paymentStatus`, l.129 `approved`, l.151 `rejected`, l.162 `expense`,
  l.178 `adminstore`, l.207 `delete`) ·
  `app/Repositories/Wallet/WalletInterface.php` ·
  `app/Http/Controllers/Backend/MerchantPanel/WalletController.php` (l.28, 39, 56, 79) ·
  `app/Models/Backend/Wallet.php` · `app/Models/Backend/Merchant.php` ·
  `app/Enums/Wallet/{WalletType,WalletStatus,WalletPaymentMethod}.php` ·
  `database/migrations/2023_10_17_122352_create_wallets_table.php` ·
  `database/migrations/2014_10_11_000001_create_merchants_table.php:25` ·
  `routes/web.php` (l.698-707 admin, l.933-942 marchand) ·
  `app/Providers/AppServiceProvider.php:111`

### ⇒ Cinq constats qui pèsent sur le branchement FedaPay
1. **Aucun paiement en ligne n'alimente le wallet aujourd'hui.** Il faudra ajouter
   `FEDAPAY = 3` à `WalletPaymentMethod`.
2. **`approved()` n'est pas transactionnel.** Contrairement à `adminstore()`
   (encadré par `DB::beginTransaction/commit/rollBack`), elle fait deux `save()`
   nus dans un `try/catch` qui avale l'exception et renvoie `false`. Si le second
   échoue : solde crédité, ligne restée `PENDING` — donc **recréditable**.
3. **`approved()` ne vérifie pas le statut de départ.** Deux appels sur la même
   ligne = **crédit doublé**. C'est le point exact où un webhook rejoué casse tout.
   L'idempotence devra être garantie **en amont**, et l'absence d'index unique sur
   `wallets.transaction_id` prive du filet le plus simple.
4. **`paymentStatus($orderId, $transactionId, $status)`** (l.118) pose
   `transaction_id` + `status` et **ne crédite pas**. Écrite pour un retour de
   passerelle jamais branché. ⚠️ Lui passer `WalletStatus::APPROVED` marquerait la
   ligne approuvée **sans toucher au solde**.
5. **Route morte** : `routes/web.php:941` déclare `POST /my-wallet/recharge-status`
   → `WalletController::rechargeStatus`, **méthode inexistante** (`grep` sans
   résultat sur tout `app/Http/Controllers/`). Vestige du retour de passerelle
   abandonné ; appeler cette route lève une erreur.

## Bloc E — Abonnements SaaS  ⭐
- Service + méthode d'activation/renouvellement :
  **`CompanyRepository::switchPlan($request): bool`** —
  `app/Repositories/Superadmin/Company/CompanyRepository.php:348-381`. Unique point
  d'activation, pour l'achat comme pour le changement de plan. Elle :
  1. recalcule les permissions du plan (`$this->permissions($plan)`) et les écrit
     sur `users.permissions` (l.352-355) ;
  2. **crée systématiquement une nouvelle ligne `subscriptions`** (l.360-370) avec
     `start_date = now()` et `expired_date = now()->addDays($plan->days_count)` ;
  3. met à jour `general_settings.subscription_id` et `.plan_id` (l.373-375).
  - ⚠️ **Un renouvellement n'additionne pas les jours restants** : `expired_date`
    repart de `now()`. Renouveler avant l'échéance **perd** le reliquat.
  - ⚠️ Pas de transaction : `try/catch` qui avale l'exception et renvoie `false`.
    Un échec après le `save()` des permissions laisse l'utilisateur avec les droits
    du nouveau plan **sans abonnement**.
  - **Contrôle d'accès** = helper **`subscriptionCheck()`** (`Helper.php:945`), simple
    comparaison `strtotime(today) <= strtotime(expired_date)`, appliqué par le
    middleware **`subscriptionCheck`** (`app/Http/Middleware/subscriptionCheckMiddleware.php`),
    monté sur `routes/web.php:191` et retiré au cas par cas via
    `->withoutMiddleware('subscriptionCheck')` (profil/mot de passe, l.307-311 et 835-838).
    Le superadmin (`UserType::SUPER_ADMIN`) est exempté.
- Tables plans / abonnements :
  - **`plans`** (`database/migrations/2023_12_24_102349_create_plans_table.php`) :
    `name`, `parcel_count`, `deliveryman_count`, `days_count`, `price decimal(22,2)`,
    `description`, `position`, `modules` (longText = liste des permissions), `status`.
  - **`subscriptions`** (`database/migrations/2023_12_28_090620_create_subscriptions_table.php`) :
    `company_id` (FK `general_settings`), `user_id`, `plan_id` (FK `plans`),
    `price decimal(16,2)`, `parcel_count`, `deliveryman_count`, `days_count`,
    `start_date`, `expired_date`, timestamps.
    **Copie des quotas du plan au moment de l'achat** (le plan peut changer ensuite).
  - ⚠️ **Aucune colonne de paiement** : ni `transaction_id`, ni `payment_method`,
    ni `status`, ni `gateway`. Rien ne relie un abonnement à sa transaction —
    donc **rien sur quoi bâtir l'idempotence** d'un webhook FedaPay.
  - ⚠️ **XOF** : `price decimal(22,2)` / `decimal(16,2)` = 2 décimales.
  - `Subscription` (`app/Models/Backend/Subscription.php`) : `company()`, `plan()`,
    `user()` — **pas de `scopeCompanywise()`**, le filtrage est fait à la main
    (`PlanController::subscriptionHistory:88-95`, `User::subscription()` l.153).
- Point où un paiement réussi active l'abonnement :
  `PlanController::StripePaymentSuccess:137` → `$this->companyRepo->switchPlan($request)`.
  - **Chemin complet** : `GET /subscription` → `PlanController::subscription:79`
    (`routes/superadmin.php:69`) → `subscriptionPayment:105` qui lit
    `Setting::where('company_id',1)->where('key','stripe_secret_key')` puis crée une
    **Stripe Checkout Session codée en dur** (l.115-135) → redirection vers Stripe →
    retour sur `ANY /subscription/success` (`routes/web.php:187`) →
    `StripePaymentSuccess` → `switchPlan()`.
  - 🚨 **FAILLE — l'activation ne vérifie rien.** Le `success_url` est
    `route('subscription.success', ['plan_id' => $plan->id, 'user_id' => Auth::user()->id])`,
    et `StripePaymentSuccess` passe `$request` **directement** à `switchPlan()` sans
    consulter Stripe (pas de `Session::retrieve`, pas de vérification de signature,
    pas de contrôle du `client_reference_id`). Conséquences :
    **appeler `/subscription/success?plan_id=X&user_id=Y` en GET suffit à activer
    n'importe quel plan, sans payer, pour n'importe quel utilisateur** (`user_id`
    est fourni par l'appelant, jamais recoupé avec la session authentifiée).
    Aucun rejeu n'est bloqué non plus.
  - ⚠️ **Devise codée en dur `'USD'`** et `unit_amount = (double)$plan->price * 100`
    (l.121-126) — incompatible avec XOF, que Stripe traite en zéro-décimale.
  - ⚠️ **Clés Stripe toujours lues sur `company_id = 1`** : l'abonnement SaaS passe
    par le compte plateforme, jamais celui du locataire. C'est cohérent avec la
    règle « clés FedaPay plateforme = abonnements SaaS » de `.claude/rules/multitenant.md`.
  - Voie parallèle **sans paiement** : `super-admin/company/subscription/switch/{id}`
    → `switchSubscription` / `switchSubscriptionStore` (`routes/superadmin.php:96-97`,
    permission `company_subscribe`) — le superadmin attribue un plan à la main.

### ⇒ Conséquences pour le chantier FedaPay (abonnement)
1. **Reprendre `switchPlan()` comme point d'activation unique** — ne pas dupliquer
   la création de `subscriptions`.
2. **Le webhook devra apporter son propre garde-fou d'idempotence** : la table
   `subscriptions` n'a aucune colonne de transaction. Prévoir a minima
   `transaction_id` + index unique, ou une table de transactions dédiée.
3. **Corriger ou contourner `StripePaymentSuccess`** : reproduire ce schéma
   (activation sur simple GET de retour) reconduirait la faille. La décision
   « le webhook signé est la seule source de vérité » y répond directement.
4. **Traiter le renouvellement** : décider si `expired_date` cumule le reliquat.

### ✅ Chantier 3, seconde moitié — abonnement par FedaPay (2026-09-04)
| Élément | Où |
|---|---|
| Paiement d'un plan | `POST /subscription/fedapay` → `FedaPayController::subscribe` (session web, hors `subscriptionCheck` : un plan expiré doit pouvoir être renouvelé) |
| Clés | **plateforme** (`.env`), `company_id` volontairement nul à l'initialisation — règle de `.claude/rules/multitenant.md` |
| Journal / idempotence | `fedapay_transactions` avec `purpose = subscription`, colonnes `plan_id` et `user_id` ajoutées (migration `2026_09_04`) ; `subscriptions` reste sans colonne de paiement |
| Activation | le webhook `transaction.approved`, sous le même verrou que le wallet, appelle **`CompanyRepository::switchPlan()`** — point 1 respecté, même chemin que Stripe |
| Retour de page | `fedapay/callback?reference=…` redirige vers la page des plans avec un message « en cours » ou « actif » ; **n'active rien** |
| Page des plans | bouton « Payer par Mobile Money » si les clés plateforme sont renseignées et le plan payant |
| Tests | `FedaPaySubscriptionTest` : initiation, plan gratuit refusé, **rejeu du webhook = une seule activation**, refus, signature absente, retour de page inerte |

⏳ Point 4 (renouvellement) **non tranché** : `switchPlan()` repart de `now()`, comme
pour Stripe. Le reliquat d'un plan renouvelé avant échéance est toujours perdu — à
décider avec le métier, hors de ce chantier.

## Bloc F — Entreprise/marchand & identité légale (IFU/RCCM/CNSS)
> **Deux entités sans rapport** : une « entreprise » (locataire) et un « marchand »
> (client du locataire) ont des tables, formulaires et validations distincts.

- Modèle(s) concerné(s) :
  - **Entreprise : il n'existe pas de modèle `Company`.** L'entreprise **est**
    `App\Models\Backend\GeneralSettings` — la même table qui sert de table de
    configuration (identité d'affichage + charte graphique mélangées).
  - **Marchand** : `App\Models\Backend\Merchant` (données métier) +
    `App\Models\User` (données personnelles), reliés par `merchants.user_id`.
- Migrations correspondantes :
  - `database/migrations/2014_05_31_094551_create_general_settings_table.php` —
    `name`, `phone`, `email`, `address`, `currency`, `copyright`, `logo`,
    `light_logo`, `favicon`, `current_version`, `par_track_prefix`,
    `invoice_prefix`, `primary_color`, `text_color`, `status`, `subscription_id`,
    `plan_id`, `purchase_code`.
    ⚠️ **Aucun champ d'identité légale** : ni IFU/SIRET, ni RCCM, ni TVA, ni forme
    juridique. Uniquement `name`/`phone`/`email`/`address`.
  - `database/migrations/2014_10_11_000001_create_merchants_table.php` —
    `company_id` (FK `general_settings`), `user_id`, `business_name`,
    `merchant_unique_id`, `current_balance`, `opening_balance`, `wallet_balance`,
    `vat`, `cod_charges`, **`nid_id`** (FK `uploads`), **`trade_license`**
    (FK `uploads`), `payment_period`, `status`, `address`, `wallet_use_activation`,
    `return_charges`, `reference_name`, `reference_phone`.
  - `database/migrations/2014_10_11_000000_create_users_table.php` — `name`,
    `unique_id`, `email`, `mobile`, **`nid_number`** (string), `company_id`,
    `company_owner`, `user_type`, `hub_id`, `designation_id`, `department_id`,
    `address`, `otp`, `status`, `verification_status`, `permissions`…
  - ⚠️ **Aucune migration additive n'existe** : `grep` sur `table('merchants'`,
    `table('general_settings'`, `table('users'` dans `database/migrations/` ne
    renvoie **rien**. Tout est dans les migrations de création → les nouveaux
    champs passeront par de vrais `ALTER`, **sans précédent à imiter**.
- Formulaire d'inscription + validation :
  | | Entreprise | Marchand |
  |---|---|---|
  | Vue publique | `resources/views/backend/super-admin/company/company_signup.blade.php` | `resources/views/backend/merchant/sign_up.blade.php` |
  | Vue admin | (via `StoreRequest`) | `resources/views/backend/merchant/create.blade.php` |
  | Contrôleur | `Backend/Superadmin/CompanyController::signUp:121` / `signUpStore:126` | `Backend/MerchantController::signUp:47` / `signUpStore:75` |
  | Routes | `routes/superadmin.php:60-61` | `routes/web.php:168-169` |
  | FormRequests | `app/Http/Requests/Company/{SignUp,Store,Update}Request.php` | `app/Http/Requests/Merchant/{SignUp,Store,Update,Otp}Request.php` |
  - **Champs form. entreprise (public)** : `company_name`, `domain`, `name`,
    `email`, `mobile`, `address`, `password`, `policy` (case à cocher **non
    validée côté serveur**).
  - **Validation `Company/SignUpRequest`** :
    `company_name: required` · `domain: required|unique:domains,domain_name|regex:/(^[a-zA-Z]+[a-zA-Z0-9\-]*$)/u`
    · `name: required|string|max:191` · `email: required|string|unique:users`
    · `password: required|string` · `mobile: required|numeric|unique:users`
    · `address: string|max:191`.
    `Company/StoreRequest` + `UpdateRequest` ajoutent `currency`, `logo`, `plan_id`,
    `nid_number`, `designation_id`, `department_id`, `image`, `joining_date`, `status`.
    ⚠️ `email` **sans règle `email`**, `password` **sans longueur minimale**,
    et `unique:users` **global — non scopé par société**.
  - **Champs form. marchand (public)** : `business_name`, `full_name`, `hub_id`,
    `mobile`, `password`, `address`, `policy`.
  - **Champs form. marchand (admin, 19)** : `merchant_unique_id`, `business_name`,
    `email`, `opening_balance`, `vat`, `nid`, `trade_license`, `name`, `mobile`,
    `password`, `hub`, `status`, `image_id`, `reference_name`, `reference_phone`,
    `payment_period`, `wallet_use_activation`, `address`, `return_charges`.
  - **Validation `Merchant/SignUpRequest`** :
    `business_name: required|string` · `full_name: required|string|max:191`
    · `address: required|string|max:191` · `mobile: required|numeric|digits_between:11,14`
    · `password: required|min:6`.
    Plus un `withValidator()` vérifiant l'unicité du mobile **scopée par
    `company_id`** (`settings()->id`) — **le bon modèle**, contrairement au
    parcours entreprise. `Store`/`UpdateRequest` ajoutent `name`, `hub`, `status`,
    `payment_period` avec le même contrôle scopé sur email **ou** mobile.
    ⚠️ `nid`, `trade_license`, `vat`, `opening_balance`, `return_charges` du
    formulaire admin **ne sont validés nulle part**.
  - ⚠️ `mobile: digits_between:11,14` — calibré sur le format BD, **à revoir pour
    le Bénin** (numéros à 8 chiffres, +229 → 11 avec indicatif).

### ✅ Chantier 2 réalisé le 2026-08-17
- **Migration additive** `2026_08_17_120000_add_legal_identifiers_…` — la **première
  du dépôt** : tout le socle tient dans ses migrations de création. Colonnes `ifu(13)`,
  `rccm(50)`, `cnss(30)` sur **`merchants`** (la PME cliente) **et sur
  `general_settings`** (le transporteur), une facture SYSCOHADA devant porter les
  identifiants des deux parties.
- **Nullable au schéma, obligatoire à la validation** : une colonne `NOT NULL` aurait
  fait échouer la migration sur une base peuplée, et empêché de régulariser un marchand
  déjà enregistré. L'exigence vit dans les FormRequest.
- **`App\Rules\LegalIdentifier`** centralise les trois formats — IFU 13 chiffres, RCCM
  et CNSS tolérants — parce qu'ils sont demandés à **4 formulaires**. Volontairement
  permissifs : ils écartent l'absurde sans prétendre valider l'existence d'un numéro.
- **Obligation graduée** : `ifu` + `rccm` **requis à l'inscription en ligne**
  (`Merchant/SignUpRequest`), `cnss` optionnel (elle ne concerne que les employeurs) ;
  les trois **facultatifs** côté admin (`Store`/`UpdateRequest`) — un administrateur
  enregistre parfois une PME avant d'avoir ses pièces.
- **Persistance aux 3 points d'écriture** de `MerchantRepository` (`store`,
  `signUpStore`, `update`), sous `filled()` : une mise à jour partielle n'efface pas un
  IFU déjà saisi.
- **Aucune modification d'API nécessaire** : le modèle `Merchant` étant sérialisé en
  entier, les trois champs apparaissent d'office dans `/signin` et `/profile`.
- Vérifié bout en bout par appels réels : IFU à 9 chiffres → refusé (« L'IFU doit
  comporter exactement 13 chiffres. »), inscription sans IFU → refusée, inscription
  complète → marchand créé avec ses identifiants en base. `php artisan test` : 2 passed.
- ✅ **Les 5 formulaires sont complétés** (2026-08-18) :
  | Formulaire | Champs | Obligation |
  |---|---|---|
  | `merchant/sign_up` (inscription en ligne) | IFU · RCCM · CNSS | IFU + RCCM **requis** |
  | `merchant/create` · `merchant/edit` (admin) | IFU · RCCM · CNSS | facultatifs |
  | `general_settings/index` (transporteur) | IFU · RCCM · CNSS | facultatifs |
  | `super-admin/company/company_signup` | IFU · RCCM | facultatifs |
  Persistance ajoutée à `GeneralSettingsRepository::update` et
  `CompanyRepository` (création du locataire), sous `filled()`.
  Validation ajoutée aux 3 `Company/*Request` via la même règle `LegalIdentifier`.
- ⚠️ **Le formulaire marchand n'est pas testable depuis `localhost`** : c'est un
  **domaine central** (`config/tenancy.php`), il sert `routes/superadmin.php` et
  renvoie une page « Page Not Found » (en HTTP 200) pour les routes locataires de
  `routes/web.php`. Vérifier l'inscription marchand exige un sous-domaine locataire ;
  l'API `/api/v10/register`, elle, reste joignable et a servi aux contrôles.

### ⇒ Conséquences initiales relevées pour le chantier 2 (IFU / RCCM / CNSS)
1. **Ce qui s'en rapproche existe déjà mais ne convient pas** : `merchants.trade_license`
   et `merchants.nid_id` sont des **`foreignId` vers `uploads`** — des scans, pas des
   numéros exploitables. Seul `users.nid_number` est un numéro texte, côté personne
   physique. Prévoir de **nouvelles colonnes texte** ; `trade_license` peut rester
   comme justificatif à côté du numéro RCCM.
2. **Emplacement à trancher** : côté marchand, `business_name` est sur `merchants`
   mais `nid_number` sur `users` — la convention du socle n'est pas tranchée. Côté
   entreprise, tout irait sur `general_settings`, qui mêle déjà config et identité.
3. **Coût par champ ajouté : 4 formulaires + 7 FormRequest** (inscription publique
   et création admin, pour l'entreprise comme pour le marchand).
4. **Valider `mobile` au format béninois** en même temps (chantier 1).

## Bloc G — Facturation (SYSCOHADA)
> ⚠️ **Il n'y a ni « service de facturation », ni PDF.** Aucune bibliothèque PDF
> n'est installée (`grep -niE "dompdf|snappy|mpdf|tcpdf"` sur `composer.json` → rien)
> et les deux routes qui promettent un PDF pointent vers une **méthode inexistante**.

- Modèle/service de facturation :
  - **Tables** :
    - `invoices` (`database/migrations/2022_10_11_121745_create_invoices_table.php`) :
      `company_id`, `merchant_id`, **`invoice_id` (string, `unique`)**,
      `invoice_date` (**string** `d-m-Y`), `total_charge`, `cash_collection`,
      `current_payable`, `parcels_id` (longText, **inutilisé**), `status`.
    - `invoice_parcels` (`…2024_09_04_063833_create_invoice_parcels_table.php`) :
      `company_id`, `invoice_id` (FK), `parcel_id`, `parcel_status`,
      `total_delivery_amount`, `collected_amount`, `return_charge`, **`vat_amount`**,
      `cod_amount`, `total_charge_amount`, `current_payable`.
    - `vat_statements` (`…2022_05_24_141546_create_vat_statements_table.php`) :
      `company_id`, `parcel_id`, `type` (1=income, 2=expense), `amount`, `date`, `note`.
  - **Modèles** : `App\Models\Backend\InvoiceParcel`, `App\Models\Backend\VatStatement`,
    `App\Models\Backend\Merchantpanel\Invoice`.
    Statuts `app/Enums/InvoiceStatus.php` : `UNPAID=0`, `PROCESSING=2`, `PAID=3`
    (pas de `1`).
  - **Génération = `InvoiceRepository::store($merchant_id)`**
    (`app/Repositories/Invoice/InvoiceRepository.php:47-176`, 130 lignes, **seul
    point de création**). Déclenchée par 3 voies :
    1. **Cron** : `app/Console/Kernel.php:21` → `$schedule->command('invoice:generate')->daily('13:00')`,
       commande `app/Console/Commands/Invoice.php` (boucle sur tous les marchands).
    2. **Manuel global** : `GET settings/invoice-generate-menually` →
       `MerchantInvoiceController::InvoiceGenerateMenually` (`routes/web.php:622`,
       permission `invoice_generate_menually`).
    3. **Par marchand** : `GET merchant/invoice-generate/{id}` →
       `MerchantController::invoiceGenerate:188` (`routes/web.php:320`).
  - **Logique** (l.51-70) : facture produite seulement si la dernière date d'au moins
    `merchants.payment_period` jours **et** qu'aucune n'existe déjà aujourd'hui.
    Agrège les colis `DELIVERED` / `partial_delivered` + les retours dont
    `invoice_id IS NULL`, puis marque chaque colis avec l'`invoice_id`.
    Total = `total_delivery_amount + vat_amount + return_charges` (l.83), retours
    traités à part (charge de retour en négatif, l.128).
  - ⚠️ Le `try/catch` final (l.173) renvoie `false` **sans `DB::rollBack()`** alors
    qu'aucune transaction n'est ouverte : une erreur en milieu de boucle laisse une
    **facture partiellement remplie**, avec une partie des colis déjà rattachés.
  - ⚠️ Tous les montants sont en `decimal(…,2)` → **2 décimales**, à reprendre pour le FCFA.
- Gabarit (blade/PDF) :
  | Vue | État |
  |---|---|
  | `backend/merchant/invoice/index.blade.php` | liste, **active** |
  | `backend/merchant/invoice/invoice_details.blade.php` | détail, **active** |
  | `backend/merchant_panel/invoice/{index,invoice_details}.blade.php` | côté marchand, **actives** |
  | `backend/invoice/paid_invoice_list.blade.php` | **active** |
  | `backend/merchant/invoice/invoice_pdf.blade.php` | **ORPHELINE** — référencée nulle part |
  | `backend/merchant/invoice/Invoice_mail_pdf.blade.php` | **ORPHELINE** |
  - 🐞 **Routes PDF mortes** : `routes/web.php:368` et `:910` déclarent
    `MerchantInvoiceController::InvoicePdf`, **méthode inexistante**. Méthodes
    réelles du contrôleur : `index`, `InvoiceDetails`, `StatusUpdate`,
    **`InvoiceCSV`**, `InvoiceGenerateMenuallyIndex`, `InvoiceGenerateMenually`,
    `PaidInvoice`. Seul le *repository* a un `InvoicePdf()` (l.211) — qui se
    contente de renvoyer le modèle `Invoice`, sans rien produire.
    ⇒ **2ᵉ route morte du dépôt**, après `WalletController::rechargeStatus`.
  - Seul export fonctionnel : **CSV** (`MerchantInvoiceController::InvoiceCSV:44`).
  - `app/Mail/InvoicePDFSend.php` existe, expéditeur **codé en dur** `admin@example.com`.
- Numérotation + gestion TVA actuelles :
  - **Numérotation** — `InvoiceRepository::invoiceId($merchant_id)`, méthode
    **privée**, l.168-176 :
    ```php
    $prefix       = Str::upper(settings()->invoice_prefix) . '-';
    $invoicecount = Invoice::companywise()->get()->count();
    $invoice_id   = $prefix . $merchantId . ($invoicecount + 1);
    ```
    Format `PREFIXE-<id_marchand><compteur+1>`, préfixe = `general_settings.invoice_prefix`.
    **Trois défauts bloquants pour SYSCOHADA** :
    1. 🐞 **Concaténation ambiguë** : marchand 1/compteur 23 et marchand 12/compteur 3
       donnent tous deux `PRE-123`. `invoices.invoice_id` étant `unique`, le 2ᵉ insert
       **échoue** — et l'exception est **avalée par le `catch`**.
    2. **`count()+1` n'est pas séquentiel** : toute suppression réutilise un numéro.
       Pas de séquence, pas de verrou → condition de course en génération concurrente.
    3. **Aucune notion d'exercice comptable** : ni année, ni remise à zéro, ni
       chronologie garantie. `invoice_date` est un **`string`** `d-m-Y`, donc ni
       triable ni comparable en SQL.
  - **TVA** — **il n'existe pas de taux de TVA de l'entreprise**. Le taux est un
    pourcentage **par marchand** : colonne `merchants.vat decimal(16,2)`, saisie au
    formulaire marchand (`MerchantRepository:90-91`, `:313-314`), initialisée à `0` (l.188).
    Calcul (seul endroit fait côté serveur — `ParcelRepository:2185-2187`,
    livraison partielle) :
    ```php
    $total_charges = $cod_charges_amount + $parcel->delivery_charge
                   + $parcel->liquid_fragile_amount + $parcel->packaging_amount;
    $vat_amount    = ($total_charges / 100) * $parcel->vat;
    ```
    La TVA porte sur les **frais de service du transporteur**, pas sur la valeur du
    colis — cohérent avec une prestation de livraison.
  - 🚨 **Sur le chemin nominal, la TVA n'est PAS calculée par le serveur.** À la
    création d'un colis (`ParcelRepository:340, 516, 710` et
    `MerchantParcelRepository:225, 370, 554`) :
    ```php
    $chargeDetails      = json_decode($request->chargeDetails);
    $parcel->vat        = $chargeDetails->vatTex;
    $parcel->vat_amount = $chargeDetails->VatAmount;
    ```
    `chargeDetails` est un **JSON posté par le navigateur** — montants calculés en
    **JavaScript** dans les vues (`backend/parcel/create.blade.php:351`,
    `merchant_panel/parcel/create.blade.php:334`…) et enregistrés **tels quels, sans
    recalcul ni validation**. Un client qui modifie ce champ **choisit sa propre TVA
    et ses propres frais de livraison**. Frontière de confiance franchie sur le
    montant facturé.
  - `vat_statements` sert à l'agrégation comptable mais n'est écrit qu'au fil des
    événements de colis (`ParcelRepository:1667`, `:1855`).

### ⇒ Conséquences pour le chantier 4 (SYSCOHADA)
1. **Refaire la numérotation entièrement** : séquence par société **et par exercice**,
   `invoice_date` en `date` (pas `string`), verrou anti-course.
2. **Écrire le rendu PDF de zéro** : ni bibliothèque installée, ni gabarit branché
   (2 blades orphelines + 2 routes mortes à réparer ou supprimer).
3. **Mentions légales béninoises** (IFU, RCCM du transporteur *et* du client)
   ⇒ **dépend du chantier 2**.
4. **Taux de TVA au niveau entreprise** plutôt que par marchand.
5. **Rapatrier le calcul des montants côté serveur** — préalable non négociable à
   une facture opposable.

### ✅ Chantier 4 — relevés de règlement SYSCOHADA (2026-09-04)
| Conséquence | Réponse |
|---|---|
| 1. Numérotation | `App\Services\Invoicing\InvoiceNumbering` : `PREFIXE-AAAA-NNNNNN`, séquence par société **et par exercice** (`invoice_sequences`, `lockForUpdate`), réservée dans la transaction qui crée la facture. `invoices.issued_on` (date), `fiscal_year`, `sequence` + index unique ; `invoice_date` (chaîne) conservée pour les vues du socle. Migration `2026_09_04_120000`, avec reprise des factures existantes (numéros inchangés). |
| 2. PDF | `barryvdh/laravel-dompdf ^2.0` (dompdf 2, compatible PHP 8.2). Gabarit `backend/invoice/statement_pdf.blade.php`, rendu depuis **`SettlementStatement::for()`**, seule source des montants (lignes figées de `invoice_parcels`, entiers XOF). Les deux routes mortes (`merchant.invoice.pdf`, `merchant.panel.invoice.pdf`) pointent enfin sur `MerchantInvoiceController::InvoicePdf`. Pour l'app : `GET /api/v10/invoice-pdf-link/{id}` → lien **signé 15 min** vers `invoice/statement/{invoice}/pdf` (route publique `signed`, hors tenant). |
| 3. Mentions légales | Transporteur (`general_settings`) et marchand (`merchants`) : raison sociale, **IFU, RCCM**, adresse, téléphone — lus par identifiant, jamais via `settings()`. |
| 4. TVA au niveau entreprise | **Non tranché** : le taux reste par marchand (`merchants.vat`, comme `ChargeCalculator`). Le relevé affiche le ou les taux réellement appliqués. |
| 5. Calcul serveur | Acquis par S2. |
| Export SYSCOHADA | `App\Services\Invoicing\SyscohadaJournal` + `config/syscohada.php` (plan de comptes **à valider par l'expert-comptable**) : ventes (4111 / 7061 / 4431), compensation frais-COD (4712 / 4111), reversement à l'état PAYÉ (4712 / 521). CSV `;` + BOM, par relevé (`…/journal/…`) ou par période (`paid/invoice/syscohada-journal`). |
| Transaction | `InvoiceRepository::store()` tourne sous `DB::transaction` : plus de facture à moitié remplie, et le numéro réservé est rendu au rollback. |
| Tests | `SettlementStatementTest` : séquence par société/exercice, numéro et net du relevé généré (= `current_payable`), pas de second relevé le même jour, journal équilibré (5 puis 7 lignes), PDF par lien signé, refus sans signature et pour un autre marchand. |

⚠️ Les gabarits orphelins du socle (`merchant/invoice/invoice_pdf.blade.php`,
`Invoice_mail_pdf.blade.php`) restent en place, non branchés : `InvoicePDFSend`
(expéditeur codé en dur) n'est pas réactivé.

## Bloc H — Localisation & devise (francisation/FCFA)
- Dossier de langues (resources/lang) : ⚠️ **`resources/lang/` N'EXISTE PAS**
  (`ls` → *No such file or directory*). Laravel 10 a déplacé les traductions à la
  racine du projet : **`web/lang/`**.
  ```
  lang/
  ├── ar/  bn/  en/  es/  fr/  in/  zh/
  └── en.json          ← seul fichier JSON
  ```
  - ✅ **`lang/fr/` : 79 fichiers, à parité avec `lang/en/` depuis le 2026-08-15.**
    Trois fichiers manquaient (23 chaînes), **créés depuis** — seule correction de code
    apportée à l'issue de cette cartographie :
    | Fichier créé | Chaînes | Note de traduction |
    |---|---|---|
    | `WalletPaymentMethod.php` | **2** | `OFFLINE` → « Hors ligne », `WALLET` → « Portefeuille » |
    | `WalletStatus.php` | **3** | `PENDING` → « En attente », `APPROVED` → « **Approuvé** », `REJECTED` → « Rejeté » |
    | `addon.php` | **18** | écran Addons rendu par « modules complémentaires », pour ne pas confondre avec l'**extension PHP** ZipArchive citée par `error_msg_install_extension` |
    ⚠️ Écart assumé avec l'anglais : `WalletStatus::APPROVED` y est libellé
    « **Confirm** », ce qui ne correspond ni à la constante ni à
    `WalletRepository::approved()` — le français dit « Approuvé ».
    ⇒ Ces libellés d'état du wallet sont ceux que le chantier FedaPay affichera ;
    ils sont désormais disponibles. `addon.php` ne concerne qu'un écran
    d'administration technique, hors parcours métier.
  - ✅ **Les 12 fichiers incomplets ont été complétés le 2026-08-16.** Le relevé initial
    annonçait « 149 clés manquantes sur 13 fichiers » ; **le compte exact est 144 sur
    12 fichiers**. Deux corrections de méthode :
    - `to_do.php` **était déjà complet** : l'écart venait d'un espace côté anglais
      (`TodoStatus:: PENDING` contre `TodoStatus::PENDING`) que la comparaison des clés
      lisait comme trois entrées différentes.
    - Deux « manquantes » de `validation.php` (`attribute-name`, `rule-name`) sont les
      **placeholders d'exemple de Laravel**, que le français avait simplement traduits
      en `nom-de-l-attribut` : rien à combler.

    Répartition réelle et traitement appliqué :
    | Fichier | Clés absentes | Traitement |
    |---|---|---|
    | `levels.php` | **48** | traduites (écrans superadmin : forfaits, abonnements, clés API) |
    | `parcel.php` | **38** | traduites (métier **et wallet** : `my_wallet`, `wallet_request`, `wallet_recharge`…) |
    | `merchant.php` | **29** | **28 banques supprimées**, `wallet` traduit |
    | `permissions.php` | **12** | traduites |
    | `dashboard.php` · `menus.php` | 4 · 4 | traduites |
    | `delete.php` · `designation.php` · `placeholder.php` | 2 chacun | traduites |
    | `ActivityLogs.php` · `userType.php` · `validation.php` | 1 chacun | traduites (`UserType::SUPER_ADMIN` était absent) |
    ⇒ **116 chaînes traduites**, 28 supprimées. Reste **1 écart théorique**
    (`rule-name`, le placeholder d'exemple) : `lang/fr/` est **aligné sur `lang/en/`**.
  - 🐞 **Les banques : trois listes incohérentes, découvertes en traduisant.**
    - `lang/en/merchant.php` listait **28 banques bangladaises** (`ab_bank_ltd`,
      `brac_bank_ltd`, `dbbl_agent_banking`…), reprises telles quelles dans
      `config/merchantpayment.php`.
    - `lang/fr/merchant.php` contenait, lui, **11 banques marocaines**
      (`attijariwafa_bank`, `al_barid_bank`, `credit_du_maroc`…) — des clés **mortes** :
      absentes de la config, elles n'étaient **jamais affichées**, et la liste déroulante
      servait en réalité les banques bangladaises via le repli `fallback_locale = en`.
    - ⇒ Les deux listes ont été remplacées par **11 établissements béninois**
      (BOA, Ecobank, Société Générale, UBA, NSIA, Banque Atlantique, Orabank, BSIC,
      Coris Bank, BGFIBank, « Autre banque »), avec les clés alignées entre
      `config/merchantpayment.php`, `lang/en/` et `lang/fr/`. **Liste indicative, à
      valider avec le métier.**
    - 🚨 Défaut de conception à corriger séparément :
      `resources/views/backend/merchant/payment/add_payment.blade.php:78` écrit
      `<option value="{{ __('merchant.'.$value) }}">` ⇒ **c'est le libellé traduit, et
      non la clé, qui est enregistré** dans `merchant_payments.bank_name`. Le contenu de
      la colonne dépend donc de la langue de l'utilisateur au moment de la saisie.
  - ⚠️ **Reste bangladais non traité** : `config/merchantpayment.php` conserve
    `account_methods` = `bkash`, `nogod`, `rocket` — les mobile money du Bangladesh,
    à remplacer par **MTN MoMo** et **Moov Money** (chantier FedaPay, bloc C).
  - 🐞 **`lang/fr/statementNote.php` ne se chargeait pas** : apostrophes non échappées
    dans « l'entrepôt » (l.4-7) ⇒ **erreur fatale de parsing** dès qu'un libellé de
    relevé était demandé en français. Corrigé le 2026-08-16. Ce fichier porte les
    intitulés comptables des mouvements (revenus/dépenses par statut de colis) que le
    **chantier SYSCOHADA** (bloc G) va reprendre.
    ⇒ Contrôle à ajouter au fil de la francisation : `php -l` sur chaque fichier de
    `lang/fr/` — la syntaxe n'est vérifiée par rien aujourd'hui.
  - ✅ **`lang/fr.json` créé le 2026-08-16** (5 clés, en regard de `lang/en.json`) :
    les chaînes passant par `__('texte libre')` ne retombent plus sur l'anglais.
    Portée limitée de toute façon — le socle passe presque partout par des fichiers PHP.
  - **Bascule de langue** : `routes/web.php:137` → `GET localization/{language}` →
    `LocalizationController::setLocalization` (`App::setLocale()` +
    `session()->put('locale', …)`), réappliquée à chaque requête par
    `app/Http/Middleware/LanguageManager.php` **depuis la session**.
  - ⚠️ La langue est **par session, pas par société ni par utilisateur** (rien en base).
  - ⚠️ `LanguageManager` n'est monté que sur le groupe `web` (`app/Http/Kernel.php`)
    ⇒ **l'API `/api/v10` n'a aucune gestion de locale**, elle répond toujours dans
    la locale par défaut.
- Locale par défaut (`config/app.php`) : ✅ **basculée le 2026-08-16** —
  `'locale' => env('APP_LOCALE', 'fr')` (l.86) et
  `'timezone' => env('APP_TIMEZONE', 'Africa/Porto-Novo')` (l.73, **UTC+1 sans heure
  d'été**). `'fallback_locale' => 'en'` est **conservé volontairement** : il sert de
  repli aux langues restées incomplètes (`ar`, `bn`, `es`, `in`, `zh`).
  `'faker_locale' => 'en_US'` (l.111) inchangé — ne concerne que les factories.
  Le `.env` ne définissant ni `APP_LOCALE` ni `APP_TIMEZONE`, ces valeurs s'appliquent.
  - ⚠️ **Les sessions ouvertes gardent leur langue** : `LanguageManager` n'écrase la
    locale que si `session('locale')` existe ⇒ un utilisateur ayant choisi l'anglais
    reste en anglais jusqu'à expiration de sa session. Comportement normal, à connaître
    lors d'une recette.
  - 🚩 **Décision à prendre avant la mise en production** : les enregistrements déjà
    écrits l'ont été en **UTC**, les suivants le seront en **UTC+1**. Sur une base
    vierge, sans effet ; sur une base contenant déjà des données, cela crée un mélange.
    L'alternative classique est de **stocker en UTC et convertir à l'affichage** — mais
    le socle affiche les dates directement, sans couche de présentation.
  - ⚠️ `LanguageManager` appelle `Schema::hasTable('settings')` **à chaque requête**
    (`app/Http/Middleware/LanguageManager.php:22`) : une requête SQL par appel, juste
    pour tester l'existence d'une table.
- Helper de formatage monétaire : ✅ **créé le 2026-08-16.** Il n'en existait aucun —
  ni helper, ni directive Blade, ni cast Eloquent, ni `Illuminate\Support\Number`.
  Trois fonctions en fin de `app/Http/Helper/Helper.php`, **seul endroit où un montant
  est mis en forme** :
  | Fonction | Rôle |
  |---|---|
  | `amountValue($m)` | entier brut (`(int) round`) — **API et tout ce qui doit rester analysable** |
  | `currencySymbol()` | `general_settings.currency`, repli sur `FCFA` |
  | `formatAmount($m, $avecDevise = true)` | affichage : entier, milliers séparés par une **espace insécable**, symbole **suffixé** (« 1 500 FCFA ») |
  - **Choix à connaître** : le socle affichait le symbole **avant** le montant
    (`$ 1,234.56`) ; l'usage francophone le place **après**. Le helper étant le point
    unique, revenir en arrière est une ligne à changer.
  - **Devise = une chaîne libre, pas un code ISO** : colonne
    `general_settings.currency` (`string` nullable,
    `…2014_05_31_094551_create_general_settings_table.php`) — inchangé. Le seeder y
    mettait **le symbole** `"$"`, désormais `"FCFA"`
    (`database/seeders/GeneralSettingsSeeder.php:32,51`).
  - ✅ **XOF ajouté au `CurrencySeeder`** (ligne 142 : `Benin`, `Franc CFA (UEMOA)`,
    `XOF`, symbole `FCFA`). La table `currencies` reste **décorative** : ni `position`
    ni `exchange_rate` ne sont consultés à l'affichage (voir plus bas).
  - **Table `currencies` existe mais ne sert pas**
    (`…2022_12_08_104319_create_currencies_table.php` : `country`, `name`, `symbol`,
    `code`, `exchange_rate`, `position`, `status` ; modèle
    `App\Models\Backend\Currency` + `CurrencyRepository`). Simple CRUD d'admin :
    **`position` (préfixe/suffixe) n'est jamais consulté à l'affichage**,
    `exchange_rate` non plus. Lien avec `general_settings.currency` = **recopie
    manuelle du symbole**, sans clé étrangère.
- Endroits supposant 2 décimales : ✅ **traités le 2026-08-16 aux deux premiers
  niveaux**, le troisième reste ouvert.
  | Niveau | État |
  |---|---|
  | **Affichage** | **109** `number_format(…, 2)` des vues remplacés par `formatAmount()` |
  | **Affichage (2ᵉ famille)** | **169** montants qui n'étaient **pas formatés du tout** — `{{ settings()->currency }}{{ $x }}` sortait la valeur brute de la colonne `decimal(16,2)`, donc « 1500.00 ». Encapsulés dans `formatAmount()`. Ce gisement n'apparaissait pas dans le relevé initial, qui ne comptait que les `number_format`. |
  | **API** | **32** `(string) number_format($x, 2, '.', '')` remplacés par `amountValue($x)` ⇒ les montants ne sont plus des chaînes `"1234.00"` mais des **entiers JSON** `1234`. |
  | **Base** | ⏳ **inchangé** : tous les montants restent en `decimal(…,2)` — `merchants.wallet_balance(16,2)`, `wallets.amount(22,2)`, `parcels.vat_amount(13,2)`, `invoices.*(16,2)`, `plans.price(22,2)`… Les décimales sont donc encore stockées, seulement plus affichées. Migration à décider séparément. |
  **Bilan : 278 sites d'affichage passent par le helper ; 82 fichiers modifiés.**
  - ✅ **Moment favorable pour le changement de contrat d'API** : le passage de
    `"1234.00"` à `1234` casserait les apps Flutter — mais elles sont **dépréciées**, et
    `mobile/` comme `mobile-livreur/` **ne consomment encore rien**. Fait maintenant,
    c'est gratuit ; fait plus tard, c'est une migration.
  - ⚠️ **Deux exclusions volontaires**, non monétaires malgré leur mise en forme :
    - `currencies.exchange_rate` (`setting/currency/index.blade.php`) — un taux de
      change garde ses décimales, il conserve donc `number_format(…, 2)`.
    - `merchants.vat` — c'est un **taux en pourcentage**, pas un montant. 🐞 Le socle
      l'affiche pourtant précédé du symbole monétaire (`{{ settings()->currency }}{{
      $merchant->vat }}` ⇒ « FCFA 5.00 » pour un taux de 5 %). **Laissé en l'état :
      c'est un bug d'affichage à corriger avec le chantier 4 (SYSCOHADA)**, pas un
      formatage de montant.

### ⇒ Conséquences pour le chantier 1 (francisation + FCFA)
1. **Langue** : ~~(a) créer les 3 fichiers absents~~ **fait le 2026-08-15** (23 chaînes,
   dont les 5 libellés wallet attendus par FedaPay) ; ~~(b) combler les clés
   manquantes~~ **fait le 2026-08-16** (116 traduites, 28 banques bangladaises
   supprimées et remplacées par 11 béninoises) ; ~~(c) basculer `config/app.php` sur
   `fr` et le fuseau sur UTC+1~~ et ~~(d) créer `lang/fr.json`~~ **faits le
   2026-08-16**. **Restent** : (e) décider si la locale doit être persistée **par
   société** plutôt qu'en session — question ouverte pour l'API, aujourd'hui sans
   locale ; (f) remplacer `account_methods` (bKash/Nagad/Rocket) par MTN MoMo et
   Moov Money ; (g) arbitrer le stockage des dates (UTC ou heure locale) **avant**
   d'accumuler des données.
   ⇒ **Le volet « langue » du chantier 1 est terminé.**
2. **Devise** : ~~introduire un helper puis remplacer les appels~~ **fait le
   2026-08-16** — `amountValue()` / `currencySymbol()` / `formatAmount()` créés,
   278 sites d'affichage et 32 sorties d'API basculés, XOF ajouté au `CurrencySeeder`,
   devise par défaut passée de `"$"` à `"FCFA"`. ~~Point de rupture avec les apps~~ :
   saisi maintenant, tant qu'aucune app ne consomme l'API.
   ⇒ **Le volet « devise » est terminé côté affichage et API.** **Restent** :
   (a) migrer les colonnes `decimal(…,2)` vers des entiers — ou décider de les
   conserver et d'arrondir à l'écriture ; (b) **rapatrier le calcul des montants côté
   serveur** (S2), sans quoi le client continue de poster ses propres décimales ;
   (c) corriger l'affichage de `merchants.vat`, un taux affiché comme un montant ;
   (d) vérifier les écrans en conditions réelles — **aucune vue n'a pu être rendue
   ici**, `vendor/` étant incomplet (voir ci-dessous).
3. 🚩 **Vérification impossible dans l'environnement actuel** : `php artisan` ne
   démarre pas (paquet `nunomaduro/collision` amputé dans `vendor/`), donc ni les vues
   ni les tests n'ont pu être exécutés. Les 82 fichiers modifiés ont été contrôlés par
   `php -l` (fichiers PHP) et par équilibre des `{{ }}` (vues). **Un `composer install`
   puis une passe de recette sur les écrans de montants sont nécessaires avant de
   considérer le chantier clos.**

## Bloc I — API & routes (OpenAPI / apps Flutter)
**Structure de `routes/api.php`** — 177 lignes, trois niveaux imbriqués :
```php
Route::prefix('v10')->group(function() {                          // l.43
    Route::middleware(['CheckApiKey'])->group(function () {       // l.45
        // ~10 routes publiques (auth, hub, settings)
        Route::group(['middleware'=> ['auth:sanctum']], function () {  // l.60
            // ~60 routes marchand + ~10 routes livreur
        });                                                            // l.166
        Route::post('deliveryman/parcel-location-update', …);          // l.167 ← HORS auth
    });
    Route::get('customer/installation', …);                            // l.170 ← hors CheckApiKey
    Route::get('parcel/tracking/{tracking_id}', …);                    // l.172
    Route::post('/contact-us', …); Route::post('/subscribe', …);
    Route::get('/delivery-charges', …);                                // l.176
});
```
Un `Route::get('/user')` sous `auth:sanctum` traîne hors du préfixe (l.39-41),
vestige du squelette Laravel.

- Mécanisme d'auth API (Sanctum/Passport/token) : **deux couches superposées.**
  1. **Clé API statique** — `app/Http/Middleware/CheckApiKeyMiddleware.php`
     (alias `CheckApiKey` dans `app/Http/Kernel.php`) : compare l'en-tête `apiKey`
     à `config('rxcourier.api_key')`.
     🚨 **La clé est EN DUR dans le dépôt, sans `env()`** :
     `config/rxcourier.php:90` → `'api_key' => '123456rx-ecourier123456'`.
     Valeur **unique, partagée par toutes les installations We Courier, identique
     pour tous les locataires, embarquée dans les APK des deux apps.**
     Elle n'authentifie rien — c'est un filtre, pas un secret.
  2. **Laravel Sanctum** (`laravel/sanctum ^3.2`), jetons personnels émis dans
     `app/Http/Controllers/Api/V10/AuthController.php` :
     `:102` `createToken($request->merchant_id)` (signin marchand) ·
     `:128` `createToken($request->driver_id)` (deliveryManLogin) ·
     `:141` refresh (`tokens()->delete()` puis recréation) · `:147` logout.
     ⚠️ **Aucune capacité (`abilities`) n'est passée à `createToken()`** → tous les
     jetons reçoivent `['*']`. Le nom du jeton est purement cosmétique.
  - Réponses normalisées par `App\Traits\ApiReturnFormatTrait` :
    `{success, message, data}`.
- Groupes d'endpoints marchand : **~60 routes, l.61-146** — dashboard (+ filter,
  balance-details, available-parcels), profil (+ update, password), notifications
  FCM (subscribe/unsubscribe), réglages (`cod-charges`, `delivery-charges`),
  **boutiques** (CRUD), **comptes de versement** (CRUD), transactions de compte,
  relevés, **demandes de paiement** (CRUD), **fraudes** (CRUD + `check`),
  actualités, **support** (CRUD + `view` + `reply`), **colis** (`index`, `create`,
  `store`, `details`, `edit`, `update`, `logs`, `filter`,
  `{id}/status/{statusId}`, `delete`, `all/status`, `status-wise/…`),
  **factures** (`invoice-list`, `invoice-details/{id}`), `analytics`,
  `statement-reports`.
- Groupes d'endpoints livreur : **~10 routes, l.148-165**, toutes préfixées
  `deliveryman/` — `parcel/index`, `parcel/details/{id}`,
  `parcel/delivered/{id}`, `parcel/partial-delivered/{id}`, `income-expense`,
  `dashboard`, `profile`, `payment-logs`, `parcel-payment-logs`,
  `parcel-status`, `parcel-status-update`.
  - **Public** (hors `auth:sanctum`) : `register`, `signin`, `deliveryman/login`,
    `otp-verification`, `resend-otp`, `password/email` (throttlé 5/min),
    `password/reset`, `hub`, `general-settings`, `all-currencies`, + routes vitrine
    de fin de fichier et `customer/installation`.
- Versionnage éventuel : préfixe **`/api/v10`** (l.43), contrôleurs dans
  `app/Http/Controllers/Api/V10/` (21 fichiers). **Une seule version existe** — pas
  de `v9`/`v11`, aucune négociation par en-tête, aucun `Accept-Version`.
  ⚠️ **Versionnage purement décoratif** : des ajouts ont été faits *dans* `v10`
  sans changer de version, signalés par un simple commentaire **`// update v1.2`**
  (l.138) au-dessus de 8 routes (`balance-details`, `invoice-list`, `analytics`,
  `statement-reports`…). Le `v10` de l'URL et le « v1.2 » du commentaire ne
  désignent pas la même chose.

### ⇒ Quatre points à signaler
1. 🚨 **Marchand et livreur partagent le même groupe de middleware.** Les routes
   `deliveryman/*` sont dans le même `auth:sanctum` que les routes marchand, **sans
   garde `user_type`**. Les jetons n'ayant aucune ability, **un jeton marchand peut
   appeler les endpoints livreur** et inversement. Les contrôleurs ne rattrapent
   pas : `DeliveryManParcelController::index:27` appelle `deliveryManParcel()` sans
   vérifier le type d'utilisateur ; `ParcelController::index:47-49` résout un
   marchand depuis `auth()->user()->id` — un livreur y déclenche une **500**, pas un refus.
2. 🚨 **`deliveryman/parcel-location-update` (l.167) est HORS `auth:sanctum`**
   (placée juste après la fermeture du groupe). Protégée uniquement par la clé API
   statique publique ⇒ **n'importe qui peut pousser une position de colis**.
3. **Aucune tenancy sur l'API** (confirmé bloc B) : 0 occurrence de `tenant` dans
   `routes/api.php`. Scoping entièrement porté par `Auth::user()->company_id` via
   `settings()`.
4. **Aucun throttle hors `password/email`.** Le groupe `api` d'`app/Http/Kernel.php`
   applique `throttle:api` (60/min par utilisateur ou IP) — seule limite existante.

### ⇒ Conséquences pour le chantier 7 (OpenAPI/Swagger) — ✅ répondues le 2026-09-04
- ~~Aucun package de doc installé, spec à écrire de zéro~~ → **générée depuis le
  routeur** (`App\Services\OpenApi\SpecGenerator`), sans package ni annotation :
  chaque route `api/v10/*` devient une opération, la sécurité est déduite des
  middlewares réels (`CheckApiKey` → `apiKey`, `auth:sanctum` → `bearer`,
  `throttle` → 429). Ce que le code ne dit pas (résumés, corps, schémas) vit dans
  `resources/openapi/overlay.php`, qui ne peut décrire qu'une route existante.
- ~~Réponses sans forme stable~~ → l'overlay décrit `data` endpoint par endpoint
  (une quarantaine de schémas, tous enveloppés dans `Envelope`).
- ~~Montants en chaînes~~ → le chantier 1 est passé avant : le schéma `Amount` est
  un entier XOF.
- Voir « ✅ Chantier 7 » plus bas.

## Bloc J — Tarifs de livraison (barème poids × zone)
- **Tables** :
  - `deliverycategories` (`…2014_10_11_000000_create_deliverycategories_table.php`) :
    `company_id`, `title`, `status`, `position` — une « catégorie » de colis, libellé libre.
  - `delivery_charges` (`…2022_04_09_101126_create_delivery_charges_table.php`) :
    `company_id`, `category_id` (FK `deliverycategories`), **`weight` `tinyInteger`**,
    puis **4 colonnes tarifaires** `decimal(16,2)` : `same_day`, `next_day`, `sub_city`,
    `outside_city`, + `position`, `status`.
  - `merchant_delivery_charges` (même horodatage) : `company_id`, `merchant_id`,
    `delivery_charge_id`, **`weight` `bigInteger`**, `category_id`
    (`unsignedTinyInteger`, **sans contrainte FK ici**), les mêmes 4 colonnes en nullable.
    ⇒ surcharge du barème société **par marchand**.
- ⚠️ **Il n'existe pas de zone géographique.** Les 4 colonnes mélangent **délai** et
  **périmètre** — `app/Enums/DeliveryType.php` : `SAMEDAY=1`, `NEXTDAY=2`, `SUBCITY=3`,
  `OUTSIDECITY=4`. Une zone est une **colonne**, pas une ligne : en ajouter une = `ALTER
  TABLE` sur deux tables + tous les sites de lecture.
- ⚠️ **Le poids n'est pas une tranche** (ni min ni max) : une ligne = une valeur discrète.
  `tinyInteger` côté société ⇒ **plafond 127**, et **type incohérent** avec la table
  marchand (`bigInteger`).
- **Résolution du tarif — deux méthodes AJAX jumelles, non partagées** :
  - `ParcelController::deliveryCharge:420` (`POST parcel/delivery-charge`, `routes/web.php:450`)
    et `MerchantPanel\MerchantParcelController::deliveryCharge:277`.
  - Même algorithme : chercher `MerchantDeliveryCharge` (`merchant_id` + `category_id` +
    `weight`), sinon retomber sur `DeliveryCharge`, puis un `if/elseif` sur
    `delivery_type_id` renvoie **un nombre brut** (`return $chargeAmount;`) — pas de JSON,
    `return 0` en cas d'échec, **indistinguable d'un tarif nul**.
  - 🚨 **Le repli ignore le poids** : `DeliveryCharge::where(['category_id' => …])->first()`
    → première ligne de la catégorie **quel que soit le poids demandé**. Un colis lourd
    peut être facturé au tarif le plus bas, silencieusement.
  - 🚨 **Le repli admin n'est pas scopé par société** : `ParcelController:432` et `:438`
    appellent `DeliveryCharge::where(…)` **sans `companywise()`**, alors que
    `MerchantParcelController:283,288` l'utilisent. Fuite inter-locataires (cf. S7).
  - `MerchantDeliveryCharge::where([…])` est sans `companywise()` des deux côtés
    (scopé de fait par `merchant_id`).
  - Poids proposés à la saisie : `deliveryWeight()` (`ParcelController:462`,
    `MerchantParcelController:311`) — celui-ci **est** scopé `companywise()`.
- 🚨 **Application au colis : côté client, comme la TVA (S2).** Le montant renvoyé par
  l'AJAX est recalculé en JavaScript puis reposté dans `chargeDetails` :
  `$parcel->delivery_charge = $chargeDetails->deliveryChargeAmount`
  (`ParcelRepository:414`, `:593`), avec `codChargeAmount`, `totalDeliveryChargeAmount`,
  `currentPayable` — **aucun n'est revérifié**.
  Seule exception : le **taux** COD est lu côté serveur,
  `$Codmerchant->cod_charges['inside_city' | 'sub_city' | 'outside_city']`
  (`ParcelRepository:419-425`), colonne `merchants.cod_charges` castée en `array`
  (`Merchant.php:23`) — ⚠️ **3 clés de zone, qui ne correspondent pas aux 4 colonnes
  de `delivery_charges`**. Le *montant* COD, lui, revient du client.
- **Exposition** :
  - Admin : `delivery-charge/*` (`routes/web.php:475-482`, permissions `delivery_charge_*`)
    et `merchant/{merchant}/delivery-charge/*` (`:322-328`).
  - Marchand (web) : `MerchantPanel\SettingsController::deliveryCharges` — lecture seule.
  - API authentifiée : `GET /api/v10/settings/delivery-charges` (`routes/api.php:74`,
    `DeliveryChargeResource`).
  - 🚨 API **hors `auth:sanctum`** : `GET /api/v10/delivery-charges` (`routes/api.php:174`
    → `Api/V10/ParcelController::DeliveryCharges:267` → `allGet()`). Sans utilisateur
    authentifié, `settings()` retombe sur **`company_id = 1`** (bloc A) : la route
    renvoie **les tarifs de la société plateforme quel que soit le sous-domaine appelé**.
  - Import CSV : `app/Imports/ParcelImport.php:180`.
- 🐞 `DeliveryChargeRepository::allGet()` (l.11-22) contient **10 lignes mortes** après
  son `return` (l.14).

### ⇒ Conséquences pour le barème béninois
1. **Modéliser les zones en lignes, pas en colonnes** (Cotonou / périphérie / intérieur /
   CEDEAO) : la structure à 4 colonnes fixes ne se prolonge pas.
2. **Passer le poids en tranches** (min/max) et sortir du `tinyInteger`.
3. **Un seul résolveur de tarif, côté serveur** : les deux méthodes AJAX dupliquées, le
   repli sans poids et le repli non scopé disparaissent ensemble si le calcul devient un
   service appelé à l'enregistrement — **même chantier que S2**.
4. **Aligner les zones COD** (3 clés) sur les zones de livraison (4 colonnes).
5. Les **8 colonnes tarifaires sont en `decimal(…,2)`** ⇒ chantier 1 (FCFA).

## Bloc K — Notifications (SMS, e-mail, push)
> **Aucune file d'attente** : `app/Jobs/` n'existe pas et `config/queue.php:16` donne
> `QUEUE_CONNECTION` par défaut à **`sync`**. Les trois canaux partent **dans la requête
> HTTP** de l'utilisateur.

### SMS — `app/Http/Services/SmsService.php` (123 lignes)
- **3 fournisseurs codés en dur** : **REVE** (opérateur bangladais, cURL brut),
  **Twilio** (`twilio/sdk ^8.2`), **Nexmo/Vonage** (`vonage/client ^4.0`).
  Pas d'interface, pas de registre — même schéma qu'au bloc C côté paiement.
- **État des trois API vérifié (contrairement au push, elles sont toutes vivantes)** :
  - **REVE** : service **toujours en activité**, aucun arrêt annoncé — mais c'est un
    fournisseur A2P **du Bangladesh uniquement** (agréé BTRC). Rien à migrer : c'est
    **hors géographie** pour BeninLink, à désactiver. `reve_api_url` étant un réglage en
    base, l'implémentation cURL générique pourrait à la rigueur viser un agrégateur
    acceptant les mêmes paramètres (`apikey`, `secretkey`, `callerID`, `toUser`,
    `messageContent`) — solution de dépannage, pas une cible.
  - **Nexmo = Vonage** : ce n'est pas une API morte mais un **renommage**. Le socle
    utilise le SDK Vonage actuel — `composer.json` demande `vonage/client ^4.0`,
    `composer.lock` fige **4.0.0**, la dernière version publiée est **4.3.0 (6 janvier
    2026)** et le paquet **n'est pas abandonné** (`vonage/client-core` 4.3.2,
    `vonage/nexmo-bridge` 0.1.2 pour la compatibilité de l'ancien namespace). Le code
    appelle directement `\Vonage\Client` : **rien à réécrire**, seulement à mettre à jour.
  - **Twilio** : API et SDK **actifs**. `composer.json` demande `twilio/sdk ^8.2`,
    `composer.lock` fige **8.2.3** alors que la dernière version est **8.11.6 (7 mai
    2026)** — paquet non abandonné, **0 avis de sécurité**. La contrainte `^8.2` autorise
    déjà la montée : c'est un simple `composer update`, pas une migration.
    ⚠️ Différence de conception avec Vonage : `twilioSms()` envoie depuis
    `smsSettings('twilio_from')`, un **numéro** (long code) et non un identifiant
    alphanumérique ⇒ vers le Bénin, un long code étranger est mal acheminé ou filtré ;
    la voie conforme reste le **sender ID enregistré** (mêmes règles que ci-dessous).
- 🚩 **Le vrai obstacle n'est pas technique, il est réglementaire (Bénin).** Vonage
  **et** Twilio desservent le Bénin (indicatif **229**, ISO **BJ**) et imposent les
  mêmes règles locales :
  - **Le sender ID alphanumérique doit être pré-enregistré** (démarche en console chez
    les deux fournisseurs, pièces justificatives à fournir ; 11 caractères max). Or
    `nexmoSms()` passe `settings()->name` en expéditeur (l.107) ⇒ **identifiant non
    enregistré, messages rejetés** tant que la démarche n'est pas faite.
  - **Fenêtre d'envoi imposée : 8 h – 17 h GMT+1.** Les **29 envois** du socle sont
    déclenchés par des événements de colis, **à toute heure** ⇒ il faut une file avec
    fenêtre d'expédition (renforce le point 2 des conséquences ci-dessous).
  - **Double opt-in et commandes STOP/HELP** exigés ; contenus politiques, religieux,
    promotionnels non sollicités et jeux d'argent interdits. Régulateur : **ARCEP Bénin**.
  - **SMS bidirectionnel et messages concaténés** (160 caractères GSM-7) supportés ;
    **pas de lignes fixes**.
  - Opérateurs : **MTN Bénin, Moov Bénin, Glo Bénin**.
- Configuration **en base, par société** : table `sms_settings` (`company_id`, `key`,
  `value`), lue par `smsSettings('clé')` (`Helper.php:827`) : `reve_status`,
  `reve_api_key`, `reve_secret_key`, `reve_api_url`, `twilio_status`, `twilio_sid`,
  `twilio_token`, `twilio_from`, `nexmo_status`, `nexmo_key`, `nexmo_secret_key`.
  Hors session authentifiée, le helper retombe sur `company_id = 1`.
- ⚠️ **Les fournisseurs actifs se cumulent** : trois `if` indépendants (l.32-40) ⇒ deux
  fournisseurs actifs = **deux SMS facturés** par envoi.
- ⚠️ **`sendOtp()` ne teste que REVE et Twilio** (l.15-22) : avec Nexmo seul actif,
  **aucun OTP ne part**, sans erreur.
- 🚨 `reveSms()` : `CURLOPT_SSL_VERIFYPEER = FALSE` (l.67) — **vérification TLS
  désactivée** sur un appel qui transporte la clé API en *query string*.
- ⚠️ Les trois méthodes **avalent leurs exceptions** (`return $exception`) et aucun
  appelant ne teste le retour ⇒ **un SMS non parti est invisible**. Aucune table de journal.
- Message OTP **en anglais codé en dur** (l.52) : `… ' is your ' . settings()->name .
  ' verification code.'` — hors i18n (bloc H).
- **Déclencheurs** : **29 appels** à `sendSms()` dans `app/`, surtout `ParcelRepository`
  (l.499, 848, 862, 941-955…) et `MerchantRepository` (OTP, l.229, 250).
  ⚠️ **Seuls 3 sont débrayables** : table `sms_send_settings` + `App\Enums\SmsSendStatus`
  (`PARCEL_CREATE=1`, `DELIVERED_CANCEL_CUSTOMER=2`, `DELIVERED_CANCEL_MERCHANT=3`),
  testés par `SmsSendSettingHelper()` (`ParcelRepository:492, 2135, 2144`).
- ⚠️ **Aucun formatage E.164** : le numéro part tel quel depuis la base — à reprendre
  pour `+229` (cf. `digits_between:11,14` du bloc F).

### Push — `app/Http/Services/PushNotificationService.php`
- 🚨 **Envoi : API FCM « legacy », arrêtée par Google — le push ne fonctionne plus.**
  `https://fcm.googleapis.com/fcm/send` avec `Authorization: key=<server key>`
  (l.30, 71, 197). Chronologie officielle : **dépréciée le 20 juin 2023**, support
  **arrêté le 20 juin 2024**, extinction effective **à partir du 22 juillet 2024**.
  ⇒ **réécriture** vers `POST https://fcm.googleapis.com/v1/projects/<id>/messages:send`
  avec jeton **OAuth2 issu d'un compte de service** (fichier de clé privée JSON), et
  **charge utile restructurée** (tout sous un objet `message`, options Android
  regroupées, `time_to_live` → `ttl`, `data` limité à un plat `string → string`).
- ⚠️ **Abonnement aux topics : l'URL reste valable, c'est l'authentification qui ne
  passe plus.** `iid.googleapis.com/iid/v1/<token>/rel/topics/<topic>` (l.108, 140)
  **continue d'exister**, mais **n'accepte plus les clés serveur statiques depuis le
  21 juin 2024** — il faut un en-tête `Authorization: Bearer <access_token>` OAuth2.
  ⇒ correctif plus léger que pour l'envoi : conserver l'appel, remplacer l'en-tête.
  (`fcmUnsubscribe` (l.161) vise en outre `iid.googleapis.com/v1/web/iid/<token>`,
  un endpoint *web push* sans rapport avec les topics — à revoir aussi.)
- ⇒ **Conséquence commune : `notification_settings.fcm_secret_key` (clé serveur) ne sert
  plus à rien.** Le stockage par société doit accueillir un **compte de service JSON**,
  pas une clé — impact sur la table et sur l'écran de réglages.
  Sources : [Migrate from legacy FCM APIs to HTTP v1](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
  · [Azure Notification Hubs — FCM migration](https://learn.microsoft.com/en-us/azure/notification-hubs/firebase-migration-rest)
  · [Instance ID — Server Reference](https://developers.google.com/instance-id/reference/server)
- Configuration par société : `notification_settings` (`fcm_secret_key`, `fcm_topic`),
  via `notificationSettings()` (`Helper.php:129`, `companywise()`).
- 🚨 **Le topic est dérivé de l'adresse e-mail** : `fcm_topic . '_' .
  str_replace(['@','.','+'], …, $topicName)` avec `$topicName =
  $parcel->merchant->user->email` (`ParcelRepository:487, 871, 961…`). `fcmSubscribe()`
  (l.94, exposée en API) abonne **n'importe quel device token au topic de n'importe qui**
  ⇒ connaître une adresse e-mail suffit pour recevoir les notifications de ce marchand.
- 🚨 `CURLOPT_SSL_VERIFYPEER = false` dans les trois méthodes d'envoi (l.43, 83, 239) ;
  `sendWebNotification` y ajoute `CURLOPT_SSL_VERIFYHOST = 0` (l.237).
- 🐞 `die('Curl failed: ' . curl_error($ch))` (l.48) : une panne réseau **interrompt la
  requête HTTP** en plein changement de statut de colis.
- Textes **en anglais codés en dur** (l.66 `"Your parcel #… status updated"`, l.207
  `"A new parcel has been placed"`).
- **14 appels** à `sendStatusPushNotification()`. ⚠️ La table `push_notifications` ne
  journalise rien : elle stocke les notifications **rédigées à la main** par l'admin
  (`title`, `description`, `image_id`, `user_id`/`merchant_id`).

### E-mail
- **4 Mailables** (`app/Mail/`) : `CompanySignup`, `MerchantSignup`, `ContactMail`,
  `InvoicePDFSend`. Transport = `config/mail.php` (donc `.env`) — **aucun réglage par
  société**, contrairement au SMS et au push.
- Expéditeur des inscriptions : `settings()->email` (`CompanySignup:21`,
  `MerchantSignup`) ⇒ **le domaine d'expédition n'est pas forcément celui du serveur
  d'envoi** : SPF/DKIM à cadrer au déploiement.
- 🚨 `ContactMail:34` : `->from($data['email'])` — **l'adresse saisie par le visiteur
  devient l'expéditeur** ⇒ usurpation et rejets SPF.
- ⚠️ `InvoicePDFSend` : expéditeur `admin@example.com` **codé en dur** (bloc G).
- Objets en anglais codés en dur (`'Welcome to new company'`, `'Welcome to new merchant'`).
- ⚠️ **Aucun Mailable n'implémente `ShouldQueue`** (l'import existe dans `CompanySignup`
  mais n'est pas utilisé) ⇒ envoi bloquant, y compris à l'inscription.

### ⇒ Conséquences pour les notifications BeninLink
1. **Choisir la voie SMS pour le Bénin.** Vonage et Twilio sont déjà branchés et
   fonctionnent : la question n'est pas « quelle API est morte » mais **enregistrer un
   sender ID** chez l'un des deux, puis comparer leur coût par SMS avec un agrégateur
   local MTN/Moov. REVE est à désactiver, et le SDK Twilio à remonter (`^8.2` le permet).
   Si un fournisseur local s'ajoute, l'absence d'interface impose **une 4ᵉ méthode privée
   + un `if`** — ou l'occasion d'introduire enfin un contrat, comme pour les paiements
   (bloc C).
2. **Mettre les envois en file avant d'en ajouter** : `QUEUE_CONNECTION=redis` est prévu
   côté infra. Sans cela, chaque notification allonge la requête, une panne fournisseur
   bloque un changement de statut de colis — et surtout **la fenêtre légale 8 h-17 h
   GMT+1 est intenable** sans expédition différée.
3. **Réécrire l'envoi push sur FCM HTTP v1** (compte de service + nouvelle charge utile),
   **rebrancher les abonnements aux topics sur un jeton OAuth2** (l'URL, elle, reste
   bonne), et **cesser d'utiliser l'e-mail comme topic** : rattacher le jeton d'appareil
   à l'utilisateur authentifié. À chiffrer comme un développement, pas comme un réglage.
4. **Traduire les gabarits** (SMS OTP, titres push, objets d'e-mail) — chantier 1.
5. **Journaliser les envois** : aucun canal ne trace aujourd'hui ses échecs.

---

## Synthèse — à recopier dans web/CLAUDE.md
- [x] #1 **Tenancy** : `stancl/tenancy ^3.7` en façade — `InitializeTenancyByDomain`
  monté dans `routes/web.php:144-148`, domaine **complet** résolu via la table
  `domains`. Mais `DatabaseTenancyBootstrapper` est **désactivé**
  (`config/tenancy.php:31`) ⇒ **une seule base**, isolation réelle par colonne
  **`company_id`** + **`scopeCompanywise()`** (47/51 modèles). `Tenant` ne fait que
  mapper *domaine → `company_id`* ; la vraie société est `GeneralSettings`, résolue
  par `settings()` (`Helper.php:105`). ⚠️ Aucun filet framework : un oubli de
  `companywise()` expose les données d'autres sociétés. **`/api/v10` n'initialise
  aucune tenancy.**
- [x] #2 **Interface de passerelle de paiement** : **IL N'Y EN A AUCUNE**, et **la
  classe Paystack n'existe pas** (`grep -ril paystack` hors `vendor/` → 3 fichiers,
  aucun n'est du code : `Enums/PayoutSetup.php:12` + libellés `lang/{en,es,zh}`).
  Un **contrôleur par passerelle**, sans ancêtre commun ; la « sélection » est un
  `switch` qui normalise des cases à cocher (`PayoutSetupRepository:14-42`) et
  n'instancie rien. Clés en base (table `settings`, `company_id`/`key`/`value`),
  lues par `globalSettings()`. ⇒ **FedaPay = écrire un flux complet**, pas
  implémenter un contrat. Aucun webhook signé n'existe comme modèle.
- [x] #3 **Service de crédit wallet** : **`WalletRepository::approved($id): bool`**
  (`app/Repositories/Wallet/WalletRepository.php:129`, crédit l.135) — lié via
  `WalletInterface` (`AppServiceProvider:111`). Solde = colonne
  `merchants.wallet_balance`, historique = table `wallets`. Second point de crédit :
  `adminstore($request)` (l.178). ⚠️ **`approved()` n'est ni transactionnelle ni
  idempotente** et ne vérifie pas le statut de départ ⇒ deux appels = crédit doublé.
  `wallets.transaction_id` **sans index unique**. Aucun paiement en ligne n'alimente
  le wallet aujourd'hui (`WalletPaymentMethod` = OFFLINE, WALLET).
- [x] #4 **Module d'abonnement** : **`CompanyRepository::switchPlan($request): bool`**
  (`app/Repositories/Superadmin/Company/CompanyRepository.php:348-381`) — unique
  point d'activation. Tables `plans` / `subscriptions` ; contrôle d'accès par
  `subscriptionCheck()` (`Helper.php:945`) + middleware `subscriptionCheck`
  (`routes/web.php:191`). Paiement = **Stripe Checkout codé en dur**
  (`PlanController::subscriptionPayment:105`, devise `'USD'`, clés lues sur
  `company_id = 1`). 🚨 **`StripePaymentSuccess:137` active l'abonnement sans rien
  vérifier auprès de Stripe** ⇒ `GET /subscription/success?plan_id=X&user_id=Y`
  suffit à activer n'importe quel plan, gratuitement, pour n'importe quel compte.
  `subscriptions` n'a **aucune colonne de transaction** ⇒ rien sur quoi bâtir
  l'idempotence.

---

## Constats de sécurité relevés pendant la cartographie
> Tous héritent du socle **We Courier** — aucun n'est propre au fork BeninLink.

| # | Bloc | Constat | Fichier |
|---|---|---|---|
| ~~S1~~ | E | ~~Abonnement activable sans paiement~~ — ✅ **corrigé le 2026-08-18** : la session Stripe est relue et 4 contrôles exigés (payée, même utilisateur, montant du plan, session présente) | `PlanController::StripePaymentSuccess` |
| ~~S2~~ | G | ~~**TVA et frais de livraison calculés côté client**~~ — ✅ **corrigé le 2026-08-18** : `App\Services\Parcel\ChargeCalculator` recalcule tout côté serveur, `chargeDetails` n'alimente plus aucun montant (voir §S2 ci-dessous) | `ParcelRepository` · `MerchantParcelRepository` |
| ~~S3~~ | I | ~~Clé API en dur~~ — ✅ **corrigé le 2026-08-18** : lue via `env('API_KEY')`, propre à chaque installation. ⚠️ Reste une porte d'entrée, pas une authentification : elle voyage dans chaque requête | `config/rxcourier.php` |
| ~~S4~~ | I | ~~`parcel-location-update` hors `auth:sanctum`~~ — ✅ **corrigé le 2026-08-18** : rentrée dans le groupe authentifié, vérifié 401 sans jeton | `routes/api.php` |
| ~~S5~~ | I | ~~Aucune séparation marchand/livreur~~ — ✅ **corrigé le 2026-09-04** : un jeton marchand appelait `deliveryman/*` (lecture et changement de statut des colis du hub, remontée de position) et un jeton livreur sur une route marchand finissait en 500. Le groupe `auth:sanctum` est découpé en trois : routes communes (session, profil, mot de passe, push), espace marchand sous `userType:merchant`, espace livreur sous `userType:deliveryman` ; hors périmètre, 403. Le jeton porte désormais l'ability de son type dès la connexion (`merchant` / `deliveryman`), conservée par `refresh` ; les jetons antérieurs (`['*']`) restent cloisonnés par `user_type` | `Middleware/UserTypeMiddleware` · `routes/api.php` · `Api\V10\AuthController` |
| ~~S6~~ | C | ~~`Setting::where('key')` non scopé~~ — ✅ **corrigé le 2026-08-18** : recherche scopée par `company_id`. Démontré : deux sociétés portant la même clé, la requête d'origine renvoyait celle de la société 1 | `PayoutSetupRepository` |
| ~~S7~~ | B | ~~Aucun filet contre les fuites inter-locataires~~ — ✅ **filet posé le 2026-09-04**, sans scope global (voir « ✅ S7 » plus bas) : chaque route `/api/v10` à identifiant doit être inscrite dans `IsolationCoverageTest` avec le test qui prouve son isolation, sinon la suite échoue. L'audit qui l'accompagne a fermé **cinq fuites** que la cartographie n'avait pas relevées : fiches de fraude, tickets de support, comptes de versement, demandes de retrait (un marchand lisait, modifiait et supprimait ceux d'un autre) et colis côté livreur (détail, livraison, statut, position de n'importe quel colis de la société) | `tests/Feature/IsolationCoverageTest` · `TenantIsolationTest` |
| ~~S8~~ | J | ~~Repli de tarif non scopé par société~~ — ✅ **corrigé le 2026-09-04** : `ChargeCalculator` l'était depuis S2, mais l'AJAX de l'administration et l'import CSV lisaient encore `DeliveryCharge::where(…)` sans `companywise()`. Les quatre sites passent par `DeliveryChargeResolver`, scopé société | `Services/Parcel/DeliveryChargeResolver` |
| ~~S9~~ | J | ~~Repli de tarif ignorant le poids~~ — ✅ **corrigé le 2026-09-04** : une ligne de barème vaut « jusqu'à N kg » ; poids exact, sinon la tranche immédiatement supérieure, sinon la plus lourde. Un colis n'est jamais facturé à une tranche plus légère que son poids (le socle servait la première ligne de la catégorie) | `Services/Parcel/DeliveryChargeResolver` |
| ~~S10~~ | J | ~~`delivery-charges` hors `auth:sanctum`~~ — ✅ **corrigé le 2026-08-18** : placée sous authentification, le locataire se résout par `Auth::user()->company_id`. Vérifié 401 sans jeton, 200 avec | `routes/api.php` |
| ~~S11~~ | K | ~~Topic FCM dérivé de l'e-mail~~ — ✅ **corrigé le 2026-08-18** (tests et cartographie complétés le 2026-09-04) : `fcmSubscribe()` ignore le topic demandé et abonne l'appareil au topic du compte **authentifié**, `topicFor()` = HMAC de l'identifiant utilisateur (non devinable, stable, sans e-mail) ; un inconnu retombe sur le topic global | `PushNotificationService::topicFor` |
| ~~S12~~ | K | ~~TLS non vérifié sur les appels sortants SMS et push~~ — ✅ **corrigé le 2026-08-18** : `CURLOPT_SSL_VERIFYPEER=true` (et `VERIFYHOST=2`) sur REVE SMS et les quatre appels FCM ; `NotificationHardeningTest` interdit toute régression. ⚠️ Le même défaut subsiste dans deux **passerelles de paiement héritées**, voir S21 | `SmsService` · `PushNotificationService` |
| ~~S13~~ | K | ~~Expéditeur d'e-mail contrôlé par le visiteur~~ — ✅ **corrigé le 2026-08-18** : le message part de l'adresse de la plateforme, celle du visiteur devient `replyTo` (usurpation et rejets SPF/DKIM évités). Complété le 2026-09-04 : `POST /api/v10/contact-us` valide enfin `name`, `email`, `subject`, `message` comme le formulaire web (il passait `$request->all()` tel quel) | `ContactMail` · `Api\V10\ParcelController::ContactUs` |
| ~~S14~~ | G | ~~`invoice-details/{id}` lisait la facture de n'importe quel marchand~~ — ✅ **corrigé le 2026-08-18** : `InvoiceRepository::getFind()` faisait `Invoice::find($id)` sans filtre ; il scope désormais par société **et** par marchand connecté, et le contrôleur répond 404 hors périmètre. Relevé en branchant l'écran `invoices` de `mobile/` | `InvoiceRepository:234` · `Api\V10\InvoiceController:28` |
| ~~S15~~ | A | ~~`Handler::render()` renvoyait **HTTP 200 + une page HTML** pour chaque HttpException~~ — ✅ **corrigé le 2026-08-18** : les sept branches `response()->view('errors.<code>')` (sans code de statut) sont supprimées ; Laravel choisit déjà la même vue **en conservant le statut** et répond en JSON aux clients d'API. Avant : un 404 d'API arrivait en 200 sur `mobile/`, indistinguable d'un succès | `app/Exceptions/Handler.php` |
| ~~S16~~ | G | ~~`getInvoiceStatusAttribute()` laissait `$status` indéfini~~ — ✅ **corrigé le 2026-08-18** : un statut hors des trois connus faisait lever une `ErrorException` sous PHP 8 et l'API répondait **500 sur une simple lecture de facture**. Initialisé à `''` | `Merchantpanel/Invoice.php:95` |
| ~~S17~~ | I | ~~Tout `MerchantParcelRepository` travaillait sur `Parcel::find($id)` nu~~ — ✅ **corrigé le 2026-08-18** : un marchand authentifié lisait le colis d'un autre (destinataire, téléphone, adresse, montants) via `parcel/details/{id}` et `parcel/logs/{id}`, puis pouvait le **modifier**, en changer le statut ou le supprimer ; `update()` relisait `merchant_id` dans le corps de la requête, donc réaffectait le colis à un autre marchand. Toutes les lectures et écritures passent par un `ownedParcels()` scopé société + marchand connecté, et l'API répond 404 hors périmètre. Seul `parcelTrack()` (suivi public par numéro) reste non scopé, c'est sa raison d'être | `MerchantParcelRepository` · `Api\V10\ParcelController` |
| ~~S18~~ | I | ~~`ShopsRepository` lisait, modifiait et supprimait `MerchantShops::where('id')` sans filtre~~ — ✅ **corrigé le 2026-09-04** : depuis l'API (`shops/edit`, `shops/update`, `shops/delete`) comme depuis le panneau marchand, changer l'identifiant suffisait à lire l'adresse et le téléphone de la boutique d'un concurrent, à la renommer ou à la supprimer. Toutes les lectures et écritures passent par un `ownedShops()` scopé sur le marchand connecté ; hors périmètre, 404. Relevé en branchant l'écran boutiques de `mobile/` | `MerchantPanel/Shops/ShopsRepository` · `Api\V10\ShopsController` |
| ~~S20~~ | G | ~~Le panneau marchand servait le CSV d'un autre marchand~~ — ✅ **corrigé le 2026-09-04** : `merchant.panel.invoice.csv` (et désormais `pdf`, `journal`) portent `merchant_id` dans l'URL sans le recouper avec le compte connecté. `MerchantInvoiceController::ownsOrAbort()` répond 404 hors périmètre | `MerchantInvoiceController` |
| ~~S19~~ | C | ~~Une demande de retrait acceptait n'importe quel `merchant_account`~~ — ✅ **corrigé le 2026-09-04** : le marchand pouvait désigner le compte d'un autre, puis `PaymentResource` lui en renvoyait le détail (titulaire, numéro, banque) dans sa propre liste. L'API vérifie désormais que le compte lui appartient (422 sinon). Le panneau web, qui propose une liste fermée, n'est pas modifié | `Api\V10\PaymentRequestController` |
| ~~S21~~ | C | ~~TLS non vérifié dans deux passerelles de paiement héritées~~ — ✅ **désactivées le 2026-09-05** (décision : désactiver plutôt que corriger, aucun usage au Bénin). `config/payments.php` liste Aamarpay et SSLCommerz comme désactivées ; leurs routes ne sont plus enregistrées, les réglages société et marchand refusent de les activer, les écrans ne les proposent plus, une migration passe les statuts existants à inactif (clés conservées). Le code reste (référence, licence) | `config/payments.php` · `gatewayEnabled()` · migration `2026_09_05_100000` |
| ~~S22~~ | B | ~~Le détail du journal d'activité, sans périmètre, sans permission et sans échappement~~ — ✅ **corrigé le 2026-09-18** : `ActiveLogController::view($id)` faisait `Activity::find($id)` **nu** alors qu'`index()`, juste au-dessus, scope par la société du causeur — un opérateur lisait l'avant/après champ par champ d'un autre transporteur en changeant l'identifiant dans l'URL. Sa route ne portait **aucune** permission quand `logs.index` porte `hasPermission:log_read`. Et la vue rendait ces valeurs — des saisies d'utilisateurs — avec `{!! !!}`, dans un fragment injecté en `.html()` dans une fenêtre modale : **XSS stocké**. Les trois sont fermés, et l'échappement passe par `logValue()` parce qu'un `{{ }}` posé naïvement aurait fait **500** sur les attributs `array` de `User` et `Role` (voir « ✅ S22 » plus bas) | `ActiveLogController` · `routes/web.php:216` · `backend/log/view.blade.php` · `logValue()` |
| ~~S23~~ | B | ~~`admin/support/view/{id}` servait le ticket **et tout son fil** de n'importe quelle société~~ — ✅ **corrigé le 2026-09-18** : le périmètre de `all()` est extrait en `ticketsVisibles()` (société de l'auteur, les deux types d'administrateur pour un super-administrateur) et les **trois** lectures l'utilisent ; le contrôleur répond 404 hors périmètre. La route ne porte toujours pas de permission — c'est une décision, voir « 📋 Inventaire » | `SupportRepository` · `Backend\SupportController` |
| ~~S24~~ | C | ~~`admin/expense/search-account/{id}` renvoyait n'importe quel compte financier~~ — ✅ **corrigé le 2026-09-18** : `AccountRepository::get()` est `companywise()` comme les quatre autres lectures du dépôt. Les cinq appelants sont tous en requête back-office et veulent tous un compte de la société courante ; trois passent un identifiant déjà lu sur un enregistrement scopé, où le filtre ne change rien | `AccountRepository` |
| ~~S25~~ | B | ~~La chronologie d'un colis d'une autre société était lisible~~ — ✅ **corrigé le 2026-09-18** : les **deux** écrans concernés (`logs`, `deliveredInfo`) s'arrêtent en 404 si le colis est hors périmètre, et passent ensuite `$parcel->id` — le colis **déjà vérifié** — au lieu de l'identifiant brut. `parcelEvents()` reste volontairement non scopé : le **suivi public par numéro** l'appelle, c'est sa raison d'être (cf. S17), et un test l'inscrit | `Backend\ParcelController` |
| ~~S26~~ | B | ~~La boutique par défaut de n'importe quel marchand se changeait sur un GET~~ — ✅ **corrigé le 2026-09-18** : les **quatre** méthodes du dépôt back-office des boutiques passent par `boutiquesDeLaSociete()` (`whereHas('merchant', companywise())` — la table ne porte pas de `company_id`) ; l'ordre est inversé pour ne rien toucher quand la cible est hors périmètre, là où le socle basculait les anciennes **avant** de la chercher puis plantait sur `null` ; la route devient **PUT** et la vue soumet un formulaire `@csrf`. La permission reste une décision | `MerchantShops\ShopsRepository` · `MerchantShopsController` · `routes/web.php` |
| ~~S27~~ | A | ~~L'éditeur du fichier `.env` répondait **200 sans authentification**~~ — 🔴 ✅ **corrigé le 2026-09-18** : `geo-sot/laravel-env-editor` monte onze routes sous `/env-editor` avec le seul middleware `['web']`, depuis un fournisseur **auto-découvert** qui ne teste aucun environnement. `GET /env-editor` et `GET /env-editor/files` répondaient **200 à un visiteur anonyme** : lecture et écriture des identifiants de base, d'`APP_KEY`, des clés FedaPay et de la messagerie, régénération de la clé d'application, téléchargement et restauration de sauvegardes. Le garde `BlockEnvEditorRoutes` répond **404** pour les onze routes ; la **bibliothèque** reste en place, l'installeur en a besoin (voir « ✅ S27 » plus bas). Second versant relevé au moment de commiter : les sauvegardes du paquet sont des **copies intégrales de `.env`** (`APP_KEY` en clair) et `storage/env-editor` n'était **pas ignoré par git** — corrigé aussi. Relevé en inventoriant les routes web à paramètre pour le filet d'isolation | `config/env-editor.php` · `App\Http\Middleware\BlockEnvEditorRoutes` · `web/.gitignore` |
| ~~S28~~ | I | ~~La carte des courses du livreur répondait **200 sans authentification**~~ — 🔴 ✅ **corrigé le 2026-09-18** : `GET /deliveryMan/parcel/map/{id}/{lat}/{long}/{status}` était déclarée dans `routes/web.php` **hors du groupe `auth`** — juste après la fermeture d'un groupe, à la même indentation. Sa pile s'arrêtait à la tenancy : ni `auth`, ni `hasPermission`. Et `User::find($id)` était **nu**. La vue écrit `@json($mapParcels)` dans le source de la page : **nom, téléphone et adresse du client final**, nom, téléphone et adresse du marchand, montant à encaisser, numéro de suivi. Prouvé par appel HTTP anonyme : **200**, téléphone et adresse dans le corps. La route est retirée (aucune vue, aucun script, aucun nom de route — la retirer ne retire l'accès à personne) et le contrôleur est scopé pour que la remonter ne rouvre pas la fuite. Relevé en inventoriant les routes web à paramètre pour le filet | `routes/web.php` · `MapParcelController` |
| ~~S29~~ | B | ~~Dans le back-office, **les écritures étaient moins scopées que les lectures**~~ — ✅ **première passe le 2026-09-18** : motif dominant de l'arriéré du filet, et il s'était glissé dans mes propres correctifs. Trois formes fermées. **La vitrine** : les six dépôts `FrontWeb` lisent en `companyWise()->findOrFail()` mais supprimaient en `Modele::destroy($id)` **nu** — la question, l'article, le service, le partenaire, le lien social ou le bloc « pourquoi nous » d'un autre transporteur se supprimait en changeant l'identifiant, et le site public de la victime perdait son contenu. **⚠️ Le trou de S23** : ce lot avait scopé les trois *lectures* du support et laissé `update()` et `delete()` nues — on réécrivait le ticket d'un autre transporteur et on s'en attribuait la paternité par le `user_id`. **⚠️ Le trou de S26** : idem pour les boutiques, et `update()` lisant `merchant_id` dans la requête permettait en plus de **rattacher** la boutique à un autre marchand. Plus cinq écrans du panneau marchand qui répondaient **500** au lieu de 404 hors périmètre | `FrontWeb/*Repository` × 6 · `SupportRepository` · `MerchantShops\ShopsRepository` · `MerchantShopsController` · `MerchantPanel\MerchantParcelController` |
| ~~S30~~ | C | ~~L'argent du back-office : sept dépôts touchant à des comptes bancaires lisaient **nu**~~ — ✅ **corrigé le 2026-09-18** (5ᵉ passe sur l'arriéré). Pour six d'entre eux la lecture nue précédait un **mouvement d'argent** : `Income::update` touche le compte bancaire **et** le relevé du marchand rattachés à la recette lue ; `Expense::update` **rend le solde** au compte de la dépense lue ; `FundTransfer::update` rejoue un **virement** entre ses deux comptes ; `MerchantManage\Payment::update` réécrit la demande de versement et la **réaffecte** à un autre marchand ; `cancelReject` remet le versement rejeté **en attente de paiement** ; `Account::update` réécrit le compte bancaire ; `HubPaymentRequest::update` **rattache** la demande à l'entrepôt de l'agent connecté. Plus le **décaissement** d'un versement marchand, lu nu dans le contrôleur avec l'identifiant dans le corps — 3ᵉ occurrence de l'angle mort du filet | `Income` · `Expense` · `FundTransfer` · `MerchantManage\Payment` · `Account` · `HubPaymentRequest` · `ReceivedRepository` · `MerchantmanagePaymentController` |
| ~~S31~~ | B | ~~Les responsables d'entrepôt : périmètre par `hub_id` **et par rien d'autre**~~ — ✅ **corrigé le 2026-09-18** (6ᵉ passe). `hub_incharges` ne porte pas de `company_id`, donc `where('hub_id', $hubID)` acceptait l'entrepôt de n'importe quelle société. **Neuf points dans un seul dépôt**, et le plus grave n'est pas une lecture : la rafle d'`assignedHub()` passe tous les autres responsables actifs de l'entrepôt à inactif — chez l'autre société, une **interruption de service**. Elle réécrivait aussi le `hub_id` d'un utilisateur sans vérifier qu'il est à nous, `delete()` était `destroy($id)` **sans même le `hub_id`**, et `users()` nommait les **administrateurs de tous les transporteurs** dans le menu déroulant | `HubInChargeRepository` · `HubInChargeController` |
| ~~S32~~ | G | ~~Les relevés de règlement : neuf routes non prouvées~~ — ✅ **inscrites le 2026-09-20** (7ᵉ passe). **Passe de vérification, pas de correction** : les six méthodes du dépôt que ces routes atteignent étaient **déjà** `companywise()` (S14, S20, chantier 4). L'arriéré les tenait faute de test, pas faute de périmètre. Un seul défaut trouvé : `InvoiceDetails()` déréférençait un `null` hors périmètre — 500 au lieu de 404. Et un piège signalé : `InvoiceRepository::InvoicePdf()` est un **doublon mort** d'`invoiceGet()`, corps pour corps — il a avalé un de mes sabotages | `MerchantInvoiceController` · `InvoiceRepository` |

## ✅ S2 — le calcul des montants est revenu côté serveur (2026-08-18)

**`App\Services\Parcel\ChargeCalculator`** est désormais la seule source des montants.
Les 6 blocs des deux repositories (`store`, `signUpStore`-équivalent, `update` × 2 fichiers)
et les 6 lignes de journal (`parcel_logs`) ont été convertis : **plus aucun montant ne
provient de `chargeDetails`** (`grep chargeDetails->` → 0 occurrence).

Règles reproduites **à l'identique** depuis `public/backend/js/parcel/create.js`, seule
spécification existante :
```
frais COD      = encaissement × taux COD du marchand (selon la zone)
sous-total     = frais livraison + frais COD + emballage + fragile
TVA            = sous-total × taux de TVA du marchand
net à reverser = encaissement − (sous-total + TVA)
```
⚠️ `total_delivery_amount` porte le **sous-total hors TVA** — comportement d'origine
conservé, le changer modifierait les factures déjà émises.

**Corrige au passage S8 et S9** : la recherche du barème filtre maintenant sur le poids
(le socle servait la première ligne de la catégorie, un colis lourd passant au tarif le
plus bas) et reste scopée par société côté administration.

**Vérifié par l'attaque** : un `POST parcel/store` portant un `chargeDetails` mensonger
(frais 0, TVA 0, net = encaissement entier sur 50 000) produit en base
`delivery_charge=60`, `cod_amount=500` (1 %), `total=560`, `net=49 440` — les valeurs
falsifiées sont ignorées. Avec `merchants.vat = 18` : `vat_amount=100,80` et
`net=49 339,20`, exact au centime.

✅ **Endpoint de devis livré le 2026-08-18** : `POST /api/v10/parcel/quote`
(`Api\V10\ParcelController::quote`) rend les montants du même `ChargeCalculator`
**sans rien écrire**, marchand résolu par le jeton. Vérifié : à entrées égales, le devis
et ce qu'enregistre `store()` coïncident au centime. Il porte une clé de plus,
`total_payable_charges` = sous-total + TVA, pour qu'aucun client n'ait à décider si
`total_delivery_amount` porte la TVA (il ne la porte pas).

✅ ~~Reste : les vues Blade affichaient un total calculé en JavaScript~~ — les écrans
de création, duplication et modification (admin et panneau marchand) appellent
`POST parcel/quote` (`ParcelQuoteController`) et n'alimentent plus `chargeDetails`
(`public/backend/js/parcel/create.js`). Constaté périmé le 2026-09-05.

## ✅ Chantier 3, complément — recharge Mobile Money depuis le panneau web (2026-09-05)

L'app marchand rechargeait déjà par FedaPay ; le panneau web ne proposait que la
demande manuelle validée par l'administrateur. Même flux, même webhook :

| Élément | Où |
|---|---|
| Action | `FedaPayController::rechargeWeb` (`POST my-wallet/recharge/fedapay`, panneau marchand) ; `initiate()` (API) et `rechargeWeb()` partagent `startWalletRecharge()` — une seule écriture de la ligne `wallets` PENDING et de `fedapay_transactions` |
| Retour | `callback()` : `channel=web` dans l'URL de retour ramène au portefeuille avec un message (crédité si le webhook est déjà passé, sinon « en attente ») ; sans `channel`, la page d'attente de la WebView, qui affiche désormais la référence |
| Écran | `mywallet/recharge.blade.php` : bouton « Recharger par Mobile Money » (`formaction`, même montant, mêmes raccourcis) si la société a ses clés FedaPay ; le flux manuel reste |
| Route morte | `my-wallet/recharge-status` retirée |
| Tests | `FedaPayWalletWebTest` (5) : initiation → PENDING + redirection, refus → REJECTED/DECLINED, sans clés → rien, compte non marchand → rien, retour web → portefeuille sans crédit / retour app → page d'attente |

## ✅ Chantier 5 — alertes douanières (2026-08-19)

Le bloc « rien n'existe » de la revue fonctionnelle §3.4 est comblé : ni table, ni
endpoint, ni règle → **deux tables, quatre endpoints, deux écrans**.

| Élément | Où |
|---|---|
| Référentiel pays × catégorie | `customs_rules` (scopé société) · `CustomsRuleSeeder` |
| Alertes émises | `customs_alerts` (règle **recopiée**, pour survivre à sa modification) |
| Colis d'export | `parcels.destination_country` + `parcels.customs_category`, nullables |
| Règle métier | `App\Services\Customs\CustomsService` — seule source, comme `ChargeCalculator` |
| Refus BLOQUANT | `App\Rules\CustomsAllowed` dans `StoreRequest` — **le seul point commun aux 3 chemins de création** |
| Écriture de l'alerte | `ParcelCustomsObserver` sur `Parcel::saved` — 6 chemins écrivent un colis |
| API | `customs/reference` · `customs/alerts` · `customs/alerts/{id}/resolve` · bloc `customs` dans `parcel/quote` |
| Back-office | `admin/customs/alerts` · `admin/customs/rules` |

**Un colis est un export** dès que `destination_country` est renseigné et différent
de `BJ`. Sinon rien ne change — c'est ce qui rend les colonnes rétrocompatibles avec
les colis existants.

⚠️ **Le référentiel livré est une base de travail, pas un avis douanier.** 8 pays ×
6 catégories, dont les 3 exemples de la maquette. À faire relire par un transitaire :
une règle BLOQUANTE erronée empêche un marchand de créer son colis.

⏳ **Reste au chantier** : la notification à la création, demandée par
`.claude/rules/customs.md`. Le push est hors service (API FCM legacy arrêtée, bloc K),
l'e-mail demande un Mailable et un gabarit. L'alerte est enregistrée et visible dans
les deux écrans, mais rien n'est envoyé.

⏳ L'écran `customs` de `mobile/` suit — le chantier a été mené « backend d'abord ».

## ✅ Fil de notifications marchand (2026-09-04)

L'écran `notifications` de `mobile/` n'avait aucune source : `news-offer/index`
sert des offres, le push FCM legacy est arrêté (bloc K) et `push_notifications`
stocke ce que l'admin rédige, pas ce qui arrive au marchand.

| Élément | Où |
|---|---|
| Stockage | table `notifications` **standard Laravel** (canal `database`), `User` étant déjà `Notifiable` — migration `2026_09_04_110000` |
| Une seule classe | `App\Notifications\MerchantNotification` (`kind`, `title`, `body`, extras) ; un canal e-mail ou push s'ajoutera dans `via()` sans toucher aux émetteurs |
| Émetteur unique | `App\Services\Notifications\MerchantFeed` : textes FR, destinataires, tolérance aux pannes (une notification en échec ne fait jamais échouer l'écriture métier) |
| Accroche | six observers `app/Observers/Feed/*` : statut colis (`updated` + `wasChanged('status')`), wallet approuvé, relevé créé, alerte douane créée (la « notification à la création » de `.claude/rules/customs.md`), message admin créé, retrait approuvé/traité/rejeté |
| API | `notifications/index` (20 par page + `unread_count`), `unread-count`, `{id}/read`, `read-all` — périmètre inhérent à la relation `notifications()` de l'utilisateur |
| Tests | `MerchantNotificationTest` : chaque événement rejoué par son écriture Eloquent, liste scopée, lecture, tout marquer lu |

⚠️ Les observers ne voient que les écritures **Eloquent** (`save()`/`create()`).
Une mise à jour par query builder (`DB::table()->update()`) resterait muette ;
aucune n'a été trouvée sur les statuts de colis, les wallets, les factures ni
les retraits.

## ✅ Chantier 6 — reporting SaaS (2026-09-04)

Lecture seule des tables existantes (`subscriptions`, `plans`, `general_settings`,
`expenses` + `account_heads`, `fedapay_transactions`) ; aucun schéma modifié.

| Élément | Où |
|---|---|
| Formules | `App\Services\Reporting\SaasMetrics` : `snapshot(date)` (abonnement courant par société, MRR = prix × 30 / `days_count`, ARR = 12 × MRR), `month(mois)` (nouveaux = premier abonnement dans le mois, churn = actifs au 1er et plus au dernier jour, ARPA, LTV = ARPA / churn, CAC = dépenses d'acquisition / nouveaux, encaissé FedaPay, souscriptions), `trend(n)` |
| Conventions | `config/saas_reporting.php` : société plateforme (1), 30 jours par mois, chapitres comptables du CAC (marketing, acquisition, publicité, communication, commercial) |
| Page | `super-admin/reporting` → `Superadmin\ReportingController` (super-admin seul, `isSuperadmin()`), vue `backend/super-admin/reporting/index.blade.php` : six indicateurs, douze mois, détail par société, définitions affichées |
| Menu | entrée « Reporting SaaS » dans la barre latérale, super-admin seul |
| Tests | `SaasMetricsTest` : MRR/ARR/churn/LTV/CAC sur un jeu construit, ratios non disponibles sans churn ni dépense, plan gratuit, renouvellement ≠ churn, page réservée au super-admin |

⚠️ Limites assumées : le CAC dépend de dépenses **saisies** par la plateforme sous un
chapitre d'acquisition — sans saisie, il est « non disponible », jamais zéro ; le churn
est mesuré sur l'expiration sans renouvellement (le socle n'a pas de résiliation
explicite) ; les plans du seed ont des prix et durées aléatoires, sans valeur.

## ✅ Chantier 7 — spécification OpenAPI de `/api/v10` (2026-09-04)

Aucune dépendance ajoutée, aucun schéma modifié : la spec est **dérivée du routeur**,
elle ne peut donc pas s'écarter de `routes/api.php`.

| Élément | Où |
|---|---|
| Générateur | `App\Services\OpenApi\SpecGenerator` : parcourt `Route::getRoutes()` sous `api/v10`, une opération par méthode, sécurité déduite de `gatherMiddleware()`, paramètres de chemin déduits, réponses par défaut (200 `Envelope`, 400 clé, 401 jeton, 404 si paramètre, 422 sur POST/PUT, 429 si throttle), extensions `x-middleware` et `x-documented` |
| Overlay | `resources/openapi/overlay.php` : schémas (`Envelope`, `Amount` entier XOF, `Parcel`, `Invoice`, `WalletEntry`, `CustomsAlert`, `Notification`…) et une entrée « `METHOD chemin` » par route (résumé, corps, réponse). `orphans()` refuse toute entrée sans route ; `undocumented()` liste les routes sans entrée |
| Config | `config/openapi.php` : titre, version, préfixe, fichier de sortie, serveurs |
| Commande | `php artisan openapi:generate` écrit `public/openapi/v10.json` (versionné) ; `--check` ne fait que signaler les écarts |
| Routes | `GET /api/v10/openapi.json` (public, hors `CheckApiKey`, régénéré à la demande) et `GET /api/docs` (Swagger UI, CDN jsdelivr) |
| Tests | `OpenApiSpecTest` : chaque route est dans la spec, aucun orphelin ni route non documentée, sécurité conforme aux middlewares, **chaque endpoint de `mobile/src/api/endpoints.ts` existe dans la spec**, routes servies, `--check` passe |

⚠️ La spec **décrit** l'API, elle ne la corrige pas. Le point 1 du bloc I (livreur et
marchand dans le même groupe `auth:sanctum`) y apparaissait tel quel via
`x-middleware` — ✅ corrigé le 2026-09-04 (S5, ci-dessous) : la spec porte désormais
`x-user-type` et un 403 sur chaque route cloisonnée. Le point 2 (`parcel-location-update`)
était S4, corrigé le 2026-08-18.

## ✅ S5 — cloisonnement marchand / livreur de l'API (2026-09-04)

| Élément | Où |
|---|---|
| Middleware | `App\Http\Middleware\UserTypeMiddleware` (alias `userType:merchant`, `userType:deliveryman`) : vérifie `user_type` du compte **puis** l'ability du jeton ; 403 dans l'enveloppe habituelle (`auth.forbidden_user_type`) |
| Routes | `routes/api.php` : sous `auth:sanctum`, un bloc commun (`refresh`, `profile`, `profile/update`, `update-password`, `sign-out`, `fcm-*` — ceux que l'app livreur Flutter d'origine appelait aussi), puis `userType:merchant` (tableau de bord, boutiques, colis, argent, douane, FedaPay, wallet, notifications, support, fraude) et `userType:deliveryman` (`deliveryman/*`, dont `parcel-location-update`) |
| Jetons | `AuthController` : `createToken(nom, UserTypeMiddleware::abilitiesFor($user))` à `signin`, `otp-verification`, `deliveryman/login` et `refresh` |
| Spec | `SpecGenerator` ajoute `x-user-type` et la réponse 403 aux routes concernées ; `public/openapi/v10.json` régénéré |
| Tests | `ApiUserTypeTest` (7) : marchand → `deliveryman/*` 403 ; livreur → routes marchand 403 (avant : 500) ; chacun atteint ses routes et les communes ; jeton `['*']` cloisonné par `user_type` ; jeton d'un autre type refusé ; connexion et `refresh` émettent un jeton portant l'ability. Les tests existants passent `['merchant']` à `Sanctum::actingAs`, comme un jeton réel |

Non traité, volontairement : `profile` (GET) reste commun et renvoie `UserResource`
(orienté marchand) à un livreur, comme avant ; `deliveryman/profile` est la route
prévue pour lui.

## ✅ S8 / S9 — un seul résolveur de tarif, par tranche de poids (2026-09-04)

Le socle répétait la même recherche à quatre endroits — `ChargeCalculator` (corrigé
en S2), `ParcelController::deliveryCharge` (AJAX admin), `MerchantParcelController::deliveryCharge`
(AJAX panneau marchand) et `ParcelImport::deliveryCharge` (import CSV) — les trois
derniers avec le repli non scopé (S8) et sans poids (S9). C'est la conséquence 3 du
bloc J (« un seul résolveur, côté serveur »), sans toucher au schéma (conséquences 1 et 2,
zones en lignes et tranches min/max, restent des décisions métier).

| Élément | Où |
|---|---|
| Résolveur | `App\Services\Parcel\DeliveryChargeResolver::resolve(marchand, catégorie, poids, type)` : barème négocié du marchand (poids exact, sinon tranche supérieure), sinon barème de la société scopé `companywise()` (poids exact, sinon tranche supérieure, sinon la plus lourde) ; colonne choisie par `delivery_type_id` |
| Sites | `ChargeCalculator` délègue ; les deux AJAX gardent leur contrat (nombre brut, `0` hors AJAX) ; celui du panneau marchand prend le marchand **du compte connecté**, plus celui du formulaire (il consultait le barème négocié d'un autre) ; l'import CSV délègue |
| Tests | `DeliveryChargeResolverTest` (9) : poids exact et colonne par type ; 2 kg → tranche 3 kg, jamais 1 kg ; 10 kg → tranche la plus lourde, pas la ligne 10 kg d'un autre locataire ; barème négocié prioritaire puis repli société ; sans barème → 0 ; `parcel/quote` cohérent ; AJAX admin scopé, AJAX marchand ignore un `merchant_id` étranger, refus hors AJAX |

⚠️ Effet visible : un colis dont le poids dépassait le barème était facturé au tarif
le plus léger ; il l'est désormais à la tranche la plus lourde. C'est le comportement
voulu (sous-facturation silencieuse corrigée), à annoncer aux marchands pilotes.

## ✅ S7 — un filet contre les fuites inter-locataires, sans scope global (2026-09-04)

**Décision** : pas de *global scope* Eloquent sur les 47 modèles porteurs de `company_id`.
Il aurait fallu le poser modèle par modèle, il casserait les vues super-admin qui lisent
toutes les sociétés, et il ne protégerait pas du cas le plus fréquent — deux marchands
de la **même** société (S14, S17, S18, S19 et les cinq fuites ci-dessous l'étaient tous).
Le filet est donc posé **au niveau des tests**, là où l'oubli se voit.

| Élément | Où |
|---|---|
| Filet | `IsolationCoverageTest` : énumère les routes `/api/v10` portant un paramètre et exige pour chacune une entrée « test d'isolation » ou un motif d'exemption (`{status}`, suivi public). Ajouter une route à identifiant sans la déclarer fait échouer la suite ; déclarer un test inexistant ou une route disparue aussi |
| Audit | `TenantIsolationTest` (6) : compte A contre les ressources du compte B de la même société. Fraude (edit/update/delete → 404, liste noire de la société partagée), support (edit/view/delete/reply → 404, ticket intact), compte de versement (edit/update/delete → 404), demande de retrait (edit/update/delete → 404, la sienne supprimable), livreur (détail / livré / livraison partielle / changement de statut d'un colis confié à un collègue → 404, liste limitée aux siens, position mise à jour pour soi seul) |
| Repositories | `MerchantPanel/{Fraud,Support,PaymentAccount,PaymentRequest}Repository` : toute lecture et écriture passe par un `owned…()` (auteur, marchand connecté) sur le modèle de S17 ; `FraudRepository::store` renseigne enfin `company_id`, et la liste noire est scopée société |
| Contrôleurs | API : 404 dans l'enveloppe hors périmètre (Fraud, Support, PaymentAccount, PaymentRequest, DeliveryManParcel, `parcel-status-update`) ; `parcel-location-update` ignore `deliveryID` et prend le livreur authentifié ; panneau marchand web : `abort_if(…, 404)` aux mêmes endroits |
| Livreur | `ParcelRepository::deliveryManOwns($id)` (colis dont un événement porte le livreur en livraison ou ramassage), partagé par `deliveryManParcel()` |

⚠️ Effet de bord assumé : les fiches de fraude créées par des marchands **avant** ce
correctif n'ont pas de `company_id` (le socle ne le renseignait pas) ; elles n'apparaissent
plus dans la liste noire tant qu'on ne les rattache pas (`UPDATE frauds SET company_id …`
à partir de `created_by`). Aucune fiche de ce type dans le seed.

## ✅ S21 — Aamarpay et SSLCommerz désactivées (2026-09-05)

Un seul interrupteur, `config/payments.php` → `disabled_gateways`, lu par le helper
`gatewayEnabled($gateway)`. Le code des deux passerelles n'est pas supprimé.

| Élément | Où |
|---|---|
| Routes | `routes/web.php` : les blocs admin (`payout/sslcommerz`, `payout/aamarpay*`), panneau marchand (`online-payment/sslcommerz`, `online-payment/aamarpay`) et rappels publics (`pay-via-ajax`, `success`, `fail`, `cancel`, `ipn`, `aamarpay-*`) ne sont enregistrés que si la passerelle est active → 404 |
| Réglages | `PayoutSetupRepository::update` et `PaymentSetupRepository::update` renvoient `false` pour une passerelle désactivée : ni clés enregistrées, ni statut actif |
| Écrans | tuiles masquées dans `payout/index` et `merchant_panel/onlinepayment/index`, cartes de configuration masquées dans `setting/payout_setup/index` et `merchant_panel/settings/online_payment_setup/index` |
| Données | migration `2026_09_05_100000_disable_legacy_gateways` : `aamarpay_status` et `sslcommerz_status` à 0 dans `settings` et `merchant_settings` ; seeders alignés |
| Tests | `LegacyGatewaysDisabledTest` (5) : interrupteur, refus côté société et côté marchand, migration (statuts à 0, clés conservées, Stripe intact), compilation des quatre vues |

Réactiver une passerelle = la retirer de `disabled_gateways` **après** avoir passé
`CURLOPT_SSL_VERIFYPEER` à `true` dans son code — pas de rollback de la migration.

## ✅ Décisions métier tranchées en code (2026-09-05)

Registre complet : `docs/DECISIONS_METIER.md` (D1 à D5).

| Décision | Où |
|---|---|
| D1 — TVA au niveau société, surcharge par marchand | `App\Services\Parcel\VatRate::for()` (`ChargeCalculator`, import CSV, recherche marchand admin) ; `configs.vat_rate` = 18 (seed, création de société, migration `2026_09_05_110000`) ; réglage sur la page « Liquide/Fragile & TVA » (`LiquidFragileController::update`) |
| D3 — chapitre « Marketing et acquisition clients » | `AccountHeadSeeder`, migration `2026_09_05_130000` ; reconnu par `config/saas_reporting.php` |
| D5 — fiches de fraude orphelines rattachées à la société de leur auteur | migration `2026_09_05_120000` |
| D2, D4 — SYSCOHADA à valider, barème à refondre | propositions et questions dans le registre, aucun code |
| Tests | `BusinessDecisionsTest` (9) : taux société / taux marchand / aucun taux, devis, migration TVA idempotente, page compilée, chapitre unique et reconnu, rattachement des fiches |

## ✅ Harnais de tests (2026-08-18)

`RefreshDatabase` fonctionne : les 86 migrations passent sur SQLite en mémoire. Deux
contraintes découvertes :

- **`DatabaseSeeder` en entier échoue** — `CurrencySeeder` envoie du SQL MySQL brut que
  SQLite refuse. Le trait `tests/Concerns/SeedsTenant` appelle donc son **préfixe utile**
  (société, plans, uploads, hub, rôles, utilisateurs, catégories, barème, marchand,
  boutique, config, emballages), dans le même ordre — les clés étrangères en dépendent.
- **PHP 8.4 déprécie du code vendor de Laravel 10** ; Laravel convertit ces avis en
  `ErrorException` et le test qui les déclenche s'arrête. `phpunit.xml` les tait
  (`error_reporting`), ce qui ne masque rien de notre code.

Couverts à ce jour : signature du webhook FedaPay, **S14** (facture d'un autre marchand →
404) et **`parcel/quote`** — dont le test central : le devis annonce exactement ce que
`parcel/store` enregistre, même quand la requête porte un `chargeDetails` mensonger
(c'est S2 vérifié de bout en bout).

## Routes mortes repérées
| Route | Méthode cible | Statut |
|---|---|---|
| ~~`POST /my-wallet/recharge-status`~~ | `WalletController::rechargeStatus` | ✅ **route retirée le 2026-09-05** (aucune méthode, aucun appelant) |
| ~~`GET .../pdf/{invoice_id}`~~ | `MerchantInvoiceController::InvoicePdf` | ✅ **implémentée le 2026-09-04** (chantier 4) |
| ~~`GET .../pdf/{merchant_id}/{invoice_id}`~~ | `MerchantInvoiceController::InvoicePdf` | ✅ **implémentée le 2026-09-04** (chantier 4) |

⇒ Un passage `php artisan route:list` complet est recommandé avant d'ouvrir les chantiers.

## Ordre des chantiers — dépendances issues de la cartographie
1. ~~**Chantier 1 (FCFA)** avant **chantier 7 (OpenAPI)**~~ — respecté : la spec
   (2026-09-04) documente `Amount` en entier XOF.
2. **Chantier 2 (IFU/RCCM/CNSS)** avant **chantier 4 (SYSCOHADA)** : les mentions
   légales de la facture en dépendent.
3. **S2 (calcul serveur)** avant ou pendant le **chantier 4** : sans cela, aucune
   facture produite n'est opposable.
4. **Chantier 1** : créer `formatAmount()` **d'abord**, sinon 142 sites à éditer
   indépendamment.
5. **S2 et le bloc J vont ensemble** : rapatrier le calcul côté serveur et refaire le
   barème (zones en lignes, poids en tranches) sont **le même chantier** — ~~le repli de
   tarif actuel ignore le poids et n'est pas scopé par société~~ ✅ résolveur unique le
   2026-09-04 (S8, S9) ; la refonte du schéma (zones, tranches min/max) reste à décider.
6. ~~**Le push (bloc K) est hors service, pas mal configuré**~~ ✅ **traité le
   2026-09-05** (décision **D11**, revue §21). L'API d'envoi du socle a bien été
   arrêtée par Google le 20 juin 2024 ; le transport passe désormais par le service
   de push d'Expo (`config('push.driver')`), sans compte de service par société.
   Le push **navigateur** du back-office a été **retiré** le même jour (décision
   **D12**, revue §22) : il inscrivait les agents au projet Firebase de l'éditeur
   et réclamait une permission pour un canal qui ne livrait rien.
7. ~~**Mettre les envois en file avant d'ajouter une passerelle SMS locale**~~ ✅
   **fait le 2026-09-05** (décision **D13**, revue §23). SMS, push et courriels
   passent par la file `database` ; `php artisan beninlink:file-attente` signale un
   worker arrêté, la panne silencieuse que la file introduit.

---

## ✅ S22 — le détail du journal d'activité (2026-09-18)

Relevé en enveloppant le tableau de sa vue dans un `table-responsive` — une ligne
d'ergonomie qui a fait lire les huit lignes du contrôleur juste à côté.

```php
public function index()
{
    $logs = Activity::whereHas('causer', function ($query) {
        $query->where('company_id', settings()->id);   // ← scopé
    })->orderBy('id','desc')->paginate(10);
    …
}

public function view($id){
    $logDetails  =  Activity::find($id);               // ← rien
    return view('backend.log.view',compact('logDetails'));
}
```

**Trois défauts, et le troisième en cachait un quatrième.**

### 1. Aucun périmètre société (famille S7)

La liste ne montre que les activités causées par un utilisateur de la société
courante. Le détail n'en tenait aucun compte : `log-activity-view/42` servait
l'activité 42, quelle que soit la société. Et une activité n'est pas une ligne
anodine — elle porte **l'ancienne et la nouvelle valeur de chaque champ modifié** :
tarifs, coordonnées d'un marchand, permissions d'un rôle.

Le correctif reprend **exactement** la requête de `index()`, puis `abort_if(blank(...), 404)`.
Ce qu'on ne peut pas voir dans la liste, on ne peut pas l'ouvrir — y compris les
activités sans causeur rattaché à une société, que `index()` n'a jamais listées.
Cette règle du socle n'est pas modifiée, elle est alignée.

### 2. Aucune permission sur la route

```php
Route::get('logs',                   …)->middleware('hasPermission:log_read');
Route::get('log-activity-view/{id}', …);   // ← rien
```

Voir la liste et voir un détail sont le même droit. La seconde route porte
maintenant la même permission.

### 3. Sortie non échappée — XSS stocké

La vue rendait les valeurs avec `{!! $value !!}`. Ce sont des attributs de
modèles, donc **des saisies d'utilisateurs** : une raison sociale, une adresse.
Et le fragment est injecté en `.html()` dans une fenêtre modale depuis
`backend/log/index.blade.php`. Un marchand qui nomme sa boutique
`<script>…</script>` obtenait une exécution dans le navigateur de l'opérateur qui
consulte le journal.

Le socle échappait dans **une** branche de la même vue (la suppression, qui n'a
que des anciennes valeurs) et pas dans l'autre. La branche correcte était la
première.

### 4. Le défaut que corriger le troisième aurait créé

`properties['attributes']` reprend les attributs du modèle **tels qu'il les
porte**. `User` et `Role` déclarent `'permissions' => 'array'`, et les deux sont
journalisés : une modification de rôle journalise donc un **tableau**.

| Rendu | Sur un tableau |
|---|---|
| `{!! $value !!}` (avant) | avertissement « Array to string conversion », le mot « Array » à l'écran |
| `{{ $value }}` (naïf) | `htmlspecialchars()` refuse un tableau en PHP 8 → **erreur 500** |
| `{{ logValue($value) }}` | `["parcel_read","parcel_create"]` |

La branche « suppression », qui échappait déjà, **plantait donc déjà** sur une
suppression de rôle. `logValue()` (dans `app/Http/Helper/Helper.php`) rend
toujours une chaîne — son type de retour `: string` est un second garde-fou — et
le journal montre ce qui a été enregistré sans l'interpréter : une balise stockée
s'affiche en clair.

### Ce que ce correctif NE ferme pas — et c'est systémique

Le journal d'activité n'était pas une exception. Mesuré sur `routes/web.php` et
les contrôleurs du back-office :

| Constat | Compte |
|---|---|
| Routes à paramètre (`web.php` **+** `superadmin.php`) | **258** |
| … portant `hasPermission` | 197 |
| … **sans aucune permission** | **61 déclarations, 53 distinctes** |
| Appels `::find($id)` / `::find($request->id)` dans les contrôleurs | **12** |
| … dont un seul porte `companywise()` | **0** |

⚠️ Ce tableau annonçait d'abord « 31 des 100 routes à `{id}` de `web.php` » : un
comptage qui ne voyait qu'un seul nom de paramètre et un seul fichier. Le relevé
complet, et le classement des 53 — dont **seulement 6** relèvent d'une décision
de permission, et **4 sont des fuites vérifiées (S23–S26)** — vit dans
« 📋 Inventaire » en fin de document.

Le filet **S7** ne couvre que les routes `/api/v10` (`IsolationCoverageTest`).
Le back-office n'avait pas d'équivalent, et c'est ce qui a laissé passer celui-ci.
✅ **Il en a un depuis le 2026-09-18** : `WebIsolationCoverageTest`, décrit en fin
de document. Il ne prétend pas que le back-office est isolé — il gèle l'arriéré et
rend l'oubli impossible à ignorer.

La question des **permissions** reste entière, et reste une décision : ajouter une
permission là où il n'y en avait aucune **retire l'accès** à un rôle qui ne la
porte pas. Ce n'est pas un correctif mécanique, et le filet ne le fait pas à notre
place — il ne parle que de portée, pas de droits.

### Couverture

`tests/Feature/ActivityLogAccessTest.php` — 10 tests. Les routes du back-office
ne sont montées qu'avec un domaine de locataire, hors de portée d'un test : on
exerce donc le contrôleur et la vue directement, et on lit la déclaration de la
route (méthode d'`OnlinePayoutModuleDisabledTest`). Vérifié par quatre sabotages :
périmètre retiré, permission retirée, `{!! !!}` rétabli, `logValue()` rendant la
valeur brute — chacun fait échouer son test.

---

## 📋 Inventaire — les routes web à paramètre sans garde de permission (2026-09-18)

Demandé après **S22** : le journal d'activité n'était pas une exception, mais
« 31 routes » était un chiffre approximatif. Le voici corrigé et classé.

### La méthode, et pourquoi le chiffre annoncé était faux

`php artisan route:list` **ne voit pas** ces routes : dans `routes/web.php`, tout
ce qui suit `if ($domain)` (ligne 147) n'est enregistré que lorsque l'hôte de la
requête est un domaine de locataire connu — jamais en ligne de commande. Les
routes ont donc été relevées en **analysant** `routes/web.php` **et**
`routes/superadmin.php` (qui redéclare une partie des écrans `admin/` pour le
domaine central), en suivant la pile des préfixes de groupe.

| | Annoncé après S22 | Réel |
|---|---|---|
| Périmètre mesuré | `{id}` dans `web.php` seul | **tout paramètre**, `web.php` **+** `superadmin.php` |
| Routes à paramètre | 100 | **258** |
| … sans `hasPermission` | 31 | **61 déclarations, 53 distinctes** |

Le premier chiffre ne comptait qu'un seul nom de paramètre et un seul fichier.

### Les 53, classées — et seulement 6 relèvent d'une décision de permission

| Famille | Nombre | Ce qu'il faut en faire |
|---|---|---|
| **Publiques par nature** | 4 | Rien. `blog-details/{id}`, `service-details/{id}`, `/login/{social}`, `localization/{language}` |
| **Gardées autrement** | 1 | Rien. `invoice/statement/{invoice}/pdf` porte `->middleware('signed')` — une URL signée, que le comptage ne voyait pas |
| **Routes mortes** | 5 | **Les supprimer**, pas les garder (voir ci-dessous) |
| **Panneau marchand** | 26 | La permission **n'est pas le mécanisme** : ce panneau n'est pas gouverné par les permissions, il est scopé au marchand connecté. La question y est l'appartenance, et **S17, S18, S19 et S20 en ont déjà fermé la plus grande partie** |
| **Profil** | 10 | Rien à garder : le `{id}` est **décoratif**, `ProfileController` et son homologue marchand travaillent tous deux sur `auth()->user()->id`. ⚠️ Un détail : `view()` répond **500** quand l'identifiant ne correspond pas, là où 403 ou 404 conviendrait |
| **Carte du livreur** | 1 | À décider (voir ci-dessous) |
| **Back-office** | **6** | **C'est la vraie liste.** Détail ci-dessous |

### Les 5 routes mortes — à supprimer

Ces cinq routes pointent vers une méthode `view()` **qui n'existe pas** sur leur
contrôleur (vérifié par `method_exists`). Elles répondent **500** à chaque appel :
c'est pour cela que personne ne leur a jamais posé de permission.

| Route | Contrôleur |
|---|---|
| `GET admin/accounts/view/{id}` | `AccountController` |
| `GET admin/delivery-category/view/{id}` | `DeliverycategoryController` |
| `GET admin/delivery-charge/view/{id}` | `DeliveryChargeController` |
| `GET admin/fund-transfer/view/{id}` | `FundTransferController` |
| `GET admin/packaging/view/{id}` | `PackagingController` |

✅ **Retirées le 2026-09-18** — cinq lignes, pas dix : cette section annonçait
« déclarée deux fois chacune », par analogie avec `currency/edit/{id}` ; c'était
une inférence, pas une vérification. Les cinq ne vivent que dans `web.php`.
Vérifié avant de supprimer : aucune vue, aucun script ne référence leurs noms.

### Les 6 routes du back-office — et 4 d'entre elles sont des fuites vérifiées

| Route | Périmètre société | Permission suggérée | État |
|---|---|---|---|
| `GET admin/support/view/{id}` | ❌ `Support::find($id)` | `support_read` | **S23 — fuite** |
| `POST admin/expense/search-account/{id}` | ❌ `Account::find($id)` | `expense_create` | **S24 — fuite** |
| `GET admin/parcel/delivered/logs/info/{id}` | ⚠️ colis scopé, **chronologie non** | `parcel_read` | **S25 — fuite** |
| `GET admin/merchant/shops/default/{merchant_id}/{id}` | ❌ scopé par l'URL seulement | `merchant_update` | **S26 — écriture sur GET** |
| `GET admin/parcel/clone/{id}` | ✅ `companywise()` | `parcel_create` | permission seule — **elle duplique un colis** |
| `POST admin/income/search-account/{id}` | — | `income_create` | **cassée** : `searchAccount($id, Request $request)` appelle `$this->account->get($request)` — l'objet `Request` là où un identifiant est attendu |

✅ Les quatre premières — **S23 à S26** — sont **corrigées le 2026-09-18**, et
le tableau ci-dessus le détaille. Aucune n'a demandé de décision : un périmètre
société manquant se corrige comme S22, sans retirer l'accès à personne.

⚠️ **Les permissions, elles, restent entières** : aucune des six routes de ce
tableau n'en porte aujourd'hui. Corriger la fuite et poser la garde d'accès sont
deux gestes distincts, et seul le premier est fait.

Deux élargissements assumés en corrigeant, parce que la même méthode portait le
même défaut : **S23** scope aussi `chats()` (le fil d'un ticket hors périmètre),
et **S26** scope les **quatre** méthodes du dépôt back-office des boutiques —
`all()`, `get()` et `merchant_shops_get()` faisaient `where('id', …)` nu comme
`defaultShop()`. Ne corriger que la méthode nommée aurait laissé l'écran de
modification lire la boutique d'une autre société.

### La carte du livreur

`GET /deliveryMan/parcel/map/{id}/{lat}/{long}/{status}` →
`MapParcelController@parcelMap`. Deux choses :

- la requête de colis **est** `companywise()` : pas de fuite inter-société ;
- mais le `{id}` est un identifiant d'utilisateur **quelconque**, et la réponse
  liste, pour la tournée de ce livreur, le **nom, l'adresse et le téléphone de
  chaque client** plus le nom et le téléphone de chaque marchand. Sans permission,
  tout compte authentifié lit la tournée de n'importe quel livreur de sa société ;
- et `User::find($id)->deliveryman->id` fait une **erreur fatale** si
  l'identifiant n'est pas celui d'un livreur.

Périmètre intra-société, donc moins grave que S23–S26, mais ce n'est pas rien :
c'est un fichier clients. Permission suggérée : `delivery_man_read`.

### Ce qui reste à décider — et ce qui ne se décide pas

**Ne se décidait pas** : ✅ **fait le 2026-09-18** — S23, S24, S25, S26 et les
cinq routes mortes. Poser un périmètre société là où il en manque ne retire
l'accès à personne : c'est le correctif S22, répété quatre fois.

**Se décide** : les permissions. Ajouter `hasPermission:x` là où il n'y en avait
aucune **retire l'accès** à tout rôle qui ne porte pas `x`. Sur les six routes
du back-office et la carte du livreur, il faut vérifier, rôle par rôle, qui perd
quoi — en particulier `parcel_create` sur `admin/parcel/clone/{id}`, que des
agents utilisent peut-être sans porter la permission de création.

> ✅ **Fait le 2026-09-20 — S36.** La vérification a renversé cette note : le clone
> n'était pas un usage légitime sans permission, c'était un **contournement** de
> `parcel_create` (ni `clone` ni `clone-store` ne l'exigeaient, alors que `create` et
> `store` l'exigent). Et la permission « évidente » de
> `expense/search-account/{id}` était **fausse** : `expense_create` aurait fermé
> l'encaissement livreur à tous les chefs de hub. Voir « ✅ S36 » en fin de document.

**Le filet.** `IsolationCoverageTest` n'oblige à prouver l'isolation que des
routes `/api/v10`. C'est cette absence d'équivalent côté web qui a laissé passer
S22 puis ces quatre-là. Un filet web demanderait d'exercer des routes qui ne sont
montées que sur un domaine de locataire : les tests de S22 contournent la
difficulté en exerçant le contrôleur directement, ce qui marche mais ne prouve
rien sur le **routage**. C'est le vrai sujet, et il est plus grand que les
soixante lignes qu'il protégerait.

---

## ✅ S23 à S26 — les quatre fuites de l'inventaire (2026-09-18)

Quatre constats, une seule forme — celle de **S22** : la **liste** est scopée par
société, le **détail** fait `find($id)` nu. Changer l'identifiant dans l'URL
suffisait.

| | Ce que lisait le socle | Ce qui sortait |
|---|---|---|
| **S23** | `Support::find($id)` | le ticket d'une autre société : sujet, description, pièce jointe — **et tout son fil de discussion** |
| **S24** | `Account::find($id)` | un compte financier d'une autre société : titulaire, banque, numéro, solde |
| **S25** | `ParcelEvent::where('parcel_id', $id)` | la chronologie d'un colis d'une autre société : livreur, dates, **chemins de la photo et de la signature de livraison** |
| **S26** | `MerchantShops::where('id', …)` | la boutique de n'importe quel marchand — et le droit de **changer** sa boutique par défaut |

### Ce qui a été fait, et ce qui a été délibérément laissé

**S23** — le périmètre de `all()` (société de l'auteur du ticket, avec la branche
super-administrateur) est extrait en `ticketsVisibles()`, et les **trois**
lectures l'utilisent : `all()`, `get()` et `chats()`. Une seule définition, qui
ne peut plus diverger. Le contrôleur répond **404** au lieu de rendre une vue
avec un ticket nul.

**S24** — `get()` devient `companywise()` comme les quatre autres lectures du
dépôt. Les cinq appelants sont tous en requête back-office ; trois passent un
identifiant déjà lu sur un enregistrement scopé, où le filtre ne change rien.

**S25** — la protection est posée **au point d'appel**, pas dans la méthode :
`parcelEvents()` est aussi appelée par le **suivi public par numéro**
(`Frontend\FrontendController`), sans utilisateur authentifié — c'est sa raison
d'être, comme `parcelTrack()` au constat S17. Les deux écrans concernés
(`logs()`, `deliveredInfo()` — le second seul était relevé, le premier avait le
même défaut) s'arrêtent en 404 si le colis est hors périmètre, puis passent
`$parcel->id`, le colis **déjà vérifié**. Ce second geste ferme la fuite à lui
seul : sans garde, `$parcel->id` sur `null` échoue au lieu de servir les données.
Un test inscrit que la méthode reste non scopée **à dessein**.

**S26** — trois gestes. Le périmètre : les **quatre** méthodes du dépôt
back-office passent par `boutiquesDeLaSociete()`, qui filtre
`whereHas('merchant', companywise())` — `merchant_shops` ne porte pas de
`company_id`. L'ordre : le socle basculait les anciennes boutiques par défaut
**avant** de chercher la nouvelle, puis faisait une erreur fatale sur `null`,
laissant le marchand sans boutique par défaut du tout ; la cible est maintenant
résolue d'abord et rien n'est touché si elle est hors périmètre. Le verbe : la
route devient **PUT** et la vue soumet un formulaire `@csrf` — une écriture
déclenchable par un `GET` s'exécute depuis une image distante et échappe à la
protection CSRF. La confirmation SweetAlert est conservée, portée par
`form.confirm-submit` dans `custom.js` sur le modèle de `form#delete`.

⚠️ **Aucune permission n'a été ajoutée.** Les six routes du tableau de
l'inventaire n'en portent toujours pas. Corriger la fuite et poser la garde
d'accès sont deux gestes distincts : le second retire l'accès à tout rôle qui ne
porte pas la permission, et se décide en regardant les rôles. Pour
`merchant.shops.default` en particulier, le lien vit **hors** du bloc
`@if(hasPermission('merchant_shop_update'))` de sa vue : des rôles s'en servent
peut-être sans porter cette permission.

### Les cinq routes mortes

Retirées de `routes/web.php`. Elles pointaient vers une méthode `view()` qui
n'existe pas sur leur contrôleur et répondaient **500** à chaque appel. Vérifié
avant suppression : aucune vue, aucun script ne référence leurs noms. Le test
vérifie **les deux** moitiés du constat — route absente **et** méthode toujours
inexistante — pour qu'on ne puisse pas le « refermer » en ajoutant une méthode
vide.

### Couverture

`tests/Feature/BackOfficeScopingTest.php` — 14 tests. Les routes du back-office
ne sont montées qu'avec un domaine de locataire : on exerce les dépôts et les
contrôleurs directement, et on lit la déclaration des routes (méthode
d'`OnlinePayoutModuleDisabledTest` et d'`ActivityLogAccessTest`).

Vérifié par **six sabotages** : périmètre du ticket retiré, périmètre du compte
retiré, garde du colis retirée, périmètre de la boutique retiré, route morte
remise, `@method('PUT')` retiré de la vue. Chacun fait rougir son test — celui du
colis en **erreur** plutôt qu'en échec, ce qui est le signe attendu : sans garde,
`$parcel->id` sur `null` échoue avant d'avoir rien livré.

### Un effet de bord de ce lot

Le commentaire Blade qui explique le passage en `PUT` citait le nom d'une balise
image en prose. L'analyseur de `WebAccessibilityTest` l'a compté comme une image
sans `alt` — un faux positif. La prose est reformulée **et** l'analyseur ignore
désormais les commentaires Blade : le piège était facile à reposer. Vérifié qu'il
signale toujours une vraie image sans alternative.

## ✅ S27 — l'éditeur de `.env` n'était pas authentifié (2026-09-18)

🔴 **Le plus grave du document.** Avant correction, sur le VPS comme en local :

```
GET /env-editor        → 200   (visiteur anonyme)
GET /env-editor/files  → 200   (visiteur anonyme)
```

`geo-sot/laravel-env-editor` monte **onze** routes sous `/env-editor`. Sa
configuration publiée, `config/env-editor.php`, ne leur donnait que
`'middleware' => ['web']` — la session et le cookie CSRF, **aucune
authentification**. Ce qui était ouvert : lire le fichier d'environnement
(identifiants de base de données, `APP_KEY`, clés FedaPay, mot de passe de
messagerie), l'**écrire** clé par clé, **régénérer `APP_KEY`** — ce qui invalide
toutes les sessions et rend illisible tout ce qui est chiffré — et télécharger ou
**restaurer** une sauvegarde.

### Pourquoi personne ne l'avait vu

Trois raisons qui se cumulent, et qui valent pour tout paquet de ce genre :

- le fournisseur est **auto-découvert** (`bootstrap/cache/packages.php`) et son
  `loadResources()` appelle `loadRoutesFrom()` **sans garde d'environnement** :
  rien à écrire pour l'activer, rien à lire dans `routes/` pour le constater ;
- `php artisan route:list` dans ce dépôt ne montre pas les routes de locataire
  (`routes/web.php` n'est inclus que si l'hôte correspond à une ligne de
  `domains`), donc la liste des routes n'est jamais lue en entier ;
- nginx envoie tout sur Laravel (`location /`) et sa seule règle `deny` couvre
  les fichiers commençant par un point — `/.env` était bien protégé, `/env-editor`
  pas du tout.

Relevé en classant les routes web à paramètre pour le filet d'isolation :
`env-editor/files/download/{filename?}` figurait dans la liste des 218.

### Le correctif

`App\Http\Middleware\BlockEnvEditorRoutes` — un `abort(404)` inconditionnel —
est ajouté au groupe dans `config/env-editor.php` :

```php
'middleware' => ['web', \App\Http\Middleware\BlockEnvEditorRoutes::class],
```

**404 et non 403** : l'interface ne confirme pas son existence. Un 403 dirait à un
visiteur qu'il y a là un éditeur d'environnement à atteindre.

⚠️ **La bibliothèque reste en place, et c'est voulu** : `InstallerController`
écrit `.env` par la **façade** `EnvEditor` pendant l'installation. On coupe
l'**interface web**, pas le paquet — même doctrine que S21, D10, D11 et D12 :
couper l'usage, garder le code. Vérifié avant correction qu'aucune vue, aucun
script ne référence un nom de route `env-editor.*` : la coupure n'a pas d'effet de
bord.

C'est aussi la raison de ne pas désinstaller le paquet : `composer remove`
casserait l'installeur, et une mise à jour du socle le ramènerait.

### Le second versant, relevé en préparant le commit

`git status` juste avant de commiter montrait un fichier **non suivi** que je
n'avais pas écrit :

```
web/storage/env-editor/env_2026-09-18_141951
```

Le paquet écrit ses sauvegardes dans `storage_path('env-editor')`, et ce sont des
**copies intégrales de `.env`** — `APP_KEY` en clair à la troisième ligne. Une de
mes requêtes de constat en avait déclenché une. Le répertoire **n'était pas
ignoré** par git : `.env` l'est depuis toujours, mais pas sa copie sous un autre
nom. Un `git add -A` versionnait le fichier d'environnement dans un dépôt distant.

La copie est retirée et `/storage/env-editor` est ajouté à `web/.gitignore`. Le
test vérifie **les deux** : la ligne d'exclusion, et qu'aucun fichier ne traîne
dans le répertoire.

### Couverture

`tests/Feature/EnvEditorClosedTest.php` — 6 tests : les onze routes sont bien
montées (sinon le test n'a plus d'objet), aucune ne répond à un visiteur anonyme,
aucune ne répond à un **super-administrateur connecté** — éditer les secrets de la
plateforme depuis le web n'est pas un droit à distribuer, et aucune trace ne dirait
qui a changé quoi —, le garde est déclaré dans la configuration publiée, et la
bibliothèque fonctionne toujours (`EnvEditor::keyExists('APP_KEY')` + l'installeur
passe toujours par la façade), et le répertoire de sauvegarde reste ignoré et vide.

Vérifié par **deux sabotages** : le garde retiré de la configuration fait rougir
3 des 6 tests, dont celui qui appelle les onze routes ; une copie déposée dans
`storage/env-editor` fait rougir le sixième.

⚠️ Le premier sabotage a répondu `OK` à tort : un `bootstrap/cache/config.php`
traînait, et la suite lisait la configuration **mise en cache** au lieu du fichier.
Le sabotage n'a rien prouvé jusqu'à `php artisan config:clear`. À retenir pour tout
test qui lit `config()` : un cache de configuration rend un sabotage muet.


## ✅ S28 — la carte des courses du livreur (2026-09-18)

🔴 **Données personnelles de clients finals, sans authentification.**

```
GET /deliveryMan/parcel/map/{id}/{lat}/{long}/{status}  → 200  (visiteur anonyme)
```

La pile de middlewares, mesurée avant correction :

```
web, XSS, IsInstalled, PreventAccessFromCentralDomains,
InitializeTenancyByDomain, CompanyActivationMiddleware
```

Ni `auth`, ni `hasPermission`. La route était déclarée dans `routes/web.php`
**juste après le `});` d'un groupe, à la même indentation** — la forme même d'une
ligne qu'on lit comme étant « dedans ».

Et `User::find($id)` était **nu** : n'importe quel identifiant d'utilisateur, de
n'importe quelle société, était accepté.

Ce que la page rendait : `parcel-map.blade.php` écrit `@json($mapParcels)` dans le
source, et la charge utile porte, colis par colis —

| Champ | |
|---|---|
| `customer_name`, `customer_phone`, `customer_address` | le **client final**, un tiers qui n'a jamais eu de compte chez nous |
| `merchant_business_name`, `merchant_phone`, `merchant_address` | le marchand |
| `current_payable` | ce qu'il y a à encaisser, porte par porte |
| `tracking_id`, `latitude`, `longitude` | le colis et sa position |

Prouvé par appel HTTP anonyme : **200**, avec le téléphone et l'adresse du client
dans le corps de la réponse.

### Le correctif

**La route est retirée.** Vérifié avant : aucune vue, aucun script ne l'appelle,
et elle ne portait **même pas de nom de route** — rien ne pouvait donc la
référencer. La retirer ne retire l'accès à personne.

Le contrôleur et la vue **restent** (0 fichier supprimé du socle), et la portée
est posée dans le contrôleur :

```php
$livreur = User::companywise()->with('deliveryman')->find($id);
abort_if(blank($livreur) || blank($livreur->deliveryman), 404);
```

Deux raisons de le faire alors qu'aucune route n'y mène plus : si quelqu'un la
remonte un jour, elle ne rouvre pas la fuite ; et le socle faisait
`User::find($id)->deliveryman->id`, donc une **erreur fatale sur `null`** dès
qu'on passait l'identifiant d'un utilisateur qui n'est pas livreur.

### Couverture

`tests/Feature/DeliverymanMapLeakTest.php` — 7 tests : plus aucune route ne mène
au contrôleur ; l'URL qui répondait 200 répond 404 ; **l'hôte du test sert bien
les routes du locataire** (sans quoi le 404 précédent ne prouverait rien) ; le
contrôleur refuse un utilisateur d'une autre société ; un utilisateur qui n'est
pas livreur donne un 404 et non une erreur fatale ; son propre livreur est
toujours servi ; et un test qui tient l'**enjeu** — la vue verse bien la charge
utile dans la page, et cette charge utile porte bien les champs clients — pour
qu'on ne remonte pas la route en croyant qu'il ne s'agissait que d'une carte.

Vérifié par **deux sabotages** : la route remise fait rougir 2 tests, la portée du
contrôleur retirée en fait rougir 2 autres.

## ✅ Le filet d'isolation du web (2026-09-18)

`tests/Feature/WebIsolationCoverageTest.php` — le pendant de
`IsolationCoverageTest`, qui ne couvre que `/api/v10`.

### La surface, enfin mesurée

Le socle n'enregistre les routes du locataire que si `request()->getHost()` figure
dans la table `domains`. Conséquence : **`php artisan route:list` ne les voit
pas**, et personne n'avait jamais lu la liste en entier. C'est le fait central de
ce lot — les deux failles ci-dessus vivaient là.

| | |
|---|---|
| Routes à paramètre, toutes origines | **249** |
| … `/api/v10` (filet S7) | 31 |
| … montées par un **paquet** | 6 |
| … publiques ou d'authentification | 9 |
| … **routes d'application du back-office** | **203** |

`admin/` 166, `merchant/` 31, `super-admin/` 6, plus **deux à la racine**
(`category/edit/{id}`, `category/delete/{id}`) qu'un filtre par préfixe aurait
manquées.

### Les quatre invariants

1. **Tout est classé** — prouvée (avec son test), exemptée (avec le motif pour
   lequel le paramètre ne désigne pas la ressource d'autrui), publique à dessein
   (avec le motif), ou héritée. Une route neuve hors de ces listes fait échouer la
   suite : on ne peut plus en ajouter une sans dire ce qu'on a fait de sa portée.
2. **L'arriéré ne peut que rétrécir** — 171 routes héritées, plafond figé. Le seul
   mouvement autorisé est `HERITAGE` → `PROUVEES`, et il baisse le plafond.
3. **Une route de locataire est authentifiée**, ou déclarée publique avec un
   motif. C'est l'invariant qui aurait attrapé **S28** le jour où elle a été
   écrite. Une seule route d'application était nue sur les 203 : celle-là.
4. **Aucun paquet ne monte de routes web en silence** — le critère est l'**espace
   de noms de l'action**, pas le préfixe d'URL : un paquet peut monter ses routes
   n'importe où, y compris sous `admin/`, et c'est le nom de sa classe qui le
   trahit. C'est l'invariant qui aurait attrapé **S27**. Au premier passage il a
   d'ailleurs sorti deux paquets de plus (`laravel/sanctum`,
   `spatie/laravel-ignition`) qui n'étaient déclarés nulle part.

État de départ : **7 prouvées** (S22 à S26), **33 exemptées**, **6 publiques**,
**171 héritées**.

### Ce que ce filet n'est pas

Il **ne dit pas** que le back-office est isolé. L'arriéré de 171 est la mesure
honnête du contraire : ces écrans suivent le motif de S22 à S26 — liste scopée,
détail en `find($id)`. Certains sont sûrement sans danger, d'autres sûrement pas ;
personne ne l'a vérifié, et c'est tout le point de l'inscrire.

Il ne parle pas non plus de **droits** : les permissions restent la décision
ouverte de l'inventaire.

### Deux garde-fous repris de l'API

- Pas de **déclaration fantôme** : une entrée qui ne correspond plus à une route
  fait échouer le test (sinon le filet se vide de lui-même à mesure que les routes
  bougent).
- **F6** : un test déclaré doit utiliser `RefreshDatabase`. Une preuve d'isolation
  demande d'atteindre la ressource d'un autre compte, donc de l'avoir écrite ; un
  test sans base migrée ne peut pas la produire. Le filet de l'API avait déjà été
  pris en défaut là-dessus.

### Le mécanisme, réutilisable

`tests/Concerns/MountsTenantRoutes.php`. `BackOfficeScopingTest` affirmait que les
routes du back-office étaient « hors de portée d'un test » : c'était **faux**, il
suffit de semer le domaine. Le commentaire est corrigé.

Deux obstacles, dans cet ordre, et le second est un piège :

1. `routes/web.php` n'enregistre ses routes que si l'hôte figure dans `domains` et
   si `app.app_installed` vaut `yes`.
2. `PreventAccessFromCentralDomains` refuse par **404** tout appel dont l'hôte est
   un domaine central — et `config('tenancy.central_domains')` contient
   `localhost` et `127.0.0.1`, c'est-à-dire l'hôte des tests.

Un 404 « route absente » et un 404 « hôte central » sont indistinguables : on
croit que la route n'est pas montée alors qu'elle l'est. D'où le second domaine
(`pme.test`) et, dans `DeliverymanMapLeakTest`, un test dont le seul rôle est de
vérifier que l'hôte utilisé sert bien les routes du locataire.

C'est ce trait qui a permis de **prouver S28 par un appel HTTP** au lieu de le
déduire d'une déclaration.

### Vérification

**Six sabotages**, un par invariant, chacun vérifié rouge : une route neuve non
classée, la route de S28 remise (elle fait rougir **deux** invariants), une entrée
dans l'arriéré au-delà du plafond, une déclaration pointant une route disparue,
une couverture déclarée par un test sans base migrée, un paquet retiré de la liste.

⚠️ Le sabotage F6 a d'abord répondu vert parce que le test de remplacement que
j'avais choisi utilisait `RefreshDatabase` lui aussi : le sabotage ne sabotait
rien. Refait avec `FedaPayWebhookTest` — celui-là même qui avait révélé F6 côté
API — il rougit. Un sabotage qui passe au vert est d'abord un sabotage à
vérifier, pas un test à féliciter.

### Trois constats à part, signalés et non corrigés

- `admin/profile/{id}` et `merchant/profile/{id}` comparent bien à l'utilisateur
  connecté, mais répondent **`abort(500)`** au lieu de 403 ou 404 : un refus
  d'accès annoncé comme une panne serveur. Même famille que S15.
- `POST admin/income/search-account/{id}` est une **route morte** : personne ne
  l'appelle — même l'écran des revenus appelle celle des dépenses — et son
  contrôleur passe l'objet `Request` à `find()`, qui le traite comme un tableau de
  clés (`Request` est `Arrayable`) et renvoie donc une **collection** au lieu d'un
  compte. Depuis S24 c'est scopé, donc sans fuite.
- Une route du socle déclare son action avec un **antislash de tête**
  (`\App\Http\Controllers\…`) là où toutes les autres n'en ont pas. Sans le
  `ltrim` de l'invariant 4, elle passait pour un paquet tiers.


## ✅ S29 — les écritures du back-office, première passe sur l'arriéré (2026-09-18)

L'arriéré de `WebIsolationCoverageTest` comptait **171** routes. Le dépouiller
méthode par méthode a fait apparaître un motif unique, et il vaut pour la suite du
chantier :

> **Les écritures étaient moins scopées que les lectures.**

Le socle a beau n'avoir aucun *global scope* — décision S7, et elle tient — les
dépôts qui avaient reçu un périmètre l'avaient reçu sur leur `get()`. Leurs
`update()` et `delete()` restaient nus. Deux de ces trous sont les miens.

### Ce qui a été mesuré

Aucun `addGlobalScope` dans tout `app/` : `companywise()` est un scope **local**,
déclaré modèle par modèle. Un `find($id)` nu est donc réellement sans périmètre —
il n'y a pas de filet Eloquent en dessous.

En dépouillant les ~250 méthodes de dépôt, quatre formes apparaissent :

| Forme | Ce que ça vaut |
|---|---|
| `Modele::companywise()->find($id)` | scopé ✅ |
| `$m = Modele::find($id); if ($m->company_id == settings()->id):` | **gardé** — l'écriture ne part pas hors périmètre, mais un identifiant inexistant déréférence `null` |
| `Modele::find($id)` puis `$m->company_id = settings()->id; $m->save();` | 🔴 **vol de ligne** : la ligne d'une autre société est *transférée* chez nous |
| `Modele::destroy($id)` | 🔴 suppression sans périmètre |

Les deux dernières formes sont l'essentiel du travail restant.

### Les trois familles fermées dans cette passe

**1. La vitrine — six suppressions sans périmètre.** `Faq`, `Blog`, `Service`,
`Partner`, `SocialLink`, `WhyCourier` : leur `getFind()` est bien
`companyWise()->findOrFail()` — un `edit` hors périmètre répondait déjà 404. Mais
leur `delete()` était `Modele::destroy($id)`. Le contenu du **site public** d'un
autre transporteur se supprimait en changeant l'identifiant dans l'URL. On résout
d'abord, on supprime ensuite ; hors périmètre rien n'est touché et la valeur de
retour le dit, donc le contrôleur affiche une erreur au lieu d'un faux succès.

**2. ⚠️ Le trou de S23.** S23 avait scopé les *lectures* du support — `all()`,
`get()`, `chats()` — et j'avais écrit « les **trois** lectures l'utilisent ».
C'était exact, et incomplet : `update()` faisait `Support::find($id)` nu, donc on
réécrivait le ticket d'un autre transporteur (sujet, description, pièce jointe) et
on s'en attribuait la paternité par le `user_id` de la ligne suivante ; `delete()`
était `Support::destroy($id)`. Le `catch` vide rendait l'échec muet.

**3. ⚠️ Le trou de S26.** Même chose pour les boutiques : les quatre lectures
passaient par `boutiquesDeLaSociete()`, `update()` et `delete()` non. Et
`update()` lit `merchant_id` **dans la requête** : on pouvait donc aussi
**rattacher** la boutique d'un concurrent à son propre marchand.

### Et cinq refus d'accès annoncés comme des pannes

`MerchantPanel\MerchantParcelController` (`logs`, `duplicate`, `details`, `edit`,
`destroy`) : le périmètre est bien posé dans le dépôt (`ownedParcels()`), mais hors
périmètre `get()` rend `null` et la ligne suivante déréférençait — **500** là où
tout le reste du panneau répond 404. Même famille que S15. Idem pour
`MerchantShopsController::edit()`, dont le `blank()` était posé **après** la
déréférence, et `delete()`, qui annonçait un succès sans regarder ce que le dépôt
avait fait.

### La leçon, pour la suite de l'arriéré

**Corriger la fuite que le rapport montre ne suffit pas : il faut relire les
méthodes sœurs du même dépôt.** S23 et S26 ont tous deux fermé la porte signalée
en laissant la fenêtre ouverte à côté. Les prochaines passes se font dépôt par
dépôt, pas route par route.

### Effet sur le cliquet

| | Au départ | 1re | 2e | 3e | 4e |
|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | **90** |
| Exemptées | 33 | 35 | 35 | 35 | 35 |
| Publiques | 6 | 6 | 6 | 6 | 6 |
| Routes mortes retirées | — | — | — | 2 | 2 |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | **84** |

Les deux routes de `front-web/section` passent en *exemptées* : leur paramètre
nommé `{id}` est en réalité un **type** de section — `SectionController::edit($type)`
le dit — et la lecture est `companyWise()`.


### Seconde passe — le panneau marchand web (24 routes)

Ces dépôts sont scopés depuis **S7**, **S17** et **S18** : tout passe par un
`owned…()` sur le marchand ou l'utilisateur connecté. Ils étaient dans l'arriéré
pour une raison précise : ce qui le prouvait, `TenantIsolationTest`, appelle les
routes de l'**API**. Les écrans web sont d'autres contrôleurs, et le filet exige la
preuve au point d'entrée qu'il inscrit — c'est exactement la leçon **F6** : une
déclaration qui pointe un test incapable de toucher la route ne prouve rien.

`MerchantPanelWebScopeTest` pose donc **deux marchands de la même société**. Ce
n'est pas un détail de mise en scène : c'est le cas que la décision S7 nomme comme
le plus fréquent, et celui qu'un *global scope* Eloquent sur `company_id`
n'aurait **jamais** attrapé. La frontière ici n'est pas la société, c'est le
marchand.

Les douze écrans refusent tous (404), les six suppressions n'aboutissent pas, les
trois écritures à requête échouent, et un **contrôle négatif** vérifie que les
mêmes dépôts trouvent bien mes propres ressources — sans lui, un `owned…()` qui ne
renverrait jamais rien ferait passer le test sans rien prouver.

Le portefeuille est l'exception du lot : `admin/wallet-request/{approve,reject,delete}`
sont des écrans d'**administration** qui déplacent de l'argent, donc scopés par
société, via le garde `proprieteVerifiee()` posé dans le contrôleur par un lot
antérieur. Ils sont désormais couverts.

Un seul défaut trouvé dans cette passe, et il est de la famille déjà connue :
`MerchantPanel\InvoiceController::InvoiceDetails()` déréférençait un `null` hors
périmètre — **500** au lieu de 404, comme les cinq écrans de colis de la première
passe.

**Deux sabotages** : `ownedShops()` privé de son périmètre (3 rouges),
`proprieteVerifiee()` ramené à un `find()` nu (1 rouge).

Suite complète après les deux passes : **612 tests, 44 496 assertions, vert.**


### Troisième passe — le vol de ligne (30 routes)

La forme la plus grave de l'arriéré. Onze dépôts du back-office faisaient, dans
leur `update()` :

```php
$ligne = Modele::find($id);              // aucun périmètre
$ligne->company_id = settings()->id;     // ← et là, la ligne change de main
$ligne->save();
```

La conséquence dépasse la lecture : la ligne d'une autre société n'était pas
seulement modifiable, elle était **transférée**. Elle disparaissait des écrans de
son propriétaire — ses listes sont `companywise()` — et apparaissait dans les
nôtres. Un rôle et ses permissions, un entrepôt, un barème de livraison, un
versement à un hub, un poste, un service, un emballage : en changeant un
identifiant dans un formulaire.

Aucune trace ne l'aurait expliqué côté victime : la ligne n'est pas supprimée, elle
s'évapore.

`Role`, `Asset`, `Assetcategory`, `Designation`, `Packaging`, `Hub`,
`DeliveryCharge`, `To_do`, `NewsOffer`, `Department`, `HubPayment` — les onze
modèles portaient **déjà** `scopeCompanywise`, donc le correctif est uniforme et
refuse au lieu d'écrire : `companywise()->find(...)` puis un `blank()` qui rend
`false`, ce que les contrôleurs savent déjà afficher.

Six de ces dépôts lisaient aussi nu (`get($id)`) : `Role`, `Asset`,
`Assetcategory`, `Hub`, `To_do`, `HubPayment`. L'écran de modification d'un rôle
d'une autre société — **avec ses permissions** — s'ouvrait en changeant
l'identifiant.

### 🔴 Et trois chemins d'argent, dans la même famille

Les versements aux entrepôts (`HubPayment`) lisent nu **avant de déplacer de
l'argent** :

| Chemin | Ce qu'il faisait |
|---|---|
| `cancelProcess($id)` | **crédite notre compte bancaire** du montant lu, puis remet le versement en attente — sur celui d'une autre société, on encaissait son montant |
| `processed($request)` | le **décaissement** lui-même : écrit une sortie bancaire chez nous et solde le versement — celui d'une autre société inclus |
| `HubPaymentController::process($id)` | `HubPayment::findOrFail($id)` **nu dans le contrôleur** : l'écran qui précède le décaissement s'ouvrait sur le versement d'un autre |

### ⚠️ L'angle mort du filet, découvert ici

`processed($request)` prend son identifiant dans le **corps** de la requête
(`$request->id`), pas dans l'URL. `WebIsolationCoverageTest` énumère les routes
**à paramètre** : il ne pouvait pas la voir, et ne la verra jamais.

C'est une limite réelle et structurelle du filet, à retenir : **il couvre les
identifiants qui voyagent dans le chemin, pas ceux qui voyagent dans le corps.**
Les `update()` de ce socle prennent très souvent `$request->id` — c'est d'ailleurs
pourquoi tant de routes `update` n'apparaissent pas du tout dans les 249. Le filet
ne remplace donc pas la relecture d'un dépôt ; il garantit seulement qu'aucune
route à identifiant n'est ajoutée sans qu'on dise ce qu'on a fait de sa portée.

### Deux routes mortes de plus

`assets/view/{id}` et `asset-category/view/{id}` pointaient vers une méthode
`view()` qui n'existe sur **aucun** des deux contrôleurs : elles répondaient 500 à
chaque appel. Aucune vue, aucun script ne référençait leurs noms. Retirées, comme
les cinq de S26, et le test tient **les deux** moitiés du constat — route absente
*et* méthode toujours inexistante — pour qu'on ne puisse pas le refermer en
ajoutant une méthode vide.

### Couverture et vérification

`tests/Feature/BackOfficeRecordTakeoverTest.php` — 9 tests, dont un garde-fou qui
vérifie que la table des cas couvre bien les **onze** dépôts (une table vide ferait
passer tout le reste), et deux contrôles négatifs : mes propres lignes restent
lisibles et modifiables, et l'écran de décaissement s'ouvre bien sur **mon**
versement.

**Cinq sabotages**, chacun vérifié rouge : `Role::update` nu (2 rouges — le vol et
la réécriture), `DeliveryCharge::update` nu, `cancelProcess()` nu,
`processed()` nu, `process()` nu, et une route morte remise.

⚠️ **Deux de mes tests passaient d'abord pour la mauvaise raison**, et les deux
méritent d'être notés :

1. Le premier test des chemins d'argent était vert **sabotage compris**. Sans
   `from_account`, `cancelProcess()` échoue sur `Account::find(null)` avant
   d'écrire quoi que ce soit, et son `catch` rend `false` : j'observais un refus
   qui ne venait pas du périmètre. Il a fallu une fixture réaliste — un versement
   déjà décaissé, avec son compte bancaire — pour que le chemin soit *réalisable*
   et que le refus prouve quelque chose.
2. J'avais écrit `assertSame((int) $x->fresh()->status, (int) $x->fresh()->status)`
   — une tautologie qui compare une valeur à elle-même. Corrigée en relevant le
   statut avant l'appel.

La règle qui s'en dégage, et elle vaut au-delà de ce lot : **un test de refus doit
d'abord pouvoir réussir.** Si le chemin échoue tout seul, le refus ne prouve rien.

Suite complète après les trois passes : **621 tests, 44 552 assertions, vert.**


### Quatrième passe — la paie (5 routes)

`SalaryRepository` lisait **nu partout** : `get()`, `edit()`, `update()`,
`delete()`, `singleSalaryGenerate()`. Le bulletin de paie d'un agent d'une autre
société — bénéficiaire, mois, montant, compte bancaire — s'ouvrait en changeant
l'identifiant dans l'URL, et l'écran `pay-slip` l'imprime. C'est une **donnée
personnelle**.

Deux des cinq méthodes déplaçaient de l'argent :

- `update()` **créditait le compte bancaire de l'autre société** du montant lu,
  réécrivait sa ligne de paie (bénéficiaire, compte, montant, mois), puis débitait
  le nôtre. Un désordre comptable à cheval sur deux sociétés.
- `delete()` créditait ce même compte avant d'effacer la ligne.

`salaryGenerateDelete()` faisait exception : elle comparait déjà `company_id`. Elle
est inscrite, pas corrigée.

⚠️ Et `SalaryController::update()` lisait nu **dans le contrôleur**, avec
l'identifiant dans le **corps** — l'angle mort du filet, deuxième occurrence après
`HubPayment::processed()`. Le motif se confirme : dans ce socle, les `update()`
prennent `$request->id`, donc les chemins d'écriture sont précisément ceux que le
filet ne voit pas.

### ⚠️ Deux corrections de mes propres constats

**1. `Profile` n'était pas une fuite.** Je l'avais mis en tête des priorités de la
passe suivante — à tort. `ProfileRepository::get()` est bien `User::find($id)` nu,
mais ses **deux seuls appelants** ne lui passent jamais un identifiant choisi par
l'appelant : `Backend\ProfileController` lit `auth()->user()->id` (ses routes sont
déjà *exemptées* pour cette raison) et `Api\V10\AuthController` fait de même. Le
dépôt est nu, le chemin ne l'est pas. Rien à corriger.

**2. Le sabotage du test du contrôleur de paie ne mord pas, et c'est explicable.**
L'écran porte maintenant **deux** gardes — sur le bulletin, et sur le compte
bancaire. Retirer le premier seul ne fait pas rougir le test, parce que le second
(`AccountRepository::get()`, `companywise()` depuis S24) attrape déjà le cas. Il
faut retirer **les deux** pour voir revenir le comportement du socle : `Attempt to
read property "balance" on null`, un **500**. L'écran ne servait donc pas le
bulletin du voisin par ce chemin, il plantait ; ce que ce lot ajoute là, c'est un
refus propre. Le périmètre de la paie, lui, est prouvé par les trois autres tests,
sur le dépôt. C'est écrit dans le docblock du test.

### Et un sabotage qui, une fois de plus, n'en était pas un

Le sabotage de `SalaryRepository::get()` a d'abord répondu vert : mon ancre de
remplacement supposait que `edit()` suivait `get()`, alors que `store()` s'intercale
entre les deux. Le remplacement n'a jamais eu lieu. Refait sur la position exacte,
il rougit.

C'est la **troisième** fois dans ce chantier (après S27 et la fixture des chemins
d'argent). La règle est claire : **après un sabotage, vérifier que le fichier a
réellement changé avant de lire le résultat des tests.**

Suite complète : **626 tests, 44 576 assertions, vert.**

### Ce qui reste dans l'arriéré : 84 routes

Les deux familles graves — le vol de ligne et les chemins d'argent — sont fermées.
Ce qui reste est de la **lecture nue** (`get($id)` en `Modele::find($id)`, donc
l'écran `edit` d'une autre société) et des suppressions de la forme **gardée**, à
inscrire plutôt qu'à corriger :

| Dépôt | Ce que la lecture nue ouvre |
|---|---|
| `Fraud` (back-office) | une fiche de fraude d'une autre société |
| `HubPaymentRequest` | une demande de versement d'un entrepôt |
| `Income` / `Expense::update` | une écriture de recette, son compte, son marchand |
| `AccountHead` | un poste comptable |
| `MerchantPayment::edit` | un compte de versement de marchand |
| `Currency::getFind` | une devise (objet de plateforme — à exempter, probablement) |
| `Wallet::getFind` | une recharge de portefeuille |
| `User`, `Merchant`, `Parcel::update`, `Expense`, `FundTransfer`, `DeliveryCategory`, `DeliveryMan`, `HubInCharge`, `AccountHeads` | reste à dépouiller méthode par méthode |

`Salary` et `Profile` sont traités ou écartés ci-dessus. Les suivantes à ouvrir, par
ordre de gravité décroissante : **`Income` / `Expense::update`** (des écritures
comptables, et `Expense::update` lit nu avant de toucher un compte), puis
**`Wallet::getFind`**, **`MerchantPayment::edit`** et **`Fraud`** (back-office).
`Currency` est un objet de plateforme : à *exempter* plutôt qu'à corriger, comme les
plans et les sociétés du panneau central.

### Couverture

`tests/Feature/BackOfficeWriteScopeTest.php` — 11 tests : les six suppressions de
la vitrine hors périmètre ne suppriment rien ; les six légitimes suppriment
toujours ; la lecture hors périmètre est bien un 404 (sept modèles, `Page`
comprise, dont l'`update` délègue à `getFind`) ; le ticket d'un autre transporteur
ne se réécrit pas, ne se supprime pas, ne change pas de statut ; le mien reste
modifiable et supprimable ; la boutique d'un marchand d'une autre société ne se
réécrit pas, ne se supprime pas, et ses deux écrans répondent 404.

**Trois sabotages**, un par famille, chacun vérifié rouge : la suppression de la
vitrine remise en `destroy` nu (1 rouge), les écritures du support remises nues
(2 rouges), celles des boutiques remises nues (3 rouges — dont l'écran, ce qui
montre que le garde du contrôleur dépend bien du dépôt).

Suite complète : **606 tests, 44 429 assertions, vert.**

⚠️ Deux pièges de fixtures, notés pour les passes suivantes : ces modèles du socle
ne déclarent ni `$fillable` ni `$guarded` (un `Modele::create([...])` lève
`MassAssignmentException` — il faut écrire les attributs un par un), et
`supports` ne porte pas de `company_id` : son périmètre passe par l'**auteur**.

Et un outil : `php artisan test` fait avaler le message d'erreur par Collision
(« Cannot find TestCase object on call stack ») quand l'échec vient d'une
exception dans une fixture. `./vendor/bin/phpunit --filter …` donne le vrai
message.


## ✅ S30 — l'argent du back-office (2026-09-18, 5ᵉ passe)

Sept dépôts touchant à des comptes bancaires lisaient **nu**. Pour six d'entre eux,
la lecture nue précédait un mouvement d'argent :

| Dépôt | Ce que l'écriture faisait sur la ligne d'une AUTRE société |
|---|---|
| `Income::update` | touche son compte bancaire **et** le relevé de son marchand |
| `Expense::update` | **rend le solde** au compte rattaché à la dépense lue |
| `FundTransfer::update` | rejoue un **virement** entre ses deux comptes |
| `MerchantManage\Payment::update` | réécrit sa demande de versement, et la **réaffecte** à un autre marchand |
| `MerchantManage\Payment::cancelReject` | remet son versement rejeté **en attente de paiement** |
| `Account::update` | réécrit son compte bancaire (titulaire, banque, numéro, passerelle) |
| `HubPaymentRequest::update` | réécrit sa demande et la **rattache** à l'entrepôt de l'agent connecté |

Deux dissymétries connues, dans les deux sens : `Expense` et `Account` lisaient déjà
`companywise()` et écrivaient nu ; `CashReceivedFromDeliveryman` écrivait scopé et
lisait nu.

### ⚠️ Angle mort du filet, troisième occurrence

`MerchantmanagePaymentController::processed()` — le **décaissement** d'un versement
marchand — lit son identifiant dans le **corps** de la requête. Comme
`HubPayment::processed()` (3ᵉ passe) et `SalaryController::update()` (4ᵉ passe),
`WebIsolationCoverageTest` ne pouvait pas la voir. Trois fois sur cinq passes, et
toujours sur un chemin d'argent : **les décaissements de ce socle prennent leur
identifiant dans le corps.** À traiter comme une classe, pas comme des cas isolés.

### Une route morte de plus

`POST admin/income/search-account/{id}`, signalée à la 3ᵉ passe et retirée ici :
personne ne l'appelait — même l'écran des revenus appelle celle des **dépenses** — et
son contrôleur passait l'objet `Request` à `find()`, qui le traite comme un tableau
de clés (`Request` est `Arrayable`) et renvoyait donc une **collection** au lieu d'un
compte.

### Couverture et vérification

`tests/Feature/BackOfficeMoneyScopeTest.php` — 7 tests. L'assertion qui porte le
lot : un **instantané de tous les soldes de comptes** avant les écritures, comparé
après. Une écriture qui « échoue » après avoir crédité un compte aurait laissé le
désordre derrière elle ; le solde le dit, la valeur de retour non.

**Onze sabotages, onze morsures** — vérifiées une par une, avec contrôle que le
fichier a bien changé (empreinte MD5 avant/après) avant de lire le résultat des
tests.

### ⚠️ Quatre défauts dans mes propres fixtures, dont deux sabotages muets

Cette passe a été la plus instructive sur la manière de tester, et les quatre
méritent d'être écrits :

1. **Deux sabotages verts alors que le fichier avait bien changé.**
   `Expense::update` et `Account::update` refusaient *déjà* sans le périmètre — pour
   leurs propres raisons. `Account::update` remet toutes ses colonnes à `null` puis
   les repeuple selon `$request->gateway` : sans `gateway` ni `status` dans ma
   requête, il posait `status = null` sur une colonne NOT NULL et échouait tout seul.
   `Expense::update` lit `$request->account_head` (et non `account_head_id`, que je
   passais) puis termine sur `BankTransaction::where('expense_id',$id)->first()` :
   sans cette écriture bancaire dans la fixture, il levait sur `null`. Dans les deux
   cas j'observais un refus qui ne venait pas de ce que je testais.
2. **Un faux positif de mon instantané des soldes.** Je créais mon propre compte
   **après** l'instantané : il apparaissait donc comme un solde « qui a bougé » alors
   qu'il venait de naître.
3. **Une fixture que j'ai failli prendre pour un bug du socle.** Créer une remise
   d'espèces sans `user_id`, `hub_id` ni `delivery_man_id` fait lever une `TypeError`
   dans le trait `LogsActivity` de Spatie, qui remonte `user.hub.name` et
   `deliveryman.user.name`. Ce n'est **pas** un défaut de l'application :
   `ReceivedRepository::store()` renseigne toujours les trois. La fixture était
   irréaliste, pas le code.
4. **Un poste comptable manquant.** `SeedsTenant` n'inclut pas `AccountHeadSeeder`,
   donc la clé étrangère `expenses.account_head_id` échouait.

La règle de la 4ᵉ passe se confirme, et se précise : **un test de refus doit d'abord
pouvoir réussir — et ce qui l'en empêche est souvent dans la fixture, pas dans le
code testé.** Le symptôme est toujours le même : un sabotage qui reste vert.

Et une conséquence de l'environnement de test à retenir : sous `RefreshDatabase`,
une méthode qui ouvre une transaction, écrit, puis échoue **sans** `DB::rollBack()`
laisse la transaction ouverte ; tout est annulé au démontage du test. Un crédit
illégitime devient donc invisible. C'est une raison de plus de rendre le chemin
réalisable jusqu'au `commit`.

Suite complète : **634 tests, 44 628 assertions, vert.**

### Le cliquet après cinq passes

| | Départ | 1re | 2e | 3e | 4e | 5e |
|---|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | 90 | **113** |
| Exemptées | 33 | 35 | 35 | 35 | 35 | 35 |
| Publiques | 6 | 6 | 6 | 6 | 6 | 6 |
| Routes mortes retirées | — | — | — | 2 | 2 | **3** |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | 84 | **60** |

### Ce qui reste : 60 routes

Plus aucun chemin d'argent connu. Restent quatre familles, toutes de lecture ou de
suppression gardée :

| Famille | Routes | État |
|---|---|---|
| `MerchantInvoiceController` | 9 | les relevés marchands — `ownsOrAbort()` existe depuis S20, à vérifier et inscrire |
| `ParcelController` (back-office) | 8 | `get()` scopé société **et hub**, `update()` nu |
| `HubInChargeController` | 7 | scopé par `hub_id` seulement, pas par société |
| `MerchantController` (5), `MerchantPaymentAccount` (4), `MerchantDeliveryCharge` (6) | 15 | `get()` scopé, `update()` nu |
| `User` (3), `SmsSettings` (3), `Customs` (3), `Category` (2), `DeliveryCategory` (2), `DeliveryMan` (2), `Fraud` (2), `Currency` (2), `PushNotification` (1), `DeliveryZone` (1) | 21 | divers |

`Currency` reste à **exempter** : une devise est un objet de plateforme, comme les
plans et les sociétés du panneau central.


## ✅ S31 — les responsables d'entrepôt (2026-09-18, 6ᵉ passe)

`HubInChargeRepository` était scopé par `hub_id` **et par rien d'autre**. Or
`hub_incharges` ne porte pas de `company_id` : le périmètre doit passer par
l'entrepôt, exactement comme `merchant_shops` passe par le marchand (S26).
`where('hub_id', $hubID)` acceptait donc l'entrepôt de n'importe quelle société.

Neuf points dans un seul dépôt — le record du chantier :

| Méthode | Ce qu'elle faisait |
|---|---|
| `all`, `get` | la liste et la fiche des responsables d'un entrepôt d'autrui |
| `hub` | `Hub::findOrFail($hubID)` nu — la fiche de l'entrepôt |
| `users` | `User::where('user_type', ADMIN)` **sans filtre** : le menu déroulant nommait les administrateurs de **tous** les transporteurs |
| `store`, `update` | nommer un responsable sur l'entrepôt d'autrui, avec n'importe quel agent |
| `assignedHub` (rafle) | 🔴 passe **tous** les autres responsables actifs de l'entrepôt à inactif — chez l'autre société, une **interruption de service** |
| `assignedHub` (agent) | réécrivait le `hub_id` d'un utilisateur sans vérifier qu'il est à nous |
| `delete` | `HubInCharge::destroy($id)` nu, **sans même le `hub_id`** |

### Ce qui distingue ce lot des précédents

Les cinq passes antérieures fermaient des **lectures** et des **écritures sur une
ligne**. Ici le défaut le plus grave est une **écriture de masse** : la rafle
d'`assignedHub()` désactive d'un coup tous les responsables actifs de l'entrepôt
visé. Exercée sur l'entrepôt d'un concurrent, elle ne lit rien et ne vole rien —
elle le **prive de son responsable d'entrepôt**, et l'écran de la victime n'en dit
pas la cause.

`users()` mérite aussi d'être noté à part : ce n'est pas un identifiant qui fuyait,
c'est un **annuaire**. Le menu déroulant de l'écran listait les administrateurs de
toutes les sociétés de la plateforme, avec leurs noms.

### ⚠️ Deux requêtes laissées non scopées, à dessein

`HubInChargeController::assigned()` garde deux `HubInCharge::where(...)` sans filtre
société : ce sont des contrôles d'**unicité** (« cet agent est-il déjà responsable
ailleurs ? »). Non scopés, ils rejettent *trop* — ils ne divulguent rien. Et depuis
que `users()` est scopé, un agent ne peut plus y soumettre l'identifiant d'un
utilisateur d'autrui. Les laisser fait même surfacer les données aberrantes qu'a pu
laisser l'ancien défaut, au lieu de les accepter en silence.

### Couverture et vérification

`tests/Feature/HubInChargeScopeTest.php` — 10 tests, dont un **contrôle négatif
complet** : sur mon propre entrepôt, la nomination bascule bien l'ancien responsable
et réécrit bien le `hub_id` de l'agent. C'est le comportement voulu de la rafle, et
il devait continuer de fonctionner dans son périmètre.

**Onze sabotages, onze morsures.**

⚠️ Le onzième n'en était pas un au premier essai : le garde sur le `hub_id` de
l'agent restait vert, parce que mes tests s'arrêtaient au garde **précédent** (celui
sur l'entrepôt) et ne l'atteignaient jamais. Ce garde ne protège plus rien depuis une
base saine — `store()` et `update()` refusent déjà un agent d'autrui — mais il
protège les **données que l'ancien défaut a pu laisser** : une ligne
`hub_incharges` qui apparie notre entrepôt à leur agent. J'ai donc écrit un test qui
reproduit exactement cet état aberrant. Sans lui, le garde serait resté non exercé.

C'est une variante nouvelle du symptôme connu : **un sabotage vert ne veut pas
toujours dire que le test est faible — parfois le garde est simplement inatteignable
par le chemin testé, et il faut mettre en scène l'état qui le rend atteignable.**

Suite complète : **643 tests, 44 668 assertions, vert.**

### Le cliquet après six passes

| | Départ | 1re | 2e | 3e | 4e | 5e | 6e |
|---|---|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | 90 | 113 | **120** |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | 84 | 60 | **53** |

### Ce qui reste : 53 routes

| Famille | Routes | État |
|---|---|---|
| `MerchantInvoiceController` | 9 | les relevés marchands — `ownsOrAbort()` existe depuis S20, à vérifier et inscrire |
| `ParcelController` (back-office) | 8 | `get()` scopé société **et hub**, `update()` nu |
| `Merchant` (5), `MerchantPaymentAccount` (4), `MerchantDeliveryCharge` (6) | 15 | `get()` scopé, `update()` nu |
| `User` (3), `SmsSettings` (3), `Customs` (3), `Category` (2), `DeliveryCategory` (2), `DeliveryMan` (2), `Fraud` (2), `Currency` (2), `PushNotification` (1), `DeliveryZone` (1) | 21 | divers |

`Currency` reste à **exempter** : une devise est un objet de plateforme.


## ✅ S32 — les relevés de règlement (2026-09-20, 7ᵉ passe)

**Une passe de vérification.** Les six méthodes de `InvoiceRepository` que ces neuf
routes atteignent — `merchantInvoiceGet`, `merchantInvoiceDetails`, `invoiceGet`,
`statusUpdate`, et les listes par statut — sont **déjà** `companywise()`, posées par
S14, S20 et le chantier 4. L'arriéré les tenait faute de **test**, pas faute de
périmètre. C'est le premier lot du chantier où il n'y avait presque rien à corriger,
et c'est une bonne nouvelle : la surface non prouvée n'est pas entièrement une
surface non protégée.

### Trois couches de périmètre, qui ne protègent pas des mêmes choses

Ce module est le seul de l'arriéré à en superposer trois, et il valait la peine de
les nommer :

| Couche | Contre qui | Où |
|---|---|---|
| `companywise()` | un **administrateur d'une autre société** | le dépôt |
| `ownsOrAbort()` | un **marchand** qui forge le `merchant_id` de l'URL depuis son propre panneau (S20) | le contrôleur — et seulement pour les comptes marchands |
| le **lien signé** | personne : la signature *est* l'authentification | `signedPdf()`, déjà déclaré *publique à dessein* dans le filet |

La deuxième est celle qui compte pour le cas que la décision S7 nomme comme le plus
fréquent : **deux marchands de la même société**. `companywise()` ne les sépare pas ;
`ownsOrAbort()` si. Son garde existait depuis S20 et n'avait jamais été exercé par un
test — il l'est maintenant.

### Le seul défaut, et une décision de ne pas toucher

`InvoiceDetails()` déréférençait un `null` hors périmètre : **500** au lieu de 404,
la famille S15 pour la septième fois.

⚠️ `index()` est laissé **tel quel**, et c'est un choix : sa liste est
`companywise()`, donc pour le marchand d'une autre société elle rend une liste
**vide**, pas un 404. Rien ne fuit. Je ne change pas un comportement pour la seule
symétrie avec les écrans voisins ; le test l'inscrit tel qu'il est.

### ⚠️ Un doublon mort qui a avalé un sabotage

`InvoiceRepository::InvoicePdf($merchant_id, $invoice_id)` et
`InvoiceRepository::invoiceGet($merchant_id, $invoice_id)` ont un corps **identique,
ligne pour ligne**. Seule `invoiceGet()` est appelée ; `InvoicePdf()` est du code mort
— déclaré dans l'interface, appelé par personne (l'action `InvoicePdf` du contrôleur
passe par `invoiceGet`).

Mon sabotage, ancré sur la **requête** et non sur la méthode, a touché le jumeau mort.
Le fichier avait bien changé — l'empreinte MD5 le confirmait — et le test est resté
vert. Refait avec la signature de la méthode comme ancre, il rougit.

Le doublon est **laissé en place** : il est scopé de la même façon, et retirer une
méthode d'interface dépasse le cadre d'une passe d'isolation. Mais il est signalé,
dans le docblock du test comme ici, parce qu'un **correctif** appliqué au mauvais
jumeau serait tout aussi silencieux qu'un sabotage.

C'est la deuxième fois qu'une ancre trop courte fait passer un sabotage à côté
(après `SalaryRepository::get()`, 4ᵉ passe). La règle se durcit : **ancrer un
sabotage sur la signature de la méthode, jamais sur une ligne qui peut se répéter.**

### La devise : exemptée, avec une réserve

`currencies` ne porte **pas** de `company_id` : c'est une liste de référence de la
plateforme (pays, symbole, code, taux de change). Et `general_settings.currency`
stocke le **symbole**, une chaîne — pas une clé étrangère. Supprimer une devise ne
casse donc pas l'affichage d'une autre société.

⚠️ Réserve inscrite dans l'exemption : le catalogue reste **partagé et modifiable**
par tout administrateur. Une société peut retirer une entrée qu'une autre voudrait
choisir plus tard. C'est un problème de **catalogue commun**, pas de cloisonnement —
signalé, hors du cadre de ce chantier.

### Couverture

`tests/Feature/MerchantInvoiceScopeTest.php` — 7 tests, couvrant les trois couches,
plus deux contrôles négatifs (mes relevés restent lisibles et modifiables ; un
marchand télécharge bien le sien).

**Six sabotages, six morsures** après correction de l'ancre.

Suite complète : **654 tests, 44 727 assertions, vert.**

### Le cliquet après sept passes

| | Départ | 1re | 2e | 3e | 4e | 5e | 6e | 7e |
|---|---|---|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | 90 | 113 | 120 | **129** |
| Exemptées | 33 | 35 | 35 | 35 | 35 | 35 | 35 | **37** |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | 84 | 60 | 53 | **42** |

---

## ✅ S33 — les colis du back-office (2026-09-20, 8ᵉ passe)

Le module le plus central du socle, et la passe où l'arriéré a rendu le plus : **huit
routes prouvées, cinq lectures nues fermées** — dont deux sur des chemins que le filet
ne voit pas.

### Le périmètre de lecture était correct, et pas trop strict

La 7ᵉ passe annonçait un risque : `ParcelRepository::get()` filtre par société **et par
l'entrepôt de l'agent**, ce qui est plus strict que partout ailleurs, et j'avais noté
qu'il fallait vérifier que ce n'était pas trop strict au point de casser un écran
légitime. **Vérifié : il ne l'est pas.** Le filtre par entrepôt ne s'applique que si
l'agent a un `hub_id` ; un administrateur sans entrepôt voit tous les colis de sa
société. Le test l'inscrit **dans les deux sens** — l'administrateur sans entrepôt voit
tout, le chef de hub ne voit que le sien — pour que personne ne « relâche » ce filtre en
croyant corriger une gêne.

### Ce qui restait

| Point | Ce qu'il faisait |
|---|---|
| `update()` — le colis | 🔴 `Parcel::find($id)` **nu** — le colis d'une autre société se réécrivait entièrement |
| `update()` — le marchand | 🔴 `Merchant::find($request->merchant_id)` **nu**, et la ligne suivante **réaffectait** le colis à ce marchand d'ailleurs, en recopiant son `hub_id` dans `first_hub_id` et `hub_id` |
| `update()` — le tarif | 🔴 le **même identifiant relu une troisième fois**, sans périmètre, pour le calcul : taux COD et TVA négociée d'un marchand d'une autre société |
| `store()` | 🔴 même lecture nue : le colis naissait dans **ma** société mais **au nom** du marchand d'une autre, à qui il débitait frais, TVA et net à reverser |
| `duplicateStore()` | 🔴 identique à `store()` |
| `duplicate`, `edit`, `parcelPrint`, `parcelPrintLabel` | déréférençaient `null` → **500** au lieu de 404 (famille S15, 8ᵉ fois) |
| `details` | rendait une page **vide avec un 200** : la vue déréférence `$parcel` partout avec l'opérateur `@`, qui supprime l'erreur — un refus d'accès devenait une page blanche sans message |
| `destroy` | annonçait un succès sans regarder ce que le dépôt avait fait |

### Les deux chemins de création : la tache aveugle du filet, troisième famille

`store()` et `duplicateStore()` n'ont **aucun identifiant dans leur URL** — c'est le
formulaire qui porte `merchant_id`. Ils sont donc **invisibles au filet**, qui n'énumère
que les routes à paramètre, et ils ne figuraient dans aucune de ses trois listes. Je ne
les ai trouvés qu'en corrigeant `update()` : la même lecture nue, dans le même fichier,
trois méthodes plus haut.

C'est la troisième fois que la tache aveugle rend quelque chose — après les trois
décaissements des passes 5 et 6. Elle n'est pas une série de cas isolés : **c'est une
classe**, et le filet ne la couvrira jamais par construction. Ce qui la couvre est de
lire le fichier entier quand on en corrige une méthode, pas la route.

> ⚠️ **Cette dernière phrase a été démentie — et c'est une bonne nouvelle.** « Le filet
> ne la couvrira jamais par construction » était vrai de `WebIsolationCoverageTest`,
> qui énumère `$route->parameterNames()`. **S38** a construit l'autre filet, celui qui
> énumère la **source des contrôleurs et des dépôts** plutôt que les routes
> (`BodyIdentifierCoverageTest`). La classe est donc mécanisable ; elle ne l'était pas
> par le filet que j'avais sous la main. Je laisse la phrase d'origine et ce démenti
> côte à côte : c'est le genre d'affirmation qu'il vaut mieux voir tomber que voir
> disparaître.

⚠️ Conséquence de ces deux-là, plus grave qu'une lecture : le colis se créait avec
`company_id = settings()->id` — la **mienne** — et `merchant_id` d'ailleurs. L'écriture
n'était pas seulement hors périmètre, elle était **incohérente** : une ligne appartenant
à deux sociétés à la fois, dont le débit de portefeuille et le relevé partaient chez le
voisin.

### ⚠️ Un constat que j'ai cru trouver et qui n'en était pas un

`details()` construisait `ParcelEvent::where('parcel_id', $id)` avec l'identifiant
**brut** — la forme exacte de S25. J'ai d'abord cru à une troisième fuite de
chronologie que S25 avait manquée.

Vérification faite : **`details.blade.php` n'utilise pas `$parcelevents`.** La requête
était calculée à chaque affichage et jamais rendue. Aucune fuite. Elle passe désormais
par le colis déjà vérifié, comme `logs()` et `deliveredInfo()`, pour que la forme ne
redevienne pas un piège — mais ce n'est **pas** une faille fermée, et je ne la compte
pas comme telle.

### La leçon de la tarification : un test de refus doit d'abord pouvoir réussir

Les huit premiers sabotages ont donné **deux verts** — les deux du dépôt. Diagnostic :
`ChargeCalculator` s'exécute **avant** le `save()`, et depuis **D4 étape 6** il refuse un
colis sans zone tarifée (`UnpricedDeliveryException::sansZone()`). Le jeu d'amorçage ne
tarife que la société 2 ; mes requêtes de test ne portaient pas de zone. Le test
observait donc un refus venu de la **tarification**, pas du périmètre — et concluait que
le périmètre tenait.

C'est la **troisième occurrence de la même leçon** (après `cancelProcess` sans
`from_account` et `Account::update` sur une colonne NOT NULL). La fixture installe
maintenant une zone tarifée pour ma société, par `ZoneCatalog` — le chemin de
production — et les dix sabotages mordent.

### Un test existant a rougi, et il avait raison de le faire

`BackOfficeScopingTest::test_the_timeline_method_stays_unscoped_on_purpose` affirmait
« exactement **deux** appels `parcelEvents($parcel->id)` dans le contrôleur » — les deux
écrans que S25 avait corrigés. En réparant `details()`, il y en a trois, et le test est
tombé **sur un progrès**.

L'assertion est réécrite pour dire l'invariant au lieu de le compter : **aucun** appel ne
part de l'identifiant brut, tous passent le colis déjà vérifié. Vérifié à son tour par
sabotage. Un compte exact fait échouer un test quand le code s'améliore ; une forme
interdite, non.

### Couverture

`tests/Feature/BackOfficeParcelScopeTest.php` — 10 tests, 34 assertions, dont **trois
contrôles négatifs** : mes propres écrans rendent bien leur vue, l'administrateur sans
entrepôt voit bien ses colis, et les deux chemins de création fonctionnent bien avec mon
marchand.

**Dix sabotages, dix morsures.**

### Le cliquet après huit passes

| | Départ | 1re | 2e | 3e | 4e | 5e | 6e | 7e | 8e |
|---|---|---|---|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | 90 | 113 | 120 | 129 | **137** |
| Exemptées | 33 | 35 | 35 | 35 | 35 | 35 | 35 | 37 | **37** |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | 84 | 60 | 53 | 42 | **34** |

### Ce qui reste : 34 routes

| Famille | Routes | État |
|---|---|---|
| `Merchant` (5), `MerchantPaymentAccount` (4), `MerchantDeliveryCharge` (6) | 15 | `get()` scopé, `update()` nu |
| `User` (3), `SmsSettings` (3), `Customs` (3), `Category` (2), `DeliveryCategory` (2), `DeliveryMan` (2), `Fraud` (2), `PushNotification` (1), `DeliveryZone` (1) | 19 | divers |

La famille **marchand** serait la prochaine, et c'est la plus sensible qui reste : le
`update()` nu d'un `MerchantPaymentAccount` ou d'un `MerchantDeliveryCharge` ne réécrit
pas un colis, il réécrit **où l'argent est versé** et **à quel tarif il est facturé**.

---

## ✅ S34 — la famille marchand (2026-09-20, 9ᵉ passe)

L'annonce de la 8ᵉ passe était juste, et en dessous de la réalité. Trois
sous-familles, trois natures de dégât, et **le dépôt le plus ouvert rencontré depuis
le début du chantier**.

| Sous-famille | Ce qui était atteignable chez un autre transporteur |
|---|---|
| **fiche marchand** | `update()` lisait `Merchant::find($id)` **nu** puis réécrivait l'e-mail **et le mot de passe** du compte marchand — changer les deux suffit à s'y connecter : **reprise de compte complète** |
| **comptes de versement** | 🔴 **tout** le dépôt était nu : lecture de la banque, du titulaire, du **numéro de compte** et du numéro Mobile Money ; suppression de n'importe quelle ligne ; et remplacement du compte du voisin par le sien |
| **barèmes négociés** | `update()` lisait la ligne sans `companywise()` puis écrivait `company_id = settings()->id` — **reprise de ligne** ; `store()` acceptait n'importe quel `merchant_id` d'URL ; l'AJAX de la grille rendait le **montant** d'un autre transporteur |

### Le pire du lot : là où l'argent est versé

`PaymentRepository` n'avait **aucun** périmètre, sur aucune de ses neuf méthodes. Et
le mécanisme d'écriture rend la chose immédiatement exploitable : `bankstore()`,
`mobilestore()`, `bankUpdate()` et `mobileUpdate()` **détruisent** la ligne désignée
par `editid` avant d'en créer une neuve avec le `merchant_id` du formulaire. L'écran
« ajouter un compte de versement » était donc, littéralement, « retirer le compte du
marchand d'un autre transporteur et le remplacer par le mien ».

⚠️ **Ces quatre écritures n'ont pas d'identifiant dans leur URL.** `merchant_id` et
`editid` voyagent dans le **corps** de la requête. Le filet ne les voit pas — il
n'énumère que les routes à paramètre — et elles ne figuraient dans aucune de ses trois
listes. **Quatrième occurrence** de cette tache aveugle après les trois décaissements
et les deux créations de colis, et la plus coûteuse des quatre.

### ⚠️ Un scope qui mentait

`MerchantPayment::scopeCompanywise()` faisait `where('company_id', settings()->id)`.
La table `merchant_payments` **n'a pas de colonne `company_id`** : sa migration de 2022
ne porte que `merchant_id`. Le scope aurait donc levé une erreur SQL — personne ne
l'appelait, donc personne ne l'avait constaté.

C'est un piège d'une nature nouvelle dans ce chantier : jusqu'ici le danger était
l'absence de périmètre. Ici, sa **présence apparente**. Quiconque venait « scoper » ce
modèle voyait un `companywise()` déjà écrit et pouvait raisonnablement le croire bon.

Le scope dit maintenant la vérité — le rattachement passe par le marchand, seul
porteur du `company_id` :

```php
return $query->whereHas('merchant', function($query){ $query->companywise(); });
```

Un test inscrit les deux moitiés : la colonne est absente, et le scope sépare.

### Deux constats **D4** relevés au passage, distincts du périmètre

Ils ne sont pas des failles d'isolation, mais ils cassaient la tarification, et ils
vivent dans les méthodes que cette passe corrigeait.

1. **`MerchantDeliveryChargeRepository` ne posait pas `zone_id`.** Depuis l'étape 6,
   `DeliveryChargeResolver::trancheDeZone()` cherche la ligne négociée **par zone** :
   une ligne sans zone n'est jamais trouvée. Le tarif négocié à la main ne facturait
   donc **rien** — le marchand négociait un prix qui ne s'appliquait pas. Et
   `beninlink:tarification-prete` compte ces lignes comme un **blocage de
   déploiement** : une société ayant utilisé cet écran depuis l'étape 6 faisait échouer
   sa propre mise en production. `MerchantRepository::store()` portait déjà la zone ;
   c'est cet écran qui avait été oublié.

2. **`company_id` n'était posé sur aucun des trois chemins de création d'un
   marchand.** Les barèmes négociés créés avec le marchand naissaient avec
   `company_id = null`, et `MerchantDeliveryCharge::companywise()` — donc son écran —
   ne les voyait **jamais**. Un marchand tout juste créé avait un barème invisible, et
   non modifiable par l'interface.

### La leçon de cette passe : deux sabotages verts, deux trous dans le test

Les 22 sabotages ont donné deux verts au premier tour, et aucun des deux ne
disculpait le code.

- **`pay/marchand`** (retirer le périmètre du marchand désigné) restait vert parce que
  **tous** mes tests d'écriture passaient un `editid`, dont le garde refusait d'abord :
  le périmètre du marchand n'était jamais atteint. Il manquait le cas de la **création
  pure** — un `merchant_id` d'ailleurs, sans `editid`, rien à détruire, juste un compte
  de versement ajouté chez le voisin. Test ajouté, sabotage mordu.
- **`cmdc/ajax`** restait vert parce que `deliveryChargeInfo()` teste
  `request()->ajax()` — la requête **globale**, pas celle qu'on lui passe en argument.
  Ma requête AJAX construite à la main ne franchissait donc pas la condition : la
  méthode rendait `''` sans rien lire. Il faut lier la requête au conteneur
  (`app()->instance('request', …)`). C'est la deuxième fois qu'un garde inatteignable
  fait passer un sabotage (après S31) : un sabotage vert ne disculpe pas le code, il
  interroge le test.

### Couverture

`tests/Feature/MerchantFamilyScopeTest.php` — 20 tests, 71 assertions, dont **six
contrôles négatifs** : mes écrans de versement s'ouvrent, mes quatre écritures de
versement fonctionnent, ma ligne de barème se supprime, ma fiche se modifie et se
supprime, et l'AJAX répond pour ma propre grille.

**Vingt-deux sabotages, vingt-deux morsures** après correction des deux tests.

### Le cliquet après neuf passes

| | Départ | 1re | 2e | 3e | 4e | 5e | 6e | 7e | 8e | 9e |
|---|---|---|---|---|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | 90 | 113 | 120 | 129 | 137 | **152** |
| Exemptées | 33 | 35 | 35 | 35 | 35 | 35 | 35 | 37 | 37 | **37** |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | 84 | 60 | 53 | 42 | 34 | **19** |

### Ce qui reste : 19 routes

| Famille | Routes |
|---|---|
| `User` (3 : édition, permissions, suppression) | 3 |
| `SmsSettings` (3), `Customs` (3), `Category` (2), `DeliveryCategory` (2), `DeliveryMan` (2), `Fraud` (2), `PushNotification` (1), `DeliveryZone` (1) | 16 |

Le reste est du **paramétrage** — libellés, catégories, règles, modèles de SMS — sauf
`User` : `admin/users/permissions/{id}` attribue des rôles, et un `update()` nu y
serait de la même nature que la reprise de compte marchand de cette passe. Ce serait
la prochaine.

---

## ✅ S35 — les comptes et le paramétrage : **l'arriéré est fermé** (2026-09-20, 10ᵉ passe)

**171 → 0 en dix passes.** `PLAFOND_HERITAGE` vaut désormais zéro, et ce zéro devient la
règle de travail : toute route web à paramètre ajoutée au dépôt doit être **prouvée**,
**exemptée** avec son motif, ou **publique à dessein**. `HERITAGE` n'est plus une liste
d'attente, c'est une liste qui doit rester vide.

### Les 19 dernières se répartissaient en cinq états

Il valait la peine de les nommer plutôt que de les traiter en bloc : « 19 routes non
prouvées » ne veut pas dire « 19 failles ».

| État | Routes | Suite donnée |
|---|---|---|
| **déjà correctes** | 4 — `customs` ×3 (chantier 5), `delivery-zone/countries` (D4) | un test, rien d'autre |
| **lecture nue** | 2 — `fraud/edit`, `users/permissions` | scopées |
| **reprise de ligne** | 3 écritures — `users`, `deliveryman`, `delivery-category` | scopées |
| **500 au lieu de 404** | 6 suppressions | forme gardée réparée |
| **ni l'un ni l'autre** | 2 exemptées (`category`), 5 **mortes** (`sms-settings`) | motivées / retirées |

### 🔴 Le pire du lot : l'attribution des droits

`UserController::permission()` lisait `User::where('id', $id)->first()` — **ni société,
ni type d'utilisateur** — et `UserRepository::permissionUpdate()` **écrivait** le jeu de
permissions sur la même lecture nue, avec un identifiant venu du **corps** de la requête
(`PUT admin/users/permissions/update`). On réécrivait donc les droits de n'importe quel
compte de n'importe quelle société.

Le filtre sur `user_type` compte autant que celui sur la société : sans lui, l'écran
atteignait aussi les **marchands** et les **livreurs** de sa propre société, dont le jeu
de permissions n'a rien à voir avec celui d'un agent.

`UserRepository::update()` s'y ajoutait — `User::find($id)` nu suivi de
`company_id = settings()->id`, sur un écran qui réécrit l'e-mail, le **mot de passe**, le
rôle et les permissions. C'est la reprise de compte de S34, cette fois sur
l'administrateur d'un autre transporteur. Et `Role::where('id', $request->role_id)`
était nu dans `store()` **et** `update()` : le rôle, donc le jeu de droits, pouvait venir
d'ailleurs.

Les cinq points passent par une seule méthode privée, `utilisateurDeLaSociete()`, qui
reprend **exactement** le périmètre de `get()` — pour qu'un écart entre la lecture et
l'écriture ne puisse plus réapparaître.

### Cinquième occurrence de la tache aveugle (annoncée « dernière », à tort)

Cinq des écritures corrigées ici n'ont **pas d'identifiant dans leur URL** :
`users/update`, `users/permissions/update`, `deliveryman/update`,
`delivery-category/update` et `fraud/update` le portent dans le **corps**. Le filet ne
les voit pas, et ne les verra jamais.

Compte de cette classe sur les dix passes d'arriéré : **trois décaissements**
(passes 5 et 6), **deux créations de colis** (S33), **quatre écritures de compte de
versement** (S34), **l'AJAX de la grille** (S34), **cinq écritures de paramétrage**
(S35). Quinze points, aucun visible du filet **des routes à paramètre**.

⚠️ « Dernière » était faux : **S38** a énuméré cette surface pour de bon — **122 routes
d'écriture** lisant un identifiant dans le corps — et y a trouvé bien davantage, dont
les chemins **en lot**. Ce que cette passe-ci pouvait honnêtement dire, c'est que
c'était la dernière de *l'arriéré des routes à paramètre*. Ce qui les a tous trouvés est la même
chose : **lire le fichier entier quand on corrige une de ses méthodes**, jamais la seule
route.

### Cinq routes mortes, pas deux

`sms-settings/edit/{id}` et `delete/{id}` figuraient dans l'arriéré. En les retirant,
trois autres sont apparues dans le même bloc : `create`, `store` et `status`.
`SmsSettingsController` ne déclare que **`index` et `update`** — les cinq autres
désignaient des méthodes inexistantes, et les atteindre rendait 500. Aucune vue ne les
nommait. Deux d'entre elles étaient de plus **déclarées deux fois**, côté locataire et
côté super-administrateur.

Les réglages SMS n'ont pas de CRUD : ce sont des clés dans `sms_settings`, écrites par
`update()`, qui est déjà `companywise()` clé par clé. Son `{id}` est le **nom de la
passerelle** (`reve`, `twilio`, `nexmo`), pas l'identifiant d'une ressource — d'où son
exemption. Un test inscrit l'invariant : aucune route `sms-settings.*` au-delà de ces
deux méthodes.

### Une décision : plus strict que la lecture

`DeliverycategoryRepository::get()` laisse lire la **catégorie 1**, partagée par toutes
les sociétés (le jeu d'amorçage crée ses six catégories **sans** `company_id`). Son
`update()` ne le permet plus : laisser réécrire cette ligne laisserait n'importe quel
transporteur renommer la catégorie de tous les autres — et le socle y ajoutait
`company_id = settings()->id`, donc se l'appropriait. L'écriture est volontairement plus
étroite que la lecture, et le test le dit.

### Le catalogue partagé, pour la deuxième fois

Les deux routes `category/*` sont **exemptées** : la table `categorys` ne porte **aucun**
`company_id` — c'est un catalogue de plateforme, et rien ne la consomme en dehors de son
propre CRUD. Même cas que `currencies` au constat S32, même réserve inscrite : le
catalogue reste **partagé et modifiable**. Ce n'est pas un défaut de cloisonnement, c'est
un problème de catalogue commun.

### La leçon de cette passe : un refus venu d'un accident

Un sabotage sur 22 est revenu vert : retirer le garde du rôle dans `store()`.

La cause est instructive. Sans `hub_id`, le socle traversait
`if($role->permissions !== null)`. Lire une propriété sur `null` est un simple *warning*
PHP — que Laravel promeut en `ErrorException`. Le `catch` l'avalait et rendait `false` :
la création était refusée **par accident**, par le gestionnaire d'erreurs, et non par une
règle.

Avec `hub_id`, cette branche est sautée (les permissions viennent de `hubPermissions()`),
`$role` n'est jamais déréférencé, rien ne lève — **et le compte se créait avec le
`role_id` d'une autre société**. C'est le chemin réellement atteignable ; le test
l'exerce, et le sabotage mord.

Troisième fois qu'un garde inatteignable fait passer un sabotage (après S31 et S34), et
la première où la barrière accidentelle venait du **gestionnaire d'erreurs du
framework**. Un refus que l'on ne sait pas expliquer n'est pas une protection.

### Une bricole du socle réparée au passage

`UserController::destroy()` appelait `$this->repo->delete($id)` une **seconde fois** dans
son `elseif` : une suppression qui échouait au premier tour était donc retentée, image du
disque comprise. On appelle une fois et on lit le résultat.

### Couverture

`tests/Feature/UserAndSettingsScopeTest.php` — 18 tests, 62 assertions, dont **sept
contrôles négatifs**, plus deux tests qui n'inscrivent pas un périmètre mais un **motif**
(le catalogue sans société, les deux seules méthodes de `SmsSettingsController`).

**Vingt-deux sabotages, vingt-deux morsures** après correction du test du rôle.

### Le cliquet, fermé

| | Départ | 1re | 2e | 3e | 4e | 5e | 6e | 7e | 8e | 9e | 10ᵉ |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Prouvées | 7 | 33 | 57 | 85 | 90 | 113 | 120 | 129 | 137 | 152 | **166** |
| Exemptées | 33 | 35 | 35 | 35 | 35 | 35 | 35 | 37 | 37 | 37 | **40** |
| **Héritées (plafond)** | **171** | 143 | 119 | 89 | 84 | 60 | 53 | 42 | 34 | 19 | **0** |

La ligne « Exemptées » agrège, depuis la première passe, les routes **exemptées** et
celles déclarées **publiques à dessein** — au 10ᵉ jalon, 34 + 6. Les trois listes du
filet plus l'arriéré vide totalisent **206** routes classées ; les cinq écarts avec le
relevé d'origine sont les routes mortes retirées en cours de route (trois en 3ᵉ passe,
cinq ici, moins celles ajoutées depuis par les chantiers).

### Ce qui n'est pas fermé, et reste à décider

Le chantier d'isolation est terminé ; ces points-là ne le sont pas, et ils n'en relèvent
pas :

- ✅ **les permissions** des routes de l'inventaire — **posées le 2026-09-20 (S36)**,
  après mesure rôle par rôle. Voir la section ci-dessous ;
- ✅ `admin/profile/{id}` et `merchant/profile/{id}` — **corrigés le 2026-09-20 (S37)** ;
- le **doublon mort** `InvoiceRepository::InvoicePdf()` (S32) ;
- les **catalogues communs** `currencies` (S32) et `categorys` (S35), partagés et
  modifiables ;
- les six catégories de livraison du jeu d'amorçage, créées **sans société** : aucune
  société ne peut donc les modifier par son écran, sauf la n° 1 en lecture ;
- ✅ la **destination du refus** de `PermissionCheckMiddleware` pour une navigation
  de page — **corrigée le 2026-09-20 (S38)** ;
- `IncomeController::searchAccount()` est une **méthode morte** : sa route a disparu
  et l'écran des revenus appelle celle des dépenses. Elle passe de plus l'objet
  `Request` là où un identifiant est attendu.

---

## ✅ S36 — la garde d'accès des routes nues (2026-09-20)

La dernière décision ouverte du chantier de sécurité. Elle était ouverte pour une
bonne raison : poser `hasPermission:x` **retire l'accès** à tout rôle qui ne porte
pas `x`. Il fallait donc mesurer avant, et la mesure a changé une conclusion.

### La mesure, rôle par rôle

Sur les jeux réellement semés (`RoleSeeder`, plus le jeu **fixe** du chef de hub dans
`UserRepository::hubPermissions()`) :

| Permission | Admin | User | Chef de hub | Super-admin |
|---|---|---|---|---|
| `support_read` | ✅ | ✅ | ❌ | ✅ |
| `parcel_read` | ✅ | ✅ | ✅ | — |
| `merchant_shop_update` | ✅ | ❌ | ❌ | — |
| `parcel_create` | ✅ | ❌ | ❌ | — |
| `expense_create` | ✅ | ❌ | ❌ | — |
| `cash_received_from_delivery_man_create` | ✅ | ✅ | ✅ | — |

### Ce qui a été posé

| Route | Permission | Qui perd l'accès |
|---|---|---|
| `GET admin/support/view/{id}` **et son jumeau super-admin** | `support_read` | le chef de hub, par la cloche de notifications |
| `GET admin/parcel/delivered/logs/info/{id}` | `parcel_read` | **personne** |
| `GET admin/parcel/multiple/print/label` | `parcel_read` | **personne** |
| `PUT admin/merchant/shops/default/{merchant_id}/{id}` | `merchant_shop_update` | le rôle `User` — **voulu** |
| `GET admin/parcel/clone/{id}` **+** `POST admin/parcel/clone-store` | `parcel_create` | le rôle `User` — **voulu** |
| `POST admin/expense/search-account/{id}` | **liste de cinq droits** | un rôle qui n'en a aucun |

### 🔴 Le cas annoncé comme « délicat » était un trou, pas un arbitrage

La note de l'inventaire disait : « en particulier `parcel_create` sur
`admin/parcel/clone/{id}`, que des agents utilisent peut-être sans porter la
permission de création ». La vérification a montré autre chose :

```
parcel/create       → hasPermission:parcel_create
parcel/store        → hasPermission:parcel_create
parcel/clone/{id}   → (rien)      ← ouvre le formulaire pré-rempli
parcel/clone-store  → (rien)      ← ENREGISTRE le colis
```

Le clone était un **contournement complet** du droit de création. Et
`duplicateStore()` ne fait pas que créer : il **débite le portefeuille du marchand**
(`WalletDebit`, depuis W5). Ce que le rôle `User` perd ici, il n'aurait jamais dû
l'avoir. Les **quatre** portes exigent maintenant le même droit, et un test l'inscrit
comme invariant.

⚠️ Garder le `GET` sans garder le `POST` n'aurait rien fermé : c'est le second qui
enregistre. C'est la même leçon que les identifiants portés par le corps de la
requête — une garde posée sur l'écran et pas sur l'écriture ne garde rien.

### ⚠️ Le cas qui ne se ferme pas par une permission unique

`expense/search-account/{id}` est appelée par **quatre** écrans, avec quatre droits
différents — et les quatre fichiers qui la nomment sont dans `public/backend/js/` :

| Écran appelant | Droit de son écran |
|---|---|
| dépenses (`expense/custom.js`) | `expense_create` |
| revenus (`income/custom.js`) | `income_create` |
| salaires (`expense/salary.js`) | `salary_create` / `salary_update` |
| **panneau du chef de hub** (`hub_panel/received_from_delivery_man/custom.js`) | `cash_received_from_delivery_man_create` |

`expense_create` — la permission « évidente », celle que suggérait l'inventaire —
aurait fermé **l'encaissement livreur à tous les chefs de hub**, dont le jeu fixe ne
le porte pas. C'est le seul cas du lot où la permission suggérée était fausse.

D'où l'extension du middleware : `hasPermission:a|b|c` passe si l'utilisateur porte
**l'une** des permissions. La route est donc fermée à un rôle qui n'a aucun des cinq
droits financiers, sans casser aucun de ses quatre appelants.

### Trois défauts du middleware, corrigés en l'étendant

`PermissionCheckMiddleware` faisait huit lignes, et trois choses l'y attendaient :

1. **`in_array($p, null)` lève une `TypeError` en PHP 8.** Un compte dont le jeu de
   permissions est nul — et rien dans le socle n'exige que la colonne soit remplie —
   recevait une **erreur 500** sur chaque route gardée, au lieu d'un refus.
2. **Un refus AJAX rendu en `redirect('/')`**, donc **200 avec le HTML du tableau de
   bord** dans le gestionnaire de succès d'un `$.ajax`. Les appels AJAX et JSON
   reçoivent maintenant **403**. La navigation de page garde la redirection : la
   changer toucherait les 197 autres déclarations, et c'est un lot à part.
3. **`abort('403')` après `return redirect('/')`** : du code mort, jamais atteint.
   Retiré, et le 403 posé là où il sert.

### La leçon de ce lot : trois refus indistinguables, dans un seul test

Ce test a été faux **trois fois**, et toujours pour la même raison — un refus qui
ressemblait à un autre refus.

1. **`subscriptionCheckMiddleware`** redirige vers `/subscription` toute requête d'un
   compte dont la société n'a pas d'abonnement en cours. Sans abonnement semé, mes
   22 appels répondaient `302` **quelles que soient les permissions**. Un test qui
   comparait à `302` était vert et ne prouvait rien. C'est la **troisième barrière**
   de cette famille après les deux déjà documentées dans `MountsTenantRoutes`
   (l'enregistrement conditionnel des routes, l'hôte central) — elle y est
   maintenant, avec `souscrireLeLocataire()`.
2. Corrigé cela, comparer la **destination** à `/` ne suffisait pas non plus : sans
   en-tête `Referer`, `redirect()->back()` retombe **aussi** sur `/`. Une validation
   échouée sur `clone-store` et un retour en arrière sur l'impression en lot
   devenaient indistinguables d'un refus de la garde.
3. Le discriminateur final est donc : `302` **et** destination `/`, avec un référent
   fourni. La garde vise toujours `/` ; `back()` vise le référent.

### Une limite assumée

Le jumeau super-administrateur de `support/view/{id}` est vérifié **sur la
déclaration**, pas par un appel HTTP : `MountsTenantRoutes` monte `routes/web.php`
sur un hôte de **locataire**, et les routes du super-administrateur ne
s'enregistrent que sur un domaine **central** — l'exact opposé. Un sabotage l'a
montré, en restant vert. Le test le dit, et vérifie au passage que le jeu du
super-administrateur porte bien `support_read`.

### Couverture

`tests/Feature/WebPermissionGuardTest.php` — 23 tests, 50 assertions. Chaque route
est exercée **par appel HTTP** sur un hôte de locataire, donc à travers le routage
complet et sa pile de middlewares, dans les deux sens : refusée sans le droit,
servie avec. Plus l'invariant des quatre portes de la création, celui des paires
sœurs, les trois comportements du middleware, et un test qui inscrit la **mesure**
elle-même — si un jeu de rôles change, la décision doit être relue.

**Treize sabotages, treize morsures** après correction du test du jumeau.

---

## ✅ S37 — les écrans de profil : un refus annoncé comme une panne (2026-09-20)

Constat relevé en inventoriant la surface web (S28) et laissé ouvert depuis :
`admin/profile/{id}` et `merchant/profile/{id}` **comparaient** bien l'identifiant de
l'URL à l'utilisateur connecté, puis répondaient **`abort(500)`**.

Aucune fuite — c'est pourquoi ce n'était pas urgent. Mais trois conséquences réelles :
l'opérateur voit une page de panne là où il devrait lire « interdit », la supervision
compte une erreur applicative à chaque tentative, et **un test ne peut pas distinguer
un refus d'un bug** — exactement ce que la 5ᵉ passe a payé ailleurs.

### En le corrigeant, deux défauts de plus dans les mêmes fichiers

**1. Quatre méthodes sur cinq ne vérifiaient rien.**

| Méthode | Ce que faisait le socle |
|---|---|
| `view($id)` | comparait, puis `abort(500)` |
| `create($id)` | **ignorait** `$id` et servait mon propre formulaire |
| `changePassword($id)` | idem |
| `update($id)` | écrivait mon profil, puis redirigeait vers `$id` |
| `updatePassword($id)` | idem |

Pas de fuite là non plus — l'écriture porte toujours sur `auth()->user()->id` — mais
l'URL mentait, et c'est pour cette raison que le filet avait dû classer ces huit
routes « identifiant décoratif ».

**2. Une écriture réussie qui finissait sur la page de panne.** `update()` et
`updatePassword()` redirigeaient vers `profile.index` avec l'identifiant **de l'URL**.
Modifier son profil depuis `/admin/profile/update/42` enregistrait bien, puis affichait
un 500. C'est le seul défaut du lot qu'un utilisateur rencontrait pour de vrai.

**3. Un troisième 500, côté marchand seulement.**
`MerchantProfileRepository::get()` cherche le marchand **par son `user_id`**. Un compte
qui n'est pas marchand — un agent, un livreur — franchit le garde sur son propre
identifiant, puis la vue déréférence `null`. Ce panneau ne porte aucune garde de type
d'utilisateur ; il répond 404.

### ⚠️ 403 et non 404 — un écart assumé avec la convention du chantier

S23 à S35 répondent **404** hors périmètre, et c'était juste : là, cacher
l'**existence** de la ressource d'une autre société fait partie du cloisonnement.

Ici l'identifiant est celui d'un compte de la **même société**, que tout porteur de
`user_read` voit déjà dans la liste. Il n'y a pas d'existence à cacher, et
« interdit » est la réponse exacte. La convention n'est pas contredite, elle est
appliquée à un cas différent.

### Le filet : d'exemptées à prouvées

Les dix routes de profil étaient **exemptées**, motif « identifiant décoratif : la
méthode lit `auth()->user()->id` ». Le motif était exact et reposait sur la seule
lecture du code. Le paramètre est maintenant **vérifié**, et prouvé par appel HTTP :
elles passent en **prouvées**.

| | avant S37 | après |
|---|---|---|
| Prouvées | 166 | **176** |
| Exemptées | 34 | **24** |
| Publiques à dessein | 6 | 6 |
| Héritées | 0 | 0 |

C'est le premier mouvement `EXEMPTEES → PROUVEES` du chantier — l'arriéré, lui, était
fermé depuis S35. Un motif d'exemption n'est pas une dette, mais quand il devient
vérifiable, il vaut mieux le vérifier.

### La leçon de ce lot : un test qui lisait sa propre documentation

`test_neither_profile_controller_aborts_with_a_server_error` a échoué au premier
passage — en trouvant `abort(500)` dans le **tableau de mon propre docbloc**, celui qui
décrit ce que faisait le socle. Le test lisait le fichier, pas son code.

Corrigé en retirant commentaires et docblocs par `token_get_all()` avant de chercher.
C'est la même famille que les leçons précédentes du chantier — un test qui observe
autre chose que ce qu'il croit observer — sous une forme nouvelle : ici l'obstacle
était **la documentation du correctif lui-même**.

### Couverture

`tests/Feature/ProfileAccessTest.php` — 25 tests, 51 assertions. Les dix routes sont
exercées **par appel HTTP** dans les deux sens : 403 sur l'identifiant d'un autre, et
servies sur le sien. Plus la destination d'après-écriture, le 404 du compte non
marchand, et deux tests qui inscrivent le constat d'origine sur le **code** (plus aucun
`abort(500)`, plus aucune destination construite depuis l'URL).

**Dix-sept sabotages, dix-sept morsures.** Suite complète : **752 tests, 45 039
assertions, vert** (mesuré après rebasage sur le `main` qui a reçu la PR #106).

### Ce qui restait, de la même famille

La **destination du refus** de `PermissionCheckMiddleware` pour une navigation de
page — ✅ **traitée dans S39**, plus bas. C'était le dernier membre connu de la
famille « un refus qui ne se dit pas ».

---

## ✅ S38 — la tache aveugle du filet : l'identifiant qui vit dans le corps (2026-09-20)

### Ce qui a déclenché ce lot

Dix passes d'arriéré ont fermé les 171 routes **à paramètre d'URL**. En les
fermant, j'ai buté **cinq fois** sur une forme que le filet ne cherchait pas :
une écriture dont l'identifiant voyage dans le **corps** de la requête.
`UserRepository::update`, `permissionUpdate`, `DeliveryManRepository::update`,
la création d'un compte avec le rôle d'un autre, la reprise d'un bulletin de
paie — chaque fois trouvée **par hasard**, en suivant un appel depuis une autre
piste.

`WebIsolationCoverageTest` construit sa liste de travail à partir de
`$route->parameterNames()`. Une route comme `POST
admin/parcel/delivery-man/assign/cancel` n'en a aucun : l'identifiant du colis
arrive dans `$request->parcel_id`. Elle n'apparaît dans aucune des quatre listes
du filet, et n'y apparaîtrait jamais. La note d'avertissement était déjà dans
`web/CLAUDE.md` ; elle disait que ce qui couvrait ce cas était « de lire le
fichier entier ». C'est-à-dire : rien de mécanique.

### L'énumération : les contrôleurs, pas les routes

**122 routes d'écriture sans paramètre d'URL** lisent un identifiant dans la
requête. Le relevé ne pouvait pas se faire sur les routes — c'est exactement ce
qui échoue — mais sur la **source** des méthodes de contrôleur, et sur celle des
méthodes de dépôt qu'elles appellent.

⚠️ **Le second niveau n'est pas un raffinement.** Sans lui, `POST
admin/assign-pickup/bulk` sort de l'énumération : son contrôleur ne lit aucun
identifiant, il passe `$request` tel quel au dépôt. C'est précisément une des
méthodes en lot corrigées ci-dessous. Un filet qui n'aurait regardé que le
contrôleur aurait reconduit l'angle mort qu'il prétend fermer. Et la résolution
doit être **exacte** : chercher `store()` dans tous les dépôts injectés d'un
contrôleur rapproche n'importe quoi de n'importe quoi — 126 routes au lieu de
122, avec des listes de champs illisibles. Le filet résout donc
`$this->prop->methode()` sur la **propriété réelle** de l'instance.

### Ce que le relevé a trouvé dans le dépôt des colis

Un balayage par réflexion sur `ParcelRepository` : **27 méthodes** portent une
lecture nue de `Parcel`. Dix-huit d'entre elles sont précédées d'une garde posée
aux passes précédentes, une (`delete`) décide correctement par comparaison de
`company_id`. Restaient **huit méthodes sans aucun périmètre**, plus **neuf
annulations** dont la garde manquait.

| Méthode | Ce qu'un identifiant étranger obtenait |
|---|---|
| `transferToHubMultipleParcel` | statut `TRANSFER_TO_HUB` + `transfer_hub_id` vers NOTRE entrepôt |
| `deliveryManAssignMultipleParcel` | colis d'autrui confiés à NOTRE livreur, SMS à SON client |
| `pickupdatemanAssignedBulk` | idem côté ramassage |
| `AssignReturnToMerchantBulk` | retour au marchand + écritures comptables |
| `parcelReceivedByMultipleHub` | `hub_id` réécrit : le colis change d'entrepôt |
| `returnAssignToMerchant` | frais de retour débité au livreur d'en face |
| `bulkParcels` | nom, téléphone, adresse du client |
| `parcelMultiplePrintLabel` | les mêmes données, **sur une étiquette imprimée** |
| 9 annulations de statut | statut reculé **et évènements SUPPRIMÉS** |

**Sept des huit sont des chemins en lot**, et c'est ce qui les avait gardées
invisibles : leur identifiant n'est pas un identifiant, c'est une **liste**.
Rien dans la signature d'une route ne la porte.

Ce que supprimaient les annulations mérite d'être nommé : les `ParcelEvent` sont
la **chronologie que le client d'en face consulte** en suivant son colis.
L'effacement ne laissait aucune trace, chez personne.

### La forme du correctif

Elle suit la logique du socle plutôt que de la corriger. Dans un lot, un
identifiant hors périmètre est **ignoré** et la boucle continue : c'est ce que
fait déjà le socle quand une ligne manque, et cela évite qu'un identifiant
glissé par erreur dans une sélection fasse échouer tout le lot d'un agent
légitime. Sur un chemin à identifiant unique, on **refuse**.

### Le filet

`tests/Feature/BodyIdentifierCoverageTest.php` — même doctrine que son aîné, sur
la moitié de surface qui lui échappait : tout est classé, l'arriéré ne peut que
rétrécir, et une déclaration qui pointe un test incapable d'atteindre la
ressource d'en face ne compte pas (F6).

À son ouverture : **21 prouvées, 11 exemptées, 90 à l'arriéré**. L'arriéré n'est
pas une liste de failles — c'est la liste des routes dont personne n'a encore
écrit ce qu'il advient d'un identifiant étranger. Certaines sont sûrement
correctes ; le point est qu'on ne le sait pas, et que jusqu'ici rien ne le
demandait.

Il porte un test de plus que son aîné : un **témoin du second niveau**. Si la
résolution des dépôts casse — contrôleur que le conteneur ne monte plus,
propriété renommée — `POST admin/assign-pickup/bulk` disparaîtrait
silencieusement de l'énumération et le filet se remettrait à ne voir que ce que
voyait déjà l'autre. Le témoin le dit à voix haute.

### Couverture et sabotage

`tests/Feature/ParcelBulkScopeTest.php` — 17 cas, 76 assertions, un par chemin
corrigé, chacun avec son **contrôle négatif**. Sur les annulations, l'assertion
ne porte pas seulement sur le statut mais sur le **nombre d'évènements** du
colis d'en face : un statut remis en place ne dirait rien de l'effacement.

Dépôt remis dans son état d'avant : **17 rouges sur 17**, chacun sur son
assertion — et les statuts affichés par les échecs (6, 7, 2, 26, 19) sont
exactement ceux que le défaut écrivait sur le colis d'autrui.

Filet saboté à son tour, deux fois : second niveau coupé → le témoin mord, avec
son message ; une ligne retirée de l'arriéré → la route redevient non classée.

Suite complète : **750 tests, 45 105 assertions**.

---

> ⚠️ **Collision de numéro, et comment elle est tranchée.** Deux lots menés en
> parallèle ont pris le numéro **S38** : celui ci-dessus (la tache aveugle du filet)
> et celui ci-dessous (la destination d'un refus de droit). Le premier est **déjà
> fusionné dans `main`** ; il garde donc son numéro, et le second devient **S39**.
> Les messages de commit du second disent encore « S38 » — c'est la trace d'un lot
> qui ignorait l'autre, laissée telle quelle plutôt que réécrite.

## ✅ S39 — la destination d'un refus de droit (2026-09-20)

Dernier membre de la famille ouverte depuis S28 : **un refus qui ne se dit pas**.

S36 avait corrigé le cas AJAX — un refus rendu en `redirect('/')` arrivait en **200
avec du HTML** dans le gestionnaire de succès d'un `$.ajax` — et laissé la navigation
de page inchangée, en notant que la changer demandait de regarder plus loin que le
middleware. Voici ce que cette mesure a donné.

### Ce que `redirect('/')` faisait réellement

`/` n'est **pas** le tableau de bord. Sur un domaine de locataire, il est déclaré dans
le groupe `frontend` de `routes/web.php`, **hors de `auth`** : c'est la **page publique
du site**.

Un opérateur refusé était donc **éjecté du back-office vers la vitrine commerciale**,
sans un mot. Il n'apprenait ni qu'il avait été refusé, ni pourquoi, et devait revenir
au tableau de bord à la main. Le cas n'est pas théorique : depuis S36, un chef de hub
qui clique une notification de ticket de support est refusé — et c'est exactement ce
qui lui arrivait.

### Les trois gestes

| | |
|---|---|
| il **reste dans le back-office** | retour sur la page précédente, et à défaut le **tableau de bord**, qui n'exige aucune permission (vérifié par un test : y renvoyer un refus alors qu'il serait gardé ferait boucler) |
| il **sait pourquoi** | un message, dans l'idiome du socle (`Toastr`), avec sa clé de traduction en FR et EN |
| la page précédente vient de la **session** | et non de l'en-tête `Referer` — voir ci-dessous |

### ⚠️ Le piège évité : `url()->previous()` lit d'abord le `Referer`

Contrairement à ce que son nom suggère, `UrlGenerator::previous()` lit **l'en-tête
`Referer`** et ne retombe sur la session qu'à défaut :

```php
$referrer = $this->request->headers->get('referer');
$url = $referrer ? $this->to($referrer) : $this->getPreviousUrlFromSession();
```

S'en servir aurait fait de cette garde une **redirection ouverte** : une page tierce
pointant vers une route refusée aurait renvoyé le navigateur chez elle. La page
précédente est donc lue dans la session — écrite par le socle à partir des navigations
réellement servies — et l'hôte est vérifié malgré tout.

C'est le seul endroit du chantier où le piège a été vu **avant** d'être écrit, et
uniquement parce que la question « d'où vient cette donnée ? » a été posée au
framework plutôt qu'au nom de la méthode.

### Le code HTTP : 302, et c'est un choix

`resources/views/errors/403.blade.php` existe : répondre 403 à une navigation était
possible. Ce n'est pas ce qui est fait, et la raison est inscrite dans le test : une
page d'erreur ferait perdre à l'opérateur son contexte de travail, alors qu'un refus
de droit dans un back-office n'est pas une impasse — c'est « pas ici ». **La machine
reçoit 403** (cas AJAX, S36), **l'humain reçoit son écran et un message**.

### La leçon : trois sabotages verts, trois gardes inatteignables

Onze sabotages, trois verts au premier tour — et les trois interrogeaient le test.

1. **Retirer la clé de traduction** laissait tout au vert : `__()` replie sur la
   **clé elle-même**, et les deux côtés de ma comparaison repliaient de la même
   façon. L'opérateur aurait lu « message.permission_denied » à l'écran. Le test
   exige maintenant une phrase, et la présence de la clé dans les deux langues.
2. **Le contrôle d'hôte** et **le garde anti-boucle** n'étaient atteignables par
   aucun chemin normal : la page précédente de la session est toujours du bon hôte
   et jamais l'URL refusée, puisque c'est le socle qui l'écrit. Les exercer demande
   de **semer la session à la main** — ce qui est légitime, une valeur périmée ou
   empoisonnée étant exactement ce contre quoi ces gardes existent.

Quatrième fois du chantier qu'un garde inatteignable fait passer un sabotage (après
S31, S34 et S36). La règle tient : **un sabotage vert interroge le test, il ne
disculpe pas le code.**

### Couverture

`WebPermissionGuardTest` passe de 23 à **29 tests, 63 assertions** : les acquis de S36
plus la destination du refus (jamais la page publique, retour sur la page précédente,
repli sur un tableau de bord non gardé), le message et ses deux traductions, le refus
de suivre un `Referer` étranger, et les deux valeurs de session aberrantes.

**Onze sabotages, onze morsures** après correction des trois tests.

Suite complète : **758 tests, 45 052 assertions, vert.**
## ✅ S40 — les aides AJAX des colis (2026-09-20, 1ʳᵉ passe sur l'arriéré de S38)

### Où l'arriéré se creuse

Le filet de S38 a ouvert avec **90 routes** à l'arriéré. Elles ne sont pas
réparties au hasard : **27** sont sous `POST admin/parcel/`, et elles ne se
valent pas. Les transitions de statut ont été gardées au fil des dix passes
précédentes ; les **aides AJAX** — listes déroulantes, recherches, bascules —
n'ont jamais été touchées par aucune passe. C'est là que la lecture nue a
survécu, et c'est ce sous-groupe que celle-ci prouve.

### Sept points, dont un dans le panneau marchand

| Route | Ce qu'un identifiant étranger obtenait |
|---|---|
| `POST admin/parcel/merchant/shops` | nom, téléphone, adresse des boutiques de n'importe quel marchand |
| `POST merchant/parcel/merchant/shops` | idem, entre marchands du **même** transporteur |
| `POST admin/parcel/priority/update` | une **écriture** : la priorité du colis d'en face |
| `POST admin/parcel/received-warehouse-hub-selected` | les entrepôts de **tous** les transporteurs |
| `POST admin/parcel/transfer-hub` | l'évènement du colis d'en face **et** tous les entrepôts |
| `POST admin/transertohub-selected-hub` | le nom de l'entrepôt d'un colis étranger |
| `POST admin/parcel/deliveryman/search` | le **nom** du livreur affecté au colis d'en face |

### Deux modèles qui ne peuvent pas porter `companywise()`

`merchant_shops` n'a **aucune colonne `company_id`** — c'est le marchand qui
rattache la boutique à une société. `parcel_events` non plus — c'est le colis.
Un `companywise()` sur ces modèles n'existe pas et *ne peut pas* exister : le
périmètre passe par une jointure.

C'est ce qui rendait ces points faciles à manquer. Chercher l'absence d'un
`companywise()` — le réflexe des dix passes précédentes — ne les désigne pas.

### Le périmètre du jumeau n'est pas celui de l'original

Au back-office, l'agent voit les boutiques de sa société. Dans le panneau
marchand, s'arrêter à la société laisserait un marchand lire celles de son
**voisin chez le même transporteur**. L'identifiant envoyé par le formulaire
n'y décide donc de rien : c'est la session — la règle que
`ShopsRepository::ownedShops()` pose déjà depuis S17.

C'est précisément l'écart qu'une correction mécanique — le même `companywise()`
partout — aurait introduit, et le test l'inscrit dans les deux sens.

### Un défaut du socle que la garde a mis au jour

`merchantShops` empilait le résultat de `first()` **sans le vérifier**, et
`shops.blade.php` déréférence `$shop->id`. Un marchand sans boutique par défaut
rendait donc **déjà** un 500 ; avec le périmètre, c'était le cas de *tout*
identifiant étranger — un refus annoncé comme une panne, la famille que S37
vient de documenter. Corrigé dans les deux contrôleurs.

### La leçon de ce lot : un test vert qui ne prouvait rien

`test_the_transfer_hub_helper_leaks_neither_the_event_nor_the_other_hubs` est
resté **vert au sabotage**. La raison est instructive : avec le défaut présent,
l'aide lisait bien l'évènement du colis d'en face — et excluait donc l'entrepôt
du voisin de sa liste, par son propre `whereNotIn`. Mon assertion portait
exactement sur l'entrepôt que le défaut écartait par coïncidence.

Le test porte maintenant sur un **second** entrepôt du voisin, que rien
n'exclut. Sans le sabotage, cette passe aurait livré une preuve creuse — et
c'est la deuxième fois du chantier qu'un vert au sabotage révèle non pas un
correctif inutile, mais une **assertion mal visée**.

### Une garde d'accès manquante, signalée et non corrigée

Cinq de ces routes ne portent **aucun `hasPermission`** — dont
`parcel/priority/update`, qui **écrit**. Un agent sans aucun droit sur les colis
peut en changer la priorité. Ce n'est pas l'axe de ce lot (l'isolation entre
sociétés, désormais fermée sur ces routes) mais celui de **S36**, dont la règle
est de **mesurer qui perd l'accès, rôle par rôle, avant de poser une garde**.
C'est une passe à part entière, et elle n'est pas faite.

### Couverture

`tests/Feature/ParcelAjaxHelpersScopeTest.php` — 12 cas, 31 assertions, **par
appel HTTP** : ces aides vivent en AJAX, et c'est la pile complète — hôte de
locataire, authentification, abonnement, permissions — qui doit laisser passer
la requête jusqu'à elles. Chaque cas porte son contrôle négatif.

**Sabotage : 12 rouges sur 12** après correction de l'assertion mal visée.

Filet de S38 : arriéré **90 → 83**.

## ✅ S41 — la séparation des trois panneaux : un type de compte, pas une permission (2026-09-20)

### Le constat

`routes/web.php` place `admin/*` et `merchant/*` dans le **même** groupe
`auth` + `subscriptionCheck`. Aucune garde sur `user_type`, ni sur l'un ni sur
l'autre : le **seul** séparateur entre le back-office et le panneau marchand
était le `hasPermission` posé route par route.

Là où il manque, la porte était donc ouverte à **tout compte authentifié**.
Mesuré sur 576 routes sous `auth` : **408 gardées, 168 nues, dont 38 écritures**
dans `admin/`.

### Ce que ça donnait, par appel HTTP, avant correctif

| Compte | `POST admin/parcel/priority/update` | `GET admin/payout` | `POST admin/merchant/search` |
|---|---|---|---|
| agent **sans aucun droit** | 200 | 200 | 200 |
| **marchand** | **200** — une écriture | **200** | **200** |
| **livreur** | **200** | 500 | **200** |

`admin/payout` est la page de **paiement aux marchands** : un marchand la lisait.
`parcel/priority/update` **écrit** : un marchand changeait la priorité d'un colis.
Et dans l'autre sens, un agent ou un livreur atteignait le panneau marchand, où
il récoltait un **500** — un refus annoncé comme une panne, la famille de S37,
fermée ici par la même garde.

### ⚠️ Le même trou était déjà fermé sur l'API, en S5

`UserTypeMiddleware` existe depuis S5, et son docbloc décrit **ce symptôme
exact** : « le socle plaçait `deliveryman/*` et les routes marchand dans le même
groupe `auth:sanctum`, sans garde sur `user_type` […] le contrôleur marchand
répondait alors 500, pas un refus ». Le web est resté ouvert pendant tout le
chantier.

C'est la leçon la plus coûteuse de la campagne : **huit passes d'isolation sont
passées à côté**, parce qu'elles cherchaient un `company_id` manquant, pas un
`user_type` manquant. Le cloisonnement entre sociétés et le cloisonnement entre
panneaux sont deux axes différents, et fermer le premier ne dit rien du second.

### Pourquoi une classe à part et non `userType` réutilisé

`UserTypeMiddleware` vérifie en second l'**ability du jeton** (`tokenCan`) et
répond dans l'enveloppe JSON de l'API. Une session web n'a pas de jeton, et un
humain n'attend pas une enveloppe. Surtout, ses méthodes `scopeOf()` et
`abilitiesFor()` servent à **émettre** les abilities à la connexion : y ajouter un
type « back-office » changerait les jetons émis aux administrateurs. Le concept
est partagé, l'implémentation ne peut pas l'être — et c'est écrit dans les deux
classes.

### Le préfixe d'URI dit le panneau, pas le nom de route

Une cinquantaine de routes **nommées** `merchant.*` vivent sous `admin/` : ce
sont les écrans du back-office *à propos* des marchands
(`merchant.shops.index` = `admin/merchant/{id}/shops/index`). Le nom trompe, le
préfixe dit vrai — d'où une garde posée sur le **groupe**.

### Qui perd l'accès : personne, et c'est mesuré

La règle de S36 appliquée avant de poser la garde :

- les comptes du back-office sont **ADMIN et SUPER_ADMIN**, et rien d'autre —
  `UserType::HUB` et `UserType::INCHARGE` ne sont pas des types de compte mais
  des **marqueurs** posés sur des lignes de relevé et sur `created_by` ; un chef
  de hub est un ADMIN avec `hub_id` ;
- le **livreur n'a aucun écran web** : aucun préfixe `deliveryman` dans
  `routes/web.php` — il passe par l'API ;
- **aucune vue marchande n'appelle une URL `admin/`**, et aucune vue du
  back-office n'appelle une URL `merchant/` ; les routes nommées qu'utilisent les
  vues marchandes résolvent toutes sous `merchant/`, sauf `dashboard.index` et
  `logout` ;
- **aucun rôle de locataire ne porte `plans_*` ni `company_*`** : réserver le
  panneau plateforme au super-administrateur ne retire rien ;
- il n'existe **aucune usurpation d'identité** dans le dépôt (`loginAs`,
  `impersonate`, `Auth::loginUsingId` : zéro occurrence).

### `GET /dashboard` est partagé, et doit le rester

Il est déclaré **hors** des deux préfixes, et c'est voulu :
`DashbordController::index()` branche sur `user_type` et rend
`backend.merchant_panel.dashboard` à un marchand. Une garde de panneau posée
dessus casserait le panneau marchand. Un test inscrit ce partage pour que
personne ne le « corrige ».

### Ce que le filet a trouvé que je n'avais pas vu

`GET admin/subscription/history` est déclarée **hors** du groupe `admin`
(`routes/web.php`), parce qu'elle est aussi hors de `subscriptionCheck` — pour
rester consultable sans abonnement en cours. Le filet l'a désignée au premier
passage ; la garde est posée sur la route, sans la déplacer.

### ⚠️ La limite du filet, mesurée

`routes/superadmin.php` déclare lui aussi un groupe `admin/` (**61 URI**) et le
panneau `super-admin/`. Ses 61 URI sont **toutes** redéclarées dans
`routes/web.php` — vérifié une par une — et `MountsTenantRoutes` réenregistre
`web.php` **après** le chargement de `superadmin.php`. La collection étant
indexée par méthode + URI, les versions de `web.php` écrasent les autres : le
filet est **structurellement aveugle** à ce fichier, et un sabotage l'a prouvé en
restant vert. Le contrôle s'y fait donc sur la **déclaration**, ce qui est plus
faible qu'un appel HTTP. Même limite que celle déjà documentée en S36.

### La leçon : deux sabotages verts, deux lignes qui ne prouvaient rien

1. **Un `abort_if(blank($autorises), 403)` explicite était REDONDANT.** Le
   retirer ne changeait aucun verdict : avec une liste d'autorisés vide,
   `in_array` est déjà toujours faux. Une ligne qu'aucun test ne peut distinguer
   n'a pas sa place — elle est retirée, et le sabotage a été **remplacé** par
   celui qui vise le vrai risque : rendre le middleware **ouvert** par défaut sur
   un panneau inconnu. Il mord.
2. **Le `true` de `in_array` n'est pas prouvable.** `user_type` est une colonne
   entière ; la comparaison relâchée rend le même verdict sur tous les cas
   exercés. Le drapeau reste par hygiène, contre un changement de cast futur, et
   c'est dit — pas présenté comme prouvé.

### Une affirmation de S37, rectifiée

Le docbloc de `ProfileAccessTest` justifiait son 404 par « ce panneau ne porte
aucune garde de type d'utilisateur ». S41 la lui a donnée : un non-marchand est
maintenant arrêté à la **frontière** et lit **403**. Le 404 du contrôleur reste —
il garde le compte **de type marchand** dont la ligne `merchants` manque — et les
deux cas sont désormais prouvés séparément. La rectification est posée **à côté**
de la phrase d'origine, pas à sa place.

### Couverture

`tests/Feature/WebPanelSeparationTest.php` — **15 cas, 29 assertions**, dont le
filet d'énumération (toute route montée sous un panneau porte sa garde : **552**
routes comptées, avec un **plancher** et non un compte exact — la leçon de S33),
les preuves HTTP dans les deux sens, les contrôles négatifs, le partage de
`/dashboard`, et le test qui inscrit la mesure des trois panneaux.

**Sabotage : 11 morsures sur 12**, le vert étant documenté ci-dessus.

Suite complète : **808 tests, 45 237 assertions**, vert.

### Ce que ce lot ne fait pas

- **Quel rôle** parmi les administrateurs peut appeler les 38 écritures nues de
  `admin/` reste ouvert : c'est l'axe de S36, signalé par S40, et il demande de
  mesurer rôle par rôle. Ce lot ferme la porte du **bâtiment**, pas celle du
  bureau.
- **8 routes de démo du thème** répondent 500 pour tous les types :
  `dashboard-finance`, `dashboard-influencer`, `dashboard-sales`,
  `ecommerce-product`, `ecommerce-product-checkout`, `ecommerce-product-single`,
  `influencer-finder`, `influencer-profile`. Mêmes symptômes que les 5 routes
  mortes `sms-settings` de S35.
- `POST search-charts` répond 200 à tout compte ; `POST store-token` répond 410 ;
  `GET subscription` répond 500 pour les trois types.
- Les vues marchandes référencent `route('aamarpay.payment')` et
  `route('bkash.redirect')`, **introuvables** dans la table des routes.
- `GET /dashboard` répond **500 à un livreur** — inchangé par ce lot, et de la
  famille de S37.

## ✅ S42 — la garde des aides colis, et la mesure qui a corrigé le lot (2026-09-22)

### D'où vient ce lot

S40 a fermé l'isolation des aides AJAX des colis entre sociétés, et **signalé**
qu'aucune ne portait de garde de droit. S41 a depuis fermé l'accès des marchands
et des livreurs, **par type de compte**. Restait le cas que ni l'un ni l'autre ne
couvre : l'**agent du back-office sans droit sur les colis**. Un `user_type` ne
le filtre pas, et l'isolation entre sociétés ne dit rien de lui.

### La mesure, avant de poser quoi que ce soit

La règle de S36, appliquée. Droits colis par rôle, mesurés sur la base semée :

| Rôle | read | create | update | delete | status_update |
|---|---|---|---|---|---|
| Admin | oui | oui | oui | oui | oui |
| User | oui | — | — | — | — |
| **Chef de hub** (jeu **fixe** de `hubPermissions()`) | oui | — | — | — | — |

### Six gardes posées

| Route | Droit | Qui perd l'accès |
|---|---|---|
| `parcel/priority/update` | `parcel_update` | `User` et chef de hub — une **écriture** qu'ils n'auraient jamais dû avoir |
| `parcel/quote` | `parcel_create\|parcel_update` | personne : les 3 vues appelantes sont déjà gardées ainsi |
| `parcel/delivery-category` | `parcel_create\|parcel_update` | idem |
| `parcel/received-warehouse-hub-selected` | `parcel_status_update` | **personne** — appelée depuis le menu de statut, déjà masqué derrière ce droit |
| `transertohub-selected-hub` | `parcel_status_update` | personne, même raison |
| `parcel/recived-by-hub/search` | `parcel_status_update` | `User` et chef de hub perdent une recherche dans un écran de masse dont **l'action** leur était déjà refusée |

### ⚠️ Deux gardes retirées du lot **par la mesure**

`parcel/merchant` et `parcel/hub` ressemblent à des sélecteurs du formulaire
colis. Ils ne le sont pas : ils sont appelés depuis **26 et 5 vues** — salaires,
paie, panneau de hub, revenus, dépenses, rapports, versements, portefeuille.

Un droit « colis » y aurait cassé, entre autres, l'écran d'**encaissement du chef
de hub** — un écran dont il détient le droit. C'est exactement le genre de
régression que la règle de S36 existe pour empêcher, et c'est la première fois du
chantier qu'elle en empêche une.

Le détour instructif : ma première résolution des appelants cherchait
`route('parcel.merchant')` alors que le nom réel est `parcel.merchant.get`. Elle
a rendu **zéro appelant**, et j'ai failli conclure à des routes mortes. C'est en
vérifiant le nom exact dans `routes/web.php` que les 26 vues sont apparues. Une
mesure qui rend « rien » mérite qu'on vérifie d'abord l'instrument.

### Les deux sélecteurs partagés restent nus, et pourquoi

`parcel/deliveryman/search` (19 vues) et `parcel/merchant/shops` (12 vues)
traversent **huit droits distincts** chacun. La liste `a|b|c` de S36 saurait les
porter — mais plusieurs de leurs écrans appelants n'ont **eux-mêmes aucune
garde** (`parcel/filter`, `parcel/specific/search`, `payout/merchant/payout`…).

On ne peut pas dériver un droit d'un écran qui n'en a pas : la liste serait
incomplète, et une liste incomplète **refuse** un compte légitime. Ces deux
aides ne peuvent donc être gardées qu'**après** les écrans qui les appellent.
C'est une dépendance, pas un oubli.

### La paire sœur, poussée jusqu'à la vue

La bascule de priorité était le **seul** contrôle de la liste des colis rendu
sans condition, alors que modifier et supprimer sont gardés deux lignes plus haut
dans le même tableau. Garder la route sans masquer le contrôle aurait seulement
transformé l'écriture en erreur visible. La règle des paires sœurs vaut donc
aussi **entre une route et le contrôle qui l'appelle**.

### Un écran marchand déjà cassé, trouvé en chemin

`merchant_panel/parcel/edit` et `duplicate` appelaient la route **du
back-office** pour leur sélecteur de poids, là où `create` appelle sa jumelle
marchand. Depuis la garde de panneau de S41, un marchand y recevait **403** et le
sélecteur ne se remplissait plus. Corrigé, et un invariant l'inscrit : aucune vue
du panneau marchand ne référence cette route du back-office.

### La leçon de ce lot : un contrôle négatif qui avait raison

`test_the_priority_toggle_is_hidden_from_a_read_only_account` a échoué sur sa
**seconde** moitié : le compte qui *a* le droit ne voyait pas la bascule non
plus. La liste des colis était vide — la fixture ne produisait aucune ligne. La
première assertion passait donc au vert **sans rien mesurer**.

C'est la troisième fois du chantier qu'une preuve creuse est rattrapée, et la
première par un contrôle négatif plutôt que par un sabotage. Le test crée
maintenant son colis et **vérifie qu'il apparaît** avant de conclure.

### Couverture

Les six routes rejoignent le fournisseur de `WebPermissionGuardTest`, qui les
exerce dans les deux sens avec son discriminateur éprouvé — le **message flashé**
de la garde, que ni une validation échouée ni un `back()` n'écrivent. Plus deux
tests propres à ce lot : la bascule masquée, et l'invariant des vues marchandes.

**Sabotage : six gardes retirées, six rouges ciblés**, plus le contrôle de vue.

Suite complète : **809 tests, 45 237 assertions**.

## ✅ S43 — la chaîne des écrans nus, et les deux sélecteurs enfin gardés (2026-09-22)

### La dépendance que S42 avait nommée

S42 a laissé `parcel/deliveryman/search` et `parcel/merchant/shops` sans garde,
avec sa raison : ils sont appelés depuis 19 et 12 vues, et **plusieurs de leurs
écrans appelants n'avaient eux-mêmes aucune garde**. On ne dérive pas un droit
d'un écran qui n'en a pas — la liste aurait été incomplète, et une liste
incomplète **refuse un compte légitime**.

Ce lot remonte la chaîne, la ferme, puis garde les sélecteurs.

### Le relevé : quatre écrans, pas quarante

Le chantier annoncé était « 169 routes nues, dont 87 écritures ». En résolvant
chaque vue appelante vers la route qui la rend, le blocage se réduit à **quatre
routes** — tout le reste était déjà gardé :

| Écran nu | Droit posé | Pourquoi celui-là |
|---|---|---|
| `GET parcel/filter` | `parcel_read` | rend **la même vue** que `parcel/index`, qui le porte déjà |
| `GET parcel/specific/search` | `parcel_read` | idem — sœurs au sens de S36 |
| `GET payout/` | `payout_read` | le droit existait déjà au catalogue, inutilisé |
| `GET payout/merchant/payout` | `payout_read` | idem |

### Qui perd l'accès, mesuré

| Droit | Admin | User | Chef de hub |
|---|---|---|---|
| `parcel_read` | oui | oui | **oui** |
| `payout_read` | oui | oui | **—** |

Les deux sœurs de la liste des colis ne coûtent donc **rien** : les trois rôles
portent `parcel_read`. Le chef de hub perd l'écran des versements aux marchands,
et c'est l'intention — il n'y a pas affaire.

⚠️ Et un fait contre-intuitif, relevé au passage :
`cash_received_from_delivery_man_*` appartient au rôle **User et au chef de hub,
mais PAS au rôle Admin**. C'est ce qui rendait la régression de S42 possible, et
c'est ce que l'invariant ci-dessous protège.

### Un second défaut sur le même écran, trouvé en le gardant

`PayoutController` lisait ses deux écrans **sans périmètre de société** — alors
que `MerchantOnlinePaymentReceived` **porte** `scopeCompanywise()` et que la
table a sa colonne `company_id`. Le périmètre existait ; le contrôleur ne s'en
servait pas.

On rendait la liste des encaissements en ligne de **tous les transporteurs** —
montants, comptes bancaires et marchands compris. Le second écran est pire dans
sa forme : il filtre sur un `merchant_id` venu de l'URL, donc changer ce numéro
suffisait à lire les versements du marchand d'en face.

⚠️ **La garde de droit et le périmètre de société sont deux axes**, et fermer
l'un ne dit rien de l'autre. S41 l'avait tiré dans un sens ; ce lot le vérifie
dans l'autre — c'est en venant poser une garde d'accès qu'une fuite d'isolation
est apparue.

### Les deux sélecteurs, et pourquoi une liste longue est tenable

La chaîne fermée, les listes se dérivent :

- `parcel/deliveryman/search` → **8 droits**
- `parcel/merchant/shops` → **10 droits**

Le danger d'une telle liste n'est pas sa longueur, c'est qu'elle se **périme en
silence**. Un écran ajouté demain qui appelle le sélecteur sans que son droit y
figure ne produira aucune erreur visible : la requête AJAX répondra 403 et la
liste déroulante restera **vide**. Personne ne le remarquera avant qu'un
opérateur ne se plaigne.

D'où `SharedPickerGuardTest`, qui ne relit pas une liste écrite à la main mais la
**recalcule depuis les vues** : il relève les vues appelantes, remonte à la route
qui rend chacune, et exige que son droit figure dans la garde du sélecteur.

### ⚠️ Un test qui vérifie son propre instrument

En écrivant S42 j'ai cherché `route('parcel.merchant')` quand le nom réel était
`parcel.merchant.get`. Le relevé a rendu **zéro appelant**, et j'ai failli
conclure à une route morte — donc à la supprimer.

L'invariant porte donc un **témoin d'instrument** : le nombre de vues appelantes
relevé au moment de la garde (19 et 12). S'il s'effondre, le test échoue
bruyamment au lieu de passer au vert. Un relevé qui ne trouve rien n'est pas une
bonne nouvelle.

### Un contrôle négatif d'un autre lot, adapté

`WebPanelSeparationTest::test_the_back_office_still_serves_its_own_agent`
(S41) affirmait que `/admin/payout` répond 200 à un agent **sans aucun droit**.
C'était vrai — précisément parce que la route était nue. La garde change cette
prémisse ; le test reçoit maintenant `payout_read` et continue de dire ce qu'il
a toujours voulu dire : la garde de **panneau** ne refuse pas l'agent du
back-office.

### Couverture

- Les six nouvelles gardes rejoignent le fournisseur de `WebPermissionGuardTest`
  (**55 tests**), exercées dans les deux sens.
- `SharedPickerGuardTest` — 3 tests, l'invariant et son témoin d'instrument.
- `PayoutScreenScopeTest` — 2 tests, chacun avec son contrôle négatif.

**Sabotage** : un droit retiré de la garde d'un sélecteur → l'invariant mord et
**nomme les deux écrans du chef de hub**, exactement la régression que S42 avait
évitée. Périmètre des versements retiré → deux rouges ciblés.

Suite complète : **840 tests, 45 287 assertions**.

---

> ⚠️ **Quatrième collision de numéro — et une DOUBLE collision sur le même numéro.**
> Ce lot-ci s'est d'abord appelé **S42**, en même temps que « la garde des aides colis »
> ([#114](https://github.com/malcomx2022/beninlink/pull/114)). Celui-là étant arrivé le
> premier sur `main`, ce lot est devenu **S43** — et pendant ce temps « la chaîne des
> écrans nus » ([#115](https://github.com/malcomx2022/beninlink/pull/115)) prenait
> **aussi** S43 et arrivait sur `main` avant lui. Il devient donc **S44**.
>
> La règle ne change pas, elle a simplement servi deux fois de suite : **le lot fusionné
> le premier garde le numéro.** Ce qu'elle ne suffit plus à faire, c'est prévenir la
> collision : quatre fois en cinq lots, deux sessions ont choisi le même numéro parce
> qu'aucune ne voit la cartographie de l'autre avant de commiter. Le numéro se choisit
> **au moment de la fusion**, pas au moment d'écrire.
>
> ⚠️ **Ce qui distingue ces collisions des deux premières** : les numéros n'étaient pas le
> seul chevauchement. Avec **S42**, six routes étaient gardées par les deux lots — trois
> du même droit, deux divergentes, et c'est la mesure la plus serrée qui a été retenue
> (`parcel_status_update` plutôt que `parcel_read` sur `received-warehouse-hub-selected`
> et `transertohub-selected-hub`, parce qu'elle avait identifié l'écran appelant exact).
> **Le correctif le plus serré gagne, même quand il n'est pas le sien.**
>
> Avec **S43**, le recoupement a porté sur l'**arriéré** : **cinq** routes que ce lot-ci
> laissait en attente — `parcel/filter`, `payout`, `payout/merchant/payout`,
> `parcel/deliveryman/search`, `parcel/merchant/shops` — y ont été gardées. C'est le
> troisième cliquet de ce filet (`test_the_backlog_holds_no_route_that_is_already_guarded`)
> qui l'a exigé, et la deuxième fois qu'il sert : l'arriéré de ce lot passe de **27 à 22**.
>
> Ses propres gardes, elles, restent **27** — celles de S43 ne sont pas les siennes. Et le
> détail mérite d'être noté, parce qu'il valide les deux lots à la fois : ce lot-ci laissait
> les deux sélecteurs partagés à l'arriéré en disant qu'il fallait « lire leurs appelants un
> par un » ; S43 l'a fait par l'autre bout, en gardant d'abord les écrans appelants qui
> n'avaient aucune garde, ce qui a rendu leur liste de droits **dérivable** (8 et 10
> droits). Aucun des deux n'aurait pu le faire seul.

## ✅ S44 — la porte du bureau : les 60 routes du back-office sans garde de droit (2026-09-20)

### Le constat

S41 a fermé la porte du **bâtiment** : `admin/*` exige désormais un compte de type
back-office. Restait celle du **bureau**. Sur les **447 routes `admin/*`** montées
sous `auth`, **60 ne portaient aucun `hasPermission`** : tout opérateur du
back-office les atteignait, quel que soit son rôle — y compris un compte **sans un
seul droit**, vérifié par appel HTTP.

**60 → 22** une fois ce lot fusionné avec S42 puis S43 — le décompte à jour, et il
ne se lit plus pour ce lot seul :

| | |
|---|---|
| **27 gardes prouvées par ce lot** | 26 posées ici, plus `parcel/recived-by-hub/search` que S42 a gardée et que mon cliquet a fait sortir de l'arriéré |
| **5 gardes de S43** | `payout`, `payout/merchant/payout`, `parcel/filter`, `parcel/deliveryman/search`, `parcel/merchant/shops` — le cliquet les a fait sortir aussi |
| **6 exemptions** motivées | |
| **22 lignes d'arriéré** | plafond ramené de 28 à 22 en deux fusions |

⚠️ **Le troisième cliquet a servi deux fois de suite**, et c'est sa raison d'être :
à chaque fusion il a exigé que les routes désormais gardées **par le lot voisin**
sortent de ma liste d'attente. Sans lui le plafond aurait menti deux fois.

### Comment se mesure le droit d'une aide AJAX

Il n'est pas déductible de son nom : c'est celui des **écrans qui l'appellent**
(règle de S36, `hasPermission:a|b|c`). La chaîne se remonte en trois sauts —
route nue → fichier appelant → écran → droit de l'écran — et le premier saut est
le piège.

⚠️ **Les appelants vivent dans `public/backend/js/**/custom.js`, pas dans les
vues.** Une recherche limitée aux `.blade.php` déclare « sans appelant » **onze**
routes qui sont en réalité des aides AJAX bien vivantes — S40 vient d'en prouver
les fuites. C'est la raison pour laquelle ces 60 routes avaient survécu à S36 :
chercher l'appelant au bon endroit n'est pas évident.

### ⚠️ Pourquoi c'est un arriéré et non un balayage

Chercher l'URI **courte** (`parcel/filter`) attrape aussi `merchant/parcel/filter` :
la liste d'écrans se pollue de vues du **panneau marchand**, et le jeu de droits
déduit s'élargit à tort. `POST admin/parcel/merchant` en ressort avec **19
droits** — une garde à 19 droits ne garde presque rien.

La déduction mécanique donne donc une **piste, pas un verdict**. Une route reste à
l'arriéré tant que ses appelants n'ont pas été lus un par un. C'est exactement la
discipline de S28 → S35 (171 → 0 en dix passes).

### La trouvaille qui a recadré le lot

Le super-administrateur semé (**70 droits**, issus de `SuperAdminPermission` — une
**autre table** que le catalogue locataire) est **déjà refusé** sur une route
gardée du back-office locataire (`admin/parcel/index`, `hasPermission:parcel_read`)
et servi sur les nues. Or `backend/super-admin/partials/sidebar.blade.php` lie
`subscribe.index`.

Poser un droit de locataire sur cette route **casserait le menu du
super-administrateur**. Elle est donc exemptée, avec ce motif : la garde qui
convient là est celle du **type de compte** (S41), pas celle du droit. C'est ce
qui explique, au moins en partie, pourquoi ces routes sont restées nues — leur
garde n'est pas sur la dimension qu'on croit.

### La décision la plus coûteuse, et sa mesure

`POST admin/parcel/priority/update` est une **écriture**. Son écran appelant —
l'index des colis — n'exige que `parcel_read`, que portent le rôle **Admin**, le
rôle **User** *et* le chef de hub. La garder par `parcel_update`, que **seul le
rôle Admin** porte, retire la bascule au rôle User et au chef de hub.

C'est voulu, et c'est la même décision que S36 a prise pour le clone de colis :
**ce que le rôle User perd ici, il n'aurait jamais dû l'avoir** — un droit de
lecture ne fait pas écrire. Un test dédié inscrit la mesure.

Les 25 autres gardes **ne retirent l'accès à personne** : leurs écrans appelants
exigent déjà exactement ces droits, donc un compte qui ne les porte pas n'atteint
pas l'écran qui appelle l'aide.

### Le discriminateur, repris de S39

Le contrôle négatif ne compare pas à **200** mais au **message flashé** : un refus
de droit se reconnaît à `message.permission_denied`, et rien d'autre ne le porte.
Comparer à 200 rendrait le test faux dès qu'une de ces aides répond autre chose
sur un corps minimal — ce qui n'a rien à voir avec le droit. C'est la troisième
fois du chantier que ce discriminateur sert.

### La leçon : un sabotage vert qui était un faux sabotage

Remplacer `parcel_update` par `parcel_read` sur la bascule de priorité est resté
**vert**. La cause n'était ni le code ni le test : mon ancrage
(`hasPermission:parcel_update`) **n'était pas unique** dans `routes/web.php`, et le
remplacement a frappé une autre route du socle. Le MD5 du fichier avait bien
changé — ce qui rendait le vert crédible.

Rejoué avec l'ancrage complet (`->name('parcel.priority.status')->middleware(…)`),
après avoir **vérifié l'unicité**, il mord sur trois tests. La règle du chantier
s'enrichit : un sabotage vert interroge le test — **et d'abord l'ancrage du
sabotage lui-même**. Vérifier que le fichier a changé ne suffit pas ; il faut
vérifier qu'il a changé **là**.

### Couverture

`tests/Feature/WebAdminPermissionCoverageTest.php` — **58 cas, 118 assertions** :

- le **filet** : aucune route `admin/*` ne reste indéterminée — gardée, exemptée
  avec motif, ou à l'arriéré ;
- **trois cliquets** : l'arriéré ne grandit pas ; une ligne d'arriéré dont la route
  est désormais gardée doit être retirée ; une exemption doit désigner une route
  qui existe et reste nue ;
- les **27 gardes**, exercées par appel HTTP dans les deux sens, plus un
  test qui vérifie que **chaque** droit d'une liste `a|b|c` ouvre la route — sans
  lui, une liste dont seul le premier fonctionne passerait au vert ;
- la mesure de la bascule de priorité, inscrite.

**Sabotage : 8 morsures sur 8** (après correction de l'ancrage du deuxième).

Suite complète : **866 tests, 45 355 assertions**, vert.

### Ce que ce lot ne fait pas — l'arriéré, nommé

- **Les addons (8 routes)** : module de plateforme, aucun droit `addons_*` au
  catalogue. `admin/addons/create` n'est référencée par aucune vue.
- **Google Maps (2)** : aucun droit déduit de l'écran de réglage.
- **Abonnement et versements (4)** : `subscription/history`, `paid/invoice`,
  `payout`, `payout/merchant/payout` — à départager entre droit et type de compte,
  comme `subscribe`.
- **Deux routes non reliées** : `admin/parcel/file-export` et
  `admin/reports/mhd-pdf` — aucune vue, aucun JS ne les nomme, mais leurs méthodes
  existent et fonctionnent. Non **mortes** au sens de S35 (dont les cinq routes
  `sms-settings` n'avaient pas de méthode) : **non reliées**. Le panneau marchand,
  lui, a bien sa propre `merchant-panel.parcel.file-export`.
- **Huit aides AJAX à jeu large** (6 à 19 droits déduits) : `parcel/filter`,
  `parcel/search`, `parcel/merchant`, `parcel/merchant/shops`,
  `parcel/deliveryman/search`, `parcel/hub`, `merchant/account`,
  `merchant/search`.
- **Trois écrans en lot** et la barre de navigation : `assign-pickup/parcel/search`,
  `assign-return-to-merchant/parcel/search`, `parcel/recived-by-hub/search`,
  `todo/momal`.

## ✅ S45 — l'agent nommé : le second identifiant d'un mouvement (2026-09-23)

### Ce que S38 avait fermé, et ce qu'il avait laissé

Un changement de statut porte **deux** identifiants dans le corps de la requête,
et pas un seul : le **colis** qu'on déplace, et **l'agent ou l'entrepôt qu'on
nomme au passage**. S38 a énuméré les routes d'écriture sans paramètre d'URL,
trouvé les identifiants de colis qu'elles lisaient, et fermé cet axe-là.

Le second est resté ouvert — y compris sur **cinq chemins que S38 avait inscrits
comme *prouvés***. La preuve portait sur le colis ; elle ne disait rien du
livreur. Une route inscrite dans `PROUVEES` n'est donc pas une route close :
c'est une route close **sur l'axe que le test a mesuré**.

⚠️ C'est la troisième fois que ce projet bute sur cette forme. S41 l'a montrée
entre droit et société, S43 dans l'autre sens, S45 entre **la ressource
déplacée** et **l'agent payé pour la déplacer**. La leçon se durcit : une
surface fermée sur un axe n'est pas fermée, elle est fermée *sur un axe*.

### Le relevé : huit méthodes, deux natures de défaut

Huit méthodes du dépôt des colis lisaient `delivery_man_id` ou `hub_id` dans le
corps sans jamais vérifier de quelle société ils venaient. Les deux identifiants
ne coûtent pas la même chose, et c'est le point le plus utile du lot :

**L'axe du livreur — une écriture comptable qui franchit la frontière.**

| Méthode | État avant S45 | Ce qu'un livreur étranger obtenait |
|---|---|---|
| `returnAssignToMerchant` | S38 : colis prouvé | solde **crédité** du frais de retour + deux relevés à `company_id` = la nôtre |
| `AssignReturnToMerchantBulk` | S38 : colis prouvé | idem, **une fois par colis du lot** |
| `deliveryManAssignMultipleParcel` | S38 : colis prouvé | livreur étranger nommé sur nos colis |
| `pickupdatemanAssignedBulk` | S38 : colis prouvé | idem côté ramassage |
| `returnAssignToMerchantReschedule` | arriéré S38 | livreur étranger nommé |

Nommer le livreur d'une autre société faisait **payer son employé par nos
livres** : `DeliveryMan::find()` nu, puis `current_balance + return_charge`, puis
`DeliverymanStatement` et `CourierStatement` portant notre `company_id` et son
identifiant à lui. L'argent traverse dans les deux sens.

**L'axe de l'entrepôt — notre colis qui disparaît.**

| Méthode | État avant S45 | Ce qu'un `hub_id` étranger obtenait |
|---|---|---|
| `transfertohub` | arriéré S38 | `transfer_hub_id` vers un entrepôt d'en face |
| `transferToHubMultipleParcel` | S38 : colis prouvé | idem, sur tout le lot |
| `receivedWarehouse` | arriéré S38 | `hub_id` vers un entrepôt d'en face |

⚠️ **Et ici il faut être exact plutôt que spectaculaire.** Un `hub_id` étranger
ne montre **rien** à l'autre société : ses listes d'entrepôt croisent
`companywise()`. Il fait autre chose, et c'est pour nous — **nos** listes
croisent `companywise()` **et** `hub_id`, donc le colis en sort et devient
introuvable à nos propres agents. C'est une corruption, pas une fuite. Les deux
sont des défauts ; ce ne sont pas les mêmes, et on ne les plaide pas pareil.

### La forme du refus, reprise du socle

Dans un lot, S38 **ignore** un colis hors périmètre et poursuit la boucle :
l'identifiant est *par élément*, et un numéro glissé par erreur dans une
sélection ne doit pas faire échouer le lot entier d'un agent légitime.

L'agent, lui, vaut pour **tout le lot**. L'ignorer viderait le lot de son sens —
on ne pose pas vingt événements sans livreur. On refuse donc l'appel entier,
comme sur un chemin unitaire. C'est la règle de S38 appliquée à la bonne échelle,
pas une règle nouvelle.

Un cas à part : dans `transfertohub` et `transferToHubMultipleParcel`, le livreur
est **facultatif** — le contrôleur n'exige que `hub_id`. La garde ne s'applique
donc que lorsqu'il est nommé, et deux contrôles négatifs le vérifient : transfert
sans livreur accepté, transfert avec le nôtre accepté **et inscrit**.

### ⚠️ Deux fois, le sabotage a corrigé le lot

**Un contrôle négatif creux.** `receivedWarehouse` paie le ramasseur : sans
événement de ramassage antérieur, l'appel échoue de toute façon. Le premier test
mesurait donc un `false` déjà acquis **sans la garde**. Le colis est désormais
placé dans l'état où l'appel réussirait, avant qu'on lui oppose un entrepôt
étranger.

**Une garde verte sous sabotage.** Le sabotage des dix gardes a trouvé un
**vert** : celui du livreur étranger dans `transferToHubMultipleParcel`. Le test
du lot couvrait l'entrepôt et **jamais** le livreur — la garde était juste, sa
preuve n'existait pas. Le cas manquant a été écrit, et le sabotage repasse rouge.

C'est la troisième fois que le sabotage ne valide pas un correctif mais
**complète un test**. La discipline tient : on sabote chaque garde *séparément*,
jamais le lot en bloc, sinon un test qui en couvre une couvre visuellement les
autres.

### L'arriéré du filet S38, et comment on a décidé de le baisser

Le mouvement autorisé sur ce filet est un seul : retirer une ligne de
`HERITAGE`, l'inscrire dans `PROUVEES` **avec le test qui l'établit**, et baisser
le plafond d'autant. La tentation est de le faire au nom d'un test : « ce test
s'appelle `test_a_parcel_of_another_company_cannot_be_delivered`, donc la route
est prouvée. » Un nom n'est pas une preuve.

La décision s'est donc prise à la **mesure** : pour chaque méthode candidate, on
retire sa garde `companywise()`, on relance la suite colis, et on regarde. Rouge
= un test tient réellement le périmètre, la route peut sortir de l'arriéré.
Vert = rien ne la tient, elle y reste, quel que soit le nom des tests alentour.

C'est la même exigence que le sabotage d'un correctif, retournée vers l'arriéré :
on ne se fie pas à ce qu'un test prétend couvrir, on regarde ce qu'il casse.

### Le relevé, et les trois fois où il s'est trompé

Dix-huit méthodes candidates, chacune privée de sa garde puis remise à
l'épreuve. Premier relevé : **dix-huit rouges**. Un score parfait aurait dû
alerter tout de suite — il n'a alerté qu'après coup, quand la vérification
d'attribution a commencé à le contredire.

| Ce que le relevé disait | Ce que la vérification a trouvé |
|---|---|
| `returnReceivedByMerchant` prouvée | **faux rouge** — aucun des six fichiers ne la tient |
| `parcelPartialDeliveredCancel` prouvée | **faux rouge** — idem |
| `receivedWarehouse` prouvée par `ParcelLifecycleTest` | **vrai rouge, mauvaise attribution** — ce fichier ne la tient pas |

Trois erreurs sur dix-huit. Le réflexe a été de vérifier **l'instrument avant la
conclusion** : la suite colis lancée sans aucun sabotage est verte (89 tests,
332 assertions), donc l'outil est sain et les rouges venaient bien des gardes
retirées. Les deux qui ne se rejouent pas restent donc à l'arriéré : la garde
existe dans le code, mais **rien ne la retient**, et c'est exactement ce que
l'arriéré désigne — « on ne sait pas », pas « c'est cassé ».

### ⚠️ Le troisième cas : une assertion creuse dans un test existant

`ParcelLifecycleTest::test_no_step_touches_a_parcel_of_another_company` énumère
neuf étapes et les oppose au colis d'une autre société. Pour `receivedWarehouse`,
son `assertFalse` **passe sans la garde** : la réception paie le ramasseur, donc
sans la garde elle irait chercher l'événement de ramassage du colis étranger, ne
le trouverait pas, et échouerait sur un `null`. Le faux était acquis par accident
de fixture, pas par périmètre.

C'est la **deuxième fois dans ce seul lot** que cette forme apparaît — la
première dans mon propre test, corrigée avant commit. La leçon se précise :

> Une assertion négative sur une étape qui a des **préconditions** ne prouve rien
> tant que la ressource d'en face ne les remplit pas. Il faut lui donner de quoi
> réussir avant de lui opposer la garde.

`ParcelAgentScopeTest` tient désormais cette étape pour de bon : le colis
étranger **porte** son événement de ramassage, donc sans la garde l'appel
réussirait. Sabotage → rouge, et rouge sur ce test-là précisément.

### L'arriéré du filet S38 : 83 → 67

Seize routes sortent, chacune avec le fichier **mesuré** qui la tient :

| Routes | Prouvées par |
|---|---|
| ramassage assigné · reprogrammé · reçu · reçu par hub · livreur assigné · reprogrammé · retour au courrier · transfert vers hub | `ParcelLifecycleTest` |
| réception entrepôt · reprogrammation du retour au marchand | `ParcelAgentScopeTest` |
| livré | `DeliveryAccountingTest` |
| livraison partielle | `PartialDeliveryAccountingTest` |
| annulation : livraison · entrepôt · affectation de retour · réception du retour | `DeliveryCancellationAccountingTest` |

Deux restent, nommément : `POST admin/parcel/return-received-by-merchant` et
`POST admin/parcel/partial-delivered/cancel`. Leur garde est dans le code ; aucun
test ne tombe quand on la retire.

⚠️ Et la colonne « prouvée par » porte désormais son avertissement dans le filet
lui-même : elle nomme le test qui tient l'identifiant de **colis**, et ne dit
rien du second. C'est ce lot qui a rendu cet avertissement nécessaire.

### La passe suivante, nommée

Le même relevé, poussé au-delà des mouvements de statut, désigne la création et
la modification d'un colis : `store`, `duplicateStore` et `update` lisent
`shop_id`, `zone_id`, `category_id`, `packaging_id`, `delivery_type_id`,
`delay_id` et `priority_id` en ne gardant que `Merchant`. Le cas le plus net est
`shop_id` : la table `merchant_shops` ne porte **aucune** colonne `company_id`
(constat de S26), donc son périmètre ne peut passer que par le marchand.

**Vérification** : cliquet abaissé d'un cran → mord et nomme l'écart ; une route
retirée de `PROUVEES` → mord et la nomme. Les dix gardes du lot sabotées une à
une → dix rouges.

## ✅ S46 — les catalogues d'un colis : le troisième identifiant (2026-09-23)

### Ce que le relevé de S45 annonçait, et ce que la mesure a trouvé

S38 a fermé l'identifiant du **colis**. S45 celui de l'**agent** — le livreur ou
l'entrepôt nommé au passage. La création d'un colis en porte une troisième
famille, plus nombreuse : les **catalogues** qu'on lui applique.

La cartographie de S45 nommait la passe suivante ainsi : « `store`,
`duplicateStore` et `update` lisent `shop_id`, `zone_id`, `category_id`,
`packaging_id`, `delivery_type_id`, `delay_id` et `priority_id` en ne gardant
que `Merchant` ». **Sept identifiants non gardés.** C'était faux, et la mesure
est plus intéressante que l'annonce :

| Identifiant | Ce que la mesure a trouvé |
|---|---|
| `zone_id` | **déjà gardé deux fois** — `ChargeCalculator` et la règle `DeliveryRoutePriced` |
| `delivery_type_id` | constante de plateforme (`DeliveryType`), pas une clé étrangère |
| `priority_id` | drapeau 1/2 écrit en dur par le contrôleur |
| `delay_id` | **nu dans le résolveur** — le défaut d'argent |
| `shop_id` | **nu** — et `merchant_shops` n'a pas de `company_id` (S26) |
| `packaging_id` | **nu à l'écriture**, alors que son prix, lui, était gardé |
| `category_id` | **nu** |

Trois défauts, pas sept. Les nommer séparément vaut mieux qu'annoncer « sept
identifiants non gardés » — plus spectaculaire, et faux. Un test inscrit
désormais que la zone était déjà gardée, pour que personne n'aille « réparer »
ce qui tient.

### Le délai : 1 500 F qui deviennent 10 500 F

`DeliveryChargeResolver::supplement()` lisait `DeliveryDelay::find()` nu. Le
délai porte une **surcharge** ajoutée à la course : nommer le délai d'une autre
société facturait son supplément à notre marchand. Mesure faite : une course de
1 500 F devient **10 500 F** avec un délai voisin surchargé à 9 000.

Atteignable par **deux** chemins — la création du colis et la règle de
validation `DeliveryRoutePriced`, qui passe elle aussi `delay_id` brut. Garder
dans le résolveur les ferme tous les deux.

### ⚠️ Le périmètre est celui de la zone, pas du locataire ambiant

`companywise()` lirait `settings()`. Or ce résolveur ne suppose nulle part que
la route demandée est celle du locataire courant : `trancheDeZone()` ne s'appuie
que sur le marchand et la zone.

C'est `DeliveryZoneGridTest` qui l'a dit, en tombant. Sa fixture tarifie un
marchand de la **société 2** pendant que `settings()->id` vaut **1** — le
premier marchand du locataire semé n'appartient pas au locataire. Une garde sur
`settings()` y avalait silencieusement le supplément.

La garde tient quand même : un délai étranger présenté avec **notre** zone est
écarté, puisque c'est la zone qui donne la société. Un test épingle la décision,
sinon quelqu'un « simplifiera » en `companywise()` un jour.

### La boutique, l'emballage, la catégorie

- **`shop_id`** — `merchant_shops` ne porte **aucune** colonne `company_id`
  (constat de S26) : le périmètre passe par le **marchand**, et pas seulement
  par la société — la boutique d'un confrère de la maison n'est pas davantage la
  sienne. Conséquence imprimée : `backend/parcel/bulk_print` rend
  `merchantShop->contact_no`. Le téléphone d'une boutique étrangère partait sur
  **notre** étiquette.
- **`packaging_id`** — son **prix** était déjà gardé
  (`ChargeCalculator::packagingAmount()`), son **écriture** non. Le colis portait
  un emballage absent de notre catalogue, et personne ne le payait.
- **`category_id`** — une des deux clés du barème. Refusée désormais par une
  garde et non par accident : sans ligne de barème, une catégorie étrangère
  faisait échouer la création toute seule. Le test pose donc une ligne de barème
  de **notre** société portant la catégorie du voisin — rien dans le schéma ne
  l'interdit — sans quoi il mesurerait un faux déjà acquis.

Chaque catalogue reste **facultatif** : un champ absent laisse le colis sans
catalogue, comme avant. Seul un identifiant fourni **et** étranger fait refuser.

### ⚠️ L'autre porte, et le pire défaut du lot

Le panneau **marchand** a son propre dépôt de colis, et il lisait les mêmes
champs sans en vérifier **aucun**. Il en porte un de plus :

```php
$parcel->merchant_id = $request->merchant_id ?? $merchant_id;
```

Le formulaire du panneau envoie `merchant_id` dans un champ **caché**, et sa
requête de validation ne le mentionne même pas. Un marchand pouvait donc, depuis
**son** panneau, attribuer un colis à n'importe quel autre marchand — y compris
d'une autre société — pendant que `company_id` restait la nôtre. Le colis
atterrissait dans les relevés et le solde de l'autre.

C'est **mot pour mot** la forme de S33 côté back-office, et la même phrase vaut
ici : le formulaire ne propose que soi, rien n'oblige le navigateur à s'y tenir.

> Une faille corrigée d'un côté d'une application vit souvent intacte de
> l'autre. S33 avait fermé la porte de l'administration ; celle du marchand est
> restée ouverte dix lots.

### ⚠️ Le sabotage a réclamé trois fois dans ce seul lot

| Ce que le lot croyait couvert | Ce que le sabotage a montré |
|---|---|
| la garde du back-office, posée dans trois méthodes | `duplicateStore` et `update` restaient **verts** |
| la garde du panneau marchand | `duplicateStore`, `update` **et** la branche « catégorie » restaient verts |
| le contrôle négatif du panneau | il échouait pour une autre raison — voir ci-dessous |

> **Une aide partagée donne l'illusion d'une couverture que ses appelants n'ont
> pas.** Le sabotage doit viser chaque **point d'appel** et chaque **branche**,
> pas la fonction appelée.

C'est la troisième fois de suite qu'un sabotage complète le lot au lieu de le
valider — après le livreur étranger en lot de S45 et l'assertion creuse de
`ParcelLifecycleTest`.

Et un contrôle négatif a de nouveau corrigé un test : le dépôt du panneau
suppose le **marchand** connecté — il lit `auth()->user()->hub_id`. Agir en
administrateur faisait échouer la création légitime pour une autre raison, et le
contrôle ne mesurait plus rien.

### L'arriéré du filet S38 : 67 → 63

Les quatre portes de création sortent, chacune établie par sabotage contre le
seul `ParcelCatalogScopeTest` : `admin/parcel/store`, `admin/parcel/clone-store`,
`merchant/parcel/store`, `merchant/parcel/clone-store`.

`admin/parcel/delivery-category` **reste** : son contrôleur est bien
`companywise()`, mais aucun test ne tombe quand on le retire. La garde existe ;
rien ne la tient.

### La passe suivante

Le panneau marchand a d'autres dépôts que celui des colis — boutiques,
portefeuille, tickets — et le défaut du `merchant_id` caché invite à les relever
de la même façon : ce qui a été fermé côté administration l'a-t-il été côté
marchand ?
