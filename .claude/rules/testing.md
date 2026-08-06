---
paths: ["web/**/*Test.php", "web/tests/**"]
---
# Règles de test — web/
- PHPUnit. Lancer `php artisan test` (dans web/) avant tout commit.
- Couvrir tout module de paiement modifié, dont l'idempotence du webhook.
- Isoler le SDK FedaPay via mock ; tester NOTRE logique, pas le SDK tiers.
