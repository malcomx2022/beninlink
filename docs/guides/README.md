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
- `socle/`  **Mettre à jour We Courier** : on ne met pas ce socle à jour, on le
  re-fusionne. La base de fusion existe — le premier commit du dépôt porte le
  socle intact — et la carte des conflits est chiffrée : **238 fichiers du socle
  modifiés, 196 ajoutés, 0 supprimé**, concentrés dans les vues et la couche
  HTTP. Le guide liste ce qu'il ne faut jamais laisser l'éditeur réécrire, et
  pose la question qu'on oublie : faut-il monter ?
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
    détecteur**. Son annexe `fedapay-webhooks.md` couvre la panne la plus
    coûteuse — le paiement aboutit, le portefeuille ne bouge pas : trois
    lectures en base, dont celle qui trouve l'état qu'un rejeu de webhook ne
    rattrape pas. Sur les neuf commandes `beninlink:*`, **trois seulement** ont
    une sortie 1 qui signale un incident ; les autres la réservent aux erreurs
    d'appel, et deux constats renvoient SUCCESS *même quand ils trouvent des
    écarts*. Les brancher sur un cron produirait un vert permanent.
  ⚠️ Le workflow GitHub Actions n'est plus un modèle : il vit à
  `.github/workflows/deploy.yml` et **un push sur `main` déploie en
  production**, après la suite de tests. Les secrets `SSH_HOST`, `SSH_USER`,
  `SSH_KEY` (et `SSH_PORT`, facultatif) se renseignent dans
  *Settings → Secrets and variables → Actions*.
- `charte-web/`  **Audit UX du web et alignement sur la charte mobile.** État des
  lieux chiffré du front public et du back-office face à `mobile/src/theme/` et à
  `maquettes/3_back_office_web.html` : **132 occurrences du violet We Courier**,
  **zéro occurrence de l'ocre**, Sora et DM Sans absentes, et deux défauts qu'une
  ligne corrige — les titres du site public tournent en `fangsong` (serif CJK, donc
  le serif par défaut du navigateur), et le back-office est rendu **à 80 % avec le
  zoom désactivé**. Il pose la méthode : la charte s'ajoute par une **couche de
  jetons** chargée en dernier, jamais dans les 7 263 lignes du CSS du socle — sinon
  chaque montée de We Courier rejoue le conflit (cf. `socle/`). Il chiffre aussi les
  contrastes : **blanc sur ocre = 2,17:1**, l'ocre ne porte jamais de texte blanc, et
  **six pastilles de la maquette validée échouent en AA** — valeurs corrigées
  fournies.
  **Lot 0 tranché et lot 1 livré le 2026-09-18** : `colors.ts` l'emporte sur la
  maquette (corrigée), et la couche de jetons est en place — `public/beninlink/`,
  Sora et DM Sans auto-hébergées, le zoom rétabli, la couleur verte en base sans
  écraser celle d'un transporteur. Six fichiers du socle touchés, d'une poignée de
  lignes chacun. `WebBrandCharterTest` compare `tokens.css` à `colors.ts` jeton par
  jeton et **recalcule les contrastes**, pour qu'une re-fusion du socle ne défasse
  pas tout en silence.
  **Lot 2 livré le 2026-09-18** : la sémantique des statuts colis. L'audit avait
  sous-estimé le chantier — la table de couleurs existait en **trois** copies, dont
  une morte et fautive par construction ; les trois délèguent désormais à
  `ParcelStage`, portage de `mobile/src/domain/parcelStatus.ts`. « En attente »
  cesse d'être rouge, la livraison partielle passe en orange (y compris sur les
  détails de facture, où elle était verte sur un relevé d'argent), les neuf retours
  deviennent une famille, et les 14 codes `_CANCEL` ne rendent plus une cellule
  vide. Voir §10.4 et §11.8 pour ce qui n'est pas encore livré, et §11.6 pour deux
  constats de **code mort** trouvés en chemin (une vue portant une erreur fatale,
  un mailable dont le nom de vue ne résout pas sous Linux).
  **Lot 4 livré le 2026-09-18** : la francisation. L'audit avait manqué le
  mécanisme principal — `__('Reset Password')` est une **clé JSON**, et sans entrée
  dans `lang/fr.json` Laravel rend la clé, donc l'anglais, **sans erreur** : 45
  chaînes s'affichaient en anglais, dont tout le parcours de connexion et **toutes
  les pages d'erreur**. Corrigées dans un seul fichier de données, à coût de fusion
  nul. Les placeholders étaient 161 et non 85 ; 143 traduits, les 18 autres
  justifiés dans le test (installeur non internationalisé, vues mortes, et un code
  pays ISO qu'un passage naïf aurait cassé). Le défaut des boîtes de dialogue
  n'était pas celui annoncé : `denyButtonText` n'est pas l'option du bouton Annuler
  dans SweetAlert2, donc traduire cette ligne n'aurait **rien changé à l'écran** —
  et le défaut touchait 16 dialogues, pas 2. Au passage : deux fuites de **taka
  bangladais** dans les libellés français, et deux fautes de français. Voir §12.5
  pour une affirmation du guide `socle/` que ce dépôt ne permet pas de vérifier.
- `../../web/CARTOGRAPHIE.md`  Relevé de l'Étape 0 sur le socle We Courier
  (blocs A-I, constats de sécurité, routes mortes). À lire en premier.

La partie narrative de ces guides (procédures pas-à-pas GitHub + VPS, méthode de
cartographie) est consolidée dans le **document maître**
(BeninLink_Documentation_maitre_DAT.docx), sections 7, 8 et 9.
