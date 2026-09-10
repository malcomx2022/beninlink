# Reprise des relevés de règlement

> Quoi faire quand des relevés n'ont pas été émis : planificateur arrêté, ou
> incident. Écrit le 2026-09-10, **mis à jour le 2026-09-11** : le défaut
> multi-transporteurs décrit ici a été **corrigé** — la section le dit et
> explique quoi vérifier sur une installation qui a tourné avant.
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

## Le planificateur a longtemps ne servi **que la société 1** — corrigé

Jusqu'au 2026-09-11, sur une installation à plusieurs transporteurs, **les
relevés des sociétés 2, 3, … n'étaient jamais générés par le cron**. Aucune
erreur, aucune alerte : la tâche passait, réussissait, et ne faisait rien pour
eux.

**Le mécanisme.** Le socle résout la société courante par `settings()`, qui lit
le sous-domaine (`tenant()`) ou l'utilisateur connecté. Dans une tâche planifiée
il n'y a **ni l'un ni l'autre** : la fonction retombe alors sur `id = 1`. Or
`invoice:generate` bouclait sur `Merchant::where('company_id', settings()->id)`,
et la génération scopait tout le reste de la même façon.

C'est exactement la règle que le projet s'était déjà donnée pour les envois
(**F4**, `web/CLAUDE.md`) : *porter la société dans le job, parce que hors
requête `settings()` retombe sur la société 1*. Elle valait pour les SMS ; elle
ne valait pas encore pour les relevés.

**Ce qui a changé.** La commande lit désormais la liste des sociétés actives et
traite chacune à son tour ; la génération d'un relevé résout sa société **depuis
le marchand**, plus depuis la session. Un test le prouve : le marchand du jeu de
test appartient à la société 2, et il reçoit son relevé.

⚠️ **Sur une installation qui a tourné avant le correctif**, le retard est déjà
là : les colis non relevés se sont accumulés pour toutes les sociétés sauf la
première. Le rattrapage se fait en un passage (voir la section suivante) — et il
produira **un** relevé par marchand, couvrant toute la période.

```sql
SELECT company_id, COUNT(*) AS releves, MAX(issued_on) AS dernier
FROM invoices GROUP BY company_id ORDER BY company_id;
```

Une société active qui ne compte aucun relevé, ou dont le dernier remonte à la
mise en service, est dans ce cas.

## Rattraper : trois voies, et une seule marche pour tout le monde

| Voie | Portée | Quand s'en servir |
|---|---|---|
| Cron `invoice:generate` (13 h) | **toutes les sociétés actives** | le cas normal, depuis le correctif |
| `php artisan invoice:generate --societe=N` | une société | un rattrapage ciblé, ou un doute sur une société |
| Écran *Réglages → génération des relevés* | la société de l'administrateur connecté | le rattrapage depuis l'interface |
| Bouton par marchand (fiche marchand) | un marchand | un cas isolé, un doute sur une fiche |

L'écran passe désormais `--societe` explicitement : un administrateur ne
déclenche que la génération **de sa** société, jamais celle des autres
transporteurs. Le bouton par marchand relit le marchand par le dépôt scopé avant
de générer — sans ce contrôle, l'URL aurait suffi à émettre le relevé du marchand
d'un autre transporteur.

Pour rattraper tout un parc d'un coup, après un arrêt :

```bash
cd /var/www/beninlink/web
php artisan invoice:generate          # toutes les sociétés actives, une par une
```

La commande dit, par société, combien de marchands elle a examinés et combien de
relevés elle a émis.

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
- **Ne pas supposer qu'un rattrapage reconstitue le passé.** Il émet les relevés
  dus *aujourd'hui* : un seul document par marchand, couvrant toute la période
  non facturée.

## Le correctif, pour mémoire

Appliqué le 2026-09-11 (demande n° 75), en trois points :

1. `invoice:generate` lit la liste des **sociétés actives** et traite chacune à
   son tour ; la liste des marchands est filtrée par la société en cours, pas par
   la session. Une option `--societe=N` restreint la portée.
2. `InvoiceRepository::store()` résout la société **depuis le marchand** — colis
   retenus, numérotation, `company_id` du relevé et de ses lignes. Le scope reste
   explicite : ce n'est pas un relâchement de **D8**, c'est la même règle écrite
   sans dépendre d'une session qui n'existe pas.
3. Les deux chemins HTTP sont resserrés : l'écran global passe sa société, et le
   bouton par marchand relit le marchand par le dépôt scopé avant de générer.

Quatre tests couvrent l'ensemble, dont celui qui comptait : un marchand d'une
société **autre que la première** reçoit bien son relevé, avec le préfixe et la
séquence de sa société. Vérifié dans les deux sens — en remettant la ligne
fautive, le test repasse au rouge.
