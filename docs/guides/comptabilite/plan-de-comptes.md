# Plan de comptes SYSCOHADA — fiche de validation

> À remettre à l'expert-comptable du transporteur. Ce document sert à **arrêter**
> le paramétrage comptable de BeninLink (décision **D2**). Tout ce qui y est
> proposé est modifiable dans un fichier de configuration — jamais dans le code.
>
> Établi le 2026-09-06 · **décisions du porteur consignées le 2026-10-03 (lot S73)**
> · il reste à l'expert-comptable **trois numéros de comptes** et **quatre codes de
> journaux** à confirmer (§ 6).

## 1. Ce que le logiciel fait aujourd'hui

BeninLink émet un **relevé de règlement** par marchand : les colis livrés sur la
période, ce qui a été encaissé auprès des destinataires (COD), les frais du
transporteur, la TVA, et le net à reverser. De ce relevé, il tire trois écritures.

Exemple réel, un relevé de **trois colis** (montants en FCFA entiers). Le détail
est donné colis par colis parce que le total, seul, ne se recompose pas au taux
de 18 % sans ce détail — et c'est la première chose qu'un comptable vérifie :

| Colis | Encaissé COD | Frais HT | TVA | Taux | Frais TTC |
|---|---|---|---|---|---|
| `BL-1` livré | 50 000 | 560 | 101 | 18 % | 661 |
| `BL-2` livré | 30 000 | 700 | 126 | 18 % | 826 |
| `BL-3` **retourné** | — | 500 | **90** | 18 % | 590 |
| **Relevé n° `CO-2026-000001`** | **80 000** | **1 760** | **317** | 18 % | **2 077** |

**Net à reverser : 80 000 − 2 077 = 77 923.**

Les TVA de ce tableau sont **arrondies au franc** : 18 % de 560 F font
100,80 F, arrondis à 101. Depuis le **2026-09-18**, la base porte le même 101 —
l'arrondi est appliqué au calcul (**question 7**).

La ligne de retour porte sa TVA depuis le **2026-10-03** (**question 6**) : le
retour est une prestation rendue contre rémunération, **taxable au taux normal**.
Jusque-là le logiciel la forçait à zéro ; les relevés émis avant cette date l'ont
donc facturée hors champ, et `beninlink:retours-sans-tva` les montre (§ 4).

**Écriture 1 — journal des ventes (`VE`)** : la prestation facturée au marchand,
livraison **et** retour.

| Compte | Libellé | Débit | Crédit |
|---|---|---|---|
| 4111 + code marchand | Clients | 2 077 | |
| 7061 | Prestations de services de livraison | | 1 760 |
| 4431 | État, TVA facturée sur ventes | | 317 |

**Écriture 2 — opérations diverses (`OD`)** : les frais sont retenus sur le COD
encaissé pour le compte du marchand, dette constatée au compte COD.

| Compte | Libellé | Débit | Crédit |
|---|---|---|---|
| 4712 + code marchand *(numéro à valider)* | COD encaissé pour compte de marchands | 2 077 | |
| 4111 + code marchand | Clients | | 2 077 |

**Écriture 3 — banque (`BQ`)**, émise **seulement quand le relevé est payé**, à
la **date de l'ordre de virement** : le reversement du net.

| Compte | Libellé | Débit | Crédit |
|---|---|---|---|
| 4712 + code marchand *(numéro à valider)* | COD encaissé pour compte de marchands | 77 923 | |
| 521 | Banques locales | | 77 923 |

Chaque pièce est équilibrée, et l'extrait vérifie l'équilibre **avant** d'écrire
le fichier : un lot déséquilibré n'est jamais produit.

## 2. Les huit points — tranchés par le porteur le 2026-10-03

Chaque réponse est traduite dans `web/config/syscohada.php` et figée par
`tests/Feature/PlanDeComptesSigneTest` : un changement ultérieur se fait **dans
les deux**, jamais l'un sans l'autre. Les cases « à valider » sont celles de
l'expert-comptable.

| # | Question | Décision | Ce qui reste à l'expert-comptable |
|---|---|---|---|
| 1 | **Sous-comptes par marchand ?** | **Auxiliaire par marchand, par défaut** : `4111PIL001`. Seuls les comptes de **tiers** sont suffixés (clients, COD, avances reçues) ; produits, TVA, banque et caisse restent collectifs. `SYSCOHADA_AUXILIARY=collectif` pour revenir à une racine unique. | — |
| 2 | **COD encaissé pour compte de tiers** | Le principe d'un compte **dédié** est acté : ces fonds ne sont pas un produit du transporteur, ils doivent se lire en balance âgée. | ☐ le **numéro** (4713 ? 4718 ? 419 ?) — 4712 reste posé en attendant : ______ |
| 3 | **TVA** | **4431**, taux **18 %** au niveau de la société (surchargeable par marchand, D1). Figés. | — |
| 4 | **Codes de journaux** | `VE` / `OD` / `BQ`, plus `CA` (caisse, pour les remises d'espèces de la question 8). Lus dans la configuration. | ☐ les codes du logiciel comptable cible — VE : ____ OD : ____ BQ : ____ CA : ____ |
| 5 | **Date de l'écriture de banque** | La **date de l'ordre de virement** fait foi. Le logiciel la prend au moment où le relevé est **marqué payé** (`invoices.paid_on`) : l'action « marquer payé » se fait **le jour du virement**, pas après coup. Un relevé payé avant le 2026-10-03 n'a pas cette date et garde celle de son émission. | — |
| 6 | **Frais de retour dans l'assiette de TVA ?** | **Taxable au taux normal.** Le retour reçu prélève le frais **et** sa TVA au marchand, au franc (`parcels.return_vat_amount`), et le relevé la facture. Les relevés émis **avant** sont **constatés**, jamais modifiés : `beninlink:retours-sans-tva` (§ 4). | ☐ la **régularisation du passé** : avoir, relevé complémentaire, ou rien si non significatif — décision : ______ |
| 7 | **Arrondi de la TVA au franc ?** | **Au franc le plus proche**, appliqué depuis le 2026-09-18 au seul endroit où un taux devient des francs (TVA et commission COD). **Pas de reprise du passé** : les écarts (0,40 F par colis au plus) ne sont pas significatifs. | — |
| 8 | **Les trois flux hors relevé** | **Recharges de portefeuille** (Mobile Money, approuvées) : journalisées en **avances reçues** du marchand — D 521 / C avances. **Remises d'espèces des livreurs** au hub : journalisées par un **compte de transit** — D 571 Caisse / C transit livreurs. **Abonnements SaaS : aucune écriture** côté transporteur (livres de la société éditrice). | ☐ le numéro des **avances reçues** (4191 proposé) : ______ · ☐ le numéro du **transit livreurs** (4713 proposé) : ______ |

## 3. Ce que l'extrait couvre, et ce qu'il ne couvre pas

| Flux | Journal | Écriture | Statut |
|---|---|---|---|
| Relevé émis (livraisons et retours facturés) | `VE`, `OD` | § 1, écritures 1 et 2 | en place |
| Relevé payé (reversement du net) | `BQ` | § 1, écriture 3, à la date de l'ordre de virement | en place |
| Recharge de portefeuille approuvée | `BQ` | D 521 Banques / C 4191 avances reçues (+ code marchand) | en place, **numéro à valider** |
| Remise d'espèces d'un livreur au hub | `CA` | D 571 Caisse / C 4713 transit livreurs | en place, **numéro à valider** |
| Abonnements SaaS de la plateforme | — | aucune | décidé : hors des livres du transporteur |
| Reversements aux livreurs (courses payées) | — | aucune | suivis dans les soldes internes du logiciel ; à ouvrir si l'expert-comptable le demande |

Chaque écriture tombe dans l'extrait de **sa** période : ventes et compensation à
l'émission du relevé, banque à la date du virement, recharges à leur approbation,
remises à leur date. Un relevé émis en août et payé en septembre a ses ventes
dans l'extrait d'août et sa banque dans celui de septembre.

## 4. Obtenir un extrait à valider

```bash
# le mois dernier, toutes les sociétés, affichage seul
php artisan beninlink:journal-syscohada

# une période précise, une société, écriture du CSV
php artisan beninlink:journal-syscohada --du=2026-09-01 --au=2026-09-30 \
    --societe=2 --fichier=journal-septembre.csv

# seulement les relevés payés (sans recharges ni remises)
php artisan beninlink:journal-syscohada --payes

# les relevés émis AVANT le 2026-10-03 qui ont facturé un retour sans TVA (question 6)
php artisan beninlink:retours-sans-tva [--societe=2] [--marchand=12]
```

La première commande affiche le nombre de pièces, le total débit, le total
crédit et le détail par journal ; elle **refuse d'écrire** si une pièce ne
s'équilibre pas. Le CSV est en point-virgule avec BOM UTF-8 (ce qu'Excel
français attend).

La seconde **constate seulement** : relevé par relevé, le frais de retour
facturé, la TVA manquante au taux du colis, et si le relevé est déjà payé. Elle
n'a **aucune option d'écriture** — un relevé émis ne se modifie pas (**D8**,
**D9**). La régularisation est la case restante de la question 6.

## 5. Ce qui ne dépend pas de vos réponses

Quelle que soit la décision, le logiciel garantit :

- des montants **entiers en FCFA**, jamais de centimes — **des documents
  comme de la base** depuis le 2026-09-18 : l'arrondi s'applique au calcul, et
  non plus seulement à l'impression ;
- une **numérotation continue** des relevés par société et par exercice
  (`PREFIXE-2026-000001`), sans trou ni doublon ;
- l'**équilibre** de chaque pièce, vérifié avant export ;
- le taux de TVA lu au niveau de la société, surchargeable par marchand (**D1**) ;
- l'IFU du marchand porté sur chaque ligne, pour le rapprochement tiers ;
- un relevé émis **ne se modifie jamais** après coup : les correctifs passent par
  un constat, puis une régularisation explicite (**D8**, **D9**).

## 6. Retour attendu

Les huit questions sont tranchées côté métier. Ce qui vous revient tient en
**sept cases** du tableau du § 2 :

1. le numéro du compte **COD dédié** (question 2) ;
2. les **codes de journaux** VE / OD / BQ / CA du logiciel cible (question 4) ;
3. la **régularisation du passé** sur les retours facturés sans TVA (question 6) —
   l'extrait de `beninlink:retours-sans-tva` chiffre ce qu'elle représente ;
4. le numéro des **avances reçues** (question 8) ;
5. le numéro du **transit livreurs** (question 8) ;
6. et, depuis la décision **R9** (S75) : les relevés partent **par courriel** au marchand à
   leur émission, avec le PDF officiel — identique au téléchargement, jamais réémis
   modifié. Son **opposabilité juridique** (valeur probante du PDF, mentions, archivage)
   relève de votre dossier : dire ce qu'il doit porter de plus, s'il manque quelque chose.

Les numéros se changent en une ligne de `web/config/syscohada.php` et dans le
test qui les fige — aucune migration, aucun code. La régularisation du passé,
elle, sera un lot à part, écrit **après** votre réponse, sur le modèle de
`beninlink:retours-annules --corriger` : constat d'abord, écriture ensuite,
jamais en production sans `--force`.

> **Au 2026-10-06 (S106).** Les numéros en place (4712, 4191, 4713) et les codes
> VE / OD / BQ / CA sont le **plan de travail** : l'export tourne avec, et une
> préférence de votre part se pose en une ligne de `web/config/syscohada.php`.
> La **régularisation du passé (point 3) est sans objet** : la production a
> démarré le 2026-10-06, après S73 — aucun retour n'y a été facturé sans TVA,
> `beninlink:retours-sans-tva` y rend zéro. Restent à vous : une préférence de
> numéros, et l'opposabilité du PDF (point 6).

Nom et signature : ______________________  ·  Date : ____________
