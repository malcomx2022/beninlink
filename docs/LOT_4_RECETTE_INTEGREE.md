# Lot 4 — recette intégrée (2026-10-09)

## Plan et périmètre

1. Vérifier la fusion du lot 3 et ses contrôles.
2. Vérifier les bundles Android et la répétition automatisée des parcours.
3. Identifier précisément le serveur et les APK testés.
4. Exécuter la recette terrain marchand → livreur → back-office.
5. Consigner les preuves, corriger les anomalies, puis décider de la diffusion.

L'architecture et les décisions Claude restent celles de `CLAUDE.md` et des
documents locaux. Ce lot complète le guide `guides/recette-pilote/README.md`.

## État vérifiable

- Lot 3 : PR [#214](https://github.com/malcomx2022/beninlink/pull/214) déjà fusionnée ; commit `874795d1cad40052c6ac562276bef9c0138edb68`.
- CI de la PR : Laravel, les deux apps et répétition de déploiement réussis.
- CI du commit fusionné : [exécution 37921590201](https://github.com/malcomx2022/beninlink/actions/runs/37921590201). Laravel, les deux apps et la répétition sont réussis ; déploiements en file d'attente au moment du relevé, à vérifier sur cette exécution.
- Vérification locale du commit fusionné : `RecettePiloteRepetitionTest`, `RecetteDeploymentTest`, `DeploymentRehearsalTest` et `ThreeApplicationsAlignmentTest` : **72 tests réussis, 370 assertions** (PHP 8.3, SQLite, sans réseau).
- Deux exports Android de production locaux réussis, Hermes : marchand environ 3 Mo, livreur environ 3,5 Mo. Export effectué hors ligne avec une clé API factice ; bundles réservés à la vérification, non distribuables.
- URL confirmée par le responsable de recette : `https://recette.beninlink.app`, réponse HTTP 200 rapportée ; API des deux APK : `https://recette.beninlink.app/api/v10`.
- APK déjà compilés selon le responsable. Leur commit source, leur identifiant de build et leur installation sur les appareils restent à consigner.

Une réponse HTTP 200 ne prouve pas la version du serveur. Une URL API correcte
ne prouve pas que les APK contiennent le lot 3. Vérifier le SHA déployé via le
processus d'exploitation existant et les métadonnées EAS/build des deux APK.
Ne publier aucun secret ni mot de passe dans les preuves.

## Recette terrain obligatoire

### Suivi technique après fusion de la préparation

L'exécution [37922282998](https://github.com/malcomx2022/beninlink/actions/runs/37922282998)
a réussi Laravel, l'app livreur, la répétition et les deux déploiements SSH.
L'app marchand a échoué sur deux tests du portefeuille : dépassement du délai
sur le scénario pending, puis réponse approved inattendue dans le cas declined.
La validation des contrôles du lot 3 demeure celle de son exécution dédiée ;
cette nouvelle exécution révèle une fragilité de tests à corriger.

Les tests du portefeuille attendent désormais que les boutons soient activés
avant de les presser (restauration du suivi et fin de traitement asynchrones).
Le mock des statuts est réinitialisé entre scénarios afin qu'une réponse à usage
unique non consommée ne contamine pas le test suivant. Le code de paiement
et ses règles métier ne sont pas modifiés. Vérification locale : 50 tests
marchand réussis ; résultat CI de la correction à vérifier sur sa PR.

Utiliser uniquement les comptes et colis pilotes de recette, les paiements
sandbox et le guide existant. Remplir `guides/recette-pilote/lot-4-suivi.csv`.

| Cas | Action | Résultat attendu |
|---|---|---|
| R1 | Ouvrir un relevé nouveau puis modifier un colis lié ; relire web, app et PDF | Montants figés issus du relevé ; TVA et autres frais cohérents. En cas d'incohérence signalée, aucune ventilation trompeuse. |
| R2 | Livrer partiellement avec champ vide, puis zéro explicite, puis montant entier valide | Vide refusé ; zéro et entier soumis aux règles métier ; mêmes résultats via les deux routes API et le web. |
| R3 | Livrer partiellement avec COD donnant une fraction de franc, puis annuler | COD et TVA arrondis au franc ; annulation inverse exactement les écritures enregistrées. |
| R4 | Créer/modifier un retrait avec zéro, décimal et entier positif | Zéro et décimal refusés ; entier positif accepté sous réserve du solde et des règles métier. |
| R5 | Ralentir le réseau, modifier les paramètres après un devis, provoquer une erreur de devis | Devis ancien inutilisable ; soumission bloquée sans devis actuel valide ; alerte et zone cohérentes. |
| R6 | Initier une recharge sandbox, fermer puis relancer l'app ; rejouer un webhook signé | Référence reprise ; aucune seconde initiation pendant le suivi ; solde crédité une seule fois après webhook. Tester aussi refus et annulation. |
| R7 | Revenir sur le portefeuille après approbation puis comparer dépenses du tableau web | Profil et historique actualisés ; total des dépenses fondé sur les dépenses. |
| R8 | Créer un colis marchand, l'affecter au livreur, joindre photo/signature, livrer, consulter le suivi et le relevé | Statuts, preuves et montants concordent entre les trois applications ; accès d'une autre société refusé. |

## Critères de sortie

Les contrôles automatisés sont une préparation à la recette terrain. Aucun cas
terrain n'est déclaré validé sans résultat observé et preuve. La diffusion exige
des versions identifiées, les huit cas réussis, aucune anomalie bloquante et un
déploiement effectivement exécuté. Un job vert dont l'étape SSH a été sautée
ne constitue pas un déploiement. La validation caméra, signature, réseau mobile,
notifications et reprise sur appareil reste humaine.
