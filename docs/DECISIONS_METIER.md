# Décisions métier — BeninLink

> Registre des décisions qui ne relèvent pas du code seul. Chaque entrée dit ce
> qui a été **tranché** (et livré), ce qui reste **à trancher** par le métier, et
> par qui. Mis à jour le 2026-10-09, S144 (D1-D16 ; les décisions R1-R9 du porteur du
> 2026-10-03 sont ventilées dans les lignes qu'elles tranchent).

| # | Sujet | État | Livré dans le code | Reste à trancher |
|---|---|---|---|---|
| D1 | TVA au niveau entreprise | ✅ tranché | taux société `configs.vat_rate` (18 %), surcharge par marchand ; **statut explicite** `unset` / `taxable` / `exempt` (R7 b, S75) | valeur par société hors Bénin : configurable (D1), défaut 18 % |
| D2 | Plan de comptes SYSCOHADA | ✅ tranché (S73) | les 8 questions tranchées par le porteur le 2026-10-03 : auxiliaire par défaut, COD dédié, 4431 / 18 %, journaux en config, banque à la date du virement (`paid_on`), **retour taxable** (+ constat `retours-sans-tva`), arrondi confirmé sans reprise, recharges et remises journalisées, SaaS hors livres | **3 numéros** (COD dédié, avances reçues, transit livreurs), **4 codes de journaux**, et la **régularisation du passé** des retours sans TVA — expert-comptable |
| D3 | Dépenses d'acquisition (CAC) | ✅ tranché | chapitre « Marketing et acquisition clients » | discipline de saisie mensuelle |
| D4 | Refonte du barème (zones, tranches) | ✅ tranché (S72) | zones/délais/forfaits pays **en base**, résolveur, écrans, API, apps ; **grille de départ dans un fichier versionné**, posée par la commande et le jeu pilote, **ajustable à tout moment à l'écran** | forfaits des 5 autres pays CEDEAO, taux COD CEDEAO, TVA à l'export (R1) |
| D5 | Fiches de fraude sans `company_id` | ✅ tranché | migration de rattachement par l'auteur | — |
| D14 | Décisions produit R3–R9 | ✅ tranché (S75) | pas de prorata mais **dit avant confirmation** (R3) ; SMS français seul (R4) ; menu Réglages visible en **OU** (R5) ; catalogue catégories **super-admin** (R6) ; statut TVA explicite (R7 b) ; relevés **par courriel** à l'émission (R9) | signature sur retour (R8) après la recette ; expansion anglophone des SMS ; personnalisation des catalogues par société |
| D15 | Dette technique T2, T3, T9 | ✅ tranché (S107) | **pas** de migration des colonnes `decimal` (constat `beninlink:montants-non-entiers`) ; Bootstrap 4 + 5 cohabitent, étapes C–E non entreprises ; **pas** de re-fusion We Courier, le fork est le produit | rouvrir T3 après la recette si un écran le demande ; T9 à chaque version de l'éditeur, contre la grille « ce qui nous manque » |
| D16 | Comptes de paiement : codes 3, 4, 5 et banques | 🔁 **défaut réversible** (S117) | 3 = MTN MoMo, 4 = Moov Money, 5 = Autre Mobile Money ; banques 1-8 béninoises ; une seule source, `lang/*/account_gateway.php` et `account_bank.php` ; rien n'est renommé en base | le porteur confirme ou corrige les libellés (un fichier) ; le transporteur vérifie la banque de ses comptes créés avant S117 |

---

## D1 — TVA au niveau entreprise ✅

**Constat.** Le socle ne connaissait qu'un taux **par marchand** (`merchants.vat`,
défaut 0). Un marchand créé sans saisie n'était jamais taxé : la facture était fausse
par omission.

**Décision.** Un taux **au niveau de la société** (`configs.vat_rate`), **18 %** par
défaut (taux normal de TVA au Bénin), que chaque marchand peut surcharger :

- `merchants.vat` > 0 → ce taux (régime négocié ou particulier) ;
- sinon → le taux de la société ;
- sans réglage → 0, comme avant (aucune installation ne change de comportement sans
  la migration qui pose le 18 %).

**Où.** `App\Services\Parcel\VatRate::for($merchant)`, utilisé par `ChargeCalculator`
(devis, création, modification), l'import CSV et la recherche marchand de l'écran
admin. Réglage sur la page « Liquide/Fragile & TVA » de l'administration (permission
`liquid_fragile_update`, la page des frais de la société). Migration
`2026_09_05_110000` : 18 % pour toute société qui n'a pas de taux.

**Limite assumée.** `0` signifie « pas de saisie », pas « exonéré » : un marchand
exonéré dans une société taxée n'est pas représentable. Si le cas se présente,
ajouter un drapeau `vat_exempt` sur le marchand — pas avant.

**Reste au métier.** Confirmer 18 % pour les sociétés pilotes ; fixer le taux des
sociétés hors Bénin (CEDEAO) le jour venu.

### Complément du 2026-10-03 (R7 b, S75) — l'exonération devient un statut

La limite assumée ci-dessus (« un marchand exonéré ne peut pas se déclarer, 0 =
pas saisi ») est levée : `merchants.vat_status` porte `unset`, `taxable` ou
`exempt`. `VatRate::for()` rend 0 pour un exonéré **par statut**, jamais pour un
0 non renseigné, qui prend toujours le taux de la société. La migration pose
`taxable` là où un taux était saisi (comportement inchangé) et ne pose `exempt`
nulle part. Le relevé imprime « Marchand exonéré de TVA (statut déclaré) » —
distinct de « Aucune TVA facturée ». Décision D14 / R7 b.

## D2 — Plan de comptes SYSCOHADA ✅ (numéros = plan de travail depuis S106)

**Ce qui existe.** `config/syscohada.php` propose : 4111 Clients, 7061 Prestations de
services de livraison, 4431 TVA facturée, 4712 Créditeurs divers (COD encaissé pour
compte de marchands), 521 Banques ; journaux VE, OD, BQ. L'export journal
(`SyscohadaJournal`) est équilibré par construction et testé.

**Ce qui a été fait le 2026-09-06, sans attendre l'arbitrage** — parce qu'on ne
valide pas un plan de comptes sur du papier :

- **Fiche de validation** : `docs/guides/comptabilite/plan-de-comptes.md` — les
  trois écritures avec un exemple chiffré réel, les questions ci-dessous
  avec une proposition **et sa raison**, une case par réponse, et une signature.
- **Extrait de période** : `php artisan beninlink:journal-syscohada [--du=]
  [--au=] [--societe=] [--payes] [--fichier=]`. L'export n'existait que relevé
  par relevé, depuis le back-office ; l'expert-comptable a besoin d'un mois.
- **Équilibre vérifié avant écriture** (`SyscohadaJournal::balance()`) : un lot
  déséquilibré n'est pas produit, plutôt que refusé à l'import sans explication.
- **Question 1 déjà implémentée** : `SYSCOHADA_AUXILIARY=merchant_code` suffixe
  les comptes de **tiers** (4111, 4712) du code marchand, et eux seuls — un
  produit ou la TVA n'ont pas de tiers. Défaut inchangé : compte collectif.
- **Ce que l'export ne couvre pas** est désormais écrit noir sur blanc dans la
  fiche : recharges de portefeuille, reversements aux livreurs, abonnements
  SaaS. C'est une question de plus pour l'expert-comptable, pas un oubli.

**Ce que l'expert-comptable doit trancher** (modifier la config, jamais le code) :

1. **Sous-comptes** : 4111 (clients) et 7061 (prestations) conviennent-ils, ou
   faut-il des sous-comptes par marchand / par type de prestation ?
2. **COD** : le 4712 « Créditeurs divers » est-il le bon compte pour les fonds
   encaissés pour compte de tiers, ou préférer un 4713/4718 dédié ?
3. **TVA** : 4431 (TVA facturée sur ventes) vs 4432 (sur prestations) selon le
   régime retenu pour le transport.
4. **Codes de journaux** : VE / OD / BQ correspondent-ils au paramétrage du logiciel
   comptable cible (Sage, Saari, autre) ?
5. **Écriture de banque** : émise seulement au statut PAYÉ du relevé — confirmer que
   c'est la date de valeur attendue.
6. **Frais de retour et TVA** — question ajoutée le 2026-09-07, en relisant la
   fiche : un colis retourné est facturé au marchand (`return_charges`) mais
   `InvoiceRepository` force `vat_amount = 0`. Le socle traite donc le retour
   **hors champ**, sans que ce choix ait jamais été posé. Si l'expert-comptable
   le juge taxable, l'assiette déclarée est aujourd'hui sous-évaluée du montant
   des retours.
7. **Arrondi de la TVA au franc** — question ajoutée le 2026-09-18, en faisant le
   point fonctionnel avant mise en production. Le FCFA n'a pas de subdivision,
   mais la TVA est un pourcentage : 18 % de 1 680 F donne **302,40 F**, et le
   logiciel gardait la décimale. Le relevé, lui, arrondit chaque ligne à
   l'impression (`SettlementStatement::int()`). Les deux ne disent donc pas la
   même chose : mesuré sur le jeu pilote, **12 colis sur 35** portaient une TVA
   non entière, et **2 relevés sur 5** différaient de leur propre ligne en base
   de 0,40 F. C'était la forme exacte de l'anomalie à 14,40 F trouvée sur D9.

   **Appliqué le 2026-09-18** : l'arrondi vit dans
   `ChargeCalculator::percentage()`, le seul endroit où un taux devient des
   francs — il couvre donc la TVA **et la commission COD**, qui ne tombait juste
   que par la rondeur de ses taux (17 350 F à 2,5 % font 433,75 F). Le même jeu
   pilote reconstruit ne porte plus **aucun** centime et **aucun** écart
   document / base. Deux tests le tiennent, éprouvés en retirant le correctif.
   Ce qui reste au comptable : confirmer la règle (au plus proche) et dire si le
   passé doit être repris — les colis antérieurs gardent leurs décimales.

La question **6** demande encore du code ; la **7** l'a reçu, et n'attend plus
qu'une confirmation.

**Défaut corrigé le 2026-09-07 dans la fiche.** L'exemple chiffré annonçait « un
relevé d'un colis » alors que ses montants étaient les **totaux de trois colis**,
dont un retour. Lu tel quel, il affichait 227 F de TVA sur 1 760 F de base, soit
**12,9 %**, quand la question 3 de la même page annonce 18 % : le premier
contrôle qu'un comptable effectue mettait la fiche en défaut. Le détail est
désormais donné colis par colis — 18 % se recompose sur les deux livrés, et la
ligne de retour porte zéro. C'est en cherchant d'où venait l'écart qu'on a trouvé
la sixième question : elle était cachée dans un total.

### Décisions du 2026-10-03 (S73) — les huit questions, tranchées par le porteur

| # | Décision | Traduction en code |
|---|---|---|
| 1 | Auxiliaire par marchand **par défaut** | `config('syscohada.auxiliary')` vaut `merchant_code` sans variable d'environnement ; `SYSCOHADA_AUXILIARY=collectif` pour revenir à une racine unique. Seuls les comptes de tiers (`syscohada.auxiliarised`) sont suffixés |
| 2 | Compte COD **dédié** acté ; numéro à venir | `accounts.cod_liability` reste `4712`, libellé « compte dédié — numéro à valider » ; figé par le test |
| 3 | 4431, taux société 18 % | figés par `PlanDeComptesSigneTest` |
| 4 | Journaux en paramétrage | `VE` / `OD` / `BQ`, plus **`CA`** (caisse) pour les remises ; codes à aligner sur le logiciel cible |
| 5 | **Date de l'ordre de virement** | `invoices.paid_on`, posée quand le relevé est **marqué payé** (effacée si le statut recule) ; l'écriture `BQ` la prend, et l'extrait de période range chaque écriture dans la période de **sa** date (`SyscohadaJournal::periode()`). Règle d'usage : *marquer payé le jour du virement* |
| 6 | Frais de retour **taxable** au taux normal | `ReturnVat` : TVA au franc, au taux du colis, prélevée **au retour** sur sa propre ligne (`statementNote.return_vat_merchant_statement`) et écrite sur `parcels.return_vat_amount` ; rendue à l'annulation ; facturée par le relevé (plus de `vat_amount = 0` forcé). **`beninlink:retours-sans-tva`** constate les relevés émis avant — aucune option d'écriture ; la régularisation attend l'expert-comptable |
| 7 | Arrondi au franc confirmé, **pas de reprise du passé** | rien à coder ; la règle est figée par le test (302 pour 18 % de 1 680) |
| 8 | Recharges → **avances reçues** ; remises livreurs → **compte de transit** ; SaaS → **aucune écriture** | `SyscohadaJournal::recharges()` (D 521 / C 4191 + code marchand, à l'approbation) et `::remises()` (D 571 / C 4713, à la date) ; numéros **à valider** ; le test vérifie qu'aucun abonnement n'entre dans le journal |

Ce qui reste à l'expert-comptable, et qui ne demande ni code ni migration :
trois numéros (COD dédié, avances reçues, transit livreurs), quatre codes de
journaux, et la décision de régularisation des retours facturés sans TVA — dont
`beninlink:retours-sans-tva` chiffre l'enjeu. Jusque-là l'extrait sert à la
revue ; après, il s'importe.

## D3 — Dépenses d'acquisition pour le CAC ✅

**Constat.** Le CAC du reporting SaaS divise les dépenses d'acquisition de la société
plateforme par le nombre de nouveaux abonnés. Sans chapitre comptable dédié, aucune
dépense n'était reconnue : le CAC restait « non disponible ».

**Décision.** Un chapitre de dépenses **« Marketing et acquisition clients »** (table
globale `account_heads`, migration `2026_09_05_130000`, seeder aligné). Le reporting le
reconnaît par le mot-clé « acquisition » (`config/saas_reporting.php`).

**Reste au métier.** Saisir chaque mois, sous ce chapitre et pour la société
plateforme (id 1), les dépenses de marketing, publicité, communication et
prospection. Sans saisie, le CAC reste « non disponible » — jamais zéro, c'est voulu.

## D4 — Refonte du barème de livraison ✅

**Ce qui existe.** Une ligne de barème = catégorie × poids, avec **quatre colonnes**
qui mélangent délai et périmètre (`same_day`, `next_day`, `sub_city`, `outside_city`).
Depuis S9, une ligne vaut « jusqu'à N kg » : poids exact, sinon tranche supérieure,
sinon la plus lourde. Le calcul est côté serveur (S2) et unique (S8/S9).

**Ce qui coince pour le Bénin.** Une zone est une colonne : en ajouter une
(Cotonou / périphérie / intérieur / CEDEAO) impose une migration sur deux tables et
tous les écrans. Les taux COD, eux, ont **trois** zones (`inside_city`, `sub_city`,
`outside_city`).

**Proposition (à décider avant de coder).**

| Élément | Proposition |
|---|---|
| Zones | table `delivery_zones` (société, code, libellé, position) — au moins Cotonou, Périphérie, Intérieur, CEDEAO |
| Délais | `delivery_type_id` reste un **délai** (jour même / lendemain / standard), découplé de la zone |
| Grille | `delivery_charges` devient (société, catégorie, zone, `weight_max`, prix) ; les 4 colonnes disparaissent |
| Adresse → zone | à la création du colis : choix explicite de la zone (liste), pas de géocodage dans un premier temps |
| COD | `merchants.cod_charges` clé par code de zone, alignée sur `delivery_zones` |
| Migration | chaque ligne actuelle devient 4 lignes (une par colonne → zone équivalente) ; aucun montant ne change |

**Questions pour le métier.** Liste et libellés des zones ; grille tarifaire cible
(par tranche de poids et par zone) ; faut-il un délai par zone ou un délai global ;
politique CEDEAO (tarif au pays ou forfait) ; taux COD par zone.

Aucun code tant que la grille n'est pas fixée : la structure actuelle rend juste
ce que le barème contient.

**Préparé le 2026-09-06, sans préjuger des réponses :**

- **L'étalon** — `tests/Feature/DeliveryPricingBaselineTest` fixe ce que coûte un
  colis aujourd'hui : neuf poids × quatre types, le devis complet TVA comprise,
  et le fait qu'**aucune case ne croise délai et périmètre** (le constat de D4,
  sous forme exécutable). La migration promet « aucun montant ne change » ;
  cette promesse ne se vérifie pas à l'œil sur 274 occurrences — elle se
  vérifie contre un étalon écrit **avant**.
- **L'étude d'impact** — `docs/guides/tarification/refonte-bareme.md` : les 274
  occurrences réparties par nature (120 dans les vues, 41 en écriture, 24 en
  validation, 11 en lecture **déjà centralisée** par `DeliveryChargeResolver`),
  le contrat mobile à faire évoluer dans l'ordre `web/` → OpenAPI → apps, et
  une exécution en six étapes dont les trois premières sont réversibles et ne
  déplacent aucun montant.
- **La grille à remplir** — même document, partie 1 : zones proposées, tableau
  tranche × zone avec les tarifs d'aujourd'hui rappelés en regard, questions
  sur le délai, la CEDEAO et les taux COD.

Ce qui manque pour coder n'est donc plus une analyse : ce sont **cinq réponses**.

### Tranché par le métier le 2026-09-06

| Question | Réponse |
|---|---|
| Zones | **Cotonou, Périphérie, Intérieur, CEDEAO** |
| Délai | **global** — les mêmes délais partout, avec un **supplément par délai** indépendant de la zone |
| CEDEAO | **forfait par pays** (Togo, Nigeria, Burkina…), pas de tarif au poids |
| Grille de prix | **les tarifs actuels sont conservés** — Cotonou ← `next_day`, Périphérie ← `sub_city`, Intérieur ← `outside_city` |
| Supplément « jour même » | **300 F**, global |
| Taux COD par zone | **les taux actuels sont conservés** (Cotonou ← `inside_city`, Périphérie ← `sub_city`, Intérieur ← `outside_city`) |
| Forfaits CEDEAO par pays | **Togo 12 000, Nigeria 18 000, Burkina Faso 15 000** (tranché le 2026-09-06) |
| Taux COD de la zone CEDEAO | **3 %** (tranché le 2026-09-06) |

### Livré le même jour (étapes 1 à 3, réversibles)

- **Schéma** — `delivery_zones`, `delivery_delays` (avec leur supplément),
  `delivery_zone_countries` (le forfait par pays), et `zone_id` + `amount` sur
  les deux tables de barème. Migration **additive** : les quatre colonnes
  restent, personne ne les lit autrement tant qu'une société n'a pas de zones.
- **Résolution** — `DeliveryChargeResolver::resolveByZone()` : zone × tranche,
  plus le supplément du délai ; forfait du pays pour la CEDEAO ; priorité au
  barème négocié du marchand ; `null` sans zones, donc repli sur l'existant.
- **Conversion** — `php artisan beninlink:zones-tarifaires [--societe=]
  [--supplement=] [--appliquer]` : constate, puis écrit une ligne par zone aux
  montants d'aujourd'hui (Cotonou ← `next_day`, Périphérie ← `sub_city`,
  Intérieur ← `outside_city`, CEDEAO ← 0).

**Le seul tarif qui bouge, et de combien.** Le supplément « jour même » valait
200, 300, 300 puis 500 F selon la tranche ; le modèle retenu n'en admet qu'un,
et le métier a fixé **300 F**. Conséquence, sur le barème pilote :

| Tranche | « Jour même » avant | Après | Écart |
|---|---|---|---|
| 1 kg | +200 | +300 | **+100** |
| 3 kg | +300 | +300 | 0 |
| 5 kg | +300 | +300 | 0 |
| 10 kg | +500 | +300 | **−200** |

Tout le reste — les trois zones nationales, tous délais confondus — est
**identique au centime près**. La commande affiche ce tableau avant d'écrire ;
un test le fixe (`DeliveryZoneGridTest`).

**Plus rien ne reste côté métier sur D4** : les cinq questions ouvertes du
2026-09-06 au matin ont toutes reçu leur réponse.

### Complément du 2026-10-03 (S72) — la grille a un point de départ, pas une valeur figée

La question posée le 2026-10-03 était : la grille du 2026-09-06 est-elle **le**
tarif, ou un point de départ ? Réponse du porteur : **les montants ne sont pas
figés** ; le transporteur doit pouvoir **les réajuster à tout moment depuis le
back-office**. Ce qui est tranché et écrit en code :

- la grille de départ vit dans **un seul fichier versionné**,
  `web/database/bareme/grille-nationale.csv` (`categorie;poids_max;cotonou;peripherie;interieur`,
  FCFA entiers, une ligne par tranche « jusqu'à N kg », pas de colonne CEDEAO — elle se
  facture au forfait par pays) ;
- `beninlink:zones-tarifaires --installer --grille=<fichier>` la pose, et le jeu
  `beninlink:pilote` lit **le même fichier** : la recette et la production partent de
  la même grille, sans ressaisie ;
- une ligne (société, catégorie, zone, tranche) **déjà en base n'est jamais réécrite**.
  L'écran *Réglages → Zones et barème* garde le dernier mot ; la commande relancée à
  chaque déploiement crée ce qui manque et ne touche pas au reste
  (`GridFileTest::test_un_montant_reajuste_a_l_ecran_survit_a_la_relance`) ;
- un fichier fautif (montant décimal, colonne `cedeao`, tranche en double, colonne
  manquante) est refusé **avant la première écriture** : rien n'est posé, zones comprises.

Reste ouvert, hors du code : les forfaits des cinq autres pays CEDEAO (CI, NE, ML, SN,
GH — refusés tant qu'aucun forfait n'est fixé), le taux COD de la zone CEDEAO, et la
TVA à l'export (R1 de `docs/CARTOGRAPHIE_PROJET.md`).

### Livré ensuite (étape 4) — les écrans de saisie

Une décision qu'aucun écran ne peut enregistrer n'est pas appliquée : elle est
en attente. Jusqu'ici, seule `beninlink:zones-tarifaires` savait écrire ces
tables, et **rien** ne savait saisir les forfaits CEDEAO — précisément la
valeur que le métier doit encore fixer.

Deux écrans, sous *Réglages → Zones et barème* :

| Écran | Ce qu'on y saisit |
|---|---|
| `admin/delivery-zone/index` | les **zones** (une ligne par zone, ajout et retrait), les **délais** et leur supplément global, les **forfaits CEDEAO par pays** |
| `admin/delivery-zone/grid` | la **grille** d'une catégorie : une ligne par tranche de poids, une colonne par zone |

Trois garde-fous, chacun couvert par `DeliveryZoneScreensTest` :

- **le code d'une zone se fixe à la création et ne change plus.**
  `ChargeCalculator::codRateForZone()` et `ZoneGridConverter` reconnaissent une
  zone à son code ; le renommer détacherait le taux COD en silence. Le libellé,
  lui, se modifie librement ;
- **une zone qui porte des tarifs ne se supprime pas.** `zone_id` est en
  `nullOnDelete` : la supprimer transformerait ses lignes en lignes héritées
  portant un `amount` que plus personne ne lit — un tarif changé sans que
  personne l'ait demandé. L'écran refuse et nomme les zones concernées ;
- **la grille n'écrit que des lignes zonées.** Les quatre colonnes héritées ne
  bougent pas : une installation qui ne saisit rien facture comme avant.

Les routes empruntent les permissions `delivery_charge_*` plutôt que d'en
introduire de nouvelles — `PermissionSeeder` fait des `new Permission()` sans
garde d'unicité, et une permission neuve qu'aucun rôle ne porte rendrait
l'écran inaccessible à tout le monde. Même raisonnement que le back-office
douane.

**Deux défauts trouvés en passant sur l'écran hérité**, corrigés ici :

- `delivery_charges/index` affichait le tarif du **lendemain** sous l'en-tête
  « jour même » et inversement — les deux cellules étaient interverties ;
- `DeliveryChargeRepository::get()` n'était pas scopé par société (S8) :
  l'écran d'édition ouvrait la ligne de barème d'un autre transporteur dès
  lors qu'on en connaissait l'identifiant. `delete()` vérifiait déjà la
  société ; `get()` non.

### Livré ensuite (étape 5, côté `web/`) — le contrat d'API

`GET settings/delivery-charges` sert désormais **les deux formes** :

| Clé | Contenu |
|---|---|
| `deliveryCharges` | les quatre colonnes héritées, **inchangées** |
| `zones` | une entrée par zone : `code`, `name`, `export`, `cod_key`, ses `rates` (poids × montant) et ses `countries` (forfaits) |
| `delays` | le **supplément global** de chaque délai |

Deux règles portent la transition, et deux tests les tiennent :

- **`zones` arrive vide** tant que la société n'a rien configuré. C'est le
  signal, pour une app à jour, de rester sur l'ancien affichage — et la raison
  pour laquelle un APK déjà installé ne voit aucune différence le jour de la
  bascule. Sans cette période à deux formes, il afficherait une grille vide.
- **La grille servie annonce ce que le calcul facturera** : même priorité au
  barème négocié du marchand que `DeliveryChargeResolver::resolveByZone()`.
  Une liste qui n'annonce pas le bon prix est pire qu'une liste vide.

`GET settings/cod-charges` gagne `zone_code` sur chaque entrée. La
correspondance vient de `ChargeCalculator::COD_KEY_BY_ZONE` — extraite du
`match` qui vivait dans le calcul, elle est désormais **lue aux deux endroits**
plutôt que recopiée. `null` sur la zone d'export, comme le taux lui-même.

La spec est régénérée (`openapi:generate`, 106 chemins) avec les schémas
`DeliveryZone`, `ZoneRate`, `ZoneCountry` et `DeliveryDelay`. Aucune route
ajoutée : l'inventaire des apps (`mobile*/src/api/endpoints.ts`) ne bouge pas.

### Livré ensuite (étape 5, côté apps)

- **`mobile/`** — l'écran *Tarifs* sert **les deux formes** : le barème par
  zones dès que `zones` n'est pas vide, sinon les quatre colonnes, à
  l'identique. Une zone d'export liste ses forfaits par pays, et dit
  « aucun pays n'est encore tarifé » plutôt que d'afficher un zéro. Le
  supplément de délai est annoncé **une fois**, en tête, jamais répété dans
  chaque case. Les taux COD prennent le nom de leur zone via `zone_code`,
  et gardent le libellé d'origine quand le serveur ne l'envoie pas.
- **`mobile-livreur/`** — **rien à faire, et c'est vérifié** : aucun endpoint
  de tarif dans son inventaire, aucun écran n'affiche `deliveryType`. Un
  livreur encaisse un montant que le serveur a déjà calculé ; il ne consulte
  pas la grille. Consigné dans `mobile-livreur/CLAUDE.md` pour ne pas
  reposer la question.

### Livré ensuite (étape 5 bis) — la grille facture

Le maillon manquant : `resolveByZone()` n'avait **aucun appelant en
production**, parce qu'un colis ne savait pas dire dans quelle zone il va ni
sous quel délai. Il ne portait qu'un `delivery_type_id` — l'axe mélangé que D4
défait. La grille se saisissait, se servait, s'affichait ; elle ne facturait
pas.

- **Le colis porte sa route** : `parcels.zone_id` et `parcels.delay_id`,
  nullables, `delivery_type_id` conservé. Un colis sans zone est facturé
  exactement comme avant.
- **Le calcul suit la route** : avec une zone, `ChargeCalculator` prend le
  montant de `resolveByZone()` (zone × tranche + supplément du délai, ou
  forfait du pays) et le **taux COD de la zone** — plus celui du type de
  livraison. Sans zone, c'est le calcul d'avant, au franc près.
- **Une route non tarifée est refusée, jamais devinée.**
  `UnpricedDeliveryException` arrête le calcul plutôt que de retomber sur une
  colonne héritée, ce qui reviendrait à inventer un prix. En amont, la règle
  `DeliveryRoutePriced` attrape le cas dans les deux `StoreRequest` — le seul
  point commun aux **trois** chemins de création, comme `CustomsAllowed`.
  L'opérateur voit donc un message sur le bon champ, pas une erreur serveur.
  Le devis répond **422** avec le motif, et les deux écrans l'affichent au lieu
  de garder les montants du devis précédent.
- **Les écrans** : sélecteurs *zone* et *délai* sur les formulaires de création
  de l'administration et du panneau marchand, avec le supplément du délai en
  regard. Ils n'apparaissent que si la société a des zones.
- **L'API** : `parcel/create` et `parcel/edit` servent `zones` et `delays` ;
  `parcel/quote` et `parcel/store` acceptent `zone_id` et `delay_id` ;
  `ParcelResource` expose la route du colis. Spec régénérée.

**Hors périmètre, dit explicitement** : l'import Excel garde le chemin hérité —
le format du fichier n'a pas de colonne de zone, et lui en inventer une serait
une décision de format, pas une conséquence de D4. L'écran de création de
`mobile/` garde lui aussi son sélecteur de type de livraison ; le contrat qui
lui permet d'offrir la zone existe maintenant.

### Livré ensuite — le sélecteur de zone sur `mobile/`

L'écran de création propose *zone* et *délai* **dès que le serveur envoie des
zones**, avec la même règle que le back-office : la première entrée vaut
« barème hérité », et le colis est alors facturé par son type de livraison. Sur
un serveur qui n'a pas basculé, les clés sont absentes, les sélecteurs ne
s'affichent pas, l'écran est celui d'avant.

Deux détails qui évitent un refus incompréhensible :

- le supplément d'un délai est affiché **en regard du délai**, une fois — il est
  global, il ne se répète pas par zone ;
- choisir la zone d'export **sans destination** affiche un avertissement avant
  l'envoi : le serveur refuserait la création, autant le dire au moment du
  choix.

Le détail d'un colis affiche sa zone et son délai quand il en porte ; un colis
hérité garde son seul type de livraison, sans ligne vide.

### La porte de l'étape 6

`php artisan beninlink:bareme-herite` dit, **société par société**, ce qui
s'appuie encore sur les quatre colonnes, et **sort en erreur** tant qu'il en
reste — pour qu'un déploiement automatisé s'arrête là.

| Constat | Effet |
|---|---|
| Aucune zone configurée | **bloquant** — après l'étape 6, la société ne facturerait plus rien |
| Tranche héritée sans équivalent zoné | **bloquant** — le tarif existe dans l'ancien monde, pas dans le nouveau |
| Barème négocié marchand non zoné | **bloquant** — le marchand perdrait son tarif sans que rien ne le dise |
| Colis créé sans zone sur 30 jours | **bloquant** — un écran ou une app en circulation utilise encore le chemin hérité |
| Zone d'export sans forfait | avertissement — elle n'était pas tarifée avant non plus |

Le plan disait « une fois les apps déployées ». C'était une intention ; c'est
désormais une vérification. La commande ne corrige rien : la conversion reste
`beninlink:zones-tarifaires`, qui montre le tableau des écarts avant d'écrire.

### ⚠️ Ce que l'étape 6 coûte, mesuré en l'écrivant

Écrire le cœur de l'étape 6 a montré ce qu'on ne pouvait que supposer : une
fois `resolve()` et les colonnes retirés, **`ChargeCalculator` n'a plus de
repli** et lève dès qu'un colis n'a pas de zone. Ce n'est pas un défaut
d'écriture, c'est la définition de l'étape — le basculement est **sec**, il n'y
a pas de version intermédiaire.

Conséquences recensées, à trancher avant de s'y engager :

- toute société non convertie cesse de pouvoir créer un colis ;
- l'**import Excel** exige une colonne de zone, et les deux fichiers modèles
  `.xlsx` distribués doivent être régénérés ;
- `ZoneGridConverter` et `beninlink:zones-tarifaires` partent avec les colonnes
  qu'ils lisent : l'outil qui rend une installation éligible disparaît dans le
  même lot. Il doit donc avoir servi **avant** ;
- `DeliveryPricingBaselineTest`, l'étalon qui prouve depuis l'étape 1 qu'aucun
  montant n'a bougé, perd son sujet.

### Les forfaits CEDEAO — tranchés le 2026-09-06

| Pays | Code | Forfait |
|---|---|---|
| Togo | TG | 12 000 F |
| Nigeria | NG | 18 000 F |
| Burkina Faso | BF | 15 000 F |

Ils vivent dans `ZoneGridConverter::PAYS`, à côté des zones, des délais et du
supplément — le même endroit que les autres décisions du barème — et la
conversion les écrit.

**Créés s'ils manquent, jamais réécrits.** Le supplément de délai, lui, se
réécrit à chaque passage parce que la commande le prend en option
(`--supplement`) : c'est une valeur du run. Un forfait de pays n'a pas
d'équivalent, et un transporteur qui l'a ajusté à l'écran ne doit pas le voir
revenir à la valeur d'usine parce qu'on a relancé la conversion.

Un pays **hors de cette liste** — le Ghana, par exemple, membre de la CEDEAO —
reste sans tarif : la zone ne lui applique rien plutôt que d'emprunter le
montant d'un voisin, et la création est refusée avec son motif. C'est fixé par
l'étalon.

### Le taux COD de la zone CEDEAO — tranché le 2026-09-06

**3 %.** La zone d'export a désormais **sa propre clé** dans
`merchants.cod_charges` (`cedeao`), au lieu de n'en avoir aucune :
`ChargeCalculator::COD_KEY_BY_ZONE` la nomme comme les trois autres, et chaque
zone lit la sienne — aucune n'emprunte le taux d'une voisine.

Trois conséquences, chacune tenue par un test :

- **la migration comble ce qui manque, sans écraser ce qui existe.** Un taux
  négocié avec un marchand ne se réécrit pas parce qu'on a passé une migration ;
  même règle que pour les forfaits par pays ;
- **un marchand sans la clé reste à zéro.** Une colonne vide ou illisible n'est
  pas « réparée » au passage — ce serait inventer les trois autres taux ;
- l'écran de création d'un marchand affiche la nouvelle zone sans modification :
  il itère `config('rxcourier.cod_charges')`, où la ligne a été ajoutée.

Un envoi CEDEAO est donc désormais **entièrement tarifé** : forfait au pays pour
la livraison, 3 % sur l'encaissement.

### La relève de l'étalon — tranché le 2026-09-06

Le métier a répondu : **attendre le déploiement des apps** pour la suppression,
et **remplacer l'étalon** plutôt que l'effacer.

`DeliveryZonePricingBaselineTest` est donc écrit **maintenant**, pendant que les
deux barèmes coexistent — c'est précisément ce qui permet de le valider :

- il part du barème hérité du jeu pilote et le **convertit**, plutôt que de
  recopier une grille à la main qui pourrait diverger de ce que la conversion
  produit ;
- il fixe les mêmes neuf poids que l'ancien, zone par zone, et la même règle de
  tranche (S9) ;
- un test **pont** vérifie que les deux barèmes annoncent le même prix
  (`next_day` ↔ Cotonou, `sub_city` ↔ Périphérie, `outside_city` ↔ Intérieur) :
  c'est ce qui autorisera à retirer l'ancien le jour venu, au lieu de perdre la
  garantie avec la colonne ;
- il fixe aussi ce que l'ancien ne pouvait pas dire : le supplément **global**
  (le même dans les trois zones), le croisement *délai × périmètre* enfin
  possible — « jour même à l'intérieur », 3 800 F —, le forfait au pays de la
  zone d'export, et le taux COD qui suit la zone.

Vérifié en le cassant : en faisant reprendre `same_day` à Cotonou au lieu de
`next_day`, l'étalon échoue sur chaque tranche. Il garde donc bien la
correspondance décidée, il ne se contente pas de se confirmer lui-même.

**Fait le 2026-09-07** — voir la section suivante. La porte est passée du
constat à la migration : c'est elle, désormais, qui refuse de retirer les
colonnes tant qu'une société en dépend.

### L'étape 6 — les quatre colonnes sont retirées, 2026-09-07

`same_day`, `next_day`, `sub_city` et `outside_city` ont quitté
`delivery_charges` et `merchant_delivery_charges`. La refonte est **terminée** :
il n'existe plus qu'un seul axe de tarification, la **route** — zone × tranche,
plus le supplément global du délai.

**Ce que la bascule change, en une phrase.** Un colis sans zone n'est plus
facturé « à l'ancien tarif » : il n'a **pas de tarif**. `ChargeCalculator` lève
`UnpricedDeliveryException::sansZone()` plutôt que de rendre zéro, `zone_id` est
`required` à la création, et l'import Excel lit une colonne `zone_code` — les
deux fichiers modèles la portent.

**La migration refuse de s'exécuter** tant que quelque chose dépend encore des
colonnes : pas de zones, une tranche tarifée dans une zone et pas dans une
autre, un barème négocié sans zone, un colis créé sans zone sur trente jours.
Elle reprend les règles de `beninlink:tarification-prete`, volontairement
réécrites en SQL nu : une migration est jouée une fois, parfois des années après
avoir été écrite, et la faire dépendre d'un service applicatif reviendrait à
accepter qu'une refonte de ce service casse l'installation d'un nouveau client.

> ⚠️ **Mise à niveau en deux temps.** Le code qui lisait les colonnes part avec
> elles : une installation qui n'a pas converti doit rester sur la version
> précédente, y poser ses zones et sa grille, vérifier avec
> `beninlink:tarification-prete`, puis déployer celle-ci. Le message d'erreur de
> la migration le rappelle mot pour mot.

**Ce que l'étape emporte, et ce qu'elle garde.**

| Ce qui part | Ce qui prend la relève |
|---|---|
| `DeliveryChargeResolver::resolve()` et ses quatre colonnes | `resolveByZone()`, seul chemin |
| Les deux points AJAX `parcel/delivery-charge` | morts depuis l'étape 5 bis, remplacés par le devis complet |
| `ZoneGridConverter` (il convertissait) | `ZoneCatalog` : le **catalogue** des zones, délais et forfaits, en un seul endroit |
| `beninlink:bareme-herite` | `beninlink:tarification-prete` — même question, devenue permanente |
| `DeliveryPricingBaselineTest` | `DeliveryZonePricingBaselineTest`, après que le test **pont** eut vérifié que les deux barèmes annonçaient le même prix |
| Le 2ᵉ paramètre de `calculate()` | rien : il ne servait qu'à choisir une colonne |

**Les garanties, elles, ne partent pas.** S8 (le barème d'un autre locataire) et
S9 (un colis lourd au tarif le plus léger) valaient sur le résolveur hérité ;
elles sont réécrites mot pour mot sur le résolveur par zones. Le défaut des
colonnes interverties à l'écran — l'en-tête annonçait un tarif, la cellule en
affichait un autre — garde lui aussi son test, sur les deux colonnes qui
restent. Changer de modèle n'était pas une raison de perdre ce qu'on avait
appris.

**Une seule chose diffère, et c'est délibéré** : là où le résolveur hérité
rendait **0** quand il ne trouvait rien, celui-ci rend **`null`**. Zéro est un
prix ; l'absence de prix n'en est pas un, et l'appelant doit refuser plutôt que
de facturer gratuitement.

**442 tests**, dont sept sur le refus de la migration elle-même.

**Complément S106 (2026-10-06) — le reliquat CEDEAO de R1 est clos.** Les cinq
autres pays desservis entrent dans `ZoneCatalog::PAYS` comme **point de départ**,
gradués à la distance depuis Cotonou : Ghana 15 000, Niger 18 000, Côte d'Ivoire
20 000, Mali 22 000, Sénégal 25 000 F. Même règle que les trois premiers : créés
s'ils manquent, **jamais réécrits**, ajustables à l'écran. Le taux COD de la zone
(3 %) datait du 2026-09-06. La TVA à l'export reste **au taux du marchand** (D1,
R7) : une exonération du transport international est une question fiscale, à
instruire par l'expert-comptable, pas un défaut de code.

## D5 — Fiches de fraude sans `company_id` ✅

**Constat.** Avant S7, le panneau marchand créait les fiches de fraude sans
`company_id` ; depuis S7 la liste noire est scopée société, donc ces fiches en
sortaient.

**Décision.** Migration `2026_09_05_120000` : chaque fiche orpheline reçoit la
société de son auteur (`created_by` → `users.company_id`). Une fiche sans auteur
reste orpheline (elle n'a pas d'origine à laquelle la rattacher) ; aucune dans le
seed.

## D6 — Le solde du portefeuille est un plancher à la création d'un colis ✅

**Constat.** Un marchand réglant par porte-monnaie prépayé pouvait passer en
négatif sans limite : rien n'arrêtait la descente. La règle existait pourtant
déjà dans le socle — `Backend\ParcelController::store()` et
`MerchantPanel\MerchantParcelController::store()` refusaient tous deux la
création quand `total_delivery_amount` dépassait `wallet_balance` — mais elle
vivait dans ces **deux écrans seulement**. Les trois autres chemins de création
(API mobile, et les deux duplications) créaient le colis et débitaient sans rien
vérifier.

**Décision (2026-09-05).** On tranche pour la règle du socle, appliquée partout :
**pas de découvert**. Un colis qu'on ne peut pas facturer ne part pas.

| Point | Choix |
|---|---|
| Portée | tous les chemins de création : back-office, panneau marchand, API, duplications |
| Comparaison | `total_delivery_amount` (sous-total **hors TVA**) contre `wallet_balance` — la convention du socle, qui est aussi le montant réellement prélevé |
| Frontière | strictement supérieur : un solde exactement égal aux frais passe et tombe à zéro |
| Hors portée | les marchands qui ne règlent pas par portefeuille (`wallet_use_activation` inactif) ne sont jamais bloqués : ils paient au relevé |
| Découvert autorisé | **non**, et pas de réglage pour l'activer. Un transporteur qui veut faire crédit crédite le portefeuille — c'est traçable, un plafond de découvert ne l'est pas |
| Où vit le contrôle | avec le débit, dans `Services\Parcel\WalletDebit`, sous le même verrou de ligne : sans cela deux créations simultanées lisent le même solde et débitent deux fois |

**Ce que le refus rend.** 422 côté API, avec `required`, `wallet_balance` et
`missing` en entiers XOF : l'app affiche ce qui manque et propose la recharge du
bon montant, au lieu d'un message sans issue. Côté web, un message et un retour
vers la recharge — le comportement d'origine, conservé.

**À revoir si le métier change d'avis.** Autoriser un découvert plafonné se
ferait en un seul endroit (`WalletDebit`) ; c'est précisément pour cela que le
contrôle n'a pas été recopié dans les écrans.

## D7 — L'import Excel de colis facture comme la saisie ✅

**Constat.** `App\Imports\ParcelImport` créait les colis directement, sans passer
par aucun repository, et ne touchait **jamais** le portefeuille : ni contrôle de
solde, ni débit. Pour un marchand au portefeuille, le débit à la création *est*
la facturation : un import de deux cents colis n'était facturé nulle part.

Trois questions se posaient. Voici les réponses, tranchées le 2026-09-05.

### 1. L'import doit-il débiter, ou est-il volontairement hors portefeuille ?

**Il débite.** Rien dans le socle ne suggérait une exemption : pas de réglage,
pas de commentaire, pas de seconde passe de facturation. Le même fichier saisi
ligne à ligne dans le formulaire débite ; l'import est le même acte, en gros.
Il passe donc par `Services\Parcel\WalletDebit`, comme les quatre autres
chemins de création (**D6**), avec le même plancher de solde.

### 2. Un solde insuffisant refuse-t-il tout le fichier, ou les lignes couvertes ?

**Tout le fichier.** Et ce n'est pas une nouveauté : Laravel Excel enveloppe
déjà l'import dans une transaction (`config('excel.transactions.handler')`), ce
sur quoi le socle s'appuie pour les erreurs de validation — une ligne invalide
annule déjà tout le fichier. Le refus de solde suit la même règle.

La raison de ne pas faire autrement : un import à moitié passé est
**indiscernable** d'un import complet — même écran, même message — et le
marchand qui relance son fichier pour « finir » duplique ce qui était déjà
entré. Rien dans le fichier ne permet de dédoublonner (pas de clé, pas de
référence externe). Le refus nomme la ligne qui bloque et le montant manquant.

### 3. Que fait-on des imports déjà passés en production ?

**On constate, puis on régularise à la main.**
`php artisan beninlink:colis-non-debites` liste, par marchand, les colis qui ne
portent aucune écriture de portefeuille, avec le montant dû et les dates.
`--regulariser` écrit les débits manquants ; `--marchand=<id>` limite la portée ;
la commande refuse d'écrire en production sans `--force`.

Elle balaie plus large que l'import : W5 (les colis créés depuis l'app) et le
`catch` vide ont laissé la même signature.

| Point | Choix |
|---|---|
| Rapprochement | libellé du mouvement (`WalletDebit::SOURCE` + n° de suivi), seul lien existant entre une écriture et son colis |
| Plancher de solde | **ignoré** à la régularisation : le colis existe, la dette est acquise ; la refuser laisserait la créance invisible |
| Balayage automatique | **non**. `merchants.wallet_use_activation` n'a pas d'historique : un colis créé quand le marchand réglait au relevé apparaît dans la liste s'il est passé au portefeuille depuis. Un humain regarde, puis régularise marchand par marchand |

**Ce que la correction a ouvert au passage.** Brancher le débit rendait
exploitable un trou qui ne coûtait rien tant que rien n'était facturé : le
marchand venait de la colonne `merchant_id` du **fichier**, sans aucun contrôle.
Un marchand qui y écrivait l'identifiant d'un autre — la colonne n'est même pas
dans son fichier modèle, il fallait l'ajouter — créait des colis au compte de
cet autre, et aurait vidé son portefeuille. Règle posée, alignée sur le reste du
socle : **un marchand n'importe que pour lui-même** ; le back-office importe
pour un marchand **de sa société**, validé `companywise()`.

## D8 — Une étape comptable s'exécute une fois, chez soi, tout ou rien ✅

**Constat.** Quatre étapes déplacent de l'argent réel — la livraison, la
livraison partielle, le retrait, le relevé de règlement — et aucune n'avait de
test. En les couvrant (2026-09-05), les mêmes trois défauts sont apparus aux
quatre : elles se rejouaient sans broncher, elles n'étaient pas scopées à la
société, et elles n'étaient pas transactionnelles.

**Décision.** La règle vaut pour toute étape qui écrit dans les comptes —
`merchant_statements`, `deliveryman_statements`, `courier_statements`,
`vat_statements`, `wallets`, `bank_transactions` — ou qui touche un solde
(`merchants.current_balance`, `delivery_man.current_balance`,
`accounts.balance`, `merchants.wallet_balance`).

| Règle | Ce qu'elle veut dire |
|---|---|
| **Une fois** | L'étape vérifie l'état de départ et refuse s'il a déjà changé. Un second appel rend `false` et n'écrit rien. Ce n'est pas une précaution théorique : l'app livreur renvoie sur réseau instable |
| **Chez soi** | La ressource est chargée `companywise()`, jamais par `find($id)` nu. Le compte payeur aussi, pas seulement la demande |
| **Tout ou rien** | Toutes les écritures d'une même étape dans une seule transaction. Des livres à moitié faits sont pires que pas de livres : on ne peut pas relancer l'étape sans doubler l'autre moitié |
| **Notifier après** | SMS et notifications **hors** de la transaction, chacun rattrapé. Un opérateur injoignable ne défait pas une livraison qui a eu lieu |
| **Nommer** | Chaque écriture porte le tiers qu'elle concerne (`merchant_id`, `delivery_man_id`) : un solde qui bouge sans ligne pour l'expliquer est un litige à venir |
| **Un seul prétendant** | Les étapes **non comptables** qui désignent qui sera payé (affectation, reprogrammation) laissent **un seul** événement en lice. Le socle empilait les affectations là où la reprogrammation effaçait la précédente : réaffecter un colis payait le premier livreur nommé, pas celui qui avait livré. Effacer à l'écriture rend justes les cinq endroits qui relisent, plutôt que cinq `->latest()` à ne pas oublier |

**Et l'appelant le dit.** Une étape qui refuse doit se voir : 422 côté API,
message à l'écran côté web. L'API livreur répondait 200 quoi qu'il arrive —
inoffensif tant que l'étape se rejouait, trompeur dès qu'elle refuse.

**Portée.** La règle vaut aussi pour les **annulations**, qui inversent ces
mêmes écritures : couvertes le 2026-09-05, elles présentaient bien les mêmes
défauts — plus un qui leur est propre, *annuler ce qui n'a jamais eu lieu*.
D'où la formulation « vérifie l'état de départ » plutôt que « refuse un second
appel » : une annulation doit constater qu'il y a quelque chose à annuler.

Et pour la **remise d'espèces du livreur à l'agence**
(`CashReceivedFromDeliveryman`), qui solde la dette constatée à la livraison.
Sa méthode de correction y ajoute un cas limite instructif : elle défait
l'ancienne remise puis refait la nouvelle, six mouvements à la suite. Sans
transaction, un incident au milieu laisse un état que **relancer aggrave** —
la correction défait une seconde fois. Une étape qui inverse puis refait est
la plus exposée des trois règles à la fois.

### La cinquième étape — le retour reçu, 2026-09-07

D8 avait couvert **quatre** étapes. Le **retour reçu par le marchand** n'en
faisait pas partie, et il portait les trois défauts en entier — découvert en
chiffrant la question 6 de D2, qui vient s'écrire exactement là.

L'annulation était la plus grave : elle n'inversait **rien**. Elle supprimait
l'événement, reculait le statut, et laissait le marchand débité de son frais de
retour, le livreur payé de sa course, le transporteur créditeur. Or la séquence
*réception → annulation → réception* tient en deux clics dans le back-office :

| | Solde du marchand |
|---|---|
| Retour reçu | − 500 |
| Annulé | − 500 *(inchangé)* |
| Reçu à nouveau | **− 1 000** |

Deux règles posées à cette occasion, toutes deux tenues par un test :

- **on inverse, on n'efface pas** — chaque mouvement reçoit sa contrepartie de
  sens opposé, comme `parcelDeliveredCancel`. Supprimer les lignes aurait rendu
  le relevé illisible pour qui cherche à comprendre après coup ;
- **on rend ce qui a été prélevé, pas ce qu'on recalculerait.** Le montant vient
  de `parcels.return_charges`, écrit au moment du retour.
  `merchants.return_charges` est un **pourcentage** du tarif de livraison, qui
  peut avoir changé entre-temps : réverser un montant recalculé laisserait un
  résidu au marchand — précisément le défaut des 14,40 F de D9, sur une autre
  étape.

Le frais est aussi **effacé du colis** à l'annulation. Le relevé rassemble les
colis en retour **par statut**, et `RETURN_ASSIGN_TO_MERCHANT` — là où
l'annulation les renvoie — en fait partie : laisser le montant en place aurait
rendu l'argent au solde pour le reprendre au relevé suivant.

**Neuf tests**, tous rouges sur le code d'origine avant de passer.

**Et le passé.** Le correctif arrête l'hémorragie, il ne rend pas ce qui a déjà
été prélevé. `php artisan beninlink:retours-annules` s'en charge, sur le modèle
des deux régularisations existantes : constat par défaut, écriture sur
`--corriger`, refus en production sans `--force`.

Un cas se reconnaît sans deviner. Les écritures de retour portent une `note` qui
est **la clé de traduction elle-même** — `statementNote.return_received_by_merchant_statment`
n'existe dans aucun fichier de langue, donc `__()` rend la clé, identique en
français comme en anglais. C'est ce marqueur stable qui permet d'isoler les
lignes de retour parmi les autres mouvements d'un colis (une livraison partielle
peut précéder un retour). Pour chaque colis :

    prélevé = Σ(dépenses) − Σ(recettes)   sur les lignes de retour du colis
    dû      = frais du colis si le retour tient encore, 0 sinon

La différence est ce qu'il faut rendre — et la formule couvre les deux dégâts
d'un coup : l'annulation jamais inversée (dû = 0, tout revient) comme le double
prélèvement (dû = un frais, le second revient).

| Point | Choix |
|---|---|
| Montant rendu | celui **prélevé**, jamais recalculé — `merchants.return_charges` est un pourcentage qui a pu changer depuis |
| Frais résiduel | remis à zéro sur le colis : sans quoi le relevé suivant le refacture |
| Relevé déjà émis | **montré, jamais touché**. Le relevé a facturé le retour ; rendre l'argent au solde sans rien dire du document mettrait les deux en désaccord — l'invariant de D9. Un avoir se décide avec l'expert-comptable |
| Trace | des contreparties, pas des suppressions : les deux lignes coexistent |

**Dix tests**, qui partent du **dégât d'origine réinjecté** — l'événement
supprimé, le statut reculé, l'argent en place. Une fois le code corrigé, c'est le
seul moyen honnête de vérifier une reprise du passé : on ne peut plus produire le
dégât en appelant l'étape.

## D9 — Le solde d'un marchand est un cache ; le relevé est la vérité ✅

**Constat.** `merchants.current_balance` et `merchant_statements` doivent
toujours se répondre :

    current_balance = opening_balance + Σ(recettes) − Σ(dépenses)

L'annulation d'une livraison partielle a cassé cet invariant : elle créditait
le solde de la TVA **recalculée** sur le montant d'origine alors que sa propre
ligne de relevé portait la TVA réellement prélevée — 14,40 F par colis sur le
jeu de recette, définitivement. Le correctif du 2026-09-05 arrête l'hémorragie,
pas le passé.

**Décision.** En cas de désaccord, **le relevé a raison**. Le solde n'est qu'un
cache : le réaligner ne demande aucune écriture, et lui en ajouter une rendrait
le relevé faux à son tour.

`php artisan beninlink:ecarts-marchands` rapproche les deux, marchand par
marchand.

| Point | Choix |
|---|---|
| Correction | seulement les écarts **entièrement expliqués** par les annulations de livraisons partielles, au centime près |
| Écarts inexpliqués | montrés, jamais corrigés — réaligner rendrait au marchand un argent qui lui a peut-être été réellement versé |
| Trace | aucune ligne de relevé n'accompagne la correction : le relevé était juste |
| Affichage | **au centime**, contrairement à la règle XOF entier du reste du projet : c'est la décimale qui identifie l'écart |

**Deux autres chemins cassaient le même invariant**, tranchés depuis (**D10**) :
le module de paiement en ligne est coupé, et corriger un solde d'ouverture
déplace désormais le solde courant du même écart au lieu de l'écraser. La
commande continue de les nommer dans sa sortie : une base antérieure aux
correctifs porte encore leurs écarts.

**Découvert en écrivant le rapprochement.** Les **treize** écritures de
`MerchantStatement` du cycle de vie d'un colis ne renseignaient pas
`merchant_id` — la colonne que lit l'écran « Mes relevés », côté panneau comme
côté API. Le relevé du marchand ne montrait donc **aucune** ligne de livraison :
son solde bougeait sans rien pour l'expliquer. Corrigé, et rattrapé en base par
la migration `2026_09_05_140000` : contrairement aux lignes de `settings`
orphelines (**D-A**), l'attribution est certaine — chaque ligne porte son
`parcel_id`, et un colis a un seul marchand.

## D10 — Les deux derniers chemins qui cassaient l'invariant ✅

Le rapprochement de **D9** nommait deux écarts qu'il ne savait pas expliquer.
Voici ce qu'ils étaient, et ce qu'on en a fait.

### 1. Le module « payout / paiement en ligne » — **coupé**

Cinq chemins (Stripe, PayPal, bKash, Skrill, Razorpay) réglaient un marchand,
ou l'encaissaient, hors du flux de retrait. En ouvrant le dossier, ils
partagent quatre défauts :

| Défaut | Conséquence |
|---|---|
| Aucune écriture de relevé | l'écart de D9 : le solde bouge, le relevé ne dit rien |
| Devise **BDT** codée en dur | un transporteur béninois débiterait des takas |
| `Merchant::find()` / `Account::find()` **sans scope** | un marchand crédite le compte bancaire d'une autre société |
| **PayPal et Razorpay ne vérifient rien** | une requête avec un identifiant de transaction inventé éteint la dette du marchand et crédite le transporteur d'un argent jamais reçu |

Le dernier point est le plus grave, et c'est l'exact opposé de la règle du
projet : *webhook signé = seule source de vérité*. Razorpay est en outre cassé
depuis toujours (`accountId` contre `account_id`) — et il échoue **après** avoir
déplacé le solde du marchand.

**Décision : couper le module**, comme S21 avait coupé Aamarpay et SSLCommerz.
Corriger cinq chemins qu'aucun transporteur béninois n'utilisera n'a pas de
sens ; les couper ferme l'écart comptable, la fuite inter-locataires et la
fraude d'un seul geste. Le reversement au marchand passe par la demande de
retrait (`MerchantManage\Payment`), couverte et scopée depuis **D8**.

**Portée volontairement étroite.** On coupe le **module**, pas les passerelles :
`config('payments.online_payout')`, lu par `onlinePayoutEnabled()`, distinct de
`gatewayEnabled()`. La raison est concrète : `stripe_status` sert **aussi**
l'abonnement SaaS de la plateforme, qui ne touche aucun solde marchand. Couper
par identité de passerelle l'aurait emporté avec, et rien ne le justifiait. Un
test fixe cette frontière.

Pour rouvrir : `online_payout => true` **et** les quatre défauts corrigés. Le
code est resté en place.

### 2. Le solde d'ouverture qui écrasait le solde courant — **corrigé**

`MerchantRepository::update()` écrivait `current_balance = opening_balance` à
chaque enregistrement de la fiche. Ré-enregistrer un marchand pour corriger son
adresse effaçait donc tout ce qui s'était accumulé depuis son ouverture —
encaissements, frais, retraits — sans avertissement et sans trace au relevé.

**Décision.** L'invariant de D9 commande la règle : corriger le solde
d'ouverture déplace le solde courant du **même écart**.

    current_balance += (ouverture_voulue − ouverture_actuelle)

Un enregistrement qui ne touche pas à l'ouverture ne touche à rien. On corrige
une saisie, on n'efface pas une activité.

---

## D11 — Les notifications poussées passent par Expo, pas par FCM en direct ✅

**Constat.** Le socle poussait par l'API FCM « legacy »
(`fcm.googleapis.com/fcm/send`, en-tête `Authorization: key=…`), **arrêtée par
Google le 20 juin 2024**. Conséquence : le fil du marchand s'écrivait bien en
base, mais **rien n'arrivait sur le téléphone** — et les quinze appels de
`ParcelRepository` payaient un aller-retour HTTPS bloquant pour recevoir un
refus. Pire, `sendPushNotification()` faisait `die()` sur échec cURL : une
coupure réseau tuait la requête métier en cours.

Le livreur était le plus mal servi : il n'a **pas de fil consultable**, la
poussée est son seul canal. Une course affectée ne le prévenait pas.

**Décision : transporter par le service de push d'Expo.**

| Question | Réponse, et pourquoi |
|---|---|
| Pourquoi pas FCM HTTP v1 ? | Il exige un **compte de service par société** (fichier JSON, rotation, stockage) et un `google-services.json` dans chaque app. Expo relaie vers FCM et APNs avec **ses** identifiants : le backend ne porte aucun secret de push — décisif en multi-tenant. |
| Et si un client l'exige un jour ? | `config('push.driver')` choisit le pilote ; le reste du code ne connaît que l'interface `PushGateway`. Ajouter FCM v1, c'est une classe de plus, pas une reprise. |
| Où sont les appareils ? | Table `device_tokens` : une ligne par appareil (un marchand a téléphone **et** tablette), `company_id` pour le scope. `users.device_token`, la colonne du socle, n'a jamais été écrite par aucun code — elle reste inutilisée. |
| Qui décide du destinataire ? | Le **compte authentifié**, jamais la requête. C'est la suite de S11 : le socle laissait s'abonner aux notifications d'autrui en connaissant son adresse e-mail. |

**Une seule notification par événement.** Le fil marchand
(`MerchantNotification`) pousse désormais par un canal ajouté à `via()` — les
six familles d'événements suivent **sans qu'aucun émetteur change**. Le chemin
du socle (`PushNotificationService`), lui, ne sert plus que les **non-marchands**
(livreur, ramasseur, hub) : servir le marchand des deux côtés lui vaudrait deux
notifications pour un fait.

**Périmètre du destinataire.** Il vient de l'objet notifié (le colis, le
message), **jamais de `settings()`** : les adresses e-mail ne sont pas uniques
en base, et `settings()` retombe sur la société 1 hors requête locataire — une
commande console aurait poussé au mauvais compte.

**Ce qui reste hors du push.** Un envoi part encore **dans la requête HTTP**
(`QUEUE_CONNECTION=sync`), avec un délai d'attente de 5 s ; c'est le constat 7
de la cartographie, valable pour le SMS comme pour le push, et il n'est pas
aggravé ici — l'appel remplace un aller-retour vers une API morte. Le back-office
garde par ailleurs son push **navigateur** (`users.web_token`), toujours branché
sur l'API arrêtée : il ne concerne aucune app mobile et attend son propre
chantier.

---

## D12 — Le push navigateur du back-office est retiré ✅

**D11** a rebranché les deux apps mobiles. Restait le canal du back-office, et
en ouvrant le dossier il s'est révélé pire que mort.

| Ce qu'il faisait | Pourquoi c'est un problème |
|---|---|
| Inscrivait le navigateur de chaque agent au projet Firebase **de l'éditeur** (`we-courier-81101`) | Les jetons sont émis par un projet que le transporteur ne contrôle pas ; qui détient la clé serveur de ce projet peut notifier les agents de **toutes** les installations We Courier |
| Chargeait le SDK Firebase depuis `gstatic.com` **sur chaque page** du back-office | Un appel tiers et ~150 Ko à chaque chargement, pour une fonction qui ne marchait pas |
| Appelait `requestPermission()` à chaque chargement | Une demande de permission système réclamée pour un canal incapable de livrer |
| Envoyait par l'API FCM **legacy** | Arrêtée par Google en juin 2024 (le même constat que D11) |
| Affichait, en arrière-plan, « Background Message Title » avec le logo de l'éditeur | Le service worker n'avait jamais été personnalisé |

**Décision : retirer le module**, comme D10 pour le paiement en ligne et S21
pour deux passerelles. Le rebrancher aurait demandé, au minimum, un projet
Firebase appartenant au transporteur (FCM v1) ou du Web Push standard (VAPID,
donc une dépendance de chiffrement) — pour un canal dont le seul contenu réel
est le message qu'un administrateur écrit à ses propres collègues, déjà
enregistré et visible dans le back-office. La branche « nouveau colis » de ce
transport, elle, n'était appelée de nulle part.

**Le service worker se retire lui-même.** C'est le point qu'on aurait pu
manquer : un service worker déjà installé **survit** au déploiement. Retirer le
code de la page n'aurait pas désinscrit les navigateurs des agents. Le fichier
garde donc son chemin — c'est celui qu'ils interrogent — et ne fait plus que se
désinscrire avant de recharger les onglets ouverts. À supprimer une fois le
parc renouvelé.

**Ce qui a été purgé, et ce qui reste.** Les valeurs de `users.web_token` sont
supprimées par migration : ce sont des jetons d'un projet tiers, pour un canal
retiré. La colonne reste, elle servira à qui rebranchera.

**Pour rouvrir**, il faut les deux : un transport vivant (FCM HTTP v1 ou Web
Push VAPID) **et** un projet maîtrisé par le transporteur. Les apps marchand
et livreur, elles, sont servies depuis D11 — c'est là que la notification
compte, et c'est là qu'elle arrive.

**Deux défauts trouvés en chemin, dans le même fichier, corrigés.**
`PushNotificationRepository::store()` faisait `dd($exception)` : un échec
d'acheminement vidait la pile d'exécution dans le navigateur de
l'administrateur au milieu de son formulaire, et interrompait la requête. Et
`update()` lisait le message par `find($id)` **sans scope** — un administrateur
modifiait le message d'un autre transporteur (la suppression, elle, vérifiait
déjà la société). Les deux sont couverts par des tests.

---

## D13 — Les envois quittent la requête HTTP ✅

**Constat 7 de la cartographie**, laissé ouvert par D11 et D12 : tout partait
`sync`, c'est-à-dire **dans la requête de l'agent**.

Le chiffre qui décide : `SmsService::reveSms()` pose un `CURLOPT_TIMEOUT` de
**80 secondes**, et un changement de statut de colis déclenche jusqu'à **deux**
SMS (le livreur et le marchand). Un opérateur lent faisait donc attendre, à un
clic, l'agent qui n'a rien à voir avec l'acheminement du message. Le push (5 s)
et les courriels s'ajoutaient par-dessus.

**Décision : mettre en file, sans toucher aux appelants.**

| Ce qui change | Où |
|---|---|
| SMS | `SmsService::sendSms()` / `sendOtp()` gardent leurs **28 appels** ; ils mettent en file. La livraison vit dans `deliverSms()` / `deliverOtp()`, appelées par `App\Jobs\SendSms` |
| Push | `PushChannel` (le fil) et `PushNotificationService` (le chemin du socle) passent par `App\Jobs\SendPush` |
| Courriels | `ContactMail`, `MerchantSignup`, `CompanySignup` implémentent `ShouldQueue` |
| Pilote | `QUEUE_CONNECTION=database` : un VPS mutualisé n'a que sa base, et le volume d'un transporteur béninois tient dans une table |

**Le vrai risque du changement : le locataire.** Un job s'exécute **hors
requête**, où `settings()` retombe sur la société 1. Un SMS mis en file sans
précaution serait donc parti avec le nom commercial ET les identifiants
d'opérateur d'un **autre** transporteur — facturés à lui. C'est le constat F4,
que `forCompany()` avait fermé côté requête ; le sortir de la requête le
rouvrait. La société est donc **résolue à la mise en file** et voyage avec le
job ; les trois mailables figent de même leur expéditeur à la construction.

**La garantie qui rend le changement sûr.** En `QUEUE_CONNECTION=sync`, un job
s'exécute immédiatement : une installation sans worker se comporte
**exactement** comme avant. Le passage à `database` est un choix de
déploiement, pas une rupture de code.

**La panne que la file introduit, et son témoin.** Si le worker s'arrête,
l'application répond normalement, les colis avancent, les écrans sont justes —
et plus aucun SMS ne part. Personne ne le voit avant qu'un client se plaigne.
D'où `php artisan beninlink:file-attente` : en attente, âge du plus ancien,
échoués ; sortie 1 au-delà du seuil, de quoi la brancher sur une alerte. Le
guide d'infra fournit l'unité superviseur (corrigée au passage : elle visait
`queue:work redis`, que l'application n'utilise pas — le worker n'aurait rien
traité) et un repli cron pour un hébergement sans superviseur.

**Le modèle d'environnement disait le contraire — corrigé le 2026-09-07.**
`docs/guides/infra/.env.example` déclarait `QUEUE_CONNECTION=redis`,
`SESSION_DRIVER=redis` et `CACHE_STORE=redis`. Une installation montée dessus
aurait exigé `php8.3-redis` et un serveur Redis que rien n'utilise :
`RedisTenancyBootstrapper` est commenté dans `config/tenancy.php`, aucun paquet
`predis` n'est installé, et `web/.env.example` — le socle — dit `file`,
`database`, `file`. La troisième ligne ne faisait même rien : Laravel 10 lit
`CACHE_DRIVER`, pas `CACHE_STORE` (nom apparu en Laravel 11), donc le cache
retombait sur `file` en silence. Le modèle suit désormais cette décision.

**Ce qui restait inline, et ce qu'il en est advenu.** `InvoicePDFSend` : son
expéditeur était codé en dur (`admin@example.com`) et le PDF voyageait dans la
charge du job. S69 l'a rendu juste (expéditeur = marque du destinataire, en
file, PDF rendu à la sortie de la file), et **S75 (R9) l'a branché** : chaque
relevé émis part au courriel du compte marchand par `StatementMailer`, hors
transaction, avec le PDF de `SettlementPdf` — le même que le téléchargement.

## D14 — Décisions produit R3 à R9 ✅ (2026-10-03, S75)

Sept points de `docs/CARTOGRAPHIE_PROJET.md` § 7.1 tranchés en une séance par le
porteur ; cinq ont du code, deux n'en ont pas et le disent.

| # | Décision | Code |
|---|---|---|
| R3 | **Pas de prorata** au renouvellement : `switchPlan()` repart de la date du jour, le reliquat n'est pas reporté. La règle est **dite avant la confirmation**, sur l'écran des plans du locataire et sur celui du super-administrateur | `levels.plan_switch_notice` dans les deux vues ; `PlanSwitchNoticeTest` tient le texte **et** l'absence de calcul de crédit |
| R4 | SMS en **français seul** pour le pilote. Pas de colonne de langue, pas de gabarits traduits ; la couture `SmsTemplate::locale()` reste pour l'expansion anglophone (Nigeria, Ghana) | aucun |
| R5 | Menu Réglages visible dès qu'**une** entrée est accessible (OU sur les quatorze droits que lisent ses entrées). Les gardes d'écriture des routes ne bougent pas | `sidebar.blade.php` ; `SettingsMenuGuardTest` compare la garde aux droits du sous-menu, et vérifie qu'un lecteur seul voit le menu sans pouvoir écrire |
| R6 | Catalogue des **catégories** (`categorys`, sans `company_id`) réservé au **super-administrateur**, comme les devises (S55) : routes sous `super-admin/category`, `panel:super-admin`, droit retiré des semences du locataire, migration pour les super-admins existants. Les six catégories de livraison d'amorçage restent | `routes/superadmin.php`, migration `2026_10_03_110000`, `CategoryPanelScopeTest` |
| R7 a | Hors Bénin : taux par société, configurable (D1), défaut 18 % | aucun |
| R7 b | **Exonéré ≠ non renseigné** : `merchants.vat_status` (`unset` / `taxable` / `exempt`, `App\Enums\VatStatus`). `exempt` → 0 et le relevé l'imprime ; `unset` → taux société ; un taux saisi avant devient `taxable` (même comportement) ; **personne** n'est reclassé exonéré par migration | `VatRate`, formulaire marchand, `SettlementStatement` / PDF, `MerchantVatStatusTest` |
| R8 | Signature du destinataire sur un retour : ~~reporté~~ **livré en S106 (2026-10-06), facultative** — même champ que la livraison, stockée sur l'événement de retour, lue par le marchand ; L6 de la recette décide du maintien à l'écran | `ParcelRepository::returntoQourier()`, `reportOutcome()` de l'app, `DeliveryProofTest` |
| R9 | **Relevés par courriel** : à l'émission de chaque relevé, en file (D13), au courriel du compte marchand, avec **le** PDF officiel (`SettlementPdf`, point de rendu unique). **Jamais de réémission modifiée** (D8) : aucune route n'écrit sur un relevé, un test l'affirme. Délivrabilité : file active à surveiller (`beninlink:file-attente`). Opposabilité juridique du PDF : dossier de l'expert-comptable (R2) | `StatementMailer`, `SettlementPdf`, `InvoiceRepository::store()`, `StatementEmailTest` |

Hors de ce lot, et volontairement : le prorata d'abonnement, la colonne de langue
des SMS, la personnalisation des catalogues par société, la signature sur retour.

---

## D15 — Dette technique : T2, T3 et T9 tranchés ✅ (2026-10-06, S107)

Trois lignes de dette technique attendaient une décision dans le registre (§ 7.2).
Le porteur a rendu la main. La règle d'or du projet — **appropriation, pas
réécriture ; ne jamais modifier le cœur du socle de façon invasive** — tranche les
trois dans le même sens : on ne refait pas ce qui marche, on le **tient**.

| # | Sujet | Décision | Ce qui la tient |
|---|---|---|---|
| T2 | Colonnes monétaires en `decimal(…,2)` | **Pas de migration de schéma.** Le FCFA est entier **par calcul** (`ChargeCalculator::percentage()` arrondit, seul endroit où un taux devient des francs) et **par affichage** (`formatAmount()`, zéro décimale) ; la base peut porter deux décimales sans qu'un document ne les montre jamais. Changer une quinzaine de colonnes du socle serait invasif, compliquerait T9, et une migration MySQL interrompue ne se défait pas. | `VatRoundingTest` ; **`beninlink:montants-non-entiers`** constate dans une base vivante les montants qui auraient contourné le calcul (import, saisie directe) — rien d'écrit, sortie 0, quatrième constat du guide de reprise § 8 (`NonIntegerAmountsTest`) |
| T3 | Bootstrap 4 **et** 5 ensemble | **Les étapes A et B suffisent ; C, D, E ne sont pas entreprises.** Les deux versions cohabitent à dessein (5 câble `data-bs-*`, 4 câble `data-*`) et rien ne casse ; renommer 331 attributs et 26 classes change l'apparence de tout le back-office pour aucun gain au pilote. | `bootstrap/migration-4-vers-5.md` § 5 ; à rouvrir après la recette, avec le contrôle visuel humain (E5), si un défaut d'écran le demande |
| T9 | Re-fusion We Courier | **On ne monte pas.** Le fork est le produit : 106 lots, 32 constats de sécurité fermés, paiement en ligne coupé (D10). Une montée ne se justifie que par un correctif de l'éditeur **qui nous manque** sur ce que nous utilisons. | `socle/mise-a-jour-we-courier.md` : base `eb56a37`, fusion à trois points, suite avant/après, recette — la méthode reste prête, la grille « faut-il monter ? » se relit à chaque version de l'éditeur |

**Ce que D15 ne décide pas.** T4 (PHP 8.4 fermé par trois dépendances) n'est pas une
décision mais une attente : il s'ouvrira quand `league/commonmark` et ses
dépendances le permettront.

## D16 — Comptes de paiement : à quel opérateur béninois correspondent les codes 3, 4, 5 ? 🔁 (relevé S116, défaut réversible S117)

Un compte de paiement (`accounts.gateway`) porte un code : 1 espèces, 2 banque, et **3, 4, 5**
que le socle nomme **bKash, Rocket, Nagad** — les portefeuilles mobiles du Bangladesh. Le
formulaire de création d'un compte (`backend/account/create`) les propose encore sous ces noms,
et les écrans qui les affichent les écrivaient en dur.

**Ce que S116 a fait, sans rien décider** : côté marchand, les paiements reçus disent « Mobile
Money » pour les trois codes. C'est vrai quel que soit l'opérateur, et rien n'est réécrit en base.

**Ce qui reste au porteur** : attribuer chaque code à un opérateur. Proposition, alignée sur les
semences (S92, `MTN MoMo` / `Moov Money`, les deux opérateurs servis par FedaPay) :

| Code | Socle | Proposé |
|---|---|---|
| 3 | bKash | **MTN MoMo** |
| 4 | Rocket | **Moov Money** |
| 5 | Nagad | à préciser (troisième opérateur, ou code retiré du formulaire) |

Une fois tranché : libellés du formulaire de compte et des écrans du back-office (`merchantmanage/
payment/*`), clés `merchant.mtn_momo` / `merchant.moov_money` déjà présentes dans `lang/fr/merchant.php`.

**Ce que S117 a fait — défaut réversible, comme R1, R2 et R8 (S106).** Le back-office ne pouvait pas
attendre : un compte du transporteur se créait en choisissant bKash, Rocket ou Nagad, et sa banque
(`accounts.bank`, autre code entier) parmi **BB, DBBL, IB** — trois banques du Bangladesh. Les
libellés vivent désormais en **un seul endroit**, et tous les écrans les lisent :

| Fichier | Codes |
|---|---|
| `lang/fr/account_gateway.php` | 1 Espèces · 2 Banque · **3 MTN MoMo** · **4 Moov Money** · 5 Autre Mobile Money |
| `lang/fr/account_bank.php` | 1 Ecobank Bénin · 2 BOA-Bénin · 3 Orabank Bénin · 4 NSIA Banque Bénin · 5 UBA Bénin · 6 Coris Bank Bénin · 7 BIIC · 8 Autre banque |

Rien n'est réécrit en base. ⚠️ Un compte **créé avant S117** avec la banque « BB », « DBBL » ou « IB »
s'affiche maintenant sous le nom béninois du même code : le transporteur relit la banque de ses
comptes (Comptes → modifier) — sur une installation béninoise, le libellé d'origine n'a jamais été vrai.
Le panneau marchand garde « Mobile Money » (S116), vrai quel que soit l'opérateur.
**Pour corriger** : changer la ligne du fichier, et sa jumelle `lang/en/` ; `BackOfficeSpeaksFrenchTest`
fixe les valeurs, on le change avec la décision.

