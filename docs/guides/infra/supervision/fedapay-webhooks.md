# Superviser les webhooks FedaPay

> L'angle mort le plus cher du produit : le client paie chez l'opérateur, et le
> portefeuille ne bouge pas. Ce guide dit **comment s'en apercevoir** — la
> réparation, elle, est au § 5 de `infra/reprise/`.
> Écrit le 2026-09-16. Complète `infra/supervision/`, qui listait cette panne
> parmi celles « sans détecteur ».

## Pourquoi il n'y a rien à écouter

Le webhook est **le seul endroit qui crédite** (`.claude/rules/payments.md`) :
ni le retour de la WebView, ni `fedapay/status/{reference}` — qui ne lit que la
ligne locale — ne touchent un solde. Quand le webhook n'arrive pas, ou qu'il est
refusé, **rien n'échoue** : aucune requête n'est en erreur, aucune commande ne
sort en 1, aucune alerte ne part. L'application va parfaitement bien. C'est le
marchand qui finit par téléphoner.

Il n'y a donc rien à *écouter*. Il y a des états à **lire** — et ils sont en
base, pas dans le journal.

## Ce que le paiement laisse derrière lui

Une recharge écrit **deux** lignes, dans cet ordre :

| Table | Écrite quand | Ce qu'elle porte |
|---|---|---|
| `wallets` | à l'ouverture du paiement | la demande, en `PENDING` (1) ; le solde n'a pas bougé |
| `fedapay_transactions` | à l'ouverture, puis à chaque webhook | `pending` → `approved` \| `declined` \| `canceled`, et `last_event` |

Le webhook `transaction.approved` fait ensuite **deux choses, en deux
transactions séparées** :

1. il passe `fedapay_transactions.status` à `approved` sous verrou de ligne —
   c'est le garde-fou d'idempotence, et il commite ;
2. **puis** il appelle le service du socle qui crédite : `wallets.status`
   passe à `APPROVED` (2) et `merchants.wallet_balance` augmente.

**Un état sain fait toujours correspondre les deux.** Tout l'intérêt de la
supervision tient dans cette phrase : les cas malades sont ceux où les deux
lignes ne disent pas la même chose.

## ⚠️ Le cas que personne ne voit : approuvé sans être crédité

Entre le commit (1) et l'appel (2), il n'y a aucune transaction commune : c'est
délibéré — le crédit passe par le service du socle, qu'on ne duplique pas. Mais
cela veut dire qu'une interruption entre les deux (délai PHP dépassé, FPM
redémarré, base indisponible une seconde) laisse la transaction **`approved`** et
le portefeuille **`PENDING`**.

Et cet état-là ne se rattrape pas tout seul : **rejouer le webhook ne sert à
rien**. Le second passage voit la transaction déjà `approved`, répond
`already processed` et ressort **avant** d'atteindre le crédit. C'est le même
verrou qui protège du double crédit qui interdit ici le rattrapage.

La seule réparation est l'approbation à la main, dans *Demandes de recharge* :
la ligne y est toujours en attente, et le service ne crédite qu'un portefeuille
encore `PENDING`, sous verrou — donc une seule fois.

## Les trois lectures qui disent la vérité

À passer en MySQL sur la production. Aucune n'écrit quoi que ce soit.

**A. Recharges restées en attente.** Webhook jamais reçu, ou refusé.

```sql
SELECT t.company_id, t.reference, t.amount, t.created_at
FROM fedapay_transactions t
WHERE t.purpose = 'wallet_recharge'
  AND t.status  = 'pending'
  AND t.created_at < NOW() - INTERVAL 30 MINUTE
ORDER BY t.created_at;
```

Trente minutes : une validation USSD prend quelques minutes, et un client peut
abandonner devant sa page de paiement. En deçà, la liste ne contiendrait que des
paiements en cours. **Une ligne ici n'est pas encore un incident** — c'est peut-être
un client qui a renoncé. C'est le tableau de bord FedaPay qui tranche : si la
transaction y est *approuvée*, l'argent est parti et le solde ment.

**B. Approuvées chez FedaPay, portefeuille non crédité.** L'état décrit
ci-dessus. Toute ligne ici est un incident, sans exception.

```sql
SELECT t.company_id, t.reference, t.amount, t.approved_at, w.status AS statut_wallet
FROM fedapay_transactions t
LEFT JOIN wallets w ON w.id = t.wallet_id
WHERE t.purpose = 'wallet_recharge'
  AND t.status  = 'approved'
  AND (w.id IS NULL OR w.status <> 2)
ORDER BY t.approved_at;
```

**C. Abonnements approuvés.** Le paiement d'un plan SaaS n'a pas de ligne de
portefeuille à confronter : il faut rapprocher à la main du plan actif de la
société. Un `plan_id` ici qui ne correspond pas à `general_settings.plan_id`
signale une activation qui n'a pas eu lieu.

```sql
SELECT t.company_id, t.reference, t.plan_id, t.user_id, t.approved_at
FROM fedapay_transactions t
WHERE t.purpose = 'subscription' AND t.status = 'approved'
ORDER BY t.approved_at DESC LIMIT 20;
```

Pour brancher A et B sur une alerte, c'est la **sortie non vide** qui fait
l'incident, pas un code de retour — la distinction du guide de supervision tient
ici aussi :

```bash
SORTIE=$(mysql -N -e "SELECT reference FROM fedapay_transactions t
  LEFT JOIN wallets w ON w.id = t.wallet_id
  WHERE t.purpose='wallet_recharge' AND t.status='approved'
    AND (w.id IS NULL OR w.status <> 2)" beninlink)
[ -n "$SORTIE" ] && echo "$SORTIE" | mail -s "BeninLink : recharge approuvée non créditée" ops@exemple.bj
```

## ⚠️ Le journal ment sur l'argent

`FedaPay : crédit du wallet en échec` **ne veut pas dire que le solde n'a pas
bougé**. Le service crédite, commite, *puis* envoie le SMS — hors transaction,
pour qu'un envoi lent ne tienne pas le verrou. Si le SMS échoue, la méthode
retourne `false` alors que **l'argent est bien arrivé**, et le contrôleur écrit
cette ligne d'erreur.

La même ligne apparaît aussi quand quelqu'un a déjà approuvé la recharge à la
main : le portefeuille n'est plus `PENDING`, le service refuse d'agir une
seconde fois. C'est la sécurité qui parle.

**Le journal ne tranche pas ; la requête B tranche.** Une erreur au journal sans
ligne en B ne demande aucune action sur l'argent.

## Les huit lignes de journal, et ce qu'elles veulent dire

Toutes portent le même préfixe :

```bash
grep "FedaPay :" storage/logs/laravel-*.log
```

| Message | Niveau | Ce qu'il faut en faire |
|---|---|---|
| `signature de webhook invalide` | warning | **Urgent si répété** : mauvais secret → plus rien n'est crédité |
| `webhook hors tolérance d'horodatage` | warning | L'horloge du serveur a dérivé — voir ci-dessous |
| `webhook pour une transaction inconnue` | warning | Base restaurée plus ancienne que le paiement, ou webhook sandbox reçu en live |
| `montant payé différent du montant attendu` | warning | Rapprochement à faire à la main : on a crédité le montant **attendu** |
| `crédit du wallet en échec` | error | **Ne prouve rien sur l'argent** — confronter à la requête B |
| `activation de l'abonnement en échec` | error | Le plan n'a pas basculé : rapprocher par la requête C |
| `initialisation impossible` | error | Le paiement n'a jamais été ouvert — aucun argent en jeu |
| `lecture de transaction impossible` | warning | Rapprochement manuel empêché ; sans effet sur les soldes |

Le premier est le seul qui puisse rendre **toutes** les recharges muettes à la
fois. Il mérite d'être lu tous les jours, pas après coup.

## L'horloge du serveur peut tout rejeter

La signature porte un horodatage, et un écart de plus de
`FEDAPAY_WEBHOOK_TOLERANCE` secondes — **300 par défaut** — fait rejeter
l'événement. Une dérive NTP de six minutes suffit donc à refuser *tous* les
webhooks, sans qu'aucun paiement n'échoue côté opérateur.

```bash
timedatectl | grep -E 'System clock|NTP'   # « synchronized: yes » attendu
```

Élargir la tolérance serait le mauvais réflexe : elle est ce qui empêche le
rejeu d'un webhook intercepté. C'est l'horloge qu'on remet à l'heure.

## Le secret suit le compte qui encaisse

Un locataire peut encaisser sur **son propre** compte FedaPay. Ses webhooks sont
alors signés par le secret de *ce* compte : une clé posée sans son secret de
webhook ferait rejeter tous ses encaissements. L'écran *Réglages → Pay-out*
**refuse** cette combinaison, et un test le tient.

Le refus vit dans l'écran, pas dans la base : une clé écrite directement en SQL
ou par un *seeder* passerait à côté. Une lecture suffit à s'en assurer :

```sql
SELECT c.company_id
FROM settings c
WHERE c.`key` = 'fedapay_secret_key'
  AND NOT EXISTS (
        SELECT 1 FROM settings s
        WHERE s.`key` = 'fedapay_webhook_secret'
          AND s.company_id <=> c.company_id
      );
```

Toute ligne renvoyée est une société dont **aucun** paiement ne sera crédité.

`NOT EXISTS`, et non `NOT IN` : `settings.company_id` est **nullable**, et un
seul `NULL` dans la sous-requête ferait renvoyer `NOT IN` une liste vide — la
requête dirait « tout va bien » précisément parce qu'une donnée est incomplète.

## Après toute rotation de clé, deux gestes

1. Une **recharge réelle de petit montant**, menée jusqu'au bout, et le solde
   vérifié. Rien d'autre ne prouve la chaîne complète.
2. Un coup d'œil au **tableau de bord FedaPay**, onglet Webhooks : il liste les
   livraisons en échec, avec leur code de réponse. C'est la seule source qui
   voit ce que notre serveur n'a jamais reçu.

## Ce que ce guide ne sait pas faire

Il lit **nos** lignes. Un paiement dont la transaction n'a jamais été ouverte
chez nous — un lien réutilisé, un incident au moment de l'initiation — n'y
laisse aucune trace, et aucune requête ne peut l'inventer. Le rapprochement de
dernier recours reste le tableau de bord FedaPay confronté à la table
`fedapay_transactions`.

L'URL à y déclarer est unique pour toute l'installation, plateforme et
locataires confondus : `https://<votre-domaine>/fedapay/webhook`. Elle est
publique et hors du groupe locataire — c'est la **signature** qui fait foi, pas
l'hôte appelé.
