# Revue d'alignement — lots 1 et 2

Date : 2026-10-09. Référence : S145, `51b217cd4a78b2fa4a2bacb05e7d7d89fc320ce3`.
Périmètre : Laravel `web/`, application marchand `mobile/`, application livreur `mobile-livreur/`.
Cette revue complète les documents historiques et ne remplace aucune décision métier.

## Lot 1 : résultat de lecture

Les parcours sont raccordés aux contrôleurs et services existants. Les 63 chemins répertoriés dans l'inventaire marchand et les 20 chemins livreur existent dans OpenAPI après normalisation des paramètres. Ce constat ne valide ni toutes les méthodes HTTP ni toutes les formes de réponse.

| Parcours | Conclusion |
|---|---|
| Connexion, OTP, session, mot de passe | Relié ; comptes désactivés corrigés par S145 |
| Société, marchand et course affectée | Contrôles présents dans les chemins examinés ; tests négatifs nécessaires |
| Création et devis | Calcul serveur ; ancien devis encore confirmable pendant un changement |
| Livraison, partiel, retour | Transitions reliées ; validation et arrondi partiels incomplets |
| FedaPay et historique wallet | Serveur autoritaire ; suivi mobile après retour navigateur incomplet |
| Retrait | Compte propriétaire contrôlé ; domaine du montant insuffisamment validé |
| Relevé liste/détail/PDF/journal | Source officielle figée ; détail API encore dépendant des colis vivants |
| Douane | Règles et alertes reliées ; couleurs du devis à aligner |
| Gains livreur | Écran relié ; agrégat dépenses API erroné |
| Notifications, preuves, localisation | Code raccordé ; recette sur Android réel indispensable |

## Lot 2 : environnement et vérifications

La copie locale a été avancée de S144 vers S145 par fast-forward, sans divergence. Les dépendances mobiles ont été réinstallées avec `npm ci`, sans modifier les fichiers verrouillés. PHP 8.3.35 est exécuté dans une image locale de test dotée des extensions de la CI : GD (avec JPEG et FreeType), ZIP, bcmath, intl et mysqli, en plus des extensions de l'image PHP initiale. Les tests Laravel utilisent SQLite en mémoire et le conteneur d'exécution n'a pas de réseau. La limite mémoire de PHP a été portée de 128 à 512 Mo pour la suite complète après un arrêt du rendu PDF à la limite initiale.

Les diagnostics de régression sont conservés hors de la suite applicative, dans le dossier de travail : `Lot2AlignmentTest.php`. Ils expriment le comportement attendu et doivent échouer sur S145. Ils ne constituent pas des corrections.

| Contrôle | Résultat |
|---|---|
| Typage marchand | Réussi après installation des dépendances |
| Jest marchand | 9 suites, 45 tests réussis |
| Typage livreur | Réussi après régénération des types Expo depuis les routes réelles |
| Jest livreur | 6 suites, 28 tests réussis |
| ESLint des deux apps | Aucune erreur ; une directive eslint-disable inutilisée dans chaque fichier Expo généré |
| Première sélection Laravel | 54 tests, 252 assertions ; 2 erreurs d'environnement (GD et ZIP absents), extensions ensuite installées |
| Sélection Laravel avec GD/JPEG et ZIP | **54 tests réussis, 269 assertions** |
| Reproductions P1 | 6 tests, 9 assertions, 6 échecs attendus ; aucun arrêt pour erreur de montage du diagnostic |
| Suite Laravel complète | 1 425 tests exécutés, 51 866 assertions ; 2 erreurs JPEG et 1 échec lié au manifeste de dépendances obsolète |
| Recontrôle des classes concernées après réparation de l'environnement | **34 tests réussis, 206 assertions** (`DeliveryProofTest`, `DevDependencyLeakTest`, `RecettePiloteRepetitionTest`) |

Le premier échec de typage livreur était lié au fichier ignoré `.expo/types/router.d.ts` datant du 3 octobre : les routes de mot de passe oublié existaient dans `app/` mais pas dans les déclarations. Les déclarations ont été régénérées avec le générateur fourni par Expo. Aucun contournement TypeScript ni changement de navigation n'a été nécessaire.

Le manifeste ignoré `web/bootstrap/cache/packages.php`, également daté du 3 octobre, découvrait encore automatiquement Debugbar malgré l'exclusion actuelle de `composer.json`. Il a été reconstruit avec `PackageManifest::build()`, puis les classes affectées ont été recontrôlées. La suite complète n'a pas été relancée une troisième fois : les 1 422 autres tests avaient réussi, et seuls les incidents identifiés d'environnement nécessitaient ce recontrôle. Ne pas présenter ce bilan comme une exécution unique de 1 425 tests sans incident.

**Clôture du lot 2 :** environnement utilisable, vérifications existantes effectuées, quatre écarts P1 confirmés par six diagnostics. Les écarts métier restent ouverts ; la recette sur appareils n'a pas été exécutée. Modifications suivies : ce document et son lien dans le CLAUDE racine. Aucun fichier de code applicatif ni décision métier modifié.

## Écarts prioritaires reproduits

### F01 — Détail de relevé incompatible avec la source officielle

- Nouveau relevé produit par `InvoiceRepository::store` : `parcels_id` n'est plus renseigné. `GET /api/v10/invoice-details/{id}` retourne **500**, car `InvoiceDetailsResource` appelle `whereIn` avec null.
- Forme historique avec `parcels_id` renseigné : un colis de COD 20 000, frais HT 1 200 et TVA 216 donne **18 800** dans le détail, contre **18 584** dans `SettlementStatement`.
- Sources : `web/app/Http/Resources/v10/InvoiceDetailsResource.php`, `web/app/Repositories/Invoice/InvoiceRepository.php`, `web/app/Services/Invoicing/SettlementStatement.php`, `mobile/app/(app)/invoices.tsx`.

Correction prévue : lire les lignes figées `invoice_parcels` via `SettlementStatement`, préserver ou faire évoluer explicitement le contrat API, exposer la TVA au mobile. Vérifier détail d'un relevé neuf, cohérence liste/détail/PDF/CSV/journal et maintien des montants après mutation des colis. Prévoir le traitement des véritables relevés historiques sans lignes figées avant toute reprise de données.

### F02 — TVA partielle fractionnaire

Une livraison partielle de **12 000** avec livraison 1 000, COD 1 % et TVA 18 % enregistre **201,6** de TVA, contre **202** selon la règle d'arrondi au franc. Les tests `PartialDeliveryAccountingTest` attendent actuellement cette fraction et peuvent donc passer malgré l'écart métier.

Source : `web/app/Repositories/Parcel/ParcelRepository.php`, méthodes `parcelPartialDelivered` et `parcelPartialDeliveredCancel`.

Correction prévue : appliquer les arrondis de référence au COD et à la TVA, puis vérifier écritures, soldes et annulation. Conserver l'inversion des sommes réellement comptabilisées selon D8. Ne pas migrer les colonnes DECIMAL, conformément à D15.

### F03 — Encaissement négatif accepté sur la vraie route livreur

`POST /api/v10/deliveryman/parcel-status-update`, avec une course effectivement affectée au livreur, l'action partielle et **cash_collection = -100**, retourne **200** au lieu de **422**. Le test pilote L7 utilise une autre route, `/deliveryman/parcel/partial-delivered/{id}`.

Sources : `DeliverymanController::parcelStatusUpdate`, `DeliveryManParcelController::parcelPartialDelivered`, `mobile-livreur/src/api/deliveryman.ts`, écran de statut livreur.

Correction prévue : validation commune sur les deux entrées, montant explicitement renseigné, entier et non négatif. Une saisie vide ne doit pas être transformée silencieusement en zéro. Ne pas interdire un zéro volontaire ou fixer une borne supérieure sans confirmer le contrat métier. Tester aussi le colis d'un autre livreur et vérifier l'absence d'écritures lors d'un refus.

### F04 — Retraits négatifs et fractionnaires acceptés

Avec un solde marchand positif et son propre compte Mobile Money, les demandes de **-100** et **100,5** retournent toutes deux **200** au lieu de **422**. Le formulaire mobile impose déjà un entier strictement positif. Ce diagnostic porte sur l'enregistrement de demandes, sans démontrer un décaissement automatique.

Sources : `MerchantPanel/PaymentRequest/StoreRequest`, `Api/V10/PaymentRequestController`, repository de demande de paiement.

Correction prévue : validation serveur d'un entier strictement positif, sur création et modification, en conservant le contrôle du solde et du compte propriétaire. Tester zéro, négatif, fraction, solde dépassé et compte tiers.

## Écarts secondaires du lot 1

| ID | Priorité | Correction prévue |
|---|---|---|
| F05 | P2 | Invalider le devis au changement des paramètres ; confirmer seulement le devis courant, disponible et non bloquant |
| F06 | P2 | Garder la référence FedaPay, distinguer attente/refus/annulation, permettre une nouvelle vérification et actualiser profil/historique après approbation |
| F07 | P2 | Réutiliser le mapping commun INFO/WARNING/BLOCKING dans la bannière de création |
| F08 | P3 | Calculer totalDeliveryExpense depuis les dépenses dans DeliveryManIncomeExpenseController ; impact direct sur l'écran non démontré |

## Ordre des corrections et critères de livraison

1. Corriger F01–F04 dans l'architecture existante, avec tests des vraies routes des applications.
2. Quand une réponse change, mettre à jour le backend puis OpenAPI, les types et les écrans concernés.
3. Corriger F05–F08 et vérifier les erreurs réseau, la latence et les retours de navigation.
4. Actualiser CLAUDE/CARTOGRAPHIE et les décisions touchées après chaque correction ; ne pas réécrire les sessions historiques ni attribuer un numéro de session Claude à cette revue.
5. Exécuter la recette intégrée : web visuel, deux Android en réseau mobile, livraison avec preuves, retour, relevé, recharge sandbox avec webhook tardif et répété, push et localisation.

Aucune correction applicative, publication GitHub ou mise en production n'a été faite pendant les lots 1–2. Les résultats automatisés ne remplacent pas la recette terrain ni la validation comptable du pilote.

## Suite du chantier

Les corrections du lot 3 et leur validation sont consignées dans [LOT_3_CORRECTIONS.md](LOT_3_CORRECTIONS.md). Les constats ci-dessus décrivent la référence S145 avant ces corrections.
