# Lot 8 — preuves de livraison (2026-10-09)

Base : `32e723a330805d0154bfca4b9ceed09a8af126db`, Claude S146–S149 et lot 7 inclus.
Aucune nouvelle PR Claude ouverte au début du chantier.

## Plan avant implémentation

1. Reproduire une signature devenue incohérente après changement d'issue,
   ainsi qu'une capture vide, dans les tests écran → API → client de S146.
2. Réinitialiser le tracé et son indicateur au changement d'issue.
3. Bloquer les changements de formulaire pendant la capture et l'envoi.
4. Ne pas transmettre une déclaration sans la signature que le livreur a choisi
   de joindre : une capture manquante est réessayable, sans écriture serveur.
5. Vérifier les suites mobiles, typages, lints et exports Android ; publier en PR.

Les signatures restent facultatives pour une livraison ou un retour (S106).
Le backend reste l'autorité : aucun nouvel endpoint ni calcul client.
Les parcours Maestro S149 et les corrections réseau du lot 7 sont conservés.

## Constat de code

Le canevas est démonté pour une livraison partielle, mais `hasSignature` reste
vrai. Au retour vers une issue avec signature, le nouveau canevas est vide ;
`capture()` peut rendre `null`, silencieusement converti en absence de pièce.
Les choix d'issue et les preuves restent aussi modifiables pendant la capture.

## Validation

La première reproduction donne trois échecs et quatorze succès : indicateur
de signature conservé, capture vide envoyée, changement d'issue pendant capture.
Les six nouveaux cas vérifient aussi le passage direct livraison → retour,
les captures vides des deux issues, la nouvelle tentative et la poursuite sans
signature après effacement explicite. Aucun module d'API n'est simulé.

`changeAction()` efface le tracé et l'indicateur ensemble ; le canevas porte une
clé par issue. Les choix, champs et preuves sont bloqués pendant l'envoi et
après succès. `ChoiceGroup.disabled` est reporté dans les deux apps, sans changer
les repères Maestro. Une capture vide est expliquée en français et n'envoie rien.
Un échec de capture reste réessayable ; l'effacement explicite permet de continuer
sans signature, conformément au caractère facultatif de S106.

Suites locales : **197 tests marchand et 242 tests livreur réussis**.
Typages et lints des deux apps réussis. Deux exports Android Hermes réussis,
avec clé API factice, réservés à la validation et non distribuables.
`expo-doctor` a été exécuté pour les deux apps : 19/21 contrôles réussis ;
schéma Expo et métadonnées React Native Directory non vérifiables ici
(DNS `exp.host` indisponible / réponse inattendue du service). Aucun fichier
de configuration ou de dépendance n'est modifié par ce lot. Rejouer ces deux
contrôles depuis la VM de build avant reconstruction EAS.

Recette sur téléphone nécessaire
avec un nouvel APK livreur : signer, changer d'issue, signer de nouveau, envoyer
et vérifier la preuve depuis le suivi marchand et le back-office de recette.
Aucun cas terrain n'est déclaré réussi dans ce document.
