# Lot 3 — corrections d'alignement des trois applications

Date : 2026-10-09. Base : S145, `51b217cd4a78b2fa4a2bacb05e7d7d89fc320ce3`.
Plan et reproductions : [revue des lots 1–2](REVUE_ALIGNEMENT_LOTS_1_2.md).

## Corrections

| Écart | Changement |
|---|---|
| F01 — relevé neuf en 500 et ancien calcul sans TVA | InvoiceDetailsResource utilise SettlementStatement et les lignes invoice_parcels figées. Le net est celui enregistré à l'émission. TVA, autres frais, totaux HT/TTC et indicateur de cohérence complètent le contrat. Le mobile affiche les postes supplémentaires. |
| F02 — fractions de franc en livraison partielle | COD et TVA sont arrondis au franc lors du partiel et du rétablissement des frais à l'annulation. L'annulation inverse les sommes réellement comptabilisées, sans recalculer le remboursement. |
| F03 — encaissement négatif et vide | PartialDeliveryRequest fournit la même validation au web, aux deux routes livreur et au repository : required, integer, min:0. Une saisie vide dans l'app ne devient plus zéro ; un zéro explicite reste permis. |
| F04 — retrait négatif ou fractionnaire | La validation commune de création/modification exige un entier positif. Les contrôles de solde et de compte propriétaire sont conservés. |
| F05 — ancien devis confirmable | Chaque réponse est associée aux paramètres utilisés. Tout changement invalide le devis dès le rendu. La confirmation exige le devis courant, disponible et non bloquant. Une réponse abandonnée n'est pas retenue. |
| F06 — recharge pending perdue | La référence et l’URL de paiement sont persistées par compte marchand via le stockage existant (SecureStore sur Android/iOS). Vérifier le paiement réinterroge le serveur après retour ou réouverture ; Reprendre le paiement rouvre la même URL, sans créer une transaction supplémentaire. pending conserve la référence ; approved actualise le profil ; declined/canceled ont leurs messages propres. Profil/historique sont actualisés au retour sur l'écran. |
| F07 — couleurs douanières | Le devis utilise customsLevelColorName pour INFO/WARNING/BLOCKING. |
| F08 — dépenses livreur égales aux revenus | totalDeliveryExpense additionne les dépenses. |

Les champs API existants restent présents, y compris total_deliverd_amount (faute historique) et parcels:null. OpenAPI est régénéré depuis l'overlay ; les nouveaux champs sont optionnels dans les types mobiles pour tolérer un serveur antérieur pendant le déploiement.

## Tests de régression

- ThreeApplicationsAlignmentTest : relevé neuf, forme historique, détail stable après mutation du colis avec emballage, TVA arrondie, COD fractionnaire et annulation, montants invalides sur les deux routes livreur sans écriture, zéro explicite, retraits invalides et dépenses distinctes des revenus.
- PartialDeliveryAccountingTest : TVA attendue de 202 au lieu de 201,6 ; écritures et soldes toujours vérifiés.
- NewParcelScreen.test.tsx : changer le COD désactive immédiatement la confirmation et empêche l'envoi avec l'ancien devis.
- WalletScreen.test.tsx : webhook tardif, référence conservée et reprise, réouverture du même paiement sans nouvelle initiation, refus et annulation distincts de l'attente.

## Validation

Résultats intermédiaires : 108 tests Laravel ciblés / 740 assertions réussis ; 34 tests de contrats et premières régressions / 422 assertions réussis. Typage et lint exécutés sur les deux apps ; 50 tests marchand et 28 tests livreur réussis. La suite complète `php artisan test` a exécuté **1 446 cas / 51 936 assertions** : 1 441 réussites et cinq échecs de tests. Quatre provenaient des fixtures de `MerchantBalanceDriftCommandTest` qui utilisaient le calcul courant pour simuler des lignes historiques à 201,60 ; elles reconstituent maintenant explicitement ces lignes, sans changer la commande de production. Le cinquième était un argument manquant dans le nouveau test d'annulation. Après correction, le recontrôle des deux classes a réussi : **31 tests / 88 assertions**. La suite complète n'a pas été relancée après ces seules corrections de tests : ne pas présenter ce bilan comme une exécution unique sans incident.

Résultat final mobile : **50 tests marchand / 11 suites**, **28 tests livreur / 6 suites** ; typage réussi des deux apps, aucun échec de lint (un avertissement de directive inutilisée dans chaque fichier Expo généré). `git diff --check` réussi. Aucun changement de code applicatif après les contrôles qui le couvrent.

## Limites et recette suivante

Aucune colonne DECIMAL migrée, règle de TVA changée ou somme historique réécrite. D8/D9/D15 restent applicables. Un relevé dont les lignes manquent ou ne concordent pas garde son net enregistré et signale statement_consistent:false ; le mobile demande une vérification comptable au lieu d'afficher une ventilation trompeuse. Une reprise de ces données doit être décidée séparément.

Le navigateur ne crédite jamais le paiement : le serveur reste l'autorité après webhook signé. Vérification explicite, sans polling. Une référence en attente bloque une nouvelle initiation depuis cet écran afin de garder son suivi.

Recette suivante : deux Android en réseau mobile, webhook sandbox tardif/répété, navigateur fermé puis app relancée, devis lent/en erreur, partiel suivi d'annulation, relevé PDF et écrans web. Les tests automatisés ne remplacent pas cette recette. Aucune fusion sur main ni mise en production dans ce lot.
