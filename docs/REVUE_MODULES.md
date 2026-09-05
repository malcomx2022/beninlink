# Revue des modules — BeninLink (backend `web/` + apps mobiles)

> Inventaire de **tous les modules du projet**, côté backend (`web/`, Laravel / socle
> We Courier) et côté front (`mobile/` marchand, `mobile-livreur/` livreur, back-office
> web Blade). Relevé sur le code réel, pas sur les intentions.
> Sources : `web/routes/*.php`, `web/app/**`, `mobile/app/**`, `mobile/src/**`,
> `web/CARTOGRAPHIE.md`, `docs/REVUE_FONCTIONNELLE_MOBILE.md`, `maquettes/*.html`.
> Établie le 2026-09-04.

## 0. Vue d'ensemble

| Composante | Techno | Rôle | État |
|---|---|---|---|
| `web/` | Laravel 10 · PHP 8.2 | Backend We Courier + modules BeninLink. **Le contrat.** | Actif — chantiers 1 à 7 livrés |
| `web/` (back-office Blade) | Blade + JS compilé dans `public/` | Panneaux Admin, Marchand, Hub, Super-admin, site vitrine | Actif (socle) |
| `mobile/` | React Native · Expo 57 · expo-router · TypeScript | App **marchand** (PME) | Actif — les 15 écrans de la maquette codés |
| `mobile-livreur/` | React Native · Expo 57 · expo-router · TypeScript | App **livreur** | Actif — v1 et v2 livrées le 2026-09-05 (7 écrans, fenêtre Création) |
| `courier_merchant_saas-main/` · `courier_delivery_saas-main/` | Flutter | Apps d'origine We Courier | **Dépréciées**, référence seulement |

Volumétrie `web/` : 22 contrôleurs API, 89 contrôleurs back-office, 52 domaines de
repositories, 78 modèles, 87 migrations, 37 seeders, 96 routes API, 625 routes web
tenant, 136 routes super-admin, 81 fichiers `lang/fr/`.

Volumétrie `mobile/` : 19 fichiers d'écran (expo-router), 12 modules d'API, 3 modules
de domaine, environ 5 400 lignes TypeScript (au 2026-09-04).

---

## 1. Backend `web/` — socle transverse

| Module | Où | Notes |
|---|---|---|
| **Multi-tenancy** | `stancl/tenancy` en façade, `Tenant`, `CustomerDomain`, `config/tenancy.php`, `scopeCompanywise()` sur 47/51 modèles | Une seule base, isolation par `company_id`. Aucune tenancy sur `/api/v10` (scoping par `Auth::user()->company_id`). |
| **Authentification web** | `Http/Controllers/Auth/*` (login, register, reset, verify), `SocialLoginController` (Google, Facebook) | Laravel UI + Socialite |
| **Authentification API** | `Api/V10/AuthController`, Sanctum, `CheckApiKeyMiddleware` | Inscription, OTP SMS, reset, refresh. Jetons portant l'ability de leur type (`merchant` / `deliveryman`) et routes cloisonnées par `UserTypeMiddleware` (S5 corrigé le 2026-09-04). |
| **Rôles et permissions** | `RoleController`, `Permission`, `SuperAdminPermission`, middleware `hasPermission` | Permissions par clé (`parcel_read`…). |
| **Abonnement SaaS** | `subscriptionCheck()`, middleware `subscriptionCheck`, `Subscription`, `Plan` | Paiement Stripe (S1 corrigé) **ou FedaPay** (`POST /subscription/fedapay`, activation par webhook, depuis le 2026-09-04). |
| **Localisation** | `LocalizationController`, `LanguageManager`, `lang/{fr,en,ar,bn,es,in,zh}` | FR par défaut, `lang/fr/` complet (chantier 1). L'API ne négocie pas la locale. |
| **Devise / montants** | `Helper.php` : `formatAmount()`, `amountValue()`, `currencySymbol()`, `formatRate()` | XOF entier (chantier 1). API sérialise en `number`. |
| **Installeur** | `InstallerController`, `IsInstalledMiddleware` | Installation We Courier + `PurchaseVerify` (licence Envato). |
| **Addons** | `AddonController`, `Addon` | Activation de modules We Courier. |
| **Journal d'activité** | `ActiveLogController`, `spatie/laravel-activitylog` | |
| **Sauvegarde BDD** | `DatabaseBackupController`, commande `database:autobackup` (quotidienne) | |
| **Éditeur `.env`** | `geo-sot/laravel-env-editor`, `setEnv()` | Sensible : écrit `.env` depuis l'admin. |
| **Sécurité HTTP** | `XSS`, `Cors`, `ModifyHeaderMiddleware`, `TrustProxies` | |
| **Planificateur** | `Console/Kernel` : `database:autobackup` daily, `invoice:generate` daily 13:00 | |
| **Notifications sortantes** | `SmsService` (Twilio, Vonage, REVE), `PushNotificationService` (FCM **legacy, arrêté**), `Mail/` (4 Mailables) | Envoi synchrone, non journalisé. Push à réécrire en HTTP v1. |
| **Exports / imports** | `Exports/` (7 : rapports, factures, colis), `Imports/ParcelImport`, `Services/Invoicing/*` | `maatwebsite/excel` (CSV/XLSX) ; **PDF** des relevés par `dompdf` et journal SYSCOHADA (CSV) depuis le 2026-09-04. |
| **Codes-barres** | `milon/barcode` | Étiquettes colis. |

---

## 2. Backend `web/` — panneau Admin (société locataire, préfixe `admin/`)

Routes dans `routes/web.php` l.205-822, contrôleurs `Backend/*`, vues
`resources/views/backend/*`.

| Domaine | Module | Contrôleur(s) | Repository |
|---|---|---|---|
| **Colis** | Colis (CRUD, filtres, import, recherche, carte) | `ParcelController`, `MapParcelController`, `ParcelQuoteController` | `Parcel` |
| | Statuts colis (33 transitions + annulations) | `ParcelController` (bloc `parcel status`) | `Parcel` |
| | Demandes de ramassage | `PickupRequestController` | — (`PickupRequest`) |
| | Liquide / fragile | `LiquidFragileController` | — |
| | Emballages | `PackagingController` | `Packaging` |
| | **Douane** (BeninLink, chantier 5) | `Backend/CustomsController` (alertes, règles) | `Services/Customs/CustomsService` |
| **Tarification** | Barème poids × zone | `DeliveryChargeController` | `DeliveryCharge` |
| | Catégories de livraison | `DeliverycategoryController` | `DeliveryCategory` |
| | Types de livraison | `DeliveryTypeController` | `DeliveryType` |
| | Tarif spécifique marchand | `MerchantDeliveryChargeController` | `MerchantDeliveryCharge` |
| | **Calcul serveur des montants** (BeninLink, S2) | `Services/Parcel/ChargeCalculator` | — |
| **Marchands** | Marchands (CRUD, inscription, OTP) | `MerchantController`, `MerchantProfileController` | `Merchant`, `MerchantProfile`, `MerchantManage` |
| | Boutiques marchand | `MerchantShopsController` | `MerchantShops` |
| | Comptes de paiement marchand | `MerchantPaymentAccountController` | `MerchantPayment` |
| | Paiements marchand (règlement COD) | `MerchantmanagePaymentController` | `MerchantPayment` |
| | Factures marchand = relevés de règlement (PDF, CSV, journal SYSCOHADA) | `MerchantInvoiceController`, commande `invoice:generate`, `Services/Invoicing/{InvoiceNumbering,SettlementStatement,SyscohadaJournal}` | `Invoice` |
| | Demandes de wallet (recharge manuelle) | `Backend/MerchantPanel/WalletController` (routes admin) | `Wallet` |
| | **Identité légale IFU / RCCM / CNSS** (BeninLink, chantier 2) | formulaires marchand + `general_settings`, `Rules/LegalIdentifier` | — |
| **Livreurs** | Livreurs (CRUD) | `DeliveryManController` | `DeliveryMan` |
| **Hubs** | Hubs, responsables de hub | `HubController`, `HubInChargeController` | `Hub`, `HubInCharge`, `HubManage` |
| | Paiements hub | `HubPaymentController` | `HubPaymentRequest` |
| **Comptabilité** | Comptes bancaires / caisse | `AccountController`, `BankTransactionController` | `Account`, `BankTransaction` |
| | Chapitres comptables | `AccountHeadsController` | `AccountHeads` |
| | Revenus / dépenses | `IncomeController`, `ExpenseController` | `Income`, `Expense` |
| | Transferts de fonds | `FundTransferController` | `FundTransfer` |
| **RH / paie** | Utilisateurs (staff) | `UserController`, `ProfileController` | `User`, `Profile` |
| | Départements, désignations | `DepartmentController`, `DesignationController` | `Department`, `Designation` |
| | Salaires et génération de paie | `SalaryController`, `SalaryGenerateController` | `Salary` |
| **Actifs** | Actifs et catégories d'actifs | `AssetController`, `AssetcategoryController` | `Asset`, `AssetCategory` |
| **Relation** | Support (tickets + chat) | `SupportController` | `Support` |
| | Fraude (liste noire de clients) | `FraudController` | `Fraud` |
| | Actualités et offres | `NewsOfferController` | `NewsOffer` |
| | To-do | `TodoController` | `Todo` |
| | Notifications web | `WebNotificationController` | — |
| | Push (rédaction manuelle) | `PushNotificationController` | `PushNotification` |
| **Rapports** | Rapports (colis, marchand, livreur, hub, profit, TVA) | `ReportsController`, `TotalSummeryReportController` | `Reports` |
| **Paramètres** | Généraux, devise, Google Maps, notifications, SMS, social login | `GeneralSettingsController`, `CurrencyController`, `GoogleMapSettingsController`, `NotificationSettingsController`, `SmsSettingsController`, `SmsSendSettingsController`, `SocialLoginController` | idem |
| | Catégories génériques | `CategoryController` | — |
| **Paiement en ligne (payout)** | Stripe, PayPal, SSLCommerz, Skrill, Aamarpay, bKash | `AdminSkrillController`, `AdminSslCommerzController`, `AdminAamarpayController`, `AdminBkashController`, `PayoutController` | — (un contrôleur par passerelle, sans interface commune) |
| | Configuration payout | `PayoutSetupController` | `PayoutSetup` |
| **CMS site vitrine** | Sections, services, « pourquoi nous », FAQ, partenaires, blogs, pages, liens sociaux | `Backend/FrontWeb/*` (8) | `FrontWeb` |

---

## 3. Backend `web/` — panneau Marchand (préfixe `merchant/`)

Routes `routes/web.php` l.825-953, contrôleurs `Backend/MerchantPanel/*` (17).

| Module | Contrôleur |
|---|---|
| Comptes et transactions | `PaymentAccountController`, `AccountTransactionController` |
| Relevés (statements) | `StatementsController` |
| Paramètres et profil marchand | `SettingsController`, `MerchantProfileController` |
| Boutiques | `ShopsController` |
| Colis (CRUD, import, statut) | `MerchantParcelController` |
| Demandes de règlement (payout) | `PaymentRequestController` |
| Actualités et offres | `NewsOfferController` |
| Support | `SupportController` |
| Fraude | `FraudController` |
| Rapports | `MerchantReportsController`, `ReportsController` |
| Demandes de ramassage | `PickupRequestController` |
| Réception de paiements en ligne (Stripe, PayPal, SSLCommerz) | `MerchantOnlinePaymentSetupController`, `OnlinePaymentController` |
| Factures | `InvoiceController` |
| **Mon wallet et recharge** | `WalletController` (`rechargeStatus` inexistant — route morte) |

## 4. Backend `web/` — panneau Hub

| Module | Contrôleur |
|---|---|
| Demandes de paiement du hub | `HubPanel/HubPaymentRequestController` |
| Encaissements reçus des livreurs | `HubPanel/ReceivedFromDeliverymanController` (`CashReceivedFromDeliveryman`) |

## 5. Backend `web/` — Super-admin (`routes/superadmin.php`)

| Module | Contrôleur |
|---|---|
| Plans SaaS et modules par plan | `Superadmin/PlanController` |
| **Reporting SaaS** (BeninLink, chantier 6) : MRR, ARR, churn, LTV, CAC | `Superadmin/ReportingController`, `Services/Reporting/SaasMetrics`, `config/saas_reporting.php` |
| Sociétés locataires (CRUD, bascule d'abonnement, inscription + OTP) | `Superadmin/CompanyController` |
| Historique d'abonnement, paiement Stripe | `PlanController::subscriptionPayment / StripePaymentSuccess` |
| Support, rôles, désignations (mêmes contrôleurs que l'admin) | — |

## 6. Backend `web/` — site vitrine public

`Frontend/FrontendController` : accueil, suivi de colis, à propos, confidentialité,
CGU, FAQ, blogs, services, contact, abonnement newsletter. Contenu piloté par le
CMS `FrontWeb`.

---

## 7. Backend `web/` — API `/api/v10` (contrat des apps mobiles)

Toutes les routes portent le header `apiKey` puis `auth:sanctum`, sauf le bloc public.
Depuis S5 (2026-09-04), les routes marchand exigent `userType:merchant` et les routes
`deliveryman/*` `userType:deliveryman` (403 sinon) ; seules **Auth**, **Profil** et
**Push** restent communes aux deux types de compte.

| Bloc | Endpoints | Contrôleur | Consommé par |
|---|---|---|---|
| **Auth** | `register`, `signin`, `deliveryman/login`, `otp-verification`, `resend-otp`, `password/email`, `password/reset`, `refresh`, `sign-out`, `update-password` | `AuthController` | marchand + livreur |
| **Référentiels** | `hub`, `general-settings`, `all-currencies`, `settings/cod-charges`, `settings/delivery-charges` | `HubController`, `GeneralSettingCotroller`, `SettingsController` | marchand |
| **Tableau de bord** | `dashboard`, `dashboard/filter`, `dashboard/balance-details`, `dashboard/available-parcels`, `analytics` | `DashboardController`, `AnalyticsController` | marchand |
| **Profil** | `profile`, `profile/update` | `AuthController` | marchand |
| **Boutiques** | `shops/*` (index, store, edit, update, delete) | `ShopsController` | marchand |
| **Colis** | `parcel/*` (index, create, store, **quote**, details, edit, update, logs, filter, status, delete, all/status), `status-wise/parcel/list/{status}` | `ParcelController` | marchand |
| **Douane** (BeninLink) | `customs/reference`, `customs/alerts`, `customs/alerts/{id}/resolve` | `CustomsController` | marchand |
| **FedaPay** (BeninLink) | `fedapay/initiate`, `fedapay/status/{reference}` (+ webhook et callback publics dans `web.php`) | `Payment/FedaPayController` | marchand |
| **Wallet** (BeninLink, 2026-09-04) | `wallet/history` (mouvements du porte-monnaie prépayé, paginés par 10) | `WalletController` | marchand |
| **Notifications** (BeninLink, 2026-09-04) | `notifications/index`, `notifications/unread-count`, `notifications/{id}/read`, `notifications/read-all` | `NotificationController` | marchand |
| **Argent** | `payment-accounts/*`, `account-transaction/*`, `statements/*`, `payment-request/*`, `invoice-list/index`, `invoice-details/{id}`, `invoice-pdf-link/{id}` (lien signé, chantier 4), `statement-reports` | `PaymentAccountController`, `AccountTransactionController`, `StatementsController`, `PaymentRequestController`, `InvoiceController`, `ReportController` | marchand |
| **Relation** | `fraud/*` (+ `fraud/check`), `news-offer/index`, `support/*` | `FraudController`, `NewsOfferController`, `SupportController` | marchand |
| **Push** | `fcm-subscribe`, `fcm-unsubscribe` | `PushNotificationController` | marchand + livreur (hors service : API FCM legacy arrêtée ; topic par compte authentifié depuis S11) |
| **Livreur** | `deliveryman/parcel/*` (index, details, delivered, partial-delivered), `deliveryman/income-expense`, `deliveryman/dashboard`, `deliveryman/profile`, `deliveryman/payment-logs`, `deliveryman/parcel-payment-logs`, `deliveryman/parcel-status`, `deliveryman/parcel-status-update`, `deliveryman/parcel-location-update` | `DeliveryManParcelController`, `DeliveryManIncomeExpenseController`, `DeliverymanController` | **livreur** (aucune app RN ne les consomme encore) |
| **Public** | `parcel/tracking/{tracking_id}`, `contact-us`, `subscribe`, `customer/installation` | `ParcelController`, `InstallerController` | site / app |
| **Contrat** (BeninLink, 2026-09-04) | `openapi.json` (spec OpenAPI 3.0.3 générée depuis le routeur ; Swagger UI sur `/api/docs`) | `OpenApiController` | apps + intégrateurs |

---

## 8. Modules BeninLink ajoutés au socle (état des chantiers `web/`)

| # | Chantier | État | Où dans le code |
|---|---|---|---|
| 1 | Francisation + FCFA | ✅ livré | `lang/fr/` (81 fichiers), `fr.json`, helpers `formatAmount()`… |
| 2 | IFU / RCCM / CNSS | ✅ livré | migration `2026_08_17`, `Rules/LegalIdentifier`, 5 formulaires |
| 3 | FedaPay (recharge wallet **et abonnement SaaS**) | ✅ livré (abonnement le 2026-09-04) | `Services/Payments/FedaPayGateway`, `Payment/FedaPayController`, `FedaPayTransaction`, `config/fedapay.php`, migrations `2026_08_18` et `2026_09_04`, tests `FedaPayWebhookTest`, `FedaPaySubscriptionTest` |
| S2 | Calcul serveur des montants + devis | ✅ livré | `Services/Parcel/ChargeCalculator`, `POST parcel/quote`, `ParcelQuoteTest` |
| 4 | Facturation SYSCOHADA + relevés PDF | ✅ livré le 2026-09-04 (TVA au niveau entreprise non tranchée) | `Services/Invoicing/*`, `config/syscohada.php`, `statement_pdf.blade.php`, migration `2026_09_04_120000`, test `SettlementStatementTest` |
| 5 | Alertes douanières UEMOA / CEDEAO | ✅ livré (notification au fil marchand par `CustomsAlertFeedObserver` depuis le 2026-09-04) | `Services/Customs/CustomsService`, `Observers/ParcelCustomsObserver`, `Rules/CustomsAllowed`, `CustomsRule`, `CustomsAlert`, `CustomsRuleSeeder`, migration `2026_08_19`, `CustomsAlertTest` |
| 6 | Reporting SaaS (MRR, ARR, Churn, LTV, CAC) | ✅ livré le 2026-09-04 | `Services/Reporting/SaasMetrics`, `config/saas_reporting.php`, page `super-admin/reporting`, test `SaasMetricsTest` |
| 7 | OpenAPI / Swagger | ✅ livré le 2026-09-04 (générée depuis le routeur, sans package) | `Services/OpenApi/SpecGenerator`, `resources/openapi/overlay.php`, `config/openapi.php`, commande `openapi:generate`, `public/openapi/v10.json`, `GET /api/v10/openapi.json`, `GET /api/docs`, test `OpenApiSpecTest` |

**Tests** (`web/tests/Feature`) : `FedaPayWebhookTest`, `CustomsAlertTest`,
`InvoiceScopeTest` (S14), `ParcelQuoteTest` (S2), `ParcelScopeTest` (S17), trait
`Concerns/SeedsTenant`. Base SQLite en mémoire.

---

## 9. Front `mobile/` — app marchand (React Native / Expo)

### 9.1 Écrans (expo-router, `mobile/app/`)

| Groupe | Écran | Fichier | Écran maquette | Endpoints |
|---|---|---|---|---|
| Auth | Connexion | `(auth)/login.tsx` | `login` | `signin` |
| | Inscription PME (IFU / RCCM / CNSS) | `(auth)/signup.tsx` | `signup` | `register` |
| | Vérification OTP | `(auth)/verify-otp.tsx` | `login` (étape) | `otp-verification`, `resend-otp` |
| | Mot de passe oublié | `(auth)/forgot-password.tsx` | `forgot` | `password/email` |
| | Nouveau mot de passe (jeton du lien, lien profond `beninlink://reset-password`) | `(auth)/reset-password.tsx` | `forgot` (étape 2) | `password/reset` |
| App | Tableau de bord | `(app)/index.tsx` | `home` | `dashboard`, `balance-details` (lien vers l'écran douane) |
| | Colis (liste par statut) | `(app)/parcels.tsx` | `parcels` | `parcel/index`, `parcel/all/status` (filtrage local, pas `parcel/filter`) |
| | Détail colis + timeline | `(app)/parcel/[id].tsx` | `parcel-detail` | `parcel/details`, `parcel/logs` |
| | Nouveau colis (devis serveur, export douane) | `(app)/parcel/new.tsx` | `new-parcel` | `parcel/create`, `parcel/quote`, `parcel/store`, `customs/reference` |
| | Portefeuille : solde, recharge FedaPay (navigateur), historique | `(app)/wallet.tsx` | `wallet` + `recharge` | `profile`, `fedapay/initiate`, `fedapay/status`, `wallet/history` |
| | Retrait du net à reverser (comptes Mobile Money, demandes et statuts) | `(app)/wallet/withdraw.tsx` | `wallet` (retrait) | `payment-accounts/index`, `payment-account/store`, `payment-request/index`, `payment-request/store` |
| | Alertes douanières | `(app)/customs.tsx` | `customs` | `customs/alerts`, `customs/alerts/{id}/resolve` |
| | Factures = relevés de règlement (+ PDF par lien signé) | `(app)/invoices.tsx` | `invoices` | `invoice-list/index`, `invoice-details`, `balance-details`, `invoice-pdf-link` |
| | Tarifs (poids × zone, COD) | `(app)/rates.tsx` | `rates` | `settings/delivery-charges`, `settings/cod-charges` |
| | Boutiques (liste) | `(app)/shops.tsx` | `shops` | `shops/index` |
| | Boutique : création, modification, suppression | `(app)/shop/[id].tsx` | `shops` | `shops/edit`, `shops/store`, `shops/update`, `shops/delete` |
| | Profil (lecture + déconnexion) | `(app)/profile.tsx` | `profile` | `profile`, `sign-out` |
| | Mes informations | `(app)/profile/edit.tsx` | `profile` | `profile/update` |
| | Changer le mot de passe | `(app)/profile/password.tsx` | `profile` | `update-password` |
| | Notifications (fil, marquage lu, ouverture du colis) | `(app)/notifications.tsx` | `notifications` | `notifications/*` |

Bilan : **les 15 écrans** de la maquette existent, tous complets depuis le
2026-09-04 (wallet avec historique et retrait, boutiques modifiables, profil
modifiable, réinitialisation du mot de passe en deux étapes, fil de
notifications servi par `web/`).

Chaînes **natives** (hors JavaScript) : `app.json` déclare `"locales": { "fr":
"./src/i18n/expo-fr.json" }`. Ce fichier fournit le nom d'app et la demande Face ID en
français (`fr.lproj/InfoPlist.strings` sur iOS, `values-b+fr/strings.xml` sur Android).
Il manquait jusqu'au 2026-09-04 : Expo se contentait d'un avertissement au `prebuild`
et iOS gardait la demande Face ID anglaise du plugin `expo-secure-store`.

### 9.2 Modules techniques (`mobile/src/`)

| Module | Fichiers | Rôle |
|---|---|---|
| Client d'API | `api/client.ts`, `api/config.ts`, `api/session.ts` | `apiKey` + Bearer Sanctum, URL en `EXPO_PUBLIC_API_URL`, jeton en `expo-secure-store` |
| Inventaire d'endpoints | `api/endpoints.ts` | Liste des routes réellement servies + bloc `MISSING` |
| Services d'API | `api/auth.ts`, `api/merchant.ts`, `api/parcels.ts`, `api/shops.ts`, `api/wallet.ts`, `api/fedapay.ts`, `api/customs.ts`, `api/notifications.ts`, `api/types.ts` | Un module par domaine |
| Session | `session/SessionProvider.tsx` | Contexte utilisateur, garde des groupes `(auth)` / `(app)` |
| Domaine | `domain/money.ts`, `domain/parcelStatus.ts`, `domain/deliveryType.ts` | FCFA entier, table 33 statuts backend → 7 statuts affichés, types de livraison |
| i18n | `i18n/fr.ts`, `i18n/index.ts`, `i18n/expo-fr.json` | FR seul ; `expo-fr.json` = chaînes natives (`expo.locales`) |
| Thème | `theme/colors.ts`, `theme/typography.ts` | Vert `#12503A`, Ocre `#E0A63C`, Sora + DM Sans |
| UI | `components/ui.tsx` | Composants partagés (boutons, cartes, champs) |

---

## 10. Front `mobile-livreur/` — app livreur

**v1 livrée le 2026-09-05** (fenêtre Création ouverte à la demande du porteur, hors
ligne 11). Mêmes fondations que `mobile/` : charte, briques, client d'API, argent et
statuts repris à l'identique ; environ 1 100 lignes TypeScript.

| Écran maquette | Fichier | Endpoints `/api/v10` | État |
|---|---|---|---|
| `login` | `(auth)/login.tsx` | `deliveryman/login`, `sign-out` | ✅ (`driver_id`) |
| `parcels` (En cours / Retours / Livrés) | `(app)/(tabs)/index.tsx` | `deliveryman/dashboard` | ✅ Appeler / Itinéraire depuis la liste |
| `detail` | `(app)/parcel/[id]/index.tsx` | `deliveryman/parcel/details/{id}` | ✅ marchand, colis, destinataire, historique |
| `status` (Livré / partielle / Retour + montant) | `(app)/parcel/[id]/status.tsx` | `deliveryman/parcel-status-update` | ✅ (montant obligatoire pour le partiel) |
| `earnings` | `(app)/(tabs)/earnings.tsx` | `deliveryman/profile`, `income-expense`, `parcel-payment-logs` | ✅ |
| `profile` | `(app)/(tabs)/profile.tsx` | `deliveryman/profile` | ✅ stats, soldes, tarifs, déconnexion |

**v2 le même jour** : position GPS à la demande et après chaque livraison
(`parcel-location-update`, `expo-location`), photo de livraison facultative en multipart
(`parcel/delivered/{id}`, `expo-image-picker`), écran de changement de mot de passe
(`update-password`), icônes Ionicons. Un écart de contrat relevé au passage et corrigé
dans la spec : `parcel-status-update` lit **`status_action`**, pas `status`.

**v3 — signature manuscrite** : le socle We Courier attendait déjà `signatureImage` sur
`parcel/delivered/{id}` (l'app Flutter d'origine l'envoyait via un pad de signature)
mais ne la montrait qu'à l'administrateur. Livré : canevas de signature dans l'app
livreur (`src/components/SignaturePad.tsx`, `react-native-svg` + `react-native-view-shot`,
facultatif comme la photo), `ParcelLogsResource` expose `delivered_image` /
`signature_image` en URL absolues, et l'app marchand les affiche dans le suivi du colis.
Au passage, le type `ParcelEvent` de `mobile/` est aligné sur la ressource réelle
(`parcel_status_name`, `date`, `time_date`) : la timeline marchande lisait des champs
inexistants. Visuels propres à l'app livreur livrés dans la foulée (icône, adaptive icon
Android + monochrome, splash, favicon), générés depuis `mobile-livreur/assets/source/generate.py`.
L'app marchand `mobile/` garde encore les visuels du gabarit Expo.

## 10 bis. Revue FedaPay et workflows (2026-09-05)

Une revue transverse du socle FedaPay, de sa configuration côté administration et
des neuf workflows métier vit dans **`docs/REVUE_FEDAPAY_ET_WORKFLOWS.md`**. Elle
n'a modifié aucun code. Deux constats y appellent une décision rapide :

- ~~**W1 (critique)**~~ ✅ **corrigé le 2026-09-05** : un marchand posait lui-même
  le statut « Livré » par `GET /api/v10/parcel/{id}/status/9`, sans contrôle de
  transition, et le colis entrait au relevé de règlement en sa faveur. Liste
  blanche vide dans `MerchantParcelRepository`, 404 / 422 distingués, 5 tests.
- ~~**F1 (grave)**~~ ✅ **corrigé le 2026-09-05** : l'écran d'approbation des
  recharges créditait une seconde fois une recharge FedaPay, dans les deux ordres.
  `WalletRepository::approved()` n'approuve plus qu'une ligne `PENDING`, sous
  verrou de ligne, 7 tests.

- ~~**W2**~~ ✅ **corrigé le 2026-09-05** : la position du livreur n'écrit plus
  que sur ses courses en main ; l'historique des livraisons closes est préservé.
- ~~**W4**~~ ✅ **corrigé le 2026-09-05** : la liste des relevés de l'app tombait
  en 500 dès le premier relevé émis (`InvoiceResource` sommait deux propriétés
  inexistantes) et ne filtrait que les payés. Elle sert désormais le net porté par
  le relevé, tous statuts, comme le panneau web.
- ~~**W5**~~ ✅ **corrigé le 2026-09-05** : le portefeuille n'était **jamais**
  débité pour un colis créé depuis l'app (le code lisait `$request->merchant_id`,
  absent de cette requête), et le `catch` vide garantissait le silence.

- ~~**F2**~~ et ~~**F3**~~ ✅ **corrigés le 2026-09-05** : FedaPay a sa carte dans
  Réglages → Pay-out (environnement, compte, clé, secret de webhook, interrupteur),
  et un locataire peut enfin encaisser sur son propre compte. Le secret de
  signature suit le compte qui encaisse, sans quoi brancher une clé par locataire
  aurait fait rejeter tous ses webhooks. Découvert au passage : `Setting` ne
  déclarait pas `company_id` assignable, donc tout réglage de passerelle
  enregistré depuis l'administration partait avec un locataire nul.

- ~~**W3**~~ et ~~**F4**~~ ✅ **corrigés le 2026-09-05** : la requête de création
  côté administration porte enfin les règles douanières, comme le prescrivait le
  commentaire de `CustomsAllowed` ; et le SMS de confirmation de recharge part au
  nom, à la devise et par l'opérateur SMS de la société du portefeuille, au lieu
  de ceux de la première société.

- ~~**F5**~~ et ~~**F6**~~ ✅ **corrigés le 2026-09-05** : les issues du webhook de
  recharge autres que le crédit sont couvertes ; et le filet d'isolation, qui
  déclarait une route couverte par un test sans base migrée, porte désormais une
  preuve réelle **et** vérifie que chaque test déclaré peut atteindre une route.

**Plus aucun constat de la revue n'est ouvert**, et les **trois décisions**
restantes ont été tranchées le 2026-09-05 (§13 de
`docs/REVUE_FEDAPAY_ET_WORKFLOWS.md`) :

- **Lignes `settings` sans société : on ne rattache pas.** Le seeder visait la
  société 1 et l'écran de réglages visait la société connectée, sans qu'on puisse
  les distinguer : les attribuer à la société 1 donnerait la clé d'un locataire à
  un autre. `php artisan beninlink:reglages-orphelins` les constate, sans afficher
  aucun secret, et ne supprime qu'avec `--purge`.
- **Débit du portefeuille : atomique avec la création du colis.** Un colis qu'on
  ne sait pas facturer ne doit pas partir. Sûr à annuler, tout ce que la création
  déclenche s'écrivant en base.
- **Les deux voisins non scopés sont fermés** : le statut de colis côté
  administration lit désormais `companywise()`, et les trois actions
  d'administration sur un portefeuille portent un garde au contrôleur — le
  service restant sans scope pour le webhook, qui n'a pas de session.

La dernière question ouverte — **aucun contrôle de solde** à la création d'un
colis — a été tranchée le 2026-09-05 (§14 de
`docs/REVUE_FEDAPAY_ET_WORKFLOWS.md`, décision **D6** de
`docs/DECISIONS_METIER.md`) : **pas de découvert**. La règle existait déjà dans
le socle, mais dans deux écrans sur cinq ; l'API mobile et les deux duplications
créaient le colis et laissaient le solde descendre sans plancher. Le contrôle est
descendu là où le débit a lieu — `Services\Parcel\WalletDebit`, qui remplace
quatre copies du même bloc — sous verrou de ligne, et le refus arrive à l'app en
422 avec ce qui manque, de quoi proposer directement la bonne recharge.

Découverts en descendant : la duplication depuis le panneau marchand ne
fonctionnait pas du tout (elle écrivait une colonne `parcel_bank` que
`parcel_logs` n'a jamais eue, créait le colis et rendait une erreur), et son
journal lisait le marchand de la requête au lieu de celui du colis — la mécanique
exacte de W5.

**L'import Excel en masse** (`ParcelImport`, back-office et panneau marchand)
créait les colis sans jamais toucher le portefeuille — ni contrôle, ni débit.
Ses trois questions ont été tranchées le 2026-09-05 (§15, décision **D7**) :
**il débite** comme la saisie, un solde insuffisant refuse **tout le fichier**
(le socle faisait déjà ainsi pour les erreurs de validation), et le passé se
constate avec `php artisan beninlink:colis-non-debites` avant de se régulariser
compte par compte.

Quatre défauts découverts en ouvrant le fichier : les colis importés n'avaient
pas de `company_id` et restaient invisibles à tous les écrans du back-office ;
le marchand venait de la colonne `merchant_id` du fichier sans aucun contrôle —
inoffensif tant que rien n'était facturé, vidangeur de portefeuille une fois le
débit branché ; l'import avait son propre barème au lieu du `ChargeCalculator`
imposé par S2 ; et une colonne facultative en moins tuait l'import.

**Les étapes comptables sont couvertes** depuis le 2026-09-05 (§16) : la
livraison, la livraison partielle, les retraits et les relevés de règlement,
**42 tests**. Les montants étaient justes ; c'est le comportement hors chemin
nominal qui ne l'était pas. Les mêmes trois défauts aux quatre étapes — elles
se rejouaient sans broncher (livrer deux fois doublait **tous** les comptes,
et l'app livreur renvoie sur réseau instable), elles n'étaient pas scopées à la
société, elles n'étaient pas transactionnelles. La règle générale qui s'en
dégage est consignée en **D8** de `docs/DECISIONS_METIER.md`.

## 11. Apps Flutter dépréciées (référence)

| App | Modules (`lib/Screen/`) |
|---|---|
| `courier_merchant_saas-main` | Authentication, Home, Parcel, Shops, Payment (+ Statement), Frauds, Support, Profile, SplashScreen |
| `courier_delivery_saas-main` | Authentication, Home, Payment, Profile, SplashScreen |

Elles documentent la forme des réponses d'API (`lib/Models/`) et la liste des endpoints
consommés (`services/api-list.dart`). Ne rien y coder.

---

## 12. Matrice front ↔ backend (chantiers transverses)

| Chantier | `web/` | `mobile/` | `mobile-livreur/` | Back-office Blade |
|---|---|---|---|---|
| Francisation + FCFA | ✅ | ✅ (FR seul, entiers) | — | ✅ (`lang/fr`) |
| IFU / RCCM / CNSS | ✅ | ✅ (inscription) | — | ✅ (5 formulaires) |
| FedaPay recharge wallet | ✅ | ✅ (navigateur système, historique) | — | ✅ bouton Mobile Money sur `my-wallet/recharge` (2026-09-05), flux manuel conservé à côté |
| Retrait (payout) marchand | ✅ (propriété du compte vérifiée) | ✅ | — | ✅ |
| FedaPay abonnement SaaS | ✅ | — | — | ✅ bouton sur la page des plans |
| Calcul serveur + devis | ✅ | ✅ | — | ✅ (affiche le devis) |
| SYSCOHADA / relevés PDF | ✅ | ✅ relevé natif + PDF | — | ✅ PDF, CSV, journal SYSCOHADA |
| Alertes douanières | ✅ | ✅ | — | ✅ (alertes + règles) |
| Reporting SaaS (MRR…) | ✅ | — | — | ✅ page super-admin |
| OpenAPI / Swagger | ✅ (`openapi.json`, `/api/docs`) | ✅ `endpoints.ts` vérifié contre la spec par test | — | — |
| Suivi / statuts colis | ✅ | ✅ (timeline) | ✅ (livré / partiel / retour) | ✅ |
| Notifications | ✅ fil marchand (`notifications` Laravel + 6 observers) · SMS ok · push FCM hors service · mail sync | ✅ écran + compteur | — | ✅ |

---

## 13. Constats à retenir pour la suite

1. ~~Un chantier `web/` reste à ouvrir~~ — ✅ **les sept chantiers `web/` sont livrés**
   (4, 6 et 7 le 2026-09-04). Les décisions métier sont consignées dans
   `docs/DECISIONS_METIER.md` : ~~TVA au niveau entreprise~~ ✅ (taux société 18 %,
   surcharge par marchand, 2026-09-05), ~~dépenses d'acquisition~~ ✅ (chapitre
   « Marketing et acquisition clients »), plan de comptes SYSCOHADA ⏳ (questions à
   l'expert-comptable), refonte du barème ⏳ (proposition zones/tranches à décider).
2. ~~FedaPay ne couvre que la recharge wallet~~ — ✅ **abonnement branché le
   2026-09-04** : bouton Mobile Money sur la page des plans, activation par le webhook
   signé via `switchPlan()`. Stripe reste disponible en parallèle. Le renouvellement
   avant échéance perd toujours le reliquat (comportement du socle, non tranché).
3. ~~Quatre écrans mobiles sont partiels~~ — ✅ **complétés le 2026-09-04** : historique
   du wallet (nouvel endpoint `wallet/history`), retrait, boutiques modifiables, profil
   modifiable, mot de passe oublié en deux étapes. Au passage, deux failles du socle
   relevées et corrigées (voir `web/CARTOGRAPHIE.md`, S18 et S19) : les boutiques
   n'étaient pas scopées par marchand, et une demande de retrait pouvait désigner le
   compte bancaire d'un autre marchand.
4. ~~L'écran `notifications` attend une source~~ — ✅ **livré le 2026-09-04** : fil
   Laravel (`notifications` table, canal `database`) alimenté par six observers
   (statut colis, crédit wallet, relevé émis, alerte douane, message admin, retrait),
   API `notifications/*`, écran mobile avec compteur sur le tableau de bord. Le push
   FCM reste hors service : le fil est consulté, pas poussé.
5. ~~Une route morte reste dans `web.php` (`my-wallet/recharge-status`)~~ — ✅ **retirée
   le 2026-09-05** avec la recharge Mobile Money du panneau marchand ; les deux
   routes PDF de facture sont implémentées par le chantier 4.
6. **Constats de sécurité** : ~~S21~~ ✅ **Aamarpay et SSLCommerz désactivées le
   2026-09-05** (`config/payments.php`, routes non enregistrées, réglages verrouillés,
   statuts existants passés à inactif). **Plus aucun constat ouvert.** ~~S7~~ ✅ **filet posé le 2026-09-04** : toute route
   `/api/v10` à identifiant doit déclarer son test d'isolation
   (`IsolationCoverageTest`), et l'audit a fermé cinq fuites (fraude, support, comptes
   de versement, retraits, colis côté livreur). ~~S8-S9~~ ✅ **corrigés
   le 2026-09-04** : un seul `DeliveryChargeResolver` (scopé société, par tranche de
   poids) sert le calculateur, les deux AJAX des écrans et l'import CSV. ~~S11-S13~~
   ✅ **corrigés dans le code le 2026-08-18**, tests et cartographie complétés le
   2026-09-04 (topic push non devinable, TLS vérifié, e-mail de contact au nom de
   la plateforme, validation de `contact-us`). ~~S5~~ ✅ **corrigé le
   2026-09-04** : l'API est cloisonnée par `userType` (marchand / livreur → 403) et
   les jetons portent l'ability de leur type.
7. ~~`mobile-livreur/` n'est pas commencé~~ — ✅ **v1 livrée le 2026-09-05** (fenêtre
   Création) : 6 écrans sur les endpoints livreur existants, mêmes fondations que `mobile/`.
8. **Recette pilote préparée le 2026-09-05** : guide `docs/guides/recette-pilote/`,
   profils EAS `recette` / `production` dans les deux apps, jeu de données béninois
   `php artisan beninlink:pilote` (5 PME, 3 livreurs, 35 colis, barème FCFA), scénarios
   et critères de sortie.
9. ~~`mobile/app.json` pointe un fichier de locale inexistant~~ — ✅ **corrigé le
   2026-09-04** : `src/i18n/expo-fr.json` créé (nom d'app et Face ID en français).
