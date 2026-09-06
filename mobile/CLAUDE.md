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

## Écrans (référence maquette validée)
- Auth : connexion, **inscription PME (IFU/RCCM/CNSS)**, récupération mot de passe (SMS).
- Tableau de bord (KPIs, solde wallet, alerte douane, colis récents).
- Colis : liste (En cours/Livrés/Retours), **détail + timeline de suivi**, **création**
  (Boutique, destinataire, Catégorie, Type Jour même/Lendemain/Sous-ville/Hors-ville,
  **Cash Collection/COD**, Prix de vente, N° facture).
- Portefeuille : solde, **Recharger** (FedaPay), **Retrait** (payout), historique.
- **Factures = relevés de règlement** (Encaissé COD − Frais − TVA = Net à reverser · PDF/CSV).
- **Alerte douanière** (déclenchée à la création export · 3 niveaux Info/Avertissement/Bloquant).
- Boutiques (multi-shop), Tarifs (poids × zone), Notifications, Profil.
  L'écran **Tarifs** sert **les deux formes** (**D4**) : le barème par zones dès que
  `zones` n'est pas vide, sinon les quatre colonnes héritées. Ne jamais supprimer le
  second affichage tant que l'API sert encore les colonnes — c'est ce qui permet à
  cette version de tourner sur un serveur qui n'a pas basculé.
  Même règle sur la **création** : les sélecteurs *zone* et *délai* n'apparaissent
  que si `parcel/create` renvoie des zones, et « barème hérité » reste offert. Une
  zone d'export sans pays est signalée **avant** l'envoi — le serveur refuserait.

## Statuts colis (alignés backend)
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
- Chaînes **natives** (nom d'app, demandes de permission iOS/Android) : `src/i18n/expo-fr.json`,
  déclaré par `expo.locales` dans `app.json`. Sans ce fichier, Expo ne fait qu'avertir au
  `prebuild` et les textes natifs restent en anglais.
