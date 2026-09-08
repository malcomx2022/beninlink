# docs/guides — livrables techniques BeninLink

- `fedapay-module/`  Connecteur FedaPay (config, service, contrôleur, migration,
  test PHPUnit, README d'intégration). À déposer dans web/ aux mêmes chemins.
- `recette-pilote/`  Préparation des tests pilotes : environnement de recette,
  builds EAS, jeu de données béninois (`php artisan beninlink:pilote`), scénarios,
  critères de sortie.
- `infra/`  Fichiers de déploiement VPS Infomaniak (Nginx multi-tenant, Supervisor,
  deploy.sh, .env.example, .gitignore, cron du scheduler).
  ⚠️ **Trois guides se lisent AVANT le premier déploiement, dans cet ordre :**
  1. `infra/php/` — depuis la n° 62 le backend ne s'installe plus en PHP 8.2, et
     `deploy.sh` s'arrête sur un serveur qui n'est pas en 8.3, avant même de
     couper le site.
  2. `infra/mysql/` — une base, un utilisateur, et deux paramètres qui ne se
     rattrapent pas après coup (`utf8mb4_unicode_ci`, MySQL 5.7.7+).
  3. `infra/env/` — le `.env` de production, dont la ligne `APP_INSTALLED=yes` :
     sans elle, un déploiement réussi sert l'installateur We Courier au lieu de
     l'application.
  4. `infra/nginx/` — le service web et le certificat. Le certificat est
     **générique** (`*.beninlink.app`) : il ne s'obtient que par défi DNS-01,
     donc pas avec `certbot --nginx`, et son renouvellement demande un accès à
     la zone DNS. Il doit exister **avant** que la configuration soit posée,
     sans quoi nginx refuse de démarrer.

  Puis, APRÈS le premier déploiement :
  - `infra/supervisor/` — le worker de la file (D13). Sans lui, l'application
    répond normalement et **plus aucun SMS ne part** ; l'installer sans
    installer sa surveillance ne fait que déplacer le problème.
  - `infra/sauvegarde/` — base, fichiers téléversés et `.env`. `public/uploads/`
    n'est **jamais** dans git : les signatures de livraison n'existent que sur
    le serveur. Le guide porte surtout sur l'**exercice de restauration** — une
    sauvegarde jamais restaurée n'est pas une sauvegarde, c'est un fichier.
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
