# Décisions métier — BeninLink

> Registre des décisions qui ne relèvent pas du code seul. Chaque entrée dit ce
> qui a été **tranché** (et livré), ce qui reste **à trancher** par le métier, et
> par qui. Mis à jour le 2026-09-06.

| # | Sujet | État | Livré dans le code | Reste à trancher |
|---|---|---|---|---|
| D1 | TVA au niveau entreprise | ✅ tranché | taux société `configs.vat_rate` (18 %), surcharge par marchand | valeur par société hors Bénin, exonérations |
| D2 | Plan de comptes SYSCOHADA | ⏳ à valider | config, export journal, **extrait de période**, auxiliaires par marchand, **fiche de validation** | signature de l'expert-comptable sur `docs/guides/comptabilite/plan-de-comptes.md` |
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

**Ce qui a été fait le 2026-09-06, sans attendre l'arbitrage** — parce qu'on ne
valide pas un plan de comptes sur du papier :

- **Fiche de validation** : `docs/guides/comptabilite/plan-de-comptes.md` — les
  trois écritures avec un exemple chiffré réel, les cinq questions ci-dessous
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

Tant que ce n'est pas validé, l'export sert à la revue, pas à l'import comptable.
Ce qui manque n'est plus du logiciel : c'est une signature au bas de la fiche.

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

**Ce qui reste inline, et pourquoi.** `InvoicePDFSend` : son expéditeur est
codé en dur (`admin@example.com`) et le PDF voyagerait dans la charge du job.
Le corriger est un chantier à part, pas un effet de bord de celui-ci.

