# CLAUDE.md — mobile-livreur/ (app livreur · React Native / Expo)

> Chargé quand Claude Code travaille dans mobile-livreur/. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici.

## Périmètre & financement
La ligne 11 (fenêtre Idéation) ne finance que l'app marchand. **Fenêtre Création ouverte
le 2026-09-05 à la demande du porteur** : v1 (6 écrans, 12 endpoints) puis v2 le même
jour (position GPS, photo de livraison, mot de passe, icônes). Ne pas imputer ce temps
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

## Écrans (expo-router)
| Écran | Fichier | Endpoints |
|---|---|---|
| Connexion | `(auth)/login.tsx` | `deliveryman/login` |
| Mes courses (En cours / Retours / Livrés) | `(app)/(tabs)/index.tsx` | `deliveryman/dashboard` (4 listes en `ParcelResource`) |
| Détail (marchand, colis, destinataire, historique, Appeler / Itinéraire) | `(app)/parcel/[id]/index.tsx` | `deliveryman/parcel/details/{id}` |
| Issue de la course (livré / partielle / retour + montant) | `(app)/parcel/[id]/status.tsx` | `deliveryman/parcel-status-update` (`status_action`) |
| Gains | `(app)/(tabs)/earnings.tsx` | `deliveryman/profile`, `income-expense`, `parcel-payment-logs` |
| Profil | `(app)/(tabs)/profile.tsx` | `deliveryman/profile`, `sign-out` |
| Mot de passe | `(app)/profile/password.tsx` | `update-password` (6 caractères, confirmation) |

### v2 (2026-09-05)
- **Position** : `src/domain/location.ts` (`expo-location`, autorisation « pendant
  l'utilisation » seulement — pas de suivi en arrière-plan). Bouton « Partager ma
  position » sur Mes courses, et envoi silencieux après chaque déclaration de livraison.
  Le backend l'écrit sur les courses en cours du livreur authentifié (S4, S7).
- **Preuve de livraison** : `src/domain/photo.ts` (`expo-image-picker`, appareil photo,
  qualité 0,5). Photo facultative sur « Livré », envoyée en multipart à
  `deliveryman/parcel/delivered/{id}` (`image`) ; partiel et retour restent sur
  `parcel-status-update`. Le client d'API laisse passer un `FormData` tel quel.
- **Icônes** : Ionicons (`@expo/vector-icons`).

Non branché : signature manuscrite (`signatureImage`, demanderait un canevas de
dessin), icônes et splash propres à l'app (ceux de `mobile/` réutilisés).

## Statuts colis (alignés backend)
En attente → Ramassage assigné → Entrepôt → Livreur assigné → Livré ; + Livraison partielle, Retour.
Codes dans `src/domain/parcelStatus.ts` (jamais redéfinis côté app).

## Design system
Identique à `mobile/` : Vert `#12503A` · Ocre `#E0A63C` · Sora + DM Sans · FCFA entiers.
Icônes Ionicons ; les visuels d'app (icône, splash) restent à produire.

## Commandes
- `npm install` · `npx expo start` · `npm run typecheck` · `npx expo lint`
- Build : `eas build -p android` (APK/AAB) · `eas build -p ios`
- `.env` : copier `.env.example` (`EXPO_PUBLIC_API_URL`, `EXPO_PUBLIC_API_KEY`).
- Dépendances propres à cette app (en plus de `mobile/`) : `expo-location`,
  `expo-image-picker`, `@expo/vector-icons` — textes d'autorisation dans `app.json`.
