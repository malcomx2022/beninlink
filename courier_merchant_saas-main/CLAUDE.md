# CLAUDE.md — courier_merchant_saas-main/ (app marchand Flutter)

> Chargé quand Claude Code travaille dans ce dossier. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici.

## Rôle
Application **marchand** (Flutter / Dart) : création de colis, suivi, wallet,
recharge par Mobile Money. Elle **consomme l'API de web/** ; elle n'a aucune
logique métier propre au paiement.

## Décisions actées
- On **garde cette app Flutter** : on l'adapte (rebranding, FR, config API),
  on ne la réécrit pas.
- Devise **XOF** : affichage **entier**, sans décimales.
- Locale **FR** par défaut.

## FedaPay côté marchand (important)
- L'app **n'accède jamais** aux clés FedaPay.
- Recharge wallet = appeler `POST /fedapay/initiate` sur web/, récupérer
  `payment_url`, puis l'ouvrir dans une **WebView** (le client choisit MTN/Moov
  et valide par USSD).
- Le solde n'est considéré à jour **qu'après confirmation serveur** (webhook),
  pas sur le simple retour de WebView : rafraîchir via l'endpoint de solde.

## Configuration
- URL d'API dans une variable d'environnement / config (sandbox vs prod) ;
  cible par défaut `https://beninlink.app`. Ne pas coder l'URL en dur.

## Chantiers qui touchent cette app
- Francisation des chaînes (fichiers d'internationalisation Dart) + format XOF.
- Formulaire d'onboarding marchand : champs **IFU / RCCM / CNSS**.
- Écran de recharge wallet (WebView FedaPay).
- Affichage des factures SYSCOHADA (lecture seule).

## Commandes
- Dépendances : `flutter pub get`
- Analyse statique : `flutter analyze`
- Tests : `flutter test`
- Build Android : `flutter build appbundle` (ou `apk` pour test)
- Build iOS : `flutter build ipa`

## Conventions
- Réutiliser les patterns déjà présents dans l'app (state management, services
  d'API) ; repérer un écran existant avant d'en créer un.
- Toute évolution d'API vient d'abord de web/ : ne pas inventer d'endpoint.
