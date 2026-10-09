# Lot 5 — saisie de l'inscription marchand (2026-10-09)

## Plan

1. Reprendre le constat des captures du lot 4 sans conserver de données personnelles.
2. Clarifier les champs obligatoires et distinguer les exemples des valeurs.
3. Signaler les champs vides avant l'appel réseau ; conserver la validation serveur.
4. Vérifier le formulaire, ses erreurs et la navigation SMS, puis publier en PR.

## Constat et corrections

Les captures montrent un RCCM vide (l'exemple gris ne constitue pas une saisie),
un indicatif téléphonique à vérifier et un bouton proche de la barre système
Android. Le faux message de succès et la requête SMS incomplète ont été corrigés
par la PR #219 ; ce lot facilite la saisie en amont.

- IFU et RCCM marqués obligatoires, CNSS toujours optionnelle.
- Indication explicite que le RCCM réel est nécessaire et que le texte gris est un exemple.
- Exemple téléphonique national actuel et aide pour l'indicatif béninois 229.
- Minimum de huit caractères indiqué pour le mot de passe, selon le serveur.
- Champs obligatoires vides signalés localement ; aucun appel API dans ce cas.
- Espace inférieur adapté à la zone système Android pour garder le bouton accessible.
- Libellés accessibles associés aux champs via le composant existant `Field`.

Aucune modification des règles métier, des endpoints ou de la normalisation
serveur des numéros. Aucun remplacement automatique d'un indicatif saisi.
Les refus serveur restent affichés par champ. Architecture Expo-router et
chaînes françaises centralisées conservées.

## Fiabilité de la recette automatisée

La CI du commit de fusion #219 a réussi Laravel, l'app livreur, la répétition
et les déploiements, mais le premier test portefeuille marchand a de nouveau
dépassé son délai de cinq secondes. Les nouvelles suites inscription/SMS ont
réussi. La synchronisation du lot 4 n'a donc pas éliminé toute intermittence.
Le job apps exécute désormais Jest en série (`--runInBand`), comme lors des
validations locales, afin de limiter la concurrence de chargement du runtime
React Native sur le runner. Aucun délai ni assertion n'est assoupli. La CI
de ce lot doit confirmer le résultat ; il ne suffit pas d'un passage local.

## Recette après reconstruction

Validation locale : 57 tests marchand réussis, dont une exécution sans cache ;
28 tests livreur réussis avec la commande CI ; typage et lint marchand réussis ;
export Android Hermes réussi ; 9 tests du contrat de déploiement réussis
(63 assertions). La confirmation CI reste à relever sur la PR.

Installer un nouvel APK marchand contenant #219 et ce lot. Soumettre un RCCM
vide : correction demandée sans requête réseau. Renseigner le RCCM réel, vérifier
le téléphone, puis soumettre : les erreurs serveur restent visibles, et une
inscription acceptée conduit à la vérification SMS avec le numéro renvoyé.
Vérifier le bouton avec navigation Android par boutons et par gestes, ainsi
qu'avec le clavier ouvert. La recette sur appareil reste à réaliser.
