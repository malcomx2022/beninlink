# docs/guides — livrables techniques BeninLink

- `fedapay-module/`  Connecteur FedaPay (config, service, contrôleur, migration,
  test PHPUnit, README d'intégration). À déposer dans web/ aux mêmes chemins.
- `recette-pilote/`  Préparation des tests pilotes : environnement de recette,
  builds EAS, jeu de données béninois (`php artisan beninlink:pilote`), scénarios,
  critères de sortie. Le jeu pose le **modèle par zones** au complet (D4, étape 6) :
  sans zone, un colis n'a plus de tarif, donc plus de recette.
- `comptabilite/`  Plan de comptes SYSCOHADA (en attente de signature) et
  **reprise des relevés** de règlement : leur cadence (par marchand, en jours —
  pas mensuelle), ce qu'un passage manqué change, et comment rattraper.
  ⚠️ La tâche planifiée ne générait les relevés que pour la **société 1**
  (`settings()` retombe sur elle hors requête) ; **corrigé le 2026-09-11**, avec
  `invoice:generate --societe=N` pour un rattrapage ciblé. Sur une installation
  antérieure au correctif, le guide dit quoi vérifier.
- `infra/`  Fichiers de déploiement VPS Infomaniak (Nginx multi-tenant, Supervisor,
  deploy.sh, .env.example, .gitignore, cron du scheduler).
  ⚠️ **Commencer par `infra/mise-en-service/`** : c'est le chef d'orchestre, du
  serveur nu au premier déploiement. Il séquence les quatre guides ci-dessous et
  couvre ce qu'aucun d'eux ne couvre — l'utilisateur système, les **deux** paires
  de clés SSH (Actions → VPS, et VPS → GitHub : le dépôt est privé), le clone, et
  le premier remplissage de la base. Il nomme aussi le piège vérifié qui arrête
  le premier déploiement automatique : le `db:seed` du socle crée **deux**
  sociétés et n'en dote qu'une de zones, si bien que
  `beninlink:tarification-prete` — que `deploy.sh` exécute avant de migrer —
  sort en erreur sur une installation neuve.

  **Quatre guides se lisent AVANT le premier déploiement, dans cet ordre :**
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
  - `infra/reprise/` — la **reprise après incident**, rangée par symptôme : site
    resté en maintenance, déploiement arrêté, file bloquée, recharge Mobile Money
    non créditée, base restaurée, VPS perdu. Il dit aussi ce qu'on ne fait pas —
    au premier rang, `migrate:rollback` sur l'étape 6, qui rend le schéma mais
    pas les montants, donc un barème à zéro franc.
  - `infra/supervision/` — ce qui se surveille, et **ce qui n'a pas de
    détecteur**. Sur les neuf commandes `beninlink:*`, **trois seulement** ont
    une sortie 1 qui signale un incident ; les autres la réservent aux erreurs
    d'appel, et deux constats renvoient SUCCESS *même quand ils trouvent des
    écarts*. Les brancher sur un cron produirait un vert permanent.
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
