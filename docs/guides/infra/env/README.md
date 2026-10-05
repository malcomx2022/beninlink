# Configurer le `.env` de production

> Le modèle est `docs/guides/infra/.env.example`. Ce guide dit **d'où vient
> chaque valeur**, ce qui casse quand elle manque, et comment le vérifier avant
> d'ouvrir le site.
> Il se lit après le provisionnement PHP (`infra/php/`) et avant le premier
> déploiement.

Le fichier vit à `/var/www/beninlink/web/.env`, appartient à `deploy`, et
**n'est jamais versionné** (`.gitignore` l'exclut). `deploy.sh` ne le touche
pas : il est posé une fois, à la main, et survit aux déploiements.

```bash
sudo -u deploy cp docs/guides/infra/.env.example /var/www/beninlink/web/.env
sudo chmod 640 /var/www/beninlink/web/.env
```

`640` : lisible par `deploy` et son groupe, pas par le reste du monde. Le
fichier contient la clé secrète FedaPay et le mot de passe SMTP.

---

## 1. La ligne sans laquelle rien ne s'affiche

```env
APP_INSTALLED=yes
```

`IsInstalledMiddleware` redirige **chaque requête** vers `/install` tant que
cette valeur ne vaut pas exactement `yes`. Un déploiement techniquement
réussi — migrations passées, caches construits, site remonté — servirait alors
l'**installateur We Courier**, qui propose de recréer la base.

L'installateur écrit cette ligne lui-même quand on passe par lui. Sur une
installation déployée par `deploy.sh`, personne ne le fait à sa place. C'est le
premier symptôme à reconnaître : *le déploiement a réussi et le site montre un
formulaire d'installation.*

> La vérification de licence du socle (`PurchaseVerify`) est neutralisée dans ce
> dépôt — elle renvoie `true`. Aucune variable à prévoir de ce côté.

## 2. Les deux valeurs à générer, sur le serveur

```bash
cd /var/www/beninlink/web
php artisan key:generate                      # écrit APP_KEY dans le .env
php -r 'echo "blk_".bin2hex(random_bytes(16)), PHP_EOL;'   # à coller dans API_KEY
```

**`APP_KEY`** signe les cookies et chiffre les sessions. Elle se génère **sur le
serveur** — jamais recopiée d'un poste de développement.

La changer déconnecte tout le monde et invalide les URL signées ; elle ne
détruit **aucune donnée**, ce projet n'ayant aucune colonne `encrypted`
(vérifié sur `app/` et `database/`). C'est une gêne, pas un incident — mais une
gêne sans contrepartie, donc on ne la change pas sans raison.

**`API_KEY`** est la clé que les applications mobiles présentent à chaque
requête. Le socle en livrait une **en dur, identique pour toutes les
installations** (constat S3) ; il faut donc en générer une propre à celle-ci.

⚠️ Elle voyage dans le bundle des APK : **ce n'est pas une authentification**,
et les données restent protégées par Sanctum. Sa rotation impose de republier
les deux applications mobiles — à décider avant de la changer, pas après.

Depuis **S77** elle n'a **plus de repli** : sans `API_KEY`, l'API refuse
**toutes** les requêtes (y compris la clé publique du socle, que les anciennes
APK We Courier présentent), et `verifier-env.sh` arrête le déploiement avant
la coupure du site. La même valeur se pose côté apps dans `EXPO_PUBLIC_API_KEY`.

## 3. Les valeurs à obtenir ailleurs

| Variable | Où la trouver | Ce qui arrive si elle manque |
|---|---|---|
| `DB_DATABASE` `DB_USERNAME` `DB_PASSWORD` | à la création de la base MySQL | `deploy.sh` échoue à la migration — proprement, site remonté |
| `FEDAPAY_PUBLIC_KEY` `FEDAPAY_SECRET_KEY` | tableau de bord FedaPay, section **live** | aucune recharge de portefeuille ne démarre |
| `FEDAPAY_WEBHOOK_SECRET` | FedaPay → Workbench → Webhooks | **le pire cas** : le paiement aboutit chez l'opérateur et le portefeuille n'est jamais crédité |
| `MAIL_HOST` `MAIL_USERNAME` `MAIL_PASSWORD` | l'hébergeur de messagerie | les courriels échouent en silence, dans `failed_jobs` |

**Les clés FedaPay `sandbox` et `live` sont distinctes.** Une clé sandbox en
production échoue, et inversement. `FEDAPAY_ENVIRONMENT` accepte `live` ou
`sandbox` ; **toute autre valeur est traitée comme sandbox**, pour qu'une faute
de frappe ne puisse pas faire basculer en production par accident.

Le secret de webhook mérite qu'on s'y arrête : il est **distinct** de la clé
secrète, et c'est lui qui rend un webhook acceptable. Sans lui, aucun n'est
accepté — l'argent part de chez le marchand, la transaction réussit chez
l'opérateur, et le solde ne bouge pas.

## 4. Ce qui ne se règle *pas* ici

C'est la moitié du travail : savoir où **ne pas** chercher.

| Sujet | Où ça vit vraiment | Pourquoi |
|---|---|---|
| **Identifiants SMS** (Twilio, Vonage…) | en base, par société, dans le back-office | chaque transporteur a son compte et sa facture ; les mettre dans le `.env` les rendrait communs |
| **Notifications poussées** | rien à renseigner | le transport est Expo, qui porte lui-même les identifiants FCM et APNs — le backend n'a **aucun** secret à stocker (D11) |
| **Barème de livraison** | en base, par société | *Réglages → Zones et barème* ; ces montants appartiennent au transporteur, pas au logiciel (D4) |
| **Paiement en ligne** (PayPal, Stripe, Razorpay, Paytm) | nulle part — module retiré | il ne vérifiait rien auprès du fournisseur (D10). Renseigner ces variables ne réactive rien, mais laisse croire le contraire |
| **Redis** | nulle part — pas installé | file, cache et sessions passent par la base et le disque (D13) |

## 5. Les trois pièges de production

**`SESSION_DOMAIN` doit rester vide.** Le cookie est alors limité à l'hôte exact
qui l'a posé — donc au sous-domaine d'**une** société. Y mettre
`.beninlink.app` partagerait un même cookie entre tous les transporteurs.
Ce n'est pas un réglage de confort : c'est la frontière entre deux clients.

**`APP_DEBUG=false`, sans exception.** À `true`, une erreur affiche la trace, les
chemins, les versions et parfois la requête SQL — sur une page de paiement.

**`LOG_LEVEL=error`, pas `debug`.** Le défaut du socle est `debug` : il écrit
chaque requête SQL, donc des données de clients, dans un fichier que l'on finit
par envoyer par courriel pour diagnostiquer autre chose. `LOG_CHANNEL=daily`
fait tourner les fichiers au lieu de laisser `laravel.log` grossir sans fin.

## 6. Vérifier avant d'ouvrir le site

```bash
cd /var/www/beninlink/web

# 1. Le fichier est lu, et les valeurs décisives sont celles qu'on croit.
# `false` et `NULL` doivent rester distincts à l'affichage : APP_DEBUG vaut
# légitimement false, APP_INSTALLED absent vaut NULL — et c'est une panne.
php artisan tinker --execute='
  foreach (["app.key","app.env","app.debug","app.app_installed",
            "session.secure","session.domain","logging.default",
            "database.connections.mysql.database"] as $c)
      printf("%-44s %s\n", $c,
          $c === "app.key" ? (config($c) ? "définie" : "MANQUANTE") : var_export(config($c), true));
'

# 2. La base répond
php artisan migrate:status | head -3

# 3. Les caches sont construits sur le BON .env
php artisan config:clear && php artisan config:cache
```

> ⚠️ `config:cache` **fige** le `.env`. Toute modification ultérieure du fichier
> reste sans effet tant qu'on n'a pas rejoué `config:clear && config:cache` —
> ce que `deploy.sh` fait à chaque déploiement. Une valeur corrigée « qui ne
> prend pas » vient presque toujours de là.

Puis, une fois le site ouvert, les deux contrôles qui ne se voient pas dans un
fichier :

```bash
# La file part-elle vraiment ? (worker en marche + SMTP joignable)
php artisan beninlink:file-attente

# Chaque société peut-elle facturer ? (zones et barème saisis)
php artisan beninlink:tarification-prete
```

## 7. Quand une valeur change

| Valeur | Effet du changement |
|---|---|
| `APP_KEY` | déconnecte tous les utilisateurs et invalide les URL signées. Aucune perte de données — aucune colonne n'est chiffrée |
| `API_KEY` | impose de **republier les deux apps mobiles** ; les versions installées cessent d'être servies |
| `FEDAPAY_WEBHOOK_SECRET` | à changer **en même temps** que dans le Workbench FedaPay ; entre les deux, aucun webhook n'est accepté |
| `MAIL_*` | sans effet sur les jobs déjà en échec — les relancer avec `queue:retry all` |
| n'importe laquelle | **sans effet** tant que `config:cache` n'a pas été rejoué |
