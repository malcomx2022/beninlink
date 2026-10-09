# CLAUDE.md — mobile/ (app marchand · React Native / Expo)

> Chargé quand Claude Code travaille dans mobile/. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici.

## Rôle & financement
Application **marchand (PME)**, React Native / Expo. **Financée — ligne 11 / TDR-L8**
(livrable : app mobile-first React Native + APK Android pour les tests des 5 PME pilotes).
Elle **consomme l'API de `web/`** ; aucune logique de paiement propre.

## Décisions actées
- **React Native / Expo** (acté Option A ; ne pas repartir sur Flutter).
- Devise **XOF** : affichage **entier**, sans décimales. Locale **FR** par défaut.
- **Ne jamais inventer d'endpoint** : toute évolution d'API vient d'abord de `web/`.
  Le contrat est la spec OpenAPI servie par `GET /api/v10/openapi.json` (lisible sur
  `/api/docs`) ; `src/api/endpoints.ts` est vérifié contre elle par `OpenApiSpecTest`.
- URL d'API en **variable d'environnement** (sandbox vs prod). Ne pas coder en dur.

## FedaPay côté marchand
- L'app **n'accède jamais** aux clés FedaPay.
- Recharge wallet = `POST /fedapay/initiate` → récupérer `payment_url` → **WebView**
  (le client choisit MTN/Moov, valide par USSD).
- Le solde n'est à jour **qu'après confirmation serveur** (webhook) : rafraîchir via
  l'endpoint de solde, pas sur le retour de WebView.

## Où vivent les écrans
⚠️ **expo-router** (`"main": "expo-router/entry"`) : un écran est un fichier de
**`app/`**, et son chemin est sa route — `app/(app)/customs.tsx` → `/(app)/customs`.
Il n'y a **pas** de `src/screens/`, et il n'y en aura pas. `src/` tient ce qui n'est
pas un écran : `api/`, `domain/`, `components/`, `theme/`, `i18n/`, `push/`, `session/`.
Chercher un écran dans `src/` ne trouve rien — et ne prouve rien (S68).

## Écrans (référence maquette validée)
- Auth : connexion, **inscription PME (IFU/RCCM/CNSS)**, récupération mot de passe (SMS).
- Tableau de bord (KPIs, solde wallet, alerte douane, colis récents).
- Colis : liste (En cours/Livrés/Retours), **détail + timeline de suivi + alertes douanières du colis** (S82), **création**
  (Boutique, destinataire, Catégorie, Type Jour même/Lendemain/Sous-ville/Hors-ville,
  **Cash Collection/COD**, Prix de vente, N° facture).
- Portefeuille : solde, **Recharger** (FedaPay), **Retrait** (payout), historique.
- **Factures = relevés de règlement** (Encaissé COD − Frais − TVA = Net à reverser · PDF/CSV).
- **Alerte douanière** (déclenchée à la création export · 3 niveaux Info/Avertissement/Bloquant).
  Depuis **S82 (M1)** elle se lit aussi **sur le colis** : `parcel/logs/{id}` porte
  `customs_alerts`, les alertes **de ce colis**, et le détail les rend par la même table de
  couleurs (`customsLevelColorName`) avec « Marquer traitée ». Ne jamais filtrer côté app la
  liste paginée de `customs/alerts` pour retrouver celles d'un colis : elle ment dès la page 2.
- Boutiques (multi-shop), Tarifs (poids × zone), Notifications, Profil.
  L'écran **Tarifs** ne sert plus que le **barème par zones** (**D4**, **S91 / M4**) : la
  seconde forme (quatre colonnes héritées) est tombée avec l'étape 6, et depuis S86 aucune
  société ne se déploie sans zones — un `zones` vide est dit comme une anomalie du
  transporteur (`rates.noZone`). À la **création**, les choix de zone viennent de
  `src/domain/zoneChoices.ts` : une entrée par zone, **plus de « barème hérité »** (la valeur 0
  ne menait qu'à un refus, `zone_id` est obligatoire). Une zone d'export sans pays est signalée
  **avant** l'envoi — le serveur refuserait. `MerchantAppPricingContractTest` (web) lit ces sources.

## Statuts colis (alignés backend)
**Lot 3 (2026-10-09)** : confirmation seulement avec un devis courant ; TVA/autres frais figés dans le relevé.
Référence de recharge pending persistée par compte (stockage de session), bouton « Vérifier le paiement » après
réouverture ; « Reprendre le paiement » rouvre la même URL, sans nouvelle transaction ; refus et annulation distincts de l'attente. Voir `../docs/LOT_3_CORRECTIONS.md`.

En attente → Ramassage assigné → Entrepôt → Livreur assigné → Livré ; + Livraison partielle, Retour.

## Design system
Vert `#12503A` · Ocre `#E0A63C` · rouge = incident. Sora (titres/chiffres) + DM Sans (corps).

### Visuels d'app
- Motif **« la boutique qui expédie »** : auvent ocre à festons, devanture crème, et sur le
  comptoir le **colis du livreur** (carré ocre barré de vert). Les deux apps se lisent comme
  une famille — le livreur, c'est le colis en mouvement ; le marchand, c'est là où il part.
- Source unique : `assets/source/generate.py` (SVG rendu par Chromium **headless**, Sora
  depuis `node_modules`). **Régénérer les PNG plutôt que les retoucher à la main** :
  `CHROME=/chemin/vers/headless_shell python3 assets/source/generate.py`.
- ⚠️ Un Chromium avec interface réserve ~87 px de fenêtre : la capture sort **tronquée en
  bas sans aucune erreur**. Le script refuse désormais ce résultat (contrôle `opaque=True`) ;
  même garde-fou dans `mobile-livreur/`.

## Notifications poussées (D11)
- Transport **Expo** ; l'app envoie son jeton à `POST push/register` une fois connectée,
  et appelle `push/forget` **avant** la déconnexion (`src/push`). Aucun secret côté app.
- La permission est demandée **après** connexion seulement, et un refus ne bloque rien :
  le fil (`notifications/*`) reste consultable.
- Le texte affiché est celui rédigé par le serveur ; le toucher ouvre l'écran nommé par
  `data.kind` (colis, portefeuille, relevés, douane).
- Un build Android/iOS de production a besoin des identifiants de push via
  `eas credentials` ; en Expo Go, seul l'appareil physique reçoit.

## Commandes
- `npm install` · `npx expo start` · `npx expo lint`
- **`npx expo-doctor` avant tout build** : il attrape les erreurs de configuration qui,
  sinon, font échouer EAS après dix minutes de file d'attente. 21/21 au 2026-09-06.
- Build : `eas build -p android` (APK/AAB) · `eas build -p ios`

### Ce qu'un build EAS demande, et qui n'est pas dans le dépôt
Rien de tout ceci ne peut être versionné — ce sont des secrets et des liens de compte :

1. **un compte Expo** et `eas login` (ou `EXPO_TOKEN` dans l'environnement) ;
2. **`eas init`**, qui écrit `expo.extra.eas.projectId` et `expo.owner` dans `app.json` —
   ces deux clés sont **absentes à dessein** : elles rattachent le dépôt à un compte, et
   le choix du compte appartient au porteur ;
3. **`eas credentials`** : keystore Android et certificats iOS. ⚠️ Un keystore Android ne
   se change plus une fois l'app publiée — le générer, c'est un engagement définitif ;
4. pour le **push** (D11) : la clé de compte de service Google (FCM v1) côté Android et
   la clé APNs côté iOS, déposées elles aussi par `eas credentials`. Le backend n'en
   porte aucune.

## Conventions
- Réutiliser les patterns de l'app (navigation, services d'API, i18n) ; repérer un écran
  existant avant d'en créer un. Toute chaîne visible passe par l'i18n FR.
- **Un jeton qui tombe déconnecte l'écran** (**S144**) : le client d'API efface le jeton sur un 401, et `clearToken()`
  prévient `SessionProvider` (`onTokenCleared`), qui revient à la connexion. Depuis S135/S136 le serveur révoque des jetons
  en cours de session ; un écran ne garde jamais un compte dont le jeton n'existe plus (`SessionProvider.test.tsx`).
- Chaînes **natives** (nom d'app, demandes de permission iOS/Android) : `src/i18n/expo-fr.json`,
  déclaré par `expo.locales` dans `app.json`. Sans ce fichier, Expo ne fait qu'avertir au
  `prebuild` et les textes natifs restent en anglais.
- **Une liste servie paginée se parcourt.** Depuis **S78** l'enveloppe porte un bloc
  `page` à la racine (`current`, `per_page`, `last`, `total`) : le module d'API l'obtient
  par `api.getPaged()`, rend `{items, hasMore}` (`src/api/pagination.ts`), et l'écran ne
  lit que `hasMore` pour demander la suite sur `onEndReached` — idiome de
  `app/(app)/invoices.tsx`. Les constantes `*_PER_PAGE` restent le **repli** quand `page`
  manque (serveur d'avant S78) ; « une page pleine, donc il en reste » était avant S78 la
  seule déduction possible, et une constante fausse faisait perdre des lignes **en
  silence** (S68 : l'écran douane en lisait 20 sur N). `MerchantAppCustomsContractTest`
  compare ces constantes aux `paginate()` du serveur et vérifie que les modules lisent
  `page`. Un écran ne compare plus lui-même une longueur à une constante.
- **`src/domain/` n'importe rien.** Les tables de correspondance y sont pures : elles
  rendent un code ou un **nom** de couleur, jamais une valeur de thème. L'écran résout le
  nom dans `colors`. C'est ce qui les garde lisibles depuis les tests de `web/`.
- ⚠️ **Une couleur de gravité vient de la charte, pas du goût.** `colors.ts` nomme
  `danger` (BLOQUANT), `warning` (AVERTISSEMENT) et `info` (INFO) pour la douane. L'ocre
  est réservé aux **actions clés**, et le vert primaire se lit « tout va bien » : les
  employer pour une alerte ment sur sa gravité (S68).
- **Deux niveaux de tests, deux rôles** (**S84 / M2**). `npm test` (Jest, préréglage
  `jest-expo`, `@testing-library/react-native` 14 — `await render`, `await fireEvent`) **exécute**
  ce qui est à l'app : les modules purs de `src/domain/` et `src/api/pagination.ts`, et les
  composants extraits pour être rendus (`CustomsAlertCard`). **S101** : la liste des colis lit
  `customs_pending` (optionnel, serveur d'avant S101 toléré) et pose une pastille « Douane » sur la carte.
  **S103** : cette carte est un composant extrait, `ParcelCard` (`src/components/`), rendu en test
  (pastille, montant FCFA entiers, statut du backend, toucher) ; l'écran `parcels.tsx` ne fait que la poser. Le **contrat** avec `web/` reste
  tenu en PHPUnit dans `web/tests/` — `OpenApiSpecTest` (endpoints), `ParcelStageTest`
  (statuts), `MerchantAppCustomsContractTest` (tailles de page, valeurs des enums, la carte
  bien branchée) — parce qu'un test d'app ne peut pas lire `web/`. Un écran `expo-router`
  ne se rend pas en test : on en **extrait** le morceau à prouver en composant. Avant tout
  commit : `npx tsc --noEmit`, `npx expo lint`, `npm test` ; le job `apps` du workflow les
  rejoue sur la pull request (sans conditionner le déploiement serveur).
