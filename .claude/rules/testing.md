---
paths: ["**/*Test.php", "tests/**"]
---
# Règles de test
- PHPUnit. Lancer `php artisan test` avant tout commit.
- Couvrir tout module de paiement modifié, dont l'idempotence du webhook.
- Isoler le SDK FedaPay via mock ; tester NOTRE logique, pas le SDK tiers.
