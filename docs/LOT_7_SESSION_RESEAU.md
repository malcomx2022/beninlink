# Lot 7 — session et actualisation du profil (2026-10-09)

Base : `4fae1e2d3bca61aa39a32ac9084065fbb5482c55`, travaux Claude S146–S149 inclus.

## Plan avant correction

1. Reproduire l'actualisation du profil après connexion avec réseau interrompu.
   Vérifier aussi l'extraction du compte depuis `data.user`, forme du serveur et d'OpenAPI.
2. Séparer l'actualisation d'une session ouverte de sa restauration initiale.
3. Conserver le compte lors d'un incident réseau/serveur ; propager l'erreur.
4. Maintenir la déconnexion sur 401 et empêcher une réponse tardive après déconnexion.
5. Tester les deux apps avec le vrai client d'API et seulement `fetch` simulé,
   conformément à S146 ; vérifier typage, lint et bundles, puis publier en PR.

## Constat

`mobile/src/api/merchant.ts::fetchProfile()` rendait l'objet `data` comme un
`AuthUser`, alors que `AuthController::profile()` et OpenAPI portent le compte
sous `data.user`. Cette divergence peut masquer les données du compte au
redémarrage ou après actualisation. Le client doit extraire `user` ; aucun
changement d'endpoint ni de réponse serveur n'est nécessaire.

Les deux `SessionProvider` exposent `refresh: restore`. `restore()` efface le
compte dans son `catch`, quel que soit le refus : un incident réseau pendant
une session ouverte provoque donc une déconnexion d'interface. L'erreur est
aussi absorbée au lieu d'être rendue à l'écran appelant.

La correction porte sur l'actualisation d'une session déjà ouverte. La
restauration au démarrage continue de vérifier le jeton auprès du serveur ;
aucun profil persistant ni mode hors ligne n'est ajouté. Les garanties S144,
S147 et S148 et les conventions Maestro S149 restent applicables.

## Corrections et reproduction

Avant correction, les cinq premiers tests d'intégration marchand échouent sur
la forme réelle du profil. Côté livreur, les erreurs réseau/503 et la réponse
tardive échouent (trois échecs, deux succès).

Le module marchand extrait maintenant `user`. Les deux providers ont une
actualisation dédiée : une erreur remonte à l'appelant sans effacer le compte.
Une réponse de restauration ou d'actualisation est appliquée seulement si le
jeton est encore celui du départ. L'écouteur de suppression du jeton continue
de déconnecter lors d'un 401 ; les gardes d'accès serveur restent inchangés.

Les tests exercent le vrai client, le vrai stockage de session et le vrai
provider ; seuls `fetch`, SecureStore natif et le désabonnement push sont
simulés. Ils couvrent restauration, actualisation réussie, panne réseau, 503,
401 et réponses tardives lors de restauration ou d'actualisation.

## Recette après reconstruction

Validation locale : **197 tests marchand et 236 tests livreur réussis** ;
typages et lints des deux apps réussis ; deux exports Android Hermes réussis.
Les 14 nouveaux cas d'intégration sont inclus dans ces suites. Le backend et
la spécification OpenAPI sont conservés : le contrat existant était correct.

Installer les deux nouveaux APK depuis le commit fusionné. Vérifier le profil
marchand après relance. Sur session ouverte, couper le réseau puis provoquer
une actualisation : erreur affichée sans déconnexion, nouvelle tentative
possible après rétablissement. Révoquer le jeton avec les outils d'exploitation
de recette : l'appel suivant doit ramener à la connexion. Vérifier aussi la
déconnexion pendant une requête lente. Aucun de ces cas terrain n'est encore
déclaré validé ; aucune opération n'est réalisée sur des comptes de production.
