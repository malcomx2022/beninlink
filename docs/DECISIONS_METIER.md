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

