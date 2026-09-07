# Provisionner PHP 8.3 sur le VPS

> Depuis la demande n° 62, le backend **ne s'installe plus en PHP 8.2**.
> Ce guide amène le serveur à la version attendue, et rien d'autre.
> Il se lit avant le premier déploiement, et se relit à chaque nouveau serveur.

## Pourquoi c'est un prérequis, et pas une amélioration

`composer.json` fixe `config.platform.php` à `8.3.0`. Cette clé fait résoudre et
installer Composer **comme si** la machine était en 8.3, sans jamais regarder sa
vraie version. Sur un serveur resté en 8.2, `composer install` réussirait donc
sans broncher — et l'application casserait au premier appel d'un paquet qui
exige 8.3. En production, pas en intégration.

C'est pourquoi `deploy.sh` ouvre sur un garde qui lit le PHP *réellement*
exécuté et s'arrête **avant la coupure du site** si la version ne convient pas.
Tant que ce guide n'est pas appliqué, chaque déploiement s'arrêtera là :
proprement, site intact, base intacte — mais il s'arrêtera.

| Où la version est écrite | Ce que ça dit | Peut constater un écart ? |
|---|---|---|
| `web/composer.json` → `config.platform` | ce que Composer résout | non |
| `.github/workflows/deploy.yml` → `php-version` | ce que l'intégration exécute | non |
| **`deploy.sh` → garde en tête** | **ce que le serveur exécute** | **oui** |

---

## Étape 0 — savoir ce qu'on a

```bash
. /etc/os-release && echo "$PRETTY_NAME"
php -v | head -1
php -m | tr '\n' ' '
ls /etc/php/                      # versions déjà installées
systemctl list-units 'php*fpm*'   # laquelle sert nginx
```

Notez la version courante : elle sert au retour arrière.

## Étape 1 — la source des paquets, selon la distribution

| Distribution | PHP livré d'origine | Ce qu'il faut |
|---|---|---|
| **Ubuntu 24.04 LTS** | **8.3** | rien — passer à l'étape 2 |
| Ubuntu 22.04 LTS | 8.1 | dépôt `ondrej/php` |
| Debian 12 (bookworm) | 8.2 | dépôt `packages.sury.org` |
| Debian 11 (bullseye) | 7.4 | dépôt `packages.sury.org` |

**Ubuntu 22.04** :

```bash
sudo apt update && sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
```

**Debian 12 / 11** :

```bash
sudo apt update && sudo apt install -y ca-certificates apt-transport-https lsb-release curl
curl -fsSL https://packages.sury.org/php/apt.gpg | sudo tee /usr/share/keyrings/sury-php.gpg > /dev/null
echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/sury-php.list
sudo apt update
```

## Étape 2 — installer PHP 8.3 et ses extensions

```bash
sudo apt install -y \
  php8.3-fpm php8.3-cli \
  php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-gd php8.3-zip php8.3-bcmath php8.3-intl php8.3-opcache
```

`composer.lock` exige **23 extensions**, auxquelles s'ajoutent `pdo_mysql` et
`mysqli` pour la base. Les voici par usage — non par paquet : le découpage en
paquets varie d'une distribution à l'autre, et l'étape 5 vérifie le résultat
plutôt que le chemin.

| Extensions | À quoi elles servent ici |
|---|---|
| `ctype filter hash json openssl pcre phar session tokenizer zlib iconv fileinfo` | noyau de Laravel — fournies par `php8.3-cli`/`-fpm` |
| **`sodium`** | signature des jetons du SDK SMS (`lcobucci/jwt` 5) |
| `pdo_mysql` `mysqli` | la base de données |
| `dom libxml simplexml xml xmlreader xmlwriter` | relevés PDF (dompdf) et exports Excel |
| `mbstring` | partout — texte accentué, montants formatés |
| `gd` | codes-barres et étiquettes de colis |
| `zip` | import et export Excel |
| `curl` | FedaPay, Twilio, Vonage |

> ⚠️ **`sodium` est une exigence NOUVELLE**, apparue avec la montée elle-même :
> `lcobucci/jwt` est passé de 4.0.4 à 5.6.0, et la version 5 le réclame là où la
> 4 demandait `mbstring` et `openssl`. Il ne figurait donc **pas** dans la liste
> d'extensions donnée dans la demande n° 62 — cette liste avait été établie sur
> le verrou d'avant la mise à jour. C'est exactement le genre d'oubli que
> l'étape 5 attrape, et qu'une liste recopiée à la main ne rattrape jamais.

Si l'étape 5 signale une extension manquante, `apt search php8.3- | grep <nom>`
donne le paquet qui la porte sur cette distribution.

`bcmath` et `intl` ne sont utilisés par aucune ligne du code applicatif — ils
sont installés parce que l'intégration continue les fournit, et qu'un serveur
qui diverge de l'intégration finit toujours par le faire savoir au mauvais
moment.

## Étape 3 — les réglages

Copier la surcouche livrée **aux deux endroits** :

```bash
sudo cp docs/guides/infra/php/beninlink.ini /etc/php/8.3/fpm/conf.d/99-beninlink.ini
sudo cp docs/guides/infra/php/beninlink.ini /etc/php/8.3/cli/conf.d/99-beninlink.ini
```

FPM sert les pages ; le **CLI** exécute `deploy.sh`, le worker de la file et
toutes les commandes `artisan`. Ne régler que FPM laisse les migrations et les
imports en ligne de commande sur les valeurs par défaut.

Elle règle notamment `upload_max_filesize` à 25 Mo, pour s'accorder avec le
`client_max_body_size 25M` de `nginx/beninlink.conf`. Sans cela, PHP refuse à
2 Mo : l'import Excel d'un marchand échoue bien avant la limite annoncée, et le
message vient de PHP, pas de l'application.

### L'utilisateur du pool

Le worker tourne sous **`deploy`** (`supervisor/beninlink-worker.conf`), et FPM
sous `www-data` par défaut. Les deux écrivent dans `storage/logs` et
`storage/framework` : à la première rotation, l'un ne pourra plus écrire dans un
fichier créé par l'autre.

```bash
sudo sed -i 's/^user = www-data/user = deploy/;s/^group = www-data/group = deploy/' \
  /etc/php/8.3/fpm/pool.d/www.conf
grep -E '^(user|group|listen|listen\.(owner|group|mode))' /etc/php/8.3/fpm/pool.d/www.conf
```

`listen.owner`/`listen.group` doivent **rester `www-data`** : c'est nginx qui
ouvre la socket, et il tourne sous `www-data`. On change qui exécute PHP, pas
qui atteint la socket.

```bash
sudo chown -R deploy:deploy /var/www/beninlink
sudo systemctl enable --now php8.3-fpm
```

## Étape 4 — nginx : rien à faire (vérifier quand même)

`nginx/beninlink.conf` pointe **déjà** sur `unix:/run/php/php8.3-fpm.sock`.

C'est une bizarrerie qu'il faut nommer : ce fichier visait 8.3 alors que le
verrou refusait de s'installer au-delà de 8.2. Un serveur monté exactement
d'après les fichiers de ce dépôt n'aurait pas pu installer le backend. La montée
en 8.3 supprime cette contradiction ; il n'y a donc **aucune ligne d'nginx à
changer**, ce qui est le cas favorable et non le cas normal.

```bash
ls -l /run/php/php8.3-fpm.sock     # doit exister, propriétaire www-data
sudo nginx -t && sudo systemctl reload nginx
```

Si un ancien FPM tourne encore, l'arrêter **après** avoir vérifié l'étape 5 —
pas avant : c'est le seul retour arrière immédiat.

## Étape 5 — vérifier AVANT de déployer

C'est l'étape qui distingue « installé » de « prêt ». Trois contrôles, du plus
grossier au plus précis.

```bash
# 1. La version que le shell exécute
php -v | head -1                      # doit dire 8.3.x

# 2. Le garde de deploy.sh, joué à la main
php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && echo "garde : PASSE" || echo "garde : REFUSE"

# 3. Le contrôle qui fait autorité
cd /var/www/beninlink/web && composer check-platform-reqs --no-dev
```

Le troisième lit `composer.lock` et confronte **chaque** exigence à ce que le
serveur charge vraiment — version de PHP et extensions comprises. Toutes les
lignes doivent dire `success`. Une seule `failed` nomme précisément le paquet
manquant : c'est plus sûr que de comparer une liste à la main, et ça reste vrai
quand le verrou évoluera.

> `--no-dev` importe : le déploiement installe sans les dépendances de
> développement, et le contrôle doit porter sur le même périmètre.

Vérifier aussi que **FPM** charge les mêmes extensions que le CLI — ce sont deux
binaires distincts, et n'installer que l'un est l'erreur la plus courante :

```bash
diff <(php -m) <(sudo php-fpm8.3 -m) && echo "CLI et FPM chargent les mêmes extensions"
```

Sur Debian et Ubuntu, `apt install php8.3-<ext>` active l'extension pour les
deux à la fois : un écart signale donc une désactivation manuelle dans
`/etc/php/8.3/{cli,fpm}/conf.d`, pas un paquet oublié.

## Étape 6 — déployer

Rien de particulier : *Actions → Déploiement BeninLink → Run workflow*, ou le
prochain push sur `main`. Le garde de `deploy.sh` passera silencieusement, et
`composer install` s'exécutera sans `--ignore-platform-reqs`.

---

## Le retour arrière

Tant que l'ancien PHP n'est pas désinstallé, il tient en deux lignes :

```bash
sudo sed -i 's|php8.3-fpm.sock|php8.2-fpm.sock|' /etc/nginx/sites-available/beninlink.conf
sudo nginx -t && sudo systemctl reload nginx
```

⚠️ Mais l'application, elle, **ne fonctionnera plus** : le `vendor/` installé
depuis le verrou actuel contient des paquets qui exigent 8.3. Ce retour arrière
n'est utile que pour remettre en ligne une **version antérieure du code**, pas
la version courante. Concrètement : `git checkout` d'un commit antérieur à la
n° 62, puis `composer install`.

Garder l'ancien FPM installé une semaine avant `sudo apt purge php8.2-*`.

---

## Deux points à trancher avant, qui ne relèvent pas de PHP

Le provisionnement les révèle, sans pouvoir les résoudre.

**1. Redis ou pas.** `infra/.env.example` déclare `QUEUE_CONNECTION=redis` et
`SESSION_DRIVER=redis`, alors que la décision **D13** a retenu la file
`database`, et que le commentaire de `supervisor/beninlink-worker.conf` dit que
l'application « n'utilise pas » Redis. Les deux fichiers se contredisent.

- Si le `.env` de production garde `redis` : il faut **`php8.3-redis`** et un
  serveur Redis, sans quoi la session casse au premier visiteur (aucun paquet
  `predis` n'est installé — Laravel passera par l'extension `phpredis`).
- S'il suit D13 (`database` partout) : rien à installer de plus, et
  `.env.example` est à corriger.

**Ce guide part de D13** et n'installe pas Redis. À confirmer avant le premier
déploiement.

**2. Un réglage sans effet.** `.env.example` écrit `CACHE_STORE=redis`, mais
Laravel 10 lit `CACHE_DRIVER` (`config/cache.php` ligne 18). La variable est
donc ignorée et le cache retombe sur `file` — silencieusement. Que l'on choisisse
`redis` ou `file`, cette ligne ne dit pas ce qu'elle a l'air de dire.

---

## Récapitulatif

```bash
# Ubuntu 22.04 — adapter la source selon l'étape 1
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
                    php8.3-xml php8.3-curl php8.3-gd php8.3-zip \
                    php8.3-bcmath php8.3-intl php8.3-opcache
sudo cp docs/guides/infra/php/beninlink.ini /etc/php/8.3/fpm/conf.d/99-beninlink.ini
sudo cp docs/guides/infra/php/beninlink.ini /etc/php/8.3/cli/conf.d/99-beninlink.ini
sudo sed -i 's/^user = www-data/user = deploy/;s/^group = www-data/group = deploy/' \
  /etc/php/8.3/fpm/pool.d/www.conf
sudo systemctl enable --now php8.3-fpm && sudo nginx -t && sudo systemctl reload nginx
cd /var/www/beninlink/web && composer check-platform-reqs --no-dev   # tout doit dire success
```
