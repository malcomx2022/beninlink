# Lot 11 — chargement des comptes de retrait (2026-10-10)

Base : `f38e35d17565ebb1e4c9020d18d18321a83271bd`, Claude S146–S149 et lots
7–10 inclus. Déploiements du lot 10 vérifiés réussis sur recette et VPS.

## Plan avant implémentation

1. Reproduire le chargement lent, son refus et l'ajout confirmé suivi d'un refus
   de relecture, avec écran, vraie session, modules d'API et client (S146).
2. Distinguer données inconnues, erreur et liste vide ; bloquer les formulaires
   avant une lecture réussie. Proposer une nouvelle tentative de lecture.
3. Afficher la confirmation d'ajout indépendamment de la relecture. Si cette
   dernière échoue, ne pas proposer de recréer le compte déjà enregistré.
4. Bloquer les choix pendant l'envoi avec `ChoiceGroup.disabled` du lot 8.
5. Vérifier suites mobiles, typage, lint et export Android, puis PR et fusion.

Le backend reste l'autorité des comptes, du solde et des demandes. Aucun nouvel
endpoint, calcul financier ou changement du contrat. La session suit le lot 7.

## Constat

`accounts` commence vide et le formulaire d'ajout apparaît immédiatement.
`load()` intercepte son erreur dans `error`, mais celle-ci n'est affichée que
dans le formulaire de retrait, absent lorsque les comptes sont inconnus.
Après ajout accepté, une relecture échouée laisse donc un formulaire vide et
le message « aucun compte », sans confirmation visible de l'ajout acquis.

## Validation et recette

Première reproduction : quatre échecs, trois succès. La correction distingue
`loading` et `ready` ; aucun formulaire ni historique vide supposé avant les
deux lectures réussies. Erreur et confirmation sont visibles indépendamment
du formulaire. Une lecture refusée offre « Réessayer » avec GET uniquement.
L'ajout accepté reste confirmé ; les choix sont désactivés pendant l'envoi.

Huit cas d'intégration avec vraie session et client : lenteur, refus de chacune
des lectures, ajout acquis suivi d'un refus, validation 422, session 401,
opérateur figé pendant envoi et demande de retrait réussie. Seuls `fetch` et le
stockage natif sont simulés.

Validation locale : **213 tests marchand et 242 tests livreur réussis** ;
typages et lints des deux apps réussis ; export Android marchand Hermes réussi
avec clé factice, non distribuable. `expo-doctor` : 19/21, contrôles distants du
schéma Expo et de React Native Directory inaccessibles ici ; à rejouer depuis
la VM de build avant EAS. Aucun changement de configuration ou de dépendance.

Recette sur APK marchand reconstruit : ralentir
le chargement, provoquer son refus puis réessayer ; ajouter un compte pilote
et interrompre sa relecture. Vérifier l'ajout dans le back-office et qu'une
nouvelle tentative ne recrée pas ce compte. Aucun cas terrain validé ici.
