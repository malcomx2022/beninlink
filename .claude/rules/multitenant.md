---
paths: ["web/**"]
---
# Règles multi-tenant — web/
- Ne jamais contourner le scoping locataire (sous-domaine). Toute requête est tenant-aware.
- Clés FedaPay « plateforme » = abonnements SaaS ; un locataire peut avoir ses propres clés
  pour l'encaissement marchand.
