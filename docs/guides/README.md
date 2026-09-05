# docs/guides — livrables techniques BeninLink

- `fedapay-module/`  Connecteur FedaPay (config, service, contrôleur, migration,
  test PHPUnit, README d'intégration). À déposer dans web/ aux mêmes chemins.
- `recette-pilote/`  Préparation des tests pilotes : environnement de recette,
  builds EAS, jeu de données béninois (`php artisan beninlink:pilote`), scénarios,
  critères de sortie.
- `infra/`  Fichiers de déploiement VPS Infomaniak (Nginx multi-tenant, Supervisor,
  deploy.sh, GitHub Actions, .env.example, .gitignore, cron du scheduler).
- `../../web/CARTOGRAPHIE.md`  Relevé de l'Étape 0 sur le socle We Courier
  (blocs A-I, constats de sécurité, routes mortes). À lire en premier.

La partie narrative de ces guides (procédures pas-à-pas GitHub + VPS, méthode de
cartographie) est consolidée dans le **document maître**
(BeninLink_Documentation_maitre_DAT.docx), sections 7, 8 et 9.
