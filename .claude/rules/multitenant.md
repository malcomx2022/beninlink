---
paths: ["web/**"]
---
# Règles multi-tenant — web/
- Ne jamais contourner le scoping locataire (sous-domaine).
- Toute requête base de données est tenant-aware.
- Les clés FedaPay « plateforme » servent aux abonnements SaaS ; un locataire
  peut avoir ses propres clés pour l'encaissement marchand.
