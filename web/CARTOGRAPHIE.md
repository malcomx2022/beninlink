# CARTOGRAPHIE.md — Étape 0 (socle We Courier, dossier web/)

> Relevé de l'existant AVANT toute modification. Lecture seule.
> Chaque bloc cite les fichiers réels du socle. Blocs **A-K** renseignés.
> Dernière mise à jour : 2026-10-08 (S133 — un numéro béninois se saisit comme on le dit ; S132 — le cookie de session ne part plus en clair ; S131 — la surface publique a une cadence bornée ; S130 — le parcours OTP du site marchand est limité comme l'API ; S129 — le retour Stripe n'active qu'un abonnement, pour le compte qui a payé ; S128 — la partie serveur de S126 est close ; S127 — un texte riche écrit par un autre est nettoyé avant d'être rendu ; S126 — un fichier téléversé ne devient jamais du code serveur ; S125 — l'arriéré des droits du back-office fermé ; S124 — l'installateur de modules fermé, cinq écrans gardés par leur droit ; S123 — l'origine d'un mouvement de portefeuille se lit en français ; S122 — une date affichée parle la langue de son lecteur ; S121 — un montant affiché est en FCFA entiers ; S120 — le registre dit l'état du 2026-10-08 ; S119 — les scripts du back-office parlent français ; S118 — les messages du back-office et de l'API parlent français ; S117 — le back-office parle français, ses comptes sont béninois ; S116 — le panneau marchand parle français et compte en FCFA ; S115 — le parcours d'une PME pilote parle français, du formulaire au courriel ; S114 — les pages d'erreur et les phrases des vues parlent français ; S113 — aucune clé de traduction affichée brute ; S112 — le catalogue français ne garde plus d'anglais ; S111 — aucune marque tierce sur les pages publiques ; S110 — la société renommée, les secrets de recette nommés ; S109 — l'état du serveur corrigé : quatre points sur cinq faits, le renommage attend ; S108 — le serveur : société renommée, Supervisor et crontab posés ; S107 — dette technique T2, T3, T9 tranchée (D15) ; S106 — R1, R2 et R8 actés par des défauts réversibles ; S105 — la page des plans ne dépend plus d'un réglage Stripe absent ; S104 — un compte livreur n'entre pas au back-office web ; S103 — les cartes de colis des deux apps rendues en test ; S102 — le registre dit la production vraie ; S101 — les listes de colis signalent le document douanier à collecter ; S100 — la CI des pull requests n'attend plus le déploiement de main ; S99 — le garde du .env refuse le mode debug en production ; S98 — mot de passe oublié dans l'app livreur ; S97 — le garde du .env refuse un cache non partagé ; S96 — les entrées d'authentification de l'API limitées contre la force brute ; S95 — le livreur voit l'alerte douanière de sa course ; S94 — l'alerte douanière sur la fiche colis web ; S93 — la vitrine d'une société neuve parle français ; S92 — les semences parlent du Bénin ; S91 — l'app marchand : barème par zones seul ; S90 — l'API négocie
> sa langue ; S89 — un déploiement refusé
> remet l'ancien code ; S88 —
> l'installateur fermé sur une base installée ; S87 — plus
> de mot de passe public sur les comptes d'amorçage ; S86 — une installation neuve réussit son premier déploiement ; S85 — le contrat de l'app livreur tenu en PHPUnit ;
> S84 — un lanceur de tests dans les deux apps, M2, et le job CI `apps` ; S83 — l'écran de
> modification d'une demande de retrait réparé ; S82 — l'alerte douanière sur le détail du
> colis, M1 ; S81 — identifiants au nom libre dans les filets). Les blocs A-K décrivent le socle **tel que trouvé** ; les
> sections `## S<nn>` qui suivent racontent chaque lot, avec ses sabotages et le compte
> de la suite. Le 2026-08-16 : chantier 1 (langue et devise — helpers XOF, 278 affichages
> et 32 sorties d'API en FCFA entier) ; avant : blocs J — tarifs et K — notifications.

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
    le Bénin** (numéros à 8 chiffres, +229 → 11 avec indicatif). ✅ **S133** : le numéro saisi entre
    au format rangé `2290197010007` avant la validation (`NormalizePhoneNumbers`).

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
     → S3 l'a passée en `env('API_KEY', …)`, repli conservé ; **S77** retire le repli
     et ferme la porte (`hash_equals`, refus si clé vide) — voir `## S77`.
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
| ~~S88~~ | A | ~~**`GET /finish` détruisait la base d'une installation terminée, sans authentification**~~ — 🔴 ✅ **corrigé le 2026-10-05 (S88)** : `routes/web.php` ne posait `IsNotInstalled` que sur l'écran `GET /install` ; `POST /installing` et `GET /finish` ne portaient que `XSS`, et `InstallerController::finish()` supprime **chaque table** (`SHOW TABLES` + `Schema::drop`), rejoue `migrate:refresh` et `db:seed`, pose nom, courriel et **mot de passe du compte n° 1** depuis la requête, et réécrit `APP_INSTALLED` et `APP_URL` dans le `.env`. Second maillon : le garde ne reconnaissait une installation que par `APP_INSTALLED=yes`, que l'installation prescrite par le guide (`migrate` + `db:seed`) n'écrit pas. Les trois routes portent le garde ; une base qui porte des utilisateurs est installée, drapeau ou pas ; les deux actions répondent **404** ; `verifier-env.sh` refuse un `.env` sans le drapeau. Relevé en relisant l'installateur pour S87 ; aucun test ne nommait l'installateur | `routes/web.php` · `App\Http\Middleware\IsNotInstalledMiddleware` · `docs/guides/infra/deploy/verifier-env.sh` · `InstallerLockTest` |
| ~~S96~~ | A | ~~**Les entrées d'authentification de l'API (`signin`, `deliveryman/login`, OTP, `password/reset`) sans limite propre : 60 mots de passe ou codes OTP par minute et par adresse**~~ — 🟠 ✅ **corrigé le 2026-10-06 (S96)** : limiteur `connexion` (5/min par identifiant + adresse, 30/min par adresse), réponse 429 dans l'enveloppe, `ApiAuthThrottleTest`. | `routes/api.php`, `RouteServiceProvider` |
| ~~S99~~ | A | ~~**Rien n'empêchait un `.env` de production en `APP_DEBUG=true` : la page d'erreur de Laravel montre la pile, la requête et des variables d'environnement**~~ — 🟠 ✅ **fermé le 2026-10-06 (S99)** : `verifier-env.sh` le refuse avant `php artisan down` (signalé seulement en recette), `RecetteDeploymentTest`. | `docs/guides/infra/deploy/verifier-env.sh` |

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

✅ **La notification à la création est branchée** (réserve levée). Elle avait trois
canaux à ouvrir, ouverts à trois moments différents — et la réserve ci-dessus est
restée écrite alors que deux l'étaient déjà :

| Canal | État | Par quoi |
|---|---|---|
| Fil en base | ✅ | `CustomsAlertFeedObserver` → `MerchantFeed::customsAlertRaised()` |
| Push | ✅ | `PushChannel` + job `SendPush` (D11 ; muet si l'utilisateur n'a aucun appareil) |
| Courriel | ✅ | `EmailChannel` + `App\Mail\MerchantFeedMail` (voir plus bas) |

⚠️ **Une réserve qui survit à sa cause dit le contraire de ce qu'elle voulait dire.**
Celle-ci affirmait « rien n'est envoyé » alors que le fil et le push partaient déjà :
elle a survécu au correctif qui l'annulait. Une vérification du module l'a relevée —
la leçon vaut pour toutes les lignes `⏳` de ce fichier : elles se relisent quand on
touche à leur sujet, sinon elles deviennent la version la plus crédible d'une erreur.

✅ **L'écran `customs` de `mobile/` existe** — `app/(app)/customs.tsx`, avec ses
deux onglets, la résolution d'alerte et le routage depuis le push (`data.kind`).
Les trois exports de `src/api/customs.ts` sont consommés : `fetchCustomsAlerts` et
`resolveCustomsAlert` par cet écran, `fetchCustomsReference` par la création de
colis. La réserve qui figurait ici était **fausse**, et voir S68 pour comment.

⚠️ **`mobile/` est une app expo-router : ses écrans vivent dans `app/`.**
Il n'y a pas de dossier `src/screens/`, et il n'y en aura pas — `app/` **est** le
dossier des écrans, chaque fichier y étant une route. Chercher un écran dans
`mobile/src` ne trouve rien et ne prouve rien.

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

⇒ ~~Un passage `php artisan route:list` complet est recommandé avant d'ouvrir les chantiers.~~
✅ **Depuis S69 (2026-10-03), la suite le lit à chaque commit** : `LegacyDeadCodeCleanupTest`
monte les routes du locataire et vérifie que chaque route à contrôleur vise une classe et une
méthode qui existent — `route:list` ne le dit pas, et c'est à la main que le bloc G avait
trouvé les deux routes PDF ci-dessus.

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
- ✅ le **doublon mort** `InvoiceRepository::InvoicePdf()` (S32) — **retiré le 2026-10-03 (S69)** ;
- les **catalogues communs** `currencies` (S32) et `categorys` (S35), partagés et
  modifiables ;
- les six catégories de livraison du jeu d'amorçage, créées **sans société** : aucune
  société ne peut donc les modifier par son écran, sauf la n° 1 en lecture ;
- ✅ la **destination du refus** de `PermissionCheckMiddleware` pour une navigation
  de page — **corrigée le 2026-09-20 (S38)** ;
- ✅ `IncomeController::searchAccount()` est une **méthode morte** : sa route a disparu
  et l'écran des revenus appelle celle des dépenses. Elle passe de plus l'objet
  `Request` là où un identifiant est attendu — **retirée le 2026-10-03 (S69)**.

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

## ✅ S47 — le catalogue emprunté, et une cascade qui traverse les sociétés (2026-09-23)

### La question de S46 a reçu une réponse, et ce n'était pas celle attendue

S46 finissait sur : *« ce qui a été fermé côté administration l'a-t-il été côté
marchand ? »* Le balayage des **six** dépôts du panneau — boutiques,
portefeuille, demandes de paiement, tickets, fraude, ramassage — répond **oui** :
tous portent leur garde de ressource depuis S7. La supposition d'un retard côté
marchand était fausse.

Le trou était ailleurs, et plus grave.

### ⚠️ L'inscription publique empruntait le catalogue d'une autre société

```php
$user->designation_id = Designation::first()->id;   // signUpStore()
$user->department_id  = Department::first()->id;
```

La **première ligne de la table**, toutes sociétés confondues. Le compte
propriétaire d'une société neuve pointait vers le catalogue d'une société
existante — et `POST company/sign-up/store` ne vit derrière **aucune
authentification** : groupe `['XSS', 'IsInstalled']`, entre le blog et le
formulaire de contact.

### ⚠️ La conséquence détruisait des comptes

`users.department_id` et `users.designation_id` portent **`onDelete('cascade')`**.

Une société qui supprimait **son** service depuis ses propres réglages détruisait
les comptes qui le référençaient — y compris **le propriétaire de la société
voisine**. Aucun écran, aucun journal, aucune confirmation ne le mentionnait.

`test_deleting_our_own_department_never_destroys_another_companys_owner` le
vérifie de bout en bout. **Il échouait.**

Correctif : une société neuve n'a pas encore de catalogue, et ne rien lui donner
vaut mieux que lui prêter celui d'une autre. Les deux colonnes sont nullables.

### Le sélecteur était ouvert des deux côtés, l'écriture aussi

`Department::active()` sans périmètre dans le back-office **et** dans le panneau
marchand, alors que `UserRepository` sert le **même** catalogue en
`where('company_id', settings()->id)->active()` deux fichiers plus loin. La règle
était connue ; deux lectures l'ignoraient.

Et fermer le sélecteur ne ferme pas l'écriture :
`$support->department_id = $request->department_id` restait libre dans `store()`
et `update()`, des deux côtés. C'est la leçon de S43 prise par l'autre bout —
là-bas un écran nu empêchait de garder un sélecteur ; ici un sélecteur gardé
donnait l'illusion que l'écriture l'était.

### ⚠️ Une garde posée dans deux méthodes n'est pas prouvée dans les deux

La garde posée dans `store()` **et** `update()` n'était tenue que dans `store()`.

Troisième lot d'affilée où ce réflexe rattrape le travail.

| | |
|---|---|
| `CompanyCatalogScopeTest` | 4 cas, 22 assertions |
| Gardes sabotées séparément | **8 sur 8 rouges** |
| Suite complète | 930 tests, 45 572 assertions, verte |

## ✅ S48 — les contreparties d'une écriture comptable (2026-09-23)

### Le motif, pour la quatrième fois

S45 sur **l'agent** d'un mouvement de colis, S46 sur les **catalogues** d'un
colis, S47 sur le **catalogue** d'un compte. Toujours la même forme : **la
ressource est gardée, le second identifiant ne l'est pas.**

La ressource comptable l'est depuis S30 — `Income::companywise()->find($id)` —
mais une écriture ne touche pas que sa propre ligne : elle **déplace de l'argent**
sur une contrepartie nommée dans la requête.

| Contrepartie | Ce que le socle en faisait |
|---|---|
| `Merchant::find()` | `current_balance ± amount`, `save()`, plus un relevé |
| `DeliveryMan::find()` | idem |
| `Hub::find()` | idem |
| `User::find()` | le salaire versé |
| `Account::find()` | le **compte de trésorerie** mouvementé |
| `Parcel::find()` | la pièce rattachée |

**Mesure** : une recette de 1 000 F saisie chez nous créditait le solde d'un
marchand, d'un livreur ou d'un entrepôt d'une **autre** société, en lui attachant
un relevé portant **notre** `company_id`.

La garde vit dans le trait `GuardsAccountingCounterparties` — les trois dépôts
écrivent la même famille.

### ⚠️ Le relevé automatique s'est trompé une fois sur quatre

L'instrument signalait aussi `CashReceivedFromDeliveryman`. **Il avait tort** :
ce dépôt fait déjà `DeliveryMan::companywise()` et `Account::companywise()` avec
refus, dans `store()` **et** `update()`. La lecture partant d'une ligne déjà
scopée l'avait trompé.

Les deux gardes posées là par réflexe ont été **retirées**.

> Un relevé automatique se lit, il ne s'applique pas.

### Un défaut trouvé en gardant autre chose

`SalaryGenerate::find($request->id)` était nu dans `salaryGenerateUpdate()` :
**le bulletin de paie d'une autre société était modifiable** en changeant
l'identifiant du corps.

### ⚠️ Un seul point d'appel sur huit était tenu

Au premier passage. Les sept autres ont demandé un travail spécifique : leurs
`update` ne sont atteints qu'avec une **ressource à nous** et une **contrepartie
étrangère** — sur une ressource étrangère, la garde de la ressource refuse
d'abord et masque entièrement celle des contreparties.

| | |
|---|---|
| `AccountingCounterpartyScopeTest` | 7 cas, 44 assertions |
| Sabotages | **15 sur 15 rouges** — 8 points d'appel, 6 champs, la ressource |

## ✅ S49 — la boutique et son marchand (2026-09-23)

### Deux défauts, et le second était déjà nommé

`ShopsRepository::store()` n'avait **aucune** garde : `merchant_id` venait du
formulaire et partait tel quel dans la colonne. Un opérateur créait donc une
boutique chez le marchand d'une **autre** société — et `merchant_shops` ne porte
pas de `company_id` (constat de S26), donc cette boutique vit entièrement sous le
marchand d'en face.

`ShopsRepository::update()` portait le trou que **S29 avait nommé sans le
fermer**. Son propre commentaire, encore dans le fichier :

> « la ligne `merchant_id` juste en dessous permettait en plus de la
> **RATTACHER** à un autre marchand »

Le correctif de S29 n'avait fermé que la **lecture**. La ligne est restée cinq
lots.

> Nommer un défaut dans un commentaire ne le ferme pas.

### ⚠️ Deux sabotages ont menti — en ROUGE

C'est la leçon du lot, et elle est nouvelle.

En supprimant le **bloc** d'une garde, `$marchand` restait indéfini. L'erreur
tombait dans le `catch (\Throwable)`, la méthode rendait `false` — **pour la
mauvaise raison** — et le test paraissait couvrir une garde qu'il ne couvrait pas.

Jusqu'ici on savait qu'un sabotage **vert** interroge le test. Celui-ci apprend
qu'un sabotage **rouge** peut mentir aussi.

> On sabote la **garde** elle-même — `companywise()` retiré — jamais le bloc
> autour.

Le cas manquant sur `bankUpdate` et `mobileUpdate` suivait la même logique : tous
les tests existants passaient un `editid` **étranger**, dont la garde de la ligne
refusait d'abord. Le périmètre du **marchand** n'était jamais atteint. Le cas
ajouté est l'inverse : **ma** ligne, **son** marchand.

| | |
|---|---|
| `MerchantFamilyScopeTest` | 23 cas, 82 assertions (3 ajoutés) |
| Suite complète | 940 tests, 45 641 assertions, verte |

## ✅ S50 — l'arriéré relu : dix-huit routes déjà closes (2026-09-23)

### Un lot ferme plus de routes qu'il n'en revendique

S49 l'avait constaté sur quatorze lignes : la moitié sortait de l'arriéré parce
que S47 et S48 les avaient déjà fermées **sans que personne l'ait mesuré**. Ce
lot-ci prend le constat au sérieux et va chercher l'avance explicitement, sur
l'arriéré entier.

Aucune ligne de production n'est touchée. C'est une passe de **mesure**.

### Le relevé

Les 49 routes restantes ont été rattachées à leur `contrôleur@méthode` — 47 sur
49 ; `POST admin/wallet-request/recharge` et `POST merchant/sign-up-store`
résistent encore à la lecture statique.

La plus grosse famille cohérente est celle des **onze `update` de réglages**. Dix
portent déjà leur garde ; chacune a été sabotée séparément, et les dix sont
**rouges**. Huit autres routes — l'argent du back-office et du panneau marchand —
le sont également.

| Routes | Prouvées par |
|---|---|
| neuf réglages (immobilisations, services, fonctions, entrepôts, emballages, rôles, tâches, frais) | `BackOfficeRecordTakeoverTest` |
| catégorie de livraison | `UserAndSettingsScopeTest` |
| versement, versement traité | `BackOfficeMoneyScopeTest` |
| versement d'entrepôt traité | `BackOfficeRecordTakeoverTest` |
| deux remises d'espèces du livreur | `CashHandoverAccountingTest` |
| fraude, compte de versement, demande de paiement du marchand | `MerchantPanelWebScopeTest` |

**L'arriéré du filet S38 : 49 → 31.** Le plafond suit.

### Ce qui reste, et ce qui ne se laisse pas prouver

`POST admin/merchant/store` a été sabotée : **verte**. La garde existe, aucun
test ne tombe quand on la retire. Elle reste dans l'arriéré, à sa place — c'est
précisément ce que l'arriéré veut dire.

**Onze** routes ne montrent **aucune garde reconnaissable** : `assets/store`,
`deliveryman/store`, `fraud/store`, les trois `todo`, `payment/store`,
`hub/payment/store` et les deux `support/reply`. Elles n'ont pas été mesurées
faute de garde à saboter — ce sont les candidates du prochain lot de correction,
pas de mesure.

### ⚠️ Une question de produit, pas de technique

`PUT admin/currency/update` reste dans l'arriéré et **ne peut pas en sortir par
la mesure** : `CurrencyRepository::update` est nu, mais la table `currencies` ne
porte **aucune** `company_id` — comme `categorys` (constat de S35). Le catalogue
est pourtant atteignable depuis le back-office **locataire** *et* depuis les
routes super-administrateur.

Trois issues, et le choix n'appartient pas à la revue :

1. **l'exempter** comme `category/update` — le catalogue des devises est celui de
   la plateforme, et une société n'a pas à le modifier ;
2. **réserver la route au super-administrateur** — même décision, appliquée à
   l'accès plutôt qu'au filet ;
3. **ajouter `company_id`** — chaque société tient ses devises, migration à la clé.

⚠️ **Rectifié en S55 :** j'ai longtemps écrit ici qu'« une société qui renomme une devise la renomme pour tout le monde ». **C'était faux comme affirmé.** La mesure donne **57** entrées dans la table `permissions` du locataire, et `currency_update` n'en fait pas partie — il n'existe que dans `SuperAdminPermission`. Un administrateur de locataire portant *tous* ses droits recevait déjà un refus. Ce qui était vrai : la garantie venait de la **donnée** (les semences), pas de la **structure** — la route vivait sous `admin/` avec `panel:back-office`. S55 la déplace sous `panel:super-admin`.

## ✅ S51 — le second identifiant sur les portes de création (2026-09-23)

### Le motif, pour la septième fois

S45 sur **l'agent** d'un mouvement de colis, S46 sur les **catalogues** d'un
colis, S47 sur le **catalogue** d'un compte, S48 sur les **contreparties** d'une
écriture, S49 sur le **marchand** d'une boutique, S50 l'a mesuré partout où il
était déjà fermé. S51 attaque les onze routes que S50 avait désignées : celles
où l'instrument ne reconnaissait **aucune** garde.

La lecture a d'abord corrigé l'instrument, et c'est le premier constat du lot.

### ⚠️ Un instrument qui cherche une FORME ne trouve pas une RÈGLE

Sur les onze routes signalées « sans garde », **quatre étaient gardées** :

- `todoComplete` et `todoProcessing` comparent `company_id == settings()->id`
  **à la main**, sans passer par `companywise()` ;
- la réponse aux tickets du **panneau marchand** refuse déjà par `get()` ;
- `CashReceivedFromDeliveryman` l'était depuis toujours (déjà corrigé en S48).

Et **deux** n'ont rien à garder : la fiche de fraude ne porte aucun identifiant
de locataire — `tracking_id` est un `string`, pas une clé étrangère. Elles sont
désormais **exemptées avec leur motif**, pas corrigées.

> Mon relevé de S50 disait « aucune garde reconnue ». Il disait vrai. J'ai
> écrit « aucune garde » dans la cartographie — c'était faux, et la correction
> vaut d'être notée : un instrument ne constate pas, il **signale**.

*(La ligne de S50 qui annonçait « six routes » est corrigée au passage : il y
en avait onze.)*

### ⚠️ Deux portes déplaçaient de l'argent

C'est là que le défaut coûte, et les deux sont de la famille de S48 sur un
module qu'il ne couvrait pas :

| Porte | Identifiant nu | Ce que le socle en faisait |
|---|---|---|
| versement marchand | `merchant` | `current_balance - amount`, plus un relevé |
| versement marchand | `from_account` | le compte de **trésorerie** débité |
| versement marchand | `merchant_account` | le compte bancaire crédité |
| versement entrepôt | `from_account` | le compte de **trésorerie** débité |
| versement entrepôt | `hub_id` | l'entrepôt créancier |

Un versement saisi chez nous débitait donc le solde d'un marchand d'une **autre**
société, et le compte de trésorerie d'en face, en écrivant relevé et transaction
bancaire à **notre** `company_id`.

### ⚠️ `companywise()` ne suffisait pas sur `merchant_account`

Un compte de versement appartient à **un marchand** (S34 : la table ne porte pas
de `company_id`). Garder par `companywise()` seul laissait ouvert le paiement du
marchand **A** sur le compte bancaire du marchand **B** — les deux chez nous.
La garde est donc plus étroite : le compte doit être **celui du marchand payé**.
Même leçon qu'en S49.

### Les portes de rattachement

`AssetRepository::store` classait l'immobilisation dans le catalogue du voisin et
la posait dans son entrepôt ; `DeliveryManRepository::store` affectait le livreur
à l'entrepôt du voisin ; `TodoRepository::store` assignait la tâche à son agent.

### ⚠️ Quatrième passage dans `SupportRepository`, et `reply()` avait survécu

S23 a scopé les **lectures**, S29 a fermé `update()` et `destroy()`, S47 a fermé
le `department_id` de `store()`/`update()`. Personne n'avait regardé la
**réponse** : `support_id` venait du corps, nu. On écrivait un message dans le fil
du ticket d'un autre transporteur, signé de notre identifiant.

Et le **panneau marchand**, lui, gardait déjà ce chemin — l'inverse exact de
l'asymétrie supposée en S47. C'est la raison de mesurer les deux côtés plutôt
que de déduire l'un de l'autre.

### ⚠️ Le sabotage a corrigé le lot trois fois

**1. Un contrôle négatif creux.** `Asset / assetcategory_id` est sorti **vert**.
La requête de test laissait `hub_id = ''`, ce qui viole la contrainte de clé
étrangère : le dépôt rendait `false` **pour la mauvaise raison**, et le test
passait sans jamais exercer sa garde. Une sonde l'a établi plutôt qu'une
supposition.

**2. Une assertion vide passe toujours.** Les deux tests de contrôleur reposent
sur `assertStringNotContainsString`, qui est **vrai sur une chaîne vide** — donc
aussi quand la requête n'atteint jamais le contrôleur. Le sabotage du contrôleur
marchand est resté **vert** : la route exige `payment_create`, pas
`merchant_payment_create`, et mon agent recevait un 403.

**3. Et une validation muette.** L'ancrage positif ajouté ensuite a immédiatement
révélé une deuxième cause : `merchant_account` est obligatoire côté validation,
donc la requête mourait avant le contrôleur.

> Un test qui vérifie une **absence** doit d'abord prouver une **présence** :
> que le code visé a bien tourné. Sans cet ancrage, il mesure le vide.

### L'arriéré du filet S38 : 31 → 20

Neuf routes prouvées par `CreationDoorScopeTest`, deux exemptées avec leur motif.

| | |
|---|---|
| `CreationDoorScopeTest` | 15 cas, 53 assertions |
| Sabotages | **15 sur 15 rouges** — 10 champs de dépôt, 2 contrôleurs, 3 gardes préexistantes |
| Cliquet | mord à 19, et nomme une route retirée de `PROUVEES` **ou** d'`EXEMPTEES` |

### La passe suivante

Il reste **20** routes à l'arriéré. `PUT admin/currency/update` en fait toujours
partie et attend un arbitrage de produit (voir S50) : `Currency::find()` est nu,
mais `currencies` ne porte aucune `company_id`, donc une société qui renomme une
devise la renomme **pour tout le monde**.

## ✅ S52 — les aides qui renseignent, et la fin de l'arriéré S38 (2026-09-24)

### Les vingt dernières routes n'étaient pas de la même nature

Les lots S45 à S51 fermaient des **écritures**. Les vingt routes restantes sont
pour la plupart des **aides AJAX** : elles ne modifient rien. On pouvait les
croire sans enjeu.

Trois d'entre elles rendaient des données qu'un concurrent paierait :

| Aide AJAX | Ce qu'elle rendait d'une **autre** société |
|---|---|
| `get-merchant-cod` | la grille de frais de contre-remboursement d'un marchand |
| `merchant/account` | titulaire, banque, **numéro de compte**, agence, mobile |
| `salary/search-account` | le **montant du salaire** d'un employé |

### ⚠️ La règle qui a structuré la lecture

> Un identifiant étranger utilisé comme **filtre** sur une requête déjà scopée
> ne fuit rien — la jointure ne rend simplement aucune ligne. Il n'est dangereux
> que quand il sert à **aller chercher**.

C'est pourquoi les trois recherches de colis (`Parcel::companywise()->where([...])`)
sont saines, alors que `get-merchant-cod` (`Merchant::find()`) ne l'était pas.
Les quatre se ressemblent à l'œil.

### Trois écritures atteignaient un tiers d'une autre société

| Écriture | Ce qu'elle faisait |
|---|---|
| recharge de portefeuille | **crédite** `wallet_balance` **et envoie un SMS** |
| notification poussée | **pousse** sur l'appareil du destinataire |
| création / inscription marchand | le rattache à l'**entrepôt** du voisin |

⚠️ L'inscription marchand est **publique et sans authentification**, comme
l'inscription société de S47. Et le rattachement d'entrepôt existait à **trois**
points d'appel — `store`, `signUpStore` et `update` — le troisième trouvé en
gardant les deux premiers.

Le défaut des comptes bancaires existait lui aussi à **deux** points d'appel :
`merchantAccount` et `merchantpaymentFilter`, le second trouvé en gardant le
premier.

### Un refus se dit, il ne plante pas

`sms-send-settings/status` était **correctement scopé** — hors périmètre, la
requête rend `null` et aucune bascule n'a lieu. Mais l'affectation juste en
dessous déréférençait ce `null` : **500** au lieu d'un refus. Même famille que
S15 et S30. C'est l'ancrage du test qui l'a révélé, pas la lecture.

### ⚠️ Le sabotage a corrigé le lot cinq fois

C'est le lot où il a le plus servi, et aucune de ces cinq n'était visible à la
lecture.

**1. `permissions = null` ne vaut pas « tous les droits ».** Il vaut **aucun
droit** : les routes répondaient 403, et `assertStringNotContainsString` est vrai
sur une page « Accès interdit ». `ajax()` exige désormais **200** avant de rendre
le corps.

**2 et 3. Deux contrôles négatifs creux.** Mon colis de test était toujours en
`PENDING`, alors que la recherche des retours filtre `RETURN_TO_COURIER` et celle
de la réception `TRANSFER_TO_HUB`. Le colis ne matchait jamais : les deux tests
passaient sans exercer leur garde.

**4. Une assertion sur une valeur qui n'apparaît jamais.** J'assertais l'absence
du **montant** dans la réponse de `deliveryWeight` — or cette vue ne rend que
`weight` et `category->title`. L'assertion était vraie sans rien prouver.

**5. Un ancrage non unique.** Les gardes de `store` et `update` sont
textuellement identiques ; le sabotage frappait deux lignes et ne prouvait rien.
C'est la leçon de S44, reprise telle quelle.

### ⚠️ Et deux fois, il a corrigé ce que j'allais écrire

**Un oracle d'existence qui n'existait pas.** J'avais diagnostiqué que le filtre
des relevés révélait si un numéro de suivi existe ailleurs, et je l'avais écrit
dans deux contrôleurs et un test. Le test a échoué : **quatre lignes sous le code
que j'avais lu**, le socle porte déjà `if (tracking_id && blank($parcelID))
parcel_id = 0`. Les deux branches rendent un ensemble vide. Le `companywise()`
reste — il est correct — mais il ne ferme rien, et la route est **exemptée** pour
ce motif.

**Une attribution fausse.** J'allais inscrire au filet que
`PartialDeliveryAccountingTest` et `DeliveryCancellationAccountingTest` prouvent
les deux annulations de colis, d'après un relevé antérieur. Sabotage des deux
gardes, puis la suite **entière** : **aucun test ne tombe**. La garde existe
depuis S45 ; rien ne la tenait.

> Une attribution se vérifie au sabotage, elle ne se cite pas de mémoire.

J'ai alors écrit deux cas — et le sabotage les a trouvés **creux** à leur tour :
sur un colis étranger incomplet, la transaction lève et le `catch` rend `false`
de toute façon. Les prouver demande un colis d'en face assez complet pour que le
chemin **non gardé réussisse**. Les deux tests sont **retirés** et les deux routes
**restent à l'arriéré**.

### L'arriéré du filet S38 : 20 → 3

| | |
|---|---|
| `ArrearsRemainderScopeTest` | 17 cas, 46 assertions |
| Sabotages | **17 sur 17 rouges**, dont 5 seulement après correction |
| Cliquet | mord sur le plafond, sur `PROUVEES` et sur `EXEMPTEES` |

**Les trois qui restent, et pourquoi :**

1. `parcel/partial-delivered/cancel` — gardée, **rien ne la tient** (ci-dessus) ;
2. `parcel/return-received-by-merchant` — idem ;
3. `PUT admin/currency/update` — **question de produit**, pas de technique :
   `Currency::find()` est nu et `currencies` ne porte aucune `company_id`. En
   l'état, le catalogue est commun.
   ⚠️ **Rectifié en S55 :** j'ai longtemps écrit ici qu'« une société qui renomme une devise la renomme pour tout le monde ». **C'était faux comme affirmé.** La mesure donne **57** entrées dans la table `permissions` du locataire, et `currency_update` n'en fait pas partie — il n'existe que dans `SuperAdminPermission`. Un administrateur de locataire portant *tous* ses droits recevait déjà un refus. Ce qui était vrai : la garantie venait de la **donnée** (les semences), pas de la **structure** — la route vivait sous `admin/` avec `panel:back-office`. S55 la déplace sous `panel:super-admin`.
   Exempter comme catalogue de plateforme, réserver au super-administrateur, ou
   ajouter `company_id` — le choix n'appartient pas à la revue.

## ✅ S53 — les deux dernières annulations, et pourquoi elles avaient résisté (2026-09-24)

### Le problème n'était pas la garde, c'était la preuve

`parcel/partial-delivered/cancel` et `parcel/return-received-by-merchant` portent
leur garde depuis **S45**. Elles sont restées à l'arriéré du filet S38 à travers
S50, S51 et S52 — non faute de correctif, mais faute de **détecteur**.

S52 a établi le pourquoi, en deux temps, et les deux valent d'être écrits.

**1. Une attribution fausse.** J'allais inscrire au filet que
`PartialDeliveryAccountingTest` et `DeliveryCancellationAccountingTest` les
prouvent, d'après un relevé antérieur. Sabotage des deux gardes, puis la suite
**entière** : aucun test ne tombe.

> Une attribution se vérifie au sabotage, elle ne se cite pas de mémoire.

**2. Deux détecteurs creux.** J'ai alors écrit deux cas avec un colis étranger
**minimal**. Ils passaient — et le sabotage les a trouvés **verts**.

La raison est dans le socle : les deux méthodes lisent
`$deliveryManAssign->deliveryMan->id` dans leur branche `else`. Sans évènement
`DELIVERY_MAN_ASSIGN`, cette lecture lève, la transaction est annulée, et le
`catch` rend `false`. **Le refus ne venait pas de la garde.**

> Pour prouver une garde, le chemin **non gardé** doit RÉUSSIR. Un colis d'en
> face incomplet ne prouve rien : il fait échouer la méthode pour une raison
> étrangère au périmètre.

### Ce que ce lot ajoute : un colis d'en face complet

`ParcelCancelScopeTest` construit un colis étranger **entier** — livreur assigné
et son évènement `DELIVERY_MAN_ASSIGN`, évènement du statut courant, montants
numériques (`old_cash_collection`, `cod_charge`, `delivery_charge`, `vat`…),
marchand porteur de son taux de retour — tel que la méthode **aboutirait** sans
la garde. Ce qu'elle empêche devient alors observable :

| Méthode | Ce que le chemin non gardé écrirait |
|---|---|
| `parcelPartialDeliveredCancel` | un `VatStatement` à **notre** `company_id` sur **leur** colis, le statut ramené à `DELIVERY_MAN_ASSIGN`, l'évènement de livraison partielle **supprimé** |
| `returnReceivedByMerchant` | un `ParcelEvent` de retour reçu, un `MerchantStatement` débitant **leur** marchand, le solde de **leur** livreur modifié |

Chaque cas porte un **contrôle positif** qui exige que le chemin légitime
**réussisse** sur notre propre colis. C'est précisément ce qui manquait aux deux
tentatives creuses : sans lui, un refus pour n'importe quelle autre raison
validait le test.

⚠️ Une correction de fixture au passage : `parcels` ne porte **pas** de
`delivery_man_id` — le livreur d'un colis vit sur son `ParcelEvent`.

### L'arriéré du filet S38 : 3 → 1

| | |
|---|---|
| `ParcelCancelScopeTest` | 2 cas, 16 assertions |
| Sabotages | **2 sur 2 rouges** — après deux tentatives vertes |
| Cliquet | mord à 0, sur `PROUVEES`, et sur une classe de test absente |

**La seule route restante est `PUT admin/currency/update`**, et elle ne peut pas
sortir par la mesure : `Currency::find()` est nu, mais `currencies` ne porte
**aucune** `company_id` (comme `categorys`, S35). En l'état, une société qui
le catalogue est commun.
⚠️ **Rectifié en S55 :** j'ai longtemps écrit ici qu'« une société qui renomme une devise la renomme pour tout le monde ». **C'était faux comme affirmé.** La mesure donne **57** entrées dans la table `permissions` du locataire, et `currency_update` n'en fait pas partie — il n'existe que dans `SuperAdminPermission`. Un administrateur de locataire portant *tous* ses droits recevait déjà un refus. Ce qui était vrai : la garantie venait de la **donnée** (les semences), pas de la **structure** — la route vivait sous `admin/` avec `panel:back-office`. S55 la déplace sous `panel:super-admin`.

Trois issues, et le choix appartient au métier, pas à la revue :

1. **l'exempter** comme `category/update` — le catalogue des devises est celui
   de la plateforme ;
2. **réserver la route au super-administrateur** — même décision, appliquée à
   l'accès ;
3. **ajouter `company_id`** — chaque société tient ses devises, migration à la clé.

Tant qu'elle n'est pas tranchée, la route reste à l'arriéré : c'est la forme
honnête d'une question ouverte.

## ✅ S54 — la devise est un catalogue de plateforme : l'arriéré S38 est **clos** (2026-09-24)

### Une décision de métier, pas une mesure

`PUT admin/currency/update` était la **dernière** route de l'arriéré, et la seule
qui ne pouvait pas en sortir par la mesure : `Currency::find($request->id)` est
nu, mais la table `currencies` ne porte **aucune** `company_id`. Il n'y a donc
rien à cloisonner — et donc rien à prouver.

Les trois issues posées en S50 puis rappelées à chaque lot ont été tranchées le
**24/09** : **le catalogue des devises est celui de la plateforme**. La route est
**exemptée**, exactement comme `PUT category/update` l'avait été en S35 pour la
même raison.

Le constat lui-même est ancien : **S32** l'avait déjà relevé, et le témoin de
`categorys` le cite depuis S35 (« comme `currencies` au constat S32 »). Ce qui
change ici, c'est qu'il cesse d'être un constat en marge pour devenir une
**règle inscrite**.

### ⚠️ Ce que l'exemption ne dit pas

Elle ferme la question du **filet**, pas celle de l'**accès**.

> Le catalogue reste **partagé et modifiable** : renommer une devise la renomme
> pour toutes les sociétés. Ce n'est pas un défaut de cloisonnement — il n'y a
> rien à cloisonner — c'est le prix d'un catalogue commun.

Qui a le droit d'écrire dans un catalogue de plateforme depuis un back-office de
**locataire** reste une question ouverte ; `hasPermission:currency_update` y
répond seul aujourd'hui. L'exemption le **dit** au lieu de le taire, et la
réserve est recopiée mot pour mot de celle de `categorys`.

### La contrepartie : un témoin qui mord

Une exemption sans témoin est une affirmation. `UserAndSettingsScopeTest` porte
désormais le **jumeau** du témoin des catégories :

```php
public function test_the_shared_currency_catalogue_carries_no_company_at_all(): void
{
    $this->assertFalse(Schema::hasColumn('currencies', 'company_id'), ...);
}
```

Si `currencies` gagne un jour une société, **la décision n'a plus lieu d'être** :
le témoin tombe et force à reprendre l'exemption plutôt qu'à la laisser vivre sur
une prémisse périmée.

### L'arriéré du filet S38 : 1 → **0**

**Le filet est clos**, treize passes après son ouverture à 90 — le même chemin
que `WebIsolationCoverageTest`, fermé à S35 après 171 → 0 en dix passes.

`HERITAGE` doit désormais rester **vide** : il n'y a plus de file d'attente. Une
route d'écriture nouvelle se **prouve** ou se **motive** ; le plafond à `0`
interdit de l'y ranger.

| | |
|---|---|
| `HERITAGE` | **vide** |
| Plafond | **0** |
| L'exemption retirée | **mord** et nomme la route |
| Une ligne remise à l'arriéré | **mord** (`1` n'est pas ≤ `0`) |
| Le témoin de `currencies` | **mord** si la table gagne une société |

### Les deux filets, côte à côte

| Filet | Ouverture | Fermeture | Passes |
|---|---|---|---|
| `WebIsolationCoverageTest` (paramètre d'URL) | 171 | **0** (S35) | 10 |
| `BodyIdentifierCoverageTest` (identifiant de corps) | 90 | **0** (S53/S54) | 13 |

Ce que les deux disent ensemble : **aucune route d'écriture, à paramètre d'URL
ou à identifiant de corps, ne peut plus être ajoutée sans qu'on ait écrit ce
qu'on a fait de sa portée.** Ce n'est pas la promesse qu'il n'existe plus de
faille — c'est la promesse qu'on ne peut plus en ajouter une sans le dire.

## ✅ S55 — la devise passe au panneau du super-administrateur (2026-09-24)

### La seconde moitié de la décision du 24/09

S54 avait **exempté** `currency/update` du filet S38 : le catalogue des devises est
celui de la plateforme, il n'y a rien à cloisonner. L'exemption fermait la question
du **filet** et laissait explicitement ouverte celle de l'**accès**. Ce lot la ferme.

### ⚠️ Et d'abord : je m'étais trompé sur l'ampleur du défaut

J'ai écrit, dans la PR #122, dans trois sections de cette cartographie et dans
`CLAUDE.md`, qu'« une société qui renomme une devise la renomme **pour tout le
monde** ». **C'était faux comme affirmé.**

La mesure, faite avant de coder :

```
[droits LOCATAIRE dans la table permissions : 57]
[currency_update parmi eux ? NON]
[locataire avec TOUS ses droits -> HTTP 302]
```

`currency_read/create/update/delete` n'existent que dans `supperAdminPermissions()`,
qui sème la table `SuperAdminPermission`. Ils sont de surcroît **commentés** dans
`AdminPermissions()` du `UserSeeder`. Un administrateur de locataire portant *tous*
ses droits recevait donc déjà un refus.

Les trois occurrences sont rectifiées sur place plutôt que réécrites en silence.

### Ce qui était vrai, et qui reste le motif du lot

> La garantie était **de la donnée**, pas de la **structure**.

Les six routes vivaient sous `admin/` avec `panel:back-office` — donc dans le
panneau du **locataire**. Seul le contenu des semences l'en tenait écarté. Ajouter
`currency_*` à la table `permissions` — une ligne de semence, une écriture manuelle,
un correctif distrait — aurait ouvert la surface sans que rien ne s'en aperçoive.

### ⚠️ Un doublon mort, et un fichier mal nommé

Le relevé a trouvé deux choses que la lecture seule n'aurait pas données :

1. Les six routes étaient déclarées **deux fois** — dans `routes/web.php` **et** dans
   `routes/superadmin.php`, à l'identique (même URI, même nom, même contrôleur).
   `superadmin.php` étant chargé **après** `web.php`, sa déclaration **écrasait**
   l'autre dans la collection. Le bloc de `web.php` était donc **mort**, et
   `route:list` ne montrait bien que six routes, pas douze.

2. ⚠️ `routes/superadmin.php` **n'est pas** le fichier du super-administrateur. Son
   groupe `super-admin` ne couvre que les lignes 72→101 (plans, sociétés) ; tout le
   reste — utilisateurs, réglages généraux, SMS, sauvegarde, et currency — vit dans
   un groupe **`admin/` avec `panel:back-office`** déclaré dans ce même fichier.
   J'avais d'abord lu « le super-admin a déjà ses routes currency » : c'était une
   erreur de lecture, corrigée par l'analyse des accolades puis par `route:list`.

### Le déplacement, dans l'idiome du projet

`WebPanelSeparationTest` exige `'admin/' => 'panel:back-office'` pour **toute** route
sous ce préfixe. Poser `panel:super-admin` sur `admin/currency` aurait donc cassé
l'invariant de **S41** — c'est le préfixe d'URI qui dit le panneau, pas le nom de
route. La seule forme correcte était de **déplacer l'URI** :

| | avant | après |
|---|---|---|
| URI | `admin/currency/*` | **`super-admin/currency/*`** |
| Panneau | `panel:back-office` (ADMIN + SUPER_ADMIN) | **`panel:super-admin`** (SUPER_ADMIN seul) |
| Déclarations | **deux** (dont une morte) | **une** |

Les noms de route (`currency.index`…) sont inchangés : les vues et leurs
`route('currency.*')` continuent de fonctionner sans retouche. Seuls les motifs
`request()->is('admin/currency*')` ont suivi.

L'entrée « devises » **quitte le menu du locataire** : elle ne s'y affichait jamais
(faute de droit) mais annonçait un écran que ce menu n'a pas à proposer.

### ⚠️ Le contrôle positif était creux, une fois de plus

`assertRedirect()` seul ne prouve rien sur ce chemin : un échec de **validation**
redirige aussi. C'est exactement ce qui s'est produit au premier jet — `position`
doit être numérique et j'envoyais `'left'`, donc le super-administrateur « réussissait »
sans que rien ne soit écrit. Le cas exige désormais `assertSessionHasNoErrors()`,
la **destination** du redirect, **et** la valeur en base.

### Vérification

| | |
|---|---|
| `CurrencyPanelScopeTest` | 4 cas, 9 assertions |
| `panel:super-admin` → `panel:back-office` | **rouge** |
| les routes remises sous `admin/` | **rouge** |
| `WebPanelSeparationTest` | vert — l'invariant S41 tient |
| Les deux filets | mordent aux deux bouts : nouvelles URI non classées **et** anciennes déclarations orphelines |

Le locataire est désormais refusé **par le panneau**, et le test le prouve en lui
accordant de force `currency_update` : il reste dehors. Le refus ne dépend plus de
ce qu'on lui accorde, mais de **qui il est**.

## ✅ S56 — le filet de la surface hors requête (2026-09-24)

### Ce que les quatre filets ne regardaient pas

Les deux arriérés d'isolation sont clos (171 → 0 en S35, 90 → 0 en S54). Mais les
**quatre** filets de couverture — `WebIsolationCoverageTest`,
`BodyIdentifierCoverageTest`, `IsolationCoverageTest`,
`WebAdminPermissionCoverageTest` — appellent tous `Route::getRoutes()`.

> Ce qui tourne **sans requête HTTP** leur est entièrement invisible.

Et c'est précisément là que vit une famille de défauts que ce dépôt avait déjà
**nommée** sans jamais l'outiller : **F4**.

### F4, mesuré avant d'être écrit

`scopeCompanywise()` est `where('company_id', settings()->id)`. Et `settings()`
résout la société par le **sous-domaine** ou l'**utilisateur connecté** ; faute des
deux, elle retombe sur la société **1** :

```
[societes actives : 1, 2]
[settings()->id hors requete : 1]
```

Une commande qui écrit `Model::companywise()` ne traite donc **qu'une** société,
sans erreur ni alerte. La société 2 est silencieusement invisible.

### Elle a déjà mordu deux fois — et rien n'empêchait la troisième

| Où | Ce que ça donnait |
|---|---|
| `invoice:generate` | bouclait sur `merchantIdlist()` filtré par `settings()->id` : **seule la société 1** avait ses relevés, les autres transporteurs **jamais** |
| `SendSms` | aurait envoyé au nom du mauvais transporteur si le job ne portait pas sa société |

Les deux sont corrigés depuis, et leurs docblocs **expliquent** la règle. Mais
aucun filet ne regardait cette surface : la troisième fois n'attendait qu'un
contributeur pressé.

### L'état relevé : la surface est saine

| | |
|---|---|
| commandes Artisan | 12 |
| jobs | 2 |
| observateurs | 7 |
| notifications | 2 |
| résolutions ambiantes | **0** |

Les deux commandes qui touchent des soldes (`MerchantBalanceDriftCommand`,
`UndebitedParcelsCommand`) itèrent volontairement **tous** les marchands, puis
dérivent tout de `$marchand->id` : c'est la forme correcte — la société vient de
la **ligne**, jamais de l'ambiance. Les sept observateurs dérivent de l'instance.

> C'est le bon moment pour poser un filet : quand la surface est saine, pas quand
> elle ne l'est plus.

### ⚠️ Le dépouillement des commentaires n'est pas une précaution théorique

`Invoice` et `SendSms` **citent** `settings()` dans leur docbloc, pour expliquer le
piège. Un filet qui lirait le fichier brut signalerait donc exactement **les deux
fichiers qui documentent la règle** — il accuserait la documentation au lieu du
code.

La source passe au **tokenizer** PHP et perd ses `T_COMMENT` / `T_DOC_COMMENT`.
Le sabotage le confirme : retirer ce dépouillement rend le filet **rouge**.

### ⚠️ Un test sans assertion ne prouve rien

`test_no_exemption_points_to_a_file_that_no_longer_exists` bouclait d'abord sur
`EXEMPTEES` — vide à l'ouverture. Une boucle sur une liste vide n'exécute aucune
assertion : PHPUnit l'a marqué **risky**, et le test ne mesurait rien.

Réécrit en comparaison d'**ensembles**, il assertit une fois quoi qu'il arrive.
C'est la même famille de piège que les assertions d'absence des lots S51 à S55,
rencontrée pour la sixième fois — et cette fois c'est PHPUnit qui l'a dit, pas le
sabotage.

Un témoin garde aussi l'**énumération** elle-même : un répertoire renommé, un
glob cassé, et le filet passerait au vert sans rien examiner. Il exige au moins
20 fichiers et la présence nommée des deux que F4 a mordus.

### Vérification

| Sabotage | Verdict |
|---|---|
| une commande appelant `settings()` | **rouge** |
| une commande appelant `companywise()` | **rouge** |
| une commande appelant `auth()` | **rouge** |
| le dépouillement des commentaires retiré | **rouge** |
| la prémisse F4 faussée | **rouge** |

`OffRequestScopeCoverageTest` : 4 cas, 9 assertions, `EXEMPTEES` **vide**.

### Les cinq filets, désormais

| Filet | Surface | État |
|---|---|---|
| `IsolationCoverageTest` (S7) | routes `/api/v10` à identifiant | tenu |
| `WebIsolationCoverageTest` | routes web à paramètre d'URL | **arriéré 171 → 0** |
| `BodyIdentifierCoverageTest` (S38) | écritures à identifiant de corps | **arriéré 90 → 0** |
| `WebAdminPermissionCoverageTest` (S44) | droits des routes `admin/*` | tenu |
| `OffRequestScopeCoverageTest` (**S56**) | **hors requête** : commandes, jobs, observateurs | **ouvert à 0** |

## ✅ S57 — l'instrument affiné, et ce qu'il a désigné (2026-09-24)

### La dette de S47, payée

S47 avait relevé « ~250 occurrences de lectures non scopées », jugé l'instrument
**trop bruyant pour décider**, et remis son affinage :

> « il faudrait le raffiner — ne retenir qu'une lecture dont **aucune** garde ne
> domine le chemin — avant d'en tirer un lot. C'est un chantier en soi. »

Ce lot est ce chantier. L'outil vit désormais dans
`docs/outils/instrument-lectures-nues.py`.

| Critère | Occurrences |
|---|---|
| brut : toute lecture non scopée d'un modèle `scopeCompanywise` | **~570** |
| + l'identifiant vient de la **requête** | 53 |
| + connaître les **aides de garde du projet** | **43** |

### ⚠️ Le critère qui rend l'instrument honnête

`contrepartieHorsPerimetre()` (S48) ne contient pas la chaîne `companywise(`.
L'instrument brut **accusait** donc les dépôts de recette, dépense et salaire —
gardés depuis S48.

> Un instrument qui cherche une **forme** ne trouve pas une **règle**.

C'est la leçon de S52, appliquée cette fois à mon propre outil. Il connaît
maintenant les cinq aides que le projet s'est données, plus `abort_if`/`abort_unless`.

### Le tri des 43

| Nature | Occ. | Suite |
|---|---|---|
| derrière le drapeau `online_payout` (**D10**, à `false`) | 8 | routes non enregistrées : exemptées de fait |
| flux d'authentification globaux par nature (OTP e-mail/mobile) | 4 | un compte se retrouve par son e-mail, sans société connue |
| **vrais défauts** | **8** | **corrigés et prouvés ci-dessous** |
| reste à trancher | 23 | inscrites pour le prochain lot |

### ⚠️ La divulgation la plus grave de la série

`ParcelRepository::parcelSearchs()` n'avait **aucun** périmètre. Elle rendait les
colis de **toutes** les sociétés, avec `customer_name`, `customer_phone` et
`customer_address` : les **données personnelles** des clients d'un transporteur
concurrent, sur une simple recherche. Même nature que le défaut de **S28**.

Et les deux autocomplétions — `ExpenseUsers`, `IncomeUsers` — rendaient le **nom
et l'identifiant** de tout utilisateur de toute société. L'annuaire du personnel
d'en face, à la frappe. L'écriture qui suit était gardée depuis S48 ; la
**divulgation** ne l'était pas.

### ⚠️ Et le piège du `orWhere`

Poser `companywise()` devant cette chaîne **ne l'aurait pas scopée** :

```php
Parcel::companywise()->where(A)->orWhere(B)
// SQL : company_id = X AND A OR B   ← le OR sort du périmètre
```

Un colis d'en face correspondant sur `customer_phone` serait encore rendu. Le
groupe de `OR` est donc **enfermé** dans une fermeture.

`test_the_parcel_search_scope_survives_the_or_branches` vise précisément une
branche `orWhere`, et le sabotage qui retire la **fermeture en gardant
`companywise()`** est **rouge** : la correction naïve est prouvée insuffisante.

### Les soldes lus avant que le dépôt ne refuse

S51 avait corrigé `paymentStore()` dans les deux contrôleurs de versement. Trois
méthodes voisines portaient le même défaut et n'étaient pas couvertes :

| Méthode | Lecture nue |
|---|---|
| `HubPaymentController::processed` | le **versement** d'en face *et* le **compte** d'en face |
| `HubPaymentController::update` | le compte d'en face |
| `MerchantmanagePaymentController::update` | le **marchand** d'en face *et* son compte |

### ⚠️ Deux sabotages ont menti, de deux façons différentes

**Un vert creux.** Le premier test du versement d'entrepôt ne montait pas les
routes du locataire : l'appel rendait **404**, et les deux assertions d'absence
passaient sur du vide. Le sabotage est resté **vert**. Corrigé par le montage et
par un **ancrage** qui exige la preuve que le contrôleur a tourné.

**Un vert qui n'était même pas un sabotage.** Sur `MerchantmanagePaymentController`,
mon ancre de sabotage ne correspondait à rien (échappement de guillemets dans le
shell) : le fichier n'avait **pas changé**, et le « vert » ne mesurait rien. C'est
exactement la leçon de **S44** — vérifier que le sabotage a modifié le fichier, et
**là**. Repris avec un script qui l'assertit, puis **rouge**.

### Ce qu'aucun filet ne couvre — le chantier suivant

`parcel/specific/search` échappait aux **trois** filets :

| Filet | Pourquoi il ne la voyait pas |
|---|---|
| `WebIsolationCoverageTest` | n'énumère que les routes à **paramètre d'URL** |
| `BodyIdentifierCoverageTest` | n'énumère que les **écritures** (`ECRITURES = POST/PUT/PATCH/DELETE`) |
| `WebAdminPermissionCoverageTest` | mesure les **droits**, pas le périmètre |

> ⚠️ Un **GET sans paramètre d'URL** dont l'identifiant est un terme de recherche
> n'est couvert par **aucun** filet d'isolation.

Il y en a une **quarantaine** sous les trois panneaux. C'est un constat mesuré, pas
une correction : c'est le chantier suivant, et il est nommé ici pour qu'il ne se
perde pas.

### Vérification

| Sabotage | Verdict |
|---|---|
| recherche dépenses, `companywise()` retiré | **rouge** |
| recherche recettes, `companywise()` retiré | **rouge** |
| recherche colis, `companywise()` retiré | **rouge** |
| **la fermeture retirée** (`companywise()` gardé) | **rouge** |
| versement entrepôt `processed`, gardes retirées | **rouge** |
| versement entrepôt `update`, garde retirée | **rouge** |
| versement marchand `update`, garde retirée | **rouge** |

`SearchAndBalanceDisclosureTest` : 7 cas, 21 assertions.

---

## S58 — le sixième filet : la surface de recherche

Le chantier nommé à la fin de S57 est ouvert et fermé : un **GET sans paramètre
d'URL** dont l'identifiant voyage dans la chaîne de requête n'était couvert par
aucun des cinq filets. Ce lot construit le sixième, et le défaut qu'il a trouvé
en chemin.

### La mesure d'abord

| Mesure | Valeur |
|---|---|
| routes `GET` sans paramètre d'URL servies par un contrôleur de l'application | **203** |
| dont **lisent un champ de la requête** | **46** |
| dont un `*_id` | 26 |
| dont un **terme** (non `*_id`) | 38 |

L'estimation « une quarantaine » de S57 était juste.

### Pourquoi le trou était structurel

Chacun des cinq filets excluait cette forme **par sa propre définition**, pas par
accident :

| Filet | Ce qu'il énumère | Pourquoi il ne voyait pas cette forme |
|---|---|---|
| `IsolationCoverageTest` (S7) | l'API v10 | ce sont des routes web |
| `WebIsolationCoverageTest` (S28) | les routes **à paramètre d'URL** | celles-ci n'en ont aucun |
| `BodyIdentifierCoverageTest` (S38) | `POST`/`PUT`/`PATCH`/`DELETE` | celles-ci sont des `GET` |
| `WebAdminPermissionCoverageTest` (S44) | les droits exigés sous `admin/*` | le droit était bien exigé |
| `OffRequestScopeCoverageTest` (S56) | la surface **hors** requête | celles-ci sont dans une requête |

### Le défaut : un mot manquant, des coordonnées bancaires

Un seul vrai défaut dans les 46, et il tenait à une asymétrie entre deux écrans
jumeaux :

| écran d'impression | requête |
|---|---|
| `bank-transaction/filter/print` | `BankTransaction::companywise()->whereIn('id', $request->ids)` |
| `fund-transfer/search/flter/print` | `FundTransfer::whereIn('id', $request->ids)` — **nue** |

La vue d'impression rend, **pour le compte source et le compte destinataire** :
numéro de compte, banque, agence, type, passerelle, mobile, plus le **nom et
l'e-mail du titulaire**, et le montant. Il suffisait à un administrateur
disposant de `fund_transfer_read` chez lui de poser des `ids[]` dans l'URL pour
imprimer les coordonnées bancaires d'un transporteur concurrent.

### Ce que la mesure a corrigé chez moi

**Onze de mes treize alertes étaient fausses.** Mon marqueur de garde cherchait
`companywise(` ; or trois dépôts bornent en écrivant `where('company_id',
settings()->id)` **à la main** — `HubRepository::filter`, `UserRepository::filter`,
`DeliveryManRepository::filter`. Reconnaître la forme littérale a fait tomber les
« nues » de **13 à 2**. C'est la même leçon qu'en S57 : un instrument qui cherche
une **forme** ne trouve pas une **règle**.

**Et un marqueur de garde fautif absout au lieu d'alerter.** J'avais ajouté
`'company_id' => settings()->id` à la liste des gardes de l'instrument de S57.
C'est faux : cette forme n'est pas une portée, c'est un **champ écrit** dans un
tableau de création. Elle absolvait **neuf** lectures nues des passerelles de
paiement — dont `Merchant::find($request->merchantId)` dans `stripePost()`, qui
reste nue. Je ne l'ai vu qu'en regardant **ce que le motif retirait**, une par
une. Motif supprimé, raison inscrite dans l'instrument.

> Un marqueur de garde qui se trompe ne fait pas du bruit : il fait **silence**.

**Effet mesuré du reste de l'affinage sur l'instrument de S57 : aucun** — 35
occurrences avant, 35 après. Les trois dépôts concernés n'y figuraient pas, cet
instrument cherchant une *lecture* et non un filtre `like`. Le motif est gardé
parce qu'il est juste, pas parce qu'il a trouvé quelque chose.

### Un niveau construit, mesuré, puis abandonné

J'ai outillé un **troisième niveau** de résolution — les fonctions d'aide globales
de `app/Http/Helper/Helper.php` — en pensant qu'un contrôleur pouvait y cacher ses
lectures. Mesure : **0** route n'est vue par ce niveau seul. Il n'est pas dans le
filet. Un mécanisme qui ne change rien ne se garde pas au motif qu'il a coûté du
travail.

(Les deux aides concernées, `parcelsStatus()` et `idWiseParcels()`, écrivent bien
`Parcel::companywise()->whereIn('id', …)`.)

### Le filet

`SearchSurfaceCoverageTest` — 46 routes recensées : **1 prouvée**, **9 exemptées
avec motif**, **36 à l'arriéré**, plafond `36`. Il ne juge pas et ne cherche
aucune garde ; il exige qu'aucune route de cette forme ne soit ajoutée sans qu'on
ait écrit ce qu'on fait de sa portée. Deux témoins le gardent honnête : le second
niveau (`GET admin/users/filter`, dont le contrôleur ne lit rien lui-même) et la
forme fondatrice.

> ⚠️ **Lu n'est pas prouvé.** Plusieurs routes de l'arriéré ont été lues pendant
> ce lot et paraissent bornées. Elles restent à l'arriéré : ce filet ne connaît
> que la preuve.

### Vérification

| Sabotage | Verdict |
|---|---|
| `FundTransfer::companywise()` retiré | **rouge** (2 tests) |
| une route retirée du recensement du filet | **rouge** |
| le **second niveau** du filet éteint | **rouge** (3 tests, dont son témoin) |

Le contrôle positif du test de portée vit **dans le même appel** : on demande
l'impression du virement de la société connectée **et** de celui d'en face. Si la
vue cesse de rendre quoi que ce soit, la présence tombe **avant** que l'absence ne
puisse passer pour une preuve. C'est la leçon de S53 — pour prouver une garde, il
faut que le chemin non gardé réussisse.

Suite complète : **999 tests, 45 874 assertions**, verte (delta `+8 / +26`,
exactement les deux fichiers de ce lot).

### Le chantier suivant

L'arriéré de ce filet : **36 routes**. Le mouvement est celui qui a mené les deux
autres arriérés à zéro (171 → 0, 90 → 0) — prouver ou motiver, une par une, en
baissant le plafond d'autant.

---

## S59 — la garde VOISINE : quand une garde en absout une autre

L'arriéré du filet S58 était le chantier nommé. En l'attaquant, j'ai trouvé plus
grave que les routes : **l'instrument lui-même était aveugle à sa propre cible**.

### Le défaut de l'instrument

`instrument-lectures-nues.py` (S57) écrivait :

```python
if any(g in corps for g in GARDES):
    continue          # une garde domine la methode
```

Sa granularité était donc la **méthode**. Or la famille de défauts que toute
cette série poursuit depuis S45 est précisément *« la ressource est gardée, le
second identifiant ne l'est pas »* — **dans la même méthode**. L'instrument ne
pouvait pas voir ce qu'il était censé chercher.

### Trois critères, mesurés un par un

| critère | ce qu'il exige | mesure |
|---|---|---|
| départ (S57) | une garde quelque part dans la méthode | 35 |
| **position** | la garde doit **précéder** la lecture | 37 (+2) |
| **identifiant** | elle doit porter sur le **même champ** | 46 (+9) |
| **cartes des aides** | un appel `*HorsPerimetre()` couvre les champs de sa carte | **39** |

Le critère « identifiant » seul produisait **8 faux positifs** : les aides du
projet (`contrepartieHorsPerimetre($request)`) ne contiennent pas le nom du
champ, c'est un **appel**. L'instrument lit donc maintenant les cartes
`'champ' => Modele::class` dans `app/Traits/` — il ne les recopie pas. Le jour où
une contrepartie s'ajoute, il la connaît ; le jour où elle en sort, il recommence
à signaler les lectures qu'elle couvrait.

**35 → 39, sans en perdre une seule.** Les quatre nouvelles sont réelles.

### Les quatre lectures

| lecture | ce qu'elle faisait | rendu ? |
|---|---|---|
| `ParcelController::store` → `Merchant::find($request->merchant_id)` | **oracle sur le portefeuille** d'un marchand d'en face, + **500** sur identifiant inconnu | oui |
| `ReportsRepository::MHDreports` → `Hub::find($request->hub_id)` | résout l'entrepôt d'une autre société | non |
| `ReportsRepository::MHDreports` → `DeliveryMan::find(...)` | idem, sur le livreur | non |
| `ReportsRepository::MHDprint` → `DeliveryMan::find(...)` | idem — **chemin mort** | non |

**La première est la vraie.** Elle est de la famille de S51 : la lecture nue
**précède** le dépôt, donc elle renseignait avant que la garde de S46 ne refuse
l'écriture. Les deux lignes suivantes lisent `wallet_use_activation` puis
comparent à `wallet_balance`, et le message « low balance » rend la réponse
différente selon le solde — en faisant varier `cash_collection` on encadrait le
solde d'un marchand d'un transporteur concurrent. Et hors périmètre `find()`
rendait `null`, donc **500** au lieu d'un refus (famille S15).

> ⚠️ **Les trois autres ne sont rendues par aucune vue vivante.** Ce ne sont pas
> des divulgations constatées : ce sont des pièges armés. Et `MHDprint()` n'est
> appelée que par `MHDPrintPage()`, qui n'est déclarée dans **aucun** fichier de
> routes — chemin mort, corrigé quand même : une méthode morte se réveille en une
> ligne de route, et elle se réveillerait avec sa faille.

### Pourquoi l'instrument ne voyait pas la première

`ParcelController::store()` écrit à sa cinquième ligne
`Parcel::companywise()->count()` — un **quota d'abonnement**, qui n'a rien à voir
avec ce marchand. Cette garde-là absolvait toute la méthode.

### Un vert creux, attrapé par son ancrage

Les trois cas HTTP repartaient sur `Something went wrong!` à la **première** ligne
de `store()`, sans jamais atteindre celle qu'ils prétendaient mesurer :
`souscrireLeLocataire()` satisfait `subscriptionCheckMiddleware` (qui passe par
`Auth::user()->subscription`) mais **pas** `settings()->subscription`, qui est un
`belongsTo` sur la colonne `general_settings.subscription_id` que rien ne remplit.

C'est l'ancrage sur un message **précis** qui l'a révélé. Une assertion sur
« ça redirige » serait passée au vert.

### L'arriéré du filet S58 : 36 → 32

| route | comment elle sort |
|---|---|
| `GET admin/reports/mhd-reports` | prouvée — ses deux lectures nues corrigées et sabotées |
| `GET admin/users/filter` | prouvée |
| `GET admin/hubs/filter` | prouvée |
| `GET admin/deliveryman/filter` | prouvée |

Les trois `filter` avaient été **lus** en S58 et jugés bornés. Ils restaient à
l'arriéré parce que **lu n'est pas prouvé**. Ils en sortent maintenant par un test
qui atteint la ligne d'en face et qui tombe si la garde saute.

### Vérification

| Sabotage | Verdict |
|---|---|
| `Merchant::companywise()` retiré (store) | **rouge** |
| le refus sur marchand nul retiré (store) | **rouge** |
| `Hub::companywise()` retiré (MHDreports) | **rouge** |
| `DeliveryMan::companywise()` retiré (MHDreports) | **rouge** |
| `DeliveryMan::companywise()` retiré (MHDprint, chemin mort) | **rouge** |
| `where('company_id')` retiré de `UserRepository::filter` | **rouge** |
| idem `HubRepository::filter` | **rouge** |
| idem `DeliveryManRepository::filter` | **rouge** |

Deux sabotages n'ont **pas** été appliqués au premier jet, et le script l'a dit
au lieu de saboter en silence : une ancre à 12 espaces est un **sous-ensemble**
de la même ligne indentée à 16, et le motif du dépôt des comptes se répète quatre
fois. Reprise avec des ancres uniques (leçon S44, quatrième application).

Suite complète : **1008 tests, 45 904 assertions**, verte (delta `+9 / +30` —
les 9 cas du nouveau fichier et les 4 assertions que le filet S58 gagne en
voyant sa liste `PROUVEES` s'allonger).

### Le chantier suivant

L'arriéré du filet S58 : **32 routes**. Et, côté instrument, **35 occurrences**
après les quatre corrections — dont 8 derrière `online_payout` (D10, à `false`)
et 4 flux d'authentification globaux.

---

## S60 — le module comptable, et le `orWhere` qui recommence

Suite de l'arriéré du filet S58 : **32 → 21**.

### Le défaut : le piège du `orWhere`, deuxième occurrence

```php
FundTransfer::companywise()
    ->whereHas('fromAccount', ...)
    ->orWhereHas('toAccount', ...)     // ← sort du périmètre
```

En SQL : `company_id = X AND fromAccount… OR toAccount…`. Un virement d'une
**autre** société dont le compte **destinataire** correspondait à la recherche
remontait.

> ⚠️ **`companywise()` était déjà là.** Ce n'est pas une garde manquante, c'est
> une garde qui ne couvrait pas ce qu'elle semblait couvrir. Le sabotage qui
> retire la **fermeture** en **gardant** `companywise()` est **rouge** : la
> correction naïve est prouvée insuffisante, exactement comme en S57.

Et c'est l'écran **voisin** de celui que S58 avait corrigé — même table, mêmes
données, l'autre porte. `fund_transfer/index` rend le numéro de compte, la
banque, l'agence, le mobile, le nom et l'e-mail du titulaire, **plus le solde et
le solde d'ouverture** des deux comptes.

`BankTransactionRepository::filterSearch()` fait cela correctement depuis
toujours : tout son `OR` vit dans un seul `where(fermeture)`. La dissymétrie est
un oubli, pas une intention.

### ⚠️ L'instrument ne voit pas ce défaut, et ne le verra pas

Il reste à **35** occurrences après ce lot. Il cherche des lectures de la forme
`Model::find($request->x)` ; le piège du `orWhere` est une **structure de
requête**, pas une lecture nue. Deux outils, deux familles : l'instrument pour
les lectures, la **relecture des chaînes `orWhere`/`orWhereHas`** pour celle-ci.

### Trois vers creux, tous attrapés par leur contrôle positif ou leur sabotage

**1. La date des recettes.** `IncomeRepository::filter` traite `date` comme une
date **unique** (`strtotime`). Une plage « …To… » y devient `1970-01-01` et ne
rend **aucune** ligne. Le contrôle positif est tombé et l'a dit.

**2. Le marqueur de présence était dans la liste déroulante.** Ces écrans rendent
un `<select>` des comptes qui contient `account_holder_name`, `account_no` **et**
`branch_name` de tous **nos** comptes, résultat ou pas. Un contrôle positif pris
sur le compte passe donc au vert **avec zéro ligne rendue**.

**3. Et c'est le sabotage qui l'a prouvé.** Le test des dépenses était **vert**,
et son sabotage aussi : `companywise()` retiré, rien ne tombait. Corrigé en
prenant pour marqueur le **nom de l'auteur** de la dépense — la liste ne rend
`$account->user->name` que pour les comptes en espèces, et les nôtres sont
bancaires. Sabotage repris : **rouge**.

> Un contrôle positif ne vaut que si son marqueur ne peut venir **que** d'une
> ligne de résultat. Sur ces écrans, presque tout le reste vient du formulaire.

### Quatre routes motivées

| route | motif |
|---|---|
| `GET dashboard` | ne lit que des **dates** ; chaque requête est bornée par l'identité de session, ou relève de la vue plateforme du super-administrateur |
| `GET facebook/login` | ⚠️ **faux positif de la détection** (ci-dessous) |
| `GET google/login` | idem |
| `GET super-admin/reporting` | surface super-administrateur, reporting SaaS en lecture seule |

### ⚠️ Un faux positif du filet, et il faut le nommer

Le filet cherche la chaîne `$request->`. Or
`MerchantRepository::socialSignupStore($user, ...)` nomme son paramètre
`$request` alors qu'il reçoit **l'objet utilisateur de Socialite**, pas la
requête HTTP. Les champs lus (`id`, `name`, `email`, `avatar_original`) viennent
du fournisseur OAuth.

> Le filet reconnaît un **nom de variable**, pas la requête. C'est sa limite, et
> elle est désormais écrite dans l'exemption plutôt que découverte deux fois.

### Vérification

| Sabotage | Verdict |
|---|---|
| **la fermeture retirée, `companywise()` gardé** | **rouge** |
| `companywise()` retiré (recherche virement) | **rouge** |
| `companywise()` retiré (filtre virement) | **rouge** |
| `companywise()` retiré (filtre comptes) | **rouge** |
| `companywise()` retiré (recherche transactions) | **rouge** |
| `companywise()` retiré (impression transactions) | **rouge** |
| `companywise()` retiré (filtre recettes) | **rouge** |
| `companywise()` retiré (filtre dépenses) | **rouge** *(vert au premier jet — voir ci-dessus)* |

Suite complète : **1015 tests, 45 936 assertions**, verte (delta `+7 / +32` —
les 7 cas du nouveau fichier et les 11 assertions que le filet gagne en voyant
ses listes s'allonger).

### Le chantier suivant

L'arriéré du filet S58 : **21 routes** — la famille des colis (4), celle des
rapports (5), le panneau marchand (5) et sept isolées.

---

## S61 — le septième filet : le `OR` qui sort du périmètre

Arriéré du filet S58 : **21 → 17**.

### La famille que rien ne surveillait

```
Model::companywise()->where(A)->orWhere(B)
SQL : company_id = X AND A OR B          ← le OR de PREMIER NIVEAU s'échappe
```

`AND` lie plus fort que `OR`. Un `orWhere` posé **au même niveau** que la portée
la neutralise pour toute la branche de droite.

Elle a mordu **deux fois**, et les six filets plus l'instrument l'ont tous
manquée :

| | |
|---|---|
| **S57** | `ParcelRepository::parcelSearchs` — nom, téléphone, adresse des clients de **toutes** les sociétés |
| **S60** | `FundTransferRepository::fundTransferSearch` — coordonnées bancaires **et soldes** |

> Dans les deux cas **`companywise()` était là**. Ce n'est pas une garde
> manquante : c'est une garde que la **structure de la requête** annule. Aucun
> filet cherchant une garde *absente* ne pouvait la voir, et l'instrument des
> lectures nues cherche une **lecture**, pas une structure.

### `OrScopeEscapeCoverageTest`

Il repère un appel de portée dans chaque méthode, puis avance en comptant
parenthèses et accolades : un `orWhere` à la **même profondeur** est signalé ; un
`orWhere` plus profond — dans une fermeture, dans un `whereHas` — est **enfermé**,
donc sans danger. C'est exactement la correction appliquée en S57 et en S60.

**Quatre occurrences, toutes délibérées** : `(company_id = X OR id = 1)` ajoute la
catégorie de livraison de la **plateforme** au catalogue de la société —
`categorys` ne porte aucune `company_id` (S32), et `ParcelCatalogScopeTest`
l'établit depuis S46. Le filet ne juge pas : il exige que chacune soit écrite
avec sa raison.

Un instrument en ligne de commande l'accompagne :
`docs/outils/instrument-or-hors-perimetre.py`.

### Le témoin qui compte

Le filet embarque un témoin de **détection** : il soumet à son analyse les deux
formes côte à côte, celle qui s'échappe et celle qui est enfermée, et exige qu'il
voie la première **et ignore la seconde**.

> Sans lui, une analyse qui signalerait *tout* `orWhere` passerait pour
> fonctionnelle tout en étant inutilisable — et une analyse qui n'en signalerait
> aucun passerait pour rassurante.

Un second témoin exige que les **commentaires** soient dépouillés : les deux
corrections de cette famille *citent* la chaîne fautive pour expliquer le piège.
Sans dépouillement, le filet accuserait précisément le code qui documente la
règle (leçon S56).

### La famille des colis, prouvée

Quatre routes sortent de l'arriéré : `parcel/specific/search`, `parcel/filter`,
`parcel/multiple/print/label`, `parcel/bulkassign/print`. Toutes étaient bornées
(S38, S57) ; elles restaient à l'arriéré parce que **lu n'est pas prouvé**.

### Vérification

| Sabotage | Verdict |
|---|---|
| **le défaut de S60 réintroduit** (fermeture retirée) | **rouge — le filet le voit** |
| une occurrence retirée de `DELIBEREES` | **rouge** |
| le dépouillement des commentaires éteint | **rouge** |
| `companywise()` retiré (recherche colis) | **rouge** |
| `companywise()` retiré (filtre colis) | **rouge** |
| `companywise()` retiré (étiquettes) | **rouge** |
| `companywise()` retiré (lot d'affectation) | **rouge** |

Le premier est le plus important : **le filet aurait attrapé S60 avant sa
livraison.**

Suite complète : **1024 tests, 45 958 assertions**, verte (delta `+9 / +22`).

### Le chantier suivant

L'arriéré du filet S58 : **17 routes** — les rapports (6), le panneau marchand
(5), six isolées.

---

## S62 — les six écrans de rapport

Arriéré du filet S58 : **17 → 11**. Les six étaient bornés ; ils restaient à
l'arriéré parce que **lu n'est pas prouvé**.

### Deux niveaux de preuve, et pourquoi

Quatre écrans rendent une **identité** — nom du client, nom du salarié — et se
prouvent par appel HTTP : c'est la donnée qui fuirait.

`parcel-filter-reports` et `parcel-filter-total-summery` ne rendent que des
**compteurs agrégés**. Une assertion sur un chiffre dans du HTML serait fragile
(« 1 » et « 7 » sont partout dans une page). Ils sont prouvés au niveau du
**dépôt**, en vérifiant que la collection rendue contient notre colis et pas
celui d'en face — l'idiome de `BackOfficeMoneyScopeTest` (S30).

### Un vert creux, encore, et le même piège qu'en S60

Le test du rapport de paie était **vert**, et son sabotage aussi. Deux causes
empilées :

1. il ne semait que des `Salary`, or la vue boucle sur **`SalaryGenerate`** —
   deux tables dans le même rapport, la page n'avait donc aucune ligne ;
2. le contrôle positif passait quand même, parce que le nom du salarié figurait
   dans la **liste déroulante des utilisateurs**.

Corrigé en semant les deux tables et en prenant pour marqueur de présence un
**montant** : la liste rend les noms, elle ne rend aucun montant.

### Une portée réelle mais invisible à l'écran

La branche des **versements** (`Salary`) du rapport de paie n'est pas observable
depuis la vue : les versements y sont appariés au bulletin par `user_id`, et un
`user_id` étranger ne correspond à aucune de nos lignes. Sa portée est de la
défense en profondeur — réelle, mais qu'aucun appel HTTP ne peut mettre en
évidence. Elle est donc prouvée sur la **collection** que le dépôt rend.

> Une garde dont on ne peut pas voir l'effet n'est pas une garde inutile. Mais
> elle doit être prouvée là où elle se voit, pas là où c'est commode.

### Trois pièges de fixture, tous dits par le test avant moi

| | |
|---|---|
| `salaries` n'a pas de colonne `salary_date` | le rapport filtre sur `created_at` ; `salary_date` n'est qu'un **paramètre de requête** |
| `salaries.account_id` est `NOT NULL` | sans compte, l'insertion échoue et le test tombe pour une raison qui n'est pas la portée |
| `month` est au format `Y-m` | la vue fait `Carbon::createFromFormat('Y-m', …)` ; « September » lève |

### Vérification

| Sabotage | Verdict |
|---|---|
| `companywise()` retiré (rapport rentabilité) | **rouge** |
| `companywise()` retiré (rapport paie, branche `SalaryGenerate`) | **rouge** |
| `companywise()` retiré (rapport paie, branche `Salary`) | **rouge** *(vert au premier jet)* |
| `companywise()` retiré (impression paie) | **rouge** |
| `companywise()` retiré (filtre des bulletins) | **rouge** |
| `companywise()` retiré (rapport des colis) | **rouge** |
| `companywise()` retiré (récapitulatif total) | **rouge** |

Suite complète : **1031 tests, 45 982 assertions**, verte (delta `+7 / +24`).

### Le chantier suivant

L'arriéré du filet S58 : **11 routes** — le panneau marchand (5), six isolées.

---

## S63 — l'arriéré du filet S58 est CLOS (11 → 0)

**Troisième arriéré d'isolation fermé**, après 171 → 0 (S35) et 90 → 0 (S54).
Celui-ci aura demandé six passes : **46 → 32 → 21 → 17 → 11 → 0**.

Aucun défaut dans ce lot : les onze étaient bornées. Elles restaient à l'arriéré
parce que **lu n'est pas prouvé**.

### Trois niveaux de preuve, selon ce que l'écran rend

| ce que l'écran rend | où se prouve la portée |
|---|---|
| une **identité** (nom du client, nom du marchand, n° de transaction) | appel HTTP |
| un **agrégat** (des compteurs) | la collection que le dépôt rend |
| un **fichier** (CSV en flux, tableur binaire) | le corps téléchargé |

⚠️ Trois natures de réponse dans le même fichier de test :
`streamedContent()` **lève** sur une réponse non diffusée, et `getContent()` rend
`false` sur un fichier binaire. Il faut demander à la réponse **ce qu'elle est**
avant de lui demander son corps.

### Et pour le panneau marchand, ce n'est pas la société

Ce que ces écrans cloisonnent est le **marchand** : le voisin est de la même
société. Les sabotages visent donc `Auth::user()->merchant->id`, pas
`companywise()`.

### Une correction de ma propre lecture

`merchantparcelTotalSummeryReports()` m'a paru ne borner que par société, alors
que son voisin ajoute le marchand connecté. **C'était faux** : le filtre
`merchant_id` est bien là, onze lignes plus bas, dans la fermeture. J'avais
conclu après neuf lignes, et je l'écris plutôt que de le taire.

### Quatre tests creux, tous démasqués par leur sabotage

| | |
|---|---|
| **grille des zones** | les deux barèmes portaient des **catégories différentes** : le filtre `category_id` suffisait à exclure l'étranger. Le test mesurait le filtre, pas la portée. Corrigé en leur donnant la **même** catégorie |
| **export marchand** | `$request->parcel_date !== ""` est **vrai quand le champ est nul** : l'écran prend la première branche, et j'avais saboté la seconde — celle qui ne sert jamais |
| **rapport marchand** | ne rend que des compteurs ; cherchait un n° de suivi dans la page |
| **portefeuille marchand** | rend le **n° de transaction**, pas le nom du marchand — contrairement à son voisin d'administration |

### Cinq pièges de fixture, tous dits par le test avant moi

`customs_alerts` n'a pas de colonne `reason` (c'est `message`) · `Invoice` vit
sous `Merchantpanel\` et sa clé est `invoice_id`, pas `invoice_no` · le relevé ne
lit pas `parcels_id` mais la table **`invoice_parcels`** · sans frais sur le
colis, `fees_ttc` vaut 0 et le journal SYSCOHADA n'émet **aucune** ligne · les
droits réels sont `parcel_read`, `payment_read`, `invoice_read`.

### Vérification — onze sabotages, tous rouges

alertes douanières · grille tarifaire · versements marchands · journal SYSCOHADA
· grille des zones *(vert au premier jet)* · colis du panneau marchand · rapport
du panneau marchand · export du panneau marchand *(vert au premier jet)* ·
portefeuille administration · portefeuille marchand · récapitulatif marchand.

Suite complète : **1042 tests, 46 023 assertions**, verte (delta `+11 / +41`).

### L'état des sept filets

| filet | surface | arriéré |
|---|---|---|
| `IsolationCoverageTest` (S7) | API v10 | — |
| `WebIsolationCoverageTest` (S28) | routes à paramètre d'URL | **0** (S35) |
| `BodyIdentifierCoverageTest` (S38) | écritures, identifiant dans le corps | **0** (S54) |
| `WebAdminPermissionCoverageTest` (S44) | droits sous `admin/*` | — |
| `OffRequestScopeCoverageTest` (S56) | hors requête (F4) | **0** à l'ouverture |
| `SearchSurfaceCoverageTest` (S58) | `GET` sans paramètre d'URL | **0** (S63) |
| `OrScopeEscapeCoverageTest` (S61) | le `OR` au niveau de la portée | — |

### Le chantier suivant

Les **35 occurrences** de `instrument-lectures-nues.py`, dont 8 derrière
`online_payout` (D10, à `false`) et 4 flux d'authentification globaux.

---

## S64 — neuf lectures nues, dont la plus lourde de la série

L'arriéré du filet S58 étant clos (S63), le chantier restant était la liste de
`instrument-lectures-nues.py` : **35 → 26**.

### Le défaut le plus lourd : de l'argent sorti d'un compte d'en face

`FundTransferRepository::store()` lisait **nûment** `from_account` et
`to_account`, puis débitait l'un et créditait l'autre. Un administrateur pouvait
**sortir de l'argent du compte bancaire d'un transporteur concurrent** — et la
ligne de virement enregistrée portait **notre** `company_id`, donc la victime ne
la voyait même pas dans son journal.

> ⚠️ La carte des contreparties de S48 ne couvrait pas ce cas : elle connaît
> `account_id`, pas `from_account` / `to_account`. **Une aide partagée ne couvre
> que les champs de sa carte** — la leçon de S46 et S47, sur les *noms* cette fois.

### Les huit autres

| lecture | ce qu'elle faisait |
|---|---|
| `IncomeRepository::hubCheck` ×3 | un AJAX rendait **en JSON** le marchand, le livreur ou l'entrepôt d'une autre société |
| `DeliveryTypeController::status` | **basculait** un réglage d'une autre société — une écriture, pas une lecture |
| `ReceivedFromDeliverymanController::store` + `update` | **oracle** sur le solde d'un livreur d'en face, avant le refus du dépôt |
| `SalaryGenerateController::store` | **oracle d'existence** sur les bulletins d'une autre société |

### ⚠️ Un `POST` que le filet S38 ne pouvait pas voir

`delivery-type/status` est une **écriture**, donc dans le domaine de
`BodyIdentifierCoverageTest`. Il lui a échappé parce que son identifiant
s'appelle **`key`**, et `estIdentifiant()` ne reconnaît que `id`, `ids`, `*_id`,
`*_ids`. Même angle mort que le **terme de recherche** qui avait donné le filet
S58 — et il reste ouvert : un identifiant au nom libre échappe encore à ce filet.

### Trois sabotages verts, et ce qu'ils ont appris

**Deux n'étaient pas des tests creux, mais des sabotages partiels.** Retirer
*une* des trois gardes du virement laissait le test vert : la portée restante
rendait `null`, le déréférencement levait, et le `catch` rendait `false` — **un
refus venu du mauvais endroit** (leçon S49). Avec les trois retirées ensemble, le
dépôt rend `1` : le virement est accepté, et le test est rouge.

> Quand plusieurs gardes couvrent le même chemin, la seule sabotage honnête les
> retire **ensemble** : sinon c'est la survivante qu'on mesure.

**Le troisième était bien creux.** Le livreur d'en face avait un solde de
`-50000`, or la condition qui déclenche l'avertissement est
`current_balance > -amount || current_balance == 0` : elle était fausse des deux
côtés, et le test passait sans rien mesurer. Corrigé avec un solde de `0`.

### Vérification

| Sabotage | Verdict |
|---|---|
| virement : **les trois gardes ensemble** | **rouge** |
| AJAX solde : le marchand / le livreur / l'entrepôt | **rouge** (×3) |
| bascule du réglage | **rouge** |
| encaissement : la garde du livreur | **rouge** *(vert au premier jet)* |
| pré-vérification du bulletin | **rouge** |

Suite complète : **1047 tests, 46 042 assertions**, verte (delta `+5 / +19`).

### Ce qui reste des 26

19 passerelles de paiement derrière `online_payout` (D10 à `false`, gating
établi par `OnlinePayoutModuleDisabledTest`) · 4 flux d'authentification
globaux (OTP e-mail / mobile) · `switchPlan` (super-administrateur) ·
`transferToHubMultipleParcel` (sûr **par l'ordre** : le dépôt garde le hub et
rend `false` avant que le contrôleur ne le relise) · une reprise d'encaissement.

---

## S65 — le huitième filet, et l'angle mort qu'un défaut a révélé

### Le filet S38 élargi, parce qu'il avait laissé passer quelque chose

`POST admin/delivery-type/status` est une **écriture**, donc dans le domaine de
`BodyIdentifierCoverageTest`. Elle basculait le réglage d'une autre société
(corrigé en S64). Elle lui a échappé parce que son identifiant s'appelle **`key`**,
et `estIdentifiant()` ne reconnaissait que `id`, `ids`, `*_id`, `*_ids`.

**Mesuré avant d'élargir.** Une version large (`_key$`, `_code$`, `reference`)
ajoutait **six** routes dont **quatre faux positifs** — `map_key` et
`fcm_secret_key` sont des *valeurs*, pas des identifiants. Resserré à `key` et
`slug` : **deux** routes, toutes deux classées (`delivery-type/status` prouvée,
`category/store` exemptée comme son jumeau `category/update`).

> On n'élargit que de ce que la preuve justifie.

⚠️ **L'angle mort n'est pas refermé** : un identifiant au nom libre — `from`,
`token`, `reference` — échappe encore. Ce filet reconnaît une **convention de
nom**, pas un rôle. C'est écrit dans son docbloc.

### `NakedReadCoverageTest` — le huitième filet

Il restait un **script**. Un script se lance ; un filet mord tout seul. Ce lot
porte l'analyse de `instrument-lectures-nues.py` dans un test, avec ses trois
critères de S59 (position, même identifiant, cartes des aides lues dans
`app/Traits/`), et exige que **chaque** occurrence porte sa raison.

**24 occurrences, toutes classées** :

| catégorie | nombre | motif |
|---|---|---|
| `DERRIERE_UN_MODULE_COUPE` | 19 | `online_payout` à `false` (D10) ; `OnlinePayoutModuleDisabledTest` établit que chaque route est derrière `if (onlinePayoutEnabled())` |
| `FLUX_GLOBAUX` | 3 | OTP e-mail / mobile : un code se vérifie **avant** toute session, il n'y a pas encore de société |
| `SUPER_ADMIN` | 1 | `switchPlan` : acte de plateforme |
| `SUR_PAR_ORDRE` | 1 | `transferToHubMultipleParcel` : le dépôt garde le hub et rend `false` avant que le contrôleur ne le relise |

> ⚠️ Les 19 ne sont **pas** exemptées parce qu'elles seraient correctes, mais
> parce qu'elles sont **inatteignables**. Le jour où le module rouvre, elles
> redeviennent des failles — et `config/payments.php` le dit déjà.

> ⚠️ `SUR_PAR_ORDRE` est la catégorie la plus fragile : elle dépend d'un ordre
> d'appel, pas d'une garde sur place. Chaque ligne nomme donc **où** vit la garde.

### Le témoin de détection : quatre formes côte à côte

Le filet soumet à sa propre analyse une lecture nue, une lecture scopée, une
garde **avant** et une garde **après**. Il doit voir la première et la dernière,
et ignorer les deux du milieu. Un second témoin exige que la **carte des aides**
reste lue et non devinée ; un troisième, que les commentaires soient dépouillés.

### Une reprise que S64 avait manquée

`ReceivedFromDeliverymanController::update()` portait le même oracle que
`store()` — corrigé en S64 — **plus** une seconde lecture nue : la remise
elle-même. Mon correctif n'avait attrapé que `store()`, et c'est l'instrument qui
l'a dit en continuant de désigner `update()` après le lot.

### Et j'ai refait la même erreur de test

Le premier test de cette reprise était **creux**, pour exactement la raison
corrigée en S64 : le solde du livreur d'en face ne déclenchait pas
l'avertissement, donc la condition était fausse des deux côtés. Le sabotage l'a
dit. Corrigé avec un solde qui la déclenche.

> Connaître le piège ne suffit pas à l'éviter. C'est le **sabotage** qui l'évite.

### Vérification

| Sabotage | Verdict |
|---|---|
| **le défaut de S64 réintroduit** (le virement) | **rouge — le filet le NOMME** |
| **le défaut de la bascule réintroduit** | **rouge** |
| une occurrence retirée des listes | **rouge** |
| le dépouillement des commentaires éteint | **rouge** |
| la carte des aides n'est plus lue | **rouge** |
| reprise d'encaissement : les deux gardes retirées | **rouge** *(vert au premier jet)* |

⚠️ Un sabotage a d'abord été **vert à tort** : il laissait en place le *texte* de
la garde (`'from_account' => Account::class`), que l'analyse voit nommer le
champ. Le filet avait raison ; c'est le sabotage qui était insuffisant. Repris
en retirant la garde **entièrement** — quatrième variante de la leçon S49.

Suite complète : **1054 tests, 46 057 assertions**, verte (delta `+7 / +15`).

### Les huit filets

| filet | surface | arriéré |
|---|---|---|
| `IsolationCoverageTest` (S7) | API v10 | — |
| `WebIsolationCoverageTest` (S28) | routes à paramètre d'URL | **0** (S35) |
| `BodyIdentifierCoverageTest` (S38) | écritures, identifiant dans le corps | **0** (S54) |
| `WebAdminPermissionCoverageTest` (S44) | droits sous `admin/*` | — |
| `OffRequestScopeCoverageTest` (S56) | hors requête (F4) | **0** |
| `SearchSurfaceCoverageTest` (S58) | `GET` sans paramètre d'URL | **0** (S63) |
| `OrScopeEscapeCoverageTest` (S61) | le `OR` au niveau de la portée | — |
| `NakedReadCoverageTest` (S65) | la lecture nue d'un identifiant | — |

---

## S66 — le rejeu du webhook FedaPay, côté portefeuille

Aucune ligne de production touchée : le comportement était **correct**, c'est la
**preuve** qui manquait.

### Le constat

FedaPay réessaie, un réseau duplique, un opérateur renvoie le même événement. Le
solde ne doit bouger qu'une fois. Cette propriété était prouvée côté
**abonnement** depuis le chantier 3
(`FedaPaySubscriptionTest::test_le_webhook_approuve_active_le_plan_une_seule_fois`)
mais **pas** côté portefeuille — et c'est un **autre chemin de code** :
`walletRepo->approved()` au lieu de `switchPlan()`.

Mesuré avant d'écrire quoi que ce soit, avec un test jetable :

```
[solde après 1 webhook : 5000]
[solde après le REJEU  : 5000]
```

Pas de double crédit. Mais rien ne mordait si quelqu'un touchait aux verrous.

### Pourquoi TROIS tests et non un

Deux gardes couvrent ce chemin :

| garde | où | ce qu'elle fait |
|---|---|---|
| **1** | `FedaPayController::approve()` | `lockForUpdate` + `isApproved()` : le rejeu ressort **avant** tout crédit |
| **2** | `WalletRepository::approved()` | `lockForUpdate` + statut `PENDING` : une recharge déjà approuvée n'est pas re-créditée |

Un seul test d'ensemble aurait laissé **chaque verrou sauter en silence**, puisque
l'autre tient. C'est la leçon de S64 sur les sabotages partiels — appliquée cette
fois **en amont**, à la conception du test plutôt qu'après coup.

Le discriminant de la garde 1 est la **réponse** : `already processed` contre
`approved`. C'est le seul qui ne dépende pas de la garde 2.

### Vérification — et elle justifie le découpage

| Sabotage | détecteur dédié | propriété d'ensemble |
|---|---|---|
| garde 1 seule (verrou de la transaction) | **rouge** | *verte — la garde 2 tient* |
| garde 2 seule (verrou du portefeuille) | **rouge** | *verte — la garde 1 tient* |
| **les deux ensemble** | — | **rouge** |

`FedaPayWalletOutcomeTest` : 10 cas, 39 assertions.
Suite complète : **1057 tests, 46 070 assertions**, verte (delta `+3 / +13`).

### L'état du module, pour mémoire

Vérifié à cette occasion : aucune clé FedaPay dans une vue ni dans une ressource
d'API (l'app ne reçoit que `reference` et `payment_url`) · signature HMAC avec
tolérance d'horodatage et `hash_equals` · le montant de l'abonnement vient de
`$plan->price`, jamais du client · au crédit, c'est le montant **enregistré** qui
est utilisé, jamais celui annoncé par l'événement · la recherche du webhook est
délibérément **non scopée** (pas de session, `settings()` retomberait sur la
société 1 — famille F4) et le locataire vient de la transaction.

---

## S67 — la moitié manquante du module douane : le canal courriel

Point de départ : une **vérification** demandée du module douane, pas un défaut
signalé. Le module est sain — deux tables, `CustomsService` comme source unique,
la règle `CustomsAllowed` portée par **les trois portes de création** (l'API
réutilise `App\Http\Requests\MerchantPanel\Parcel\StoreRequest`, elle n'a pas de
règle parallèle qui aurait pu diverger), tout le back-office en `companywise()`,
l'API scopée société **et** marchand, 15 tests verts.

Deux écarts seulement, et aucun n'est une faille :

1. `.claude/rules/customs.md` demande « notification (email/**push**) ».
   `MerchantNotification` portait `toPush()` et `toDatabase()` — **pas de
   courriel**. Son propre docbloc l'admettait : « L'e-mail, lui, n'a toujours pas
   de gabarit. »
2. Une réserve `⏳` de ce fichier affirmait encore « rien n'est envoyé », alors
   que le fil et le push partaient depuis le `CustomsAlertFeedObserver`.
   Rectifiée ci-dessus.

### Le canal, et pourquoi pas celui du socle

| Pièce | Rôle |
|---|---|
| `App\Notifications\Channels\EmailChannel` | jumeau de `PushChannel` : met en file, résout la marque, tolère l'échec |
| `App\Mail\MerchantFeedMail` | `Mailable implements ShouldQueue` — **D13** |
| `resources/views/backend/merchant/mail/feed.blade.php` | même ossature en tables que `signup.blade.php` |
| `MerchantNotification::COURRIEL` | la **liste** des familles qui déclenchent un envoi |

⚠️ **Le canal `mail` du socle ne convenait pas.** Laravel bâtit et remet le
message **dans la requête** tant que la notification n'est pas `ShouldQueue` — et
la rendre `ShouldQueue` aurait aussi déplacé l'écriture du **fil en base**, que
l'app lit aussitôt. D13 veut l'inverse : le fil écrit tout de suite, l'envoi
sortant part en file. D'où un canal à nous, exactement comme `PushChannel` — qui
existe pour la même raison et dont ce lot n'est que le décalque.

⚠️ **La sélectivité est la décision, pas le branchement.** Brancher les six
familles aurait envoyé un courriel à **chaque changement de statut de colis** :
un marchand à trente colis par jour en recevrait une centaine par semaine, les
marquerait indésirables, et perdrait du même coup l'alerte douanière — celle qui
justifiait le canal. `COURRIEL` ne contient que `KIND_CUSTOMS`, et un test garde
ce choix pour que l'élargissement soit **voulu**, jamais subi.

⚠️ **F4 — la marque vient du DESTINATAIRE.** L'observer se déclenche sur
`Parcel::saved` : une création par l'API, mais aussi un import Excel ou une
commande. Hors requête, `settings()` retombe sur la société 1 et le marchand de
« Kola Distribution » recevrait un message signé du premier transporteur de la
base, logo compris — le défaut déjà corrigé dans `MerchantSignup`. La société est
donc lue sur l'utilisateur notifié. Le `Mailable`, lui, ne touche **pas** à
`settings()` du tout : la marque lui est **passée**.

### Vérification — six sabotages, et deux enseignements

| Sabotage | Effet mesuré |
|---|---|
| `via()` n'ajoute plus `EmailChannel` | **rouge** (3 cas) |
| `COURRIEL` élargi aux six familles | **rouge** — la sélectivité |
| la marque revient à `settings()` | **rouge** — F4 |
| `ShouldQueue` retiré du `Mailable` | **rouge** — D13 |
| la garde « adresse vide » retirée | **rouge** |
| le `catch` d'`EmailChannel` **seul** | *verte* — **sabotage partiel** |
| les **deux** `catch` ensemble | **rouge** |

**Enseignement 1 — le sabotage partiel, encore.** `MerchantFeed::deliver()` porte
déjà un `catch` : neutraliser celui du canal **seul** laisse le test vert. Ce test
mesure donc une **paire**, pas une garde, et son docbloc le dit. Ce que le `catch`
du canal apporte n'est pas la survie de l'alerte — c'est le journal qui **nomme**
le canal fautif.

**Enseignement 2 — deux filets mordent sur le même sabotage.** Le retour à
`settings()` fait aussi tomber `OffRequestScopeCoverageTest` (**S56**), parce que
`app/Notifications` est dans sa surface. Le filet de S56 n'avait pas été écrit
pour ce canal : il l'attendait.

### Ce que la vérification a aussi montré, et que je n'ai pas fait

`app/Mail` n'est **pas** dans la surface de `OffRequestScopeCoverageTest`. Les
quatre `Mailable` du dépôt (`MerchantSignup`, `CompanySignup`, `ContactMail`,
`InvoicePDFSend`) lisent `settings()` dans leur constructeur — correctement, car
ils sont construits **dans la requête**, et leurs docblocs l'expliquent. Ajouter
`app/Mail` à la surface demanderait donc quatre exemptions motivées : c'est un
lot en soi, pas une ligne de celui-ci. Noté ici pour qu'il ne se perde pas.

### Mon erreur de méthode dans ce lot

J'ai bâti le harnais de sabotage sur `git checkout --` pour restaurer. Deux des
trois fichiers étaient **neufs, donc non suivis** : la restauration a échoué sans
rien dire pour eux, et pour le troisième elle a rendu le fichier à son état
**commité** — c'est-à-dire qu'elle a effacé le correctif que j'étais en train de
mesurer. Les trois sabotages suivants n'ont mesuré que du bruit, et je les ai
d'abord lus comme des résultats.

> Un harnais de sabotage qui restaure par `git` ne restaure rien d'un fichier
> neuf. La sauvegarde se fait **par copie**, et on vérifie l'état restauré.

Corrigé : sauvegarde par copie, ancrage vérifié **unique** avant chaque
remplacement, et état restauré recontrôlé (vert) entre deux sabotages.

---

## S68 — l'écran douane de `mobile/`, et une affirmation de S67 qui était fausse

### D'abord : ce que j'avais écrit, et pourquoi c'était faux

S67 a conclu que l'écran `customs` de `mobile/` n'existait pas. Deux constats
exacts, une conclusion fausse :

| Constat | Vrai ? | Ce qu'il prouve |
|---|---|---|
| `mobile/src/api/customs.ts` n'a aucun importeur | **non** — le grep était limité à `mobile/src` | rien |
| `mobile/src` ne contient pas de dossier `screens/` | oui | **rien** |

`mobile/` est une app **expo-router** : `"main": "expo-router/entry"`, et ses
écrans vivent dans **`app/`**, un fichier par route. `app/(app)/customs.tsx`
existait, 181 lignes, avec ses onglets, sa résolution et son routage depuis le
push. Un dossier `src/screens/` n'a jamais eu à exister.

> Une recherche limitée au mauvais dossier ne rend pas « aucun résultat » : elle
> rend « aucun résultat **là** ». La différence est invisible dans la sortie, et
> c'est au lecteur de la rétablir.

Aggravant : je l'ai écrit dans ce fichier, et la PR est partie. Une erreur
consignée se transmet.

### Ce que l'écran avait réellement — trois défauts, tous trouvés en lisant

**1. Il ne lisait que la première page.** `customs/alerts` répond en
`paginate(20)` ; l'écran appelait `fetchCustomsAlerts(tab)`. Et
`CUSTOMS_ALERTS_PER_PAGE = 20` était **exporté depuis l'origine sans être
utilisé nulle part** — le module d'API avait prévu la pagination, l'écran ne
l'avait jamais prise. Un marchand à plus de vingt alertes en voyait vingt, sans
compteur, sans bouton, sans fin de liste : la perte était **silencieuse**, sur un
écran de conformité douanière.

**2. Il peignait deux niveaux sur trois avec des couleurs réservées à autre
chose.** `mobile/src/theme/colors.ts` nomme pourtant les trois :

| Niveau | La charte dit | L'écran faisait |
|---|---|---|
| BLOQUANT (3) | `danger` « réservé […] au niveau douanier BLOQUANT » | `danger` ✅ |
| AVERTISSEMENT (2) | `warning` « AVERTISSEMENT douanier » | **`accent`** — l'ocre, « actions clés uniquement » |
| INFO (1) | `info` « INFO douanier » | **`primary`** — le vert, qui se lit « tout va bien » |

Une couleur qui ment sur la gravité coûte plus cher qu'une couleur laide.

**3. Le tableau de bord n'avait qu'un lien.** `mobile/CLAUDE.md` liste pourtant
« alerte douane » parmi son contenu, et le lien *Notifications* juste au-dessus
porte son compteur.

### Ce que ce lot fait

| Pièce | Rôle |
|---|---|
| `mobile/src/domain/customsLevel.ts` | niveaux, statuts et **nom de charte** d'une couleur |
| `app/(app)/customs.tsx` | pagination (idiome de `invoices.tsx`) + couleurs par le domaine |
| `app/(app)/index.tsx` | compteur des alertes **en cours**, coloré par la plus grave |
| `web/tests/Feature/MerchantAppCustomsContractTest.php` | le détecteur |

⚠️ **`src/domain/` n'importe rien**, et ce module non plus : il rend le **nom**
de la couleur (`'danger' | 'warning' | 'info'`), l'écran le résout dans `colors`.
C'est ce qui garde la couche pure — et ce qui rend la correspondance lisible de
l'extérieur, donc vérifiable.

⚠️ **Aucun endpoint inventé.** Le compteur du tableau de bord est la longueur de
la première page ; au-delà il affiche « 20+ » plutôt qu'un chiffre faux. Il
n'existe pas de route de comptage, et on n'en ajoute pas depuis l'app.

### Le détecteur, et pourquoi il vit dans `web/`

`mobile/` **n'a pas de lanceur de tests**. Le dépôt a déjà sa réponse :
`OpenApiSpecTest` lit `mobile/src/api/endpoints.ts`, `ParcelStageTest` lit
`mobile/src/domain/parcelStatus.ts`. PHPUnit est le seul endroit d'où un
invariant de l'app se mesure — et la règle d'or veut de toute façon que ce soit
le backend qui arbitre.

⚠️ **La taille de page est un contrat IMPLICITE**, et c'est ce qui le rend
dangereux : rien dans la réponse HTTP ne la porte (l'enveloppe sert la collection
sans les compteurs du paginateur). L'app déduit « il en reste » d'une page
pleine. Si un côté bouge seul, elle s'arrête trop tôt — ou boucle sur une page
vide — **sans erreur**. Les trois paires concordent aujourd'hui (douane 20/20,
notifications 20/20, relevés 10/10) : le test existe pour le jour où l'une
bougera.

### Vérification — six sabotages, dont un qui a corrigé le test

| Sabotage | Effet |
|---|---|
| l'écran revient à la page 1 seule | **rouge** |
| l'app dérive sa taille de page (20 → 10) | **rouge** |
| le **serveur** change la sienne (20 → 15) | **rouge** — le test lit les deux côtés |
| l'app renumérote un niveau (WARNING 2 → 5) | **rouge** |
| le tableau de bord compte les alertes traitées aussi | **rouge** |
| l'écran repeint les niveaux en ocre/vert | *verte*, puis **rouge** |

**Le dernier est le seul qui ait appris quelque chose.** Mon test lisait
`src/domain/customsLevel.ts` et ne vérifiait jamais que l'**écran** s'en sert :
j'ai remis le défaut exact que je venais de corriger, table de domaine intacte,
et la suite est restée verte. Elle mesurait le bon principe **dans le mauvais
fichier**.

> Une table de correspondance juste ne protège rien tant qu'un écran peut la
> contourner. Le test doit vérifier qu'il la **traverse**.

Un septième cas a été écarté à dessein : afficher l'alerte sur le **détail du
colis**. `customs/alerts` n'a pas de filtre par colis, et filtrer côté client une
liste paginée mentirait. Cela demanderait une évolution de `web/` — donc un autre
lot, dans le bon ordre.

### Ce que ce lot ne garantit pas

`mobile/` n'ayant pas de lanceur de tests, ces six propriétés sont des lectures
de **source**, pas des rendus d'écran. Elles mordent si le code perd une
propriété ; elles ne diraient pas qu'un compteur affiche un mauvais nombre. Le
contrôle visuel humain reste dû — et il l'était déjà.

## S69 — le lot de nettoyage T1 : quatre pièces mortes, et six routes qui visaient le vide (2026-10-03)

### D'où il vient

`docs/CARTOGRAPHIE_PROJET.md` (PR #137) a rangé sous **T1** ce que trois documents
signalaient depuis septembre sans que personne n'y touche, parce qu'« aucun
comportement n'était à corriger » :

| Pièce | Signalée par | Défaut |
|---|---|---|
| `app/Mail/InvoicePDFSend.php` | bloc G, REVUE_FEDAPAY §24, charte-web §11.6 b | jamais instancié ; `from: admin@example.com` ; sujet « Invoice P D F Send » ; vue `invoice_mail_pdf` pour un fichier **`Invoice_mail_pdf.blade.php`** — résout sur WAMP, **pas sous Linux** |
| `backend/merchant/invoice/invoice_pdf.blade.php` | bloc G (« ORPHELINE »), charte-web §11.6 a | rendue nulle part ; `ParcelStatus::RETURN_TRANSFER_BY_HUB` et `RETURN_RECEIVED_PARCEL` **n'existent pas** → `Undefined constant` pour tout colis livré ; quatrième copie de la table des statuts, en anglais, en taka, en violet |
| `InvoiceRepository::InvoicePdf()` | S32 | doublon **corps pour corps** d'`invoiceGet()`, déclaré dans l'interface, appelé par personne ; a **avalé un sabotage** |
| `IncomeController::searchAccount()` | S35 | sa route a disparu, l'écran des revenus appelle celle des dépenses, et elle passait l'objet `Request` là où `AccountRepository::get($id)` attend un identifiant |

### Ce que le lot fait — neutraliser, pas effacer

La règle du projet (`docs/guides/socle/`) : **0 fichier supprimé du socle**, parce
qu'une re-fusion de We Courier rejouerait chaque suppression en conflit. D'où deux
traitements :

- **Un fichier mort est rendu juste.** `InvoicePDFSend` suit désormais les trois
  règles de `MerchantFeedMail` : `ShouldQueue` (**D13** — le rendu du PDF aussi quitte
  la requête), marque **passée** et résolue sur la société du destinataire (**F4** —
  aucun `settings()`), et **un seul document** : la pièce jointe est le PDF de
  `statement_pdf`, le relevé du chantier 4. La vue nommée l'est avec la casse du
  fichier. `invoice_pdf.blade.php` ne porte plus de table des statuts : elle
  **délègue** à `statement_pdf` via `SettlementStatement::for($invoice)`.
  ⚠️ Le mailable reste **non branché** : envoyer les relevés par courriel est une
  décision produit (cadence, destinataires, opposabilité) — **R9** de la cartographie
  du projet, pas un correctif.
- **Une méthode morte se retire.** `InvoicePdf()` sort de l'interface et du dépôt ;
  `searchAccount()` sort du contrôleur ; `AccountInterface::get($request)` s'appelle
  enfin `get($id)`, comme son implémentation depuis S24.

### Le neuvième filet, et ce qu'il a trouvé du premier coup

Le bloc G avait trouvé **à la main** deux routes PDF déclarées sur une méthode
inexistante, et notait : « un passage `route:list` complet est recommandé ». Or
`route:list` ne vérifie pas que la méthode existe. `LegacyDeadCodeCleanupTest` monte
les routes du locataire (`MountsTenantRoutes`) et lit chaque `Classe@methode`.
Première exécution : **six routes** du socle visaient le vide — un **500** garanti
pour qui les atteignait.

| Route | Cible absente | Appelants | Traitement |
|---|---|---|---|
| `GET admin/addons/{addon}` | `AddonController::show` | aucun | `Route::resource(...)->except(['show', 'destroy'])` |
| `DELETE admin/addons/{addon}` | `AddonController::destroy` | aucun | idem |
| `GET admin/parcel/file-export` | `ParcelController::parcelExport` (il vit sur **`MerchantParcelController`**) | aucun | retirée |
| `POST admin/todo/momal` | `TodoController::todoModal` | la barre de navigation, par `data-url="{{ route('todo.modal') }}"` — **lu par aucun script** (les lecteurs de `.data('url')` visent d'autres éléments ; la fenêtre To-do est un `@include` statique qui poste sur `todo.store`) | retirée, et l'attribut avec elle (deux lignes de `navber.blade.php`) |
| `POST merchant/parcel/merchant` | `MerchantParcelController::getMerchant` | aucun (le panneau marchand n'a pas à chercher un marchand : c'est le compte connecté) | retirée |
| `GET merchant/online-payment-received-list` | `onlinePaymentReceivedList` | aucun (module payout, **D10**) | retirée |

⚠️ **Deux inventaires de filets la croyaient vivante.** `WebAdminPermissionCoverageTest`
tenait `GET admin/parcel/file-export` à l'arriéré avec la mention « leurs méthodes
existent » — **fausse** pour celle-ci. Quatre lignes sortent de `HERITAGE` (22 → **18**,
le cliquet baisse) et deux exemptions de `WebIsolationCoverageTest` disparaissent avec
leurs routes. Un arriéré n'est pas une preuve d'existence : il recopie une déclaration.

⚠️ Relevé au passage, **hors lot** : dix vues du socle, dont deux du **panneau marchand**
(`merchant_panel/reports/*`), appellent `route('parcel.merchant.get')` — la route
**`admin/`**, gardée par `panel:back-office` (S41). Un marchand qui ouvre ce sélecteur
reçoit 403. À lire avec les écrans de rapport du panneau marchand, pas ici.

### Vérification — cinq sabotages

| Sabotage | Effet |
|---|---|
| remettre `InvoicePdf()` dans le dépôt | **rouge** (`single lookup`) |
| remettre `invoice_mail_pdf` (minuscule) dans le mailable | **rouge** (`case sensitive`) |
| remettre une condition `ParcelStatus::` dans la vue orpheline | **rouge** (`orphan view`) |
| redéclarer `todo/momal` sur `todoModal` | **rouge** (`every declared route`) |
| rendre le mailable synchrone (retirer `ShouldQueue`) | **rouge** (`queued`) |

Un sixième a corrigé le test lui-même : la première version lisait le **fichier**
du mailable et y cherchait l'absence d'`example.com` et de `settings()` — et les
trouvait dans le **docbloc** qui raconte le défaut d'origine. Le test lit désormais
le code sans ses commentaires (`token_get_all`), et le Blade sans ses `{{-- --}}`.
Un test qui mesure un fichier doit savoir ce qu'est du code dedans.

Suite complète : **1 076 tests, 46 169 assertions**, verte (delta `+7 / +36` pour ce lot) ;
les cinq sabotages ci-dessus ont chacun fait rougir le test visé, puis les fichiers ont
été remis.

## S70 — les liens du panneau marchand qui visaient l'autre panneau (2026-10-03)

### D'où il vient, et ce qui était faux dans le relevé de S69

S69 avait noté, hors lot : « deux vues du panneau marchand appellent
`route('parcel.merchant.get')`, une route `admin/` gardée par `panel:back-office` ;
un marchand y reçoit 403 ». La première moitié est vraie, la conclusion **ne l'était
pas** : ces vues ne portent **aucun** sélecteur `#parcelMerchantid`, donc le `select2`
qui aurait appelé l'URL ne s'initialise sur rien, et l'appel ne part jamais. Le 403
était **latent**. Un lien que personne ne suit ne produit pas d'erreur — il attend.

Ce que la lecture complète a trouvé est plus lourd, et **S41 l'avait affirmé absent** :
« aucune vue marchande n'appelle une URL `admin/` ». Cinq routes `admin/` étaient
nommées par des vues **vivantes** du panneau marchand, et la garde de S41 les a
transformées en 403 :

| Vue (`merchant_panel/`) | `route()` nommée | Ce que le marchand touchait | Recâblé vers |
|---|---|---|---|
| `parcel/create`, `parcel/edit` | `parcel.index` | le bouton **Annuler** | `merchant-panel.parcel.index` |
| `parcel/logs` | `parcel.index` | le fil d'Ariane « Colis » | `merchant-panel.parcel.index` |
| `reports/total_summery` | `parcel.reports`, `parcel.total.summery.index` | le fil d'Ariane, le bouton **Effacer** | `merchant.total.summery` |
| `parcel/index` | `parcel.multiple.print-label` | **Imprimer les étiquettes** en lot | une route marchande **neuve** (ci-dessous) |
| `reports/*` | `parcel.merchant.get` | rien (latent) | retiré |

Les quatorze autres noms hors espace `merchant` relevés dans ces vues sont soit des
routes **du** panneau marchand nommées sans préfixe (`payment.account.*`, lignes
880-884, dans le groupe `merchant/`), soit le module payout coupé par **D10**
(`online.payment.*`, `skrill.*`, `aamarpay.*`, `bkash.*`, derrière `onlinePayoutEnabled()`),
soit les deux routes partagées (`dashboard.index`, `logout`).

### La seconde famille : les scripts du back-office rejoués sans leurs globaux

Les deux vues de rapport chargeaient `parcel/filter.js` et `reports.js`, écrits pour
l'administration, qui lisent `merchantUrl` et `hubUrl` **en variables globales**. Les
vues marchandes définissaient la première (sur la route `admin/`) et pas la seconde ;
`merchant_panel/parcel/filter.js`, lui, lisait `merchantUrl` que **ni** `parcel/index`
**ni** `parcel_bank` ne définissent. Une `ReferenceError` dans un `document.ready`
**coupe tout ce qui suit** dans la fermeture. Rien de visible ne manquait — les
éléments concernés n'existent pas dans ces pages — mais c'est la même maladie que les
liens : **le panneau marchand réutilise les routes et les scripts du back-office
comme s'il en faisait partie.**

Correctif : le bloc marchand sort de `merchant_panel/parcel/filter.js` (un marchand ne
cherche pas un marchand) ; les vues de rapport chargent ce script-là et ne définissent
plus de route `admin/` ; `reports.js` lit `merchantUrl` et `hubUrl` sous `typeof`,
parce qu'il reste partagé.

### Les étiquettes en lot : une route marchande, périmètre du marchand

Le bouton existait, avec son script, et n'a **jamais** pu fonctionner pour un marchand
depuis S41. `GET merchant/parcel/multiple/print/label` →
`MerchantParcelController::parcelMultiplePrintLabel()` : même vue que l'administration
(`backend.parcel.multiple-print-label`), mais la lecture passe par `ownedParcels()`
— société **et** marchand connecté. Règle de lot de **S38** : l'identifiant d'un autre
marchand est **ignoré**, le reste du lot s'imprime. La route est inscrite au filet S58
(`SearchSurfaceCoverageTest`, prouvée par HTTP).

### Le dixième filet

`MerchantPanelCrossLinkTest` lit chaque vue de `resources/views/backend/merchant_panel/`,
en extrait les `route('…')`, et refuse tout nom dont l'URI servie commence par `admin/`.
Il ne juge pas les intentions : il lit ce qu'une vue **nomme**. Six écrans sont de
plus appelés par HTTP en tant que marchand (200, et aucune URL `pme.test/admin/` dans
la page), la route `admin/` que visait le bouton d'étiquettes est mesurée à **403**
pour un marchand (la prémisse), et les scripts sont lus sans leurs commentaires.

⚠️ Deux pièges de fixture, utiles à qui écrira le prochain écran marchand par HTTP :
`parcel/create` et `parcel/edit` lisent `$shops[0]->id` et
`$merchant->cod_charges['inside_city']` **sans les vérifier**. Un marchand semé sans
boutique par défaut ni taux COD rend ces écrans en **500** — ce que l'inscription ne
produit jamais, mais qu'une fixture produit à coup sûr.

### Vérification — quatre sabotages

| Sabotage | Effet |
|---|---|
| le bouton Annuler de `parcel/create` revient sur `route('parcel.index')` | **rouge** (`names a back office route`) |
| les étiquettes lisent `Parcel::companywise()` au lieu d'`ownedParcels()` | **rouge** (`renders only its own`) — le colis du voisin s'imprime |
| `reports.js` relit `url: merchantUrl` nu | **rouge** (`read no undefined global`) |
| `total_summery` redéfinit `var merchantUrl` sur la route `admin/` | **rouge** (`names a back office route`) |

Deux sabotages involontaires ont corrigé le test lui-même avant qu'il passe : un
marchand semé **sans boutique par défaut** et **sans taux COD** rend `parcel/create` et
`parcel/edit` en 500 (voir le piège de fixture ci-dessus), et le test des scripts lisait
mon propre commentaire — il lit désormais le code sans ses `//`, leçon de S69.

Onze filets voisins relancés après le lot : **139 tests, 781 assertions**, verts. Suite
complète : voir la ligne ci-dessous.
Suite complète : **1 087 tests, 46 197 assertions**, verte (delta `+11 / +28` pour ce lot).

## S71 — la répétition générale de la recette pilote (2026-10-03)

### Ce que « préparer la recette » peut vouloir dire depuis un conteneur

`docs/guides/recette-pilote/` décrivait tout : l'environnement, les APK, le jeu de
données, 34 scénarios à cocher sur des téléphones, les critères de sortie. Rien n'était
coché, et rien ne pouvait l'être d'ici — un conteneur n'a ni téléphone, ni réseau
mobile, ni compte FedaPay sandbox. Ce qu'il a, c'est le serveur et ses tests.

Chaque scénario a pourtant **deux moitiés** : ce que l'app affiche, et ce que le
serveur fait quand l'app l'appelle. La seconde se joue ici, sur le jeu
`beninlink:pilote`, avec les comptes que les PME recevront, par les routes exactes
des deux apps. C'est `RecettePiloteRepetitionTest` : **25 scénarios sur 34**, un test
par case, nommé par son numéro (`test_m5_…`). Le jour J, un test rouge est un défaut
**du serveur**, à corriger avant de distribuer les APK ; un test vert ne dit rien de
l'écran, qui reste à cocher par un humain. Le §0 du guide tient la correspondance.

Neuf scénarios n'ont pas de moitié serveur jouable ici : la question posée aux PME
(L6), le tableau de bord du back-office (A1 — ses routes ne se montent que sur le
domaine de la société pilote, et `MountsTenantRoutes` mappe la société 1, pas la 2),
le reporting SaaS côté super-admin (A3 — le calcul est couvert par `SaasMetricsTest`),
`php artisan test` sur la version déployée (S3 — c'est cette suite elle-même), et la
moitié « écran » de toutes les lignes.

### Ce que la répétition a trouvé : rien côté serveur, trois pièges côté test

Vingt-cinq scénarios, **zéro défaut serveur**. Les sept tests tombés au premier passage
étaient tous des erreurs de ma lecture, et chacune vaut d'être écrite pour qui
prolongera la répétition :

| Ce que j'avais supposé | Ce qui est vrai |
|---|---|
| un colis créé par l'API porte le préfixe `PIL` | non — `PIL` n'appartient qu'au **jeu** ; l'API attribue le préfixe de la société (`CO…`) |
| la chronologie du marchand est `data.logs[]` avec `status` | c'est `data.parcelEvents[]` avec `parcel_status` **en chaîne** |
| le détail d'une course est une `ParcelResource` | c'est le **modèle** avec ses relations (`parcel.merchant.business_name`) |
| la position s'écrit sur le colis | sur l'**événement** de la course (`parcel_events.delivery_lat`), comme S7 l'avait fixé |
| `beninlink:journal-syscohada` lit le mois courant | **le mois dernier** par défaut ; passer `--du`/`--au` |
| après `Sanctum::actingAs()`, une connexion par mot de passe marche | non — le garde par défaut est devenu `sanctum`, et `Auth::attempt()` n'existe que sur `web` ; **et `config('auth.defaults.guard')` a déjà été réécrit**, donc le rétablir par la config rend `sanctum` : il faut `shouldUse('web')` |
| le compteur du fil est `data.unread` | `data.unread_count` |

### Les apps : un `expo-doctor` qui avait rougi en silence

Le guide exigeait `npx expo-doctor` avant tout build et notait 21/21 au 2026-09-06. Au
2026-10-03 : **20/21**, « 1 check failed » — 11 paquets côté marchand, 13 côté livreur,
en retard à l'intérieur du SDK 57 (`expo` 57.0.20 → 57.0.26, `expo-router`,
`expo-notifications`, `expo-location`, `expo-image-picker`…). Rien dans le dépôt ne
pouvait le voir : les apps n'ont pas de lanceur, et ce contrôle dépend du registre npm
du jour. `npx expo install --fix` dans les deux apps, puis typecheck, lint et doctor
**21/21** sur chacune. Un `expo-doctor` rouge fait échouer EAS après dix minutes de
file (`mobile-livreur/CLAUDE.md`) : il se relance avant **chaque** build.

### Ce que le lot livre, et ce qu'il ne livre pas

| Livré | Où |
|---|---|
| la répétition (25 tests, 158 assertions) | `tests/Feature/RecettePiloteRepetitionTest.php` |
| le §0 du guide : correspondance scénario ↔ test, ce qui reste humain, les pièges | `docs/guides/recette-pilote/README.md` |
| la **fiche de préparation** P1-P10 : serveur, base, FedaPay sandbox, worker, SMS, EAS et keystores, téléphones, PME informées, suite verte, tableau de collecte — un responsable et une vérification par ligne | idem, §7 |
| le modèle de collecte des retours | `docs/guides/recette-pilote/retours-modele.csv` |
| les deux apps à jour du SDK 57, 21/21 | `mobile/package.json`, `mobile-livreur/package.json` et leurs `lock` |

Non livré, parce que non livrable d'ici : les APK (compte EAS, deux keystores), le
serveur de recette, les clés FedaPay sandbox, les téléphones, les PME. La fiche P1-P10
nomme chaque manque et qui le comble. **E1 passe de « rien n'est coché » à « tout ce
qui se vérifie sans téléphone est vérifié » ; l'exécution reste humaine.**

### Vérification — quatre sabotages

| Sabotage | Effet |
|---|---|
| le jeu n'affecte plus de course à un livreur (`PARCEL_PLAN`) | **rouge** (L2 : onglet « En cours » vide) |
| le forfait Togo passe à 15 000 (`ZoneCatalog`) | **rouge** (M3) |
| le jeu ne crée plus la boutique des PME | **rouge** (M2 : `shop_id` manquant, 422) |
| la boutique n'est plus « par défaut » | *verte* — l'API ne lit pas ce drapeau, seul l'écran web de création le fait (piège de S70) : la répétition joue l'API, pas Blade |

Suite complète : **1 112 tests, 46 355 assertions**, verte (delta `+25 / +158` pour ce lot).

## S72 — la grille de départ en un seul fichier, et des montants qui ne sont pas figés (2026-10-03)

### D'où ça vient

Le point R1 de `docs/CARTOGRAPHIE_PROJET.md` disait : « aucun montant de grille n'est
écrit ». C'était vrai en production et faux en recette. La grille du 2026-09-06 vivait
**deux fois** : dans une constante `GRID` de `PiloteDataset` (douze montants recopiés
pour le jeu pilote), et dans la mémoire du transporteur, qui devait les ressaisir à
l'écran *Réglages → Zones et barème* avant que `beninlink:tarification-prete` accepte
de laisser partir un déploiement. Deux copies, deux occasions de dériver, et une
ressaisie humaine de douze cases entre la recette et la production.

Le brief R1 posait cinq questions ; la première (« la grille du 2026-09-06 est-elle le
tarif, ou un point de départ ? ») a reçu sa réponse en cours de lot : **les montants ne
sont pas figés, le transporteur doit pouvoir les réajuster à tout moment depuis le
back-office.** Cette réponse fixe la règle de tout le lot.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `database/bareme/grille-nationale.csv` | **la** grille de départ : `categorie;poids_max;cotonou;peripherie;interieur`, FCFA entiers, lignes `#` de commentaire, pas de colonne CEDEAO (forfait par pays, `ZoneCatalog::PAYS`) |
| `App\Services\Pricing\GridFile` | `lire()` valide tout ou rien (en-tête, zones admises, entiers, tranches uniques, nombre de colonnes ; « 1 500 » toléré, « 1500.5 » refusé) ; `installer()` crée la catégorie si elle manque et les lignes **si elles manquent, jamais réécrites** ; `manquantes()` sert le constat. Un chemin relatif se lit depuis `web/`, comme la doc l'écrit |
| `beninlink:zones-tarifaires --grille=` | lit et valide le fichier **avant la première écriture** ; en constat annonce « N ligne(s) à créer » ; avec `--installer` pose le cadre puis la grille et dit « X créée(s), Y conservée(s) telle(s) qu'à l'écran » |
| `PiloteDataset::grid()` | lit `GridFile::DEFAUT` — la constante `GRID` est partie ; un test refuse qu'elle revienne |

La règle « créées si manquantes, jamais réécrites » n'est pas une prudence technique :
c'est **la décision du métier traduite en code**. Le fichier est un point de départ ;
l'écran garde le dernier mot ; la commande, relancée à chaque déploiement, crée ce qui
manque et ne touche pas au reste. Changer le fichier ne change que ce qui n'a pas encore
été posé — c'est voulu, et c'est documenté dans le fichier lui-même.

Effet de bord assumé : `beninlink:pilote` **ne réécrit plus non plus** une ligne de grille
déjà en base. Il écrasait les douze montants à chaque `--reset` ; il crée désormais ce qui
manque. Sur une société de recette, `--reset` retire les zones et les lignes zonées, donc
le résultat est le même ; sur une société où le transporteur a ajusté un montant, c'est ce
montant que la recette exerce — ce qui est précisément ce qu'une recette doit faire.

### Ce que le test fixe — `tests/Feature/GridFileTest` (16 tests, 107 assertions)

- le fichier versionné **est** la grille documentée (4 tranches × 3 zones, les douze
  montants du 2026-09-06) ; un chemin relatif se lit depuis `web/` ;
- la commande pose cadre + grille sur une société nue, et `tarification-prete` **réussit**
  derrière — c'est la première fois qu'un déploiement peut rendre une société facturable
  sans passer par l'écran ;
- la relance crée 0 et conserve 12, sans doubler ni la ligne ni la catégorie ;
- **`test_un_montant_reajuste_a_l_ecran_survit_a_la_relance`** : Cotonou ≤ 1 kg passé de
  800 à 950 F « à l'écran », relance, 950 reste et rien d'autre n'a bougé — la demande du
  porteur, mot pour mot ;
- le constat sans `--installer` n'écrit rien, pas même la catégorie ;
- six fichiers fautifs (décimal, colonne `cedeao`, colonne manquante, tranche en double,
  poids non entier, fichier vide) et un fichier introuvable : `FAILURE`, et **zéro zone
  posée** — la validation précède la première écriture ;
- le jeu pilote pose les mêmes douze montants que le fichier, sa source ne garde aucune
  copie (`GridFile::DEFAUT` présent, `const GRID` absent), et il laisse lui aussi le
  dernier mot à l'écran.

Piège de test rencontré : `expectsOutputToContain()` consomme **une écriture par
attente** (Mockery prend la première attente qui correspond), donc deux sous-chaînes
sur la **même ligne** de sortie ne peuvent pas toutes deux passer. La commande écrit
désormais le refus sur deux lignes (« Grille refusée, rien n'a été écrit. » puis le
motif), ce qui est aussi plus lisible pour l'humain.

### Vérification — quatre sabotages

| Sabotage | Effet |
|---|---|
| `GridFile::installer()` met à jour le montant d'une ligne existante | **rouge** (2 : le montant ajusté revient à 800, le jeu pilote réécrit) |
| le CSV versionné dévie (800 → 850) | **rouge** (4 : fichier ≠ grille documentée, commande, relance, jeu pilote) |
| la commande avale le fichier fautif (`SUCCESS` au lieu de `FAILURE`) | **rouge** (7 : les six fichiers fautifs et l'introuvable) |
| une `const GRID` revient dans `PiloteDataset` | **rouge** (1) |

Les voisins — `PiloteDatasetTest`, `DeliveryZoneGridTest`, `PricingReadinessAuditTest`,
`DeliveryZoneApiTest`, `DeliveryZonePricingBaselineTest`, `DeliveryZoneScreensTest`,
`ParcelZoneRouteTest`, `OffRequestScopeCoverageTest` — restent verts (91 tests ensemble).

Suite complète : **1 128 tests, 46 462 assertions**, verte (delta `+16 / +107` pour ce lot).

### Ce qui reste sur R1, hors du code

Le fichier porte la grille **nationale**. Trois réponses du métier manquent encore et
n'ont pas de place dans ce lot : les forfaits des cinq autres pays CEDEAO (CI, NE, ML, SN,
GH — tant qu'aucun forfait n'est fixé, la création vers ces pays est refusée), le taux
COD de la zone CEDEAO (par marchand, `merchants.cod_charges.cedeao`), et la TVA à
l'export (18 % comme partout, ou exonération — qui demanderait un petit chantier, D1 ne
connaissant pas « exonéré »).

## S73 — le plan de comptes signé : huit décisions, et un retour qui devient taxable (2026-10-03)

### D'où ça vient

La fiche `docs/guides/comptabilite/plan-de-comptes.md` posait huit questions à
l'expert-comptable depuis le 2026-09-06 ; le porteur les a tranchées le 2026-10-03,
et ce lot les traduit en code. Règle d'or reçue avec le brief : **un relevé émis ne se
modifie jamais directement** (D8) — tout correctif passe par un constat, puis une
régularisation explicite.

Cinq des huit réponses tiennent dans `config/syscohada.php`. Trois demandaient du code :
la date de l'écriture de banque (q.5), la TVA du frais de retour (q.6) et les deux flux
hors relevé à journaliser (q.8).

### Ce qui est écrit

| Question | Décision | Où |
|---|---|---|
| 1 | auxiliaire par marchand **par défaut** | `env('SYSCOHADA_AUXILIARY', 'merchant_code')` ; `collectif` pour revenir en arrière ; `syscohada.auxiliarised` nomme les comptes de tiers suffixés (clients, COD, avances) |
| 2 | compte COD **dédié** acté, numéro à venir | `cod_liability` reste `4712`, libellé « numéro à valider », figé |
| 3 | 4431, 18 % | figés par le test |
| 4 | journaux en paramétrage | `VE` / `OD` / `BQ` + **`CA`** (caisse) |
| 5 | **date de l'ordre de virement** | `invoices.paid_on`, posée par `statusUpdate()` au passage à payé, effacée si le statut recule ; `SyscohadaJournal::reversement()` la prend ; `periode()` range chaque écriture dans la période de sa date |
| 6 | **retour taxable** | `App\Services\Parcel\ReturnVat` ; `parcels.return_vat_amount` ; ligne marchand `statementNote.return_vat_merchant_statement` + `VatStatement` au retour, inversées à l'annulation ; `InvoiceRepository` facture `return_charges + return_vat_amount` ; `beninlink:retours-sans-tva` constate le passé |
| 7 | arrondi confirmé, pas de reprise | rien à coder ; la règle est figée (302 pour 18 % de 1 680) |
| 8 | recharges → avances reçues ; remises → transit ; SaaS → rien | `SyscohadaJournal::recharges()` (D 521 / C 4191 + code, à l'approbation du `Wallet`) et `::remises()` (D 571 / C 4713, `CashReceivedFromDeliveryman`) |

Le service journal a changé de forme sans changer de contrat : `linesFor(Invoice)` sert
toujours l'export relevé par relevé du back-office, et **`periode(société, du, au)`**
sert l'extrait de l'expert-comptable — la commande et `JournalPeriod` l'appellent. Un
relevé émis en août et payé en septembre a désormais ses ventes dans l'extrait d'août et
sa banque dans celui de septembre ; avant, tout suivait `issued_on`.

### Le point délicat : la TVA du retour touche trois moments, pas un

Mettre la TVA dans le relevé seul aurait suffi à « répondre » à la question 6 — et
aurait cassé **D9** : le relevé aurait facturé 590 F quand le solde du marchand n'en
aurait vu que 500, et `beninlink:ecarts-marchands` l'aurait signalé comme un écart. La
TVA se prélève donc **au retour**, sur sa propre ligne (comme la livraison écrit sa TVA à
part), se **rend à l'annulation** au montant prélevé, et le relevé **relit** ce qui a été
prélevé. `CancelledReturnsCommand` suit : ce qui est dû à un retour qui tient, c'est le
frais **et** sa TVA.

Le taux est celui du **colis** (`parcels.vat`, fixé à la création), à défaut celui du
marchand ou de la société — pas `settingHelper()` seul, qui résout par la société ambiante.

Trois tests existants ont changé de chiffres, et c'est le signe que la décision mord :
le retour d'un colis à 1 000 F coûte désormais **590 F** (500 HT + 90 de TVA) et non
500 ; le relevé de démonstration de la fiche passe de 1 987 à **2 077 F TTC** (TVA 317),
net **77 923** ; les lignes de relevé marchand d'un retour annulé sont quatre, pas deux.

### Ce que les tests fixent

`tests/Feature/ReturnVatTest` (10 tests) : prélèvement du frais et de sa TVA au franc sur
deux lignes ; taux société quand le colis n'en a pas ; annulation qui rend les deux et
efface `return_vat_amount` ; relevé TTC et journal (7061 500, 4431 90) ; constat d'un
relevé ancien **sans rien toucher** (relevé, ligne, colis, solde) ; TVA manquante au taux
du colis (302 pour 1 680) ; rien à constater quand les retours portent leur TVA ; filtre
par société ; et **la commande n'a pas d'option de correction** — un test lit sa
définition et refuse `--corriger` et `--force`.

`tests/Feature/PlanDeComptesSigneTest` (9 tests) : comptes et journaux **égaux** au plan
signé, placeholders compris ; auxiliaire par défaut sur les seuls comptes de tiers ;
4431 et `vat_rate = 18` ; arrondi au franc (302, 434) ; aucun abonnement dans le journal ;
banque datée du virement (relevé émis le 28/08, payé le 03/09 : août porte VE et OD,
septembre porte BQ ; reculer le statut efface la date) ; relevé payé sans date retombe
sur l'émission ; recharge approuvée = avance reçue auxiliarisée, recharge en attente
ignorée ; remise d'espèces en transit collectif ; extrait de période équilibré sur les
quatre flux, `--payes` sans recharges ni remises, commande sur toutes les sociétés, et
une autre société qui ne voit rien.

Trois fichiers mis à jour : `SettlementStatementTest` (2 077 / 317, collectif → auxiliaire
par défaut), `DeliveryCancellationAccountingTest` (590, quatre lignes),
`CancelledReturnsCommandTest` (590, 1 180).

Piège rencontré : `NOTE_MARCHAND` de `retours-annules` est stable parce que sa
traduction **n'existe pas** (la clé est écrite telle quelle). La note de la TVA du retour,
elle, est traduite — donc dépend de la locale au moment du retour. La commande accepte
toutes ses formes (`notesDuRetour()`) plutôt que de parier sur la locale.

### Vérification — cinq sabotages

| Sabotage | Effet |
|---|---|
| le relevé force à nouveau `vat_amount = 0` sur un retour | **rouge** (2) |
| le passage à payé ne pose plus `paid_on` | **rouge** (1) |
| `cod_liability` passe de 4712 à 4718 sans changer le test | **rouge** (2) |
| le retour ne pose plus sa TVA sur le colis | **rouge** (4) |
| `retours-sans-tva` gagne une option `--corriger` | **rouge** (1) |

Filets et voisins (huit filets, répétition pilote, jeu pilote, relevés, isolation) :
156 tests verts. Suite complète : **1 147 tests, 46 565 assertions**, verte — après un seul
rouge, le contrôle positif de `ParcelCancelScopeTest` qui comptait **une** ligne de relevé
marchand au retour : il y en a deux désormais, et c'est la décision qui mord.

### Ce qui reste, et à qui

À l'expert-comptable, sans code ni migration : trois numéros (COD dédié, avances reçues,
transit livreurs), quatre codes de journaux, et la **régularisation du passé** des
retours facturés sans TVA — `beninlink:retours-sans-tva` en chiffre l'enjeu. Quand il
aura tranché, la régularisation sera un lot à part, sur le modèle de
`retours-annules --corriger`. Les reversements aux livreurs (courses payées) ne sont pas
journalisés : ils restent dans les soldes internes, à ouvrir s'il le demande.

## S75 — sept décisions produit en un lot : R3 à R9 (2026-10-03)

### D'où ça vient

Le § 7.1 de `docs/CARTOGRAPHIE_PROJET.md` gardait sept points « en attente du
porteur » (R3 à R9). Ils ont été tranchés en une séance, et ce lot les traduit : cinq ont
du code, deux (R4, R8) n'en ont pas et le disent. Règle d'architecture reçue avec le
brief : backend d'abord ; aucun des sept ne touche à l'API, donc ni OpenAPI ni apps.

### Ce qui est écrit

| Point | Décision | Où |
|---|---|---|
| R3 | pas de prorata ; la règle est **dite avant la confirmation** | `levels.plan_switch_notice` dans `subscription.blade.php` et `switch_subscription.blade.php` |
| R4 | SMS français seul ; couture `SmsTemplate::locale()` conservée | — |
| R5 | menu Réglages visible en **OU** sur les droits que lisent ses entrées | `sidebar.blade.php` : la garde liste les quatorze droits du sous-menu (pas `google_map_settings_read`, que le sous-menu ne lit pas) |
| R6 | catalogue `categorys` réservé au super-admin, comme `currencies` (S55) | six routes déplacées dans `routes/superadmin.php` ; `category_*` retiré des trois listes du locataire (`PermissionSeeder`, `RoleSeeder`, `UserSeeder`) et ajouté aux attributs super-admin ; migration `2026_10_03_110000` pour les super-admins existants ; lien dans le menu super-admin ; `CategoryController` redirige par route nommée |
| R7 b | statut TVA explicite | `App\Enums\VatStatus`, `merchants.vat_status` (migration `2026_10_03_120000`, `taxable` là où `vat > 0`, jamais `exempt`), `VatRate::for()` / `statut()` / `estExonere()`, sélecteur dans les deux formulaires marchand, `SettlementStatement['merchant']['vat_exempt']`, mention imprimée sur le relevé |
| R9 | relevés par courriel à l'émission | `SettlementPdf` (point de rendu unique), `StatementMailer` (hors transaction, marque par identifiant, file), appelé par `InvoiceRepository::store()` ; `InvoicePDFSend` et `MerchantInvoiceController::pdfResponse()` passent par `SettlementPdf` |

### Trois choses apprises en chemin

**La garde d'un menu se mesure, elle ne se devine pas.** Le § 13.7 de la charte parlait
de « quinze » droits ; le sous-menu en lit **quatorze** — l'entrée Google Maps n'est pas
gardée par `google_map_settings_read`. Lister les quinze aurait ouvert le menu à un agent
qui n'y aurait rien vu. Le test ne fixe pas une liste : il compare la garde au sous-menu.

**Le droit `category_*` était offert au locataire à trois endroits**, pas un : la table
`permissions` (seeder), la liste `AdminPermissions()` de `RoleSeeder` **et** celle de
`UserSeeder`, qui ne se parlent pas. Le premier passage n'en avait retiré que deux, et le
test l'a dit (« le droit est offert au super-admin et plus au locataire », rouge).

**Blade échappe l'apostrophe.** `assertStringContainsString(__('…'), $html)` échoue sur
« n'est » devenu `&#039;` ; comparer avec `e(__('…'))`.

### Ce que les tests fixent (25 tests, 67 assertions)

- `PlanSwitchNoticeTest` : le texte sur les deux écrans, et `switchPlan()` qui repart
  toujours de la date du jour (la règle est dite, pas changée).
- `SettingsMenuGuardTest` : garde = droits du sous-menu ; un lecteur seul voit le menu ;
  un agent sans droit de réglages ne le voit pas ; le lecteur reçoit **403** à l'écriture ;
  la garde d'écriture de la route n'a pas bougé.
- `CategoryPanelScopeTest` : l'administrateur de société reçoit 403 **même avec le droit en
  poche** ; le marchand aussi ; le super-admin passe ; l'ancienne URI répond 404 ; le droit a
  changé de camp dans les semences ; les six catégories de livraison d'amorçage restent.
- `MerchantVatStatusTest` : non renseigné → taux société, exonéré → 0 (même si un taux
  traîne), taux propre ; une ligne ancienne garde son comportement ; la migration ne pose
  jamais `exempt` ; le formulaire refuse un statut inconnu ; le relevé d'un exonéré le dit en
  toutes lettres, et pas avec la phrase « aucune TVA » ; celui d'un non renseigné ne parle
  pas d'exonération.
- `StatementEmailTest` : l'émission met le courriel en file au compte marchand ; le passage
  planifié aussi, avec la marque de la société du relevé (F4) ; un compte sans courriel reçoit
  son relevé sans envoi ; la pièce jointe porte le nom du téléchargement et vient du même
  rendu ; **un seul** fichier d'`app/` nomme la vue du relevé ; **aucune** route d'écriture
  sur un relevé n'existe (D8).

`SidebarAndLocalesTest` gagne l'inventaire de l'écran des catégories côté super-admin ; les
filets `WebIsolationCoverageTest` et `BodyIdentifierCoverageTest` suivent les URI.

### Vérification — cinq sabotages

| Sabotage | Effet |
|---|---|
| l'avertissement R3 disparaît de la page des plans | **rouge** (1) |
| la garde du menu perd `general_settings_read` | **rouge** (1) |
| la route d'écriture des catégories ressort du panneau super-admin | **rouge** (2) |
| `VatRate` oublie le statut exonéré | **rouge** (2) |
| le relevé ne part plus par courriel | **rouge** (2) |

Filets et voisins : 260 tests verts. Suite complète : **1 172 tests, 46 635 assertions**, verte.

### Ce qui reste, hors de ce lot et à dessein

Le prorata d'abonnement, la colonne de langue des SMS, la personnalisation des catalogues
par société, la signature sur retour (après la recette). Et deux surveillances
d'exploitation pour R9 : la file des envois (`beninlink:file-attente`) et les rebonds de
courriel ; l'opposabilité juridique du PDF va au dossier de l'expert-comptable (R2).

## S76 — le serveur de recette se déploie comme la production, et ne peut pas encaisser (2026-10-03)

### D'où ça vient

Le plan d'action E1/E2 du porteur partait d'un diagnostic « secrets SSH probablement
manquants ». Vérifié dans les journaux GitHub Actions : les trois secrets `SSH_HOST`,
`SSH_USER`, `SSH_KEY` sont **vides**, et tous les déploiements de `main` — les sept du
jour et ceux d'avant — s'arrêtent au garde des secrets, en une seconde. La suite de
tests passe à chaque fois ; aucune version n'a jamais atteint un serveur par le workflow.

Et le workflow ne connaissait **qu'un serveur**. Un serveur de recette ne pouvait pas se
« déployer par le workflow réparé » : il n'y avait rien à réparer, il manquait un job.
Décision du porteur : la recette est un **vhost** sur la machine de production.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `deploy.yml`, job `deploy-recette` | suit les tests, ignore les pull requests, lit `RECETTE_SSH_*` ; une étape `config` pose `actif=true/false`, et l'étape SSH ne tourne que si `actif` — sans secrets, le job **se saute** au lieu d'échouer : la production ne dépend pas de la recette |
| `deploy.yml`, les deux étapes SSH | passent `DEPLOY_PATH` au serveur (`envs`), reprennent le dossier `deploy/` **entier** à la version déployée (deploy.sh appelle désormais un second script) |
| `deploy.sh` | `DEPLOY_PATH="${DEPLOY_PATH:-/var/www/beninlink}"` — sans variable, la production, comme avant ; appelle `verifier-env.sh .env` **avant** `php artisan down` |
| `verifier-env.sh` | autonome : refuse un `.env` hors production dont `FEDAPAY_ENVIRONMENT=live` **ou** dont une clé contient `_live_` ; avertit une production restée en sandbox ; refuse un `.env` absent |

Pourquoi deux lectures dans le garde : `FedaPayGateway` ne lit que `FEDAPAY_ENVIRONMENT`,
mais une clé live avec un environnement sandbox est exactement l'erreur de copier-coller
que le risque décrit. L'une peut être juste et l'autre fausse ; les deux sont lues.

Piège rencontré en l'écrivant : sous `set -euo pipefail`, un `grep` sans résultat dans une
substitution de commande **tue le script en silence** — les cinq essais rendaient 1 sans
un mot. `|| true` sur le `grep` : une clé absente n'est pas une erreur.

### Ce que le test fixe — `tests/Feature/RecetteDeploymentTest` (10 tests, 41 assertions)

Le workflow est **analysé** (Symfony Yaml) : le job de recette existe, suit les tests,
ignore les pull requests, lit ses trois secrets, se saute sans eux (pas de `exit 1` dans
l'étape de configuration), et les deux étapes SSH déploient par le même script, dossier
entier, chacune avec son chemin et ses secrets. `deploy.sh` lit `DEPLOY_PATH` et appelle le
garde **avant** la coupure (position dans le fichier). Et le garde est **exécuté** sur des
`.env` fabriqués : recette + live refusée, recette + clé live refusée même en sandbox,
recette sandbox acceptée, recette sans clé acceptée, production live acceptée, production
sandbox avertie sans refus, `.env` absent refusé.

### Vérification — trois sabotages

| Sabotage | Effet |
|---|---|
| `deploy.sh` ne vérifie plus le `.env` | **rouge** |
| l'étape de configuration du job de recette fait `exit 1` sans secrets | **rouge** |
| le garde oublie la lecture des clés `_live_` | **rouge** |

Suite complète : **1 182 tests, 46 676 assertions**, verte.

### Ce qui reste, et à qui — hors du dépôt

Tout ce lot est inerte tant que l'exploitation n'a pas : renseigné les **six** secrets
(production et recette), créé l'utilisateur et le dossier `/var/www/beninlink-recette`
avec le dépôt cloné, posé sa base et son `.env` (`APP_ENV=staging`, FedaPay sandbox), et
repointé `recette.beninlink.app` vers la vraie adresse. La fiche P0-P1 du guide de recette
le dit ligne par ligne. Le premier run vert des **deux** jobs de déploiement est le critère.

## S77 — la clé d'API sans repli, une porte qui refuse fermé, et des en-têtes à jour (2026-10-05)

### D'où ça vient

Deux lignes de dette du relevé de projet (`docs/CARTOGRAPHIE_PROJET.md` §7.2) : **T7** et
**T10**.

**T7.** S3 avait sorti la clé d'API du code (`env('API_KEY', …)`), mais en gardant la
valeur du socle en **repli** « pour qu'une installation existante ne casse pas ». Le
résultat : une installation sans variable `API_KEY` répondait à `123456rx-ecourier123456`,
la clé publique de l'éditeur, identique pour toutes les installations We Courier et lisible
dans ses APK (`courier_*_saas-main/lib/services/api-list.dart`). Et `CheckApiKeyMiddleware`
comparait avec `==` : clé configurée vide, en-tête `apiKey` vide, requête acceptée. Le
`.env.example` de `web/` ne mentionnait pas la variable — rien n'invitait à la poser.

Ce n'est pas une authentification (les données restent derrière `auth:sanctum`, S4), mais
un filtre qui laisse passer la clé que tout le monde connaît ne filtre rien.

**T10.** Trois en-têtes datés en retard : `web/CARTOGRAPHIE.md` « 2026-08-16 » avec un
contenu à S76, `CLAUDE.md` racine « 2026-07-06 », `DECISIONS_METIER.md` « 2026-09-06 »
avec D6-D14 depuis.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `config/rxcourier.php` | `'api_key' => env('API_KEY')` — **plus de repli** ; sans variable, `null` |
| `CheckApiKeyMiddleware` | **refus fermé** : clé configurée non-chaîne ou vide → 400 pour tous ; en-tête absent ou vide → 400 ; sinon `hash_equals` (strict, à temps constant). Message et code inchangés (`Invalid Api Key`, 400 — l'OpenAPI les documente déjà) |
| `web/.env.example` | ligne `API_KEY=` avec la commande de génération et le rappel « la changer = republier les apps » |
| `verifier-env.sh` | refuse, **dans tous les environnements** et avant la coupure, un `.env` sans `API_KEY` ou dont `API_KEY` vaut la clé publique du socle |
| en-têtes | les trois fichiers de T10 datent du lot courant et disent ce qu'ils couvrent |

Pourquoi le garde de déploiement, et pas seulement le middleware : sans repli, un `.env`
sans clé fait **tomber les deux apps au remontage du site**, en production comme en
recette. Le middleware est le filet ; le garde lit le problème là où il coûte le moins —
avant `php artisan down`, comme il lit déjà FedaPay (S76). Côté apps, rien à changer :
`mobile/src/api/config.ts` exige déjà `EXPO_PUBLIC_API_KEY` sans repli.

Ce que ça change pour une installation existante : **rien si `API_KEY` est posée** (le
guide `infra/env/` la demande depuis le début, et le `.env.example` d'infra la porte).
Une installation qui vivait sur le repli perd l'API au premier déploiement — c'est le
but, et `verifier-env.sh` le dit avant de couper.

### Ce que les tests fixent

`tests/Feature/ApiKeyFailClosedTest` (5 tests, 20 assertions) : la source de la config ne
porte plus la clé du socle et appelle `env('API_KEY')` sans second argument (parce que les
tests posent la clé par `config()`, un repli remis serait invisible au seul comportement) ;
clé configurée `null` ou `''` → la clé du socle, un en-tête vide, un en-tête absent sont
refusés ; clé posée → en-tête vide, absent, clé du socle, casse différente, espace en plus
sont refusés ; la bonne clé passe (`/api/v10/general-settings`, derrière `CheckApiKey`
seul) ; le middleware, **sans ses commentaires** (`php_strip_whitespace`), contient
`hash_equals(` et aucun `==`.

`RecetteDeploymentTest` (12 tests, 44 assertions) : les fixtures FedaPay portent toutes
une clé ; deux cas neufs — `.env` sans `API_KEY` refusé en `staging` **et** en
`production`, ligne vide comprise, message qui dit comment générer ; `API_KEY` égale à la
clé du socle refusée, même en production live.

### Vérification — quatre sabotages

| Sabotage | Effet |
|---|---|
| repli `'123456rx-ecourier123456'` remis dans la config | **rouge** (1) |
| `==` du socle remis, gardes retirées | **rouge** (2 : vide contre vide passe, forme) |
| le garde n'exige plus `API_KEY` | **rouge** (1) |
| `general-settings` sortie du groupe `CheckApiKey` (contrôle d'ancrage : la route lue est bien derrière la porte) | **rouge** (2) |

Piège rencontré en l'écrivant : le test de forme cherchait `==` dans la source du
middleware, et le trouvait… dans le **docbloc** qui raconte le `==` du socle. La
comparaison se fait sur la source **sans commentaires**.

Suite complète : **1 189 tests, 46 707 assertions**, verte.

### Ce qui reste

Rien côté code pour T7. Côté exploitation, la fiche P1 du guide de recette demande
désormais une `API_KEY` **propre à la recette** (ni celle de la production, ni celle du
socle), et P6 rappelle que `EXPO_PUBLIC_API_KEY` du profil de build la reprend. La règle
pour T10 : chaque lot met à jour les trois en-têtes avec sa section `## S<nn>`.

## S78 — une liste paginée dit où elle finit, et quatre routes paginaient sans le dire (2026-10-05)

### D'où ça vient

**T8** du relevé de projet : la taille de page était un **contrat implicite**. Quatre routes de
l'app marchand paginent (alertes douanières 20, notifications 20, relevés 10, portefeuille 10) ;
trois passent la collection dans l'enveloppe `{success, message, data}` du projet, et le
paginateur y **perd ses compteurs** à la sérialisation — l'app recevait un tableau nu et
déduisait « il en reste » d'une page pleine. La quatrième, la liste des relevés, renvoyait le
paginateur **nu** (`{data, links, meta}`), seule route sans enveloppe, et l'app ne gardait que
`data`. `MerchantAppCustomsContractTest` comparait les constantes de l'app aux `paginate(n)` du
serveur, fichier contre fichier : un filet qui lit du code, pas une réponse. Et il ne couvrait
pas le portefeuille, dont l'écran écrivait son `10` en dur.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `ApiReturnFormatTrait::responseWithPage()` | la même enveloppe, plus un bloc **`page` à la racine** : `current`, `per_page`, `last` (1 au minimum), `total`. À la racine et pas dans `data`, parce que `data` est tantôt un objet, tantôt un tableau — et parce que les apps installées lisent `data` tel quel : rien ne change pour elles |
| 9 méthodes d'API | répondent par `responseWithPage()` ; leurs clés dans `data` ne bougent pas |
| `InvoiceController::invoiceLists()` | rejoint l'enveloppe : `data` reste le **tableau** des relevés (l'app ne lisait que lui), `meta` laisse place à `page` |
| `resources/openapi/overlay.php` | schéma `Page`, aide `$paged`, les neuf opérations ; `Envelope.page` documenté ; spec régénérée |
| `mobile/src/api/client.ts` | `ApiPage`, `api.getPaged()` : garde `page` **seulement s'il a la forme attendue** (quatre entiers) |
| `mobile/src/api/pagination.ts` | la règle : `page` présent → `current < last` ; sinon « page incomplète = dernière » (serveur d'avant S78). `fetchAllPages()` pour une liste affichée entière |
| cinq modules d'API, cinq écrans | les modules rendent `{items, hasMore}` ; un écran ne compare plus une longueur à une constante |

### Ce que le filet a trouvé en l'écrivant

Le onzième filet, `ApiPaginationContractTest`, **énumère** les méthodes d'API qui paginent —
directement, ou par la méthode de dépôt qu'elles appellent, suivie d'un niveau (`invoiceLists()`
délègue à `get()`). Attendues : cinq. Trouvées : **neuf**. `FraudController`, `HubController`,
`ShopsController` et `SupportController` appellent `$this->repo->all()`, et ces `all()` font
`paginate(10)` pour les **tables du back-office**, avec qui le dépôt est partagé. L'API servait
donc **dix lignes** et aucune réponse ne le disait. Un marchand à onze boutiques en voyait dix
dans l'app, sur l'écran qui sert à créer un colis. Les quatre répondent désormais par
`responseWithPage()`, et `fetchShops()` **parcourt toutes les pages** (`fetchAllPages`, plafonné
à 50). Hubs, fraudes et tickets ne sont pas encore lus par un écran de l'app.

Même famille de défaut que S68 (l'écran douane lisait 20 sur N) : une **perte silencieuse**,
sans erreur, sans compteur. La différence : S68 l'avait vue à l'œil, S78 l'a mesurée.

### Ce que les tests fixent

`tests/Feature/ApiPaginationContractTest` (8 tests) : la liste des méthodes qui paginent est
exactement `PAGINEES` (ajouter une route paginée = l'inscrire, la faire répondre par
`responseWithPage()`, documenter `Page`) ; chacune contient `responseWithPage(` et pas
`responseWithSuccess(` ; chaque route correspondante référence `#/components/schemas/Page`
dans la spec **générée** et dans la spec **publiée** (`openapi:generate` oublié = rouge) ; puis
le comportement — 25 alertes : page 1 en sert 20 avec `{1, 20, 2, 25}`, page 2 en sert 5 ;
liste vide : `last` vaut 1 ; 25 notifications ; 12 mouvements ; 12 relevés avec `data` en
tableau et `meta` absent.

`MerchantAppCustomsContractTest` : la paire **portefeuille** rejoint les trois autres
(`WALLET_HISTORY_PER_PAGE`) ; l'écran douane lit `first.hasMore` / `next.hasMore` et ne compare
plus une longueur à `CUSTOMS_ALERTS_PER_PAGE` ; `pagination.ts` porte la règle **et** le repli ;
les cinq modules passent par `api.getPaged<` et `toPaged(`/`hasNextPage(` ; `fetchShops()`
appelle `fetchAllPages(fetchShopsPage)` ; `readPage()` valide les quatre entiers.

### Vérification — sept sabotages

| Sabotage | Effet |
|---|---|
| `customs/alerts` revient à `responseWithSuccess` | **rouge** (3 : forme, page 1, liste vide) |
| l'overlay de `wallet/history` oublie `Page` | **rouge** (1, spec générée) |
| la liste des relevés redevient le paginateur nu | **rouge** (2) |
| une route paginée disparaît de `PAGINEES` | **rouge** (1) |
| `customs.ts` repasse par `api.get` (perd `page`) | **rouge** (1) |
| `fetchShops()` ne lit plus que la première page | **rouge** (1) |
| la constante du portefeuille dit 20, le serveur sert 10 | **rouge** (1) |

Piège rencontré en l'écrivant : la conversion de `AccountTransactionController` a d'abord touché
`index()` — qui ne pagine pas — au lieu de `filter()`, parce que les deux `return` avaient le même
texte. Le filet l'a dit (`filter` sans `responseWithPage`) ; sans lui, `index()` aurait levé une
erreur de type en production. Second piège : `SupportController@index` existe aussi dans le
back-office ; la résolution des routes se limite à `App\Http\Controllers\Api\V10\`.

Côté app : `tsc --noEmit` et `eslint` verts sur les fichiers touchés.

Suite complète : **1 198 tests, 46 811 assertions**, verte.

### Ce qui reste

Les trois autres listes trouvées (hubs, fraudes, tickets) disent désormais où elles finissent,
mais aucun écran de l'app ne les lit encore : le jour où un écran les prend, il passe par
`getPaged` ou `fetchAllPages`, jamais par `api.get`. Pas de changement de taille de page dans ce
lot, et rien dans `mobile-livreur/`, qui ne consomme aucune liste paginée.

## S79 — deux dépendances de dev cassaient l'installation `--no-dev`, donc le déploiement (2026-10-05)

### D'où ça vient

Constat du porteur sur le VPS, le 2026-10-05 : `composer install --no-dev` — ce que fait
`deploy.sh` — puis `php artisan` échouent. Reproduit ici dans une copie du dossier, sans
`vendor/`, installée `--no-dev` : `composer install` s'arrête à son propre script
`package:discover` avec `Class "Barryvdh\Debugbar\ServiceProvider" not found`.

Deux fuites de dépendances de **développement** vers le code qui tourne en production :

| Fuite | Où | Effet sur une installation `--no-dev` |
|---|---|---|
| `barryvdh/laravel-debugbar` (`require-dev`) | `config/app.php` l'enregistrait **sans condition** dans `providers`, et sa façade dans `aliases` | `package:discover` tombe, donc `composer install` lui-même, donc `deploy.sh` |
| `fakerphp/faker` (`require-dev`) | sept semences lisent `Faker\Factory` (`ParcelSeeder`, `PlanSeeder`, `BlogSeeder`, `FaqSeeder`, `ServiceSeeder`, `PartnerSeeder`, `CompanyFrontendDataSeeder`), et `DatabaseSeeder` en appelle cinq | `db:seed` tombe (`Class "Faker\Factory" not found`) |

Pourquoi ça n'avait jamais mordu : la suite tourne **avec** les dépendances de dev, en
local comme dans l'intégration continue (`composer install` sans `--no-dev` dans le job
`tests`), et aucun déploiement n'a jamais atteint un serveur par le workflow (S76). Le
premier vrai `--no-dev` était celui du porteur.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `config/app.php` | les deux lignes Debugbar retirées (un commentaire dit où elle est partie) |
| `AppServiceProvider::registerDebugbar()` | enregistre le fournisseur **et** la façade `Debugbar` seulement si `class_exists` **et** `config('app.debug')` ; en production et en recette (`APP_DEBUG=false`) la barre ne se charge jamais, même installée |
| `composer.json` → `extra.laravel.dont-discover` | `barryvdh/laravel-debugbar` : la découverte automatique ne la rebranche pas dans le dos de la garde |
| `composer.json` / `composer.lock` | `fakerphp/faker` passe en **`require`**, **même version** (v1.23.1 — déplacé dans le verrou sans monter de version, hash du verrou rafraîchi par `composer update --lock`) |

Faker en production plutôt que des semences sans Faker : les semences **sont** du code de
déploiement (`db:seed` fait partie de la mise en service et du jeu pilote), et sept fichiers
du socle le lisent. Le paquet pèse peu et n'a aucune dépendance ; le réécrire était une
réécriture du socle pour rien.

### Ce que le test fixe — `tests/Feature/DevDependencyLeakTest` (4 tests, 19 assertions)

Douzième filet. Il lit `composer.lock`, reconstruit les **espaces de noms** des paquets de
`packages-dev` (`autoload.psr-4` / `psr-0`), et refuse toute référence à l'un d'eux — hors
commentaires, `token_get_all` — dans `app/`, `bootstrap/app.php`, `config/`, `database/`,
`routes/` et `resources/views/`. Une seule tolérance, nommée fichier par fichier : la barre
de débogage dans `AppServiceProvider`, et le test dédié vérifie qu'elle vit derrière
`!config('app.debug') || !class_exists(…)` **avant** l'enregistrement, et que le paquet est
dans `dont-discover`. Faker : en `require` dans le json **et** dans le verrou, et la raison
tient toujours (des semences le lisent — le jour où plus aucune ne le lit, le test le dit).
Puis le comportement : deux applications démarrées avec `app.debug` forcé avant
l'enregistrement des fournisseurs — `false` : `debugbar` n'est pas liée ; `true` : elle l'est,
comme avant.

⚠️ Piège rencontré en l'écrivant : `laravel/pint` embarque sa propre application sous
`App\` — pris tel quel, le préfixe désignait **notre** code et le filet voyait 200 fuites. Un
préfixe de paquet de dev qui est aussi l'un des nôtres (`autoload.psr-4` du projet) est écarté.
Second piège : `bootstrap/cache/packages.php` est un **manifeste** régénéré par
`package:discover` ; tant qu'il n'est pas régénéré, l'ancien y liste encore la barre. En
déploiement, `composer install` le régénère toujours.

### Vérification

Dans la copie `--no-dev`, après correctif : `composer install --no-dev` (installe Faker,
pas Debugbar), `package:discover` et `migrate` **passent** ; avec `APP_DEBUG=false`,
`debugbar` n'est pas liée. `db:seed` : les cinq semences Faker appelées par `DatabaseSeeder`
(`PlanSeeder`, `ServiceSeeder`, `FaqSeeder`, `PartnerSeeder`, `BlogSeeder`) **passent**, et
`migrate:fresh --seed` déroule vingt-trois semences avant de s'arrêter sur `CurrencySeeder` —
un `INSERT` brut du socle qui échappe une apostrophe à la façon MySQL (`'Pula\'s'`), que
**SQLite** refuse. C'est un artefact de la reproduction (pas de MySQL ici), pas du serveur :
sur MySQL, cette requête passe depuis l'origine. Faker n'y est pour rien. Quatre sabotages :

| Sabotage | Effet |
|---|---|
| le fournisseur Debugbar revient dans `config/app.php` | **rouge** (3) |
| Faker repasse en `require-dev` (json et verrou) | **rouge** (2 : les semences deviennent des fuites) |
| la barre sort de `dont-discover` | **rouge** |
| la garde oublie `app.debug` | **rouge** (2) |

Suite complète : **1 202 tests, 46 830 assertions**, verte.

### Ce qui reste

`CurrencySeeder`, `PageSeeder`, `SectionSeeder` et `CompanyFrontendDataSeeder` insèrent par
`DB::statement` avec des apostrophes échappées `\'` : du MySQL pur, jamais exécuté sur
SQLite (la suite sème par `SeedsTenant`, qui ne les appelle pas). Sans conséquence sur le
serveur ; à savoir le jour où un test voudrait jouer `db:seed` entier.

`symfony/yaml` est en `packages-dev` et n'est lu que par `RecetteDeploymentTest` : correct,
et le filet le dirait s'il migrait vers du code de production. Le job `tests` du workflow
installe toujours **avec** les dépendances de dev — c'est ce que la suite exige ; le filet
remplace, pour cette famille, l'installation `--no-dev` que l'intégration ne fait pas.

## S80 — la répétition de déploiement : ce que `deploy.sh` fait sur le serveur, rejoué avant (2026-10-05)

### D'où ça vient

S79 a laissé un constat : la suite tourne **avec** les dépendances de dev et sur SQLite, et
`deploy.sh` est le seul endroit qui installe `--no-dev` et parle à MySQL. Entre les deux,
tout ce qui ne se voit qu'en production. Les deux fuites de paquets de dev n'ont mordu que
sur le VPS ; `DevDependencyLeakTest` ferme cette famille, pas les autres. Lues dans
`deploy.sh`, les étapes que personne ne rejouait : `optimize:clear`,
`beninlink:tarification-prete`, `migrate --force`, `config:cache`, `route:cache`,
`view:cache`, `queue:restart`. `route:cache` refuse une route à fermeture, `view:cache`
compile toutes les vues et lève la première erreur Blade, `config:cache` fige un `env()`
lu hors des fichiers de configuration. Aucune ne tournait dans la suite.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `deploy.yml`, job `repetition` « Répétition de déploiement » | après `tests`, **sur une pull request comme sur `main`** (pas de `if`) ; service **MySQL 8** ; `composer install --no-dev` ; `.env` de recette (`APP_ENV=staging`, FedaPay sandbox, `API_KEY` propre) passé au **garde** `verifier-env.sh` ; installation d'une recette (`migrate`, `db:seed`, `beninlink:pilote`, zones de la société 1) ; **les commandes de `deploy.sh` dans son ordre** ; puis l'API répond — `general-settings` 200 avec la clé, 400 sans |
| `deploy` et `deploy-recette` | `needs: [tests, repetition]` — rien ne part sur un serveur si la répétition échoue ; les deux restent sautés sans leurs secrets |
| guide de recette §1 | la séquence d'installation gagne les **zones de la société du socle** et le constat de tarification — voir ci-dessous |

### Ce que la répétition a trouvé en l'écrivant

Rejouée d'abord ici, sur la copie `--no-dev` de S79 : après `db:seed` puis
`beninlink:pilote`, **`beninlink:tarification-prete` refuse** — « We Courier — pas prête :
aucune zone configurée ». Le jeu pilote pose les zones de **sa** société (id 2, « Company ») ;
la société du socle (id 1), créée par les semences, n'en a aucune. Or `deploy.sh` exécute
le constat pour **toutes** les sociétés, avant de migrer : sur une installation neuve montée
selon le guide de recette, **le premier déploiement s'arrêtait là**, et le `trap` remontait le
site. `beninlink:zones-tarifaires --societe=1 --installer --grille=…` règle la société du
socle ; le guide et le job le font désormais, et un test exige que les zones précèdent le
constat. Exactement le genre de défaut que S80 est fait pour voir avant le serveur.

### Ce que le test fixe — `tests/Feature/DeploymentRehearsalTest` (5 tests)

Il lit `deploy.sh` (commentaires exclus, `a && b && c` découpé) et en tire la liste des
`php artisan …` dans l'ordre : c'est un **inventaire** figé — `up`, `down`, `optimize:clear`,
`tarification-prete`, `migrate`, `config:cache`, `route:cache`, `view:cache`,
`queue:restart`, `up`. Puis le job : chaque commande (hors `down` / `up`, qui pilotent le site
vivant) est rejouée **après la précédente** dans les `run:` du job ; `composer install` porte
`--no-dev` et précède tout `artisan` ; service MySQL ; pas de `if` ; `verifier-env.sh`,
l'installation d'une recette et `general-settings` sont là ; les zones précèdent le constat ;
`deploy` et `deploy-recette` attendent `repetition`. `RecetteDeploymentTest` suit (`needs`).

### Vérification

Le job a été **rejoué ici avant d'être poussé**, à l'identique, sur la copie `--no-dev` de S79
contre une MariaDB 10.11 locale (MySQL 8 n'était pas installable dans cet environnement) :
`composer install --no-dev`, `.env` de recette accepté par `verifier-env.sh`, `migrate`,
**`db:seed` complet** (les semences à `\'` du socle passent — la réserve de S79 est levée pour
la famille MySQL), `beninlink:pilote`, zones de la société 1, puis `optimize:clear`,
`tarification-prete` (« chaque société tarife par zones »), `migrate`, les trois caches,
`queue:restart`, et `general-settings` : **200 avec la clé, 400 sans**. Le premier run du job
dans GitHub Actions, sur MySQL 8, est la preuve qui reste à lire.

Cinq sabotages :

| Sabotage | Effet |
|---|---|
| le job oublie `tarification-prete` | **rouge** (2) |
| `deploy` ne dépend plus de la répétition | **rouge** |
| le job installe avec les dépendances de dev | **rouge** |
| `deploy.sh` gagne `storage:link` sans que le job le répète | **rouge** (2 : inventaire et ordre) |
| les caches passent avant `tarification-prete` | **rouge** (ordre) |

Suite complète : **1 207 tests, 46 863 assertions**, verte.

### Ce qui reste

Le job coûte quelques minutes par run ; c'est le prix d'un S79 vu sur la pull request. Le
premier run réel se lit dans l'onglet Actions : si le service MySQL 8 refuse quelque chose
que MariaDB a accepté ici, c'est à corriger dans le job, pas à contourner. Et le jour où
`deploy.sh` change, l'inventaire du test le dit avant le serveur.

## S81 — les identifiants au nom libre : l'angle mort T5, mesuré puis fermé (2026-10-05)

### D'où ça vient

Depuis S65, `BodyIdentifierCoverageTest::estIdentifiant()` disait lui-même sa limite : il
reconnaît une **convention de nom** (`id`, `*_id`, `key`, `slug`), pas un rôle. Un champ qui
désigne une ressource sous un autre nom lui est invisible, et le relevé portait T5 : « un
identifiant au nom libre échappe ». Ce lot mesure ce que « libre » veut dire dans ce socle :
chaque `Model::find($request->x)` et `where('…', $request->x)` de `app/` dont le `x` n'est pas
de la convention. **Dix noms** : `account`, `from_account`, `to_account` (un compte bancaire),
`merchant`, `merchantId` (un marchand), `merchant_account`, `editid` (un compte de versement
d'un marchand), `accountId` (module de paiement en ligne, coupé D10), `hub` (un entrepôt),
`account_head` (un poste comptable, catalogue de plateforme). Donnés au filet, ils font entrer
**cinq routes d'écriture** qu'aucune liste ne classait.

### Ce que la mesure a trouvé

| Trouvaille | Où | Depuis quand |
|---|---|---|
| **Un virement MODIFIÉ déplaçait les soldes d'en face.** S64 avait gardé `store()` — le défaut « le plus lourd de la série », sortir de l'argent du compte d'un concurrent — mais pas `update()`, qui lit les mêmes `from_account` / `to_account` **nus** onze lignes sous sa garde S30 sur le virement. Modifier un virement de la maison en nommant un compte d'en face le débitait ou le créditait. | `FundTransferRepository::update()` | le socle ; S64 n'avait fermé que la création |
| **Le filet des lectures nues l'absolvait.** Son critère « même champ » cherchait le **nom** du champ entre la garde et la lecture : `$fund_transfer->from_account` — la colonne du virement déjà chargé — suffisait. Une garde sur un objet absolvait une lecture sur la requête. | `NakedReadCoverageTest::lecturesNues()` | S65 |
| **Une demande de retrait pouvait nommer le compte d'un AUTRE marchand.** Le panneau web écrivait `merchant_account` tel quel ; l'écran de traitement du back-office rend ensuite les coordonnées bancaires ou Mobile Money de ce compte-là. L'API l'avait fermé dès S7 (`ownsAccount()`) ; le panneau web est un autre contrôleur. | `MerchantPanel\PaymentRequest\PaymentRequestRepository::store()` et `update()` | le socle |

Les deux filtres (`admin/bank-transaction/filter` par `account`, `merchant/accounts/
account-transaction-filter` par `account`) étaient bornés. Ils sont **prouvés**, pas lus
(règle S58), par un marqueur que seule une ligne de résultat produit (règle S60 : la liste
déroulante des comptes rend `account_no` et `account_holder_name` de tous nos comptes).

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `FundTransferRepository::update()` | `identifiantsHorsPerimetre($request, [from_account, to_account])` **avant** `beginTransaction`, comme `store()` ; les deux lectures passent par `companywise()` |
| `PaymentRequestRepository` (panneau marchand) | `compteDeVersementEtranger()` : `merchant_account` doit être un compte du marchand connecté ; refus avant toute écriture, dans `store()` et `update()` — le pendant web de `ownsAccount()` |
| `BodyIdentifierCoverageTest` | `NOMS_LIBRES`, liste explicite et commentée, chaque nom avec sa ressource ; `estIdentifiant()` = convention **ou** liste ; cinq routes dans `PROUVEES` (`fund-transfer/store` et `income/balance-check` → `NakedReadRemainderScopeTest`, déjà prouvées par S64 sans être vues ; les trois autres → le test de ce lot). `HERITAGE` reste vide, plafond `0` |
| `NakedReadCoverageTest` | le « même champ » doit être lu **sur `$request`** (`preg_match('/(?:\$request\|request\(\))->x\b/')`), plus une colonne du même nom ; un cinquième témoin dans le test de détection, `gardeSurUneColonne`, que l'analyse doit **signaler** |
| `tests/Feature/FreeNamedIdentifierScopeTest` (4 tests) | le virement modifié vers un compte d'en face (source, puis destination) : refusé, aucun solde ni écriture de banque ne bouge ; le filtre des transactions par compte d'en face : rien ; la demande de retrait vers le compte d'un autre marchand, par la route puis par le dépôt (`update`) : rien d'écrit, rien de changé ; le filtre du marchand par le compte d'un autre : rien, pas même son numéro Mobile Money |

### Vérification

Neuf sabotages, chacun relancé sur le seul fichier qu'il doit faire tomber :

| Sabotage | Effet |
|---|---|
| `update()` ramené au socle (ni garde ni `companywise()`) | **rouge** — le test du lot, **et** le filet des lectures nues resserré |
| `update()` sans garde, `companywise()` gardé | **vert** — honnête : `find()` rend `null` sur un compte d'en face, le `null->balance` devient une `ErrorException`, le `catch` annule. La garde est la ceinture sur les bretelles : refus propre **avant** la transaction, et la même forme que `store()` |
| `update()` avec garde, lectures nues remises | **rouge** au filet des lectures nues — c'est exactement la ligne que S65 absolvait (mesuré avant de resserrer : seul `FundTransferRepository::update` apparaît, rien d'autre ne change) |
| demande de retrait sans garde, `store()` | **rouge** |
| demande de retrait sans garde, `update()` | **rouge** |
| filtre banque par compte sans `companywise()` | **rouge** |
| filtre marchand par compte sans `where('merchant_id')` | **rouge** |
| `account` retiré de `NOMS_LIBRES` | **rouge** (deux routes déclarées ne sont plus vues) |
| le filet S65 revenu à `str_contains` | **rouge** (témoin `gardeSurUneColonne`) |

Suite complète : **1 211 tests, 46 896 assertions**, verte.

### Ce qui reste

- La liste est **fermée par construction** : un onzième nom libre (`from`, `token`,
  `reference`…) échappe tant qu'il n'y est pas. La règle est écrite dans `web/CLAUDE.md` :
  un champ qui désigne une ressource s'appelle `*_id`, ou entre dans `NOMS_LIBRES` avec sa
  ressource. Le filet le dit aussi : une route nouvelle qui lit un nom de la liste doit se
  classer.
- Lu au passage, **non touché** : `MerchantPanel\PaymentRequestController::update()` relit
  la demande par `$this->repo->get(Auth::user()->merchant->id)` — l'identifiant du
  **marchand**, pas celui de la demande — et déréférence le résultat. L'écran web de
  modification d'une demande de retrait est donc cassé dans le socle, indépendamment de ce
  lot ; le dépôt qu'il appellerait est gardé (testé directement). À corriger dans un lot
  qui reprend cet écran, pas en passant.

## S82 — l'alerte douanière sur le détail du colis (M1), dans l'ordre web → OpenAPI → app (2026-10-05)

### D'où ça vient

S68 avait rendu l'écran « Alertes douanières » et le tableau de bord, et **écarté à dessein** un
septième cas : montrer l'alerte sur le colis lui-même. `customs/alerts` n'a pas de filtre par
colis, et filtrer côté client une liste paginée mentirait dès la deuxième page. Le relevé le
portait depuis comme **M1**. Un marchand qui ouvrait un colis export ne voyait ni le niveau de
l'alerte ni le document exigé ; il devait aller le chercher dans une autre liste.

### Le choix : un bloc avec le colis, pas un filtre sur la liste

S68 nommait un filtre `parcel_id` sur `customs/alerts` parce qu'il regardait depuis l'app. Vu
depuis `web/`, c'est un **paramètre d'identifiant de plus sur une route d'API** : une entrée de
plus à classer dans `IsolationCoverageTest`, à prouver, à tenir. Le bloc `customs_alerts` sur
`parcel/details/{id}` et `parcel/logs/{id}` s'adosse à une ressource dont la portée est **déjà
établie** (S17 : le colis est au marchand connecté, `ParcelScopeTest`) : ses alertes le
suivent, personne d'autre ne les lit, et l'app a une seule lecture, sans pagination. Un colis
domestique rend `[]`, pas une clé absente : l'app lit sans condition.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `Parcel::customsAlerts()` | relation `hasMany` — extension du modèle, le socle n'en avait pas |
| `Api\V10\ParcelController::details()` et `logs()` | `customs_alerts` dans la réponse, par `alertesDouanieresDe($parcel)` (`CustomsAlertResource`, libellés traduits) |
| `resources/openapi/overlay.php`, spec régénérée | les deux réponses documentent `customs_alerts`. **Au passage** : `parcel/logs/{id}` annonçait `{parcelLogs: [...]}` alors que le contrôleur sert `{parcel, parcelEvents}` depuis toujours — corrigé |
| `CustomsAlertTest` (+1) | deux exports, un domestique : chaque colis porte **sa** seule alerte sur les deux lectures (id, `parcel_id`, niveau, statut, document), le domestique un tableau vide |
| `mobile/src/api/parcels.ts` | `fetchParcelTimeline()` rend aussi `customsAlerts` (`customs_alerts ?? []`, un serveur d'avant S82 n'envoie pas la clé) |
| `mobile/app/(app)/parcel/[id].tsx` | une carte « Alertes douanières », absente sur un domestique ; par alerte : pays et catégorie, badge et bord gauche par `customsLevelColorName` (la table de S68), message, document requis, « Marquer traitée » si en cours (`resolveCustomsAlert`, l'alerte rendue à jour remplace la ligne), sinon le statut |
| `MerchantAppCustomsContractTest` (+1) | le module des colis **lit** `customs_alerts` (la lecture, pas le mot) et aucun des deux fichiers n'appelle `fetchCustomsAlerts` ; l'écran traverse la table des couleurs, offre « marquer traitée » et regarde le statut |
| `mobile/CLAUDE.md`, `web/CLAUDE.md` | la règle : les alertes d'un colis voyagent avec le colis ; jamais de filtre côté app sur la liste paginée |

### Vérification

`npx tsc --noEmit` et `npx expo lint` verts sur `mobile/`. Quatre sabotages, relancés depuis `web/`
(un premier passage lancé depuis la racine n'avait **rien mesuré** : `artisan test` hors de
`web/` ne trouve pas `phpunit.xml` et ne dit rien — relu, rejoué) :

| Sabotage | Effet |
|---|---|
| `logs()` sans `customs_alerts` | **rouge** |
| les alertes de toute la société au lieu de celles du colis | **rouge** (le second export verrait l'alerte du premier) |
| l'écran de détail perd sa carte douane | **rouge** |
| le suivi ne lit plus `customs_alerts` | **vert** d'abord — le test cherchait le **mot**, qu'un commentaire portait encore ; resserré à la lecture `data?.customs_alerts ?? []`, **rouge** |

Suite complète : **1 213 tests, 46 933 assertions**, verte.

### Ce que ce lot ne garantit pas

Comme S68 : `mobile/` n'a pas de lanceur de tests (M2), la propriété de l'écran est une lecture
de source. Le rendu de la carte sur un téléphone reste à voir à la recette (E1), avec un colis
export TG + textile du jeu pilote.

## S83 — l'écran de modification d'une demande de retrait relisait la demande par l'identifiant du marchand (2026-10-05)

### D'où ça vient

Lu au passage en S81, consigné dans son « ce qui reste ». Sur le panneau marchand,
`PaymentRequestController::update()` relisait la demande ainsi :

```php
$payment = $this->repo->get(Auth::user()->merchant->id);
if($payment->status == ApprovalStatus::PENDING){
```

Le formulaire envoie pourtant `id` en champ caché, et le dépôt `get()` cherche **parmi les
demandes du marchand** (S7). Il cherchait donc celle dont l'identifiant vaut celui du marchand.
Deux issues, toutes deux mauvaises : le plus souvent **rien**, et `$payment->status`
déréférençait un `null` en page d'erreur ; par coïncidence d'identifiants, **une autre** de ses
demandes décidait, par **son** statut, si la modification passait — une demande déjà traitée
redevenait modifiable si la demande n° (identifiant du marchand) était en cours. La relecture
ne visait **jamais** celle qu'il avait ouverte. Le dépôt `update()`, lui, lisait déjà
`$request->id`, gardé par `ownedPayments()` (S7) et `compteDeVersementEtranger()` (S81) : la
frontière tenait, l'écriture visait la bonne ligne, c'est la **décision** qui était prise sur
la mauvaise. Le jumeau de l'API fait juste (`get($id)`, 404 si vide, puis le statut). Un seul
contrôleur porte ce motif dans `app/Http/Controllers` (mesuré par `grep`).

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `MerchantPanel\PaymentRequestController::update()` | `get($request->id)`, `abort_if(blank, 404)` (forme S7, comme `delete()` juste dessous), puis le statut et le solde comme avant |
| `tests/Feature/MerchantPayoutRequestEditTest` (3 tests, par la route) | sans coïncidence d'identifiants, l'écran **répond** et modifie la demande ouverte (le socle : page d'erreur) ; la demande d'un autre marchand répond 404 et ne bouge pas ; une demande déjà **traitée** ne se modifie plus **même quand** une autre, en cours, porte par hasard l'identifiant du marchand (le socle : modifiée) |

### Vérification

Le **premier jet du test est tombé sur le hasard même du socle** : marchand n° 2, deuxième
demande n° 2, un garde de fixture l'a dit — et, relu après un sabotage **resté vert** sur
cette propriété, il m'a corrigé sur le défaut lui-même : le socle écrivait bien la demande
ouverte (le dépôt lit `$request->id`), c'est le **statut d'une autre** qu'il consultait. Le
test fabrique désormais les deux cas : deux marchands de bourrage pour qu'aucune demande ne
porte l'identifiant du mien (propriété 1), et une coïncidence construite à dessein, une
demande en cours au numéro du marchand devant une demande traitée (propriété 3).

| Sabotage | Effet |
|---|---|
| retour au socle (identifiant du marchand, sans 404) | **rouge** (3 : page d'erreur, voisin, traitée modifiée) |
| bon identifiant, sans le 404 | **rouge** (la demande du voisin : `null->status`) |

Suite complète : **1 216 tests, 46 945 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne touche pas aux cinq autres méthodes du contrôleur ni à `PaymentAccountController`,
relus pour le même motif et sains. Lancé pendant que la PR de S82 était encore ouverte, il
a d'abord été poussé dessus — onze minutes après sa fusion, en fait : rebasé sur `main`, il
part dans sa propre PR.

## S84 — un lanceur de tests dans les deux apps (M2), et la CI qui les regarde (2026-10-05)

### D'où ça vient

Trois lots de suite se terminaient par la même phrase : « `mobile/` n'a pas de lanceur de
tests, cette propriété est une lecture de source, pas un rendu ». S68, S78 et S82 ont posé
leurs garanties d'app en PHPUnit, en lisant des fichiers TypeScript comme du texte. Ça mord
quand une ligne disparaît ; ça ne dit pas qu'une couleur de gravité se trompe ni qu'un
montant s'affiche mal. Et la CI ne lançait ni `tsc` ni `expo lint` sur les apps : seule la
suite PHP tournait. Le relevé le portait comme **M2**.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `mobile/`, `mobile-livreur/` : `package.json`, `tsconfig.json` | `jest-expo` ~57 (aligné sur le SDK), `jest` 29, `@testing-library/react-native` 14, `@types/jest` ; script `test` ; `"jest": {preset: jest-expo}` ; `types: ["jest"]` pour que `tsc` connaisse `describe`/`it` |
| `mobile/src/domain/*.test.ts`, `src/api/pagination.test.ts` | les modules purs **exécutés** : les trois niveaux douaniers et le niveau inconnu traité comme bloquant, le plus grave d'un lot ; montants FCFA (arrondi, espace insécable, signe, taux à la française) ; les 33 statuts rabattus sur 7 étapes, une annulation vers l'étape **amont**, les onglets qui couvrent les 7 étapes une fois chacune ; les identifiants de type de livraison ; `hasNextPage` (le bloc `page` gagne, le repli à la page pleine), `fetchAllPages` (ordre, arrêt sur page vide, plafond) |
| `mobile/src/components/CustomsAlertCard.tsx` (+ `.test.tsx`) | la carte des alertes douanières **extraite** de l'écran de détail pour être **rendue** : rien sur un colis domestique ; BLOQUANT en `danger`, AVERTISSEMENT en `warning`, INFO en `info`, jamais l'ocre ni le vert primaire (le défaut de S68, enfin vu au rendu) ; message et document requis ; « Marquer traitée » sur une alerte en cours, qui remonte l'identifiant ; le statut à sa place sur une traitée |
| `mobile-livreur/src/domain/{money,parcelStatus}.test.ts` | les mêmes modules, identiques dans l'app livreur |
| `.github/workflows/deploy.yml`, job `apps` | matrice `mobile` / `mobile-livreur` : `npm ci`, `tsc --noEmit`, `expo lint`, `npm test -- --ci` ; sur pull request et sur `main` ; **ne conditionne pas** `deploy` (les apps partent par EAS) |
| `MerchantAppCustomsContractTest` | lit la carte dans son composant ; exige que l'écran passe par `<CustomsAlertCard` et que le test de rendu existe — c'est le **contrat** qui reste côté PHP : un test d'app ne peut pas lire `web/` |

### Ce que les tests ont trouvé en les écrivant

- **`toAmount(NaN)` rendait `NaN`**, donc « NaN FCFA » à l'écran : la garde `Number.isFinite`
  ne portait que sur la chaîne, pas sur le nombre. Corrigé dans les deux apps (même module).
- `@testing-library/react-native` 14 rend en **asynchrone** : sans `await render`, `screen`
  répond « render n'a pas été appelé ». Dit dans le test, pour le prochain.
- Le préréglage **ne s'applique pas sans la clé `jest` dans `package.json`** : l'app livreur
  l'a perdue une fois (une écriture concurrente avec `npm install`), et Jest a dit « Cannot use
  import statement outside a module » — un message qui ne nomme pas sa cause.

### Vérification

Les trois commandes vertes dans chaque app. Quatre sabotages, chacun relancé sur le seul
fichier qu'il doit faire tomber :

| Sabotage | Effet |
|---|---|
| AVERTISSEMENT peint en ocre (le défaut de S68) | **rouge**, deux fois : la table, et la carte rendue |
| `toAmount` sans la garde sur `NaN` | **rouge** |
| la carte offre « Marquer traitée » quel que soit le statut | **rouge** |
| une annulation de livraison rattachée à « livré » | **rouge** |

| Compte | mobile | mobile-livreur |
|---|---|---|
| Suites | 6 | 2 |
| Tests | 37 | 17 |

Suite PHP complète : **1 216 tests, 46 948 assertions**, verte (`DeploymentRehearsalTest` et `RecetteDeploymentTest`
acceptent le nouveau job : ils lisent `repetition`, `deploy` et `deploy-recette`, pas la liste).

### Ce que ce lot ne fait pas

Rendre les écrans d'`expo-router` entiers : le harnais de navigation est lourd et fragile ; la
valeur est dans les modules purs et les composants extraits, et c'est la règle écrite dans
`mobile/CLAUDE.md` — on extrait le morceau à prouver. Le contrôle visuel humain (E1) reste dû,
et M3 (les builds EAS) demande un compte. Le premier run du job `apps` dans GitHub Actions est
la preuve qui reste à lire : `expo lint` y tourne pour la première fois hors d'un poste.

## S85 — le contrat de l'app livreur, tenu en PHPUnit (2026-10-05)

### D'où ça vient

Tous les filets de contrat entre `web/` et les apps regardaient l'app **marchand** :
`OpenApiSpecTest` compare l'inventaire de `mobile/` à la spec, `ParcelStageTest` lit sa copie
des statuts, `MerchantAppCustomsContractTest` ses tailles de page et ses niveaux douaniers.
L'app **livreur** n'avait rien : aucun test ne comparait ses 18 endpoints à la spec ni aux
routes, personne ne lisait sa copie de `ParcelStatus`, seule la répétition de recette
l'exerçait, par les routes, sur le jeu pilote. Hors ligne 11, mais vivante et en recette avec
l'autre. S84 l'a rendu visible en lui donnant un lanceur de tests.

### Ce que la mesure a dit

**Aucun défaut de contrat.** Les 18 endpoints existent dans la spec, et aucun n'est réservé au
type marchand (`x-user-type`) ; la copie de `ParcelStatus` est identique à l'enum dans les deux
apps (noms, valeurs, ordre) ; les trois issues que l'app déclare (`DELIVERED`,
`PARTIAL_DELIVERED`, `RETURN_TO_COURIER`) sont exactement les trois du catalogue
`ApiParcelStatus` et du `switch` de `parcelStatusUpdate` ; aucune route livreur ne pagine (les
dépôts rendent `get()`), l'app n'a donc pas de `page` à lire.

Trois choses à consigner, pas à corriger :

- **Cinq entrées de l'inventaire n'ont aucun appelant** dans l'app : `refresh`, `parcelIndex`,
  `parcelPartialDelivered`, `parcelStatuses`, `paymentLogs`. L'inventaire documente le tag
  « Livreur » de la spec, pas seulement ce que l'app consomme ; chaque entrée a son motif dans
  `SANS_APPELANT`. ⚠️ Dont `refresh` : **l'app livreur ne rafraîchit pas son jeton**, elle se
  reconnecte. À relire si une session longue le demande.
- **Sept endpoints ne sont pas joués par la répétition de recette** : les trois sans appelant
  (`parcel/index`, `parcel-status`, `payment-logs`) et quatre routes communes aux deux apps
  (`refresh`, `sign-out`, `push/register`, `push/forget`), exercées par leurs propres tests
  (`ApiUserTypeTest`, `PushNotificationTest`, `TenantIsolationTest`). Listés dans
  `HORS_REPETITION`, pas inventés.
- **Un chemin d'API se cherche exactement, pas par préfixe** : le premier jet de la cinquième
  propriété croyait `deliveryman/parcel-status` joué par la répétition parce que
  `deliveryman/parcel-status-update` l'est. Écrit dans le test.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `tests/Feature/DeliverymanAppContractTest` (5 tests) | l'inventaire livreur dans la spec et jamais `x-user-type: merchant` ; appels ↔ inventaire (`SANS_APPELANT` motivé) ; les trois issues identiques dans l'app, le catalogue et le contrôleur ; aucune route `userType:deliveryman` qui pagine, contrôleur **et** dépôt appelé (un niveau, forme de S38) ; la couverture par la répétition (`HORS_REPETITION`) |
| `ParcelStageTest` (+1) | `BackendParcelStatus` des **deux** apps recopie l'enum mot pour mot — le test existant ne comparait que la table 33 → 7 de l'app marchand |
| `mobile-livreur/CLAUDE.md`, `web/CLAUDE.md` | la règle : chaque app a son filet ; ajouter un endpoint à l'inventaire, c'est l'appeler ou le motiver ; une issue nouvelle se fait accepter par `web/` d'abord |

### Vérification

Cinq sabotages, chacun relancé sur le seul fichier qu'il doit faire tomber :

| Sabotage | Effet |
|---|---|
| un endpoint renommé dans l'inventaire livreur (`deliveryman/profil`) | **rouge** |
| `DELIVERED: 10` dans la copie livreur de `ParcelStatus` | **rouge** |
| le contrôleur accepte une quatrième issue (`DELIVER`) | **rouge** |
| `parcelPaymentLogs()` se met à paginer | **rouge** |
| l'app déclare `RETURN_WAREHOUSE` comme issue | **rouge** |

Suite complète : **1 222 tests, 47 001 assertions**, verte.

### Ce que ce lot ne fait pas

Aucun écran ni endpoint n'est touché : c'est un lot de filets, et la mesure n'a rien trouvé à
corriger. Les cinq entrées sans appelant restent dans l'inventaire par choix (documentation du
tag), pas par oubli ; les retirer est une décision d'app, à prendre avec le rafraîchissement du
jeton si l'on y vient.

## S86 — une installation neuve réussit son premier déploiement (2026-10-05)

### D'où ça vient

L'installateur web (`InstallerController`) enchaîne `migrate:refresh` puis `db:seed`. Les
semences créent **deux** sociétés, « We Courier » (n° 1) et « Company » (n° 2), et
`DeliveryChargeSeeder` ne posait zones et grille que pour la seconde (`SOCIETE = 2`). Or
`deploy.sh` exécute `beninlink:tarification-prete` **avant** de migrer, et la commande sort en
erreur dès qu'une société n'a aucune zone (D4, étape 6). Le premier déploiement automatique de
toute installation neuve s'arrêtait donc là, à chaque tentative, sans qu'un mot du message dise
« votre base vient d'être amorcée ». Le guide de mise en service (§ 5 c, « le piège de cette
page ») et le job de répétition de S80 contournaient tous deux le piège **à la main** :
`zones-tarifaires --societe=1 --installer` après `db:seed`. La répétition rejouait donc le
contournement, pas l'installation que l'installateur fait.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `database/seeders/DeliveryChargeSeeder` | pose le **cadre** (quatre zones, trois délais, forfaits CEDEAO, par `ZoneCatalog::installer()`, qui crée ce qui manque et ne réécrit rien) pour **chaque** société de `general_settings` ; la grille de démonstration reste sur la société 2 — des montants appartiennent au transporteur, et un cadre sans montants est « prête » pour l'audit |
| `tests/Feature/FreshInstallReadinessTest` (4 tests) | les semences créent plus d'une société (sinon le piège n'existe pas) ; chaque société a son cadre ; `beninlink:tarification-prete`, **sans option**, sort en succès et passe chaque société en revue ; la grille n'est posée que pour la société de démonstration |
| `.github/workflows/deploy.yml` (job `repetition`) | **perd** le contournement : `db:seed` puis le jeu pilote, et le constat de `deploy.sh` mesure les semences seules |
| `DeploymentRehearsalTest` | le constat suit `db:seed`, et **aucun** `zones-tarifaires` dans le job — un contournement cacherait une régression du seeder |
| Huit tests existants | leurs fixtures cherchaient la zone Cotonou **par code seul** et trouvaient désormais celle de la société 1, créée en premier : la zone se cherche **par société et code** (voir ci-dessous) |
| Docs | `mise-en-service` § 5 c (piège fermé ; les commandes restent comme remède pour une base amorcée **avant** S86) ; `recette-pilote` § 1 ; grand livre E3 et T13 ; `web/CLAUDE.md` |

### Ce que la mesure a dit

La suite a mordu **dix fois** au premier passage, dans quatre fichiers (`CustomsAlertTest`,
`BusinessDecisionsTest`, `BackofficeCustomsGuardTest`, `DeliveryChargeResolverTest`), et le
grep en a montré huit : onze fixtures écrivaient `DeliveryZone::where('code', COTONOU)` sans
société, et une douzième cherchait « la zone d'un autre locataire » par
`company_id != settings()->id` — qui, depuis que la société 1 a des zones, rend la sienne avant
celle du voisin fabriqué. Aucun défaut de produit : les écrans et l'API refusaient bien une zone
d'une autre société (`Zone introuvable.`), c'est **la fixture** qui désignait une zone étrangère.
Forme retenue : `DeliveryZone::where('company_id', Merchant::firstOrFail()->company_id)
->where('code', …)`, et pour le voisin, sa société par son nom. ⚠️ Règle : **une zone de test se
cherche par société et code, jamais par code seul** — un code n'est unique que par société
(`unique(['company_id', 'code'])`).

### Vérification

Quatre sabotages, chacun relancé sur le seul fichier qu'il doit faire tomber :

| Sabotage | Effet |
|---|---|
| le seeder ne pose le cadre que pour la société 2 (forme d'avant S86) | **rouge** (2 tests : le cadre et le constat) |
| la grille de démonstration posée pour chaque société | **rouge** |
| `zones-tarifaires --societe=1 --installer` remis dans le job de répétition | **rouge** |
| le constat de tarification joué avant `db:seed` dans le job | **rouge** (2 tests : l'ordre de `deploy.sh` et le constat) |

Suite complète : **1 226 tests, 47 016 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne touche ni l'installateur, ni `GeneralSettingsSeeder` (deux sociétés restent créées : la
seconde porte l'abonnement et les utilisateurs de démonstration), ni `deploy.sh`. Une base amorcée
avant S86 garde sa société 1 sans zone : le guide dit la commande qui la rattrape. La grille de la
société 1 reste à saisir à l'écran ou à poser depuis `grille-nationale.csv` si c'est elle qu'on
exploite — un choix du transporteur, pas une semence.

## S87 — les comptes d'amorçage n'ont plus de mot de passe public en production (2026-10-05)

### D'où ça vient

Cinq semences du socle créent des comptes au mot de passe `12345678`, écrit dans le code
source que toutes les installations We Courier partagent : le super-administrateur
`admin@wemaxdevs.com`, l'administrateur `company@…` et l'agence `branch@…` (`UserSeeder`), un
marchand (`MerchantSeeder`) et un livreur (`DeliveryManSeeder`). Le guide de mise en service le
disait en § 5 a — « les changer avant que le site soit joignable » — et ne nommait que trois des
cinq. Une consigne, que rien ne mesurait, alors que `db:seed --force` est la deuxième commande de
la première installation. Les boutons « comptes de démonstration » de la page de connexion
portent les mêmes identifiants, mais derrière `env('DEMO')`, vide en production : relu, pas un
défaut.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `app/Services/Install/SeedAccounts` | un seul endroit : la liste des cinq comptes, le mot de passe public, `motDePasse($email)` (le public en `local`/`testing`, un tirage de 20 caractères lettres et chiffres partout ailleurs), `annoncer()` qui l'affiche **une fois** dans la sortie de la semence |
| `UserSeeder`, `MerchantSeeder`, `DeliveryManSeeder` | `Hash::make(SeedAccounts::motDePasse(…))` puis `annoncer($this->command, …)` — rien d'autre ne change dans le socle |
| `beninlink:comptes-amorcage` (`SeedAccountsCommand`) | **constate** : les comptes d'amorçage dont le mot de passe vérifie encore le public, par `Hash::check` ; sort en erreur s'il en reste. Un compte supprimé n'est pas un blocage ; un compte renommé en est un |
| `deploy.sh` | l'exécute ~~avant `artisan down`~~ — **corrigé en S88** : après `git pull` et `optimize:clear`, avant `migrate`. Appelée avant la mise à jour du code, la commande n'existait pas sur le serveur (« Command not defined ») : le premier déploiement qui a atteint le VPS est tombé là, site non coupé, base intacte |
| Job `repetition` | rejoue la commande en tête des commandes de `deploy.sh` ; en `staging`, les semences ont tiré au sort, le constat passe — il mesure donc la règle, pas un contournement |
| `tests/Feature/SeedAccountsPasswordTest` (6 tests) | en test, les cinq comptes gardent le public (la liste est bien celle des semences) ; en production simulée, un mot de passe distinct par compte, affiché, et c'est celui posé ; le constat rouge tant qu'un compte reste public, nommé ; vert une fois changés ou supprimés ; il lit le mot de passe, pas le nom ; `deploy.sh` le joue avant la coupure |
| `DeploymentRehearsalTest` | la liste des commandes de `deploy.sh` gagne `beninlink:comptes-amorcage` en tête |
| Docs | `mise-en-service` § 5 a (les cinq comptes, la règle, le remède pour une base amorcée avant S87), `recette-pilote` § 1, grand livre E3, `web/CLAUDE.md` |

### Ce que l'écriture a appris

- **Un `PendingCommand` de test ne s'exécute qu'à `run()` ou à sa destruction.** Assigné à une
  variable pour lui ajouter des attentes dans une boucle, il a tourné **après** les lignes qui
  changeaient les mots de passe, et l'attente « la sortie nomme `company@…` » est tombée sur une
  sortie qui ne le nommait plus. D'où le `->run()` explicite, et la règle dans `web/CLAUDE.md`.
- `PlanSeeder` vit dans `Database\Seeders\Backend\SuperAdmin`, pas à la racine des semences :
  `SeedsTenant` le sait, un import copié de mémoire ne le savait pas.
- En production, `db:seed` demande `--force` : le test le passe, comme `deploy.sh`.

### Vérification

Quatre sabotages, chacun relancé sur le seul fichier qu'il doit faire tomber :

| Sabotage | Effet |
|---|---|
| `motDePasse()` rend le public dans tous les environnements | **rouge** (production simulée : les comptes vérifient le public) |
| `UserSeeder` remet `Hash::make('12345678')` sur le super-administrateur | **rouge** (le super-administrateur vérifie le public, rien d'affiché pour lui) |
| le constat sort en succès même avec des comptes publics | **rouge** (2 tests : comptes publics et compte renommé) |
| `beninlink:comptes-amorcage` retiré de `deploy.sh` | **rouge** (2 fichiers : `SeedAccountsPasswordTest` et la liste de `DeploymentRehearsalTest`) |

Suite complète : **1 232 tests, 47 072 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne renomme pas les comptes (`@wemaxdevs.com`, « Mirpur-10, Dhaka ») ni ne touche le jeu
pilote, dont le mot de passe commun est un choix de recette, refusé en production par sa
commande. Il ne vérifie que les **comptes d'amorçage** : un utilisateur qui choisit lui-même
`12345678` relève d'une politique de mots de passe, pas de ce lot. Et il ne change rien à
`local` : le poste de développement garde les identifiants du socle.

## S88 — l'installateur n'est plus joignable sur une base installée (2026-10-05)

### D'où ça vient

Relu en préparant S87. `routes/web.php` monte trois routes d'installation, et seule `GET /install`
portait le garde `IsNotInstalled`. `POST /installing` et **`GET /finish`** ne portaient que `XSS`,
et `InstallerController::finish()` fait, sans authentification : `SHOW TABLES` et `Schema::drop`
de **chaque table**, `migrate:refresh`, `db:seed`, puis écrit le nom, le courriel et le **mot de
passe du compte n° 1** depuis la requête, et réécrit `APP_INSTALLED` et `APP_URL` dans le `.env`.
Sur une installation terminée, une requête anonyme vidait la base et posait l'administrateur de
son choix. Les routes sont montées sur tous les hôtes, avant le bloc conditionné par
`app_installed`, et aucun test ne nommait l'installateur.

Second maillon : le garde ne reconnaissait une installation que par `APP_INSTALLED=yes` dans le
`.env`. La première installation que le guide prescrit (`migrate` + `db:seed`, « jamais par
`/install` ») n'écrit pas ce drapeau — seul l'installateur web le fait — et `verifier-env.sh` ne
le vérifiait pas.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `routes/web.php` | les **trois** routes sous `['XSS', 'IsNotInstalled']` |
| `IsNotInstalledMiddleware` | `installee()` : tables présentes **et** (drapeau `yes` **ou** table `users` non vide) ; sur une base installée, `installing` et `final` répondent **404**, l'écran redirige vers `/` si le drapeau est posé, sinon **403** en texte nu qui dit quoi poser (une redirection bouclerait avec `IsInstalled`, et la page 403 du socle n'affiche pas le message d'un `abort()`) |
| `verifier-env.sh` | refuse un `.env` dont `APP_INSTALLED` n'est pas `yes`, dans tous les environnements, avant de couper le site (forme de S77) |
| `tests/Feature/InstallerLockTest` (4 tests) | les trois routes portent le garde (lu dans `Route::getRoutes()`) ; base installée : `GET /finish` et `POST /installing` 404, `/install` redirige, **nombre de tables, d'utilisateurs et de sociétés inchangé**, aucun compte « pirate » ; sans drapeau, base peuplée : mêmes 404, 403 explicite ; base vierge : l'écran répond (le flux du socle survit) |
| `RecetteDeploymentTest` (+1) | un `.env` sans `APP_INSTALLED=yes`, ou avec une autre valeur, est refusé ; les fixtures qui passent portent le drapeau |
| `deploy.sh`, job `repetition`, `DeploymentRehearsalTest` (+1) | **correctif de S87** : `beninlink:comptes-amorcage` passe après `optimize:clear` et avant `tarification-prete` ; nouveau filet : toute commande `beninlink:*` du script suit `git pull`, `composer install` et `optimize:clear` (voir ci-dessous) |
| Docs | `mise-en-service` § 5, grand livre E3, « Ne jamais casser » dans `web/CLAUDE.md`, tableau des constats de sécurité (S88) |

### Ce que le premier déploiement réel a appris

Le point de contrôle de 20 h a trouvé **`main` rouge** : la fusion de S87 (run 208) est le
**premier déploiement à avoir atteint le VPS** — les secrets `SSH_*` sont désormais en place
(E3 avance), `git fetch` a répondu, `verifier-env.sh` a validé le `.env` de production (FedaPay
en sandbox, averti). Puis `php artisan beninlink:comptes-amorcage` : **« Command not defined »**.
S87 l'avait placée **avant `git pull`**, à côté du garde du `.env` ; or seuls les scripts de
`docs/guides/infra/deploy/` sont pris à la version déployée (`git checkout FETCH_HEAD -- …`), le
code PHP du serveur est encore l'ancien, et il ne connaît pas la commande. Conséquence bénigne par
construction — l'arrêt précède `artisan down`, le site n'a pas été coupé, rien n'a été migré —
mais **plus aucun déploiement ne passait**. ⚠️ Le job de répétition ne pouvait pas le voir : il
tourne sur le code neuf de bout en bout.

Correctif porté dans ce lot : la commande passe après `optimize:clear`, avant
`tarification-prete` (coupée puis remontée par le filet, comme elle). Et un filet neuf dans
`DeploymentRehearsalTest` : **toute commande `beninlink:*` de `deploy.sh` suit `git pull`,
`composer install` et `optimize:clear`** — sabotage (la commande remise avant `git pull`) :
**rouge** sur deux fichiers. Règle dans `web/CLAUDE.md`. La CI de la PR #157 avait par ailleurs
été **annulée** avant tout test (jobs `cancelled` à 19 h 35, sans échec) ; le push du correctif la
relance.

### Ce que l'écriture a appris

- La vue `installer/index.blade.php` lit **`$_SERVER['HTTP_HOST']` et `SCRIPT_NAME` directement**,
  pas la requête : hors nginx elle tombe en 500. Le test les pose comme le serveur le ferait.
- La page 403 du socle (`errors/403.blade.php`) affiche un texte fixe, jamais le message d'un
  `abort(403, …)` : un message qui doit être lu part en réponse nue.

### Vérification

Quatre sabotages, chacun relancé sur le seul fichier qu'il doit faire tomber :

| Sabotage | Effet |
|---|---|
| `finish` ressort du groupe gardé (forme du socle) | **rouge** (3 tests : le garde manque sur `finish`, et `GET /finish` ne répond plus 404 — sur SQLite il tombe sur `SHOW TABLES`, en MySQL il aurait vidé la base) |
| `installee()` ne regarde plus que le drapeau | **rouge** (sans drapeau, la base peuplée redevient « vierge ») |
| `verifier-env.sh` sans le refus `APP_INSTALLED` | **rouge** (`RecetteDeploymentTest`) |
| le garde laisse passer `installing` sur une base installée | **rouge** (2 tests : `POST /installing` passe le garde) |

Suite complète : **1 238 tests, 47 104 assertions**, verte (après le correctif de l'ordre de `deploy.sh`).

### Ce que ce lot ne fait pas

Il ne retire ni ne réécrit l'installateur (0 fichier supprimé) : le flux du socle reste entier pour
une base vierge, il cesse seulement d'être joignable une fois la base installée. Il ne touche pas
`IsInstalledMiddleware`, qui redirige vers `/install` quand le drapeau manque — l'écran répond
alors 403 avec la ligne à poser, et `verifier-env.sh` empêche qu'un déploiement parte sans elle.
La vérification du code d'achat (`purchaseVerify`) et la vue d'installation restent telles quelles.

## S89 — un déploiement refusé remet l'ancien code, pas seulement le site (2026-10-06)

### D'où ça vient

Le premier déploiement réel (S88) a fait relire `deploy.sh` avec les yeux du serveur. Les trois
gardes qui peuvent refuser un déploiement — `comptes-amorcage`, `tarification-prete`, puis
`migrate` — tournent **après** `git pull` et `composer install`, et c'est nécessaire : ils vivent
dans la version déployée (S88 l'a appris en les appelant avant). Mais sur refus, le filet
`remonter_le_site()` ne faisait que `php artisan up` : le serveur repartait avec le **nouveau code
sur l'ancien schéma**, et le site était de nouveau servi dans cet état. La base était intacte, le
code ne l'était pas. Dès la fusion de la #157, c'est le scénario le plus probable : la base de
production a été amorcée avant S86 et S87, `comptes-amorcage` ou `tarification-prete` refusera.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `deploy.sh` | `REVISION_SERVIE="$(git rev-parse HEAD)"` et `MIGRE=0` **avant** `artisan down` ; le filet revient à cette révision (`git reset --hard`, `composer install --no-dev`, `optimize:clear` puis les trois caches) **si** `MIGRE` vaut encore 0 et que HEAD a bougé, puis remonte le site ; `MIGRE=1` juste après `migrate --force` — à partir de là, l'ancien code serait faux, il reste le nouveau et le filet le dit |
| `.github/workflows/deploy.yml` | les deux étapes `appleboy/ssh-action@v1` perdent `script_stop`, entrée que v1 ne connaît plus (avertissement dans chaque journal) ; `set -euo pipefail` est dans le script |
| `DeploymentRehearsalTest` (+2) | le parseur des commandes du script ignore désormais le **corps du filet** (chemin de secours, pas à rejouer) — la liste attendue perd le `up` du filet ; un test lit le filet : révision notée avant `git pull`, `git reset --hard "$REVISION_SERVIE"` puis `composer install --no-dev` puis `php artisan up`, garde `"$MIGRE" = 0`, `MIGRE=1` entre `migrate` et la commande suivante ; un test lit les deux étapes SSH : pas de `script_stop`, `set -euo pipefail` présent |
| Docs | `mise-en-service` § 6 : la table « où il s'arrête » gagne la colonne **code servi** et la ligne `comptes-amorcage` ; encadré **« le premier déploiement réel »** : les trois refus attendus sur une base d'avant S86/S87 et leurs remèdes sur le serveur ; `web/CLAUDE.md` |

### Ce que l'écriture a appris

- `strpos` sans décalage trouve la **première** occurrence : `php artisan config:cache` vit aussi
  dans le filet, avant `migrate` dans le fichier. Une position « après la migration » se cherche à
  partir de la migration.
- Après la migration, revenir en arrière serait **pire** : l'ancien code ne connaît pas le nouveau
  schéma. D'où le drapeau `MIGRE`, levé après `migrate --force` et lu par le filet.

### Vérification

| Sabotage | Effet |
|---|---|
| le filet ne fait plus que `php artisan up` (forme d'avant S89) | **rouge** |
| `MIGRE=1` retiré après la migration | **rouge** |
| `REVISION_SERVIE` notée **après** `git pull` | **rouge** |
| `script_stop: true` remis sur une étape SSH | **rouge** |

Suite complète : **1 240 tests, 47 121 assertions**, verte.

### Vu en production (run 212, fusion de la #157, 2026-10-06 03:43 UTC)

Exactement le scénario annoncé. `verifier-env.sh` est passé (le serveur porte donc
`APP_INSTALLED=yes`), puis `git pull`, `composer install`, `optimize:clear`, et
`beninlink:comptes-amorcage` a **refusé** : les cinq comptes d'amorçage sont à `12345678` sur la
base de production. Le filet S89 a fait son travail : « Retour à la révision servie avant le
déploiement (`fd8daa0`) », `composer install` (rien à changer), caches reconstruits, « Application
is now live ». Le serveur sert toujours la révision de la fusion de S86 — S87, S88 et S89 n'y
sont pas, et n'y seront pas tant que les comptes ne seront pas changés (puis, probablement, les
zones de la société 1 posées). Remèdes : encadré « le premier déploiement réel » du guide.

### Ce que ce lot ne fait pas

Il ne change pas la forme du déploiement (pas de répertoire de version ni de lien symbolique
basculé : ce serait une autre architecture), ni l'ordre des gardes. Un échec **pendant** la
migration reste le cas documenté de longue date : MySQL ne défait pas un schéma à moitié
modifié, la sauvegarde se prend avant ; le filet remet alors l'ancien code (la migration n'est
pas tenue pour appliquée) et le dit. Il ne pose rien sur le serveur : les remèdes du premier
déploiement réel sont les vôtres, listés dans le guide.

## S90 — l'API négocie sa langue par `Accept-Language` (T6) (2026-10-06)

### D'où ça vient

Ligne T6 du grand livre : l'API ignorait `Accept-Language`, chaque `message` de l'enveloppe
sortait dans la locale du serveur. Acceptable tant que le produit est français seul — mais une
langue se négocie, elle ne se devine pas, et `config/locales.php` sert déjà `fr` et `en` au web
(sélecteur de session, `LanguageManager`). Rien d'équivalent côté API.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `App\Http\Middleware\ApiLocale` | sur le groupe `api` (`Kernel`) : `negocier()` lit l'en-tête (poids `q`, ordre d'apparition, sous-code principal `en-GB` → `en`, `*` et `q=0` ignorés), retient la première langue servie ; sinon rien ne change. La locale est **remise après la réponse** (`try … finally`) : c'est un état de l'application, pas de la requête |
| `resources/openapi/overlay.php` → `public/openapi/v10.json` | `Envelope.message` : « dans la langue négociée par `Accept-Language` (`fr` par défaut, `en`) » |
| `tests/Feature/ApiLocaleTest` (7 tests) | sans en-tête : français ; `en` et `en-GB,en;q=0.9` : anglais ; `zh`, `bn-BD`, `*`, `xx` : français ; `q` décide, puis l'ordre ; `negocier()` pur ; locale remise après la réponse ; le garde est sur `api` et pas sur `web` |
| Docs | `web/CLAUDE.md` (règle et piège du client de test), grand livre T6 barré |

### Ce que l'écriture a appris

- ⚠️ **Le client de test de Laravel envoie `Accept-Language: en-us,en;q=0.5` par défaut**
  (Symfony `Request::create()`). Dès que l'API négocie, **deux tests existants sont tombés**
  (`ApiUserTypeTest`, `ParcelWalletBalanceGuardTest` : un libellé français attendu, l'anglais
  reçu). Plutôt que de corriger test par test, `Tests\TestCase::setUp()` pose
  `HTTP_ACCEPT_LANGUAGE` à vide pour toute la suite : « sans en-tête » redevient ce que les apps
  font, et un test qui veut une langue la demande explicitement (`ApiLocaleTest`).
- `App::setLocale()` **persiste** entre deux requêtes du même processus (tests, Octane, files) :
  un garde qui change la locale la remet.
- `['a' => ''] + $entetes` garde la valeur de **gauche** : la fusion d'en-têtes se fait par
  `array_merge`, sinon l'en-tête du test est écrasé par le défaut.

### Vérification

| Sabotage | Effet |
|---|---|
| `ApiLocale` retiré du groupe `api` | **rouge** (3 tests) |
| `negocier()` ignore `q` (ordre d'apparition seul) | **rouge** |
| la locale n'est plus remise après la réponse | **rouge** |

Suite complète : **1 247 tests, 47 149 assertions**, verte.

### Ce que ce lot ne fait pas

Les apps restent françaises et n'envoient pas l'en-tête : rien ne change pour elles. Aucune
nouvelle langue n'est servie (`es`, `ar`, `bn`, `in`, `zh` restent non servies, S-lot 5), et le web
garde sa négociation par session. Les SMS gardent `SmsTemplate::locale()` (D14).

## S91 — l'app marchand ne sert plus que le barème par zones (M4) (2026-10-06)

### D'où ça vient

Ligne M4 du grand livre : pendant la transition de D4, l'écran Tarifs retombait sur les quatre
colonnes héritées quand `zones` était vide, et le formulaire de création offrait « Barème hérité
(par type de livraison) » (valeur 0). La condition de M4 — « plus aucun serveur ne sert les
colonnes » — est remplie depuis l'étape 6 (2026-09-07) ; et depuis S86 `tarification-prete`
interdit à toute société sans zones de se déployer. L'entrée « barème hérité » ne menait donc plus
qu'à un refus du serveur (`zone_id` obligatoire) : un choix offert qui échoue toujours.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `mobile/src/domain/zoneChoices.ts` (+ test, 2) | une entrée par zone, plus de valeur 0 |
| `mobile/app/(app)/parcel/new.tsx` | les choix de zone viennent du module ; `ChoiceGroup` reçoit `zoneId` tel quel ; commentaires mis à jour |
| `mobile/app/(app)/rates.tsx` | une seule forme ; `zones` vide → `rates.noZone` (anomalie du transporteur) ; le type `DeliveryRate` n'est plus lu par l'écran (l'API le sert encore, le module `merchant.ts` le garde) |
| `mobile/src/i18n/fr.ts` | `parcels.legacyRoute` et `rates.empty` retirés |
| `web/tests/Feature/MerchantAppPricingContractTest` (3 tests) | lit les sources de l'app : pas de `legacyRoute` ni de valeur 0, `zoneChoices` utilisé et testé ; l'écran Tarifs sans repli sur `rates` ; les traductions suivent |
| Docs | `mobile/CLAUDE.md` (règle réécrite), grand livre M4 barré |

### Vérification

`tsc`, `expo lint`, `npm test` (39 tests) verts dans `mobile/`.

| Sabotage | Effet |
|---|---|
| l'entrée « barème hérité » (valeur 0) remise dans `zoneChoices` | **rouge** (jest : 2 tests ; filet web après resserrage — le premier jet laissait passer une entrée devant le `map`) |
| le repli `rates.length` remis dans l'écran Tarifs | **rouge** (filet web) |

Suite complète `web/` : **1 250 tests, 47 165 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne touche pas l'API : `settings/delivery-charges` sert toujours `deliveryCharges` (lignes
zonées), que l'app ignore désormais à l'écran. Il ne touche pas aux types de livraison
(`deliveryType.ts`, `delivery_type_id`), toujours envoyés à la création. L'app livreur n'a pas
d'écran de barème.

## S92 — les semences parlent du Bénin (2026-10-06)

### D'où ça vient

Le socle amorçait toute installation avec l'identité de son éditeur : société « We Courier »
à Mirpur, six agences de Dhaka, adresses bangladaises sur chaque compte, raison sociale
« WemaxDevs » pour le marchand, banque `NRB Commercial Bank`, mobile money `Bkash` / `Nagad` /
`Rocket`, numéros à onze chiffres, carte de la vitrine centrée sur Dhaka. Depuis S86 une
installation neuve réussit son premier déploiement, depuis S87 ses comptes n'ont plus de mot de
passe public : le transporteur qui ouvre son back-office le premier jour lisait encore un autre
pays. S87 l'avait laissé de côté explicitement (« il ne renomme pas les comptes »).

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `GeneralSettingsSeeder` | société 1 = `BeninLink` (`contact@beninlink.app`, Cadjèhoun) ; société 2 = « Transporteur de démonstration » (Akpakpa) ; copyright en français ; préfixes `we` / `co` et couleurs inchangés |
| `HubSeeder` | six agences : Cotonou — Cadjèhoun, Cotonou — Akpakpa, Abomey-Calavi — Godomey, Porto-Novo — Ouando, Parakou — Centre, Bohicon — Gare. **Ids 1 à 6 conservés** (livreur → 1, agence → 2, marchand → 4) |
| `UserSeeder`, `DeliveryManSeeder`, `MerchantSeeder`, `MerchantshopsSeeder` | adresses de Cotonou et d'Abomey-Calavi ; numéros à dix chiffres `01…` ; marchand « Boutique Démo Cotonou », boutiques au marché Dantokpa. **Courriels inchangés** (S87) |
| `AccountSeeder`, `PaymentAccountSeeder`, `MerchantPaymentSeeder` (hors `DatabaseSeeder`) | `Ecobank Bénin`, agence Ganhi ; mobile money `MTN MoMo` / `Moov Money` — les deux opérateurs que FedaPay sert |
| `ParcelSeeder` (hors `DatabaseSeeder`) | téléphones à dix chiffres |
| `CompanyFrontendDataSeeder`, `Backend/FrontWeb/SectionSeeder` | `map_link` pointe Cotonou (6.3703, 2.3912) ; rien d'autre (MySQL brut, textes anglais : lot à part) |
| `tests/Feature/SeedsSpeakBeninTest` (4 tests) | joue `SeedsTenant` : sociétés, agences, personnes, boutiques sans « Dhaka / Mirpur / Bangladesh / Wemax / +88 / Bkash… », adresses au Bénin, numéros `^(\+229)?01\d{8}$`, `BeninLink` et six agences ; les identifiants de `SeedAccounts::COMPTES` existent toujours ; les semences hors préfixe sont lues à la source, `CurrencySeeder` seul excepté |
| Docs | `web/CLAUDE.md` (décision), grand livre chantier 1, en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| l'agence 1 remise à « Mirpur 10, Dhaka, Bangladesh » | **rouge** (2 tests : agences, source) |
| le numéro de l'admin ramené à huit chiffres | **rouge** (personnes) |

Suite complète `web/` : **1 254 tests, 47 800 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne renomme pas les comptes (`Admin`, `Company`, `Branch`, `Merchant`, `Delivery Man`) ni leurs
courriels `@wemaxdevs.com` / `@wemaxit.com` : S87 les surveille par nom, et une installation déjà
semée les garde. Il ne francise pas la vitrine (`CompanyFrontendDataSeeder`, `SectionSeeder`,
`PageSeeder` : `DB::statement` MySQL, jamais joués par la suite) ni le catalogue des devises.
Une base **déjà amorcée** ne change pas : les semences ne rejouent pas ; le transporteur corrige
son identité dans Réglages, comme n'importe quelle société.

### Vu en production (runs 217 et 219, 2026-10-06)

Entre le run 212 (03:43Z, refusé par `comptes-amorcage`, filet S89 à l'œuvre) et le run 217
(04:32Z, fusion de la #159 / S91), l'opérateur a agi sur le serveur : `beninlink:comptes-amorcage`
répond « Aucun compte d'amorçage ne porte le mot de passe public du socle », `tarification-prete`
dit les deux sociétés prêtes, `migrate` n'a rien à faire. **Premier déploiement de production
réussi** : S91 à 04:32Z, puis S92 à 04:50Z (run 219). La base servie garde les noms « We Courier »
et « Company » : les semences ne rejouent pas sur une base amorcée (voir « Ce que ce lot ne fait
pas » de S92) ; le renommage se fait dans Réglages. Le run 215 (S90) a été **annulé** par le groupe
de concurrence `deploiement-production` au profit du 217, qui le contenait : rien de perdu.

## S93 — la vitrine d'une société neuve parle français (2026-10-06)

### D'où ça vient

Quand le super-admin crée une société, `CompanyRepository` remplit sa vitrine publique par
`CompanyFrontendDataSeeder::companySiteData()` : quatre services, six atouts, neuf questions,
douze partenaires, dix articles, cinq pages, vingt-cinq sections. Le socle y mettait des titres
anglais (« SUB-DOMAIN BASED », « Happy Merchant », « Subscribe Us »), des paragraphes *lorem
ipsum* tirés de `Faker` (services, réponses, articles, pages légales), des noms de partenaires
inventés et des compteurs de marketing faux (7 520 agences, 50 000 000 colis). Les semences de la
société 1 (`Backend/FrontWeb`) répétaient le même contenu, dont deux en SQL MySQL brut que la
suite ne jouait pas (S79 l'avait noté dans « Ce qui reste »). Un transporteur béninois qui
ouvrait son site le premier jour montrait tout cela à ses clients. En passant, la page d'accueil
s'appelait « Maison » (`levels.home`) : la traduction mot à mot de Home.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `CompanyFrontendDataSeeder` | réécrit : une méthode publique par rubrique (`reseauxSociaux`, `services`, `atouts`, `faq`, `partenaires`, `articles`, `pages`, `sections`), `companySiteData()` les enchaîne ; contenu français ; compteurs à **une agence et zéro colis** (le transporteur les met à jour dans Vitrine → Sections) ; pages légales et « À propos » lisibles ; trois articles vrais et courts ; six partenaires « Partenaire n » sur les logos du socle ; `DB::table()->insert` pour pages et sections |
| `Backend/FrontWeb/*Seeder` (8) | délèguent à la méthode correspondante pour la société 1 : une seule version du texte |
| `lang/fr/levels.php` | `home` : « Accueil » |
| `tests/Feature/StorefrontSpeaksFrenchTest` (4 tests) | une société neuve (ligne `general_settings` copiée) reçoit une vitrine française complète, aux clés que les gabarits lisent, sans rien d'anglais ni de lorem, et rien ne fuit hors des sociétés semées ; la société 1 lit le même texte que la démo ; la page d'accueil en HTTP (hôte locataire) montre la bannière, les atouts, « Accueil », et plus « Subscribe Us » ni « Maison » ; aucune semence de la vitrine n'écrit `DB::statement` ni ne lit `Faker` |
| `SeedsSpeakBeninTest` | suit : la carte de la vitrine n'a plus qu'une source |
| Docs | `web/CLAUDE.md` (décision), grand livre chantier 1, en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| un service rebaptisé « E-Commerce Delivery » | **rouge** (société neuve, et la société 1 avec elle) |
| un `DB::statement` remis dans `PageSeeder` | **rouge** (source) |

Suite complète `web/` : **1 258 tests, 48 819 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne touche pas aux écrans du back-office qui éditent la vitrine, ni aux images du socle
(`public/frontend/images/...`), ni aux sociétés **déjà créées** : leur vitrine est en base, elle se
corrige dans Vitrine → Sections / Pages / Services. Il ne relit pas le reste de `lang/fr` ; la
francisation du socle a eu ses lots, « Maison » est la seule retouche, parce que le filet l'a vue.

## S94 — l'alerte douanière sur la fiche colis web, back-office et panneau marchand (2026-10-06)

### D'où ça vient

S82 a mis `customs_alerts` sur `parcel/details/{id}` et `parcel/logs/{id}` de l'API, donc une
carte « Alertes douanières » sur le détail du colis dans l'app marchand. Les deux fiches **web**
du même colis n'avaient rien : un agent du back-office qui ouvrait un colis export ne voyait ni le
niveau ni le document exigé, il devait aller le chercher dans « Alertes douanières » (S68) ; un
marchand sur le panneau web non plus. Le grand livre portait encore « alerte sur le détail colis »
en reste du chantier 5 alors que ~~M1~~ (l'app) était barré : c'est la moitié web qui manquait.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `resources/views/backend/customs/_parcel_alerts.blade.php` | un bloc par colis (`id="alertes-douanieres-colis"`), absent si le colis n'a pas d'alerte : niveau (badge), pays, catégorie traduite, document exigé et message, statut ; « Traitée » (formulaire `PUT customs/alerts/{id}/resolve`) si `$peutTraiter` ; date de traitement sinon ; bord rouge si une alerte bloque |
| `Backend\ParcelController::details()` | `$alertesDouanieres = $parcel->customsAlerts()->orderByDesc('id')->get()` sur le colis déjà vérifié (S33) ; la vue l'inclut avec `hasPermission('parcel_update')` |
| `MerchantPanel\MerchantParcelController::details()` | même lecture ; la vue l'inclut avec `peutTraiter => false` : le marchand lit, le back-office traite |
| `tests/Feature/CustomsAlertOnParcelScreenTest` (5 tests, HTTP sur l'hôte locataire) | la fiche back-office montre les alertes **de ce colis** (niveau, pays, catégorie, document) et pas celles d'un autre ; un domestique n'a pas de bloc ; le bouton suit `parcel_update` ; traiter depuis la fiche marque l'alerte et la fiche le dit ; le panneau marchand montre sans bouton |
| Docs | `web/CLAUDE.md` (décision), grand livre chantier 5 (reste clos), en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| toutes les alertes de la société au lieu de celles du colis | **rouge** (la fiche du premier export montre le document du second) |
| le bouton « Traitée » affiché sans `parcel_update` | **rouge** (agent lecteur, panneau marchand) |

Suite complète `web/` : **1 263 tests, 48 845 assertions**, verte.

### Ce que ce lot ne fait pas

Il n'ajoute pas de route : le traitement passe par `customs.alerts.resolve` (S68), gardé par
`parcel_update`, et revient sur la fiche (`redirect()->back()`). Il ne touche ni à l'API ni à
l'app. Le panneau marchand ne traite pas d'alerte : c'est la décision de S68 (la résolution est
au transporteur) ; l'API, elle, laisse le marchand marquer la sienne — asymétrie héritée, à
trancher si elle gêne à la recette.

## S95 — le livreur voit l'alerte douanière de sa course (2026-10-06)

### D'où ça vient

Un colis export porte une alerte douanière : un document que le marchand doit fournir, sinon
blocage à la frontière. Le marchand la voit dans son app (S82) et sur le panneau web (S94), le
transporteur sur la fiche back-office (S94) et dans « Alertes douanières » (S68). La personne qui
**ramasse** le colis, le livreur, ne la voyait nulle part : `deliveryman/parcel/details/{id}` ne
servait que le colis et ses événements. Il repartait sans le document, et le colis attendait en
agence.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `Api\V10\DeliveryManParcelController::details()` | `customs_alerts` (`CustomsAlertResource`, libellés traduits) sur le colis déjà vérifié par `notOwned()` (S7) ; `[]` pour un domestique |
| `resources/openapi/overlay.php`, spec régénérée | la réponse documente `customs_alerts`, lecture seule |
| `tests/Feature/DeliverymanCustomsAlertTest` (4 tests) | la course porte **ses** alertes seulement ; un domestique rend `[]` et pas une clé absente ; la course d'un collègue reste un 404 ; le livreur ne peut pas traiter une alerte (403 sur la route marchande) |
| `mobile-livreur/src/domain/customsLevel.ts` (+ test) | niveaux et statuts recopiés du contrat, `customsLevelColorName` comme dans l'app marchand |
| `mobile-livreur/src/api/types.ts`, `deliveryman.ts` | type `CustomsAlert` ; `fetchParcelDetails()` rend `customsAlerts` (`customs_alerts ?? []`) |
| `mobile-livreur/src/components/CustomsNotice.tsx` (+ test rendu, 3) | « Douane — document à collecter » : pays, catégorie, badge de gravité, document, message, statut ; **aucun bouton** ; rien sur un domestique |
| `mobile-livreur/app/(app)/parcel/[id]/index.tsx`, `src/i18n/fr.ts` | la carte juste sous l'en-tête de la course ; clés `customs.*` |
| `tests/Feature/CourierAppCustomsContractTest` (3 tests) | l'app lit `customs_alerts` avec repli, l'écran rend la carte, la carte n'offre pas d'action, les niveaux sont ceux de `web/` |
| Docs | `web/CLAUDE.md`, `mobile-livreur/CLAUDE.md`, grand livre chantier 5, en-têtes datés |

### Vérification

`tsc --noEmit`, `expo lint`, `jest` (22 tests) verts dans `mobile-livreur/`.

| Sabotage | Effet |
|---|---|
| toutes les alertes de la société au lieu de celles de la course | **rouge** (API : la course porte l'alerte d'une autre) |
| l'écran de course perd la carte | **rouge** (contrat de l'app) |

Suite complète `web/` : **1 270 tests, 48 880 assertions**, verte.

### Ce que ce lot ne fait pas

Il n'ouvre aucune route au livreur : il lit. Il ne touche pas à la liste des courses (`parcel/index`)
— une pastille « douane » sur la liste serait un lot à part, à décider à la recette avec un colis
export du jeu pilote. L'asymétrie héritée (le marchand peut marquer sa propre alerte traitée par
l'API, pas par le panneau web) reste à trancher (S94).

## S96 — les entrées d'authentification de l'API sont limitées contre la force brute (2026-10-06)

### D'où ça vient

Le login web est limité par `ThrottlesLogins` (5 essais), `password/email` par `throttle:5,1`.
Les autres entrées d'authentification de l'API (`signin`, `deliveryman/login`, `otp-verification`,
`resend-otp`, `password/reset`) ne connaissaient que la borne du groupe `api` : **60 requêtes par
minute et par adresse**, sans fin — soixante mots de passe à la minute sur un compte marchand, ou
soixante codes OTP à six chiffres. Depuis S87 les comptes d'amorçage n'ont plus de mot de passe
public ; depuis les runs 217 et 219 la production est en ligne. Le trou se voyait depuis `grep`.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `RouteServiceProvider::configureRateLimiting()` | limiteur `connexion` : deux bornes, `Limit::perMinute(5)->by('connexion:' . identifiant . '|' . ip)` et `Limit::perMinute(30)->by('connexion-ip:' . ip)` ; identifiant = `merchant_id` / `driver_id` / `email` / `mobile`, en minuscules, sans espaces ; réponse 429 `{success:false, message, data:[]}` + `Retry-After`, message `auth.throttle` dans la langue négociée (le limiteur appelle `ApiLocale::negocier` : `ThrottleRequests` a la priorité sur `ApiLocale`) |
| `routes/api.php` | `->middleware('throttle:connexion')` sur les six entrées ; `password/email` passe de `throttle:5,1` au même limiteur (même réponse, même langue) |
| `public/openapi/v10.json` (régénéré) | les six routes documentent le 429 (`SpecGenerator` le fait pour tout `throttle:*`) |
| `tests/Feature/ApiAuthThrottleTest` (7 tests) | 6ᵉ essai sur un compte : 429 dans l'enveloppe, `Retry-After` repris dans le message ; identifiant normalisé (casse, espaces) ; un autre compte depuis la même adresse passe ; 31ᵉ compte depuis une adresse : 429 ; la langue négociée (S90) ; les six routes portent le limiteur ; le login livreur aussi |
| Docs | `web/CLAUDE.md` (décision), cartographie § S96, en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| `signin` sans le limiteur | **rouge** (5 tests : compte visé, normalisation, énumération, langue, inventaire) |
| borne par compte portée à 60/min | **rouge** (compte visé, normalisation, langue, livreur) |

Suite complète `web/` : **1 277 tests, 48 950 assertions**, verte.

### Ce que ce lot ne fait pas

`register` n'est pas limité : une PME dont l'inscription échoue cinq fois à la validation ne doit
pas se voir fermer la porte ; le spam d'inscriptions est un autre sujet (captcha, vérification
OTP déjà en place). Les bornes (5 et 30 par minute) sont celles du login web et un ordre de
grandeur raisonnable ; elles se règlent dans le limiteur, pas dans les routes. Le compteur vit
dans le cache de l'application : un cache `file` ou `database` le partage entre processus PHP,
un cache `array` ne compterait que dans la requête (le `.env` de production n'est pas en `array`,
`verifier-env.sh` ne le vérifie pas — à savoir).

## S97 — le garde du `.env` refuse un cache non partagé (2026-10-06)

### D'où ça vient

S96 compte les essais de connexion dans le cache de l'application. Avec `CACHE_DRIVER=array` (ou
`null`) chaque requête PHP repart de zéro : le limiteur ne refuse jamais rien, et le `.env` d'un
serveur peut porter cette valeur sans que rien ne le dise — `ApiAuthThrottleTest` tourne sur le
cache `array` de la suite, qui vit **dans le même processus** et compte donc, ce que PHP-FPM ne fait
pas. S96 l'avait noté dans « ce que ce lot ne fait pas ».

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `docs/guides/infra/deploy/verifier-env.sh` | refuse `CACHE_DRIVER=array` et `null` dans tous les environnements déployés, avant `php artisan down` ; `file`, `database` ou absent (= `file`) passent |
| `RecetteDeploymentTest` (+1) | les deux valeurs refusées en recette et en production, `file`, `database` et l'absence acceptés |
| Docs | `web/CLAUDE.md` (S96 complété), guide de mise en service (tableau des refus), en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| le garde désactivé (`if false`) | **rouge** |

Suite complète `web/` : **1 278 tests, 48 965 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne vérifie pas `SESSION_DRIVER` ni `QUEUE_CONNECTION` : une session `array` déconnecterait tout
le monde à la première page, ce qui se voit ; une file `sync` ne perd rien, elle ralentit. Il ne lit
pas le `.env` du serveur : c'est le déploiement qui le lira, au prochain run.

### Vu en production (run 229, 2026-10-06 07:21 UTC)

Le déploiement de `main` qui portait S97 a **passé** `verifier-env.sh` : le `.env` de production
n'est pas en `CACHE_DRIVER=array`, le limiteur de S96 compte bien entre requêtes. Rien à faire sur
le serveur pour ce point.

## S98 — mot de passe oublié dans l'app livreur (2026-10-06)

### D'où ça vient

L'app marchand a « Mot de passe oublié ? » depuis ses premiers écrans (`password/email` puis
`password/reset`, le courtier `users` de Laravel). L'app livreur n'avait que la connexion : un
livreur qui oubliait son mot de passe attendait que le transporteur le change au back-office. Les
deux routes sont communes aux deux types de compte (S96 les a limitées) : rien à écrire dans
`web/`, l'app suit le contrat existant.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `mobile-livreur/src/api/endpoints.ts`, `auth.ts` | `passwordEmail`, `passwordReset` ; `requestPasswordReset()`, `resetPassword()` repris de `mobile/`, appels **sans jeton** |
| `mobile-livreur/app/(auth)/forgot-password.tsx`, `reset-password.tsx`, `_layout.tsx` | les deux écrans de `mobile/`, mêmes textes (adaptés au livreur : « sans adresse connue, demandez un nouveau mot de passe à votre transporteur »), en-têtes de pile à la charte |
| `mobile-livreur/app/(auth)/login.tsx` | le lien « Mot de passe oublié ? » sous le bouton |
| `mobile-livreur/src/i18n/fr.ts` | clés `auth.*` de la réinitialisation, `errors.passwordMismatch` / `passwordTooShort` |
| `tests/Feature/CourierPasswordResetTest` (3 tests) | un livreur reçoit la notification de réinitialisation ; le jeton change le mot de passe et le nouveau ouvre `deliveryman/login`, l'ancien non ; l'app inventorie les deux routes, le lien est sur la connexion, les deux écrans existent, les appels se font sans jeton |
| `DeliverymanAppContractTest` | inchangé et vert : les deux entrées existent dans la spec, ne sont pas réservées au marchand, ont un appelant, et la répétition de recette les joue déjà |
| Docs | `mobile-livreur/CLAUDE.md` (table des écrans), en-têtes datés |

### Vérification

`tsc --noEmit`, `expo lint`, `jest` (22 tests) verts dans `mobile-livreur/`.

| Sabotage | Effet |
|---|---|
| le lien de la connexion renvoie vers la connexion | **rouge** |
| la demande de lien part avec le jeton (`{}` au lieu de `authenticated: false`) | **rouge** |

Suite complète `web/` : **1 281 tests, 48 985 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne crée pas de lien profond `beninlink-livreur://reset-password` : le lien reçu mène à la page
web du socle, et le jeton se saisit dans l'app (même choix que `mobile/`). Un livreur **sans
adresse e-mail** en base ne peut pas se réinitialiser seul : le texte le dit et le renvoie au
transporteur — l'adresse n'est pas obligatoire à la création d'un livreur, règle du socle.

## S99 — le garde du `.env` refuse le mode debug en production (2026-10-06)

### D'où ça vient

Le `.env.example` du socle livre `APP_DEBUG=true`. Un serveur installé en le recopiant sert, à la
première erreur, la page de débogage de Laravel : pile d'appels, requête, et un bloc
« Environment » avec les variables — clés d'API, FedaPay, mot de passe de base. `verifier-env.sh`
(S76, S77, S88, S97) ne le regardait pas ; `DevDependencyLeakTest` (S79) tient la barre de
débogage hors d'une installation `--no-dev`, pas la page d'erreur.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `docs/guides/infra/deploy/verifier-env.sh` | `APP_ENV=production` + `APP_DEBUG=true` : refus avant `php artisan down`, message qui dit quoi faire ; hors production : avertissement seulement (une recette se débogue) ; absent vaut `false` |
| `RecetteDeploymentTest` (+1) | refus en production, `false` et absence acceptés, avertissement sans refus en recette |
| Docs | `web/CLAUDE.md`, guide de mise en service (tableau des refus), tableau des constats de sécurité (~~S99~~), en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| le garde désactivé (`if false`) | **rouge** |

Suite complète `web/` : **1 282 tests, 48 993 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne change pas `.env.example` (qui décrit un poste de développement) et ne lit pas le `.env`
du serveur : le prochain déploiement le lira. Les runs 217 et 219 ont déployé sans que ce garde
existe ; si la production est en `APP_DEBUG=true`, le prochain run le dira et s'arrêtera avant de
couper le site.

### Vu en production (run 233, 2026-10-06 07:53 UTC)

Le déploiement de `main` qui portait S99 a **passé** `verifier-env.sh` : la production n'est pas en
`APP_DEBUG=true`. Les deux gardes du `.env` posés ce jour (S97, S99) ont donc constaté un serveur
déjà conforme. Rien à faire sur le serveur pour ce point.

## S100 — la CI des pull requests n'attend plus le déploiement de `main` (2026-10-06)

### D'où ça vient

Le groupe de concurrence `deploiement-production` (`cancel-in-progress: false`) était posé **sur le
workflow entier**. GitHub Actions ne fait tourner qu'un run du groupe à la fois : la suite de tests
d'une pull request attendait donc que le déploiement de `main` en cours — déclenché par la fusion
de la pull request précédente — soit terminé. Constaté sur les PR #161 à #166 : huit à quinze
minutes d'attente par lot, pendant lesquelles rien ne tournait. La règle, elle, ne concerne que les
deux jobs qui touchent un serveur.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `.github/workflows/deploy.yml` | plus de `concurrency` au niveau du workflow ; `deploy` porte `deploiement-production` et `deploy-recette` porte `deploiement-recette`, tous deux `cancel-in-progress: false` ; le commentaire explique les deux niveaux |
| `DeploymentRehearsalTest` (+1) | pas de groupe sur le workflow ; les deux jobs de déploiement ont le leur, distincts, sans annulation ; `tests`, `apps`, `repetition` n'en ont pas |
| Docs | cartographie § S100, « Vu en production (run 229) » sous S97, en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| le groupe remis au niveau du workflow | **rouge** |

Suite complète `web/` : **1 283 tests, 49 002 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne change pas la règle : deux déploiements sur le même serveur restent sérialisés, et un
déploiement en cours n'est jamais interrompu. GitHub garde au plus **un** job en attente par groupe :
deux fusions rapprochées font toujours annuler le déploiement en attente au profit du plus récent,
qui contient le premier (vu au run 215). Les jobs `tests` et `repetition` d'un push sur `main`
tournent désormais en parallèle de ceux d'une pull request : le job `repetition` monte sa propre
base MySQL dans son conteneur, rien n'est partagé.

## S101 — les listes de colis signalent le document douanier à collecter (2026-10-06)

### D'où ça vient

S82, S94 et S95 ont mis l'alerte douanière sur le **détail** du colis, partout. Sur les listes
(courses du livreur, colis du marchand), rien ne distinguait un export qui attend un document d'un
colis domestique : il fallait ouvrir chaque course. S95 l'avait nommé « lot à part » ; le ramassage
le réclame — un livreur qui prépare sa tournée regarde la liste, pas vingt fiches.

### Le choix : un compteur sur `ParcelResource`, pas un filtre

Un filtre `customs/alerts?parcel_id` aurait été un paramètre d'identifiant de plus à prouver
(IsolationCoverageTest) et une requête par ligne côté app. Le compteur `customs_pending` voyage
**avec le colis**, dans la ressource que les deux listes lisent déjà ; il est compté par la requête
de liste (`withCount`), une seule requête pour la liste entière ; une lecture isolée (sans
`withCount`) retombe sur un comptage par colis, jamais sur une clé absente.

### Ce qui est écrit

| Pièce | Rôle |
|---|---|
| `Http\Resources\v10\ParcelResource` | `customs_pending` : `customs_pending_count` si la requête l'a compté, sinon comptage des alertes `PENDING` du colis |
| `ParcelRepository::deliverymanStatusParcel()` (deux branches), `MerchantParcelRepository::parcelAll()` | `withCount(['customsAlerts as customs_pending_count' => PENDING])` |
| `resources/openapi/overlay.php`, spec régénérée | `Parcel.customs_pending` |
| `DeliverymanCustomsAlertTest` (+2) | le tableau de bord du livreur : 1 sur l'export (l'alerte traitée ne compte plus), 0 sur le domestique ; `parcel/index` du marchand porte la même clé |
| `mobile/`, `mobile-livreur/` | `ParcelSummary.customs_pending?` ; pastille « Douane » (bord et texte `warning`) sous l'en-tête de la carte quand `> 0` ; clé `customs.badge` |
| `MerchantAppCustomsContractTest` (+1), `CourierAppCustomsContractTest` (+1) | la liste lit `customs_pending` avec repli `?? 0`, la clé est optionnelle dans les types, la traduction existe |
| Docs | `web/CLAUDE.md`, `mobile/CLAUDE.md`, `mobile-livreur/CLAUDE.md`, grand livre chantier 5, en-têtes datés |

### Vérification

`tsc`, `expo lint`, `jest` verts dans les deux apps (39 et 22 tests).

| Sabotage | Effet |
|---|---|
| `ParcelResource` sans `customs_pending` | **rouge** (API : tableau de bord et liste marchand) |
| la liste des courses perd la pastille | **rouge** (contrat de l'app livreur) |

Suite complète `web/` : **1 287 tests, 49 021 assertions**, verte.

### Ce que ce lot ne fait pas

La pastille ne dit pas le niveau (info, avertissement, bloquant) : elle dit qu'il y a quelque chose
à lire sur la fiche, qui le dit. Les listes du back-office web ne la portent pas (le transporteur a
l'écran « Alertes douanières », S68). La pastille n'est pas rendue en test (les listes sont des
écrans, pas des composants extraits — même limite que S84 a notée).

## S102 — le registre dit la production vraie (2026-10-06)

### D'où ça vient

`docs/CARTOGRAPHIE_PROJET.md` (§ 7.4) disait encore, au 2026-10-03, que les secrets SSH étaient
vides et qu'« aucune version n'a jamais atteint un serveur par le workflow ». Depuis, le run 217
(S91) a été le premier déploiement de production réussi et neuf lots l'ont suivi jusqu'au run 233
(S99) ; les deux gardes du `.env` ont passé ; le job de recette, lui, saute à chaque run (secrets
`RECETTE_SSH_*` absents). Un registre qui dit le contraire de *Actions* fait perdre du temps à qui
le lit pour décider quoi faire sur le serveur.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `docs/CARTOGRAPHIE_PROJET.md` | en-tête au 2026-10-06 ; E3 réécrit : production **en service** depuis le run 217, gardes passés aux runs 229 et 233, ce qui reste à poser sur le serveur ; E2 : le job de recette **saute** faute de secrets ; § 8 relu |
| `docs/guides/infra/mise-en-service/README.md` | la note « Fait le 2026-10-06 » va jusqu'au run 233 et une liste **« Ce qui reste à faire sur le serveur »** réunit en un endroit les points encore dus, chacun renvoyant au guide qui le détaille |
| ici | « Vu en production (run 233) » sous S99 ; en-têtes datés |

Aucun code, aucun test : la suite reste à **1 287 tests, 49 021 assertions**.

### Ce que ce lot ne fait pas

Il ne pose rien sur le serveur et ne vérifie rien à distance : il relit les runs d'*Actions* (212 à
233) et les trois gardes de `deploy.sh`. Le renommage « We Courier » → BeninLink dans Réglages,
Supervisor, la supervision, la sauvegarde et le serveur de recette restent des gestes d'opérateur,
listés dans le guide.

## S103 — les cartes de colis des deux apps rendues en test (2026-10-06)

### D'où ça vient

S84 puis S101 l'avaient noté : les listes de colis des deux apps sont des **écrans**, et un écran
(hooks de session, appels d'API, navigation) ne se rend pas en test unitaire. La pastille « Douane »
de S101, le montant en FCFA entiers, le statut traduit par le backend et les boutons Appeler /
Itinéraire du livreur n'étaient donc tenus que par une **lecture de source** dans les tests de
contrat (`assertStringContainsString`). Une lecture de source dit qu'une expression existe, pas
qu'elle rend ce qu'on croit.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `mobile/src/components/ParcelCard.tsx` (+ test, 4) | la carte d'un colis du marchand : suivi, statut du backend, pastille « Douane » si `customs_pending > 0`, client et adresse, montant par `formatAmount`, type de livraison ; `testID` `parcel-card-<id>` ; le toucher ouvre la fiche |
| `mobile-livreur/src/components/ParcelCard.tsx` (+ test, 4) | la carte d'une course : même tronc, plus le libellé « à encaisser » et les boutons Appeler / Itinéraire (`accessibilityRole="button"`, inactifs sans numéro ou adresse) |
| `mobile/app/(app)/parcels.tsx`, `mobile-livreur/app/(app)/(tabs)/index.tsx` | ne font plus que poser `<ParcelCard>` ; les styles de la carte ont suivi |
| `MerchantAppCustomsContractTest`, `CourierAppCustomsContractTest` | le test S101 lit le **composant** (pastille, `formatAmount`), exige que l'écran passe par `<ParcelCard` sans porter lui-même `customs_pending`, et que le test de rendu existe |
| Docs | `web/CLAUDE.md`, `mobile/CLAUDE.md`, `mobile-livreur/CLAUDE.md`, grand livre (M5), en-têtes datés |

Les fixtures des tests sont des `Partial<…>` affirmés en type : la carte ne lit qu'une dizaine
de champs, les quarante autres du `ParcelSummary` n'ont pas à être inventés.

### Vérification

`tsc`, `expo lint`, `jest` verts dans les deux apps (**47** tests marchand, **30** tests livreur).

| Sabotage | Effet |
|---|---|
| la carte du livreur ne pose plus la pastille (`false &&`) | **rouge** (jest livreur, 1 test) |
| la carte du marchand écrit le montant brut (`String(...)` au lieu de `formatAmount`) | **rouge** (jest marchand, 1 test) |

Deux pièges rencontrés en écrivant les tests, utiles au prochain composant : (1) après un
`unmount()` manuel, `screen` ne suit plus le rendu suivant du même test — utiliser `rerender` ;
(2) `getAllByRole('button')` destructuré donne `TestInstance | undefined` sous le typage strict —
viser le bouton par son nom (`getByRole('button', { name: 'Appeler' })`).

Suite complète `web/` : **1 287 tests, 49 031 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne change ni le contrat ni un pixel : les deux écrans rendent la même carte qu'avant, par un
composant. Les autres listes des apps (relevés, wallet, alertes, tickets) restent des écrans ; elles
n'ont pas de propriété métier que les tests de contrat tiendraient par lecture de source.

## S104 — un compte livreur n'entre pas au back-office web (2026-10-06)

### D'où ça vient

La passe de S40 l'avait noté et laissé : « `GET /dashboard` répond **500 à un livreur** ». Mesuré
à nouveau, sur le site d'une société : un livreur qui tape son e-mail et son mot de passe sur
`/login` est **accepté** (302 vers `/dashboard`), puis le tableau de bord lui répond **500** —
`backend.dashboard` fait `in_array()` sur ses droits, qui n'existent pas. Un refus annoncé comme
une panne (famille S37). Or le livreur n'a **aucun écran web** (S41 l'a mesuré, S85 le tient en
contrat) : son outil est l'app livreur. Dans la même passe de S40 dormaient huit « Theme Pages »
du socle (`dashboard-finance`, `dashboard-influencer`, `dashboard-sales`, `ecommerce-product`,
`ecommerce-product-checkout`, `ecommerce-product-single`, `influencer-finder`,
`influencer-profile`) qui rendaient des vues `theme.*` **inexistantes** : 500 pour tout compte,
aucune vue ni script ne les nommait — la famille des cinq routes `sms-settings` de S35.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `Auth\LoginController::login()` | un compte `DELIVERYMAN` est refusé **avant la session**, par une erreur de validation sur le champ e-mail : `auth.courier_app_only` (« Ce compte est un compte livreur : il se connecte dans l'application livreur, pas sur le site. », en FR et EN) |
| `DashbordController::index()` | une session livreur qui existerait encore reçoit **403** avec le même message, au lieu d'atteindre la branche back-office et de planter dans la vue |
| `routes/web.php` | les huit routes « Theme Pages » **retirées** ; le commentaire dit pourquoi |
| `tests/Feature/CourierWebAccessTest.php` (5 tests) | la connexion web refuse un livreur et dit où aller, elle sert toujours un agent ; `/dashboard` répond 403 au livreur et 200 à l'agent ; les huit noms de route ont disparu, `resources/views/theme/` n'existe pas et aucune vue ne les nomme |
| Docs | `web/CLAUDE.md`, en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| le refus de connexion retiré (`if (false)`) | **rouge** (le livreur entre) |
| une route de thème remise | **rouge** (le filet des huit noms) |

Suite complète `web/` : **1 292 tests, 49 059 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne touche pas à l'API : `deliveryman/login` reste la porte du livreur (S96 la limite). Il ne
change pas la connexion du marchand ni de l'agent (le test le tient). Les autres lignes de la
passe de S40 (`search-charts`, `store-token`, `subscription`, les routes `aamarpay.payment` et
`bkash.redirect` nommées par des vues marchandes) restent telles quelles : passerelles hors
Bénin, décision D-FedaPay.

## S105 — la page des plans ne dépend plus d'un réglage Stripe absent (2026-10-06)

### D'où ça vient

Dernière ligne de la passe de S40 restée ouverte : « `GET /subscription` répond 500 pour les
trois types ». Mesuré : la vue `backend.subscription.subscription` faisait **sa propre requête**
(`Setting::where('company_id', 1)->where('key', 'stripe_status')->first()`) puis lisait
`->value` sur le résultat — `null` dès que la plateforme n'a pas cette ligne (une base amorcée
autrement que par `SettingSeeder`, ou une ligne effacée). Or `/subscription` est la page où le
middleware `subscriptionCheck` **renvoie un locataire dont le plan a expiré** : lui répondre 500,
c'est l'empêcher de voir les plans et de payer par Mobile Money. Le départ vers Stripe
(`subscription/payment`) lisait la clé secrète de la même façon : 500 sans clé.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `Superadmin\PlanController::subscription()` | passe `$stripeEnabled` à la vue, calculé par `stripePlatformReady()` : interrupteur `stripe_status` de la société 1 **actif et** `stripe_secret_key` renseignée |
| `subscriptionPayment()` | sans ce drapeau, **refuse** vers la page des plans avec `levels.stripe_not_configured` (FR, EN) au lieu d'appeler Stripe avec une clé nulle |
| `subscription.blade.php` | plus de requête dans la vue ; le bouton carte suit `$stripeEnabled` ; le bouton Mobile Money (FedaPay) ne change pas |
| `tests/Feature/SubscriptionPageStripeTest.php` (4 tests) | sans aucune ligne Stripe la page rend 200 et sans bouton carte ; l'interrupteur seul ne suffit pas ; interrupteur + clé font apparaître le bouton ; le départ sans clé redirige au lieu de planter |
| `PlanSwitchNoticeTest` | n'a plus à poser la ligne `stripe_status` pour que la page se rende |
| Docs | `web/CLAUDE.md`, en-têtes datés |

### Vérification

| Sabotage | Effet |
|---|---|
| `stripePlatformReady()` n'exige plus la clé | **rouge** (1 test : le bouton carte apparaît sans clé) |
| la vue affiche toujours le bouton carte (`@if (true)`) | **rouge** (2 tests) |

Suite complète `web/` : **1 296 tests, 49 068 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne retire pas Stripe de l'abonnement SaaS : la décision D10 coupe le module *payout*, pas la
passerelle de la plateforme, et `Helper.php` le dit (« `stripe_status` sert aussi l'abonnement
SaaS »). Il ne touche pas au retour de Stripe (`subscription/success`, vérifié depuis S1) ni au
chemin FedaPay (Chantier 3). La passe de S40 est close : `search-charts` répond des dates à tout
compte authentifié (aucune donnée), `store-token` répond 410 depuis D12.

## S106 — R1, R2 et R8 actés par des défauts réversibles (2026-10-06)

### D'où ça vient

Trois décisions métier attendaient dans le registre (§ 7.1) : le reliquat CEDEAO de la grille
(R1), les numéros de comptes SYSCOHADA (R2) et la signature sur un retour (R8). Le porteur a
rendu la main sur les trois. La règle du projet pour une décision de ce type est celle de S72 :
**un défaut qui marche, documenté, réversible à l'écran ou en une ligne de configuration** — pas
une case vide qui bloque la recette.

### Ce qui est écrit

| Décision | Où | Quoi |
|---|---|---|
| R1 | `ZoneCatalog::PAYS` | cinq pays de plus, point de départ gradué à la distance depuis Cotonou (GH 15 000, NE 18 000, CI 20 000, ML 22 000, SN 25 000 F), **créés s'ils manquent, jamais réécrits** ; le COD CEDEAO (3 %) datait du 2026-09-06 ; la TVA à l'export reste au taux du marchand (D1/R7) |
| R1 | `DeliveryZoneApiTest`, `DeliveryZonePricingBaselineTest`, `PricingReadinessAuditTest`, `DeliveryZoneGridTest`, `DeliveryZoneScreensTest`, `ParcelZoneRouteTest` | les huit forfaits servis et étalonnés ; cinq tests figeaient « trois pays » ou prenaient le Ghana et la Côte d'Ivoire comme exemples **non tarifés** — la Guinée et le Libéria les remplacent, le compte des forfaits se lit dans `ZoneCatalog::PAYS` |
| R2 | docs seulement | les numéros en place (4712, 4191, 4713 ; VE/OD/BQ/CA) sont le **plan de travail** ; la régularisation du passé est **sans objet** : la production a démarré le 2026-10-06, après S73 |
| R8 | `ParcelRepository::returntoQourier()` | un retour peut porter `signatureImage`, même champ et même dossier que la livraison, stocké sur l'événement `RETURN_TO_COURIER` et lu par le marchand (`parcel/logs`) |
| R8 | overlay `deliveryman/parcel-status-update` | corps en multipart, `signatureImage` facultatif, retour seulement ; spec régénérée |
| R8 | `mobile-livreur` | l'écran d'issue propose le tracé pour « Retour » (texte `status.signatureReturnHint`) ; `reportOutcome()` envoie du multipart quand la signature est jointe, le JSON du socle sinon |
| R8 | `DeliveryProofTest` (+2), `DeliverymanAppContractTest` (+1) | retour avec signature stockée, retour sans signature accepté ; l'app envoie sous le même champ, l'écran le propose, la spec le dit |
| Docs | registre § 7.1 (R1, R2 clos, R8 livré), `DECISIONS_METIER.md` (D2, D4 complément, R8), `plan-de-comptes.md` § 6, `refonte-bareme.md` § 1.4, CLAUDE.md, en-têtes |

### Vérification

`tsc`, `expo lint`, `jest` verts dans l'app livreur (30 tests).

| Sabotage | Effet |
|---|---|
| `returntoQourier()` ignore `signatureImage` (`if (false)`) | **rouge** (1 test : la signature du retour n'est pas stockée) |
| le Ghana retiré de `ZoneCatalog::PAYS` | **rouge** (2 tests : la liste servie, l'étalon des forfaits) |

Suite complète `web/` : **1 299 tests, 49 096 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne fige rien : un forfait se change à l'écran, un numéro de compte en une ligne, et la question
L6 de la recette garde son sens — si les PME jugent la signature superflue sur un retour, on la
retire de l'écran, le serveur continue de l'accepter. Il n'instruit pas l'exonération de TVA du
transport international (question fiscale, expert-comptable). La photo (`image`) reste réservée à
la livraison : un retour n'a pas de « colis remis » à photographier.

## S107 — dette technique T2, T3, T9 tranchée (D15) (2026-10-06)

### D'où ça vient

Trois lignes de dette technique du registre (§ 7.2) attendaient une décision : les colonnes
monétaires en `decimal(…,2)` (T2), la migration Bootstrap 4 → 5 (T3) et la re-fusion du socle
We Courier (T9). Le porteur a rendu la main. La règle d'or — appropriation, pas réécriture — les
tranche dans le même sens : **on ne refait pas ce qui marche, on le tient.** Le détail et les
raisons sont dans `docs/DECISIONS_METIER.md`, D15.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `app/Console/Commands/NonIntegerAmountsCommand.php` | `beninlink:montants-non-entiers {--societe=}` : table par table (`parcels`, `invoices`, `wallets`, `merchants`), les montants dont `ROUND(x) <> x`, avec les identifiants ; constat seul, sortie 0 ; société lue dans la liste des sociétés (F4) |
| `tests/Feature/NonIntegerAmountsTest.php` (3 tests) | une base amorcée ne porte aucune décimale ; une décimale glissée en base est constatée **sans être corrigée** ; les colonnes surveillées sont celles des relevés |
| `docs/guides/infra/reprise/README.md` § 8 | le constat rejoint les trois autres |
| Docs | registre § 7.2 (T2, T3, T9 barrés), `DECISIONS_METIER.md` (ligne et section D15), `bootstrap/migration-4-vers-5.md` § 5, `socle/mise-a-jour-we-courier.md`, CLAUDE.md, en-têtes |

Un piège de test, utile au prochain constat : `expectsOutputToContain()` consomme **une ligne
de sortie par attente, dans l'ordre** — deux attentes sur la même ligne font échouer la
seconde. Une attente par ligne.

### Vérification

| Sabotage | Effet |
|---|---|
| le constat compte `ROUND(x) = x` au lieu de `<>` | **rouge** (2 tests : la base amorcée devient « non entière », la décimale glissée disparaît) |

Suite complète `web/` : **1 302 tests, 49 106 assertions**, verte.

### Ce que ce lot ne fait pas

Il ne touche ni au schéma, ni aux vues Bootstrap, ni au socle : c'est le sens des trois
décisions. T4 (PHP 8.4) reste une attente, pas une décision.

## S108 — le serveur : société renommée, Supervisor et crontab posés (2026-10-07)

> ⚠️ **Corrigé par S109** : ce compte rendu était faux sur trois points. La société n'est **pas**
> renommée ; la sauvegarde et son exercice étaient **faits** dès le 06/10 ; la recette est
> **fonctionnelle** depuis le 05/10. Voir § S109.

### D'où ça vient

Compte rendu du porteur : sur le VPS, la société servie est renommée (Réglages → Généraux), le
worker de la file tourne sous Supervisor, le crontab est posé. Ce sont les points 1 à 3 de la
liste « Ce qui reste à faire sur le serveur » (S102). Conséquence métier : depuis ce moment, les
SMS, courriels et notifications poussées **partent réellement** (D13 : sans worker, l'application
répondait mais n'envoyait rien), et le planificateur Laravel tourne.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `docs/guides/infra/mise-en-service/README.md` | les trois points barrés, datés, avec ce qui se vérifie à froid (`supervisorctl status`, un SMS qui part, `queue:failed` vide, `crontab -l`) ; restent la sauvegarde avec son exercice de restauration et le serveur de recette |
| `docs/CARTOGRAPHIE_PROJET.md` E3 | l'état du serveur au 2026-10-07 |
| en-têtes datés | S108 |

Côté *Actions*, chaque fusion de la journée a déployé : runs 235 à 249 (S100 à S107) tous verts ;
la production sert donc S107.

Aucun code, aucun test : la suite reste à **1 302 tests, 49 106 assertions**.

### Ce que ce lot ne fait pas

Il ne vérifie rien à distance : il consigne le compte rendu et dit ce qui le prouverait. La
sauvegarde — et surtout l'**exercice de restauration**, la seule étape qui prouve la sauvegarde —
reste le dernier point avant la recette ; le serveur de recette (E2) attend ses secrets.

## S109 — l'état du serveur corrigé : quatre points sur cinq faits, le renommage attend (2026-10-07)

### D'où ça vient

Le porteur a vérifié le serveur après la fusion de S108 et relevé trois erreurs dans son compte
rendu :

| Point | S108 disait | État vérifié le 2026-10-07 |
|---|---|---|
| Renommer la société | fait | **pas fait** : la base porte toujours « We Courier » (id 1) et « Company » (id 2) ; en attente des coordonnées définitives du porteur |
| Sauvegarde et exercice de restauration | reste à faire | **fait le 06/10** : sauvegardes du 06/10 et du 07/10 dans `/var/backups/beninlink/`, cron quotidien à 2 h 15 ; restauration réussie sur une base jetable, données cohérentes, base d'essai supprimée |
| Serveur de recette | reste à faire | **fonctionnel depuis le 05/10** : `https://recette.beninlink.app/` répond 200, worker Supervisor `RUNNING` |
| Supervisor, crontab | faits | faits (inchangé) |

S102 portait déjà une erreur du même ordre : « rien n'a encore été déployé en recette ». Elle
déduisait l'état du serveur de recette du seul job *Actions*, qui saute son étape SSH ; or la
recette a été montée à la main. **Leçon** : un lot de documentation ne déduit pas l'état d'un
serveur de ce qu'il peut lire dans le dépôt ou dans *Actions* ; il consigne une vérification faite
sur la machine, et le dit.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `docs/guides/infra/mise-en-service/README.md` | le tableau « Ce qui reste à faire » réécrit sur l'état vérifié : 2 à 5 faits et datés, 1 en attente ; une note sur la recette, que les fusions ne mettent pas à jour |
| `docs/CARTOGRAPHIE_PROJET.md` E2, E3 | l'état vérifié ; les deux erreurs (S102, S108) nommées et corrigées |
| § S108 ci-dessus | un renvoi en tête vers cette correction |
| en-têtes datés | S109 |

### Un point que le dépôt montre, et que le compte rendu ne contredit pas

Le run 251 (fusion de S108) a déployé en production, mais l'étape « Déploiement par SSH (recette) »
a **sauté** : au moins un des secrets `RECETTE_SSH_HOST`, `RECETTE_SSH_USER`, `RECETTE_SSH_KEY` manque au dépôt (`RECETTE_DEPLOY_PATH` est facultatif — S109 citait à tort un `DEPLOY_PATH` obligatoire, corrigé en S110). La
recette répond, mais elle ne suit pas `main` d'elle-même : elle sert la version déployée à la main.
Pour que la recette pilote teste ce qui part en production, il faut poser ces secrets
(`recette-pilote/` § 1).

Aucun code, aucun test : la suite reste à **1 302 tests, 49 106 assertions**.

## S110 — la société renommée, les secrets de recette nommés (2026-10-07)

### D'où ça vient

Deux comptes rendus du porteur, après la fusion de S109 :

1. **Le renommage est fait**, le 2026-10-07 à 17 h 50 — après la rédaction de S109, qui le disait
   en attente. La société 1 s'appelle désormais « beninlink », avec le courriel et le téléphone du
   porteur ; la société 2 (« Company », celle qui porte l'abonnement et les utilisateurs de
   démonstration depuis S86) reste inchangée.
2. **Les secrets de recette** : le porteur ne peut pas lister les secrets du dépôt, mais l'étape
   sautée prouve qu'il en manque au moins un. Relu dans `deploy.yml` (job `deploy-recette`) :
   l'étape SSH exige `RECETTE_SSH_HOST`, `RECETTE_SSH_USER` et `RECETTE_SSH_KEY` ;
   `RECETTE_DEPLOY_PATH` (`/var/www/beninlink-recette` par défaut) et `RECETTE_SSH_PORT` (22) sont
   facultatifs. S109 avait écrit « `RECETTE_SSH_*` et `DEPLOY_PATH` » : le second nom était faux.

Les coordonnées du porteur ne sont **pas** recopiées dans le dépôt : le nom de la société suffit à
dire que le point est fait, et un courriel ou un téléphone n'a rien à faire dans l'historique git.

### Ce qui est écrit

| Où | Quoi |
|---|---|
| `docs/guides/infra/mise-en-service/README.md` | point 1 barré et daté ; les cinq points faits ; point 5 : les trois secrets exacts, les deux facultatifs, et où les poser |
| `docs/CARTOGRAPHIE_PROJET.md` E2, E3 | le renommage ; « plus rien à faire sur le serveur » ; les noms exacts des secrets |
| § S109 | le nom de secret faux corrigé |
| en-têtes datés | S110 |

### Vérification

Le run 253 (fusion de S109) montre encore l'étape « Déploiement par SSH (recette) » **sautée** : le
constat de S109 tient. Aucun code, aucun test : la suite reste à **1 302 tests, 49 106 assertions**.

### Ce que ce lot ne fait pas

Il ne pose pas les secrets : ils se règlent dans GitHub (Settings → Secrets and variables →
Actions), et une clé privée SSH ne passe jamais par le dépôt ni par une session de code. Une fois
posés, la **prochaine** fusion déploie aussi la recette ; son journal dira « Déploiement par SSH
(recette) » vert au lieu de « sauté ».

Le nom « beninlink » est écrit en minuscules dans la base, alors que la marque s'écrit « BeninLink »
partout ailleurs (semences, vitrine, documents). C'est le nom qu'affichent les relevés et la
vitrine : un seul champ à corriger dans Réglages → Généraux si la casse n'est pas voulue.

## S111 — aucune marque tierce sur les pages publiques (2026-10-08)

### D'où ça vient

Le porteur a relu la page d'accueil de beninlink.app avant le démarchage des PME pilotes : la
section « Nos partenaires » montrait les vignettes semées par le socle, dont `huawei.png` et
`ups.png`. Vues une à une, **les six** sont des logos de vraies marques : 500px (`1.png` et
`2.png`, le même fichier), Atom, Digg, Huawei et UPS. Aucune relation avec ces entreprises :
c'était une fausse affiliation, sur la première page qu'un prospect ouvre.

La revue des autres pages publiques demandée avec le correctif a trouvé le même défaut ailleurs :

| Visuel | Où il s'affiche | Marque |
|---|---|---|
| `images/default/logo.png`, `light-logo.png`, `favicon.png` | en-tête, pied de page et onglet de toute page publique, et relevés PDF, tant qu'une société n'a pas envoyé ses propres logos | « WeCourier SAAS » |
| `images/default/we-courier-process.png` | connexion, mot de passe oublié et réinitialisation, inscription marchand, inscription et vérification d'une société | camions « We Courier DELIVERY » |
| `frontend/logo.png`, `light-logo.png`, `favicon.png` | nulle part (aucune référence), mais servis par URL | « We Courier » |

Le reste des images publiques (bannières, services, atouts, compteurs, « colis introuvable ») est
fait d'illustrations sans marque. `purchase_verify.blade.php` (logo de l'éditeur WemaxDevs) ne
s'affiche qu'avant l'installation : laissé.

### Ce qui est fait

| Où | Quoi |
|---|---|
| `public/frontend/images/partner/` | les six fichiers supprimés, le dossier avec eux |
| `CompanyFrontendDataSeeder`, `PartnerSeeder` | plus aucun partenaire semé, ni pour la plateforme ni pour une société créée par le super-admin |
| `frontend/home.blade.php` | la section n'est incluse que si `$partners` n'est pas vide : elle revient d'elle-même quand le transporteur saisit de vrais partenaires (menu « Web avant » → « Partner ») |
| migration `2026_10_08_100000_remove_socle_brand_partners` | retire, toutes sociétés, les partenaires dont l'image est un fichier de `frontend/images/partner/` (chemin brut ou JSON des anciennes semences MySQL, `\/` compris) et leurs lignes `uploads` ; un partenaire saisi au back-office (`uploads/partner/…`) reste. `down()` ne remet rien |
| `images/default/logo.png`, `light-logo.png`, `favicon.png` | remplacés par le mot-symbole BeninLink (Sora, « Benin » vert ou blanc, « Link » ocre ; favicon « BL » sur vert), mêmes dimensions ; source `web/resources/brand/generate.py` (ImageMagick, Sora de `mobile/node_modules`) |
| six vues d'authentification et d'inscription | l'illustration « We Courier » remplacée par `frontend/images/banner1.png` (livreur sur une carte, sans marque, inutilisé jusque-là) ; le fichier est supprimé |
| `public/frontend/logo.png`, `light-logo.png`, `favicon.png` | supprimés |

### Le filet

`tests/Feature/PublicBrandAssetsTest` (6 tests) :

- aucune image de `public/` (hors `uploads/` et `vendor/`) n'a l'**empreinte SHA-1** d'un des
  douze fichiers retirés : un logo remis sous un autre nom est vu ;
- aucune vue ne nomme un chemin retiré ;
- les logos par défaut gardent les dimensions que les gabarits attendent, et ont leur source ;
- aucune semence ne pose de partenaire ;
- la page d'accueil, servie par HTTP, n'a pas de section « Nos partenaires » sans partenaire, et
  l'affiche avec un vrai partenaire saisi ;
- la migration retire les trois formes de ligne du socle et garde le vrai partenaire.

`StorefrontSpeaksFrenchTest` (S93) ne compte plus les partenaires parmi les tables semées et
vérifie qu'une société neuve n'en reçoit aucun.

Sabotages, chacun rouge : une copie de `huawei.png` remise sous `services/x.png` ; la garde de
`home.blade.php` remplacée par `@if (true)` ; la migration sans normaliser `\/` ; la semence
d'avant S111 restaurée.

Suite complète : **1 308 tests, 51 090 assertions** (1 302 avant ; +6).

### En production

La migration tourne au déploiement (`migrate` de `deploy.sh`) : la section disparaît de
beninlink.app dès la fusion déployée. Les logos par défaut ne changent ce qu'affiche une société
que si elle **n'a pas** envoyé les siens dans Réglages → Généraux ; un logo envoyé reste prioritaire.

La recette ne suivra pas tant que le garde `comptes-amorcage` la refuse (run 255 :
les cinq comptes d'amorçage de sa base portent encore `12345678`, voir le registre E2).

## S112 — le catalogue français ne garde plus d'anglais (2026-10-08)

### D'où ça vient

En relevant les marques de S111, le menu du back-office s'est lu « Web avant » → « Partner ». Un
relevé de `lang/fr/` contre `lang/en/` a compté **208 entrées identiques**. Une partie l'est à bon
droit (marques : bKash, MTN MoMo, Visa ; sigles : IFU, RCCM, MRR ; mots qui s'écrivent de même :
Action, Description, Signature). Le reste était de l'anglais :

| Fichier | Entrées | Où on les lit |
|---|---|---|
| `reports.php` | 95 | tous les écrans de rapports (statut des colis, bénéfice, marchands, agences, livreurs, comptes) |
| `permissions.php` | 77 (dont « Branche » remplacé) | l'écran des rôles et des droits |
| `status.php` | 2 | « Active » / « Inactive » sur toutes les listes |
| `levels.php`, `menus.php`, `userType.php`, `AccountType.php`, `parcel.php`, `asset.php`, `validation.php`, `ActivityLogs.php`, `hub_payment.php`, `liquid.php` | 22 | menu (« Web avant », « Partner », « Branches »), « Admin », « Hub », « Logs », « Dashboard » |

### Ce qui est fait

Traduction en place, valeur par valeur ; aucune clé ajoutée ni retirée. Deux choix de vocabulaire :
**« Hub » → « Agence »**, le mot des semences (S92, six agences béninoises) — les menus disaient
« branche », anglicisme, aussi corrigé ; **« Front web » → « Site public »**.

### Le filet

`tests/Feature/FrenchCatalogueTest` (3 tests) : chaque fichier de `lang/en/` a son jumeau français ;
aucune entrée française (tableaux imbriqués compris, par `Arr::dot`) n'est identique à l'anglaise
hors de la liste `PERMISES` ; les libellés vus à l'écran (`levels.front_web`, `levels.partner`,
`status.*`, `reports.title`, `permissions.dashboard`, `userType` agence) se lisent en français.
Sabotage : « Actif » remis à « Active » dans `status.php` — rouge, l'entrée nommée.

Suite complète : **1 311 tests, 51 187 assertions** (1 308 avant ; +3).

### Registre

Le run 257 (fusion de S111) a déployé la production — la migration
`remove_socle_brand_partners` y figure — **et la recette**, qui n'était plus refusée : les comptes
d'amorçage de sa base ont été changés depuis le run 255. Registre E2 mis à jour.

## S113 — aucune clé de traduction affichée brute (2026-10-08)

### D'où ça vient

S112 a comparé les deux catalogues entre eux ; il ne voyait pas une clé **nommée par une vue et
absente des deux**. Laravel affiche alors la clé elle-même : « Colis levels.import », « Profil
menus.update », une colonne « menus.merchant » sur la liste des alertes douanières, « delete.no » dans
la liste des demandes express. Relevé des `__('fichier.cle')` des vues contre `lang/fr/` : **14 clés
manquantes** (et deux préfixes calculés, `customs.category_*` et `levels.vat_status_*`, qui existent).

### Ce qui est fait

| Cas | Correction |
|---|---|
| fautes de frappe (`levels.lsit`, `placeholder.Persional`) | la vue nomme la clé existante (`levels.list`, `placeholder.persional`) |
| casse ou fichier différent (`placeholder.enter_name`, `Enter_email`, `enter_address`, `opening_balance`, `levels.Enter_description`) | la vue nomme la clé existante (`Enter_name`, `enter_email`, `Enter_address`, `Enter_opening_balance`, `placeholder.Enter_description`) |
| titre faux de l'écran de modification d'un partenaire (« Lien social Ajouter ») | `levels.partner` + `levels.edit` |
| clés réellement absentes (`delete.no`, `levels.import`, `levels.payout`, `levels.select`, `menus.merchant`, `menus.update`) | ajoutées en français **et** en anglais (les deux catalogues gardent les mêmes clés) |

### Le filet

`FrenchCatalogueTest::test_every_key_named_by_a_view_exists_in_french` lit chaque vue Blade, relève
les `__()`, `@lang()` et `trans()` à clé littérale, et exige la clé dans `lang/fr/` ; une clé qui
finit par `_` (suffixe calculé) compte si une clé française commence par elle. Sabotage : `levels.list`
remis à `levels.lsit` — rouge, la vue nommée.

Suite complète : **1 312 tests, 51 188 assertions** (1 311 avant ; +1).

## S114 — les pages d'erreur et les phrases des vues parlent français (2026-10-08)

### D'où ça vient

S112 et S113 tiennent les clés de fichier (`levels.import`). Les vues traduisent aussi des
**phrases** (`__('Page Not Found')`), cherchées dans `lang/fr.json` : relevé contre ce fichier,
**23 phrases absentes**. Les pages d'erreur 401, 403, 404, 405, 419, 429 et 500 en avaient la moitié
(« Opps ! Something went wrong. »), la page de domaine inactif et la vérification d'e-mail aucune, et les
**73 listes paginées** du back-office affichaient « Affichage de 1 to 10 of 50 results ».

Le test écrit pour ce relevé a trouvé trois autres choses :

| Constat | Où |
|---|---|
| pied de page « Copyright © 2022 Concept. All rights reserved. Development by WemaxDevs » avec un lien vers l'éditeur | **toutes les pages d'erreur publiques** (`errors/layout`) — même famille que S111 |
| montants affichés `0.00` avant le premier calcul (FCFA entiers) | création de colis, back-office et panneau marchand (18 cases) |
| clés vers un fichier qui n'existe pas (`todo.delete`, `DeliveryType.*`) | liste des tâches ; écrans de type de livraison (sans route) |

### Ce qui est fait

- `lang/fr.json` : 26 entrées ajoutées (les 23 phrases, « Retour à l'accueil », « Contacter :name »).
- `errors/layout` : `lang` de la page suit la locale ; « Contact with … » et « Back to homepage » passent par
  `__()` ; le pied de page devient « © année — nom de la plateforme », lu en base avec un repli sur
  `config('app.name')` (une erreur 500 peut venir de la base). Le bouton WhatsApp de l'éditeur reste sur
  la seule page d'activation d'avant installation (`purchase_verify`), jamais servie à un client.
- Montants initiaux `0` ; `to_do.delete` ; `levels.delivery_type`.

### Le filet

`FrenchCatalogueTest` gagne deux tests : toute phrase traduite par une vue existe dans `lang/fr.json`
(une clé dont le préfixe nomme un fichier de `lang/fr/` relève du test S113, quelques noms propres
restent tels quels) ; la page 404, servie en HTTP, se lit en français (`<html lang="fr">`, « La page
demandée est introuvable. », « Retour à l'accueil ») sans « Opps », « Back to homepage » ni « WemaxDevs ».
Sabotages : le pied de page de l'éditeur remis — rouge ; « to » retiré de `fr.json` — rouge, les deux
vues nommées.

Suite complète : **1 314 tests, 51 197 assertions** (1 312 avant ; +2).

## S115 — le parcours d'une PME pilote parle français, du formulaire au courriel (2026-10-08)

### D'où ça vient

S112 à S114 tiennent les catalogues ; aucun ne voit le texte **écrit en dur** dans une vue. Un relevé
des nœuds de texte des vues en a compté environ 370, dans 153 fichiers. Ce lot prend ceux qu'une PME
pilote traverse avant son premier colis :

| Écran | Avant |
|---|---|
| inscription marchand, inscription société, `auth/register` | « Registrations Form », « Please enter your user information. », « Select Hub », « I agree to … Privacy Policy & Terms. », « Register My Account », « Already member? Login Here. » |
| confirmation du code (marchand, société) | « Confirm OTP », « Check Your Phone. We have sent you a 5 digit OTP… », « Submit », « Didn't get? Resend Code! » |
| courriels de bienvenue (marchand, société) | sujet « Welcome to new merchant » / « … company », « Thank you for your interest in becoming an merchant », « Get download our android or i application », `lang="en"`, violet de l'éditeur `#7e0095` |
| portefeuille du marchand | « Total Wallet Balance », « You are low on balance. Please recharge », « Total Deducations », « Recharge Wallet » |

### Ce qui est fait

Chaque texte passe par `__('Phrase…')`, traduit dans `lang/fr.json` (34 entrées). Les deux courriels :
sujet `__('Welcome to :name')` → « Bienvenue sur … », langue de la page selon la locale, couleur
**`#12503A`** (vert de la charte) à la place de `#7e0095`, une phrase de contact réécrite (« Une question ?
Écrivez-nous à … ou appelez le … »).

### Le filet

`tests/Feature/MerchantJourneySpeaksFrenchTest` (3 tests) : dans les huit vues de `PARCOURS`, **aucun
texte littéral** hors `{{ }}` (expressions et directives Blade retirées avant le découpage, `->`
compris) ; les deux courriels rendus en français, au vert de la charte, sans violet ni phrase du socle ;
la page d'inscription marchand servie en HTTP se lit en français. Sabotages : « Registrations Form »
remis en dur — rouge (deux tests) ; violet remis dans le courriel — rouge.

Reste au relevé : les autres vues du back-office (rapports, impressions, installateur…), lot par lot.

Suite complète : **1 317 tests, 51 228 assertions** (1 314 avant ; +3).

## S116 — le panneau marchand parle français et compte en FCFA (2026-10-08)

### D'où ça vient

Après l'inscription (S115), la PME vit dans `merchant_panel/`. Le détecteur de S115, appliqué au
dossier, y a relevé 140 textes écrits en dur dans 29 fichiers. Le plus grave n'était pas de
l'anglais :

| Constat | Où |
|---|---|
| **« Tk »** (taka) après le prix de l'emballage — affiché « 1 000 FCFA Tk », `formatAmount()` portant déjà la devise | création, modification et duplication d'un colis |
| export PDF des colis en anglais, colonne « Cash Collection (TK) », montants bruts (`1500.00` possible sur une colonne `decimal`, D15), `lang="en"` | `parcel/parcel_export_pdf` |
| portefeuilles du Bangladesh nommés en dur (« Bkash », « Rocket », « Nagad ») et « Cash » | paiements reçus, liste des paiements en ligne (les deux pages restent atteignables) |
| « Low / Medium / High », « Paid At », « Download File », « View », « Notification », « Toggle Dropdown », « (KG) » | assistance, colis, relevés, menu, listes |

### Ce qui est fait

- « Tk » retiré ; l'export PDF traduit, en FCFA, montants par `formatAmount()`, langue de la page
  selon la locale ; « Cash » → `merchant.cash` ; codes 3-5 → **« Mobile Money »** (neutre et vrai :
  quel opérateur porte chaque code reste à trancher, **D16** ouvert au registre) ; le reste par
  `__()` et `lang/fr.json` (25 entrées).
- Le détecteur devient un trait partagé, `Tests\Concerns\FindsHardcodedText` (il retire aussi les
  blocs `@php … @endphp` et les entités HTML) ; `MerchantJourneySpeaksFrenchTest` (S115) l'utilise.

### Le filet

`tests/Feature/MerchantPanelSpeaksFrenchTest` (4 tests) : aucune vue du panneau n'a de texte en dur
(hors `kg`, `FCFA`, `CSV`), huit pages **exemptées avec leur motif** — deux démonstrations SSLCommerz
sans route, six pages de paiement en ligne dont la route est sous `onlinePayoutEnabled()` (D10) ou
une passerelle désactivée (S21) — et chaque exemption doit viser un fichier qui existe ; aucun taka
(`Tk`, `(TK)`, `BDT`, `৳`) ; les paiements reçus ne nomment plus les portefeuilles du Bangladesh.
Sabotages : « Tk » remis — rouge (deux tests) ; « Low » remis en dur — rouge.

Suite complète : **1 321 tests, 51 297 assertions** (1 317 avant ; +4).

## S117 — le back-office parle français, ses comptes sont béninois (2026-10-08)

### D'où ça vient

S116 tenait le panneau marchand. Appliqué à **toutes** les vues, le même détecteur a relevé 404
textes écrits en dur dans 133 fichiers : écrans de colis, impressions et étiquettes, rapports,
comptes, virements, salaires, réglages, vitrine. Trois constats dépassaient la langue :

| Constat | Où |
|---|---|
| un compte du transporteur se créait en choisissant **bKash, Rocket, Nagad**, sa banque parmi **BB, DBBL, IB** (banques du Bangladesh) ; une cinquantaine d'écrans les réécrivaient en dur | `account/*`, `fund_transfer/*`, `bank_transaction/*`, `salary/*`, `income/*`, `expense/*`, `hub_payment/*`, `merchantmanage/payment/*`… |
| **« Tk »** après le prix de l'emballage, « Amount(Tk) » dans deux rapports, étiquettes « Route : ISD » (Dhaka) | `parcel/create`, `edit`, `duplicate`, `print-label`, rapports livreur et agence |
| la page « domaine inactif » portait le **logo et le WhatsApp de l'éditeur** et parlait de CodeCanyon | `purchase_verify`, `errors/layout`, `public/wemaxdevs.png` |

### Ce qui est fait

- **D16, défaut réversible** : `lang/*/account_gateway.php` (1 Espèces, 2 Banque, 3 MTN MoMo, 4 Moov
  Money, 5 Autre Mobile Money) et `lang/*/account_bank.php` (huit banques béninoises, « Autre banque »)
  sont la **seule source** ; formulaires et listes bouclent dessus, les `@if gateway == 3` deviennent
  une expression. `Account\StoreRequest` / `UpdateRequest` refusent un code de banque hors liste.
  Rien n'est réécrit en base (voir D16 pour les comptes créés avant).
- Vues traduites par trois agents en parallèle (clés existantes d'abord, 41 phrases ajoutées à
  `lang/fr.json`) ; « Tk » retiré ; la zone du colis remplace « ISD » sur l'étiquette ; pages
  d'impression en `lang` de la locale ; « when billed annually » retiré (faux pour un plan mensuel).
- `purchase_verify` (jamais servie : `PurchaseVerify::purchaseVerify()` rend vrai) dit seulement
  « Ce domaine est inactif » au nom de la plateforme ; la branche WhatsApp du gabarit d'erreur et
  `public/wemaxdevs.png` sont retirés.

### Le filet

`tests/Feature/BackOfficeSpeaksFrenchTest` (5 tests) : **aucune vue servie** n'a de texte en dur
(hors `kg`, `FCFA`, `CSV`), avec seize exemptions motivées (installateur S88, page Laravel sans
route, pages des modules coupés D10/S21) qui doivent exister ; aucun taka, banque ou portefeuille du
Bangladesh, ni l'éditeur, ni dans le texte ni dans les phrases de `__()` (sauf l'écran de réglage du
module coupé, qui nomme ses passerelles) ; libellés D16 fixés ; page « domaine inactif » sans
éditeur. `PublicBrandAssetsTest` refuse le logo de l'éditeur par empreinte. `FrenchCatalogueTest`
lit les clés numériques (`account_gateway.3`).
Sabotages, tous rouges : une option « BB » en dur, « Tk » remis, le logo de l'éditeur remis sous un autre nom.

## S118 — les messages du back-office et de l'API parlent français (2026-10-08)

### D'où ça vient

S117 tenait les vues. Restait ce que le **code** affiche : 128 notifications `Toastr` et douze
réponses d'API écrites en anglais littéral (« Something went wrong. » trente-six fois,
« Oparation Failds! », « Catagory Insert Successfully! », « Successfully sended. »), des titres
« Error » / « Success » en dur, six `Toastr::error('parcel.error_msg')` qui affichaient **la clé
brute**, et neuf clés nommées par le code mais absentes du catalogue — dont **cinq notes de relevé**
du retour d'un colis au marchand : le relevé affichait `statementNote.return_received_by_merchant_statment`.
L'API négocie sa langue depuis S90 : ses messages restaient pourtant anglais.

### Ce qui est fait

- Les 149 appels passent par `__()` ; les phrases du socle sont corrigées en anglais (clé) et
  traduites dans `lang/fr.json` (80 entrées) ; les titres disent `message.success` / `message.error`.
- Clés fautives redirigées (`paymentrequest.deleted_msg`, `merchantshops.update_msg`,
  `account.update_msg`) ou ajoutées (`parcel.*`, `statementNote.*`). La note du transporteur en
  regard d'un retour a sa propre clé (`return_to_merchant_courier_statement`, « Dépenses ») au lieu
  de reprendre celle du livreur (« Revenus »), et l'annulation la sienne.
- Les lignes déjà écrites ne sont **pas réécrites** (D8) : `App\Traits\TranslatesStoredNote` les
  **lit** traduites sur les relevés marchand, livreur et transporteur.
- ⚠️ `beninlink:retours-annules` retrouvait ses lignes **par la clé brute** (« stable parce que sa
  traduction n'existe pas ») : il accepte maintenant toutes les formes (`CancelledReturnsCommand::formes()`
  — clé, texte français, texte anglais).
- Deux tests du socle cherchaient « low balance » / « Already salary generated » dont **un en
  négatif** : il serait passé sans rien prouver ; ils lisent le message par `__()`.

### Le filet

`tests/Feature/FlashMessagesSpeakFrenchTest` (4 tests) : aucun message ni titre de `Toastr`, de
`responseWith*()` ou de `->with('success'|…)` n'est littéral dans `app/` ; toute phrase ou clé que
`app/` traduit existe dans `lang/fr` (clés à suffixe calculé comptées par leur fichier) ; une note
stockée comme clé se lit en français, une note libre reste telle quelle, et la commande reconnaît les
trois formes. Sabotages, tous rouges : un `Toastr::error('Something went wrong.')` remis, une clé de
note retirée, le trait retiré d'un modèle.

## S119 — les scripts du back-office parlent français (2026-10-08)

### D'où ça vient

Après les vues (S117) et le code PHP (S118), restait ce que les **scripts** affichent eux-mêmes :
les cinq interrupteurs de statut et de priorité (« Are you confirm ? », boutons « Yes » / « Cancel »
posés sur `denyButtonText`, option sans effet : **le bouton d'annulation n'apparaissait pas**, puis
« Status updated successfully »), la confirmation de suppression, le sélecteur de période (« Today »,
« Last 7 Days », « Clear »), les cartes (« Your current location. »), et les écrans de recettes,
dépenses, salaires et remises d'espèces (« Parcel not found! », « Ops! not enough blance. »,
« Current Balance: 15000.5 » — montant brut, décimales).

### Ce qui est fait

- `lang/*/js.php` : 28 textes, rendus une fois par `backend.partials.footer` sous `var trad` ; les
  scripts lisent `trad.cle` ou les globales déjà traduites (`yes`, `cancel`, `confirmUpdate`).
- Les interrupteurs ont un vrai bouton d'annulation (`showCancelButton` + `cancelButtonText`).
- Les soldes passent par `montantFcfa()` (pied de page) : FCFA entiers, séparateur français.
- Le sélecteur de période garde **son format et son séparateur** (`MM/DD/YYYY`, « To ») : les
  filtres les découpent côté serveur ; seuls les libellés changent.

### Le filet

`tests/Feature/BackOfficeScriptsSpeakFrenchTest` (4 tests) : aucun script de `public/backend/js`
(hors bibliothèques tierces nommées) n'affiche un littéral — options de dialogue, `alert()` /
`confirm()`, `.text()`, plages de dates, nœud de texte d'un fragment HTML ; toute clé `trad.*` lue
existe en français et en anglais, et aucune clé n'est orpheline ; le pied de page rend le catalogue
et `montantFcfa` ; le format de période reste celui que lisent les filtres. Sabotages, tous rouges :
« Are you confirm ? » remis, une clé retirée, « Parcel found. » remis dans un fragment.

## S120 — le registre dit l'état du 2026-10-08 (2026-10-08)

Lot de documentation. `docs/CARTOGRAPHIE_PROJET.md` datait du 2026-10-06 (PR #168, S100) : § 1 et
§ 7.4 relus sur le dépôt et sur les runs d'*Actions*, rien de déduit.

- § 1, **mesuré** : 134 contrôleurs, 82 modèles, 108 migrations (`find`) ; 106 routes `/api/v10`
  (`route:list`) ; 161 fichiers de test, 1 334 tests, 51 464 assertions (suite de S119) ; 23 et 13
  écrans expo-router, 52 et 36 fichiers TypeScript hors tests. Les compteurs de tests des apps restent
  ceux de S103 (non relancés ici). Le nombre de commits n'est plus donné : le clone de travail est
  partiel, `git log` y compte faux.
- § 7.4 E5 : la francisation du web est fermée par filets (S113 à S119) ; reste à l'œil humain ce
  qu'un filet ne voit pas, et la relecture par le transporteur de la banque de ses comptes (D16).
- Les runs de déploiement de S116 (267), S117 (269) et S118 (271) sont verts en production et en
  recette, lus dans *Actions*.

## S121 — un montant affiché est en FCFA entiers (2026-10-08)

### D'où ça vient

D15 a tranché : les colonnes d'argent restent `decimal`, la règle « FCFA entiers » se tient à
l'**affichage**. Un relevé des nœuds de texte des vues a trouvé **66 montants écrits bruts** —
« 15000.00 » au lieu de « 15 000 FCFA » : soldes des comptes dans les listes de virement, de paiement
marchand et d'agence ; salaires ; prix des plans et historique d'abonnement ; COD et encaissement sur
les fiches colis ; récapitulatif d'un colis modifié ou dupliqué au panneau marchand (`?? '0.00'`).

### Ce qui est fait

- Les 66 passent par `formatAmount()` (avec la devise, ou sans dans les cases que les scripts
  réécrivent, comme le back-office depuis S116). Rien n'a changé dans un attribut `value=` ni dans un script.
- ⚠️ Deux écrans relisaient un solde affiché par `parseInt($('#account_balance_').text())` (dépense,
  salaire) ou `#currentBalance` (virement) pour refuser un montant supérieur au compte : sur
  « 15 000 FCFA », `parseInt` rend **15**, et la dépense aurait été refusée à tort. Ils lisent par
  `montantLu()` (pied de page), qui comprend « 15 000 FCFA » comme « 15000.00 ».

### Le filet

`tests/Feature/MoneyIsDisplayedInFcfaTest` (4 tests) : aucun champ d'argent n'est affiché brut dans un
nœud de texte (hors pages inatteignables : installateur, modules coupés D10/S21) ; le détecteur est
lui-même éprouvé (il voit `<td>{{ $account->balance }}</td>`, épargne `value="{{ … }}"`) ;
`formatAmount('15000.00')` rend « 15 000 FCFA » ; aucun script ne relit un montant affiché par
`parseInt`. Sabotages, tous rouges : un salaire remis brut, un `parseInt(… .text())` remis.


## S122 — une date affichée parle la langue de son lecteur (2026-10-08)

### D'où ça vient

`->format()` et `date()` ne se traduisent pas. Le socle affichait « 08 Oct 2026 06:35:00 pm » au
back-office (demandes de paiement, transactions, tickets, fiches colis), « 08th october 2026 » par
l'aide `dateFormat()` — aussi dans l'**API** (colis, journal, relevés, douane, fraudes) — et
« 01st january 1970 » pour une date absente (`pickup_date` d'un colis non ramassé). L'API renvoyait
`created_at` sous la forme « 17 Aug 2026, 09:29 PM » quel que soit l'`Accept-Language` négocié (S90).

### Ce qui est fait

- `dateFormat()` rend « 8 octobre 2026 », la nouvelle `dateTimeFormat()` « 8 oct. 2026, 18:35 » :
  Carbon `translatedFormat`, qui suit la locale de la requête — l'API répond donc dans la langue
  négociée. Une date absente rend `null` (les champs sont `nullable` dans la spec).
- 25 champs de 14 ressources `v10` (`created_at`, `updated_at`, `invoice_date`…) et la ligne de colis
  d'un relevé (`InvoiceParcelResource`, lue par l'export) passent par ces aides ; l'heure se dit sur **24 heures** partout (`H:i`), y compris
  `parcel_time` et `time_date`.
- 19 vues : 17 dates (blog public, tickets, demandes de paiement, transactions, fiches colis, rapport
  des salaires) passent par les aides ou `translatedFormat`, et les heures des journaux de colis et du
  suivi public passent à `H:i`. Au passage, le fil d'un ticket au panneau
  marchand datait chaque message de **maintenant** (`Carbon::parse()` sans argument) : il lit
  `$chat->created_at`.
- Contrat des apps : les deux apps affichent ces chaînes telles quelles (aucun `new Date()` sur
  elles) ; seule la description de l'overlay change (« 17 août 2026, 21:29 »).

### Le filet

`tests/Feature/DatesSpeakFrenchTest` (3 tests) : les aides suivent la langue (fr, en) et rendent
`null` pour une date absente ; aucune vue servie, ressource d'API ni export n'appelle `->format()` ou
`date()` avec un mot anglais (`D l N S F M`) ou une heure sur 12 (`a A h g`) ; la page d'accueil
date ses articles « 17 août 2026 ». Sabotages, tous rouges : une aide forcée en anglais, un
`format('Y-m-d h:i:s A')` remis sur la fiche d'agence.

## S123 — l'origine d'un mouvement de portefeuille se lit en français (2026-10-08)

### D'où ça vient

Le socle écrit `wallets.source` en anglais : « Wallet Recharge » quand le transporteur approuve une
recharge, « Parcel delivery charge - #TRK » quand un colis débite le portefeuille prépayé. Le marchand
le lisait tel quel dans son historique (panneau web, `GET wallet/history`) et dans sa notification de
crédit : « +50 000 FCFA crédités via Wallet Recharge. ».

### Ce qui est fait

- La valeur **stockée ne bouge pas** : `WalletDebit::SOURCE` + numéro de suivi est la clé qui relie un
  débit à son colis (`beninlink:colis-non-debites`). Même règle que les notes de relevé (S118).
- `Wallet::source_label` traduit à l'affichage : « Recharge du portefeuille », « Frais de livraison du
  colis #TRK » ; un nom propre (« FedaPay ») passe tel quel. `Wallet::SOURCE_RECHARGE` nomme la valeur
  que `WalletRepository` écrit (deux endroits).
- Le lisent : les deux vues de l'historique marchand, `WalletResource::source` (langue négociée ; les
  apps ne l'interprètent pas, description de l'overlay mise à jour) et `MerchantFeed::walletCredited()`.

### Le filet

`tests/Feature/WalletSourceSpeaksFrenchTest` (3 tests) : l'étiquette suit la langue et laisse la clé
intacte ; l'API et les deux vues l'affichent ; une recharge approuvée notifie « via Recharge du
portefeuille ». Sabotages, tous rouges : la notification relit `source` brute, l'étiquette rend la
valeur stockée.

## S124 — l'installateur de modules fermé, cinq écrans gardés par leur droit (2026-10-08)

### D'où ça vient

L'arriéré de `WebAdminPermissionCoverageTest` gardait 18 routes `admin/*` sans droit. En les lisant une
par une, les six routes `addons` se sont révélées les plus graves du dépôt : `AddonController::store()`
dépliait une archive téléversée, copiait ses fichiers dans `base_path()` et exécutait son
`sql/update.sql` (`DB::unprepared`). Seule garde : `panel:back-office`. **Tout compte du back-office de
tout transporteur, sans aucun droit, pouvait installer du code sur la plateforme commune.** Aucun menu
ne les liait.

### Ce qui est fait

- Les six routes `addons` sont **retirées** (`routes/web.php`). Le contrôleur et ses vues restent
  (code du socle neutralisé, pas effacé) ; BeninLink se déploie par git (`deploy.sh`).
- Cinq routes reçoivent le droit que le menu latéral lit déjà pour leur entrée : Google Maps
  (`notification_settings_read` / `_update` pour l'écriture, comme les réglages de notification),
  historique d'abonnement (`subscription_read`), relevés payés (`paid_invoice_read`),
  `reports/mhd-pdf` (`merchant_hub_deliveryman`, comme ses routes sœurs).
- Qui perd l'accès, mesuré : le rôle Admin garde tout ; le rôle User perd l'historique d'abonnement
  (son menu ne l'affichait pas) et l'écriture Google Maps (il n'écrivait déjà pas les réglages de
  notification) ; le chef d'agence perd ces cinq écrans, que son menu cachait déjà.
- Arriéré : 18 → **7** (plafond abaissé) ; restent les sept aides AJAX à jeu de droits large.

### Le filet

`tests/Feature/AddonInstallerClosedTest` (2 tests) : aucune route n'atteint `AddonController`, et un
compte du back-office reçoit 404 aux anciennes adresses. `WebAdminPermissionCoverageTest` prouve les
cinq gardes (refus sans droit, accès avec chacun de ses droits). Les exemptions `addons` sortent de
`WebIsolationCoverageTest` et `BodyIdentifierCoverageTest`. Sabotages, tous rouges : une route
`addons/activation` remise, la garde des relevés payés retirée.

## S125 — l'arriéré des droits du back-office fermé (2026-10-08)

### D'où ça vient

Après S124, `WebAdminPermissionCoverageTest` gardait **7** routes `admin/*` sans droit : les aides AJAX
dont S44 avait noté le « jeu de droits large ». Sans garde, tout compte du back-office les appelait :
la liste des marchands et leurs comptes de versement, les agences, la recherche de colis par suivi.

### Ce qui est fait

Chaque aide porte la liste **dérivée** des droits de ses écrans appelants (vues **et** `custom.js`), la
forme de S43 pour les deux sélecteurs partagés :
- `parcel/merchant` — 20 droits : les 24 vues qui la nomment (`merchantUrl` ou `data-url`) (colis, revenus,
  dépenses, salaires, paie, remises des livreurs, cinq rapports, recharges, versements) ;
- `parcel/hub` — revenus, remises des livreurs, rapport marchands/agences/livreurs ;
- `merchant/account`, `merchant/search` — `payment_read|payment_create|payment_update` (paiements marchands) ;
- `parcel/search`, `assign-pickup/parcel/search`, `assign-return-to-merchant/parcel/search` —
  `parcel_status_update`, le droit des écritures en masse qu'elles servent.

Qui perd l'accès, mesuré : le chef d'agence garde le sélecteur de marchands et d'agences (il porte les
droits des remises des livreurs) ; les trois recherches de colis en masse demandent l'écriture qu'elles
préparent, qu'il n'avait pas. L'**arriéré passe à 0** (plafond 0), pour la première fois depuis S44.

### Le filet

`WebAdminPermissionCoverageTest` prouve les sept gardes (refus sans droit, accès avec **chacun** de
ses droits). `ArrearsRemainderScopeTest` et `WebPanelSeparationTest` donnent le droit à leur agent (même
forme que S43 pour `payout`). Sabotages, tous rouges : garde de `parcel/hub` retirée, un droit retiré de
la liste de `parcel/merchant`.

## S126 — un fichier téléversé ne devient jamais du code serveur (2026-10-08)

### D'où ça vient

En cherchant la famille de l'installateur de S124 (un fichier qui devient du code), un défaut plus
direct : **exécution de code par n'importe quel marchand**. La pièce jointe d'un ticket de support
(`merchant/support/store`) n'avait aucune règle de validation ; `SupportRepository::file()` la nommait
`date('YmdHis') . '.' . getClientOriginalExtension()` sous `public/uploads/support/` ; et nginx exécutait
tout `.php` sous `public/` (`location ~ \.php$`). Un marchand — l'inscription est libre — joignait
`x.php` et l'appelait par son URL. Reproduit en test avant correction. Les 24 dépôts qui écrivent sous
`public/uploads/` suivaient tous la même forme (32 occurrences).

### Ce qui est fait — deux défenses, chacune suffisante seule

- **Le code** : `App\Support\SafeUpload::extension()` (aide `safeUploadExtension()`) déduit l'extension du
  **contenu** (`guessExtension()`, type MIME lu par `finfo`) et la borne à une liste : jpg, png, gif, webp,
  pdf, doc, docx, xls, xlsx, csv, txt. Ni php, ni html, ni svg (script servi depuis notre domaine). Hors
  liste, le téléversement est refusé (« Ce type de fichier n'est pas accepté. »). Les 32 occurrences
  passent par elle.
- **nginx** (`docs/guides/infra/nginx/beninlink.conf`) : seul `location = /index.php` passe à PHP-FPM ; tout
  autre `.php` répond 404 (`location ~ \.php$ { return 404; }`). Le dépôt n'a pas d'autre point d'entrée
  PHP sous `public/`.

### Le filet

`tests/Feature/UploadedPhpNeverLandsTest` (4 tests, sur de **vrais** fichiers : `UploadedFile::fake()`
annonce un type tiré du nom) : un marchand ne dépose pas de `.php` par un ticket et une vraie image passe ;
l'extension vient du contenu (une image nommée `.php` devient `.png`, php / svg / html refusés) ; aucun
fichier de `app/` n'appelle `getClientOriginalExtension()` ; nginx n'exécute que `/index.php` et `public/`
n'a que lui. Sabotage rouge : le dépôt du support marchand remis sur l'extension du client.

### Côté serveur (au porteur)

La configuration nginx ne se déploie pas avec le code : copier la nouvelle `beninlink.conf` (et l'appliquer
au vhost de recette), `nginx -t`, recharger. Puis chercher un dépôt antérieur :
`find /var/www/beninlink*/web/public/uploads -type f \( -iname '*.php*' -o -iname '*.phtml' -o -iname '*.phar' \)`.

**Fait le 2026-10-08 — rapporté par le porteur (S128)** : la nouvelle configuration est installée en
production **et** en recette (sauvegardes des anciennes : `/root/beninlink.vhost.bak-s126`,
`/root/beninlink-recette.vhost.bak-s126`), `nginx -t` passe, rechargée. Contrôle : un `.php` sous
`/uploads/` répond **404**, l'accueil **200**, sur les deux vhosts. La recherche n'a trouvé **aucun**
`.php`, `.phtml` ni `.phar` sous `public/uploads/`, ni en production ni en recette : rien n'indique
que la faille ait été exploitée avant le correctif. La partie serveur de S126 est **close**.

## S127 — un texte riche écrit par un autre est nettoyé avant d'être rendu (2026-10-08)

### D'où ça vient

Suite de S126 (« ce qu'un utilisateur dépose devient-il actif chez un autre ? ») : **XSS stocké du marchand
vers le back-office**. La description d'un ticket de support et ses réponses étaient rendues par
`{!! … !!}` dans `backend/support/view`. Le middleware `XSS` du socle retire les balises de toute entrée
**sauf `description`**, et l'API des apps n'y passe pas du tout : un marchand écrivait
`<img src=x onerror=…>` et le script tournait dans la session de l'agent qui ouvrait le ticket.

### Ce qui est fait

- `App\Support\SafeHtml::clean()` (aide `safeHtml()`), sur HTMLPurifier **déjà présent** (tiré par
  phpspreadsheet, `require`) : garde la mise en forme d'un éditeur (paragraphes, gras, listes, liens http/https/
  mailto/tel, images, tableaux) et retire scripts, gestionnaires d'évènements, styles et `javascript:`.
- Rendus par lui : tickets de support (fil et description, back-office et panneau marchand), actualités
  du panneau marchand, pages, articles, services et FAQ du site public et leurs listes au back-office.
- Le message du formulaire de contact public, du texte brut, est échappé (`nl2br(e(…))`) dans le courriel.
- Les autres `{!! !!}` restants rendent des pastilles d'état composées par le code (`my_status`,
  `parcel_status`, code-barres) : aucune saisie n'y entre.

### Le filet

`tests/Feature/RichTextIsSanitizedTest` (3 tests) : le nettoyeur garde `<strong>` et un lien https et retire
`onerror`, `<script>`, `javascript:` ; un ticket piégé s'ouvre au back-office (HTTP) sans son script ; aucune
vue ne rend brut un champ `description`, `message`, `answer`, `details` ou `note`. Sabotage rouge : la
description du ticket remise en `{!! !!}`.

## S128 — la partie serveur de S126 est close (2026-10-08)

Lot de registre seulement, sur le compte rendu du porteur : configuration nginx de S126 installée en
production et en recette (seul `/index.php` s'exécute, un `.php` sous `/uploads/` répond 404), et
recherche négative de fichiers exécutables déjà déposés. Détail sous S126 ci-dessus. Aucun code ne
change ; ce registre n'inscrit que ce que le porteur a constaté et rapporté.

## S129 — le retour Stripe n'active qu'un abonnement, pour le compte qui a payé (2026-10-08)

### D'où ça vient

En relisant les routes exemptées du jeton CSRF : `subscription/success` (retour de Stripe, tout verbe).
S1 y vérifiait la session auprès de Stripe — payée, `client_reference_id` = compte connecté, montant
du plan —, mais deux portes restaient ouvertes :
- `CompanyRepository::switchPlan($request)` appliquait le plan à `User::find($request->user_id)`, lu
  **dans l'URL** : il remplaçait les **permissions** du compte nommé, de n'importe quelle société
  (le super-administrateur compris). Les exemptions des filets disaient « société déduite de la
  session » : c'était faux.
- Une session payée **se rejouait** : rappeler la même URL créait un nouvel abonnement à compter du
  jour même. Payer une fois suffisait pour renouveler indéfiniment.

### Ce qui est fait

- Le contrôleur passe à `switchPlan()` une requête **composée** (forme de FedaPay) : plan vérifié,
  `user_id = Auth::id()` — le compte que Stripe a reconnu —, et l'identifiant de session.
- `subscriptions.stripe_session_id` (migration, **unique**) : une session déjà utilisée est refusée
  avant tout ; deux retours simultanés, l'index refuse le second (`switchPlan()` rend `false`, le
  contrôleur le dit au lieu d'annoncer un succès).
- Les motifs d'exemption de `SearchSurfaceCoverageTest` et `BodyIdentifierCoverageTest` disent
  désormais ce qui est vrai, et nomment le test.

### Le filet

`tests/Feature/StripeSubscriptionReturnTest` (3 tests, Stripe simulé par `ApiRequestor::setHttpClient`) :
le plan va au payeur et les droits du compte nommé dans l'URL ne bougent pas ; une session payée
n'active qu'un abonnement ; une session impayée n'active rien. Sabotages, tous rouges : `user_id` de
l'URL remis ; garde de rejeu **et** index unique retirés (chacun suffit seul : défense en double).

## S130 — le parcours OTP du site marchand est limité comme l'API (2026-10-08)

### D'où ça vient

S96 a limité les entrées d'authentification de l'**API** (limiteur `connexion`). Le même parcours existe
sur le **site** : `POST merchant/otp-verification` (code à **cinq chiffres**, 90 000 possibilités) et
`POST merchant/resend-otp` (un **SMS payant** à chaque appel) n'avaient aucune borne. Le premier se
devinait en quelques minutes ; le second laissait n'importe qui faire envoyer des SMS au transporteur.

### Ce qui est fait

- Limiteur nommé `connexion-web` (`RouteServiceProvider`), mêmes bornes que S96 : **5/min par numéro
  (chiffres seuls) + adresse**, **30/min par adresse**. Réponse d'écran : retour au formulaire OTP avec
  `auth.throttle` (« Trop de tentatives… »), pas une enveloppe JSON.
- Posé sur les deux routes. L'inscription (`merchant/sign-up-store`) reste libre, comme `register` en S96.

### Le filet

`tests/Feature/WebOtpThrottleTest` (2 tests) : les deux routes portent le limiteur ; le sixième code pour
un même numéro (écrit avec ou sans espaces) est refusé avec le message français, un autre numéro garde ses
essais. Sabotage rouge : limiteur retiré de `merchant/otp-verification`.

## S131 — la surface publique a une cadence bornée (2026-10-08)

### D'où ça vient

Après S130, ce qui reste sans compte et sans borne :
- le **suivi d'un colis** (`GET /tracking`, `GET api/v10/parcel/tracking/{id}`) : un numéro de suivi est un
  préfixe et **huit chiffres** tirés au sort, et la page montre les **téléphones du marchand et des
  livreurs** — sans borne, on énumérait les numéros ;
- les formulaires de **contact** et de **lettre d'information** (web et API) : un courriel par envoi ;
- l'**inscription marchand** (`merchant/sign-up-store`) : un **SMS** d'OTP par inscription.

### Ce qui est fait

- Deux limiteurs, **20 par minute et par adresse** : `public-web` (retour à la page avec `auth.throttle`) et
  `public-api` (enveloppe JSON, 429). L'API garde aussi son `throttle:api` (60/min) de groupe.
- Posés sur les sept entrées ; la spec OpenAPI régénérée documente le 429 (S96).

### Le filet

`tests/Feature/PublicSurfaceThrottleTest` (2 tests) : chaque entrée publique porte son limiteur ; la 21ᵉ
consultation du suivi depuis une adresse est refusée avec le message français. Sabotage rouge : limiteur
retiré du suivi web.

## S132 — le cookie de session ne part plus en clair (2026-10-08)

### D'où ça vient

`config/session.php` lisait `'secure' => env('SESSION_SECURE_COOKIE')`, et aucun `.env` du dépôt ni des
guides ne pose cette variable : le cookie de session n'avait **pas** l'attribut `Secure`. Il partait donc
aussi sur une requête en clair — le premier appel en `http://` avant la redirection d'nginx, un lien ancien,
un réseau Wi-Fi partagé — et qui l'écoutait reprenait la session (back-office compris). Le HSTS d'nginx est
encore à 300 s (« on commence court »).

### Ce qui est fait

- Sans valeur, le cookie est `secure` dès que `APP_URL` commence par `https://`. Une installation locale en
  `http://localhost` reste utilisable ; `SESSION_SECURE_COOKIE` garde le dernier mot.
- `verifier-env.sh` refuse `SESSION_SECURE_COOKIE=false` en production (le déploiement s'arrête avant de
  couper le site) ; en recette, il le laisse.

### Le filet

Deux tests dans `RecetteDeploymentTest` : le garde refuse `false` en production et accepte `true`, l'absence
et une recette en `false` ; la configuration rend `secure` pour une `APP_URL` en https et pas pour
`http://localhost`. Sabotages, tous rouges : garde neutralisé, défaut de la configuration retiré.

### Côté serveur (au porteur)

Rien à faire si le `.env` ne porte pas `SESSION_SECURE_COOKIE=false` et que `APP_URL` est en `https://`.
Après le déploiement, les sessions ouvertes restent valides ; le cookie suivant reçoit l'attribut.

## S133 — un numéro béninois se saisit comme on le dit (2026-10-08)

### D'où ça vient

Vingt et une règles de validation du socle exigent `numeric|digits_between:11,14` : le format du Bangladesh
(bloc F, « à revoir pour le Bénin »). Depuis la migration ARCEP du 30 novembre 2024, un numéro béninois a
**dix chiffres** et commence par `01`. Une PME pilote qui tapait « 01 97 01 00 07 » était refusée à
l'inscription (dix chiffres), « +229 01 97 01 00 07 » aussi (pas `numeric`), et ce même « +229… », lu comme
une adresse électronique, ne la connectait pas. L'aide de l'app marchand demandait encore l'ancien format à
huit chiffres (« 22997000000 »), qui n'aboutit plus. Les SMS partaient vers le numéro tel que rangé, sans
indicatif s'il avait été saisi sans (bloc H, « aucun formatage E.164 »).

### Ce qui est fait

- `App\Support\BeninPhone::normalize()` : forme rangée `2290197010007` (international sans « + », 13
  chiffres). National `01XXXXXXXX` → `229` devant ; ancien national à 8 chiffres et ancien international
  `229XXXXXXXX` → le `01` de la migration ; séparateurs et préfixe `+` / `00` retirés. Un numéro d'un autre
  pays garde ses chiffres ; ce qui n'est pas un numéro ressort tel quel, et la validation le refuse.
- `NormalizePhoneNumbers`, middleware **global** (après `ConvertEmptyStringsToNull`) : `mobile`, `phone`,
  `contact_no`, `mobile_no`, `customer_phone` — imbriqués compris — passent par `normalize()` sur toute
  **écriture**, web et API. Les vingt et une règles restent telles quelles : elles valident la forme rangée.
  Un `GET` n'est pas touché : les filtres de recherche (`?phone=`) cherchent par morceau dans des numéros
  rangés avant S133.
- Connexion web : un identifiant qui se lit comme un numéro se cherche sous la forme rangée ; un compte rangé
  avant S133 sous une autre forme reste joignable par ce que l'on tape (`LoginController::mobileSaisi()`).
- `SmsService::sendSms()` / `sendOtp()` : le numéro part en forme rangée, même rangé sans indicatif.
- App marchand : l'aide du champ téléphone dit « 01 97 00 00 00 », avec ou sans +229.

### Ce qui reste

Les numéros **déjà en base** gardent leur forme (aucune migration de données) : un doublon ancien format /
forme rangée n'est pas vu par `unique:users,mobile`. Un compte inscrit avant S133 dont le code OTP n'est pas
encore validé doit le redemander après le déploiement (sa recherche se fait sous la forme rangée).

### Le filet

`BeninPhoneNumbersTest` : neuf écritures d'un même numéro → une forme, ce qui n'est pas un numéro reste
intact ; le middleware touche les écritures (imbriquées comprises), jamais un `GET`, et il est global ;
inscription par l'API en « +229 01 97 01 00 07 » puis code OTP saisi en « 01 97 01 00 07 » ; connexion web
par « +229 01 97 92 00 02 » ; compte ancien format connecté par ce qu'il tape ; SMS et OTP vers un numéro
rangé sans indicatif. Trois tests qui attendaient l'ancien format brut sont mis à la forme rangée
(`QueuedDeliveryTest`, `RecettePiloteRepetitionTest` M9 et A4). Sabotages, tous rouges : middleware retiré,
règle `01` retirée, repli de connexion retiré, SMS non normalisé, `GET` normalisé.

### Côté serveur (au porteur)

Rien à faire. Après le déploiement, un numéro saisi au format national est accepté ; les numéros déjà en base
ne changent pas.
