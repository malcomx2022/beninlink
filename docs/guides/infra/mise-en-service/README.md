# Mise en service du VPS — du serveur nu au premier déploiement

> Ce guide est le **chef d'orchestre**. Il ne réexplique rien de ce que les
> autres guides d'infra disent : il dit dans quel ordre les jouer, et il couvre
> **ce qu'aucun d'eux ne couvre** — l'utilisateur système, les *deux* paires de
> clés SSH, le clone d'un dépôt **privé**, et le premier remplissage de la base.
> Écrit le 2026-09-09, sur `main` (`c420643`).

Les trois pièges de cette page ont été **vérifiés**, pas supposés : le premier
déploiement automatique s'arrête sur une installation fraîchement amorcée, le
`db:seed` du socle pose des comptes dont le mot de passe est public, et il ne se
rejoue pas. Chacun est démontré à sa section.

## Avant de toucher au serveur

| Il vous faut | Pourquoi, et ce qui bloque sans |
|---|---|
| Un accès **root / sudo** au VPS | tout ce qui suit |
| Un accès à la **zone DNS** de `beninlink.app` | le certificat est générique : il ne s'obtient que par défi DNS-01, et son **renouvellement** aussi (`infra/nginx/`) |
| Le rôle **admin** sur le dépôt GitHub | poser une clé de déploiement et trois secrets |
| Un **mot de passe MySQL** décidé à l'avance | il entre dans le `.env`, et le `.env` se pose avant le premier déploiement |
| Une **adresse d'alerte** qui est lue | la sauvegarde et la supervision n'écrivent qu'en cas d'échec ; une adresse fictive équivaut à pas d'alerte |

Comptez le certificat en **heures**, pas en minutes : une propagation DNS ne
s'accélère pas. C'est la seule étape dont la durée ne dépend pas de vous.

## Les deux clés SSH ne servent pas à la même chose

C'est la confusion la plus coûteuse de la mise en service, parce qu'elle ne se
voit qu'au premier déploiement — et sous la forme d'un message qui parle d'autre
chose. Il y a **deux** paires de clés, dans **deux** sens :

| | Clé A — Actions → VPS | Clé B — VPS → GitHub |
|---|---|---|
| Qui se connecte à qui | le workflow GitHub ouvre une session SSH sur le VPS | le VPS va chercher le code sur GitHub |
| Moitié **privée** | secret de dépôt `SSH_KEY` | fichier `/home/deploy/.ssh/id_ed25519` sur le VPS |
| Moitié **publique** | `/home/deploy/.ssh/authorized_keys` sur le VPS | *Settings → Deploy keys* du dépôt |
| Sans elle | `Permission denied (publickey)` au moment de la connexion | `Repository not found` sur `git fetch`, alors que la connexion a réussi |

La clé B est celle qu'on oublie, parce qu'on ne pense au dépôt que comme à une
source publique. **`malcomx2022/beninlink` est privé** : `git fetch origin main`
n'y accède pas sans identité. Or c'est la première commande que le workflow
exécute sur le VPS, et `deploy.sh` refait un `git pull` juste après.

## 1. L'utilisateur `deploy`

```bash
sudo adduser --disabled-password --gecos "" deploy      # Debian / Ubuntu
sudo install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
```

Coller la **moitié publique de la clé A** (générée sur votre poste, pas sur le
serveur) dans `authorized_keys` :

```bash
sudo -u deploy tee -a /home/deploy/.ssh/authorized_keys > /dev/null <<'CLE'
ssh-ed25519 AAAA… actions@beninlink
CLE
sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

`--disabled-password` est délibéré : ce compte ne se connecte que par clé.

**Pourquoi un utilisateur dédié plutôt que `root`.** Le secret `SSH_KEY` vit
chez GitHub ; quiconque le lit obtient exactement les droits de ce compte. Et
c'est le même utilisateur qui exécute PHP : le guide `infra/php/` bascule le
pool FPM sur `deploy`. Un déploiement en `root` produirait des fichiers que
FPM ne peut pas réécrire — ou l'inverse, un serveur web qui peut tout.

## 2. Le clone — un dépôt privé ne se clone pas sans clé

La **clé B** se génère **sur le VPS**, sous l'identité `deploy` :

```bash
sudo -u deploy ssh-keygen -t ed25519 -C "vps-beninlink" -f /home/deploy/.ssh/id_ed25519 -N ""
sudo -u deploy cat /home/deploy/.ssh/id_ed25519.pub
```

Coller cette sortie dans *Settings → Deploy keys → Add deploy key*, **sans**
cocher « Allow write access » : le serveur lit le code, il n'en publie jamais.

```bash
sudo -u deploy ssh -T git@github.com          # accepter l'empreinte une fois
sudo install -d -o deploy -g deploy /var/www
sudo -u deploy git clone git@github.com:malcomx2022/beninlink.git /var/www/beninlink
```

⚠️ **Cloner en tant que `deploy`, pas en `root`.** Trois choses en dépendent :
`deploy.sh` fait un `git pull` sous cette identité ; FPM écrit les fichiers
téléversés dans `public/uploads/` sous la même ; et le worker de la file écrit
dans `storage/logs`. Un clone fait en `root` donne un dépôt que le déploiement
ne peut pas mettre à jour et des téléversements qui échouent une fois en
production — c'est-à-dire au premier justificatif d'un marchand.

Le clone est celui du **monorepo entier**, pas de `web/` seul : le workflow
récupère `docs/guides/infra/deploy/deploy.sh` dans l'arbre déployé.

## 3. Les quatre guides, dans l'ordre

Chacun a sa raison d'être à ce rang. Ce qui suit n'est qu'un rappel — la marche
à suivre est dans les guides.

| # | Guide | Ce qui prouve l'étape faite |
|---|---|---|
| 1 | [`infra/php/`](../php/) — PHP 8.3, extensions, pool FPM | `composer check-platform-reqs --no-dev` : **toutes** les lignes disent `success` |
| 2 | [`infra/mysql/`](../mysql/) — base, utilisateur, `utf8mb4_unicode_ci` | la requête d'acceptation du guide renvoie **une** ligne |
| 3 | [`infra/env/`](../env/) — `web/.env`, `APP_KEY`, `API_KEY` | le contrôle `tinker` du guide : rien de `NULL`, `app_installed` à `yes` |
| 4 | [`infra/nginx/`](../nginx/) — certificat générique puis configuration | `certbot renew --dry-run` passe, et `nginx -t` aussi |

L'ordre n'est pas une préférence. PHP d'abord parce que `deploy.sh` s'arrête sur
un serveur en 8.2 ; la base avant le `.env` parce que le `.env` la nomme ; le
`.env` avant nginx parce qu'un site servi sans lui renvoie l'installateur ; le
certificat avant la configuration parce que nginx refuse de démarrer si le
fichier qu'elle référence n'existe pas.

## 4. Les trois secrets GitHub

*Settings → Secrets and variables → Actions* :

| Secret | Valeur |
|---|---|
| `SSH_HOST` | l'IP ou le nom du VPS |
| `SSH_USER` | `deploy` |
| `SSH_KEY` | la moitié **privée** de la clé A, **entière** — de `-----BEGIN OPENSSH PRIVATE KEY-----` à `-----END OPENSSH PRIVATE KEY-----`, retour à la ligne final compris |
| `SSH_PORT` | facultatif ; 22 par défaut |

Tant qu'il en manque un, le job de déploiement s'arrête à sa première étape en
les **nommant**, et l'étape SSH est *sautée*, pas tentée. Ce rouge-là est le
garde qui parle. C'est celui que le dépôt affiche à chaque fusion aujourd'hui.

## 5. Remplir la base la première fois — jamais par `/install`

L'installateur web du socle propose de **recréer la base** et réécrit le `.env`
que vous venez de poser. Avec `APP_INSTALLED=yes`, il n'est de toute façon plus
joignable. La première installation se fait en deux commandes :

```bash
cd /var/www/beninlink/web
sudo -u deploy php artisan migrate --force
sudo -u deploy php artisan db:seed --force
```

Trois conséquences, toutes vérifiées, et aucune n'est évidente.

### a. Le seed pose des comptes dont le mot de passe est public

`UserSeeder` crée trois comptes, tous à `12345678` :

| Compte | Rôle |
|---|---|
| `admin@wemaxdevs.com` | super-administrateur |
| `company@wemaxdevs.com` | administrateur de la société de démonstration |
| `branch@wemaxdevs.com` | agence |

Ce ne sont pas des identifiants de test cachés dans un fichier : ils sont dans
le code source du socle, que d'autres installations partagent. **Les changer
avant que le site soit joignable**, ou supprimer ceux dont vous n'avez pas
l'usage.

Le seed crée aussi une société de démonstration « Company » et son sous-domaine
— et ce sous-domaine est **dérivé d'`APP_URL` au moment où le seed tourne** :

| `APP_URL` dans le `.env` | Sous-domaine écrit dans `domains` |
|---|---|
| `https://beninlink.app` | `company.beninlink.app` |
| `http://localhost` (le défaut du modèle) | `company.localhost` — **injoignable** |

C'est une raison de plus de poser le `.env` avant d'amorcer la base, et non
l'inverse. Si le mauvais sous-domaine a été écrit, il se corrige dans l'écran
super-admin « Sociétés » — la ligne de `domains`, pas le `.env`, fait foi une
fois qu'elle existe.

### b. `db:seed` ne se rejoue pas

Un second passage sur une base déjà amorcée s'arrête sur une contrainte
d'unicité (`tenants.id`), après avoir écrit une partie de ce qu'il rejouait. Il
se joue **une fois, sur une base vide** — et si l'on doit recommencer, on repart
d'une base vide, pas d'un second passage.

### c. Deux sociétés sont créées — chacune a son cadre de zones (piège fermé en S86)

`GeneralSettingsSeeder` crée deux sociétés (« We Courier » et « Company »). Depuis
l'étape 6 de **D4**, une société sans zone ne facture plus rien — et `deploy.sh` le
vérifie **avant** de migrer, par `beninlink:tarification-prete`, qui sort en erreur
dès qu'une société n'a aucune zone.

Jusqu'au **2026-10-05 (S86)**, `DeliveryChargeSeeder` ne posait zones et grille que
pour la seconde société, et le premier déploiement automatique de toute installation
neuve s'arrêtait là, à chaque tentative, sans qu'un mot du message dise « votre base
vient d'être amorcée » :

```
We Courier — pas prête
  ✗ aucune zone configurée — la société ne peut facturer aucun colis

Company — prête

1 société(s) ne sont pas en état de facturer. […]
code de sortie: 1
```

Depuis S86 les semences posent le **cadre** (quatre zones, trois délais, forfaits
CEDEAO) de **chaque** société qu'elles créent ; la grille de démonstration reste sur
la société 2, parce que des montants appartiennent au transporteur. Un cadre sans
montants est « prête » pour le constat : sur une base qui vient d'être amorcée,
`beninlink:tarification-prete` sort en **succès** (`FreshInstallReadinessTest` le
tient, et le job de répétition le rejoue sur chaque pull request, sans contournement).

Si la société 1 est celle que vous exploitez, sa grille se saisit dans *Réglages →
Zones et barème*, ou se pose depuis le fichier de départ :

```bash
sudo -u deploy php artisan beninlink:zones-tarifaires --societe=1 --installer \
  --grille=database/bareme/grille-nationale.csv
```

Une base amorcée **avant** S86 garde sa société sans zone : la même commande (avec ou
sans `--grille`) lui pose son cadre, puis relancer le constat jusqu'au succès. Tant
qu'il est rouge, aucun déploiement automatique n'ira au bout.

## 6. Le premier déploiement — à la main, une fois

Exactement les commandes que le workflow exécutera, mais avec vous devant
l'écran :

```bash
sudo -u deploy bash -c 'cd /var/www/beninlink \
  && git fetch origin main \
  && git checkout FETCH_HEAD -- docs/guides/infra/deploy/deploy.sh \
  && bash docs/guides/infra/deploy/deploy.sh'
```

Le script coupe le site, met à jour, vide les caches, **vérifie la
tarification**, migre, recache, redemande aux workers de repartir, et remonte le
site. Ses trois points d'arrêt, et où ils vous laissent :

| Il s'arrête sur | État du site | État de la base |
|---|---|---|
| Le garde **PHP 8.3** | **jamais coupé** — le garde passe avant `artisan down` | intacte |
| `beninlink:tarification-prete` | coupé puis **remonté** par le filet | intacte : la vérification précède la migration |
| `php artisan migrate` | coupé puis **remonté** par le filet | ⚠️ voir ci-dessous |

⚠️ **Une migration MySQL interrompue ne se défait pas.** MySQL ne sait pas
annuler une modification de schéma dans une transaction : une migration qui
échoue à mi-parcours laisse ce qu'elle avait déjà appliqué. Sur une base neuve
c'est sans conséquence — on repart de zéro. Sur une base qui porte déjà des
données, **la sauvegarde se prend avant**, pas après ([`infra/sauvegarde/`](../sauvegarde/)).

Quand ce passage manuel est vert, prouvez la chaîne : *Actions → Déploiement
BeninLink → Run workflow*, ou un push sur `main`. Le workflow doit aller au bout
des deux jobs.

## 7. Ce qui prouve que la mise en service a réussi

À passer dans l'ordre ; chaque ligne échoue pour une raison différente.

```bash
# 1. Le site répond, en HTTPS, et ne renvoie pas vers l'installateur
curl -sI https://beninlink.app/ | head -1            # 200, pas 302 vers /install

# 2. Le certificat couvre aussi les sous-domaines des sociétés
curl -sI https://company.beninlink.app/ | head -1

# 3. Les fichiers téléversés sont écrivables par celui qui exécute PHP
ls -ld /var/www/beninlink/web/public/uploads         # propriétaire deploy

# 4. Chaque société peut facturer
sudo -u deploy php artisan beninlink:tarification-prete   # succès

# 5. Le déploiement automatique va au bout
#    (Actions → dernier run sur main : les deux jobs verts)
```

Puis, à l'écran : se connecter avec le compte super-administrateur **au nouveau
mot de passe**, et vérifier qu'une société est joignable sur son sous-domaine.

Ce que cette liste ne prouve **pas**, et qu'aucune commande ne prouvera à votre
place : qu'un SMS part vraiment, qu'un webhook FedaPay est accepté, qu'une
sauvegarde se restaure. Ce sont les trois chantiers de l'après.

## 8. Après la mise en service

Dans cet ordre, et pas plus tard qu'il ne faut :

1. [`infra/supervisor/`](../supervisor/) — le worker de la file (**D13**). Sans
   lui, l'application répond normalement et **plus aucun SMS ne part**.
2. [`infra/supervision/`](../supervision/) — le crontab complet, l'alerte, et
   l'inventaire de ce qui **n'a aucun détecteur**. Installer le worker sans sa
   surveillance ne fait que déplacer le problème.
3. [`infra/sauvegarde/`](../sauvegarde/) — base, `public/uploads/` et `.env` ;
   surtout l'**exercice de restauration**, la seule étape qui se répète.
4. [`recette-pilote/`](../../recette-pilote/) — l'environnement de recette et les
   cinq PME. Il se monte sur les **mêmes prérequis** que la production : une
   recette servie par un socle plus permissif ne prouverait rien.

## Ce que ce guide ne couvre pas

Le pare-feu, les mises à jour système et le durcissement SSH (désactiver
l'authentification par mot de passe, `PermitRootLogin no`) relèvent de
l'hébergeur et de vos usages : ils ne sont pas propres à BeninLink, et une
recette copiée d'un autre projet ferait plus de mal que de bien. Ils se
décident avant l'ouverture au public, pas après.
