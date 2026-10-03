# Cartographie du projet BeninLink — et ce qui reste

> Vue d'ensemble du monorepo au **2026-10-03** (`main` = fusion de la PR #136, session
> S68). Relevée sur le code et les documents du dépôt, pas sur les intentions.
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
| 1 | Francisation + FCFA entier | 2026-08-16 | colonnes `decimal(…,2)` toujours en base |
| 2 | IFU / RCCM / CNSS | 2026-08-17 | — |
| 3 | FedaPay (wallet + abonnement + panneau web) | 2026-09-04/05 | renouvellement avant échéance |
| 4 | Relevés SYSCOHADA (PDF, CSV, journal) ; plan de comptes tranché (S73) | 2026-09-04 → 2026-10-03 | 3 numéros + 4 codes de journaux à confirmer, régularisation des retours sans TVA (D2) |
| 5 | Alertes douanières (3 niveaux, push + courriel) | 2026-08-19 → S67 | alerte sur le détail colis |
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
D12 push navigateur retiré · D13 envois en file. Plus S21 (Aamarpay/SSLCommerz coupées) et
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
| R1 | **Grille tarifaire** — tranché pour l'essentiel le 2026-10-03 (S72) : la grille de départ (tarifs du 2026-09-06) vit dans `web/database/bareme/grille-nationale.csv`, posée par `beninlink:zones-tarifaires --installer --grille=…` et lue par le jeu pilote ; les montants **ne sont pas figés**, le transporteur les réajuste à tout moment à l'écran et la commande ne les réécrit jamais. Délai global (+300 F jour même) confirmé. **Reste** : les forfaits des 5 autres pays CEDEAO (CI, NE, ML, SN, GH), le taux COD de la zone CEDEAO, la TVA à l'export (18 % ou exonération — un petit chantier si exonération). | D4 complément S72, `refonte-bareme.md` §1.3-1.5 | transporteur |
| R2 | **Plan de comptes SYSCOHADA** — les 8 questions sont tranchées par le porteur (S73, 2026-10-03) et codées : auxiliaire par défaut, retour taxable, banque à la date du virement, recharges et remises journalisées. **Reste** à l'expert-comptable : le numéro du compte COD dédié, ceux des avances reçues et du transit livreurs, les codes de journaux du logiciel cible, et la régularisation des retours facturés sans TVA (chiffrée par `beninlink:retours-sans-tva`). | D2 décisions S73, `plan-de-comptes.md` § 6 | expert-comptable |
| R3 | **Renouvellement d'abonnement avant échéance** : `switchPlan()` repart de `now()`, le reliquat est perdu (Stripe comme FedaPay). | `CARTOGRAPHIE.md` bloc E point 4 | porteur |
| R4 | **Langue du destinataire des SMS** : aucune colonne ne la porte ; couture isolée dans `SmsTemplate::locale()`. | charte-web §15.5 | porteur |
| R5 | **Garde du menu Réglages** : 5 permissions listées sur 15, un agent avec `general_settings_read` seul ne voit pas le menu. Changer la garde change qui voit quoi. | charte-web §13.7 | porteur |
| R6 | **Catalogues de plateforme partagés et modifiables** (`currencies`, `categorys`) et **six catégories de livraison d'amorçage sans société**. | `CARTOGRAPHIE.md` S35 « reste à décider » | porteur |
| R7 | **TVA** : sociétés hors Bénin, marchands exonérés (`0` = « pas saisi », pas « exonéré »). | D1 limite assumée | porteur |
| R8 | **Signature du destinataire sur un retour** : utile ou superflue ? Tranché après recette. | recette §4 livreur | PME pilotes |
| R9 | **Envoyer les relevés de règlement par courriel** : le mailable `InvoicePDFSend` est prêt (S69) mais volontairement non branché — cadence, destinataires et opposabilité du PDF restent à trancher. | CARTOGRAPHIE S69 | porteur, expert-comptable |

### 7.2 Dette technique `web/` (connue, consignée, non bloquante)
| # | Sujet | Source |
|---|---|---|
| ~~T1~~ | ~~**Lot de nettoyage du code mort**~~ — ✅ **livré le 2026-10-03 (S69)** : `InvoicePDFSend` rendu juste (vue qui résout, marque du destinataire, en file — toujours **non branché**, voir R9), `invoice_pdf.blade.php` délègue au relevé officiel, `InvoicePdf()` et `IncomeController::searchAccount()` retirés, et un **neuvième filet** : toute route déclarée vise une méthode de contrôleur existante (`LegacyDeadCodeCleanupTest`). 0 fichier supprimé. | CARTOGRAPHIE S69 |
| T2 | **Colonnes monétaires en `decimal(…,2)`** : les décimales ne sont plus affichées mais toujours stockées. Migration à décider séparément. | CARTOGRAPHIE bloc H |
| T3 | **Migration Bootstrap 4 → 5** (217 `data-toggle`, deux versions chargées ensemble). Hors de tous les lots charte. | charte-web §2.8, §18 |
| T4 | **PHP 8.4 fermé** par `nette/utils`, `ezyang/htmlpurifier`, `nette/schema` (remontent à `league/commonmark`). | `web/CLAUDE.md` |
| T5 | **Angle mort du filet S65** : un identifiant au nom libre (`from`, `token`, `reference`) échappe à `estIdentifiant()`. | CARTOGRAPHIE S65 |
| T6 | **L'API ne négocie pas la locale** (`Accept-Language` ignoré, FR par défaut). Acceptable tant que le produit est FR seul. | REVUE_FONCTIONNELLE §5 |
| T7 | **`API_KEY`** : lue dans `.env` (S3), mais le repli `'123456rx-ecourier123456'` subsiste dans `config/rxcourier.php`. Une installation sans la variable retombe sur la clé publique de l'éditeur. | `config/rxcourier.php:110` |
| T8 | **Taille de page = contrat implicite** (douane 20, notifications 20, relevés 10) : gardé par `MerchantAppCustomsContractTest`, mais rien dans la réponse HTTP ne la porte. | CARTOGRAPHIE S68 |
| T9 | **Re-fusion We Courier** : méthode écrite, jamais jouée ; question « faut-il monter ? » ouverte. | `docs/guides/socle/` |
| T10 | En-têtes datés en retard : `web/CARTOGRAPHIE.md` dit « dernière mise à jour 2026-08-16 » (contenu à S68), `CLAUDE.md` racine « dernière revue 2026-07-06 », `DECISIONS_METIER.md` « 2026-09-06 » (D6-D13 depuis). | ce relevé |
| ~~T11~~ | ~~**Liens du panneau marchand vers le back-office**~~ — ✅ **livré le 2026-10-03 (S70)** : cinq routes `admin/` nommées par des vues marchandes vivantes (Annuler, fil d'Ariane, Effacer, étiquettes en lot) répondaient 403 depuis S41 ; recâblées, route marchande d'étiquettes en lot scopée au marchand, scripts partagés sans global indéfini, et un **dixième filet** (`MerchantPanelCrossLinkTest`). Le `parcel.merchant.get` relevé en S69 n'était que latent. | CARTOGRAPHIE S70 |

### 7.3 Apps mobiles
| # | Sujet | Source |
|---|---|---|
| M1 | **Alerte douanière sur le détail du colis** : `customs/alerts` n'a pas de filtre par colis ; exige une évolution `web/` → OpenAPI → app, dans l'ordre. | CARTOGRAPHIE S68 |
| M2 | **Aucun lanceur de tests** dans les deux apps : les six propriétés de S68 sont des lectures de source, pas des rendus. Le **contrôle visuel humain** reste dû. | S68 « ce que ce lot ne garantit pas » |
| M3 | **Builds EAS non produits** : `eas init`, identifiants, deux keystores distincts, profils `recette` / `production` prêts mais aucun APK référencé. `npx expo-doctor` était retombé à 20/21 (paquets Expo en retard dans le SDK 57) ; **remis à 21/21 le 2026-10-03 (S71)**, typecheck et lint verts sur les deux apps. | recette §2 et §7 (P6), CLAUDE.md livreur |
| M4 | **Écran Tarifs à double forme** (zones ou 4 colonnes) : à simplifier seulement quand plus aucun serveur ne sert les colonnes. | `mobile/CLAUDE.md` |

### 7.4 Recette pilote et mise en service (rien n'est coché)
| # | Sujet | Source |
|---|---|---|
| E1 | **Recette pilote** : ✅ **préparée le 2026-10-03 (S71)** — la moitié serveur de 25 scénarios sur 34 est jouée par `RecettePiloteRepetitionTest` sur le jeu pilote (zéro défaut serveur), fiche de préparation P1-P10 et modèle de collecte dans le guide. **Reste l'exécution humaine** : 2 Android en réseau mobile, 5 PME avec un colis réel de bout en bout, une recharge FedaPay sandbox par PME ; prérequis non livrables d'ici : serveur de recette, clés sandbox, compte EAS. | `recette-pilote/README.md` §0, §4-5, §7 |
| E2 | **Serveur de recette** `recette.beninlink.app` : `.env` propre, `API_KEY` de recette, jeu `beninlink:pilote` — décrit, à poser. | recette §1 |
| E3 | **Mise en service production** : secrets `SSH_HOST/USER/KEY` dans Actions, utilisateur système, deux paires SSH, certificat **générique** par DNS-01 (pas `certbot --nginx`), `APP_INSTALLED=yes`, puis Supervisor (sans lui plus aucun SMS ne part), sauvegarde **et exercice de restauration**, supervision. Piège connu : le `db:seed` crée deux sociétés dont une sans zones → `tarification-prete` échoue sur une installation neuve. | `infra/mise-en-service/` |
| E4 | **Reprise du passé** si une production tournait avant les correctifs : `beninlink:colis-non-debites`, `ecarts-marchands`, `retours-annules`, `reglages-orphelins`, `invoice:generate --societe=N`. À lire, pas à brancher sur un cron (sortie 0 même avec écarts). | REVUE_FEDAPAY §24, `infra/supervision/` |
| E5 | **Vérification visuelle humaine** des trois paniers d'écrans web (public, back-office, panneau marchand) : aucun lot charte ne l'a faite. | charte-web §18 |
| E6 | **Document maître DAT** (`.docx`) à resynchroniser avec les décisions D6-D13, l'étape 6 du barème et les lots charte. | `LISEZMOI.txt` |

---

## 8. Lecture en une phrase

Le code est **complet par rapport au périmètre défini** (7 chantiers, 15 + 7 écrans, 0 endpoint
manquant, 0 constat de sécurité ouvert, 8 filets à arriéré nul) ; ce qui reste est d'abord
**métier** (le reliquat CEDEAO de la grille R1, les numéros de comptes de R2) et **opérationnel**
(recette E1, mise en service E3, builds M3), puis un lot de dette technique consigné (T1-T10)
qui n'empêche ni la recette ni la mise en production.
