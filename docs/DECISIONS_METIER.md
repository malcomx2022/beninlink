# Décisions métier — BeninLink

> Registre des décisions qui ne relèvent pas du code seul. Chaque entrée dit ce
> qui a été **tranché** (et livré), ce qui reste **à trancher** par le métier, et
> par qui. Mis à jour le 2026-09-05.

| # | Sujet | État | Livré dans le code | Reste à trancher |
|---|---|---|---|---|
| D1 | TVA au niveau entreprise | ✅ tranché | taux société `configs.vat_rate` (18 %), surcharge par marchand | valeur par société hors Bénin, exonérations |
| D2 | Plan de comptes SYSCOHADA | ⏳ à valider | proposition dans `config/syscohada.php`, export journal | validation par l'expert-comptable (questions ci-dessous) |
| D3 | Dépenses d'acquisition (CAC) | ✅ tranché | chapitre « Marketing et acquisition clients » | discipline de saisie mensuelle |
| D4 | Refonte du barème (zones, tranches) | ⏳ à décider | tranches « jusqu'à N kg » sans schéma (S9) | zones béninoises, grille tarifaire, migration |
| D5 | Fiches de fraude sans `company_id` | ✅ tranché | migration de rattachement par l'auteur | — |

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

## D2 — Plan de comptes SYSCOHADA ⏳

**Ce qui existe.** `config/syscohada.php` propose : 4111 Clients, 7061 Prestations de
services de livraison, 4431 TVA facturée, 4712 Créditeurs divers (COD encaissé pour
compte de marchands), 521 Banques ; journaux VE, OD, BQ. L'export journal
(`SyscohadaJournal`) est équilibré par construction et testé.

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

Tant que ce n'est pas validé, l'export sert à la revue, pas à l'import comptable.

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

## D4 — Refonte du barème de livraison ⏳

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
politique CEDEAO (tarif au pays ou forfait).

Aucun code tant que la grille n'est pas fixée : la structure actuelle rend juste
ce que le barème contient.

## D5 — Fiches de fraude sans `company_id` ✅

**Constat.** Avant S7, le panneau marchand créait les fiches de fraude sans
`company_id` ; depuis S7 la liste noire est scopée société, donc ces fiches en
sortaient.

**Décision.** Migration `2026_09_05_120000` : chaque fiche orpheline reçoit la
société de son auteur (`created_by` → `users.company_id`). Une fiche sans auteur
reste orpheline (elle n'a pas d'origine à laquelle la rattacher) ; aucune dans le
seed.
