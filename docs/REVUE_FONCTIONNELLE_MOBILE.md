# Revue fonctionnelle — `mobile/` (app marchand) ↔ `web/` (backend)

> Confrontation des **15 écrans de la maquette validée** (`maquettes/1_app_marchand.html`)
> aux **endpoints réels** de `/api/v10`, avec l'app Flutter dépréciée
> (`courier_merchant_saas-main/`) comme témoin de ce qui était réellement consommé.
> Sources : CLAUDE.md racine + `mobile/CLAUDE.md`, DAT §2.5-2.6, TDR-L8 / ligne 11,
> `web/CARTOGRAPHIE.md`.
> Établie le 2026-08-17. Aucun endpoint n'est inventé ici : la règle d'or reste
> « `web/` est le contrat, `mobile/` consomme ».

## 1. Verdict d'ensemble

**11 des 15 écrans sont codables immédiatement**, sur des endpoints déjà en service et
déjà éprouvés par l'app Flutter. **3 écrans sont bloqués** par une absence côté backend,
**1 est incomplet**.

| | Écrans |
|---|---|
| ✅ **Codable tout de suite** (9) | login · forgot · home · parcels · parcel-detail · shops · rates · profile · invoices |
| 🟡 **Codable avec réserve** (3) | signup (champs légaux) · new-parcel (calcul serveur) · notifications (source détournée) |
| 🔴 **Bloqué — exige `web/` d'abord** (3) | wallet (partiel) · recharge FedaPay · customs (douane) |

⇒ **Le chantier mobile peut démarrer sans attendre**, en attaquant les 9 écrans verts.
Les 3 écrans rouges sont précisément les modules à valeur ajoutée BeninLink (FedaPay,
douane) : ils suivent l'ordre des chantiers de `web/CARTOGRAPHIE.md`.

## 2. Table de correspondance écran → endpoint

| Écran maquette | Endpoint(s) `/api/v10` | État |
|---|---|---|
| `login` | `POST /signin` · `POST /otp-verification` · `POST /resend-otp` | ✅ |
| `forgot` | `POST /password/email` · `POST /password/reset` | ✅ |
| `signup` | `POST /register` | 🟡 IFU/RCCM/CNSS absents (voir §3.1) |
| `home` | `GET /dashboard` · `/dashboard/filter` · `/analytics` · `/dashboard/available-parcels` | ✅ |
| `parcels` | `GET parcel/index` · `parcel/filter` · `parcel/all/status` · `status-wise/parcel/list/{status}` | ✅ |
| `parcel-detail` | `GET parcel/details/{id}` + **`GET parcel/logs/{id}`** (timeline) | ✅ |
| `new-parcel` | `GET parcel/create` (référentiels) · `POST parcel/store` | 🟡 montants calculés client (voir §3.2) |
| `shops` | `GET shops/index` · `POST shops/store` · `PUT shops/update/{id}` · `DELETE shops/delete/{id}` | ✅ |
| `rates` | `GET /settings/delivery-charges` + `GET /settings/cod-charges` | ✅ |
| `invoices` | `GET /invoice-list/index` · `GET /invoice-details/{id}` · **`GET /dashboard/balance-details`** | ✅ (voir §4 — meilleure nouvelle de la revue) |
| `profile` | `GET /profile` · `POST /profile/update` · `PUT /update-password` | ✅ |
| `notifications` | `GET news-offer/index` | 🟡 ce sont des **offres**, pas des notifications |
| `wallet` | solde à reverser ✅ · retrait ✅ (`payment-request/*`) · historique ✅ (`account-transaction/index`, `statements/index`) · **`wallet_balance` ✗** | 🔴 partiel (voir §3.3) |
| `recharge` | **aucun** — `POST /fedapay/initiate` reste à créer | 🔴 |
| `customs` | **aucun** — Module 4 inexistant | 🔴 |

Contrôle d'accès commun : header **`apiKey`** (`CheckApiKeyMiddleware`) sur **toutes** les
routes, puis `auth:sanctum`. ⚠️ La clé est **codée en dur** dans
`config/rxcourier.php:90` et l'app Flutter l'embarquait en clair
(`api-list.dart` : `apiCheckKey = "123456rx-ecourier123456"`) — constat **S3**. À traiter
avant toute diffusion d'APK aux 5 PME pilotes.

## 3. Les quatre points de friction

### 3.1 `signup` — les identifiants légaux n'existent pas en base
La maquette demande **IFU (13 chiffres), RCCM (RB/COT/…), CNSS**, conformément au DAT.
Or `POST /register` valide via `Merchant/SignUpRequest` : `business_name`, `full_name`,
`hub_id`, `mobile`, `password`, `address`, `policy` — **rien d'autre**. Le bloc F de la
cartographie le confirme : aucune colonne d'identité légale sur `merchants`,
`general_settings` ni `users` ; `trade_license` et `nid_id` sont des **scans** (`foreignId`
vers `uploads`), pas des numéros exploitables.

⇒ **Dépend du chantier 2 de `web/`.** En attendant : coder l'écran avec les champs légaux
**présents mais désactivés**, ou les recueillir sans les transmettre. Ne pas les poster à
un endpoint qui les ignore silencieusement — ce serait une perte de données invisible.
⚠️ `mobile: digits_between:11,14` est calibré sur le Bangladesh : à revoir pour `+229`
(8 chiffres, 11 avec indicatif).

### 3.2 `new-parcel` — le serveur ne calcule pas les montants
`POST parcel/store` exige `shop_id`, `category_id`, `delivery_type_id`, `customer_name`,
`customer_address`, `customer_phone`. Mais les **montants** (frais de livraison, TVA, COD,
total, net) arrivent dans un champ **`chargeDetails`** que le client fabrique et que le
serveur enregistre **tel quel, sans recalcul** (`ParcelRepository:340, 516, 710`).

C'est le constat **S2** : un client qui modifie ce JSON **choisit sa propre TVA et ses
propres frais**. L'app Flutter reproduisait ce calcul en Dart ; **reproduire ce schéma en
React Native reconduirait la faille** et obligerait à maintenir le barème en double.

⇒ **Recommandation ferme** : ne pas porter le calcul dans `mobile/`. Faire de S2
(rapatriement du calcul côté serveur) un préalable, ou au minimum obtenir un endpoint de
**devis** (`POST parcel/quote` → montants autoritatifs) que l'app affiche sans les
recalculer. À arbitrer avant d'écrire l'écran.

### 3.3 `wallet` — deux notions distinctes, une seule exposée
Le DAT §2.5 demande « solde, recharge (FedaPay), retrait (payout) ». La cartographie
(bloc C/D) montre **deux soldes sans rapport** :

| Notion | Colonne | Exposée en API ? |
|---|---|---|
| **Net à reverser** (COD encaissé − frais − TVA) | calculé à la volée | ✅ `GET /dashboard/balance-details` |
| **Porte-monnaie prépayé** | `merchants.wallet_balance` | ❌ jamais exposé |

Le wallet prépayé n'a **aucun endpoint** : ni lecture de solde, ni historique, ni recharge.
Côté web il n'est alimenté que par `WalletRepository::approved()`, déclenché à la main par
un administrateur (l'appel est même commenté dans `rechargeAdd`).

⇒ L'écran `wallet` est **codable à 3/4** dès maintenant : solde à reverser, retrait
(`payment-request/store`), historique (`account-transaction/index`). Seuls le **solde
prépayé** et la **recharge** attendent `web/`.

### 3.4 `customs` — module entièrement à créer
Alerte douanière UEMOA/CEDEAO : pays, catégorie, niveau **INFO / AVERTISSEMENT /
BLOQUANT**, document requis (DAT §2.5). **Rien n'existe** : ni table, ni endpoint, ni
règle. C'est le chantier 5 de `web/`. La maquette (`customs`, 3 630 caractères) sert de
spécification d'interface.

## 4. La bonne surprise : le relevé de règlement est déjà calculé

`mobile/CLAUDE.md` demande « **Factures = relevés de règlement** (Encaissé COD − Frais −
TVA = Net à reverser) ». Le bloc G de la cartographie concluait qu'il n'y avait « ni
service de facturation, ni PDF ». **C'est vrai pour le PDF, mais faux pour le calcul** :

`DashboardRepository::balanceDetails()` produit exactement la formule attendue —
`amount_delivered` (COD encaissé), `payable_delivery_charge` (frais), `sub_total`,
`vat_amount` (TVA), `cod_charge`, puis
**`available_balance = (sub_total − vat_amount) − cod_charge`** = net à reverser, plus
`clearable_parcels`.

Et `GET /invoice-details/{id}` renvoie la ventilation par facture :
`total_deliverd_amount`, `delivery_charge`, `cod_amount`, `total_return_fee`,
`payable_amount`, `total_parcels`, `parcels`, plus l'identité du marchand.

⇒ **L'écran `invoices` est pleinement codable** : `balance-details` pour le « à reverser »
en cours, `invoice-list` + `invoice-details` pour l'historique. Ce qui manque côté `web/`
est **l'export PDF** (aucune bibliothèque installée, 2 routes mortes, 2 blades orphelines)
— l'app peut s'en passer en affichant le relevé nativement, et n'offrir le PDF qu'une fois
le chantier 4 fait. Seul export disponible aujourd'hui : **CSV**.

## 5. Écarts de contrat à connaître avant de coder

| Sujet | Ce que l'app doit savoir |
|---|---|
| **Montants** | L'API renvoie désormais des **entiers JSON** (`1234`), plus des chaînes `"1234.00"` — bascule faite le 2026-08-16. Les modèles TypeScript doivent typer `number`, pas `string`. |
| **Statuts** | `app/Enums/ParcelStatus.php` compte **33 constantes** (chaque transition a son pendant `_CANCEL`), là où la maquette et le CLAUDE.md n'en présentent que 7. Prévoir une **table de correspondance** 33 → 7 côté app, et ne jamais réinventer les libellés : `GET parcel/all/status` les fournit. |
| **Locale** | `LanguageManager` n'est monté que sur le groupe `web` ⇒ **l'API n'a aucune gestion de locale**, elle répond dans la locale par défaut (désormais `fr`). Pas de négociation `Accept-Language`. |
| **Tenancy** | Aucune tenancy sur `/api/v10` : le scoping repose entièrement sur `Auth::user()->company_id`. L'app n'a **rien à envoyer** pour désigner la société. |
| **Sécurité** | Jetons Sanctum **sans `abilities`** et aucune garde `user_type` : le même jeton ouvre les routes marchand **et** livreur (**S5**). À ne pas exploiter côté app, mais à savoir pour la revue de sécurité avant les pilotes. |
| **Erreurs** | Réponses normalisées par `ApiReturnFormatTrait`, mais la forme de `data` **change selon l'endpoint** et les `Resource` ne couvrent qu'une partie. Prévoir un client d'API tolérant. |

## 6. Ordre de codage proposé pour `mobile/`

**Lot 0 — socle** (aucune dépendance backend)
Expo + expo-router + TypeScript ; thème (Vert `#12503A`, Ocre `#E0A63C`, Sora + DM Sans) ;
i18n FR ; client d'API (`apiKey` + Bearer Sanctum, `EXPO_PUBLIC_API_URL` en variable
d'environnement) ; helper d'affichage FCFA **entier** (miroir de `formatAmount()`).

**Lot 1 — authentification et lecture** : `login`, `forgot`, `home`, `profile`.
Endpoints tous en service. Valide le client d'API de bout en bout.

**Lot 2 — cœur métier colis** : `parcels`, `parcel-detail` (timeline via `parcel/logs`),
`shops`, `rates`. C'est la valeur d'usage immédiate pour les 5 PME pilotes.

**Lot 3 — argent en lecture** : `invoices` (relevé via §4), `wallet` en mode partiel
(solde à reverser, retrait, historique).

**Lot 4 — après `web/`** : `signup` complet (chantier 2), `new-parcel` avec calcul serveur
(S2), `recharge` FedaPay (chantier 3), `customs` (chantier 5), `notifications` (source
propre à créer).

⚠️ **Rappel de périmètre** (`.claude/rules/mobile.md`) : le temps facturé Idéation ne
concerne **que `mobile/`** (ligne 11). `mobile-livreur/` relève de la fenêtre Création.

## 7. Ce que l'app Flutter apprend, et ce qu'il ne faut pas reprendre

**À reprendre** : la liste d'endpoints de `lib/services/api-list.dart` est le témoignage le
plus fiable de ce que le backend sert réellement — elle a servi de base à la table du §2.
Les modèles de `lib/Models/` documentent la forme des réponses.

**À ne pas reprendre** :
- le calcul des montants côté client (§3.2) ;
- la clé d'API en dur dans le code (`apiCheckKey`) → variable d'environnement ;
- l'URL de production en dur (`wemaxdevs.xyz`) ;
- les 6 locales partielles (`arabic.dart`, `bangla.dart`, `hindi.dart`…) : **FR seul** ;
- l'abonnement push par **adresse e-mail** comme topic (`fcm-subscribe`) — faille **S11**,
  et l'API FCM legacy visée est **arrêtée depuis juin 2024** (bloc K). Le push mobile
  demande une réécriture serveur avant d'être promis dans l'app.
