---
paths: ["web/app/Services/Payments/**", "web/app/Http/Controllers/Payment/**"]
---
# Règles paiement (FedaPay) — web/
- FedaPay = passerelle Mobile Money BJ (MTN MoMo + Moov Money).
- **Webhook signé (`transaction.approved`) = seule source de vérité** pour créditer/activer.
- Traitement **idempotent** sous verrou : un webhook rejoué ne crédite qu'une fois.
- Montants **XOF entiers**. Créditer/activer en **appelant les services existants** We Courier.
- Toute modification ici exige un test PHPUnit couvrant l'idempotence.
