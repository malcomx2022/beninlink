# Refonte du barème — étude d'impact et grille à arrêter

> Décision **D4**. Ce document sert à **décider**, puis à exécuter sans surprise.
> La partie 1 est pour le métier (les prix) ; les parties 2 et 3 sont pour qui
> écrira le code. Établi le 2026-09-06.

## 0. Ce qui coince, en une phrase

Une ligne de barème a **quatre colonnes** — `same_day`, `next_day`, `sub_city`,
`outside_city` — qui mélangent un **délai** (jour même, lendemain) et un
**périmètre** (périphérie, intérieur). On ne peut donc pas demander « lendemain,
à l'intérieur du pays » : le barème n'a pas de case pour ça. Un test le montre
plutôt que de l'affirmer : `DeliveryPricingBaselineTest`.

Les **frais COD**, eux, ont trois zones (`inside_city`, `sub_city`,
`outside_city`) — un troisième découpage, encore différent.

## 1. La grille à arrêter (métier)

> **Tranché le 2026-09-06** : zones **Cotonou / Périphérie / Intérieur / CEDEAO** ;
> **délai global** avec un supplément par délai ; **CEDEAO au forfait par pays** ;
> **les tarifs et les taux COD actuels sont conservés**, avec un supplément
> « jour même » de **300 F**. Le schéma, le résolveur, la conversion et le jeu
> de recette sont livrés. Restent les **forfaits CEDEAO par pays** et le **taux
> COD de cette zone** — un encaissement à l'étranger n'a jamais été tarifé.

### 1.1 Zones

Proposition minimale pour le Bénin. Corriger les libellés, ajouter ou retirer.

| Code | Libellé | Contenu |
|---|---|---|
| `cotonou` | Cotonou | intra-muros |
| `peripherie` | Périphérie | Abomey-Calavi, Sèmè-Kpodji, Ouidah… |
| `interieur` | Intérieur | Porto-Novo, Parakou, Bohicon, reste du pays |
| `cedeao` | CEDEAO | export régional |

### 1.2 Délais

Le délai devient un axe **séparé** : jour même / lendemain / standard. Question
au métier : **le même délai est-il proposé partout** (donc un délai global), ou
faut-il un délai par zone (pas de « jour même » vers Parakou, par exemple) ?

### 1.3 Grille tarifaire cible

À remplir en FCFA entiers. La colonne « aujourd'hui » rappelle le tarif en
vigueur dans le jeu pilote, pour servir de point de départ — pas de référence.

**Depuis S72 (2026-10-03), ce point de départ est un fichier** :
`web/database/bareme/grille-nationale.csv`, une ligne par tranche, une colonne
par zone nationale. `php artisan beninlink:zones-tarifaires --installer
--grille=database/bareme/grille-nationale.csv` le pose ; le jeu pilote lit le
même fichier. Décision du porteur : **les montants ne sont pas figés** — le
transporteur les réajuste à tout moment dans *Réglages → Zones et barème*, et la
commande, relancée à chaque déploiement, **ne réécrit jamais** une ligne déjà
en base. Modifier le fichier ne change donc que ce qui n'a pas encore été posé.

| Tranche | Aujourd'hui (jour même / lendemain / périphérie / intérieur) | Cotonou | Périphérie | Intérieur | CEDEAO |
|---|---|---|---|---|---|
| jusqu'à 1 kg | 1 000 / 800 / 1 500 / 2 500 | | | | |
| jusqu'à 3 kg | 1 500 / 1 200 / 2 000 / 3 500 | | | | |
| jusqu'à 5 kg | 2 000 / 1 700 / 2 800 / 4 500 | | | | |
| jusqu'à 10 kg | 3 000 / 2 500 / 4 000 / 6 500 | | | | |
| au-delà de 10 kg | la tranche 10 kg s'applique | | | | |

Si le délai est tarifé, ajouter un supplément par délai (« jour même : +500 F »)
plutôt que de multiplier les colonnes — c'est ce qui a produit le mélange actuel.

### 1.4 CEDEAO

Un **forfait** par pays, ou un tarif au poids comme le reste ? Et la TVA : le
transport export est-il taxé au même taux ? (Le taux est déjà réglable par
société et par marchand — **D1**.)

**Réponse (S106, 2026-10-06).** Un **forfait par pays**, sans regarder le poids
(D4). Huit pays posés comme point de départ, ajustables à l'écran : Togo 12 000,
Ghana 15 000, Burkina Faso 15 000, Niger 18 000, Nigeria 18 000, Côte d'Ivoire
20 000, Mali 22 000, Sénégal 25 000 F. La TVA à l'export reste au taux du marchand
(18 % ou exonéré, D1/R7) ; une exonération propre au transport international
relève de l'expert-comptable.

### 1.5 Frais COD

Aujourd'hui trois zones propres (`inside_city`, `sub_city`, `outside_city`),
en pourcentage. La refonte les aligne sur les codes de zones ci-dessus. Le
métier confirme les taux par zone.

## 2. Ce que la refonte touche (étude d'impact)

**274 occurrences** des quatre colonnes, dans 25 fichiers :

| Où | Occurrences | Nature |
|---|---|---|
| `resources/views` | 120 | écrans d'administration et panneau marchand (création, édition, listes, tarifs publics) |
| `database` | 57 | migrations et seeders |
| `app/Repositories` | 41 | écriture des barèmes, société et marchand |
| `app/Http/Requests` | 24 | validation des formulaires |
| `app/Models` | 13 | `$fillable`, accesseurs |
| `app/Services` | 11 | **la lecture**, déjà centralisée |
| `app/Http/Resources` | 4 | contrat de l'API mobile |
| `app/Http/Controllers` | 2 | AJAX de devis |

**La bonne nouvelle** : la *lecture* est déjà passée par un seul point,
`DeliveryChargeResolver` (S8/S9), avec une table `COLUMNS` de quatre lignes.
Le calcul, les deux AJAX de devis et l'import CSV en dépendent tous. Changer
la lecture, c'est changer cette table.

**Le vrai coût** est ailleurs : les 120 occurrences dans les vues et les 24 en
validation, c'est-à-dire les **écrans de saisie du barème**, qui passent de
quatre colonnes fixes à un nombre de zones variable.

### Contrat mobile

`DeliveryChargeResource` expose les quatre clés telles quelles ; `mobile/`
les lit dans `src/api/types.ts` et les affiche dans l'écran « Tarifs »
(`app/(app)/rates.tsx`, colonnes déclarées en dur). Le changement suit donc
l'ordre du projet : **`web/` d'abord**, spec OpenAPI régénérée, puis l'app.
Prévoir une période où l'API sert les deux formes, sinon un APK déjà installé
affiche une grille vide.

## 3. Exécution proposée

| Étape | Contenu | Réversible ? |
|---|---|---|
| 1 | Table `delivery_zones` (société, code, libellé, position), seedée avec les zones arrêtées en 1.1 | oui, table additive |
| 2 | `delivery_charges` et `merchant_delivery_charges` gagnent `zone_id` ; chaque ligne actuelle devient **une ligne par zone**, aux montants d'aujourd'hui | oui, les colonnes restent |
| 3 | `DeliveryChargeResolver` lit `zone_id` au lieu de la colonne ; l'étalon `DeliveryPricingBaselineTest` doit rester vert **sans être modifié** | oui |
| 4 | Écrans de saisie : une ligne par zone, ajout/retrait dynamique — **livré** (`admin/delivery-zone`) | oui, aucune écriture tant que rien n'est saisi |
| 5 | API : la ressource expose `zones[]` ; `openapi:generate` ; `mobile/` — **livré** (`mobile-livreur/` n'est pas concerné) | non (contrat), mais **additif** : les quatre colonnes restent servies |
| 5 bis | **Le colis porte une zone et un délai**, et `resolveByZone()` entre dans le calcul — **livré**, `mobile/` compris | non (migration `parcels`), mais **additive** : sans zone, le calcul est celui d'avant |
| 6 a | **Porte** : `beninlink:bareme-herite` dit ce qui dépend encore des colonnes, et sort en erreur sinon — **livré** | oui, constat seul |
| 6 b | Étalon des zones (`DeliveryZonePricingBaselineTest`), écrit pendant que les deux barèmes coexistent — **livré** | oui, test seul |
| 6 c | Suppression des quatre colonnes — **livré le 2026-09-07** | non — le basculement est **sec** : plus de repli |

Les étapes 1 à 3 n'ont changé **aucun montant** : c'est ce que l'étalon a
vérifié, et le test **pont** l'a confirmé avant que l'ancien étalon ne parte.

## 3 bis. L'étape 6, telle qu'elle a été jouée

La porte est devenue une **ceinture de sécurité dans la migration elle-même** :
elle reprend les mêmes règles en SQL nu et **refuse de retirer les colonnes**
tant qu'une société en dépend. Un déploiement qui échoue proprement vaut mieux
qu'une facturation morte en silence.

> ⚠️ **Mise à niveau en deux temps.** Le code qui lisait les colonnes part avec
> elles. Une installation non convertie reste sur la version précédente, y pose
> ses zones et sa grille, vérifie avec `beninlink:tarification-prete`, puis
> déploie celle-ci.

Ce que la bascule change pour de bon :

- un colis **sans zone n'a pas de tarif** — `UnpricedDeliveryException::sansZone()`,
  et `zone_id` devient `required` à la création ;
- l'import Excel lit une colonne **`zone_code`** ; les deux fichiers modèles de
  `public/sample-parcel/` la portent ;
- `beninlink:zones-tarifaires` n'est plus une **conversion** mais une
  **installation** : zones, délais, forfaits CEDEAO. La grille se saisit à
  l'écran — ces montants appartiennent au transporteur ;
- `beninlink:bareme-herite` devient `beninlink:tarification-prete` : la question
  « cette société peut-elle facturer ? » est désormais permanente ;
- la page tarifs publique montre **un onglet par zone**, au lieu de quatre
  onglets qui mélangeaient un délai et un périmètre.

## 4. Ce qui est déjà fait

- **L'étalon** : `tests/Feature/DeliveryPricingBaselineTest` fixait le tarif de
  neuf poids × quatre types, le devis complet TVA comprise, et le fait qu'aucune
  case ne croisait délai et périmètre. Écrit **avant** la refonte, il n'a pas
  changé d'une ligne pendant. Il est parti à l'étape 6 avec son sujet, après que
  le test **pont** de son successeur eut vérifié que les deux barèmes annonçaient
  le même prix — c'est ce qui a autorisé son retrait.
- La lecture est déjà unique (S8/S9), et le poids déjà traité en tranches
  « jusqu'à N kg ».
- **Étapes 1 à 3** : le schéma (`delivery_zones`, `delivery_delays`,
  `delivery_zone_countries`), `DeliveryChargeResolver::resolveByZone()` et la
  conversion (devenue `beninlink:zones-tarifaires`, qui **installe** désormais).
  Aucun montant déplacé, sauf le supplément « jour même » ramené à 300 F par
  décision du métier.
- **Étape 4** : les deux écrans de saisie, sous *Réglages → Zones et barème*.
  `admin/delivery-zone/index` porte les zones, les délais et les **forfaits
  CEDEAO par pays** ; `admin/delivery-zone/grid` porte la grille d'une
  catégorie, une ligne par tranche et une colonne par zone.
- **Étape 5, côté `web/`** : `GET settings/delivery-charges` sert `zones` et
  `delays` **à côté** de `deliveryCharges`, et `GET settings/cod-charges` gagne
  `zone_code`. `zones` vide = la société n'a rien configuré, l'app reste sur
  les colonnes. Spec régénérée. Reste les deux apps.
- **S72 (2026-10-03)** : la grille de départ en **un seul fichier**
  (`database/bareme/grille-nationale.csv`), posée par `--grille=` et lue par le
  jeu pilote ; lignes **créées si manquantes, jamais réécrites** — l'écran garde
  le dernier mot (`GridFileTest`).

## 5. Ce qu'il manque pour coder

Rien d'autre que les réponses de la partie 1 : **liste des zones**, **grille
tarifaire**, **délai global ou par zone**, **politique CEDEAO**, **taux COD par
zone**. Tant qu'elles ne sont pas fixées, écrire le schéma reviendrait à parier
sur elles — et à refaire la migration deux fois.

Réponses attendues de : ______________________  ·  Date : ____________
