# Reprise après incident

> Ce guide se lit **pendant** la panne, pas avant. Il est rangé par **symptôme**,
> parce qu'un incident ne commence jamais par un diagnostic : il commence par ce
> qu'on voit. Pour chaque symptôme : ce que ça veut dire, quoi faire, et **à quoi
> l'on reconnaît que c'est fini**.
> Écrit le 2026-09-10, sur `main` (`d00d79a`).

Il complète trois guides, il ne les remplace pas : `infra/supervision/` dit ce
qui **détecte** une panne, `infra/sauvegarde/` comment **restaurer**,
`infra/mise-en-service/` comment **remonter** un serveur. Ici : la demi-heure
entre les deux.

## Avant tout : trois questions, dans cet ordre

1. **Le site répond-il ?** `curl -sI https://beninlink.app/ | head -1`
2. **L'argent est-il juste ?** les trois constats (§8)
3. **Les envois partent-ils ?** `php artisan beninlink:file-attente`

L'ordre compte. Un site debout qui compte faux est **pire** qu'un site coupé :
le second ne crée pas d'écritures, le premier en crée de fausses. Si le doute
porte sur l'argent, couper est une décision raisonnable — `php artisan down`.

## 1. Le site affiche « en maintenance » et ne revient pas

`deploy.sh` coupe le site, travaille, puis le remonte, et un filet
(`trap … ERR`) le remonte aussi quand une étape échoue. Mais le filet ne peut
rien si le script n'existe plus : connexion SSH coupée, coureur GitHub
interrompu, processus tué.

Le drapeau est un fichier : `storage/framework/down` (pilote `file`,
`config/app.php`).

```bash
cd /var/www/beninlink/web
php artisan up                      # la façon normale
ls -l storage/framework/down        # le drapeau, s'il reste
```

⚠️ **Ne remontez pas le site avant de savoir où le déploiement s'est arrêté.**
`deploy.sh` fait `git pull` **après** la coupure : si `composer install` s'est
interrompu, le code est neuf et `vendor/` incomplet. Remonter là, c'est servir
une application qui n'a pas tous ses morceaux. Reprenez la suite du script à la
main, dans son ordre :

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan beninlink:tarification-prete          # doit sortir en succès
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

Chacune de ces commandes se rejoue sans dommage. Si vous préférez revenir en
arrière : `git checkout <sha précédent>` puis la même suite — **sauf** si la
migration est déjà passée (voir §7, elle ne se défait pas).

**C'est fini quand** : `curl -sI https://beninlink.app/` rend 200, et
`storage/framework/down` n'existe plus.

## 2. Le déploiement s'est arrêté sur la tarification

```
Le barème hérité porte encore quelque chose — les quatre colonnes ne peuvent pas être retirées.
```

Ce n'est pas une panne : c'est le garde-fou de l'étape 6 (**D4**). L'installation
n'a pas converti son barème, et la migration refuse plutôt que de laisser une
société ne plus rien facturer. **Le site est remonté, la base est intacte.**

La marche à suivre est dans le message lui-même, et elle se joue depuis la
**version précédente** du code — celle qui porte encore le convertisseur
(`--appliquer`). Le message reste juste sur ce point ; sa parenthèse, elle, a
vieilli : `beninlink:zones-tarifaires` **existe toujours** dans la version
actuelle, avec un autre métier — il installe les zones, il ne convertit plus.

Sur une installation **neuve**, le blocage est différent et bien plus fréquent :
voir `infra/mise-en-service/` — le `db:seed` du socle crée deux sociétés et n'en
dote qu'une de zones.

**C'est fini quand** : `php artisan beninlink:tarification-prete` sort en succès.

## 3. Le site renvoie tout le monde vers `/install`

`APP_INSTALLED` ne vaut pas exactement `yes` dans `web/.env`, ou les tables du
socle manquent (`IsInstalledMiddleware` vérifie les deux).

⚠️ **Ne passez jamais par l'installateur sur une base qui porte des données** :
il propose de la recréer, et il réécrit le `.env`. Corrigez la ligne, puis
`php artisan config:clear`.

**C'est fini quand** : la page d'accueil ne redirige plus, et
`php artisan tinker --execute='var_dump(config("app.app_installed"));'` dit
`"yes"`.

## 4. Plus aucun SMS ni push ne part

```bash
php artisan beninlink:file-attente
```

Elle lit trois nombres : **en attente**, l'**âge du plus ancien** job, et les
**échoués**. Un âge de plusieurs minutes avec un worker censé tourner signifie
que le worker est arrêté (**D13**) :

```bash
sudo supervisorctl status beninlink-worker
sudo supervisorctl start beninlink-worker
```

Puis vient la décision que rien n'automatise : **rejouer ou abandonner.**

| Situation | Geste | Ce qu'il faut savoir |
|---|---|---|
| Des jobs attendent, la cause est levée | rien — le worker les prend | un changement de statut vieux de six heures partira maintenant : le message est tardif, pas faux |
| Des jobs ont **échoué** (`failed_jobs`) | `php artisan queue:retry all` | à ne faire qu'**après** avoir corrigé la cause (identifiants d'opérateur, réseau), sinon ils réchoueront |
| Des échoués à abandonner délibérément | `php artisan queue:flush` | **irréversible**, et un SMS abandonné ne laisse aucune trace côté client |

⚠️ Avant de rejouer en masse, regardez **ce que** vous rejouez : ces jobs
envoient de vrais SMS, facturés. Un rejeu de plusieurs centaines d'envois vieux
d'une journée coûte de l'argent et crée de la confusion chez les destinataires.

**C'est fini quand** : `beninlink:file-attente` sort en succès, et un envoi réel
traverse la file (le contrôle de `infra/supervisor/`, avec votre propre numéro).

## 5. Une recharge Mobile Money n'a pas crédité le portefeuille

C'est la panne silencieuse la plus coûteuse du produit, et elle n'a **aucun
détecteur** (`infra/supervision/` le dit). Le client a payé chez l'opérateur ; le
webhook n'est pas arrivé, ou a été refusé pendant la coupure. La transaction
reste `pending`, le solde ne bouge pas.

**Rien ne rattrape tout seul.** Vérifié dans le code : `fedapay/status/{reference}`
ne lit que la ligne locale — il n'interroge pas FedaPay —, et aucune tâche
planifiée ne fait de rapprochement. Il faut une main.

Deux réparations, et **aucune des deux ne peut créditer deux fois** :

1. **Rejouer le webhook** depuis le tableau de bord FedaPay. C'est la voie
   préférable : le contrôleur est idempotent par **verrou de ligne** — un second
   passage voit la transaction déjà `approved` et ressort sans rien faire.
2. **Approuver la demande de recharge à la main** dans l'administration. Le
   service ne crédite qu'un portefeuille encore `PENDING`, sous verrou lui aussi.

⚠️ **Le rejeu ne vaut que si la transaction est restée `pending`.** Si elle est
déjà `approved` alors que le portefeuille est encore en attente — le statut et
le crédit sont deux transactions distinctes, une interruption entre les deux
laisse cet état —, le rejeu ressort sur `already processed` **avant** d'atteindre
le crédit : seule l'approbation à la main répare. La requête qui trouve ces
lignes est dans `infra/supervision/fedapay-webhooks.md`.

Les deux chemins passent par **la même ligne de portefeuille**, et chacun exige
qu'elle soit encore en attente : celui qui arrive second ne fait rien. Si vous
approuvez à la main et qu'un rejeu tombe ensuite, le journal portera
`FedaPay : crédit du wallet en échec` — **c'est la sécurité qui parle**, pas un
échec à corriger.

Deux lignes de journal à chercher après tout incident de paiement :

```bash
grep -n "montant payé différent du montant attendu" storage/logs/laravel-*.log
grep -n "webhook pour une transaction inconnue"      storage/logs/laravel-*.log
```

La première signale un écart entre le montant payé et le montant attendu : le
code crédite **le montant attendu**, jamais celui annoncé par l'événement, et
laisse le rapprochement à un humain.

⚠️ Un refus arrivé **après** une approbation ne défait pas le crédit : c'est un
remboursement, décidé à la main, avec le comptable.

**C'est fini quand** : le solde du marchand correspond à ses recharges
approuvées, et `php artisan beninlink:ecarts-marchands` ne montre rien pour lui.

## 6. Le scheduler ne tourne plus

`schedule:run` est la ligne de cron dont tout le reste dépend
(`infra/scheduler-cron.txt`), et elle écrit dans `/dev/null` : si elle cesse de
tourner, **rien ne le dit**.

```bash
crontab -l | grep schedule:run
php artisan schedule:list
```

Le signe indirect : la génération des relevés ne se fait plus —
`invoice:generate` est planifiée **chaque jour à 13 h**, et c'est la seule tâche
du planificateur depuis la neutralisation de la sauvegarde du socle. Le jour où
vous le constatez, la question n'est pas seulement « pourquoi » mais « combien de
jours n'ont pas été traités ».

## 7. La base a été restaurée — ou la migration de l'étape 6 vous tente

Pour restaurer : `infra/sauvegarde/`. Ce qui relève de ce guide, c'est **ce qu'on
fait juste après** (§8) et **ce qu'on ne fait pas**.

⚠️ **`migrate:rollback` sur la migration de l'étape 6 n'est pas un retour en
arrière.** Son `down()` recrée les quatre colonnes **vides**, à 0 : le schéma
revient, les montants non — ils ont été convertis en lignes zonées, et les lignes
héritées sont supprimées. Le code qui les lisait est parti avec elles. Défaire
cette migration sans défaire le code donne un barème à **zéro franc**, c'est-à-dire
des livraisons gratuites, sans que rien n'échoue. La migration le dit elle-même
dans son en-tête ; ce guide le répète parce que c'est la tentation d'une nuit de
panne.

## 8. Après tout incident : les trois constats

Ils existent pour ce moment précis — et ils se **lisent**, ils ne s'écoutent pas
(deux d'entre eux sortent en succès même quand ils trouvent quelque chose) :

```bash
php artisan beninlink:colis-non-debites      # colis jamais facturés
php artisan beninlink:ecarts-marchands       # solde qui ne répond plus au relevé
php artisan beninlink:retours-annules        # frais de retour prélevé, jamais rendu
```

Un constat non vide après un incident n'est pas du bruit historique : c'est la
trace de ce qui s'est passé. Régularisez **marchand par marchand**
(`--marchand=<id>`), jamais d'un bloc : le constat se relit, une écriture ne se
relit pas. En production, `--regulariser` et `--corriger` demandent `--force`.

Puis trois lignes dans un fichier, datées : **ce qu'on a vu, ce qu'on a fait, ce
qui reste à vérifier**. Le prochain incident sera différent ; la mémoire de
celui-ci est ce qui rend le suivant plus court.

## 9. Le VPS est perdu

Une sauvegarde qui dort **sur** le VPS protège d'un `DROP TABLE`, pas de la perte
du VPS. Les trois choses à avoir ailleurs : le dump de la base,
`public/uploads/` — les signatures de livraison n'existent nulle part ailleurs —
et le `.env`.

La reprise, c'est `infra/mise-en-service/` du début, puis la restauration.

**Sur `APP_KEY`, une inquiétude à lever** : la perdre **ne rend aucune donnée
illisible** ici. Rien dans le modèle de données n'est chiffré au repos — vérifié,
aucun attribut n'est en cast chiffré. Ce qui casse, ce sont les sessions et les
cookies signés : tout le monde est déconnecté, et se reconnecte. Une nouvelle clé
est donc une gêne, pas une perte.

## Ce que ce guide ne peut pas faire

Trois trous restent des trous, et mieux vaut les savoir que les découvrir :

- **Aucun rapprochement automatique avec FedaPay.** Un webhook perdu reste perdu
  jusqu'à ce qu'un humain regarde le tableau de bord de l'opérateur.
- **Aucune alerte sur un webhook refusé**, ni sur un certificat expiré, un disque
  plein, un site injoignable — l'inventaire est dans `infra/supervision/`.
- **Aucun retour arrière automatique d'un déploiement.** Revenir en arrière est
  une suite de gestes (§1), et elle ne défait pas les migrations déjà jouées.
