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

## Statuts colis (alignés backend)
En attente → Ramassage assigné → Entrepôt → Livreur assigné → Livré ; + Livraison partielle, Retour.

## Design system
Vert `#12503A` · Ocre `#E0A63C` · rouge = incident. Sora (titres/chiffres) + DM Sans (corps).

## Commandes
- `npm install` · `npx expo start` · `npx expo lint`
- Build : `eas build -p android` (APK/AAB) · `eas build -p ios`

## Conventions
- Réutiliser les patterns de l'app (navigation, services d'API, i18n) ; repérer un écran
  existant avant d'en créer un. Toute chaîne visible passe par l'i18n FR.
- Chaînes **natives** (nom d'app, demandes de permission iOS/Android) : `src/i18n/expo-fr.json`,
  déclaré par `expo.locales` dans `app.json`. Sans ce fichier, Expo ne fait qu'avertir au
  `prebuild` et les textes natifs restent en anglais.
