# Parcours Maestro — rejouer les apps sur un vrai Android (S149)

> Les tests Jest prouvent la logique des apps contre un serveur simulé (S146). Ils ne disent
> rien de l'APK construit par EAS, du serveur de recette réel, ni des modules du téléphone.
> Maestro rejoue des parcours sur l'APK installé, contre le serveur qu'il vise. Il **ne
> remplace pas** la recette humaine du `README.md` (SMS, USSD, photo, réseau terrain) : il
> évite de la commencer sur un build cassé.

## Ce qui est écrit

| App | Parcours `lecture` (par défaut) | Parcours `ecriture` (sur demande) |
|---|---|---|
| `mobile/.maestro/` (`app.beninlink.marchand`) | 01 connexion refusée · 02 tableau de bord · 03 colis (onglets, fiche) · 04 portefeuille (sans payer) · 05 relevés · 06 déconnexion | 10 créer un colis « Client Maestro » |
| `mobile-livreur/.maestro/` (`app.beninlink.livreur`) | 01 connexion refusée · 02 courses (fiche, écran de statut, **sans enregistrer**) · 03 gains · 04 profil et déconnexion | 10 livrer la première course en cours (sans photo ni signature) |

- `subflows/connexion.yaml` ouvre l'app données effacées, permissions accordées, et se
  connecte avec `${MAESTRO_IDENTIFIANT}` / `${MAESTRO_MOT_DE_PASSE}`. **Aucun identifiant
  n'est écrit dans le dépôt** ; seul le mot de passe volontairement faux du parcours 01 l'est.
- `config.yaml` exclut le tag `ecriture` : un `maestro test .maestro` ne modifie rien sur le
  serveur de recette.
- Les parcours visent les écrans par leur **texte français** (celui de `src/i18n/fr.ts`) et par
  quelques `testID` (champs de connexion, champs du formulaire de colis, onglets livreur).
  `src/maestro.test.ts`, dans chaque app, échoue si un texte visé n'existe plus dans `fr.ts`
  ou si un `id:` ne correspond à aucun `testID` : renommer une phrase casse Jest, pas le
  parcours en silence.

## Pré-requis des comptes de recette

- **Marchand** : au moins une boutique, une catégorie, un type de livraison actif, une première
  zone non export, et un solde de portefeuille suffisant pour le parcours 10.
- **Livreur** : au moins une course « En cours » pour la fiche du parcours 02 (sinon la fiche est
  sautée) ; le parcours 10 **consomme** cette course.
- Sur le téléphone de test : désactiver la saisie automatique Android (sa proposition
  d'enregistrer le mot de passe couvre l'écran).

## En local (un poste avec Android et `adb`)

```bash
curl -fsSL https://get.maestro.mobile.dev | bash          # une fois
adb install -r beninlink-marchand.apk                     # l'APK de recette EAS
cd mobile
maestro test -e MAESTRO_IDENTIFIANT=... -e MAESTRO_MOT_DE_PASSE=... .maestro
# un parcours d'écriture, explicitement :
maestro test -e MAESTRO_IDENTIFIANT=... -e MAESTRO_MOT_DE_PASSE=... .maestro/flows/10-creer-un-colis.yaml
```

Même chose dans `mobile-livreur/` avec l'APK livreur et le compte livreur. Taper les
identifiants dans le terminal, jamais dans un fichier versionné ni dans un message.

## Sur GitHub (émulateur)

Workflow **« Parcours Maestro (APK de recette) »** (`.github/workflows/maestro.yml`),
déclenchement **manuel seulement** (onglet Actions → Run workflow) : app, lien `https` de
l'APK EAS, case « écriture ». Il demande quatre secrets du dépôt, à poser par le porteur :
`MAESTRO_MARCHAND_IDENTIFIANT`, `MAESTRO_MARCHAND_MOT_DE_PASSE`, `MAESTRO_LIVREUR_IDENTIFIANT`,
`MAESTRO_LIVREUR_MOT_DE_PASSE`. Le rapport JUnit et les captures sont joints au run. Compter
une quinzaine de minutes de runner par app (quota Actions).

## Limites connues

- Les parcours n'ont **pas encore été exécutés** : écrits depuis le code des écrans, sans
  émulateur. Le premier passage dira les ajustements (attentes, défilements selon la taille
  d'écran).
- Hors de portée de Maestro : la réception d'un SMS, le paiement USSD, une vraie photo. Ces
  cases restent humaines (`README.md`, §4).
