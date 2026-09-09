# Sauvegarder, et surtout restaurer

> Le script est `sauvegarde/sauvegarde.sh`. Ce guide dit **ce qu'il faut
> sauvegarder**, ce qu'il est inutile de sauvegarder, et comment vérifier que la
> sauvegarde vaut quelque chose — la seule question qui compte.
> Il se lit après la mise en service, et se relit le jour où l'on en a besoin.

## Trois choses, et trois seulement

| Quoi | Où | Pourquoi ça ne se reconstruit pas |
|---|---|---|
| **La base** | MySQL | colis, relevés, soldes, écritures |
| **`public/uploads/`** | disque | **n'existe que sur ce serveur** |
| **`.env`** | disque | non versionné, par construction |

**`public/uploads/` mérite qu'on s'y arrête.** Chaque sous-dossier porte un
`.gitignore` contenant `*` et `!.gitignore` : la structure est dans le dépôt, le
contenu ne l'est **jamais**. On y trouve :

- `parcel/signature/` — les **signatures manuscrites de livraison**. Ce ne sont
  pas des images d'agrément : c'est la preuve de ce qui a été remis, à qui. Les
  perdre, c'est perdre le moyen de trancher une contestation.
- `merchant/nid/` et `merchant/trade_license/` — pièces d'identité et registres
  de commerce des marchands.
- `parcel/image/`, `profile/`, `settings/` — le reste.

Une sauvegarde de base seule laisserait donc, après restauration, des colis
livrés sans preuve et des marchands sans dossier.

## Ce qu'il ne faut pas utiliser

Le socle We Courier livre une commande `database:autobackup`. **Ne pas la
brancher sur un cron.** Elle construit un dump à la main, en PHP, et l'envoie
**par courriel** à l'adresse des réglages. Quatre défauts, dont un rédhibitoire :

```php
$connect = new \PDO("mysql:host=$mysqlHostName;dbname=$DbName;charset=utf8", …);
…
$output .= "'" . implode("','", $table_value_array) . "');\n";
```

- **Aucun échappement.** Les valeurs sont assemblées par `implode("','", …)` :
  une seule apostrophe — « L'Express », « Cotonou l'Ancien » — produit un fichier
  SQL **irrécupérable**. Le français en est plein, et l'erreur ne se voit qu'à la
  restauration.
- **`charset=utf8`** alors que la base est en `utf8mb4` : les caractères sur
  quatre octets sont tronqués à la lecture.
- **`NULL` devient la chaîne vide** — une date nulle restaurée en `''`.
- Le dump entier est construit **en mémoire PHP** puis expédié en pièce jointe :
  au-delà de quelques milliers de lignes, la commande meurt ; en deçà, elle
  envoie toute la base — pièces d'identité comprises — en clair par courriel.

Elle était de surcroît **planifiée quotidiennement** dans `Console\Kernel` :
sur toute installation dont le cron lance `schedule:run`, elle tournait chaque
nuit — et échouait en silence, la vue de courriel qu'elle appelle n'existant même
pas.

**Depuis le 2026-09-09, elle refuse de s'exécuter et n'est plus planifiée.** La
classe reste, pour que `php artisan database:autobackup` réponde à qui la
connaît au lieu de disparaître sans explication. Deux tests l'épinglent : le
refus, et l'absence de planification.

Le script de ce guide fait le même travail avec `mysqldump`, sur disque, et avec
un exercice de restauration qui prouve le résultat.

## Ce qu'il est inutile de sauvegarder

`vendor/` se réinstalle (`composer install`), le code est dans git, le
certificat se redemande, `storage/logs/` n'a pas de valeur de reprise. Les
sauvegarder ne coûte que de la place et du temps de restauration.

## Ce que ce projet ne perd pas

Une mise en garde répandue veut que perdre `APP_KEY` rende les données
illisibles. **Ce n'est pas le cas ici** : la recherche de `encrypted` sur `app/`
et `database/` ne rend que le middleware `EncryptCookies` de Laravel — aucune
colonne du schéma n'est chiffrée.

Perdre `APP_KEY` déconnecte tout le monde et invalide les URL signées. C'est une
gêne, pas un incident. Le `.env` reste à sauvegarder pour ce qu'il contient
d'autre : les clés FedaPay, le secret de webhook, le mot de passe SMTP,
l'`API_KEY` des applications mobiles — celle-là, si elle change, impose de
republier les deux applications.

## Poser la sauvegarde

```bash
sudo mkdir -p /var/backups/beninlink && sudo chown deploy:deploy /var/backups/beninlink
sudo -u deploy cp docs/guides/infra/sauvegarde/sauvegarde.sh /usr/local/bin/beninlink-sauvegarde
sudo chmod 755 /usr/local/bin/beninlink-sauvegarde

sudo -u deploy /usr/local/bin/beninlink-sauvegarde      # premier essai, à la main
```

Puis dans le crontab de `deploy` :

```cron
# Sauvegarde quotidienne, 02 h 15 (creux de trafic au Bénin).
15 2 * * * /usr/local/bin/beninlink-sauvegarde >> /var/log/beninlink-sauvegarde.log 2>&1
```

Le script lit les identifiants dans le `.env` et les passe à `mysqldump` par un
fichier temporaire en `600`, jamais sur la ligne de commande : un
`-p<mot de passe>` reste visible de tout le serveur dans `ps aux` le temps que
la commande tourne.

`RETENTION_JOURS` vaut 14 par défaut. Deux semaines couvrent le cas courant —
une erreur remarquée quelques jours plus tard. Elles ne couvrent pas une
corruption lente découverte au bout d'un mois : pour cela, garder une copie
mensuelle à part.

## La sauvegarde est elle-même une donnée sensible

Elle contient des pièces d'identité, des numéros de téléphone, des montants, et
les clés d'un compte de paiement. Le script pose `700` sur le répertoire et
`600` sur la copie du `.env`, mais deux règles ne se délèguent pas à un script :

- **Jamais sous `public/`.** Une sauvegarde dans un répertoire servi par nginx
  est téléchargeable par quiconque devine son nom. C'est la façon la plus
  courante de perdre une base entière.
- **Chiffrer ce qui sort du serveur.** Une copie hors site est un fichier qui
  voyage : `gpg -c` ou l'équivalent de l'hébergeur avant l'envoi.

## Restaurer

L'ordre compte. Chaque étape suppose la précédente.

```bash
cd /var/www/beninlink/web
php artisan down                                   # 1. couper : plus d'écritures

gunzip -c /var/backups/beninlink/base-<HORODATAGE>.sql.gz \
  | mysql -u beninlink -p beninlink                # 2. la base

tar -xzf /var/backups/beninlink/fichiers-<HORODATAGE>.tar.gz \
    -C /var/www/beninlink/web                      # 3. les fichiers

cp /var/backups/beninlink/env-<HORODATAGE> .env    # 4. le .env
chmod 640 .env

php artisan optimize:clear                         # 5. vider les caches
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart                          # 6. le worker relit le code
php artisan up
```

**L'étape 5 n'est pas optionnelle.** `config:cache` fige le `.env` : sans
`optimize:clear`, l'application continuerait de lire l'ancienne configuration —
y compris l'ancienne base de données. C'est le piège classique d'une
restauration qui « ne prend pas ».

⚠️ **`php artisan migrate` ne fait pas partie de la restauration.** La
sauvegarde contient le schéma dans l'état où il était ; lancer les migrations
par réflexe rejouerait celles qui manquent, dont **la migration irréversible de
l'étape 6**. Restaurer d'abord, vérifier ensuite avec `migrate:status`, et ne
migrer que si l'écart est compris.

## L'exercice de restauration

C'est le seul paragraphe de ce guide qui compte vraiment.

**Une sauvegarde jamais restaurée n'est pas une sauvegarde : c'est un fichier.**
On ne découvre pas qu'un `mysqldump` était tronqué, qu'un `tar` excluait un
dossier ou qu'un cron avait cessé de tourner le jour où la production a brûlé.

À faire une fois avant la mise en service, puis **tous les trimestres** :

```bash
# Sur une base JETABLE, jamais sur la production.
mysql -e "CREATE DATABASE beninlink_essai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
gunzip -c /var/backups/beninlink/base-<HORODATAGE>.sql.gz | mysql beninlink_essai

# Ce qu'on vérifie : que les données y sont, pas que le fichier existe.
mysql beninlink_essai -e "
  SELECT 'colis' t, COUNT(*) n FROM parcels
  UNION ALL SELECT 'marchands', COUNT(*) FROM merchants
  UNION ALL SELECT 'relevés',   COUNT(*) FROM merchant_statements;
"
tar -tzf /var/backups/beninlink/fichiers-<HORODATAGE>.tar.gz | grep -c 'parcel/signature/'

mysql -e "DROP DATABASE beninlink_essai;"
```

Les compteurs doivent ressembler à la production. Le `grep -c` sur les
signatures doit rendre un nombre **cohérent avec le nombre de colis livrés** :
c'est le contrôle qui attrape un `tar` qui aurait silencieusement sauté un
sous-dossier.

Noter la date de l'exercice quelque part. Un exercice qu'on croit avoir fait
« il y a quelques mois » n'a pas eu lieu.

## Hors site

Une sauvegarde sur le VPS protège d'un `DROP TABLE` et d'une migration ratée.
Elle ne protège **pas** de la perte du VPS — panne matérielle, compte suspendu,
répertoire effacé. Ces deux risques sont distincts, et le second est celui qui
ferme une entreprise.

Copier chaque nuit `/var/backups/beninlink/` ailleurs : un espace de stockage de
l'hébergeur, un autre serveur, un poste de bureau. Le moyen importe moins que le
fait qu'il soit **ailleurs**, et que la copie soit chiffrée.

## Avant le premier déploiement

Un cas particulier, à ne pas manquer : le premier déploiement transporte la
**migration de l'étape 6**, qui retire quatre colonnes héritées et supprime les
lignes de barème sans zone. Son `down()` restaure le schéma, **pas les
montants**.

Sur une base neuve, la sauvegarde est vide et le geste inutile. Sur une base qui
tourne déjà, c'est le seul retour arrière qui existe.
