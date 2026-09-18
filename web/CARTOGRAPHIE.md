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

| | Au départ | 1re passe | 2e passe |
|---|---|---|---|
| Prouvées | 7 | 33 | **57** |
| Exemptées | 33 | 35 | 35 |
| Publiques | 6 | 6 | 6 |
| **Héritées (plafond)** | **171** | 143 | **119** |

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

### Ce qui reste dans l'arriéré : 119 routes

Le travail restant est identifiable, et le tableau des quatre formes plus haut le
classe. L'essentiel tient en deux familles :

- 🔴 **Le vol de ligne** — `Modele::find($id)` puis `$m->company_id = settings()->id`
  et `save()`. La ligne d'une autre société n'est pas seulement lue, elle est
  **transférée** chez nous. Relevé dans `Role`, `Asset`, `Assetcategory`,
  `Designation`, `Packaging`, `Hub`, `DeliveryCharge`, `Todo`, `NewsOffer`,
  `Department`, `HubPayment`. C'est la famille à traiter en premier.
- **La lecture nue** — `get($id)` en `Modele::find($id)`, donc l'écran `edit` d'une
  autre société : `Role`, `Asset`, `Assetcategory`, `Fraud` (back-office),
  `HubPayment`, `HubPaymentRequest`, `Profile`, `Income`, `Hub`, `AccountHead`,
  `Salary`, `Todo`, `MerchantPayment`, `Currency`, `Wallet::getFind`.

Les ~20 `delete()` de la forme « je cherche nu puis je compare `company_id` » sont
**gardés** : l'écriture ne part pas hors périmètre. Ils restent à inscrire, pas à
corriger — sauf le déréférencement de `null` sur un identifiant inexistant.

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
