# Cartographie du projet BeninLink — et ce qui reste

> Vue d'ensemble du monorepo au **2026-10-06** (`main` = fusion de la PR #168, session
> S100 ; § 7.4 relu en S102 sur les runs d'*Actions*). Relevée sur le code et les documents du dépôt, pas sur les intentions.
> Elle complète `web/CARTOGRAPHIE.md` (relevé technique du socle, blocs A-K + journal
> S22-S68), `docs/REVUE_MODULES.md` (inventaire module par module) et
> `docs/DECISIONS_METIER.md` (registre D1-D13). Elle ne les remplace pas : elle dit
> **où tout est**, et **ce qui n'est pas fini**.

---

## 1. Les composantes

| Dossier | Techno | Rôle | État | Financement |
|---|---|---|---|---|
| `web/` | Laravel 10 · PHP 8.3 · MySQL (SQLite en test) | Socle We Courier + modules BeninLink. **Le contrat.** | Actif — 7 chantiers livrés, 47 sessions de durcissement (S22-S68) | Ligne 10 |
| `mobile/` | React Native · Expo 57 · expo-router · TS strict | App **marchand** (PME) | Actif — 15 écrans de la maquette codés, 0 endpoint manquant | Ligne 11 / TDR-L8 |
| `mobile-livreur/` | idem | App **livreur** | Actif — v1 à v3 livrées (7 écrans, GPS, photo, signature) | Fenêtre Création (2026-09-05) |
| `docs/` | Markdown + DOCX | Revues, décisions, guides infra/compta/recette | Vivant, mis à jour à chaque session | — |
| `maquettes/` | HTML statique | 3 maquettes validées (marchand, livreur, back-office) | Référence, non contractuelle | — |
| `courier_*_saas-main/` | Flutter | Apps d'origine We Courier | **Dépréciées** (licence, référence) | — |
| `.github/workflows/deploy.yml` | GitHub Actions | Tests sur PR ; push sur `main` = déploiement VPS | Actif depuis le 2026-09-07 | — |

Volumétrie (au 2026-10-03) :

| | `web/` | `mobile/` | `mobile-livreur/` |
|---|---|---|---|
| Contrôleurs / modèles / migrations | 134 / 82 / 104 | — | — |
| Routes | 105 API (`/api/v10`) · ~1 100 lignes `web.php` · 303 lignes `superadmin.php` | — | — |
| Écrans (fichiers expo-router) | — | 23 | 11 |
| Fichiers TypeScript | — | 49 | 32 |
| Tests | 120 fichiers · 943 méthodes · **1 057 tests, 46 070 assertions**, verts (S66) | aucun lanceur | aucun lanceur |
| Historique | 129 commits · 136 PR fusionnées · 0 PR ouverte | | |

## 2. Les flux

```
 mobile/ (marchand)  ──┐                      ┌──> FedaPay (MTN MoMo, Moov)  webhook signé ──┐
                        │  /api/v10 (Sanctum,  │                                              │
 mobile-livreur/ ───────┼──> apiKey, userType) ─┤──> Expo Push (D11)                          │
                        │                      │                                              │
 back-office Blade ─────┘   web/ (Laravel)  ───┼──> SMS Twilio / Vonage / REVE (en file, D13) │
   admin · merchant ·       multi-tenant       │                                              │
   hub · super-admin        par company_id     └──> e-mail (en file, D13)                     │
                                 ▲                                                            │
                                 └────────── crédit wallet / activation abonnement <──────────┘
```

- Un seul axe de périmètre : `company_id` (`companywise()`), sans tenancy sur l'API.
- Un seul point de crédit du wallet : le webhook FedaPay, idempotent sous verrou.
- Un seul calcul des montants : `ChargeCalculator` côté serveur (S2) ; les apps affichent.
- Un seul axe de tarification depuis l'étape 6 : **la route** (zone + délai, D4).
- Tout envoi sortant part **en file** et porte sa société (D13, F4).

## 3. `web/` — où vit chaque chose

### 3.1 Couches
| Couche | Où | Remarque |
|---|---|---|
| Routes | `routes/api.php` (contrat mobile), `web.php` (tenant), `superadmin.php`, `tenant.php` | Trois panneaux gardés par type de compte (S41) |
| Contrôleurs | `app/Http/Controllers/{Api/V10, Backend/*, Payment, Frontend, Auth}` | 134 |
| Dépôts | `app/Repositories/*` (52 domaines) | Convention We Courier : contrôleur → dépôt |
| Services BeninLink | `app/Services/*` : `Customs`, `Invoicing` (relevé, numérotation, journal SYSCOHADA), `Parcel` (calcul, TVA, résolveur, débit wallet, `ParcelStage`), `Payments/FedaPayGateway`, `Pricing` (zones, audit), `Push`, `Sms/SmsTemplate`, `Reporting/SaasMetrics`, `OpenApi/SpecGenerator`, `Notifications/MerchantFeed`, `Brand/AccentColor`, `Pilote` | Toute la valeur ajoutée est **ici**, en extension du socle |
| Commandes | `app/Console/Commands` — 12, dont 9 `beninlink:*` | Seules 3 ont une sortie 1 exploitable par une alerte |
| Spec API | `public/openapi/v10.json` (généré) + `resources/openapi/overlay.php` | `OpenApiSpecTest` compare aux `endpoints.ts` des deux apps |
| Charte web | `public/beninlink/` (jetons, polices) | Couche chargée en dernier, jamais dans le CSS du socle |
| Outils d'audit | `web/docs/outils/*.py` (lectures nues, `OR` hors périmètre) | Servent les filets S61 et S65 |

### 3.2 Chantiers BeninLink (tous livrés)
| # | Chantier | Livré | Reste (voir §7) |
|---|---|---|---|
| 1 | Francisation + FCFA entier | 2026-08-16 ; semences d'amorçage béninoises (S92) et vitrine des sociétés neuves en français (S93) le 2026-10-06 | colonnes `decimal(…,2)` toujours en base |
| 2 | IFU / RCCM / CNSS | 2026-08-17 | — |
| 3 | FedaPay (wallet + abonnement + panneau web) | 2026-09-04/05 | — (renouvellement avant échéance : tranché R3, S75) |
| 4 | Relevés SYSCOHADA (PDF, CSV, journal) ; plan de comptes tranché (S73) | 2026-09-04 → 2026-10-03 | 3 numéros + 4 codes de journaux à confirmer, régularisation des retours sans TVA (D2) |
| 5 | Alertes douanières (3 niveaux, push + courriel) | 2026-08-19 → S67 ; sur le détail colis : app marchand (S82), fiches web back-office et panneau marchand (S94), course du livreur (S95), pastille sur les listes des deux apps (S101) | — |
| 6 | Reporting SaaS (MRR, ARR, churn, LTV, CAC) | 2026-09-04 | discipline de saisie CAC |
| 7 | OpenAPI `/api/v10` | 2026-09-04 | — |
| D4 | Barème par zones, étapes 1-6 ; grille de départ en fichier (S72) | 2026-09-07 → 2026-10-03 | forfaits CEDEAO des 5 autres pays, taux COD CEDEAO, TVA export |

### 3.3 Les huit filets de test (ce qui empêche de régresser)
`IsolationCoverageTest` (API, S7) · `WebIsolationCoverageTest` (routes web à paramètre, S35 : arriéré 0) ·
`BodyIdentifierCoverageTest` (identifiant dans le corps, S53 : 0) · `WebAdminPermissionCoverageTest` (S44) ·
`OffRequestScopeCoverageTest` (hors requête, F4, S56) · `SearchSurfaceCoverageTest` (S63 : 0) ·
`OrScopeEscapeCoverageTest` (S61) · `NakedReadCoverageTest` (S65). Tous les arriérés sont **clos** ;
`HERITAGE` doit rester vide partout.

### 3.4 Décisions actées en code
D1 TVA société 18 % · D2 plan de comptes (tranché S73, numéros à valider) · D3 CAC · D4 zones (grille de départ en fichier, ajustable à l'écran — S72) · D5 fraude scopée ·
D6 solde plancher à la création · D7 import Excel facture · D8 étape comptable une fois, chez soi, tout ou rien ·
D9 le relevé est la vérité, le solde un cache · D10 module payout coupé · D11 push Expo ·
D12 push navigateur retiré · D13 envois en file · D14 décisions produit R3–R9 (S75). Plus S21 (Aamarpay/SSLCommerz coupées) et
S27 (éditeur `.env` fermé).

## 4. `mobile/` — app marchand

| Zone | Écrans (`app/`) | Modules (`src/`) |
|---|---|---|
| Auth | login, signup (IFU/RCCM/CNSS), verify-otp, forgot-password, reset-password | `api/auth`, `session/SessionProvider` |
| Accueil | index (KPIs, solde, compteur douane coloré par gravité, notifications) | `api/merchant` |
| Colis | parcels, parcel/[id] (timeline, preuves), parcel/new (devis serveur, zone + délai) | `api/parcels`, `domain/parcelStatus`, `domain/deliveryType` |
| Argent | wallet, wallet/withdraw, invoices (relevés + PDF signé), rates (zones **ou** 4 colonnes héritées) | `api/wallet`, `api/fedapay`, `domain/money` |
| Douane | customs (paginé, 3 niveaux) | `api/customs`, `domain/customsLevel` |
| Divers | shops, shop/[id], notifications, profile, profile/edit, profile/password | `api/shops`, `api/notifications`, `push/` |

`MISSING = {}` dans `src/api/endpoints.ts` : aucun écran n'attend plus `web/`.
Les invariants de l'app se mesurent **depuis PHPUnit** (`OpenApiSpecTest`, `ParcelStageTest`,
`MerchantAppCustomsContractTest`) : l'app n'a pas de lanceur de tests.

## 5. `mobile-livreur/` — app livreur

7 écrans : login (`driver_id`), Mes courses (3 onglets + position), détail course, issue de la
course (livré / partiel / retour, photo + signature), gains, profil, mot de passe. 12 endpoints,
tous sous `userType:deliveryman`. Aucun montant calculé dans l'app ; aucun endpoint de tarif
(D4 ne la concerne pas). Mêmes fondations que `mobile/` (thème, `ui.tsx`, `money.ts`,
`parcelStatus.ts`, `client.ts` repris à l'identique).

## 6. Documentation et exploitation

| Document | Rôle |
|---|---|
| `web/CARTOGRAPHIE.md` (6 454 lignes) | Relevé du socle + journal de chaque session S22-S68 |
| `docs/REVUE_MODULES.md` | Inventaire module par module, matrice front ↔ backend |
| `docs/REVUE_FONCTIONNELLE_MOBILE.md` | Écrans maquette ↔ endpoints (2026-08-17, historique) |
| `docs/REVUE_FEDAPAY_ET_WORKFLOWS.md` | 11 constats W/F, tous corrigés ; décisions D6-D13 |
| `docs/DECISIONS_METIER.md` | Registre D1-D13 |
| `docs/guides/infra/*` | Mise en service VPS (PHP, MySQL, env, nginx), supervisor, sauvegarde, reprise, supervision |
| `docs/guides/recette-pilote/` | Environnement de recette, profils EAS, jeu `beninlink:pilote`, scénarios, critères de sortie |
| `docs/guides/comptabilite/` | Plan de comptes (à signer), reprise des relevés |
| `docs/guides/tarification/refonte-bareme.md` | Étude d'impact D4 et **grille à remplir** |
| `docs/guides/charte-web/` | Audit UX web, 7 lots + lot SMS livrés |
| `docs/guides/socle/` | Méthode de re-fusion We Courier (238 fichiers modifiés, 196 ajoutés) |

---

## 7. Ce qui reste

Classé par **qui débloque** : le métier, le code, l'exploitation. Rien ici n'est un constat de
sécurité ouvert — les 32 constats S1-S32 et les 11 constats W/F sont fermés.

### 7.1 Décisions métier en attente (le code attend la réponse)
| # | Sujet | Source | Qui |
|---|---|---|---|
| R1 | **Grille tarifaire** — tranché pour l'essentiel le 2026-10-03 (S72) : la grille de départ (tarifs du 2026-09-06) vit dans `web/database/bareme/grille-nationale.csv`, posée par `beninlink:zones-tarifaires --installer --grille=…` et lue par le jeu pilote ; les montants **ne sont pas figés**, le transporteur les réajuste à tout moment à l'écran et la commande ne les réécrit jamais. Délai global (+300 F jour même) confirmé. ✅ **Clos le 2026-10-06 (S106)** : les cinq autres pays (GH 15 000, NE 18 000, CI 20 000, ML 22 000, SN 25 000) sont posés comme point de départ dans `ZoneCatalog::PAYS` — créés s'ils manquent, jamais réécrits, ajustables à l'écran ; le taux COD CEDEAO était déjà tranché (3 %, 2026-09-06) ; la TVA à l'export reste **au taux du marchand** (18 % ou exonéré, D1/R7) — une exonération sectorielle du transport international serait une question fiscale pour l'expert-comptable, pas un défaut de code. | D4 complément S72 et S106, `refonte-bareme.md` §1.3-1.5 | — |
| R2 | **Plan de comptes SYSCOHADA** — les 8 questions sont tranchées par le porteur (S73, 2026-10-03) et codées : auxiliaire par défaut, retour taxable, banque à la date du virement, recharges et remises journalisées. ✅ **Clos côté code le 2026-10-06 (S106)** : les numéros en place (4712 COD dédié, 4191 avances reçues, 4713 transit livreurs ; journaux VE/OD/BQ/CA) sont le **plan de travail**, modifiables en une ligne de `config/syscohada.php` si l'expert-comptable en préfère d'autres — un contrôle, plus un bloqueur ; la **régularisation du passé est sans objet** : la production a démarré le 2026-10-06 (run 217), après S73, aucun retour n'y a jamais été facturé sans TVA (`beninlink:retours-sans-tva` y rend zéro). | D2 décisions S73 et S106, `plan-de-comptes.md` § 6 | — |
| ~~R3~~ | ~~**Renouvellement d'abonnement avant échéance**~~ — ✅ **tranché le 2026-10-03 (S75, D14)** : pas de prorata, la règle est dite avant confirmation sur les deux écrans de changement de plan. | D14 | — |
| ~~R4~~ | ~~**Langue du destinataire des SMS**~~ — ✅ **tranché (S75, D14)** : français seul pour le pilote, couture `SmsTemplate::locale()` conservée pour l'expansion anglophone. Aucun code. | D14 | — |
| ~~R5~~ | ~~**Garde du menu Réglages**~~ — ✅ **livré (S75, D14)** : visibilité en OU sur les quatorze droits lus par le sous-menu ; gardes d'écriture inchangées (`SettingsMenuGuardTest`). | D14 | — |
| ~~R6~~ | ~~**Catalogues de plateforme partagés et modifiables**~~ — ✅ **livré (S75, D14)** : `categorys` suit `currencies` (S55) sous `super-admin/`, `panel:super-admin` ; les six catégories de livraison d'amorçage restent ; pas de personnalisation par société pour l'instant. | D14 | — |
| ~~R7~~ | ~~**TVA**~~ — ✅ **tranché (S75, D14)** : hors Bénin, taux configurable par société (D1) ; **exonéré** devient un statut explicite `merchants.vat_status`, distinct de « non renseigné », imprimé sur le relevé ; aucune reclassification rétroactive. | D1 complément, D14 | — |
| ~~R8~~ | ~~**Signature du destinataire sur un retour**~~ — ✅ **livré le 2026-10-06 (S106)** : **facultative**, sous le même champ que la livraison (`signatureImage`), stockée sur l'événement de retour et lue par le marchand dans le suivi ; l'écran du livreur la propose pour « Retour » avec son propre texte. La question L6 de la recette reste posée aux PME : leur réponse décide si l'écran la garde, pas si le serveur l'accepte. | S106, `DeliveryProofTest`, recette §4 L6 | PME pilotes (L6) |
| ~~R9~~ | ~~**Envoyer les relevés de règlement par courriel**~~ — ✅ **activé (S75, D14)** : à l'émission de chaque relevé, en file, au courriel du compte marchand, PDF officiel joint (point de rendu unique) ; jamais de réémission modifiée. **Reste** : l'opposabilité juridique du PDF, à verser au dossier de l'expert-comptable (R2). | D14, `StatementEmailTest` | expert-comptable |

### 7.2 Dette technique `web/` (connue, consignée, non bloquante)
| # | Sujet | Source |
|---|---|---|
| ~~T1~~ | ~~**Lot de nettoyage du code mort**~~ — ✅ **livré le 2026-10-03 (S69)** : `InvoicePDFSend` rendu juste (vue qui résout, marque du destinataire, en file — toujours **non branché**, voir R9), `invoice_pdf.blade.php` délègue au relevé officiel, `InvoicePdf()` et `IncomeController::searchAccount()` retirés, et un **neuvième filet** : toute route déclarée vise une méthode de contrôleur existante (`LegacyDeadCodeCleanupTest`). 0 fichier supprimé. | CARTOGRAPHIE S69 |
| ~~T2~~ | ~~**Colonnes monétaires en `decimal(…,2)`**~~ — ✅ **tranché le 2026-10-06 (S107, D15) : pas de migration de schéma.** La règle « FCFA entiers » vit au seul endroit où un taux devient des francs (`ChargeCalculator::percentage()`, `VatRoundingTest`) et à l'affichage ; changer une quinzaine de colonnes du socle serait invasif (règle d'or), compliquerait T9 et une migration MySQL interrompue ne se défait pas. En échange, la règle se **vérifie** dans une base vivante : `beninlink:montants-non-entiers` (constat, rien d'écrit, quatrième constat du guide de reprise § 8). | D15, `NonIntegerAmountsTest` |
| ~~T3~~ | ~~**Migration Bootstrap 4 → 5**~~ — ✅ **tranché le 2026-10-06 (S107, D15) : les étapes A et B, faites, suffisent ; C, D, E ne sont pas entreprises.** Les deux versions cohabitent à dessein (Bootstrap 5 câble `data-bs-*`, Bootstrap 4 câble `data-*`), rien ne casse ; renommer 331 attributs et 26 classes change l'apparence de tout le back-office pour aucun gain au pilote. À rouvrir seulement après la recette, avec le contrôle visuel humain (E5), si un défaut d'écran le demande. | D15, `bootstrap/migration-4-vers-5.md` § 5 |
| T4 | **PHP 8.4 fermé** par `nette/utils`, `ezyang/htmlpurifier`, `nette/schema` (remontent à `league/commonmark`). | `web/CLAUDE.md` |
| ~~T5~~ | ~~**Angle mort du filet S65** : un identifiant au nom libre échappe à `estIdentifiant()`~~ — ✅ **livré le 2026-10-05 (S81)** : les **dix noms libres** du socle (`account`, `from_account`, `to_account`, `merchant`, `merchant_account`, `editid`, `hub`, `account_head`, `merchantId`, `accountId`) en liste explicite dans `BodyIdentifierCoverageTest` ; **cinq routes** entrent, toutes classées. En les ajoutant : `FundTransferRepository::update()` lisait ses deux comptes **nus** (S64 n'avait gardé que `store()`) et le filet des lectures nues l'absolvait par une colonne du même nom — resserré ; le panneau marchand écrivait le **compte de versement d'un autre marchand** (`merchant/payment-request/store`). `FreeNamedIdentifierScopeTest`. Un nom hors liste (`from`, `token`) échappe encore : la liste s'allonge avec sa ressource. | CARTOGRAPHIE S81 |
| ~~T6~~ | ~~**L'API ne négocie pas la locale**~~ — ✅ **livré le 2026-10-06 (S90)** : `ApiLocale` sur le groupe `api` lit `Accept-Language` (première langue servie par `config/locales.php` : `fr`, `en`), locale remise après la réponse, FR sans en-tête ; `ApiLocaleTest`, spec OpenAPI (`Envelope.message`). | CARTOGRAPHIE S90 |
| ~~T7~~ | ~~**`API_KEY`** : repli `'123456rx-ecourier123456'` dans `config/rxcourier.php`~~ — ✅ **livré le 2026-10-05 (S77)** : `env('API_KEY')` sans repli, `CheckApiKeyMiddleware` **refuse fermé** quand la clé configurée est vide et compare par `hash_equals` (le `==` du socle acceptait vide contre vide) ; `verifier-env.sh` refuse un `.env` sans `API_KEY` ou portant la clé publique du socle **avant la coupure** ; `API_KEY` dans `.env.example` ; `ApiKeyFailClosedTest`. | CARTOGRAPHIE S77 |
| ~~T8~~ | ~~**Taille de page = contrat implicite**~~ — ✅ **livré le 2026-10-05 (S78)** : `responseWithPage()` ajoute un bloc `page` à la racine de l'enveloppe (`current`, `per_page`, `last`, `total`) ; la liste des relevés rejoint l'enveloppe (`data` reste le tableau) ; l'app lit `page` et garde la page pleine en repli. Un **onzième filet** (`ApiPaginationContractTest`) énumère les méthodes d'API qui paginent — et en a trouvé **quatre de plus** (boutiques, hubs, fraudes, tickets) qui servaient dix lignes sans le dire par leur dépôt partagé avec le back-office ; `fetchShops()` lit désormais toutes les pages. | CARTOGRAPHIE S78 |
| ~~T9~~ | ~~**Re-fusion We Courier**~~ — ✅ **tranché le 2026-10-06 (S107, D15) : on ne monte pas.** Le fork est le produit : 106 lots de durcissement, 32 constats de sécurité fermés, module de paiement en ligne coupé (D10). Une montée ne se justifierait que par un correctif de l'éditeur **qui nous manque** sur ce que nous utilisons ; le journal de l'éditeur se lit contre cette grille à chaque version, et la méthode (base `eb56a37`, fusion à trois points, suite avant/après, recette) reste prête. | D15, `socle/mise-a-jour-we-courier.md` |
| ~~T10~~ | ~~En-têtes datés en retard~~ — ✅ **livré le 2026-10-05 (S77)** : les trois en-têtes (`web/CARTOGRAPHIE.md`, `CLAUDE.md` racine, `DECISIONS_METIER.md`) datent du lot courant et disent ce qu'ils couvrent ; règle : chaque lot les met à jour avec sa section `## S<nn>`. | ce relevé |
| ~~T13~~ | ~~**Rien ne rejouait `deploy.sh` avant le serveur**~~ — ✅ **livré le 2026-10-05 (S80)** : job « Répétition de déploiement » (`--no-dev`, MySQL 8, installation d'une recette puis les commandes de `deploy.sh` dans son ordre, API qui répond), dont dépendent les deux déploiements ; `DeploymentRehearsalTest` tient script et job d'accord. En l'écrivant : une installation neuve était **refusée au premier déploiement** par `tarification-prete` (société du socle sans zones) — guide et job posaient les zones à la main, jusqu'à **S86** qui le règle dans les semences. | CARTOGRAPHIE S80 |
| ~~T12~~ | ~~**Deux dépendances de dev cassaient l'installation `--no-dev`**~~ — constaté par le porteur sur le VPS le 2026-10-05, ✅ **livré le même jour (S79)** : Debugbar enregistrée sans condition dans `config/app.php` (→ `AppServiceProvider`, sous `class_exists` et `app.debug`, `dont-discover`), Faker lu par sept semences depuis `require-dev` (→ `require`, même version). Un **douzième filet** (`DevDependencyLeakTest`) interdit toute référence à un paquet de dev hors de `tests/`. Reproduit et vérifié dans une copie `--no-dev`. | CARTOGRAPHIE S79 |
| ~~T11~~ | ~~**Liens du panneau marchand vers le back-office**~~ — ✅ **livré le 2026-10-03 (S70)** : cinq routes `admin/` nommées par des vues marchandes vivantes (Annuler, fil d'Ariane, Effacer, étiquettes en lot) répondaient 403 depuis S41 ; recâblées, route marchande d'étiquettes en lot scopée au marchand, scripts partagés sans global indéfini, et un **dixième filet** (`MerchantPanelCrossLinkTest`). Le `parcel.merchant.get` relevé en S69 n'était que latent. | CARTOGRAPHIE S70 |

### 7.3 Apps mobiles
| # | Sujet | Source |
|---|---|---|
| ~~M1~~ | ~~**Alerte douanière sur le détail du colis**~~ — ✅ **livré le 2026-10-05 (S82)**, dans l'ordre `web/` → OpenAPI → app : `parcel/details/{id}` et `parcel/logs/{id}` portent `customs_alerts` (les alertes **de ce colis**, vide pour un domestique) ; la spec les documente (et corrige `parcel/logs`, qui annonçait un `parcelLogs` que personne ne servait) ; l'écran de détail les rend par la table de couleurs de S68, avec « Marquer traitée ». Pas de filtre par colis sur `customs/alerts` : le bloc s'adosse à une ressource déjà bornée. `CustomsAlertTest`, `MerchantAppCustomsContractTest`. | CARTOGRAPHIE S82 |
| ~~M2~~ | ~~**Aucun lanceur de tests** dans les deux apps~~ — ✅ **livré le 2026-10-05 (S84)** : `jest-expo` et `@testing-library/react-native` dans les deux apps, `npm test` ; 37 tests marchand (niveaux douaniers, montants FCFA, 33 statuts → 7 étapes, types de livraison, pagination, et la **carte des alertes douanières rendue**, extraite en composant), 17 tests livreur ; job `apps` dans le workflow (typage, lint, tests, sans conditionner le déploiement). En l'écrivant : `toAmount(NaN)` rendait « NaN FCFA », corrigé dans les deux apps. Le contrat avec `web/` reste en PHPUnit. Le **contrôle visuel humain** reste dû (E1). | CARTOGRAPHIE S84 |
| M3 | **Builds EAS non produits** : `eas init`, identifiants, deux keystores distincts, profils `recette` / `production` prêts mais aucun APK référencé. `npx expo-doctor` était retombé à 20/21 (paquets Expo en retard dans le SDK 57) ; **remis à 21/21 le 2026-10-03 (S71)**, typecheck et lint verts sur les deux apps. | recette §2 et §7 (P6), CLAUDE.md livreur |
| ~~M4~~ | ~~**Écran Tarifs à double forme**~~ — ✅ **livré le 2026-10-06 (S91)** : plus aucun serveur ne sert les colonnes depuis l'étape 6, et S86 interdit une société sans zones ; l'écran Tarifs n'a plus qu'une forme, la création n'offre plus « barème hérité » (`zoneChoices`), `MerchantAppPricingContractTest`. | CARTOGRAPHIE S91 |
| ~~M5~~ | ~~**Les listes de colis ne sont pas rendues en test**~~ (limite notée en S84 et S101) — ✅ **livré le 2026-10-06 (S103)** : la carte d'un colis est un composant extrait dans les deux apps (`ParcelCard`), rendu en test — pastille « Douane » selon `customs_pending`, montant en FCFA entiers, statut du backend, boutons Appeler / Itinéraire du livreur inactifs sans numéro ou adresse ; les deux tests de contrat lisent le composant et exigent son test. 47 tests marchand, 30 tests livreur. | CARTOGRAPHIE S103 |

### 7.4 Recette pilote et mise en service (la production est en service depuis le 2026-10-06 ; le reste n'est pas coché)
| # | Sujet | Source |
|---|---|---|
| E1 | **Recette pilote** : ✅ **préparée le 2026-10-03 (S71)** — la moitié serveur de 25 scénarios sur 34 est jouée par `RecettePiloteRepetitionTest` sur le jeu pilote (zéro défaut serveur), fiche de préparation P1-P10 et modèle de collecte dans le guide. **Reste l'exécution humaine** : 2 Android en réseau mobile, 5 PME avec un colis réel de bout en bout, une recharge FedaPay sandbox par PME ; prérequis non livrables d'ici : serveur de recette, clés sandbox, compte EAS. | `recette-pilote/README.md` §0, §4-5, §7 |
| E2 | **Serveur de recette** `recette.beninlink.app` — **vhost** sur la machine de production (décision du 2026-10-03), déployé depuis `main` par le job « Déploiement sur le serveur de recette » (S76 : secrets `RECETTE_SSH_*`, chemin `DEPLOY_PATH`, garde `verifier-env.sh` qui refuse une clé FedaPay live hors production). ✅ **Fonctionnel depuis le 2026-10-05** (vérifié par le porteur le 2026-10-07 : répond 200, worker `RUNNING`). S102 avait écrit « rien n'a encore été déployé en recette » : c'était faux, corrigé en S109. Les trois secrets `RECETTE_SSH_*` sont **posés** : au run 255 (fusion de S110, 2026-10-07) l'étape SSH de recette a **tourné** (elle sautait aux runs 251 et 253), puis `beninlink:comptes-amorcage` l'a **refusée** — les cinq comptes d'amorçage de la base de recette portent encore `12345678` — et le filet a remis la révision servie avant (`12b7d21`, S87). **Reste** (serveur) : changer ou supprimer ces cinq comptes dans le back-office de recette, puis `php artisan beninlink:comptes-amorcage` doit sortir en succès ; la fusion suivante déploiera la recette d'elle-même. | recette §1 « Déploiement », §7 P0-P1 |
| E3 | **Mise en service production** : ✅ **en service depuis le 2026-10-06** — après le run 212 (refusé par `comptes-amorcage`, remèdes appliqués sur le serveur), le run 217 (S91) a été le **premier déploiement de production réussi** ; chaque fusion suivante a déployé (219, 229, 231, 233…). Les gardes posés ensuite ont trouvé un serveur conforme : cache partagé (S97, run 229), `APP_DEBUG=false` (S99, run 233). Le piège « deux sociétés, une seule avec des zones » est fermé (S86), les comptes d'amorçage à `12345678` aussi (S87), l'installateur est fermé sur une base installée (S88). **Au 2026-10-07 (vérifié sur le serveur par le porteur, S109)** : worker Supervisor `RUNNING`, crontab posé — les SMS, courriels et pushs partent, le planificateur tourne ; sauvegarde quotidienne (2 h 15) avec exercice de restauration réussi le 06/10. Société 1 **renommée « beninlink » le 2026-10-07 à 17 h 50** (S110) ; la société 2 (« Company », données de démonstration) reste inchangée. **Plus rien à faire sur le serveur** pour la mise en service. (S108 avait annoncé le renommage fait trop tôt et la sauvegarde à faire : corrigé en S109.) | `infra/mise-en-service/` |
| E4 | **Reprise du passé** si une production tournait avant les correctifs : `beninlink:colis-non-debites`, `ecarts-marchands`, `retours-annules`, `reglages-orphelins`, `invoice:generate --societe=N`. À lire, pas à brancher sur un cron (sortie 0 même avec écarts). | REVUE_FEDAPAY §24, `infra/supervision/` |
| E5 | **Vérification visuelle humaine** des trois paniers d'écrans web (public, back-office, panneau marchand) : aucun lot charte ne l'a faite. Le porteur l'a commencée sur la page d'accueil le 2026-10-08 : elle affichait sous « Nos partenaires » des logos de vraies marques sans relation (Huawei, UPS…) — retirés en **S111**, avec les visuels « We Courier » des pages publiques (logo, favicon, illustration de connexion et d'inscription). | charte-web §18 |
| E6 | **Document maître DAT** (`.docx`) à resynchroniser avec les décisions D6-D13, l'étape 6 du barème et les lots charte. | `LISEZMOI.txt` |

---

## 8. Lecture en une phrase

Le code est **complet par rapport au périmètre défini** (7 chantiers, 15 + 7 écrans, 0 endpoint
manquant, 0 constat de sécurité ouvert, 8 filets à arriéré nul) ; ce qui reste est d'abord
**métier** (le reliquat CEDEAO de la grille R1, les numéros de comptes de R2, la signature sur retour R8 après recette) et **opérationnel**
(recette E1, l'après-mise-en-service de E3 — Supervisor, supervision, sauvegarde —, builds M3), puis un lot de dette technique consigné (T2-T9, dont cinq lignes déjà barrées)
qui n'empêche ni la recette ni la mise en production.
