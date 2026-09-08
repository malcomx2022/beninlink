# Créer la base MySQL de production

> Une base, un utilisateur, deux paramètres qui ne se rattrapent pas après coup.
> Ce guide se lit **avant** de poser le `.env` (`infra/env/`), qui en reprend les
> valeurs, et donc avant le premier déploiement.

## Ce que l'application attend : une seule base

Le multi-tenant de BeninLink est **à base unique**. Chaque société est une ligne
`company_id`, pas un schéma : `DatabaseTenancyBootstrapper` est **commenté**
dans `config/tenancy.php`.

La conséquence est bonne à prendre : l'utilisateur MySQL n'a **jamais besoin de
créer une base**. Il travaille dans un seul schéma, et le droit `CREATE
DATABASE` — celui qu'on regrette d'avoir accordé — n'entre pas dans le tableau.

## La version minimale, et la seule raison

**MySQL 8.0** (ou 5.7.7+, ou MariaDB 10.2+). Cette borne ne vient pas d'une
préférence : elle tient à **un seul index**.

`Schema::defaultStringLength(191)` dans `AppServiceProvider` plafonne les
chaînes sans longueur explicite. 191 n'est pas un chiffre rond par hasard :
191 × 4 octets (utf8mb4) = **764 octets**, juste sous les 767 qu'un vieil InnoDB
accepte. Tout le socle passe donc partout.

Une colonne échappe au garde-fou, parce qu'elle déclare sa longueur :

```php
// database/migrations/2019_09_15_000020_create_domains_table.php
$table->string('domain', 255)->unique();     // 255 × 4 = 1 020 octets
```

C'est la table des sous-domaines du multi-tenant — celle sans laquelle aucune
société n'est joignable. Sur un InnoDB limité à 767 octets, la migration
s'arrête sur `Specified key was too long`. À partir de MySQL 5.7.7 (format de
ligne `DYNAMIC` par défaut) la limite passe à 3 072 octets, et le problème
disparaît.

C'est le seul index du dépôt au-dessus de 191 caractères. Vérifié, pas supposé.

## Créer la base et l'utilisateur

```sql
CREATE DATABASE beninlink
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'beninlink'@'localhost'
  IDENTIFIED BY '<mot de passe long, généré>';

GRANT SELECT, INSERT, UPDATE, DELETE,
      CREATE, DROP, ALTER, INDEX, REFERENCES,
      LOCK TABLES
  ON beninlink.* TO 'beninlink'@'localhost';

FLUSH PRIVILEGES;
```

**`COLLATE utf8mb4_unicode_ci` est explicite, et ce n'est pas décoratif.**
MySQL 8 crée par défaut en `utf8mb4_0900_ai_ci`. Laravel estampille chaque table
qu'il crée avec la collation de `config/database.php` (`utf8mb4_unicode_ci`), si
bien que les tables migrées seront justes de toute façon. Le risque est
ailleurs : une table créée **hors** Laravel — un import, une reprise de données,
une table temporaire d'analyse — hériterait de la collation du schéma, et une
jointure entre les deux échouerait sur `Illegal mix of collations`. Aligner le
schéma coûte un mot ; le diagnostic coûte une soirée.

**Les privilèges sont ceux des migrations, et rien de plus.** `REFERENCES` sert
aux clés étrangères — 75 migrations en posent. `LOCK TABLES` sert à
`mysqldump`. Ni `CREATE DATABASE`, ni `FILE`, ni `SUPER`.

> `GRANT ALL PRIVILEGES ON beninlink.*` marche aussi et reste borné à ce
> schéma — `ALL` sur une base n'accorde aucun droit global. La liste explicite
> ci-dessus dit simplement de quoi l'application a besoin, ce qui se relit mieux
> dans deux ans.

## Ce que `config/database.php` impose déjà

Trois choses sont fixées côté application, et n'ont donc **pas** à être réglées
sur le serveur :

| Réglage | Valeur | Pourquoi c'est là |
|---|---|---|
| `engine` | **InnoDB**, explicite | MyISAM ignore silencieusement les clés étrangères — le socle en dépend partout |
| `charset` / `collation` | `utf8mb4` / `utf8mb4_unicode_ci` | estampillé sur chaque table créée |
| `strict` | **`false`** | choix du socle We Courier |

⚠️ **`strict => false` mérite d'être connu.** Hors mode strict, MySQL ne lève
pas d'erreur sur une valeur trop longue ou hors plage : il tronque et continue.
C'est un choix du socle, pas de BeninLink, et le remettre à `true` ferait
apparaître des erreurs sur du code qui « marchait ». Ce n'est pas un réglage de
base de données — il ne se corrige pas ici, et pas dans la précipitation d'une
mise en service.

## Sauvegarder avant le premier déploiement

Ce n'est pas une précaution de principe. Le premier déploiement transporte la
**migration de l'étape 6**, qui retire quatre colonnes héritées et supprime les
lignes de barème sans zone. Son `down()` restaure le **schéma**, pas les
montants.

```bash
mysqldump --single-transaction --routines --triggers \
  -u beninlink -p beninlink > ~/beninlink-avant-deploiement.sql
gzip ~/beninlink-avant-deploiement.sql
```

`--single-transaction` évite de verrouiller les tables pendant la copie (InnoDB).

Sur une base **neuve**, la sauvegarde est vide et le geste inutile : elle ne
prend son sens que si la production tourne déjà. Le savoir évite de croire
qu'on est protégé quand on ne l'est pas encore — et l'inverse.

## Vérifier

```bash
mysql -u beninlink -p -e "
  SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
    FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='beninlink';
  SHOW GRANTS FOR CURRENT_USER();
"
```

Puis, **après** le premier déploiement, le contrôle qui compte — une seule ligne
attendue :

```bash
mysql -u beninlink -p beninlink -e "
  SELECT ENGINE, TABLE_COLLATION, COUNT(*) AS tables
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA='beninlink' AND TABLE_TYPE='BASE TABLE'
   GROUP BY ENGINE, TABLE_COLLATION;
"
```

Attendu : **`InnoDB` / `utf8mb4_unicode_ci`**, et une seule ligne. Deux lignes
signalent une table qui a divergé — c'est le moment de le voir, pas au premier
`Illegal mix of collations` en production.

Côté application :

```bash
cd /var/www/beninlink/web
php artisan migrate:status | tail -5     # 102 migrations attendues
```

## Ce qui n'est pas ici

**Une base par société : non.** La tentation existe — `stancl/tenancy` sait le
faire — mais le bootstrapper est commenté et tout le code scope par
`company_id`. L'activer ne se décide pas en créant la base : c'est une bascule
d'architecture, avec ses migrations à rejouer par tenant.

**Le fuseau horaire.** La connexion n'en impose aucun (`config/database.php` ne
pose pas d'`init_command`). Les dates sont écrites par Laravel dans le fuseau de
`APP_TIMEZONE` (`Africa/Porto-Novo`). Ne pas régler le fuseau MySQL « pour bien
faire » : les deux se décaleraient.

**Redis.** Rien à installer : file, cache et sessions passent par la base et le
disque (D13). La table `jobs` fait partie des 102 migrations.
