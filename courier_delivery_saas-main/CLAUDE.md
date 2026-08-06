# CLAUDE.md — courier_delivery_saas-main/ (app livreur Flutter)

> Chargé quand Claude Code travaille dans ce dossier. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici.

## Rôle
Application **livreur** (Flutter / Dart) : réception des affectations, navigation,
preuve de livraison, encaissement à la livraison (**COD**). Elle **consomme
l'API de web/**.

## Décisions actées
- On **garde cette app Flutter** : adaptation (rebranding, FR, config API),
  pas de réécriture.
- Devise **XOF** : affichage **entier**, sans décimales (montants COD).
- Locale **FR** par défaut.

## Configuration
- URL d'API en variable d'environnement / config (sandbox vs prod) ;
  cible par défaut `https://beninlink.app`. Ne pas coder l'URL en dur.

## Chantiers qui touchent cette app
- Francisation des chaînes (i18n Dart) + format XOF.
- Écrans d'affectation, preuve de livraison, et **encaissement COD** en FCFA.
- (Pas de FedaPay ni de SYSCOHADA ici : ces flux sont côté web/ et app marchand.)

## Commandes
- Dépendances : `flutter pub get`
- Analyse statique : `flutter analyze`
- Tests : `flutter test`
- Build Android : `flutter build appbundle` (ou `apk` pour test)
- Build iOS : `flutter build ipa`

## Conventions
- Réutiliser les patterns existants de l'app ; repérer un écran équivalent avant
  d'en créer un.
- Toute évolution d'API vient d'abord de web/ : ne pas inventer d'endpoint.
