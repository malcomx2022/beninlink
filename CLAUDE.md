# CLAUDE.md — Projet BeninLink (socle We Courier SAAS)

> Fichier de mémoire projet lu par Claude Code au début de chaque session.
> Garder < 200 lignes. Instructions vérifiables uniquement. Aucun secret ici.
> Dernière revue : 2026-07-06.

## Le projet en bref
- **BeninLink** : SaaS logistique B2B multi-tenant pour PME béninoises.
- **Socle** : code commercial **We Courier SAAS** (Laravel, PHP 8.x, multi-tenant
  par sous-domaine ; apps mobiles livreur/marchand en **Flutter**).
- **Principe directeur** : on **approprie** le socle, on ne le réécrit pas.
  Toute valeur ajoutée s'ajoute **par extension** (nouveau module, nouveau champ,
  nouvelle langue), jamais par modification invasive du cœur.
- **Cadre** : subvention NEXT IMPACT (Fenêtre Idéation). Taux de référence
  **1 USD = 577 FCFA**.

## Ne jamais casser
- La **logique multi-tenant** (scoping par sous-domaine) : toute requête reste
  tenant-aware. Ne jamais contourner le scope locataire.
- L'**abstraction de paiement** existante : les passerelles implémentent un
  contrat commun. On y **ajoute** FedaPay, on ne modifie pas le flux des autres.
- Le **cycle de vie des colis** : on franchit ses états, on ne les redéfinit pas.

## Décisions actées
- **Mobile Money** : **FedaPay** (agrège MTN MoMo + Moov Money Bénin).
  Le **webhook signé est la seule source de vérité** pour créditer/activer.
  Traitement **idempotent** (un webhook rejoué ne crédite qu'une fois).
- **Mobile** : on **garde Flutter** (apps fournies). Pas de réécriture React Native.
- **Devise** : **XOF (FCFA)** — montants **entiers, sans décimales**. Ne jamais
  réintroduire de centimes hérités d'une devise à 2 décimales.
- **Langue** : **français** par défaut.

## Chantiers d'appropriation (ordre recommandé)
1. Francisation + format FCFA (locale FR, devise XOF).
2. Identifiants légaux béninois : **IFU, RCCM, CNSS** (migration + formulaires).
3. **FedaPay** : brancher sur la recharge wallet et l'abonnement SaaS.
4. Facturation **SYSCOHADA** (mentions BJ, TVA locale, export plan comptable).
5. Reporting SaaS : **MRR, ARR, Churn, LTV, CAC** (lecture des tables existantes).
6. Spec **OpenAPI/Swagger** sur l'API mobile existante.

## Commandes utiles
- Installer les dépendances : `composer install`
- Migrations : `php artisan migrate`
- Tests (obligatoire avant tout commit) : `php artisan test`
- Lint PHP d'un fichier : `php -l chemin/vers/Fichier.php`

## Conventions
- Suivre les conventions **déjà présentes** dans We Courier (nommage, structure
  des services, contrôleurs). Repérer un exemple existant avant d'écrire du neuf.
- Tout module de **paiement** modifié doit être **couvert par des tests PHPUnit**,
  y compris le cas d'idempotence du webhook.
- Ne jamais committer de clés : `FEDAPAY_*` vivent dans `.env`.

## Workflow avec Claude Code
- Commencer en **plan mode** pour tout changement multi-fichiers : valider le plan
  avant exécution.
- Les règles spécifiques sont dans **`.claude/rules/`** (paiement, multi-tenant,
  SYSCOHADA, tests, i18n) — chargées selon les fichiers touchés.
- Étape 0 avant de coder : demander à Claude de **cartographier** l'archi réelle
  (paquet de tenancy, interface de passerelle, service wallet, module abonnement)
  et reporter les noms réels ici.

## Points à compléter après cartographie (Étape 0)
- [x] Interface de passerelle de paiement : `App\Library\SslCommerz\SslCommerzInterface`
  (seul contrat de passerelle formel du socle ; méthodes `makePayment`,
  `orderValidate`, `setParams`, `setRequiredInfo`, `setCustomerInfo`,
  `setShipmentInfo`, `setProductInfo`, `setAdditionalInfo`, `callToApi`).
  ⚠️ **Aucune classe Paystack** dans le socle, et **pas de contrat commun**
  couvrant toutes les passerelles : Stripe/PayPal/Skrill/SSLCommerz sont câblés
  ad hoc dans les contrôleurs (`OnlinePaymentController`, `SkrillController`,
  `SslCommerzPaymentController`…). FedaPay devra donc s'inspirer de
  `SslCommerzInterface` (Abstract + Interface + Notification) plutôt que d'un
  contrat déjà partagé.
- [x] Service de crédit wallet : `App\Repositories\Wallet\WalletRepository`
  (contrat `App\Repositories\Wallet\WalletInterface`). Crédit effectif du solde
  marchand dans `approved($id)` et `adminstore($request)`
  (`$merchant->wallet_balance += $wallet->amount`) ; `store()` crée la recharge
  en statut PENDING, `paymentStatus()` fixe transaction_id + statut.
- [x] Module d'abonnement : `App\Repositories\Superadmin\Company\CompanyRepository`
  (contrat `CompanyInterface`) + modèle `App\Models\Backend\Subscription` et
  `App\Models\Backend\Superadmin\Plan`. Activation dans `store()`, renouvellement
  dans `update()`, changement de plan dans `switchPlan()` — `expired_date` calculé
  via `plan->days_count`. Contrôleur `Superadmin\PlanController` / routes
  `routes/superadmin.php` ; garde d'accès `subscriptionCheckMiddleware`.
  (Ne pas confondre avec `App\Models\Subscribe` = newsletter frontend.)
- [x] Helper de résolution du locataire : paquet `stancl/tenancy` v3.7.
  Modèle `App\Models\Tenant` (colonne custom `company_id`, `HasDomains`),
  config `config/tenancy.php`, provider `App\Providers\TenancyServiceProvider`.
  Identification par middleware `Stancl\...\InitializeTenancyByDomain`
  + `PreventAccessFromCentralDomains` + `App\Http\Middleware\CompanyActivationMiddleware`,
  activés dans `routes/web.php` quand l'hôte figure dans la table `domains`.
  ⚠️ Le locataire est ensuite mappé sur une **entreprise** : le scoping data réel
  passe par `company_id` (scope `scopeCompanywise()` = `settings()->id`, helper
  `settings()` dans `app/Http/Helper/Helper.php`, résolu via `tenant()->company_id`).
  Le switch de base de données stancl (`DatabaseTenancyBootstrapper`, `routes/tenant.php`)
  est **désactivé/commenté** : mono-base, cloisonnement par `company_id`.
