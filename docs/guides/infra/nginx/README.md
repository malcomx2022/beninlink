# nginx et le certificat SSL

> Le fichier est `nginx/beninlink.conf`. Ce guide dit dans **quel ordre** poser
> les pièces — l'ordre est le vrai sujet — et pourquoi le certificat de ce
> projet ne s'obtient pas comme les autres.
> Il se lit après `infra/php/` (la socket FPM doit exister) et avant le premier
> déploiement.

## Le certificat est GÉNÉRIQUE, et ça change tout

```nginx
server_name beninlink.app *.beninlink.app;
```

Chaque société est un sous-domaine. Il faut donc un certificat pour
`*.beninlink.app` — et Let's Encrypt **ne délivre un certificat générique que
par défi DNS-01**. Cette contrainte n'est pas négociable, et elle a trois
conséquences que l'on découvre en général au mauvais moment.

**1. `certbot --nginx` ne marche pas.** Le greffon nginx pose un défi HTTP-01,
qui prouve la possession d'**un** nom, jamais d'une famille. La commande semble
raisonnable et échoue avec un message qui ne dit pas pourquoi.

**2. Le renouvellement automatique demande un accès à la zone DNS.** Le défi
DNS-01 exige d'écrire un enregistrement `_acme-challenge.beninlink.app` à chaque
renouvellement — tous les 60 jours en pratique. Deux voies :

| Voie | Renouvellement | À prévoir |
|---|---|---|
| Greffon DNS du registrar (`certbot-dns-*`) | **automatique** | un jeton d'API, en `600`, hors du dépôt |
| `--manual --preferred-challenges dns` | **à la main, tous les 60 jours** | un rappel dans un agenda, et quelqu'un pour l'honorer |

⚠️ **La voie manuelle n'est pas une variante, c'est une dette.** Un certificat
expiré coupe le site pour tous les transporteurs à la fois, un dimanche, sans
préavis autre qu'un courriel envoyé 20 jours plus tôt. Si le registrar n'a pas
de greffon, la question à trancher n'est pas « comment renouvele-t-on » mais
« qui est responsable du renouvellement ».

**3. `*.beninlink.app` ne couvre pas `beninlink.app`.** Un générique ne vaut que
pour **un** niveau de sous-domaine. Le domaine apex — celui du back-office
central — doit être demandé explicitement dans le même certificat. C'est la
faute la plus courante, et elle ne se voit qu'en visitant la racine.

## L'œuf et la poule

`beninlink.conf` référence `/etc/letsencrypt/live/beninlink.app/fullchain.pem`.
Tant que ce fichier n'existe pas, **nginx refuse de démarrer** :

```
nginx: [emerg] cannot load certificate "/etc/letsencrypt/live/beninlink.app/fullchain.pem"
```

Poser la configuration avant le certificat coupe donc le serveur web — y compris
le site qui tournait peut-être déjà dessus. **Le certificat vient d'abord.**

## L'ordre

### 1. Le DNS, avant tout le reste

```
beninlink.app.      A     <IP du VPS>
*.beninlink.app.    A     <IP du VPS>
```

Vérifier la propagation avant de continuer — un défi DNS-01 sur une zone qui
n'a pas convergé échoue sans expliquer :

```bash
dig +short beninlink.app
dig +short n-importe-quoi.beninlink.app
```

### 2. Le certificat, nginx encore arrêté ou sur son ancienne conf

```bash
sudo apt install -y certbot python3-certbot-nginx

sudo certbot certonly \
  --preferred-challenges dns \
  -d beninlink.app -d '*.beninlink.app' \
  --agree-tos -m alerte@beninlink.app --no-eff-email
```

Ajouter `--manual` s'il n'y a pas de greffon DNS ; certbot demande alors de
créer l'enregistrement `_acme-challenge` à la main et **attend**.

> Les guillemets autour de `'*.beninlink.app'` ne sont pas cosmétiques : sans
> eux, le shell développe l'astérisque en noms de fichiers du répertoire courant.
>
> L'adresse `-m` n'est pas une formalité : c'est **la seule** qui recevra les
> avertissements d'expiration, 20 jours puis 1 jour avant. Mettre une boîte
> réellement lue — pas celle d'un compte technique que personne n'ouvre.

Le résultat attendu :

```bash
sudo certbot certificates     # doit lister beninlink.app ET *.beninlink.app
```

### 3. La configuration nginx, une fois le certificat en place

```bash
sudo cp docs/guides/infra/nginx/beninlink.conf /etc/nginx/sites-available/beninlink.conf
sudo ln -sf /etc/nginx/sites-available/beninlink.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default        # la page « Welcome to nginx »
sudo nginx -t && sudo systemctl reload nginx
```

`nginx -t` est la seule vérification qui compte avant un `reload` : elle relit
tout, y compris les chemins de certificat, sans toucher au service en marche.

### 4. Vérifier ce qui n'est pas visible

```bash
# Le générique couvre-t-il un sous-domaine quelconque ET la racine ?
echo | openssl s_client -connect beninlink.app:443 -servername une-societe.beninlink.app 2>/dev/null \
  | openssl x509 -noout -subject -ext subjectAltName -dates

# Le renouvellement automatique fonctionne-t-il pour de bon ?
sudo certbot renew --dry-run
```

Le second est le plus important du guide. Il rejoue un renouvellement complet
sans consommer de quota. **S'il échoue, le certificat expirera dans 90 jours** —
et personne ne le saura avant.

## Les deux accords à ne pas rompre

La configuration nginx partage deux valeurs avec `php/beninlink.ini`. Chacune
est la plus basse qui décide, en silence, quand les deux divergent.

| Valeur | nginx | PHP | Ce qui casse si elles divergent |
|---|---|---|---|
| Taille des envois | `client_max_body_size 25M` | `upload_max_filesize 25M` | l'import Excel échoue avant la limite annoncée |
| Durée d'exécution | `fastcgi_read_timeout 120s` | `max_execution_time 120` | **504 au bout de 60 s** avec le défaut d'nginx |

Le second était un écart **réel** dans la version précédente de ce fichier :
nginx s'en tenait à son défaut de 60 secondes pendant que PHP était réglé à 120.
Or `ParcelImport` n'implémente pas `ShouldQueue` — l'import Excel des colis
s'exécute **dans la requête**, comme la génération des relevés PDF. Sur un gros
import, nginx aurait rendu un 504 pendant que PHP continuait d'écrire en base :
le marchand voit une erreur, et son import est passé quand même. Corrigé ici.

## HSTS : un engagement, pas un réglage

La configuration envoie `Strict-Transport-Security` avec un `max-age` de **300
secondes**, délibérément court. Une fois reçu, un navigateur refuse tout HTTP
sur le domaine **et ses sous-domaines** pendant toute la durée annoncée — et
cette durée ne se rétracte pas : elle s'écoule.

C'est bien ce que l'on veut ici, chaque société étant un sous-domaine servi en
HTTPS. Mais on le vérifie avant de s'engager pour un an :

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

À passer une fois que la chaîne complète est en service et qu'un sous-domaine
quelconque répond en HTTPS. Pas avant.

## `http2`, et pourquoi la forme dépréciée est gardée

`listen 443 ssl http2;` est déprécié depuis nginx **1.25.1**, au profit d'une
directive `http2 on;` séparée. Mais cette directive **n'existe pas** avant, et
Ubuntu 22.04 sert nginx 1.18 : l'adopter casserait le démarrage là où le
déploiement a le plus de chances d'atterrir.

La forme actuelle marche partout, au prix d'un avertissement au démarrage sur
les versions récentes. Un avertissement lisible vaut mieux qu'une panne
portable. À basculer le jour où le serveur est connu et récent :

```nginx
listen 443 ssl; listen [::]:443 ssl;
http2 on;
```

## Ce que la configuration protège déjà

**Les fichiers cachés** sont refusés, sauf `/.well-known` — nécessaire à ACME.
Le bloc HTTP sert d'ailleurs ce répertoire **avant** sa redirection : sans cette
exception, un renouvellement par défi HTTP-01 échouerait sur un 301.

**PATH_INFO.** Le snippet Debian `snippets/fastcgi-php.conf` contient
`try_files $fastcgi_script_name =404;`, qui ferme la faille classique : sans
lui, une URL comme `/photo.png/x.php` ferait exécuter un fichier téléversé.
C'est une raison de garder l'`include` plutôt que de recopier ses lignes.
