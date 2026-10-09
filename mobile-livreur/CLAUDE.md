# CLAUDE.md — mobile-livreur/ (app livreur · React Native / Expo)

> Chargé quand Claude Code travaille dans mobile-livreur/. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici.

## Périmètre & financement
La ligne 11 (fenêtre Idéation) ne finance que l'app marchand. **Fenêtre Création ouverte
le 2026-09-05 à la demande du porteur** : v1 (6 écrans, 12 endpoints) puis v2 le même
jour (position GPS, photo de livraison, mot de passe, icônes), v3 (signature, visuels
d'app). Ne pas imputer ce temps
à la ligne 11.

## Rôle
Application **livreur (coursier)**, React Native / Expo. **Consomme l'API de `web/`**
(tag « Livreur » de `GET /api/v10/openapi.json`, lisible sur `/api/docs`).

## Décisions actées
- **Mêmes fondations que `mobile/`** : Expo 57, expo-router, TypeScript strict, charte
  (`src/theme`), briques (`src/components/ui.tsx`), argent (`src/domain/money.ts`),
  statuts (`src/domain/parcelStatus.ts`), client d'API (`src/api/client.ts`) repris à
  l'identique. Une évolution de ces fichiers se reporte dans les deux apps.
- Devise **XOF** entière · Locale **FR** (`src/i18n/fr.ts`). Ne jamais inventer d'endpoint.
- URL d'API et clé en variables d'environnement (`.env.example`).
- Connexion par **`driver_id`** (`users.unique_id` du livreur), jamais le téléphone.
- Le jeton porte l'ability `deliveryman` (S5) : les routes marchand répondent 403.
- Aucun montant calculé dans l'app : livré / partiel / retour ne transmettent que
  l'action (et le montant réellement encaissé pour le partiel) — le backend recalcule.
- **Le barème de livraison n'est pas dans le contrat de cette app** (**D4**, vérifié
  le 2026-09-06) : aucun endpoint de tarif dans `src/api/endpoints.ts`, et aucun écran
  n'affiche `deliveryType`. Un livreur encaisse un montant que le serveur a déjà
  calculé ; il ne consulte pas la grille. La refonte par zones n'a donc rien à y
  changer — l'app marchand est la seule concernée.

## Écrans (expo-router)
| Écran | Fichier | Endpoints |
|---|---|---|
| Connexion | `(auth)/login.tsx` | `deliveryman/login` |
| Mot de passe oublié (S98 : lien, puis jeton + nouveau mot de passe, repris de `mobile/`) | `(auth)/forgot-password.tsx`, `(auth)/reset-password.tsx` | `password/email`, `password/reset` (sans jeton, limités S96) |
| Mes courses (En cours / Retours / Livrés) | `(app)/(tabs)/index.tsx` | `deliveryman/dashboard` (4 listes en `ParcelResource`) |
| Détail (marchand, colis, destinataire, historique, Appeler / Itinéraire) | `(app)/parcel/[id]/index.tsx` | `deliveryman/parcel/details/{id}` |
| Issue de la course (livré / partielle / retour + montant) | `(app)/parcel/[id]/status.tsx` | `deliveryman/parcel-status-update` (`status_action`) |
| Gains | `(app)/(tabs)/earnings.tsx` | `deliveryman/profile`, `income-expense`, `parcel-payment-logs` |
| Profil | `(app)/(tabs)/profile.tsx` | `deliveryman/profile`, `sign-out` |
| Mot de passe | `(app)/profile/password.tsx` | `update-password` (8 caractères — S134, confirmation) |

### v2 (2026-09-05)
- **Position** : `src/domain/location.ts` (`expo-location`, autorisation « pendant
  l'utilisation » seulement — pas de suivi en arrière-plan). Bouton « Partager ma
  position » sur Mes courses, et envoi silencieux après chaque déclaration de livraison.
  Le backend l'écrit sur les courses en cours du livreur authentifié (S4, S7).
- **Preuve de livraison** : `src/domain/photo.ts` (`expo-image-picker`, appareil photo,
  qualité 0,5) et `src/components/SignaturePad.tsx` (`react-native-svg` +
  `react-native-view-shot`, PNG sur fond blanc). Photo et signature du destinataire
  facultatives sur « Livré », envoyées en multipart à
  `deliveryman/parcel/delivered/{id}` (`image`, `signatureImage`) ; partiel et retour
  restent sur `parcel-status-update`. Le client d'API laisse passer un `FormData`
  tel quel. Le marchand voit les deux preuves dans le suivi du colis
  (`ParcelEvent.delivered_image` / `signature_image`, URL absolues).
- **Icônes** : Ionicons (`@expo/vector-icons`).
- **Visuels d'app** (icône, adaptive icon Android + monochrome, splash, favicon) :
  motif « colis en mouvement » (colis ocre barré de vert, traits de vitesse blancs) sur
  fond vert. Source unique `assets/source/generate.py` (SVG rendu par Chromium headless,
  Sora depuis node_modules) — régénérer les PNG plutôt que les retoucher à la main.
  ⚠️ `CHROME` doit désigner un binaire **headless** : avec une interface, la capture sort
  tronquée en bas sans erreur. Le script le refuse (contrôle `opaque=True`, ajouté le
  2026-09-06 avec les visuels de `mobile/`).

## Statuts colis (alignés backend)
En attente → Ramassage assigné → Entrepôt → Livreur assigné → Livré ; + Livraison partielle, Retour.
Codes dans `src/domain/parcelStatus.ts` (jamais redéfinis côté app).

## Design system
Identique à `mobile/` : Vert `#12503A` · Ocre `#E0A63C` · Sora + DM Sans · FCFA entiers.
Icônes Ionicons ; visuels d'app générés depuis `assets/source/generate.py`.

## Notifications poussées (D11)
- Transport **Expo**, routes communes aux deux apps : `push/register` à l'ouverture de
  session, `push/forget` **avant** la déconnexion (`src/push`).
- C'est le **seul** canal du livreur : il n'a pas de fil consultable. Une affectation de
  course arrive par là, et le toucher ouvre directement la course.
- Un refus de permission ne bloque rien — les courses restent visibles dans l'onglet.
- **Douane (S95)** : `fetchParcelDetails()` rend aussi `customsAlerts` (`customs_alerts ?? []`, un
  serveur d'avant S95 n'envoie pas la clé) ; l'écran de course montre `CustomsNotice` juste sous
  l'en-tête — le document à demander au marchand **au ramassage**, gravité par
  `customsLevelColorName` (`src/domain/customsLevel.ts`, valeurs du contrat). Lecture seule : pas
  de bouton, le traitement est au transporteur (S68). Carte rendue en test (`CustomsNotice.test.tsx`).
  **S101** : la liste des courses lit `customs_pending` sur chaque `ParcelSummary` (optionnel) et pose une
  pastille « Douane » : le livreur sait quel colis a un document à collecter avant d'ouvrir la course.
  **S103** : la carte d'une course est un composant extrait, `ParcelCard` (`src/components/`), rendu en
  test (pastille, montant à encaisser en FCFA entiers, Appeler / Itinéraire inactifs sans numéro ou adresse,
  toucher) ; l'écran de la liste ne fait que la poser.
  **S106 (R8)** : la signature est proposée aussi pour un **retour** (facultative, texte propre) ;
  `reportOutcome()` l'envoie en multipart sous `signatureImage`, le JSON du socle sinon.

## Commandes
- Lot 8 : changement d'issue = signature remise à zéro ; formulaire figé pendant capture/envoi ; capture manquante réessayable sans déclaration. Signature facultative conservée. Voir `../docs/LOT_8_PREUVES_LIVRAISON.md`.
- Lot 7 : actualisation distincte de la restauration, panne réseau propagée sans effacer le compte ; 401 déconnectant et réponse tardive ignorée si le jeton a changé. Voir `../docs/LOT_7_SESSION_RESEAU.md`.
- `npm install` · `npx expo start` · `npm run typecheck` · `npx expo lint` · `npm test`
  (**S84** : Jest `jest-expo`, les modules purs `money` et `parcelStatus` ; le job `apps` du
  workflow rejoue typage, lint et tests sur la pull request). **S146** : le client HTTP, le jeton,
  `auth` / `deliveryman`, `photo` / `location` et `t()` ont leurs tests unitaires ; quatre écrans
  (connexion, statut d'une course, gains, mots de passe) sont rendus **avec les vrais modules d'API
  et le vrai `client.ts`**, seul `fetch` simulé, avec les modules de l'appareil (SecureStore, caméra,
  position, `expo-router`, signature). Un envoi de fichier se teste avec le `FormData` de React Native :
  celui de Node rend `{uri, name, type}` en « [object Object] ». Chaque `beforeEach` fait
  `jest.resetAllMocks()` : une réponse `mockResolvedValueOnce` non consommée ne passe pas au test suivant.
- **Le contrat avec `web/` est tenu en PHPUnit** (**S85**, `web/tests/Feature/DeliverymanAppContractTest`
  et `ParcelStageTest`) : chaque entrée de `src/api/endpoints.ts` existe dans la spec et n'est
  jamais réservée au type marchand ; l'app appelle ce qu'elle inventorie (les entrées sans
  appelant sont nommées dans `SANS_APPELANT`, avec leur motif) ; les trois issues de
  `reportOutcome()` sont exactement celles du catalogue `ApiParcelStatus` et du `switch` du
  serveur ; `BackendParcelStatus` recopie l'enum mot pour mot ; aucune route livreur ne pagine.
  Ajouter un endpoint à l'inventaire, c'est l'appeler quelque part ou le motiver ; déclarer une
  issue nouvelle, c'est la faire accepter par `web/` d'abord.
- **`npx expo-doctor` avant tout build** : il attrape les erreurs de configuration qui,
  sinon, font échouer EAS après dix minutes de file d'attente. 21/21 au 2026-09-06.
- Build : `eas build -p android` (APK/AAB) · `eas build -p ios`.
  Les prérequis (compte Expo, `eas init`, `eas credentials`) sont décrits dans
  `mobile/CLAUDE.md` — ils valent à l'identique ici, avec un **keystore distinct** :
  deux applications publiées, deux signatures.
- `.env` : copier `.env.example` (`EXPO_PUBLIC_API_URL`, `EXPO_PUBLIC_API_KEY`).
- **Partiel (lot 3, 2026-10-09)** : montant vide refusé ; zéro explicite permis. Les deux routes serveur et le
  repository exigent un entier XOF non négatif. Gains : dépenses et revenus additionnés séparément.
  Voir `../docs/LOT_3_CORRECTIONS.md`.
- Dépendances propres à cette app (en plus de `mobile/`) : `expo-location`,
  `expo-image-picker`, `@expo/vector-icons` — textes d'autorisation dans `app.json`.
- **Un jeton qui tombe déconnecte l'écran** (**S144** ; **S147** : même quand le 401 arrive en page HTML d'un proxy, `client.ts` efface le jeton avant de lire le corps ; **S148** : un `signal` déjà abandonné, ou abandonné pendant la lecture du jeton, n'envoie rien — l'erreur dit « Requête annulée. », jamais « expirée », et l'écouteur posé sur le signal est retiré après la réponse) : le client d'API efface le jeton sur un 401, et `clearToken()`
  prévient `SessionProvider` (`onTokenCleared`), qui revient à la connexion. Depuis S135/S136 le serveur révoque des jetons
  en cours de session ; un écran ne garde jamais un compte dont le jeton n'existe plus (`SessionProvider.test.tsx`).
- **Parcours Maestro** (**S149**) : `.maestro/` rejoue les écrans sur l'APK de recette installé
  (`docs/guides/recette-pilote/MAESTRO.md`). Les parcours visent le texte de `src/i18n/fr.ts` et
  quelques `testID` ; `src/maestro.test.ts` échoue si un texte visé disparaît de `fr.ts` ou si un
  `id:` ne correspond à aucun `testID`. Renommer une phrase ou retirer un `testID` utilisé par un
  parcours, c'est mettre le parcours à jour dans le même commit. Aucun identifiant dans les
  parcours : `${MAESTRO_IDENTIFIANT}` / `${MAESTRO_MOT_DE_PASSE}`. Le tag `ecriture` (création de
  colis, livraison) est exclu par `config.yaml`.
