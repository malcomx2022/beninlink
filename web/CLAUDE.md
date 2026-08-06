# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> web/ (backend Laravel). Complète le CLAUDE.md racine du monorepo.
> Garder < 200 lignes. Aucun secret ici (les clés vivent dans `web/.env`).

## Rôle
Cœur de BeninLink : SaaS de coursier multi-société (fork **We Courier**, éditeur
WemaxDevs), API consommée par les deux apps Flutter, paiements, facturation.
**C'est le contrat** : on le fait évoluer avant les apps.

## Commandes
```bash
composer install
php artisan migrate                    # migrations "centrales" (database/migrations)
php artisan serve                      # ou WAMP sur http://localhost/beninlink/web/public
php artisan test                       # obligatoire avant commit
php artisan test --filter=NomDuTest    # un seul test
vendor/bin/pint app/Chemin/Fichier.php # formatage (Pint est installé, pas de config custom)
php -l app/Chemin/Fichier.php          # lint syntaxe
```
- PHP `^8.1` (composer.json), Laravel 10, PHPUnit 10.
- **Pas de `package.json`** : `vite.config.js` est un vestige, les assets sont
  livrés compilés dans `public/`. Ne pas lancer `npm`.
- Les tests livrés sont les stubs Laravel (`tests/Unit|Feature/ExampleTest.php`) ;
  `phpunit.xml` n'active pas de base sqlite en mémoire — toute Feature test
  écrite ici tape la vraie base tant qu'on ne configure pas `DB_*` de test.
- L'app passe par un **installeur web** (`InstallerController`, middleware
  `IsInstalled`) piloté par `APP_INSTALLED` dans `.env`.

## Architecture — ce qu'il faut comprendre avant d'éditer

### Multi-tenancy : `stancl/tenancy` en façade, **colonne `company_id`** en réalité
- `config/tenancy.php` : `tenant_model` = `App\Models\Tenant`, identification par
  **domaine** (table `domains`), `central_domains` = `127.0.0.1`, `localhost`.
- **`DatabaseTenancyBootstrapper` est commenté** → une seule base. Le `Tenant`
  ne sert qu'à mapper `domaine → company_id`. `routes/tenant.php` est vide.
- Le vrai scoping se fait par **`company_id`** :
  - `settings()` (`app/Http/Helper/Helper.php:105`) résout la société courante :
    `tenant()->company_id`, sinon `Auth::user()->company_id`, sinon `1`
    (= la société « plateforme »/superadmin). Renvoie un `GeneralSettings`.
  - `scopeCompanywise()` = `where('company_id', settings()->id)`, redéfini **dans
    chaque modèle** (47 modèles sur 51 dans `app/Models/Backend/`). Tout nouveau
    modèle métier doit avoir ce scope et l'utiliser dans les requêtes.
- **Piège** : `settings()` fait une requête à chaque appel et dépend de `Auth` ;
  hors requête HTTP authentifiée (job, commande artisan, webhook) elle retombe
  sur la société `1`. Un webhook doit résoudre `company_id` depuis la transaction,
  jamais depuis `settings()`.
- `routes/superadmin.php` se charge **seulement si l'hôte n'est pas** dans la
  table `domains` : le domaine central sert le site vitrine + le panneau
  superadmin ; les sous-domaines locataires servent `routes/web.php`.
- Création d'un locataire : `Repositories/Superadmin/Company/CompanyRepository`
  (`Tenant::create(['id' => $request->domain, 'company_id' => …])`).

### Couche métier : contrat + repository, injectés dans le contrôleur
`app/Repositories/<Domaine>/<X>Interface.php` + `<X>Repository.php`, liés un par
un dans `AppServiceProvider::register()` (~70 `bind`). Les contrôleurs reçoivent
l'interface par constructeur et ne contiennent pas de logique métier.
**Toute nouvelle fonctionnalité suit ce triptyque** interface / repository / bind.

### Paiements : **il n'y a pas d'abstraction de passerelle**
C'est le point le plus important pour le chantier FedaPay.
- `app/Enums/PayoutSetup.php` = simples constantes entières (Stripe 1, SSLCommerz 2,
  PayPal 3, bKash 5, Skrill 7, Aamarpay 8, Razorpay 9, **Paystack 10**, Offline 11).
  Aucune interface `PaymentGateway`, aucune classe `Paystack` : chaque passerelle
  a **son propre contrôleur** (`AamarpayController`, `Backend/SslCommerzPaymentController`,
  `Backend/BkashController`, `Backend/SkrillController`, …) et son propre flux.
- Les clés vivent en base, par société : table `settings` (`company_id`, `key`,
  `value`), écrite par `Repositories/PayoutSetup/PayoutSetupRepository`, lue par
  `globalSettings('cle')` (`Helper.php:809`).
- Trois flux de paiement **distincts**, à ne pas confondre :
  1. **Abonnement SaaS** → `Backend/Superadmin/PlanController::subscriptionPayment`,
     Stripe Checkout **codé en dur**, tables `plans` / `subscriptions`.
     Contrôle d'accès : `subscriptionCheck()` (`Helper.php:945`, compare
     `expired_date`) via le middleware `subscriptionCheck`.
  2. **Recharge du wallet marchand** → `Backend/MerchantPanel/WalletController`
     + `Repositories/Wallet/WalletRepository`. `store()` crée une ligne `wallets`
     en `PENDING` / méthode `OFFLINE` ; **`approved($id)` est le seul endroit qui
     crédite** (`merchant->wallet_balance += amount`, puis SMS). `paymentStatus()`
     pose `transaction_id` + statut. Aucun paiement en ligne n'est branché
     aujourd'hui (l'appel à `approved()` est commenté dans `rechargeAdd`).
  3. **Encaissement marchand → client** → `Backend/MerchantPanel/OnlinePaymentController`
     (Stripe / PayPal / SSLCommerz / Aamarpay), tables `merchant_online_payments*`.
- **Conséquence pour FedaPay** : il n'y a rien à « implémenter » d'un contrat
  existant. Ajouter un contrôleur + service FedaPay dédié, et pour le crédit
  wallet **passer par `WalletRepository::approved()`** (ne pas dupliquer
  l'incrément du solde). `approved()` n'est **pas idempotent** et n'est pas
  transactionnel : le webhook doit garder son idempotence de son côté.

### Cycle de vie des colis
`app/Enums/ParcelStatus.php` (33 constantes, chaque transition ayant son pendant
`_CANCEL`). La machine à états vit dans **`parcelStatus()`** (`Helper.php:165-350`,
~185 lignes) : transitions, écritures comptables et SMS y sont mêlés. On franchit
ces états, on ne les redéfinit pas.

### API mobile
`routes/api.php`, préfixe `/api/v10`, contrôleurs `app/Http/Controllers/Api/V10/`.
Deux couches d'auth : header **`apiKey`** (`CheckApiKeyMiddleware`, comparé à
`config('rxcourier.api_key')`) sur **toutes** les routes, puis `auth:sanctum`.
Réponses normalisées par `App\Traits\ApiReturnFormatTrait`. Les apps marchand et
livreur consomment ce même préfixe — un changement de forme casse les deux.

### Configuration métier
`config/rxcourier.php` centralise types de livraison, poids/tarifs par défaut,
mapping des enums. Beaucoup de valeurs y sont encore d'origine bangladaise
(barèmes, banques dans `config/merchantpayment.php`) : à revoir avant réemploi.

## Ne jamais casser
- Le scoping `company_id` : toute requête reste tenant-aware (`companywise()`).
- Les flux des passerelles existantes : on **ajoute** FedaPay à côté.
- Les états de colis et les libellés de `ParcelStatus`.
- La forme des réponses `/api/v10` sans mettre à jour les deux apps Flutter.

## Décisions actées
- **FedaPay** = passerelle Mobile Money BJ (MTN MoMo + Moov). Le **webhook signé
  est la seule source de vérité** pour créditer/activer. Traitement **idempotent**.
- Devise **XOF** : montants **entiers**, jamais de décimales. Attention aux écrans
  hérités d'une devise à 2 décimales.
- Locale **FR** par défaut. État actuel : `config/app.php` est encore sur `en` ;
  les traductions vivent dans **`lang/`** à la racine de `web/` (Laravel 10),
  pas dans `resources/lang/` — `lang/fr/` existe et est partiel.
- Clés FedaPay « plateforme » = abonnements SaaS ; un locataire peut avoir ses
  propres clés (table `settings`) pour l'encaissement marchand.

## Chantiers (ordre)
1. Francisation + format FCFA (locale FR, devise XOF).
2. Identifiants légaux : **IFU, RCCM, CNSS** (migration + validation + factures).
3. **FedaPay** : recharge wallet et abonnement SaaS.
4. Facturation **SYSCOHADA** (mentions BJ, TVA locale, export plan comptable).
5. Reporting SaaS : **MRR, ARR, Churn, LTV, CAC** (tables `plans`/`subscriptions`).
6. Spec **OpenAPI/Swagger** sur `/api/v10` (contrat des apps Flutter).

## Conventions
- Repérer un exemple existant (repository, enum, migration) **avant** d'écrire du neuf.
- Style hérité de We Courier : syntaxe alternative `if(): … endif;`, `try/catch`
  qui renvoie `null`/`false`, helpers globaux. Rester cohérent plutôt que corriger.
- Tout module de paiement touché est couvert par des tests PHPUnit, y compris
  l'idempotence du webhook.
- Jamais de clés en dur : `FEDAPAY_*` restent dans `web/.env`.
