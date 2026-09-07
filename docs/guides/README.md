# docs/guides — livrables techniques BeninLink

- `fedapay-module/`  Connecteur FedaPay (config, service, contrôleur, migration,
  test PHPUnit, README d'intégration). À déposer dans web/ aux mêmes chemins.
- `recette-pilote/`  Préparation des tests pilotes : environnement de recette,
  builds EAS, jeu de données béninois (`php artisan beninlink:pilote`), scénarios,
  critères de sortie.
- `infra/`  Fichiers de déploiement VPS Infomaniak (Nginx multi-tenant, Supervisor,
  deploy.sh, .env.example, .gitignore, cron du scheduler).
  ⚠️ **`infra/php/` se lit AVANT le premier déploiement** : depuis la n° 62 le
  backend ne s'installe plus en PHP 8.2, et `deploy.sh` s'arrête sur un serveur
  qui n'est pas en 8.3 — avant même de couper le site.
  ⚠️ Le workflow GitHub Actions n'est plus un modèle : il vit à
  `.github/workflows/deploy.yml` et **un push sur `main` déploie en
  production**, après la suite de tests. Les secrets `SSH_HOST`, `SSH_USER`,
  `SSH_KEY` (et `SSH_PORT`, facultatif) se renseignent dans
  *Settings → Secrets and variables → Actions*.
- `../../web/CARTOGRAPHIE.md`  Relevé de l'Étape 0 sur le socle We Courier
  (blocs A-I, constats de sécurité, routes mortes). À lire en premier.

La partie narrative de ces guides (procédures pas-à-pas GitHub + VPS, méthode de
cartographie) est consolidée dans le **document maître**
(BeninLink_Documentation_maitre_DAT.docx), sections 7, 8 et 9.
