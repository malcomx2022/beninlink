# CLAUDE.md — mobile-livreur/ (app livreur · React Native / Expo)

> Chargé quand Claude Code travaille dans mobile-livreur/. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici.

## ⚠ Périmètre & financement
**HORS PÉRIMÈTRE de la fenêtre Idéation.** La ligne 11 ne finance QUE l'app marchand.
Cette app livreur est **réservée à la fenêtre Création** (ou autofinancement). Pour le MVP
Idéation, le côté livreur peut rester sur l'app Flutter We Courier / le panneau web.
**Ne pas imputer de temps facturé Idéation ici.** Développement à démarrer seulement
si la fenêtre Création est engagée.

## Rôle
Application **livreur (coursier)**, React Native / Expo. **Consomme l'API de `web/`**.

## Décisions actées
- **React Native / Expo** (même charte que `mobile/`).
- Devise **XOF** entière · Locale **FR**. Ne jamais inventer d'endpoint (API = `web/`).
- URL d'API en variable d'environnement.

## Écrans (référence maquette)
- Connexion coursier (téléphone/e-mail + mot de passe).
- Mes courses : onglets **En cours / Retours / Livrés** ; carte colis (client, N° suivi,
  adresse, COD, Appeler/Itinéraire/Statut).
- Détail : bloc **Marchand** (boutique, tél, adresse d'enlèvement) + **Infos colis**
  (N° suivi, type, heures, frais, TVA, **À encaisser COD**) + destinataire.
- **Changer le statut** : Livré / Livraison partielle / Retour + montant encaissé.
- Gains : Total COD, gains, à reverser, encaissements du jour.
- Profil : stats (En cours/Livrés/Retours) + Solde/Gains/Total COD + menu.

## Statuts colis (alignés backend)
En attente → Ramassage assigné → Entrepôt → Livreur assigné → Livré ; + Livraison partielle, Retour.

## Design system
Identique à `mobile/` : Vert `#12503A` · Ocre `#E0A63C` · Sora + DM Sans · FCFA entiers.

## Commandes
- `npm install` · `npx expo start` · `eas build -p android`
