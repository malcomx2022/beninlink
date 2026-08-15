---
paths: ["mobile/**", "mobile-livreur/**"]
---
# Règles apps mobiles — React Native / Expo
- **Consommer l'API de web/** ; ne jamais inventer d'endpoint. URL d'API en variable
  d'environnement (sandbox vs prod), jamais en dur.
- Locale **FR** par défaut ; toute chaîne visible passe par l'i18n.
- Devise **XOF (FCFA)** : montants **entiers**, aucun centime.
- Statuts colis alignés backend : En attente → Ramassage assigné → Entrepôt →
  Livreur assigné → Livré ; + Livraison partielle, Retour.
- **FedaPay** : l'app n'accède jamais aux clés ; elle ouvre `payment_url` en WebView et
  ne considère le solde à jour qu'après confirmation serveur (webhook).
- Design : Vert `#12503A` · Ocre `#E0A63C` · Sora + DM Sans. `mobile/` et `mobile-livreur/`
  partagent la charte.
- Rappel périmètre : le temps facturé Idéation ne concerne QUE `mobile/` (ligne 11).
