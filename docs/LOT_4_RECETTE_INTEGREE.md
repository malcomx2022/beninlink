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
- APK historiques déclarés terminés le **2026-10-07 à 14:40 UTC** : marchand `0bd31084`, livreur `8b3ff02d` (identifiants abrégés fournis). Ils précèdent les corrections récentes des apps et le lot 3 ; ils ne permettent pas de valider ces corrections.
- Artefacts historiques fournis : [marchand](https://expo.dev/artifacts/eas/sTCYMqh04f3RgzCOUAKv5CFSQ3Y7n8ExgWh5zXXniqM.apk), [livreur](https://expo.dev/artifacts/eas/cpe02BmK4w3V4BYg0qqEQx0UAIEEjLRlMohHw-4qEVw.apk). Métadonnées rapportées par le responsable, non vérifiées directement auprès d'EAS.
- **Nouveaux rebuilds déclarés FINISHED le 2026-10-09**, depuis `main` avec S133/S134/S144 : [marchand](https://expo.dev/artifacts/eas/GDXArOtZlQrzqdhwVJcqi4hHCGLB_RAze_lFvdnV2ww.apk), [livreur](https://expo.dev/artifacts/eas/fBrEqrB0enCuueY5gU0hx8zhq_bqcC61inFKi3uU0jA.apk). Ces liens remplacent les APK historiques pour la recette. Disponibilité et contenu rapportés par le responsable, non vérifiés directement auprès d'EAS.
- SHA source déclaré pour les deux nouveaux builds : `54b1955f988a8ba6e1c90efbecd09e1260ecf2c2` (fusion de la PR #216). Vérification Git effectuée : ce SHA contient le commit du lot 3 `874795d1cad40052c6ac562276bef9c0138edb68` dans son historique. La base source déclarée inclut donc les corrections du lot 3.
- Build marchand : [`6a525ec8-2957-48d9-89dd-9bd37a996caa`](https://expo.dev/accounts/ulrichseglas-team/projects/beninlink-marchand/builds/6a525ec8-2957-48d9-89dd-9bd37a996caa).
- Build livreur : [`699eeeeb-f37f-4077-9a9c-50958195c80d`](https://expo.dev/accounts/ulrichseglas-team/projects/beninlink-livreur/builds/699eeeeb-f37f-4077-9a9c-50958195c80d).
- Différences locales déclarées lors des builds : `owner` et `projectId` dans `app.json`, `environment: preview` dans `eas.json`, `package-lock.json` régénéré par `npm install`. Les diffs ne sont pas encore fournis. La régénération du lock peut modifier les dépendances : son absence d'impact ne peut pas être attestée sans comparaison. Versionner les fichiers utilisés sur une branche/PR pour rendre les builds reproductibles, sans recréer les projets EAS ni les keystores.
- L'installation et la disponibilité des appareils ne sont pas confirmées ; aucun appareil n'est accessible dans cet environnement. Aucun cas de recette terrain n'est déclaré validé.

Une réponse HTTP 200 ne prouve pas la version du serveur. Une URL API correcte
ne prouve pas que les APK contiennent le lot 3. Vérifier le SHA déployé via le
processus d'exploitation existant et les métadonnées EAS/build des deux APK.
Ne publier aucun secret ni mot de passe dans les preuves.

Le blocage de reconstruction est levé selon le retour du responsable : les deux
nouveaux APK sont terminés. Pour une reconstruction ultérieure depuis cet
environnement, configurer `EXPO_TOKEN` via son gestionnaire de secrets ou celui
de la CI, jamais dans le chat ni dans le dépôt.
Conserver les projets et keystores EAS existants. Depuis une copie propre du
commit retenu, exécuter dans chacune des deux apps :

```bash
npx eas-cli build --platform android --profile recette --non-interactive
```

Cette commande requiert aussi les variables EAS de recette déjà documentées
dans le guide. Elle n'a pas été exécutée ici. L'export Hermes local n'est pas
un APK signé et ne remplace pas ces rebuilds. Après installation des nouveaux
APK, le détenteur des téléphones doit confirmer le build installé et exécuter
les cas ci-dessous. La recette mobile reste bloquée jusqu'à cette étape.

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
