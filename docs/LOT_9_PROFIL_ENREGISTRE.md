# Lot 9 — confirmation d'enregistrement du profil (2026-10-09)

Base : `5b1f6cb1b3f1aa90ccbbb4ca3eeada56e772326b` ; Claude S146–S149,
lots 7 et 8 inclus. Aucune nouvelle PR Claude ouverte au début du chantier.

## Plan avant implémentation

1. Reproduire une modification acceptée par `profile/update`, suivie d'une
   erreur réseau/503 sur `profile`, avec l'écran, la vraie session et le client.
2. Distinguer l'écriture confirmée de la relecture du compte.
3. Afficher l'enregistrement acquis, figer les champs et proposer une relecture
   seule : la nouvelle tentative n'envoie pas une seconde modification.
4. Conserver les erreurs de validation serveur et la déconnexion sur 401.
5. Vérifier les suites mobiles et les contrôles de build, puis PR et fusion.

Le serveur et ses normalisations restent l'autorité : pas de profil remplacé
par une copie locale du formulaire. Aucun nouvel endpoint ni changement du backend.
Les pratiques de tests S146, les garanties de session du lot 7 et les repères
Maestro S149 restent applicables. Les références Claude sont actualisées par lot.

## Constat

L'écran enchaîne `updateProfile()` puis `refresh()` dans le même bloc d'erreur.
Une relecture refusée après écriture réussie laisse le bouton d'enregistrement
actif sans confirmation de ce qui a déjà été enregistré.

## Validation et recette

La première reproduction donne trois échecs et cinq succès. Les huit tests
exercent la vraie session et le client, avec les outils Claude de S146 :
envoi des cinq champs, relecture et navigation, champ obligatoire vide, refus
422 par champ, refus serveur de l'écriture, relecture réseau/503, 401 et lenteur.

L'état `saved` est établi seulement après acceptation de l'écriture. Les champs
restent figés ; « Informations enregistrées » est affiché et le bouton devient
« Actualiser le profil ». Une nouvelle tentative appelle la relecture seule.
Les valeurs de session proviennent toujours de la réponse serveur, jamais du
formulaire local. Un refus de l'écriture permet de modifier et soumettre à
nouveau ; le client réel continue de supprimer le jeton sur 401.

Sur APK marchand reconstruit : modifier le profil,
interrompre le réseau après acceptation, vérifier la confirmation et la tentative
de relecture, puis comparer les valeurs avec le back-office de recette.
Aucun résultat terrain n'est déclaré validé ici.

Validation locale : **205 tests marchand et 242 tests livreur réussis** ;
typages et lints des deux apps réussis ; export Android marchand Hermes réussi.
Cet export utilise une clé factice et ne constitue pas un APK distribuable.
`expo-doctor` marchand : 19/21 contrôles réussis ; le schéma distant Expo et
les métadonnées React Native Directory restent inaccessibles depuis cet
environnement (DNS `exp.host` / réponse inattendue). Les rejouer sur la VM de
build avant EAS. Aucun changement de dépendance ou de configuration.
