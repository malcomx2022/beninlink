# Plan de comptes SYSCOHADA — fiche de validation

> À remettre à l'expert-comptable du transporteur. Ce document sert à **arrêter**
> le paramétrage comptable de BeninLink (décision **D2**). Tout ce qui y est
> proposé est modifiable dans un fichier de configuration — jamais dans le code.
>
> Établi le 2026-09-06 · à retourner signé.

## 1. Ce que le logiciel fait aujourd'hui

BeninLink émet un **relevé de règlement** par marchand : les colis livrés sur la
période, ce qui a été encaissé auprès des destinataires (COD), les frais du
transporteur, la TVA, et le net à reverser. De ce relevé, il tire trois écritures.

Exemple réel, un relevé de **trois colis** (montants en FCFA entiers). Le détail
est donné colis par colis parce que le total, seul, ne se recompose pas au taux
de 18 % — et c'est la première chose qu'un comptable vérifie :

| Colis | Encaissé COD | Frais HT | TVA | Taux | Frais TTC |
|---|---|---|---|---|---|
| `BL-1` livré | 50 000 | 560 | 101 | 18 % | 661 |
| `BL-2` livré | 30 000 | 700 | 126 | 18 % | 826 |
| `BL-3` **retourné** | — | 500 | **0** | **aucun** | 500 |
| **Relevé n° `CO-2026-000001`** | **80 000** | **1 760** | **227** | *12,9 % apparent* | **1 987** |

**Net à reverser : 80 000 − 1 987 = 78 013.**

⚠️ Les TVA de ce tableau sont **arrondies au franc**, comme sur le relevé
imprimé : 18 % de 560 F font 100,80 F, affichés 101. En base, le logiciel
conserve aujourd'hui les 100,80 — c'est l'objet de la **question 7**.

Le taux apparent du relevé (12,9 %) n'est pas une erreur de calcul : il vient de
la troisième ligne. **Les frais de retour sortent de l'assiette de TVA** —
`InvoiceRepository` force `vat_amount = 0` sur un colis retourné et retient le
seul `return_charges`. C'est le comportement du socle repris tel quel, et il fait
l'objet de la **question 6** ci-dessous : un retour est une prestation
effectivement rendue, la traiter hors champ n'a rien d'évident.

**Écriture 1 — journal des ventes (`VE`)** : la prestation facturée au marchand.

| Compte | Libellé | Débit | Crédit |
|---|---|---|---|
| 4111 | Clients | 1 987 | |
| 7061 | Prestations de services de livraison | | 1 760 |
| 4431 | État, TVA facturée sur ventes | | 227 |

**Écriture 2 — opérations diverses (`OD`)** : les frais sont retenus sur le COD
encaissé pour le compte du marchand, dette constatée au 4712.

| Compte | Libellé | Débit | Crédit |
|---|---|---|---|
| 4712 | Créditeurs divers — COD encaissé pour compte de marchands | 1 987 | |
| 4111 | Clients | | 1 987 |

**Écriture 3 — banque (`BQ`)**, émise **seulement quand le relevé est payé** :
le reversement du net.

| Compte | Libellé | Débit | Crédit |
|---|---|---|---|
| 4712 | Créditeurs divers | 78 013 | |
| 521 | Banques locales | | 78 013 |

Chaque pièce est équilibrée, et l'extrait vérifie l'équilibre **avant** d'écrire
le fichier : un lot déséquilibré n'est jamais produit.

## 2. Les six points à trancher

Cocher, ou corriger dans la colonne de droite. Chaque réponse se traduit par une
ligne de `web/config/syscohada.php`.

| # | Question | Proposition du logiciel | Décision de l'expert-comptable |
|---|---|---|---|
| 1 | **Sous-comptes par marchand ?** Un compte collectif 4111, ou un auxiliaire par marchand (`4111PIL001`) pour lettrer sans dépouiller les libellés ? | Auxiliaire **recommandé** dès que le transporteur dépasse quelques marchands. Déjà implémenté : `SYSCOHADA_AUXILIARY=merchant_code`. Seuls les comptes de **tiers** (4111, 4712) sont suffixés ; produits et TVA restent collectifs. | ☐ collectif ☐ auxiliaire · racine : ______ |
| 2 | **COD encaissé pour compte de tiers** : 4712 « Créditeurs divers » convient-il, ou faut-il un compte dédié (4713 / 4718), voire un 419 « Clients créditeurs » ? | 4712 par défaut. Le point n'est pas cosmétique : ces fonds **ne sont pas** un produit du transporteur, ils lui sont dus par nature. Le compte retenu doit être lisible en balance âgée. | Compte : ______ |
| 3 | **TVA** : 4431 (TVA facturée sur ventes) ou un sous-compte propre aux prestations, selon le régime retenu pour le transport au Bénin. | 4431, taux **18 %** au niveau de la société (surchargeable par marchand). | Compte : ______ · taux : ____ % |
| 4 | **Codes de journaux** : `VE` / `OD` / `BQ` correspondent-ils au paramétrage du logiciel comptable cible (Sage, Saari, autre) ? | VE / OD / BQ. | VE : ____ OD : ____ BQ : ____ |
| 5 | **Date de l'écriture de banque** : elle est émise au passage du relevé au statut **payé**. Est-ce la date de valeur attendue, ou faut-il la date de l'ordre de virement ? | Date du relevé payé. | ☐ conforme ☐ autre : ______ |
| 6 | **Frais de retour dans l'assiette de TVA ?** Un colis retourné est facturé au marchand (`return_charges`) mais **sans TVA** : le logiciel force le montant à zéro et ne retient que le frais. | Comportement hérité du socle, **non arbitré**. Un retour est pourtant une prestation rendue contre rémunération ; s'il est taxable, l'assiette déclarée est aujourd'hui sous-évaluée du montant des retours. | ☐ hors champ ☐ taxable au taux normal · si taxable : reprise du passé ☐ oui ☐ non |
| 7 | **Arrondi de la TVA au franc ?** Le FCFA n'a pas de subdivision, mais la TVA est calculée en pourcentage : 18 % de 1 680 F donne **302,40 F**, et le logiciel conserve cette décimale en base. | Arrondir **au franc le plus proche, ligne par ligne** — c'est déjà ce que fait le relevé imprimé ; l'appliquer au calcul alignerait la base sur le document sans changer un seul chiffre déjà affiché. | ☐ arrondi au franc ☐ autre règle : ______ · si arrondi : reprise du passé ☐ oui ☐ non |

## 3. Ce que l'export ne couvre pas (encore)

À confirmer aussi : faut-il des écritures pour ces flux, et lesquelles ?

| Flux | Aujourd'hui | Remarque |
|---|---|---|
| Recharges de portefeuille marchand (Mobile Money) | non journalisées | ce sont des **avances reçues** du marchand, pas un produit |
| Reversements aux livreurs, remises d'espèces | non journalisés | suivis dans les comptes internes du logiciel (soldes livreurs) |
| Abonnements SaaS de la plateforme | non journalisés | concernent la société éditrice, pas le transporteur |

Tant que ces flux ne sont pas tranchés, l'extrait couvre **le cycle marchand**,
qui est celui que la TVA et le relevé engagent.

## 4. Obtenir un extrait à valider

```bash
# le mois dernier, tous statuts, affichage seul
php artisan beninlink:journal-syscohada

# une période précise, une société, écriture du CSV
php artisan beninlink:journal-syscohada --du=2026-08-01 --au=2026-08-31 \
    --societe=2 --fichier=journal-aout.csv

# seulement les relevés payés (les seuls à porter une écriture de banque)
php artisan beninlink:journal-syscohada --payes
```

La commande affiche le nombre de pièces, le total débit, le total crédit et le
détail par journal ; elle **refuse d'écrire** si une pièce ne s'équilibre pas.
Le CSV est en point-virgule avec BOM UTF-8 (ce qu'Excel français attend).

## 5. Ce qui ne dépend pas de vos réponses

Quelle que soit la décision, le logiciel garantit :

- des **documents en francs entiers** — le relevé et le journal arrondissent
  chaque ligne au franc avant de l'imprimer. ⚠️ La **base**, elle, conserve la
  TVA non arrondie : c'est l'objet de la **question 7**, et c'est aujourd'hui la
  seule entorse à la règle « FCFA entiers » du projet ;
- une **numérotation continue** des relevés par société et par exercice
  (`PREFIXE-2026-000001`), sans trou ni doublon ;
- l'**équilibre** de chaque pièce, vérifié avant export ;
- le taux de TVA lu au niveau de la société, surchargeable par marchand (**D1**) ;
- l'IFU du marchand porté sur chaque ligne, pour le rapprochement tiers.

## 6. Retour

Une fois cette fiche complétée, les questions **1 à 5** se règlent en deux
lignes côté logiciel : les numéros dans `web/config/syscohada.php`, l'auxiliaire
dans le `.env` (`SYSCOHADA_AUXILIARY=merchant_code`). Aucune modification de
code, aucune migration.

Les **questions 6 et 7** sont les exceptions, et c'est pourquoi elles sont
posées à part : toutes deux touchent le **calcul**, pas le paramétrage.

Répondre « taxable » à la **6** change l'assiette — une TVA sur le frais de
retour dans `InvoiceRepository`, et, si la reprise du passé est demandée, une
régularisation des relevés déjà émis.

Répondre « arrondi » à la **7** change l'endroit où le franc entier est produit :
aujourd'hui à l'impression, demain au calcul. Aucun document déjà émis n'en
change d'apparence — le relevé arrondissait déjà —, mais le **net réellement
porté au solde** du marchand bougerait de moins d'un franc par colis, dans le
sens de ce que le relevé annonce. Là aussi, la reprise du passé est une question
distincte.

Ni l'une ni l'autre n'est un paramétrage ; ce sont deux chantiers courts mais
réels, à chiffrer une fois les réponses connues.

Nom et signature : ______________________  ·  Date : ____________
