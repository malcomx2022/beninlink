---
paths: ["web/**/*Test.php", "web/tests/**"]
---
# Règles de test — web/
- PHPUnit. Lancer `php artisan test` (dans web/) avant tout commit.
- Depuis le 2026-09-07, la suite tourne **aussi** dans GitHub Actions, et un
  déploiement en production ne part que si elle passe
  (`.github/workflows/deploy.yml`). Ce n'est pas une raison de sauter
  l'exécution locale : elle reste le premier filet, et le plus rapide.
- Couvrir tout module de paiement modifié, dont l'idempotence du webhook.
- Isoler le SDK FedaPay via mock ; tester NOTRE logique, pas le SDK tiers.
