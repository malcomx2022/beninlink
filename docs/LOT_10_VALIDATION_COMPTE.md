# Lot 10 — messages de validation du compte (2026-10-09)

Base : `38dde8f9395ed0ab67041e6056b6ec4ea97c2341` ; Claude S146–S149 et
lots 7–9 inclus. Aucune nouvelle PR Claude ouverte au début du chantier.

## Plan avant implémentation

1. Reproduire les refus de validation du profil marchand et du mot de passe
   par les routes réelles, en français et en anglais (négociation S90).
2. Remplacer uniquement les messages trompeurs/génériques des réponses 422.
3. Conserver les règles, l'enveloppe et les erreurs `data.message` par champ.
4. Vérifier qu'un refus ne modifie ni le compte ni ses jetons ; garder les
   confirmations de réussite et les garanties S134–S136.
5. Exécuter la suite Laravel complète avant commit selon les règles Claude,
   vérifier les apps consommatrices, puis PR et fusion.

## Cartographie et constat

Étape 0 : `web/CLAUDE.md`, blocs F et I de `web/CARTOGRAPHIE.md` lus.
`AuthController::profileUpdate()` emploie `auth.profile_update` pour le succès
et pour le refus 422 : « Profil mis à jour avec succès » dans les deux cas.
`updatePassword()` renvoie `auth.update_password` lors d'un refus de format,
simple intitulé « Mettre à jour le mot de passe ».
Les deux apps affichent le message de l'API. La correction part du contrat
serveur : aucun changement d'endpoint, d'autorisation, de stockage ou de règle.

## Validation et recette

Reproduction avant correction : **6 échecs, 2 succès**. Les réponses invalides
annoncent le succès du profil ou donnent seulement le titre de l'action de mot
de passe, en FR comme en EN. Les deux succès de profil servent de témoin.

La correction utilise une clé commune `auth.validation_error` dans les deux
refus, avec les traductions FR/EN ; les confirmations restent inchangées.
Les huit nouveaux cas passent par les routes, la validation et les repositories
réels. Ils vérifient les erreurs par champ et la conservation des données,
du mot de passe et des deux jetons du compte ; le profil valide est enregistré.
Validation ciblée (nouveaux cas, S90 et S135) : **18 tests, 112 assertions**.
Les suites mobiles passent : **205 tests marchand et 242 tests livreur**.
Syntaxe des quatre fichiers PHP modifiés valide.

Premier passage complet : 1 456 succès et un échec de téléversement PNG dû
aux droits du répertoire local `public/uploads/support`, créé par un ancien
conteneur sous un autre utilisateur. Accès local rétabli, sans modifier le
code de téléversement : les quatre tests `UploadedPhpNeverLandsTest` passent
(12 assertions). Suite complète relancée avant commit : **1 457 tests réussis,
52 038 assertions** (PHP 8.3, SQLite, sans réseau). Aucun changement de code
hors messages de validation et tests du lot.

Sur recette, provoquer un refus de profil et
de mot de passe ; vérifier un message de correction, les erreurs par champ et
les données conservées. Aucun cas terrain déclaré réussi ici.
