# Reprise des relevés de règlement

> Quoi faire quand des relevés n'ont pas été émis : planificateur arrêté,
> incident, ou — le cas le plus discret — **installation à plusieurs
> transporteurs**. Écrit le 2026-09-10, sur `main` (`730a6cc`).
> Se lit avec `infra/reprise/` (l'incident) et `comptabilite/plan-de-comptes.md`
> (ce que les relevés alimentent).

## D'abord, une précision de vocabulaire : ils ne sont pas mensuels

Le rythme n'est pas dans le planificateur, il est **dans la fiche de chaque
marchand** : `merchants.payment_period`, en **jours**.

| Ce qui décide | Où | Effet |
|---|---|---|
| La cadence du marchand | `merchants.payment_period` | un relevé n'est émis que si le dernier date d'au moins ce nombre de jours |
| Le passage quotidien | `invoice:generate`, planifié **chaque jour à 13 h** | il **propose** ; c'est la cadence du marchand qui **dispose** |
| Le garde du jour | un relevé par marchand et par jour, au maximum | deux passages le même jour ne produisent qu'un document |

« Mensuel » n'est donc vrai que pour les marchands dont `payment_period` vaut 30.
Le dire autrement fait chercher une tâche mensuelle qui n'existe pas.

## La bonne nouvelle : un jour manqué n'est pas un relevé perdu

Un relevé agrège les colis livrés (et les retours) **dont `invoice_id` est
vide**. Un passage sauté ne perd donc rien : le suivant ramasse tout ce qui
n'a pas encore été facturé.

Ce qui change, ce n'est pas le contenu, c'est le **découpage** : après une
coupure de trois semaines, le marchand reçoit **un** relevé couvrant les trois
semaines, et non trois relevés hebdomadaires. Le garde « un par jour et par
marchand » l'impose, et aucune commande ne sait reconstituer après coup un
découpage qui n'a pas eu lieu.

Pour voir ce qui attend, avant de rattraper :

```bash
cd /var/www/beninlink/web
php artisan tinker --execute='
  foreach (\App\Models\Backend\GeneralSettings::orderBy("id")->get() as $s) {
      $n = \App\Models\Backend\Parcel::where("company_id", $s->id)
          ->whereNull("invoice_id")
          ->where(fn($q) => $q->where("status", \App\Enums\ParcelStatus::DELIVERED)
                              ->orWhere("partial_delivered", 1))
          ->count();
      printf("société %d (%s) : %d colis livrés non encore relevés%s", $s->id, $s->name, $n, PHP_EOL);
  }
'
```

## ⚠️ Le planificateur ne sert **que la société 1**

C'est le point le plus important de cette page, et il ne se voit nulle part :
sur une installation à plusieurs transporteurs, **les relevés des sociétés 2, 3,
… ne sont jamais générés par le cron**. Aucune erreur, aucune alerte : la tâche
passe, réussit, et ne fait rien pour eux.

**Le mécanisme.** Le socle résout la société courante par `settings()`, qui lit
le sous-domaine (`tenant()`) ou l'utilisateur connecté. Dans une tâche planifiée
il n'y a **ni l'un ni l'autre** : la fonction retombe alors sur `id = 1`. Or
`invoice:generate` boucle sur `Merchant::where('company_id', settings()->id)`.

Vérifié en console sur une base à deux sociétés :

```
sociétés en base : 2
settings()->id en console : 1
tenant() : NULL
```

Le code le sait, à un endroit : `InvoiceNumbering` prend son `company_id` en
paramètre, avec le commentaire « jamais via `settings()`, qui retombe sur la
société 1 hors requête ». Le garde-fou a été posé sur la **numérotation** ; la
**sélection des marchands**, elle, est restée sur `settings()`.

**Êtes-vous concerné ?** Une seule société → non, le cron suffit. Plusieurs
sociétés actives → oui, et la requête ci-dessus le montre : les colis non relevés
s'accumulent pour tout le monde sauf la société 1.

```sql
SELECT company_id, COUNT(*) AS releves, MAX(issued_on) AS dernier
FROM invoices GROUP BY company_id ORDER BY company_id;
```

Une société active qui ne compte aucun relevé, ou dont le dernier remonte à la
mise en service, est dans ce cas.

## Rattraper : trois voies, et une seule marche pour tout le monde

| Voie | Portée | Quand s'en servir |
|---|---|---|
| Cron `invoice:generate` (13 h) | **société 1 uniquement** | le cas normal d'une installation à un seul transporteur |
| Écran *Réglages → génération des relevés* | **la société de l'administrateur connecté** | le rattrapage sur une installation multi-transporteurs |
| Bouton par marchand (fiche marchand) | un marchand | un cas isolé, un doute sur une fiche |

La deuxième voie marche pour une raison précise : elle appelle la **même**
commande, mais **depuis une requête HTTP**. Là, `settings()` a un utilisateur
connecté et un sous-domaine à lire — elle résout donc la bonne société. C'est le
contournement, et il est fiable ; ce n'est pas un correctif.

En pratique, sur une installation à plusieurs transporteurs : se connecter à
l'administration **de chaque société**, sur son sous-domaine, et déclencher la
génération. Une fois par société, à la cadence de la plus courte
`payment_period`.

## Ce qu'un rattrapage tardif change dans les numéros

Rien de fâcheux, et c'est voulu. La numérotation est `PREFIXE-AAAA-NNNNNN`, une
séquence **par société et par exercice civil**, incrémentée sous verrou : deux
générations simultanées (cron et bouton) ne peuvent ni sauter ni partager un
numéro, et un échec rend le numéro réservé avec le rollback — **sans trou**.

En revanche, un relevé rattrapé porte la date **du jour où il est émis**, pas
celle de la période couverte. Un rattrapage en janvier pour des colis de décembre
prend donc un numéro de l'exercice **en cours**. Si l'exercice compte, la
question se pose à l'expert-comptable **avant** de rattraper, pas après.

## Après un rattrapage : trois vérifications

```bash
php artisan beninlink:ecarts-marchands        # le solde répond-il au relevé ?
php artisan beninlink:journal-syscohada       # les écritures s'équilibrent-elles ?
php artisan beninlink:colis-non-debites       # un colis facturé, jamais débité ?
```

Les deux premières se **lisent** ; seule `journal-syscohada` sort en erreur quand
elle trouve un déséquilibre (voir `infra/supervision/`). Un écart apparu juste
après un rattrapage n'est pas du bruit historique : c'est ce que le rattrapage
vient de révéler.

## Ce qu'il ne faut pas faire

- **Ne pas « refaire » un relevé existant.** Ses colis portent déjà son
  `invoice_id` : une seconde génération ne les reprendra pas, et produira un
  document vide ou partiel. Détacher des colis d'un relevé émis n'est pas une
  commande, c'est une décision comptable — un avoir se décide avec
  l'expert-comptable.
- **Ne pas toucher `invoice_sequences` à la main.** C'est la table qui garantit
  l'absence de trou dans la numérotation d'un exercice.
- **Ne pas compter sur le cron pour une société qui n'est pas la première**,
  tant que le défaut ci-dessus n'est pas corrigé.

## Le correctif proposé, pour mémoire

Faire boucler la génération **société par société**, en résolvant explicitement
le `company_id` au lieu de le lire dans `settings()` — exactement ce que
`InvoiceNumbering` fait déjà pour le préfixe et la séquence. Cela touche le
chemin de facturation : c'est une décision, pas une retouche, et elle mérite sa
propre livraison, avec un test qui vérifie qu'une société non-1 reçoit bien ses
relevés.
