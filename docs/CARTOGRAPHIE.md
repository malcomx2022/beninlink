# CARTOGRAPHIE.md — relevé de l'Étape 0 (socle We Courier, dossier web/)

> Relevé des points de branchement réels du socle, à figer AVANT tout développement.
> Aucune modification de code n'est décidée ici : c'est un constat.
> Blocs A–E et I relevés sur le code (commit `9570720`). Blocs F, G, H : à compléter.
> Chemins relatifs à `web/`.

## Bloc A — Multi-tenancy
`stancl/tenancy` en façade, **colonne `company_id`** en réalité.

- Paquet / mécanisme : `stancl/tenancy`, `config/tenancy.php` — `tenant_model` =
  `App\Models\Tenant`, identification par **domaine** (table `domains`),
  `central_domains` = `127.0.0.1`, `localhost`.
- **`DatabaseTenancyBootstrapper` est commenté** → une seule base de données.
  Le `Tenant` ne sert qu'à mapper `domaine → company_id`. `routes/tenant.php` est vide.
- Modèle Company / Tenant : `App\Models\Tenant` (table `tenants`) + `domains`.
  Création d'un locataire dans `Repositories/Superadmin/Company/CompanyRepository`
  (`Tenant::create(['id' => $request->domain, 'company_id' => …])`).
- Portée du scoping : **pas de global scope**. `scopeCompanywise()` =
  `where('company_id', settings()->id)`, redéfini **dans chaque modèle**
  (47 modèles sur 51 dans `app/Models/Backend/`). Tout nouveau modèle métier doit
  porter ce scope et l'utiliser explicitement dans les requêtes.
- Résolution de la société courante : `settings()` (`app/Http/Helper/Helper.php:105`) —
  `tenant()->company_id`, sinon `Auth::user()->company_id`, sinon `1`
  (société « plateforme » / superadmin). Renvoie un `GeneralSettings`.
- **Piège** : `settings()` fait une requête à chaque appel et dépend de `Auth` ;
  hors requête HTTP authentifiée (job, commande artisan, webhook) elle retombe sur la
  société `1`. Un webhook doit résoudre `company_id` **depuis la transaction**,
  jamais depuis `settings()`.
- Routage : `routes/superadmin.php` se charge **seulement si l'hôte n'est pas** dans
  la table `domains`. Le domaine central sert le site vitrine + le panneau superadmin ;
  les sous-domaines locataires servent `routes/web.php`.

## Bloc B — Abstraction de paiement
**Constat : il n'existe aucune abstraction de passerelle.** C'est le point le plus
important pour le chantier FedaPay.

- Interface / classe de base : **aucune**. Pas d'interface `PaymentGateway`,
  pas de classe `Paystack`.
- `app/Enums/PayoutSetup.php` = simples constantes entières (Stripe 1, SSLCommerz 2,
  PayPal 3, bKash 5, Skrill 7, Aamarpay 8, Razorpay 9, **Paystack 10**, Offline 11).
- Chaque passerelle a **son propre contrôleur** et son propre flux :
  `AamarpayController`, `Backend/SslCommerzPaymentController`, `Backend/BkashController`,
  `Backend/SkrillController`, …
- Table des gateways / stockage des clés : table `settings` (`company_id`, `key`,
  `value`), écrite par `Repositories/PayoutSetup/PayoutSetupRepository`, lue par
  `globalSettings('cle')` (`Helper.php:809`). Les clés sont donc **par société**.
- **Trois flux de paiement distincts**, à ne pas confondre :
  1. **Abonnement SaaS** → voir bloc D.
  2. **Recharge du wallet marchand** → voir bloc C.
  3. **Encaissement marchand → client** → `Backend/MerchantPanel/OnlinePaymentController`
     (Stripe / PayPal / SSLCommerz / Aamarpay), tables `merchant_online_payments*`.
- **Conséquence pour FedaPay** : il n'y a rien à « implémenter » d'un contrat existant.
  Ajouter un contrôleur + service FedaPay dédié, et pour le crédit wallet **passer par
  `WalletRepository::approved()`** (ne pas dupliquer l'incrément du solde).

## Bloc C — Wallet marchand
- Service / modèle : `Backend/MerchantPanel/WalletController` +
  `Repositories/Wallet/WalletRepository`, table `wallets`.
- Création : `store()` crée une ligne `wallets` en statut `PENDING`, méthode `OFFLINE`.
- **Méthode de crédit** : `approved($id)` est le **seul endroit qui crédite**
  (`merchant->wallet_balance += amount`, puis SMS). `paymentStatus()` pose
  `transaction_id` + statut.
- **Aucun paiement en ligne n'est branché aujourd'hui** : l'appel à `approved()`
  est commenté dans `rechargeAdd`.
- **Attention** : `approved()` n'est **ni idempotent ni transactionnel**. Le webhook
  FedaPay doit porter l'idempotence de son côté.
- Retrait (payout) marchand : non relevé.

## Bloc D — Abonnement SaaS
- Modèle / service : tables `plans` / `subscriptions`.
- Activation / paiement : `Backend/Superadmin/PlanController::subscriptionPayment`,
  **Stripe Checkout codé en dur**.
- Contrôle d'accès : `subscriptionCheck()` (`Helper.php:945`, compare `expired_date`)
  via le middleware `subscriptionCheck`.
- Lien avec Company (locataire) : via `company_id` (voir bloc A).

## Bloc E — Cycle de vie des colis
- Enum / constantes : `app/Enums/ParcelStatus.php` — **33 constantes**, chaque
  transition ayant son pendant `_CANCEL`.
- Transitions : la machine à états vit dans **`parcelStatus()`**
  (`Helper.php:165-350`, ~185 lignes) — transitions, écritures comptables et SMS y
  sont **mêlés**. On franchit ces états, on ne les redéfinit pas.
- Champs clés du modèle Parcel : non relevés.

## Bloc F — Facturation / relevé
- Modèle Invoice + colonnes : non relevé.
- Calcul du net (COD − frais − TVA) : non relevé.

## Bloc G — Tarifs de livraison
- `config/rxcourier.php` centralise types de livraison, poids/tarifs par défaut et
  mapping des enums. Beaucoup de valeurs sont encore d'origine bangladaise
  (barèmes ; banques dans `config/merchantpayment.php`) : **à revoir avant réemploi**.
- Modèle tarif (poids × zone) : non relevé.

## Bloc H — Notifications
- SMS émis depuis `WalletRepository::approved()` et depuis `parcelStatus()`.
- Canaux complets (mail, SMS, push) + service : non relevés.

## Bloc I — API (contrat des apps React Native)
- Préfixe : `/api/v10` (`routes/api.php`), contrôleurs `app/Http/Controllers/Api/V10/`.
- **Deux couches d'auth** : header `apiKey` (`CheckApiKeyMiddleware`, comparé à
  `config('rxcourier.api_key')`) sur **toutes** les routes, puis `auth:sanctum`.
- Format des réponses : normalisé par `App\Traits\ApiReturnFormatTrait`.
- Les apps marchand et livreur consomment **le même préfixe** — un changement de
  forme casse les deux.

## Environnement / build (relevé au passage)
- PHP `^8.1` (`composer.json`), Laravel 10, PHPUnit 10.
- **Pas de `package.json`** : `vite.config.js` est un vestige, les assets sont livrés
  compilés dans `public/`. Ne pas lancer `npm`.
- Tests livrés = stubs Laravel (`tests/Unit|Feature/ExampleTest.php`) ; `phpunit.xml`
  n'active pas de base sqlite en mémoire — toute Feature test écrite ici tape la
  **vraie base** tant que les `DB_*` de test ne sont pas configurés.
- L'app passe par un **installeur web** (`InstallerController`, middleware
  `IsInstalled`) piloté par `APP_INSTALLED` dans `.env`.
- Locale : `config/app.php` est encore sur `en` ; les traductions vivent dans
  **`lang/`** à la racine de `web/` (Laravel 10), pas `resources/lang/`.
  `lang/fr/` existe et est partiel.
- Style hérité de We Courier : syntaxe alternative `if(): … endif;`, `try/catch`
  qui renvoie `null`/`false`, helpers globaux. Rester cohérent plutôt que corriger.

## Couche métier — convention à suivre
`app/Repositories/<Domaine>/<X>Interface.php` + `<X>Repository.php`, liés un par un
dans `AppServiceProvider::register()` (~70 `bind`). Les contrôleurs reçoivent
l'interface par constructeur et ne contiennent pas de logique métier.
**Toute nouvelle fonctionnalité suit ce triptyque** interface / repository / bind.
