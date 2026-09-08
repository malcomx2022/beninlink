# Installer le worker, et le surveiller

> Le fichier est `supervisor/beninlink-worker.conf`. Ce guide dit comment le
> poser, et surtout comment vérifier qu'il **travaille** — deux choses
> différentes.
> Il se lit après le premier déploiement : la table `jobs` doit exister.

## La panne que cette étape introduit

Depuis **D13**, les SMS, les notifications poussées et les courriels ne partent
plus dans la requête HTTP. C'était le but : un changement de statut de colis
déclenche jusqu'à deux SMS, et `SmsService` posait un délai d'attente de
**80 secondes** par envoi — l'agent attendait l'opérateur.

Mais la file crée une panne d'un genre nouveau, et **silencieuse** : worker
arrêté, l'application répond normalement, les colis avancent, les écrans sont
justes… et plus rien ne part. Personne ne s'en aperçoit avant qu'un client se
plaigne.

**Installer le worker sans installer sa surveillance ne fait que déplacer le
problème.** Les deux moitiés de ce guide comptent autant l'une que l'autre.

## Le prérequis qui rend tout le reste inutile s'il manque

```bash
cd /var/www/beninlink/web && grep '^QUEUE_CONNECTION' .env
```

Doit dire **`database`**. En `sync`, rien n'est mis en file : les envois
repartent dans la requête, le worker tourne à vide, et tout *semble* marcher.
`beninlink:file-attente` le dit explicitement plutôt que de laisser croire le
contraire.

> C'est le défaut qu'avait la version d'origine de la configuration livrée : elle
> lançait `queue:work redis`, une connexion que l'application n'utilise pas. Le
> worker démarrait, supervisor l'affichait `RUNNING`, et il ne traitait **rien**.
> La configuration actuelle ne nomme aucune connexion : elle suit le `.env`.

## Poser le worker

```bash
sudo apt install -y supervisor
sudo cp docs/guides/infra/supervisor/beninlink-worker.conf \
        /etc/supervisor/conf.d/beninlink-worker.conf

sudo supervisorctl reread     # relit les fichiers, ne touche à rien
sudo supervisorctl update     # applique : démarre ce qui est nouveau
sudo supervisorctl status beninlink-worker
```

`reread` puis `update` — **pas** `restart`. `restart` redémarre ce que
supervisor connaît déjà et ignore un fichier qui vient d'apparaître ; on croit
alors avoir installé le worker alors qu'il n'existe pas encore.

Attendu : deux lignes `RUNNING`, une par processus (`numprocs=2`).

## Vérifier qu'il travaille, et pas seulement qu'il tourne

`RUNNING` dit que le processus existe. Il ne dit pas qu'il consomme la file —
c'est exactement l'écart qui a masqué le défaut `redis`. Le contrôle qui compte
met un envoi réel dans la file et regarde s'il en sort :

```bash
cd /var/www/beninlink/web
php artisan beninlink:file-attente          # état avant

# ⚠️ Ceci envoie un VRAI SMS, facturé, au numéro indiqué : mettre le sien.
# Le premier argument est l'identifiant de la société dont les identifiants
# d'opérateur seront utilisés (voir « Les envois en échec » plus bas).
php artisan tinker --execute='\App\Jobs\SendSms::dispatch(1, "<votre numéro>", "Test BeninLink.");'

sleep 5
php artisan beninlink:file-attente          # doit dire « File vide : tout est parti. »
```

Le SMS qui arrive sur votre téléphone est la seule preuve complète : il valide
la file, le worker, la résolution de la société et les identifiants d'opérateur
d'un seul coup. Une file qui se vide sans qu'aucun SMS n'arrive signale un
échec côté opérateur — et l'envoi se retrouve alors dans `failed_jobs`.

Si la file ne se vide pas, le journal du worker dit pourquoi :

```bash
tail -50 /var/www/beninlink/web/storage/logs/worker.log
```

## Brancher le témoin sur une alerte

`beninlink:file-attente` **sort en erreur** quand le plus ancien envoi dépasse le
seuil (5 minutes par défaut). C'est ce qui permet de la surveiller sans écrire
une ligne de code :

```cron
*/10 * * * * cd /var/www/beninlink/web && php artisan beninlink:file-attente >> /var/log/beninlink-file.log 2>&1
```

⚠️ **Rediriger vers `/dev/null` annule tout l'intérêt de l'étape.** Un témoin
qu'on n'écoute pas n'est pas un témoin. Le fichier de journal est un minimum ;
l'idéal est un envoi de courriel ou un appel à une sonde sur code de sortie ≠ 0.

Cette ligne accompagne celle du planificateur, qui est un **autre** besoin et
n'a pas de rapport avec la file :

```cron
* * * * * cd /var/www/beninlink/web && php artisan schedule:run >> /dev/null 2>&1
```

## Ce que la configuration fait, et pourquoi

| Réglage | Valeur | Raison |
|---|---|---|
| `user` | `deploy` | le même que le pool PHP-FPM (voir `infra/php/`) : les deux écrivent dans `storage/logs` |
| `numprocs` | `2` | deux envois en parallèle ; l'ordre entre deux SMS n'a pas de sens métier |
| `--max-time=3600` | 1 h | recycle le processus : PHP en longue durée finit par fuir |
| `stopwaitsecs` | `3600` | supervisor attend la fin du job courant avant de tuer |
| `autorestart` | `true` | un worker qui meurt sur une erreur fatale repart |

**`stopwaitsecs=3600` mérite un avertissement.** `supervisorctl stop
beninlink-worker` peut donc rester bloqué **jusqu'à une heure** avant que
supervisor n'envoie `SIGKILL`. Ce n'est pas un blocage : c'est la garantie qu'un
SMS en cours d'envoi ne sera pas coupé en deux. Ne pas s'en inquiéter, et
surtout ne pas tuer le processus à la main pendant l'attente.

## Le déploiement et le worker

`deploy.sh` appelle `php artisan queue:restart`. Cette commande ne redémarre
rien elle-même : elle **demande** aux workers de s'arrêter proprement à la fin
du job courant. Supervisor les relance aussitôt, sur le nouveau code.

C'est nécessaire, et pas cosmétique : un worker charge le code **une fois** au
démarrage et le garde en mémoire. Sans `queue:restart`, un déploiement corrigeant
un bug d'envoi laisserait les workers exécuter l'ancienne version indéfiniment.

Rien à faire, donc, au moment d'un déploiement — mais savoir que `RUNNING`
depuis trois semaines, après un déploiement d'hier, est **anormal**.

## Les envois en échec

```bash
php artisan queue:failed          # les lire
php artisan queue:retry all       # les relancer, APRÈS avoir corrigé la cause
php artisan queue:flush           # les jeter — définitif
```

Relancer avant d'avoir corrigé ne fait que remplir `failed_jobs` une seconde
fois. Les causes habituelles : identifiants d'opérateur SMS absents ou faux
(ils vivent **en base, par société**, pas dans le `.env`), ou un SMTP
injoignable.

> **Chaque job porte sa société.** Un job s'exécute hors requête, où `settings()`
> retombe sur la société 1 — un SMS relancé sans précaution partirait avec le nom
> commercial et les identifiants d'opérateur d'un **autre** transporteur, facturés
> à lui. D13 a fermé cela : la société est résolue à la mise en file et voyage
> avec le job. `queue:retry` est donc sûr — parce que le job sait déjà à qui il
> appartient, pas parce que la commande le devine.

## Sans supervisor

Un hébergement mutualisé n'en a pas toujours. Le repli est dans
`scheduler-cron.txt`, commenté :

```cron
* * * * * cd /var/www/beninlink/web && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

Le worker démarre chaque minute, vide la file, s'arrête. Conséquence assumée :
un envoi peut attendre **jusqu'à une minute** — acceptable pour un SMS de
statut, beaucoup moins pour un code de vérification à l'inscription.

⚠️ **Les deux ensemble feraient tourner deux workers pour rien.** C'est un repli,
pas un complément : si supervisor est installé, cette ligne reste commentée.
