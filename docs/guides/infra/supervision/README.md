# Supervision et alertes

> Ce projet a une famille de pannes qui ne se voient pas : l'application répond,
> les écrans sont justes, et quelque chose ne se fait plus. Ce guide les
> inventorie, dit **laquelle a un détecteur**, et — plus important — **lesquelles
> n'en ont pas**.
> Il se lit après la mise en service, avec `infra/supervisor/` et
> `infra/sauvegarde/`.

## Trois commandes peuvent piloter une alerte. Six ne le peuvent pas.

Les neuf commandes `beninlink:*` peuvent toutes sortir en code 1. Mais elles ne
disent pas la même chose en le faisant, et c'est **la** distinction à connaître
avant de brancher quoi que ce soit sur un cron.

### Celles dont la sortie 1 veut dire « quelque chose ne va pas »

| Commande | Sortie 1 quand | Fréquence |
|---|---|---|
| `beninlink:file-attente` | un envoi attend depuis plus de 5 min → **worker arrêté** | 10 min |
| `beninlink:tarification-prete` | une société ne peut plus facturer un colis | quotidien |
| `beninlink:journal-syscohada` | des écritures sont **déséquilibrées** | mensuel |

Ce sont les trois seules à brancher sur une alerte.

### Celles dont la sortie 1 veut dire « tu l'as appelée autrement »

`beninlink:colis-non-debites`, `beninlink:ecarts-marchands`,
`beninlink:retours-annules`, `beninlink:zones-tarifaires`,
`beninlink:reglages-orphelins`, `beninlink:pilote`.

Leur code 1 signale « refusé en production sans `--force` », « aucune société »
ou « rien n'est corrigeable » — jamais un incident.

⚠️ **Le piège est dans l'autre sens.** En mode constat, `colis-non-debites`
renvoie **SUCCESS même quand elle trouve des colis non facturés** :

```php
if (!$this->option('regulariser')) {
    $this->comment('Ajouter --regulariser […] pour écrire les débits manquants.');
    return self::SUCCESS;                      // ← même avec des colis trouvés
}
```

La brancher sur un cron produirait donc un vert permanent, y compris le jour où
de l'argent manque. `ecarts-marchands` a la même forme. **Ces trois commandes
sont des outils de diagnostic, pas des détecteurs** : elles se lisent, elles ne
s'écoutent pas.

## Le crontab de supervision

Il vit dans `infra/scheduler-cron.txt`, qui est le crontab **complet** : ce
guide l'explique, il ne l'augmente pas. Ne pas cumuler les deux sources, sous
peine de faire tourner les constats en double.

```cron
MAILTO=""
ALERTE_MAIL=exploitation@beninlink.app

# Planificateur Laravel — indispensable, et sans rapport avec la supervision.
* * * * * cd /var/www/beninlink/web && php artisan schedule:run >> /dev/null 2>&1

# La file des envois : worker arrêté = plus aucun SMS ne part (D13).
*/10 * * * * /usr/local/bin/beninlink-surveiller beninlink:file-attente >> /var/log/beninlink-supervision.log 2>&1

# Chaque société peut-elle facturer ? Une route non tarifée bloque la création.
30 6 * * * /usr/local/bin/beninlink-surveiller beninlink:tarification-prete >> /var/log/beninlink-supervision.log 2>&1

# Sauvegarde (infra/sauvegarde/) — le script alerte lui-même en cas d'échec.
15 2 * * * /usr/local/bin/beninlink-sauvegarde >> /var/log/beninlink-sauvegarde.log 2>&1
```

`MAILTO=""` est délibéré : sans lui, cron envoie un courriel à **chaque**
exécution qui produit une sortie, y compris les succès. L'alerte vient du
script, qui ne parle que sur échec.

```bash
sudo cp docs/guides/infra/supervision/surveiller.sh /usr/local/bin/beninlink-surveiller
sudo chmod 755 /usr/local/bin/beninlink-surveiller

# Éprouver le chemin d'alerte AVANT d'en dépendre : une commande qui échoue
# à coup sûr, et un courriel qui doit arriver.
ALERTE_MAIL=vous@exemple.org /usr/local/bin/beninlink-surveiller beninlink:pilote
```

`beninlink:pilote` refuse de s'exécuter en production : c'est un échec garanti,
sans effet de bord. Si le courriel n'arrive pas, la chaîne d'alerte n'existe
pas — et il vaut mieux l'apprendre maintenant.

## La panne que ce guide ne couvre pas : le cron lui-même

Un cron qui cesse de tourner **ne produit aucune alerte**. C'est la panne
silencieuse de la surveillance elle-même, et elle se règle par l'inverse : un
service extérieur qui attend un signal, et qui alerte quand il **n'arrive pas**.

```cron
# Témoin d'activité : prévient si CE serveur cesse de se manifester.
*/15 * * * * curl -fsS -m 10 --retry 3 https://<sonde>/<identifiant> > /dev/null
```

Les services de ce type sont gratuits jusqu'à quelques sondes. Tant qu'il n'y en
a pas, la supervision repose sur la bonne santé du serveur qu'elle surveille.

## Ce qui n'a aucun détecteur

Les nommer vaut mieux que de laisser croire à une couverture complète.

| Angle mort | Ce qui arrive | Ce qui existerait |
|---|---|---|
| **Le site ne répond plus** | rien ne le signale depuis le serveur | une sonde HTTP extérieure sur `/` |
| **Certificat expiré** | coupure totale, un dimanche | `certbot renew --dry-run` en cron mensuel + l'alerte de Let's Encrypt |
| **Disque plein** | la base refuse d'écrire, les sauvegardes échouent | `df` en cron ; les sauvegardes et `daily` grossissent |
| **Webhooks FedaPay refusés** | le paiement aboutit, le portefeuille n'est pas crédité | trois lectures en base — `supervision/fedapay-webhooks.md` |
| **Écarts comptables** | découverts au relevé suivant | les trois constats, à lire à la main |

**Le webhook FedaPay est l'angle mort qui coûte le plus cher.** Un secret de
signature erroné fait refuser *tous* les webhooks : l'argent part du marchand,
la transaction réussit chez l'opérateur, et le solde ne bouge pas. Rien dans
l'application ne le signale.

Il n'y a toujours rien à *écouter* — mais il y a désormais quelque chose à
**lire** : `supervision/fedapay-webhooks.md` donne les trois requêtes qui
confrontent la transaction à la ligne de portefeuille, dont celle qui trouve
l'état que le rejeu ne rattrape pas — approuvé chez FedaPay, jamais crédité
chez nous. Et toujours, après toute rotation de clé : une recharge réelle de
petit montant, et le tableau de bord FedaPay, qui liste les webhooks en échec.

## Les journaux : où regarder, dans quel ordre

```bash
tail -100 /var/www/beninlink/web/storage/logs/laravel.log      # 1. l'application
tail -100 /var/www/beninlink/web/storage/logs/worker.log       # 2. la file
sudo tail -100 /var/log/nginx/error.log                        # 3. le service web
tail -100 /var/log/beninlink-supervision.log                   # 4. les constats
```

L'ordre n'est pas arbitraire : une erreur applicative apparaît dans le premier ;
si le premier est muet et que le site répond mal, c'est nginx ou PHP-FPM ; si
les envois ne partent pas alors que l'application va bien, c'est le second.

`LOG_CHANNEL=daily` fait tourner `laravel.log` sur 14 jours (voir `infra/env/`).
Ce n'est pas de l'hygiène : sur `single`, le fichier grossit jusqu'à remplir le
disque, et c'est la base qui s'arrête en premier.

## Ce qu'il ne faut pas surveiller

**`database:autobackup`** — commande du socle We Courier, à ne **pas** brancher
sur un cron. Elle construit un dump à la main et l'envoie **par courriel** :

- `charset=utf8` alors que la base est en `utf8mb4` ;
- les valeurs sont assemblées par `implode("','", …)`, **sans échappement** :
  une seule apostrophe — « L'Express », « Cotonou l'Ancien » — produit un
  fichier SQL irrécupérable, et le français en est plein ;
- `NULL` devient la chaîne vide ;
- le dump entier tient en mémoire PHP avant d'être expédié.

Elle était en outre **planifiée quotidiennement** — donc active partout où le
cron lance `schedule:run`. Depuis le 2026-09-09 elle **refuse de s'exécuter** et
n'est plus planifiée ; il n'y a donc plus rien à surveiller de ce côté.

La sauvegarde du projet est `infra/sauvegarde/` : `mysqldump`, sur disque, avec
un exercice de restauration. Voir ce guide, § « Ce qu'il ne faut pas utiliser ».
