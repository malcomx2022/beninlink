---
paths: ["app/Services/Payments/**", "app/Http/Controllers/Payment/**"]
---
# Règles paiement (FedaPay)
- FedaPay est la passerelle Mobile Money du Bénin (MTN MoMo + Moov Money).
- Le **webhook signé** (`transaction.approved`) est la **seule source de vérité**
  pour créditer un wallet ou activer un abonnement. Le retour navigateur/WebView
  ne crédite jamais.
- Traitement **idempotent** sous verrou : un webhook rejoué ne crédite qu'une fois.
- Montants **XOF entiers** — jamais de décimales.
- Créditer le wallet / activer l'abonnement en **appelant les services existants**
  de We Courier, sans réécrire ces logiques.
- Toute modification ici exige un test PHPUnit couvrant l'idempotence.
