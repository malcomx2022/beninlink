# Revue du socle FedaPay et des workflows du projet

> Revue de lecture menée le 2026-09-05 sur `main` (`ffa518f`). **Aucun fichier de
> code n'a été modifié.** Les constats marqués **vérifié** ont été reproduits par
> des tests jetables exécutés sur la base de test, puis supprimés.
> Base de référence au moment de la revue : `vendor/bin/phpunit` — 127 tests,
> 824 assertions, au vert.
>
> **Mise à jour du 2026-09-05.** La revue elle-même ne corrigeait rien. **Onze**
> constats ont depuis été corrigés à la demande du porteur : **W1** et **F1**
> d'abord, puis **W2**, **W4** et **W5**, ensuite **F2** et **F3**, puis **W3**
> et **F4**, enfin **F5** et **F6**. Il ne reste plus de constat ouvert ; seules
> demeurent les décisions listées au §12. Chaque section porte son correctif et
> les tests qui le verrouillent. Suite après correction : **160 tests,
> 916 assertions, au vert**, contre 127 au moment de la revue.
>
> Deux défauts plus lourds que ce que la revue avait établi sont apparus en
> corrigeant, et sont documentés à leur place : la liste des relevés de l'app
> **échouait en 500** pour tout relevé existant (§10), et le portefeuille
> **n'était jamais débité** pour un colis créé depuis l'app (§10). Un troisième
> est apparu en ouvrant l'écran de réglages : `Setting` ne déclarait pas
> `company_id` assignable, donc **tout réglage de passerelle enregistré depuis
> l'administration partait avec un locataire nul** et devenait invisible (§4).

## Sommaire des constats classés

| # | Constat | Gravité | Vérifié |
|---|---|---|---|
| **W1** | Un marchand se déclare lui-même « Livré » et entre au relevé de règlement | **Critique** | ✅ **corrigé** |
| **F1** | Double crédit d'une recharge FedaPay par l'écran d'approbation admin | **Grave** | ✅ **corrigé** |
| **W2** | La position du livreur écrase celle de toutes ses courses passées | Grave | ✅ **corrigé** |
| **F2** | FedaPay n'a aucune surface de configuration côté administration | Structurel | ✅ **corrigé** |
| **F3** | La clé FedaPay par locataire est une branche morte | Structurel | ✅ **corrigé** |
| **W3** | Le contrôle douanier est absent de la création côté back-office | Moyen | ✅ **corrigé** |
| **F4** | Le SMS de confirmation part sous l'identité de la société 1 | Moyen | ✅ **corrigé** |
| **W4** | La liste des relevés de l'API échoue en 500, et ne filtrait que les payés | **Grave** | ✅ **corrigé** |
| **W5** | Le portefeuille n'est jamais débité depuis l'app, et l'échec est muet | **Grave** | ✅ **corrigé** |
| — | Onze constats mineurs, voir §5 et §7 | Faible | — |

---

# Partie I — Le socle FedaPay

## 1. Ce que le socle fait bien

| Garde-fou | Où | Verdict |
|---|---|---|
| Webhook signé = seule source de vérité | `FedaPayController::webhook()` | Respecté. Ni `callback()` ni `status()` ne créditent. |
| Signature HMAC-SHA256, comparaison en temps constant | `FedaPayGateway::verifySignature()` | `hash_equals`, tolérance d'horodatage, refus si le secret manque. 8 tests. |
| Idempotence du rejeu de webhook | `FedaPayController::approve()` | **Vérifié** : deux webhooks identiques donnent un seul crédit (`already processed`). |
| Montant crédité = montant attendu | `approve()` | Le montant annoncé par l'événement n'est jamais utilisé ; un écart est journalisé. |
| Isolation locataire du webhook | `fedapay_transactions.company_id` | Le locataire vient de la transaction, pas de `settings()`. **Vérifié** sur une société autre que la première. |
| Clés jamais exposées aux apps | `mobile/src/api/fedapay.ts` | L'app ouvre l'`payment_url` et interroge `fedapay/status`. |
| Sandbox par défaut | `config/fedapay.php` | Toute valeur inconnue retombe sur `sandbox`. |
| Portée des jetons | `routes/api.php` | `fedapay/initiate` et `fedapay/status` sous `userType:merchant`. |

L'architecture du module est saine. Les constats qui suivent portent sur ce qui
l'entoure : l'écran d'approbation du socle, l'absence de réglages, et une branche
de code jamais atteinte.

## 2. F1 — double crédit par l'écran d'approbation de l'administrateur (grave) — ✅ corrigé

**Vérifié : 5 000 FCFA payés, 10 000 FCFA crédités.**

Une recharge FedaPay crée une ligne `wallets` en attente
(`FedaPayController::startWalletRecharge()`). Cette ligne apparaît dans l'écran
**Demandes de recharge** de l'administration, avec un bouton « Approuver ».

L'idempotence vit sur `fedapay_transactions`. L'approbation manuelle passe par
`WalletRepository::approved()`, qui ne consulte jamais cette table et n'a **aucun
garde-fou sur le statut de la ligne** : elle incrémente le solde et met la ligne
à `APPROVED`, quel que soit son état de départ.

Les deux ordres ont été reproduits :

| Enchaînement | Payé | Crédité |
|---|---|---|
| L'administrateur approuve, puis le webhook arrive | 5 000 | 10 000 |
| Le webhook crédite, puis l'administrateur clique | 5 000 | 10 000 |

Ce n'est pas un scénario de laboratoire. Dans la liste, une recharge FedaPay est
**indiscernable d'une recharge manuelle** :

- la colonne affichée est « Moyen de paiement » = `Hors ligne`, car
  `startWalletRecharge()` pose `WalletPaymentMethod::OFFLINE` ;
- la colonne `source`, seule à porter la chaîne `FedaPay`, **n'est pas affichée** ;
- approuver les recharges en attente est précisément le travail quotidien de
  l'administrateur pour le flux manuel `recharge-add`.

Conséquences secondaires du même chaînon manquant : si le client n'achève jamais
son paiement, l'administrateur qui approuve crédite de l'argent jamais reçu ; et
un `transaction.declined` postérieur ne défait rien, car `close()` ne touche
qu'une ligne restée `PENDING`.

Le second ordre déborde d'ailleurs FedaPay : `approved()` recrédite **toute**
ligne déjà approuvée qu'on lui repasse. C'est un défaut du socle We Courier que
l'ouverture de FedaPay rend atteignable en exploitation normale.

### Correctif appliqué

`WalletRepository::approved()` porte désormais les deux garde-fous que le socle
n'avait pas, et que `FedaPayController::approve()` posait déjà de son côté sur la
transaction FedaPay :

- **seule une ligne `PENDING` est approuvée** ; un second appel ressort sans rien
  écrire, quel que soit le chemin ;
- **verrou de ligne** dans une transaction, sans quoi deux appels simultanés
  liraient tous deux « en attente ». Le crédit du solde et le passage à
  `APPROVED` sont désormais atomiques, ce qui referme aussi le « ni
  transactionnelle » relevé par la cartographie.

Le SMS part hors transaction : un envoi lent ne doit pas tenir le verrou, et son
échec ne doit pas défaire un crédit acquis.

`tests/Feature/WalletApprovalIdempotencyTest.php` (7 tests) verrouille les trois
enchaînements — approbation puis webhook, webhook puis approbation, double-clic —
plus le rejeu du webhook, le refus d'une demande rejetée, et le chemin nominal.

Restent ouvertes, et souhaitables : marquer ou masquer les lignes
`source = 'FedaPay'` dans l'écran d'approbation, et afficher la colonne `source`
pour que l'administrateur cesse de voir une recharge Mobile Money comme une
recharge « Hors ligne ».

## 3. F2 — FedaPay n'a aucune surface de configuration côté administration — ✅ corrigé

Le socle expose deux écrans de configuration de passerelles, l'un pour
l'administrateur (`Réglages → Pay-out`), l'autre pour le marchand
(`Réglages → Paiement en ligne`). Tous deux couvrent PayPal, Stripe, SSLCommerz,
bKash, Skrill, Aamarpay et RazorPay. **Aucun des deux ne propose FedaPay.**

- `App\Enums\PayoutSetup` n'a pas de constante `FEDAPAY`. Le mécanisme S21
  (`gatewayEnabled()`) ne s'applique donc pas : on ne peut pas non plus la
  désactiver par configuration.
- Aucune permission, aucune entrée de menu, aucune commande Artisan.
- L'unique surface de réglage est `web/.env` sur le serveur.

Un administrateur ne peut donc ni savoir si FedaPay tourne en `sandbox` ou en
`live`, ni voir si les clés sont présentes, ni couper la passerelle en cas
d'incident. Pour une mise en exploitation, c'est le manque le plus structurant
après F1.

### Correctif appliqué

`PayoutSetup::FEDAPAY` existe désormais, ce qui donne à la passerelle sa place
dans le socle : une carte sur **Réglages → Pay-out**, sous les permissions
`payout_setup_settings_read` / `_update` déjà en place, et la possibilité d'être
coupée par `config/payments.php` comme les autres.

La carte porte quatre choses :

- l'**environnement** en clair, bac à sable ou production, et le **compte**
  d'encaissement, le sien ou celui de la plateforme ;
- la **clé secrète** et le **secret de signature des webhooks**, en champs
  masqués : ils ne sont jamais réaffichés, et un champ laissé vide veut dire
  « ne pas changer », jamais « effacer » ;
- un **interrupteur** qui retire le paiement Mobile Money aux marchands sans
  toucher aux clés. C'est le levier qui manquait en cas d'incident.

Le statut est respecté partout où la passerelle s'ouvre : bouton de recharge du
panneau marchand, écran d'abonnement, et les deux initiations côté serveur.
Tant qu'aucun réglage n'a été enregistré, le comportement d'avant l'écran est
conservé — utilisable dès que les clés répondent ; seul un arrêt explicite coupe.

### Un défaut du socle découvert en ouvrant l'écran

`Setting` déclarait `protected $fillable = ['key','value']` : **`company_id` en
était absent**, alors que tout le socle écrit ces lignes par assignation en masse
(`SettingSeeder`, `PayoutSetupRepository`). Eloquent le laissait donc tomber en
silence. Chaque réglage de passerelle enregistré depuis l'administration partait
avec un locataire nul, devenait invisible à `scopeCompanywise()` et à
`globalSettings()`, et l'enregistrement suivant créait une ligne orpheline de
plus. `SmsSetting`, lui, le déclarait correctement.

La liste est corrigée. **Les lignes déjà écrites avec un locataire nul ne sont
pas reprises** : les rattacher ferait réapparaître d'anciens réglages, statuts de
passerelle compris, ce qui n'est pas une décision à prendre sans le porteur.

## 4. F3 — la clé FedaPay par locataire est une branche morte — ✅ corrigé

`FedaPayGateway::credentialsFor()` lit `settings.fedapay_secret_key` par
`company_id`, avec repli sur la clé plateforme du `.env`. L'intention est écrite
dans le code : « un locataire peut brancher son propre compte FedaPay pour son
encaissement marchand ».

Or `fedapay_secret_key` n'apparaît **nulle part ailleurs** dans le dépôt : ni
écran, ni seeder, ni migration ne l'écrit jamais. La branche est inatteignable.

1. Toutes les recharges de tous les locataires sont encaissées sur **le compte
   FedaPay de la plateforme**, jamais sur celui du transporteur. Pour un SaaS
   multi-locataire, c'est une décision métier majeure, prise ici par omission.
2. `WalletController::recharge()` commente « bouton affiché seulement si la
   société a ses clés ». C'est inexact : `isConfigured()` retombe sur la clé
   plateforme, donc le bouton apparaît chez **tous** les locataires dès que le
   `.env` est renseigné.

À trancher : assumer l'encaissement centralisé et retirer la branche morte, ou
ouvrir un écran de saisie par locataire.

## 5. Constats FedaPay mineurs

| # | Constat | Emplacement |
|---|---|---|
| F4 | ✅ **corrigé.** Dans le webhook, `settings()` renvoyait la société 1 : le SMS de confirmation portait le nom commercial et la devise d'un autre locataire et, `smsSettings()` retombant sur `company_id = 1`, partait avec les identifiants d'opérateur SMS de cette autre société, facturés à elle. Voir §5 bis. | `WalletRepository::approved()` |
| F5 | ✅ **corrigé.** Le chemin de crédit du portefeuille par webhook n'avait aucun test, alors que `.claude/rules/payments.md` l'exige. Le crédit et son idempotence ont été couverts en fermant F1 (`WalletApprovalIdempotencyTest`, 7 tests) ; les issues restantes le sont désormais par `FedaPayWalletOutcomeTest` (7 tests) : refus et annulation qui ferment la demande en attente, refus tardif qui ne défait pas un crédit acquis, transaction inconnue, événement étranger, montant annoncé qui ne fait pas autorité, et signature invalide qui n'atteint jamais le crédit. | `tests/Feature/FedaPayWalletOutcomeTest.php` |
| F6 | ✅ **corrigé.** `IsolationCoverageTest` déclarait `GET fedapay/status/{reference}` couvert par `FedaPayWebhookTest`, qui ne touche jamais cette route : ni base migrée, ni appel HTTP. Voir §5 ter. | `IsolationCoverageTest.php` |
| F7 | `FedaPayGateway::retrieve()` n'est appelé nulle part. Écrit « pour un rapprochement manuel », sans écran ni commande de rapprochement. | `FedaPayGateway` |
| F8 | Aucun écran n'expose `fedapay_transactions`. Une transaction refusée, en attente, ou dont le montant diffère, n'est visible que dans les journaux. | — |
| F9 | Les variables `FEDAPAY_*` sont documentées dans `docs/guides/infra/.env.example` mais absentes de `web/.env.example`, le fichier qu'un développeur copie. | `web/.env.example` |
| F10 | Le guide d'intégration porte encore « l'abonnement SaaS n'est pas encore câblé » : il a été livré depuis. | `docs/guides/fedapay-module/` |
| F11 | `SaasMetrics::subscriptionCashBetween()` ne compte que les encaissements FedaPay. Un locataire qui paie par Stripe n'apparaît pas dans `cash_collected`, alors que `bookings` le compte. L'écart est intentionnel et commenté, mais doit être affiché avec le chiffre. | `SaasMetrics.php` |

### 5 ter. F5 et F6 — les correctifs appliqués

**F5.** Le crédit et son idempotence ont été couverts en fermant F1. Ce qui
restait sans preuve sur le même chemin l'est maintenant : le refus et
l'annulation ferment la demande en attente, un refus tardif ne défait jamais un
crédit acquis — cela relèverait d'un remboursement décidé à la main —, une
transaction inconnue est acquittée sans rien créditer, un montant annoncé
différent ne fait pas autorité sur le solde, et une signature invalide n'atteint
jamais la logique de crédit.

**F6.** La fausse déclaration est remplacée par une preuve réelle : un marchand
qui lit la référence de paiement d'un voisin reçoit un 404, et lit bien la
sienne. La référence expose le montant et le solde, donc renseignerait un
concurrent sur le chiffre d'affaires.

Surtout, **le filet lui-même avait un trou** : il vérifiait que la classe
déclarée existe, jamais qu'elle puisse prouver quoi que ce soit. Une preuve
d'isolation demande d'atteindre la ressource d'un autre compte, donc de l'avoir
écrite ; un test sans base migrée ne peut pas la produire. `IsolationCoverageTest`
le vérifie désormais pour chaque entrée de sa carte. Vérifié : en remettant
l'ancienne déclaration, le nouveau contrôle la refuse en nommant la route et le
test fautif.

### 5 bis. F4 — le correctif appliqué

Le message est désormais bâti avec le nom et la devise de la société **du
portefeuille**, jamais avec ceux de `settings()`.

`SmsService` reçoit un contexte de société explicite, `forCompany()`, qui rend
une **copie** : le service est résolu depuis le conteneur et partagé, on ne doit
pas lui coller un locataire de façon durable. Toutes ses lectures de réglages
passent par ce contexte, avec repli sur le comportement du socle quand aucune
société n'est précisée — en requête authentifiée, l'aide du socle est déjà
scopée par la société connectée, donc rien ne change là où rien n'était cassé.

`tests/Feature/WalletSmsTenantTest.php` (4 tests) : la marque et la devise du
message, l'opérateur SMS effectivement interrogé, le repli conservé sans société
précisée, et le fait que `forCompany()` ne teinte jamais l'instance partagée.
Vérifié : le test échoue sans le correctif.

---

# Partie II — Les workflows du projet

## 6. Cartographie

Neuf workflows ont été relevés de bout en bout. Le détail par étape, avec le
fichier et la ligne de chaque point d'entrée, figure dans l'inventaire de travail
qui a servi à cette revue ; le tableau ci-dessous en donne l'état de santé.

| Workflow | Web | App marchand | App livreur | Tests |
|---|---|---|---|---|
| Colis : création et devis | oui | oui | — | bons (devis, barème, TVA, douane) |
| Colis : ramassage, entrepôt, transferts | oui | — | — | **aucun** |
| Colis : assignation livreur | oui | — | lecture | **aucun** |
| Colis : livraison | oui | — | oui | preuve seulement |
| Colis : livraison partielle | oui | — | oui | **aucun** |
| Colis : retours (8 étapes) | oui | — | dépôt | **aucun** |
| Inscription et connexion | oui | oui | oui | portée des jetons seulement |
| Portefeuille : recharge FedaPay | oui | oui | — | initiation et signature |
| Portefeuille : recharge manuelle | oui | — | — | **aucun** |
| Facturation et relevés | oui | oui | — | bons (relevé, SYSCOHADA, isolation) |
| Abonnement SaaS | oui | — | — | FedaPay oui, Stripe **aucun** |
| Alertes douanières | oui | oui | — | bons |
| Notifications : fil marchand | — | oui | — | bons |
| Notifications : FCM et SMS | oui | — | — | durcissement seulement |
| Livreur : courses et gains | oui | — | oui | isolation et preuve |
| Reversements et salaires | oui | — | — | **aucun** |
| Fraudes et support | oui | **non** | — | isolation seulement |

## 7. W1 — un marchand peut se déclarer livré et entrer au relevé (critique) — ✅ corrigé

**Vérifié de bout en bout.** Avec son seul jeton, un marchand appelle
`GET /api/v10/parcel/{id}/status/9` sur un colis jamais ramassé.

```
le marchand pose lui-même « Livré » : HTTP 200, statut = 9, parcel_events = 0
relevé généré : CO-2026-000001, encaissé = 50 000, à payer au marchand = 48 230
colis rattaché au relevé : invoice_id = 1
```

`MerchantParcelRepository::statusUpdate()` vérifie bien que le colis appartient
au marchand, correctif S7 appliqué, puis écrit `$parcel->status = $status_id`
**sans aucun contrôle de transition**. Ni `parcel_events`, ni écriture comptable,
ni SMS. Le colis passe de « En attente » à « Livré » en une requête.

`InvoiceRepository::store()` sélectionne ensuite les colis `DELIVERED` sans
`invoice_id` et somme leur `cash_collection` et leur `current_payable` dans un
relevé de règlement dû au marchand. De l'argent que le transporteur n'a jamais
encaissé.

Une valeur arbitraire est acceptée de la même façon : `.../status/999` renvoie
200 et écrit `999` en base.

Le back-office, lui, passe par des méthodes dédiées qui posent les événements et
les écritures. La divergence est entière entre les deux chemins.

### Correctif appliqué

`MerchantParcelRepository::statusUpdate()` n'écrit plus que les statuts inscrits
dans une liste blanche explicite, `MERCHANT_ALLOWED_STATUSES`. Cette liste est
**vide**, et c'est le constat, pas un oubli : dans le socle, aucune étape du
cycle de vie n'appartient au marchand. Chaque transition est posée par une
méthode dédiée du back-office ou du livreur, qui écrit l'événement et les
écritures comptables qui vont avec. Le seul geste légitime du marchand reste de
supprimer un colis encore « En attente », ce que `destroy()` autorise déjà en
refusant au-delà par un 422.

Y ajouter une transition un jour ne sera pas qu'une ligne : il faudra écrire
l'événement et les écritures correspondants.

Le contrôleur d'API distingue maintenant les deux cas, sur le modèle de
`destroy()` : **404** si le colis n'est pas à ce marchand, on ne révèle pas son
existence ; **422** si la transition ne lui appartient pas. Le panneau marchand
web n'annonce plus un succès quand rien n'a été écrit.

`tests/Feature/MerchantParcelStatusGuardTest.php` (5 tests) couvre le refus de
« Livré », le refus d'une valeur hors énumération, le 404 sur le colis d'autrui,
le fait que la liste blanche est le seul levier, et surtout que le colis
n'atteint plus le relevé de règlement.

## 8. W2 — la position du livreur écrase l'historique de ses courses — ✅ corrigé

`DeliverymanController::parcelLocationUpdate()` écrit la position reçue sur
**tous** les `parcel_events` portant l'identifiant du livreur, sans filtrer sur
les courses en cours :

```php
$parcelEvents = ParcelEvent::where('delivery_man_id', $user)->get();
foreach ($parcelEvents as $parcelEvent) { $parcelEvent->delivery_lat = ...; }
```

Le correctif S7 a bien fermé la faille d'usurpation, `deliveryID` de la requête
est ignoré. Mais la portée reste entière : chaque partage de position réécrit les
coordonnées enregistrées de **toutes les livraisons passées** de ce livreur. La
preuve géographique d'une livraison ancienne est donc détruite à chaque appui sur
« Partager ma position », et l'app livreur appelle cette route silencieusement
après chaque déclaration de livraison.

### Correctif appliqué

L'écriture est bornée aux colis dont le **statut courant** est encore une course
en main : livreur affecté, livraison reprogrammée, retour au coursier. Ce sont
les onglets « en cours » et « Retours » de son écran ; livré et livraison
partielle en sont exclus, la course y est close. Le correctif S7 tient toujours,
`deliveryID` de la requête reste ignoré.

`tests/Feature/DeliverymanLocationScopeTest.php` (4 tests) : la course en cours
reçoit la position, la livraison close et la livraison partielle gardent la leur,
un retour en main la reçoit encore, et la course d'un collègue reste intacte.

## 9. W3 — le contrôle douanier est absent de la création côté back-office — ✅ corrigé

`app/Http/Requests/MerchantPanel/Parcel/StoreRequest.php` porte
`destination_country`, `customs_category` et la règle `CustomsAllowed` : un colis
bloquant est refusé côté API et côté panneau marchand.

`app/Http/Requests/Parcel/StoreRequest.php`, utilisé par le back-office, ne porte
**aucun de ces champs**. Un agent peut donc créer depuis l'administration un colis
que la règle douanière interdit. L'observer émet bien l'alerte a posteriori, mais
le blocage, lui, ne s'applique pas.

Le bypass est réel et pas seulement théorique : `ParcelRepository` écrit
`destination_country` et `customs_category` depuis la requête dans ses trois
points d'entrée — `store()`, `duplicateStore()` et `update()` — sans jamais les
valider. Les formulaires du back-office n'affichent pas ces champs, mais la route
les accepte.

### Correctif appliqué

Les deux règles sont ajoutées à la requête de l'administration, à l'identique de
celle du marchand. C'est exactement ce que `CustomsAllowed` prescrit dans son
propre commentaire : elle vit dans `StoreRequest` « parce que c'est le seul point
commun aux TROIS chemins de création ». Le troisième ne la portait pas.

Les trois points d'entrée du back-office partagent la même classe de requête :
une seule modification les couvre tous.

`tests/Feature/BackofficeCustomsGuardTest.php` (5 tests) : le refus d'un export
bloqué, la catégorie rendue obligatoire sur un export — sans quoi l'omettre
contournerait le blocage —, le colis domestique laissé intact, l'export autorisé
qui passe toujours, et l'accord entre le chemin administration et le chemin
marchand. Vérifié : trois de ces cinq tests échouent sans le correctif.

## 10. W4 et W5 — deux défauts silencieux, plus lourds qu'annoncé — ✅ corrigés

**W4 — la liste des relevés de l'API ne rend que les relevés payés.**
`InvoiceRepository::invoiceLists()` écrit
`where('status', InvoiceStatus::PAID, InvoiceStatus::PROCESSING)`. Le troisième
argument de `where()` est la valeur, le deuxième l'opérateur : Laravel rétablit
silencieusement `where('status', '=', PAID)`. Vérifié sur la requête produite :

```
select * from "invoices" where "company_id" = ? and "merchant_id" = ? and "status" = ?
liaisons = [2, 1, 3]
```

Le filtre `PROCESSING` est perdu, et les relevés `UNPAID` n'ont jamais été prévus.

**Et le défaut est plus lourd que cela.** En corrigeant le filtre, la liste
répondait toujours 500. `InvoiceResource` compose le montant à partir de
`$this->PartialParcelsReturnMerchant` et `$this->parcels_return_merchant_fees`,
**deux propriétés qui n'existent nulle part** : ni accesseur, ni relation, ni
colonne. Elles valent donc toujours `null`, et `->sum()` sur `null` est fatal.
L'écran « Factures » de l'app marchand ne pouvait fonctionner que tant qu'il
était **vide** : dès le premier relevé émis, il tombait en erreur.

### Correctif appliqué

Deux choses, l'une derrière l'autre.

Le montant servi est désormais le net que **porte le relevé**,
`invoices.current_payable`. `InvoiceRepository::store()` le calcule une fois à
l'émission, colis livrés moins frais de retour, et c'est lui que le PDF du
chantier 4 publie sous « net constaté ». Même chiffre dans la liste, dans le PDF
et dans le journal SYSCOHADA, et une valeur qu'une mutation ultérieure d'un colis
ne fait plus bouger, ce qu'on attend d'un relevé de règlement.

La liste elle-même délègue à `get()`, celle que le panneau marchand web sert
déjà : tous statuts, du plus récent au plus ancien. Une seule définition, aucune
divergence entre les deux surfaces.

`tests/Feature/InvoiceListTest.php` (5 tests) : les trois statuts présents, le
plus récent en tête, le cloisonnement par marchand, la non-régression du 500, et
l'égalité stricte entre ce que voient l'app et le panneau web.

**W5 — le portefeuille n'était jamais débité depuis l'app, et l'échec était muet.**
Dans `MerchantParcelRepository::store()`, le débit du portefeuille à la création
d'un colis est enveloppé dans un `try { ... } catch (\Throwable $th) { }` **vide**.

Le silence cachait plus qu'une panne. Le bloc lisait `$request->merchant_id`,
alors que le colis, lui, est rattaché à `$request->merchant_id ?? $merchant_id`.
Créée depuis l'app, la requête ne porte pas ce champ — le marchand vient du
compte authentifié. `Merchant::find(null)` rendait donc `null`, la condition
n'était jamais vraie (lire une propriété sur `null` est un avertissement, pas une
exception), et **le portefeuille n'était jamais débité** : tout colis créé depuis
l'app marchand échappait à la facturation. Le `catch` vide garantissait que
personne ne le verrait.

### Correctif appliqué

Le débit charge le marchand **du colis**, `$parcel->merchant_id`, la même
résolution que le colis lui-même. Le chemin web, où la requête porte le champ,
est inchangé.

Le `catch` journalise désormais en erreur, avec le colis, son numéro de suivi, le
marchand et le montant : de quoi rapprocher et régulariser. Le colis reste créé,
comme avant ; rendre le débit atomique avec la création change le comportement et
relève d'une décision à part.

`tests/Feature/ParcelWalletDebitTraceTest.php` (2 tests) : le chemin nominal
débite bien et ne journalise aucune erreur ; une panne du service de portefeuille
laisse le colis en place et une trace exploitable.

## 11. Autres divergences et trous relevés

**Étapes sans aucun test.** Tout le cycle logistique intermédiaire (ramassage,
entrepôt, transferts entre hubs, assignation du livreur et leurs douze
annulations), toute la chaîne de retour, la livraison partielle et son recalcul de
COD et de TVA, les six écritures comptables de `parcelDelivered`
(`deliveryman_statements`, `merchant_statements`, `courier_statements`,
`vat_statements`, et les deux soldes), le portefeuille manuel, les retraits
marchands côté administration dont `processed` qui déplace de l'argent réel, les
reversements de caisse du livreur vers le hub, l'inscription marchand et la règle
IFU/RCCM/CNSS, le chemin Stripe de l'abonnement, et le back-office douanier.

**Étapes sans écran mobile.** Côté marchand : édition et suppression d'un colis,
demande de ramassage, support, fraude, relevés de compte, recharge manuelle,
actualités, abonnement FCM. Côté livreur : journaux de paiement, mise à jour du
profil, abonnement FCM. Aucune app ne couvre les reversements, les salaires, le
hub ni la comptabilité.

**Aucune app n'appelle `fcm-subscribe`.** Le module de notifications push est
complet côté serveur, durci en S11, mais aucun terminal ne s'y abonne : les push
ne partent vers personne.

**Le sens de la vérification OpenAPI est unique.** `OpenApiSpecTest` vérifie que
les endpoints déclarés par les apps existent dans la spec, jamais l'inverse.
Vingt-neuf chemins de la spec ne sont consommés par aucune app, et dix-huit
endpoints déclarés dans `endpoints.ts` ne sont appelés par aucun écran.

**SMS non émis depuis les apps.** Les envois sont conditionnés par des cases
`send_sms_merchant`, `send_sms_customer` et `send_sms_pickuman` du formulaire
back-office. Une livraison déclarée depuis l'app livreur n'envoie donc aucun SMS,
contrairement à la même action faite depuis le web.

**Preuve de livraison contournable.** L'app livreur envoie photo et signature sur
`deliveryman/parcel/delivered/{id}`, mais `parcel-status-update` accepte la même
transition sans preuve, et sans valider `cash_collection` pour un partiel. C'est
l'app qui choisit ; le backend n'exige rien.

**Trois statuts déclarés ne sont jamais posés** : `RETURN_WAREHOUSE` (11),
`ASSIGN_MERCHANT` (12), `RETURNED_MERCHANT` (13).

**Code mort** : `DeliveryManParcelController::deliveryIncomeExpense()` calcule des
sommes, les jette, puis appelle `parcelDelivered` ; la méthode n'est routée nulle
part.

**Fragilité latente** : `Invoice::getParcelsAttribute()` renvoie un tableau `[]`
au lieu d'une collection quand `parcels_id` est nul, et `InvoiceResource` appelle
`->sum()` dessus. Un relevé sans `parcels_id` fait échouer toute la liste en 500.
`InvoiceRepository::store()` renseigne toujours ce champ, donc le cas ne se
produit pas en exploitation normale.

---

## 12. Ordre de traitement suggéré

Cet ordre suit le risque financier, pas la difficulté.

1. ~~**W1**~~ ✅ **corrigé le 2026-09-05** — liste blanche vide, 404 / 422
   distingués, 5 tests.
2. ~~**F1**~~ ✅ **corrigé le 2026-09-05** — approbation réservée aux lignes
   `PENDING`, sous verrou de ligne, 7 tests.
3. ~~**W2**~~ ✅ **corrigé le 2026-09-05** — écriture bornée aux courses en main,
   4 tests.
4. ~~**W4**~~ et ~~**W5**~~ ✅ **corrigés le 2026-09-05** — la liste des relevés
   ne tombe plus en erreur et rend tous les statuts (5 tests) ; le portefeuille
   est débité et l'échec laisse une trace (2 tests).
5. ~~**F2**~~ et ~~**F3**~~ ✅ **corrigés le 2026-09-05** — écran de réglages,
   compte d'encaissement par locataire, secret de webhook qui suit le compte,
   11 tests.
6. ~~**W3**~~ et ~~**F4**~~ ✅ **corrigés le 2026-09-05** — le back-office porte
   la même règle douanière que les deux autres chemins (5 tests) ; le SMS part au
   nom et par l'opérateur de la bonne société (4 tests).
7. ~~**F5**~~ et ~~**F6**~~ ✅ **corrigés le 2026-09-05** — les issues du webhook
   portefeuille sont couvertes (7 tests), la fausse déclaration de couverture est
   remplacée par une preuve réelle, et le filet vérifie maintenant que chaque
   test déclaré peut réellement atteindre une route.
## 13. Les trois décisions, tranchées le 2026-09-05

### D-A — les lignes de `settings` sans société : **on ne rattache pas**

Deux écritures perdaient `company_id`, et elles sont aujourd'hui
**indiscernables** : `SettingSeeder`, qui visait la société 1, et
`PayoutSetupRepository::update()`, qui visait la société **connectée** — donc
n'importe quel locataire, et plusieurs d'entre eux, puisque la recherche
préalable ne trouvait jamais rien et créait une ligne de plus à chaque
enregistrement.

Les attribuer toutes à la société 1 remettrait donc la clé secrète Stripe ou
PayPal d'un locataire entre les mains d'un autre : exactement la fuite
inter-locataires que le constat S6 avait fermée. Et cela rallumerait des
`*_status` sans qu'on l'ait demandé.

**Décision : constater, jamais deviner.** `php artisan beninlink:reglages-orphelins`
liste ces lignes par clé, sans afficher aucun secret, explique pourquoi elles ne
sont pas rattachables, et ne supprime qu'avec `--purge` explicite — refusé en
production sans `--force`. La remise en état passe par l'écran de réglages,
société par société : la seule voie qui attribue chaque clé à son propriétaire
réel.

### D-B — le débit du portefeuille : **atomique avec la création**

Le débit vivait dans un `try/catch` à part. Un échec laissait le colis créé et le
marchand non facturé : le transporteur livrait gratuitement, sans que personne ne
le sache. La trace ajoutée en fermant W5 rendait l'écart visible, pas moins réel.

**Décision : lier les deux écritures.** Un colis qu'on ne sait pas facturer ne
doit pas partir. Le prix est une disponibilité — si le portefeuille est
indisponible, le marchand réessaie —, et il est assumé.

L'annulation est sûre, ce qui rendait cette décision possible : tout ce que la
création déclenche s'écrit en base, l'alerte douanière comme la notification du
fil marchand, qui n'a que le canal `database`. Aucun SMS ni push n'est émis à la
création, donc un `rollback` ne laisse rien derrière lui — vérifié avant de
trancher.

`ParcelWalletDebitAtomicityTest` : le chemin nominal débite, un échec annule tout,
et rien de ce que la création déclenche ne survit à l'annulation.

### D-C — les deux voisins non scopés : **fermés**

`ParcelRepository::statusUpdate()` faisait un `Parcel::find($id)` nu. Un
administrateur de la société A pouvait, en forgeant l'identifiant, changer le
statut d'un colis de la société B, et par là le faire entrer ou non dans un
relevé de règlement qui ne le regarde pas. La permission protégeait l'accès à la
route, jamais la portée. La lecture est désormais `companywise()`, et l'écran
n'annonce plus un succès quand rien n'a été écrit.

Les trois actions d'administration sur un portefeuille — approuver, rejeter,
supprimer — adressaient la ligne par identifiant sans scope. Ce n'était pas un
oubli côté service : le webhook FedaPay les appelle sans session, la société
venant de la transaction. Le garde est donc posé **au contrôleur**, là où une
session existe. Sans lui, un administrateur pouvait déplacer de l'argent chez un
confrère.

`RemainingDecisionsTest` (6 tests) couvre les trois décisions.

## 14. Le contrôle de solde à la création — tranché le 2026-09-05 ✅

### La question, et la réponse qu'elle avait déjà

Constat de départ : un marchand au portefeuille pouvait passer en négatif sans
limite. J'avais laissé la décision ouverte — un transporteur peut vouloir faire
crédit.

En allant lire, la réponse était déjà dans le socle, écrite deux fois :
`Backend\ParcelController::store()` et
`MerchantPanel\MerchantParcelController::store()` refusent la création quand
`total_delivery_amount` dépasse `wallet_balance`. Ce n'était donc pas une
question de produit ouverte, mais une **règle appliquée à deux écrans sur cinq**.

Les trois chemins qui l'ignoraient :

| Chemin | Contrôle | Débit |
|---|---|---|
| `Backend\ParcelController::store` (back-office) | ✅ | ✅ |
| `MerchantPanel\MerchantParcelController::store` (panneau) | ✅ | ✅ |
| `Api\V10\ParcelController::store` (**app mobile**) | ❌ | ✅ |
| `Backend\ParcelController::duplicateStore` | ❌ | ✅ |
| `MerchantPanel\MerchantParcelController::duplicateStore` | ❌ | ✅ |

### La décision

Pas de découvert, sur tous les chemins. Le détail et les arbitrages
(comparaison hors TVA, frontière stricte, pas de plafond de découvert
paramétrable) sont consignés en **D6** de `docs/DECISIONS_METIER.md`.

### Le correctif

Le débit était **recopié quatre fois**, chaque copie enveloppée d'un
`try { … } catch (\Throwable $th) { }` vide — quatre occasions de diverger, et
elles avaient déjà divergé. Les quatre copies deviennent un point unique,
`App\Services\Parcel\WalletDebit`, qui :

1. charge le marchand **du colis** (pas de la requête : c'était le bug W5) ;
2. **verrouille sa ligne** (`lockForUpdate`) — sans quoi deux créations
   simultanées lisent le même solde, le trouvent toutes deux suffisant, et
   débitent deux fois ; le contrôle ne tiendrait qu'à un colis à la fois ;
3. refuse par `InsufficientWalletBalance` si les frais dépassent le solde ;
4. débite et écrit l'écriture de portefeuille.

Le refus n'est pas un `false` : un solde insuffisant n'est pas une panne, le
marchand peut agir. Il traverse les repositories et chaque appelant répond dans
sa langue — 422 avec `required` / `wallet_balance` / `missing` en entiers XOF
côté API, message et retour vers la recharge côté web. Côté `mobile/`, l'écran
de création propose désormais « Recharger `<montant>` » et pré-remplit l'écran
de recharge avec ce qui manquait.

Les deux duplications deviennent au passage **transactionnelles**, comme la
création l'était depuis la décision D-B.

### Deux défauts découverts en descendant

Aucun des deux n'était visible depuis le constat de départ.

- **La duplication depuis le panneau marchand ne fonctionnait pas.** Elle écrit
  un `ParcelLogs` avec `parcel_bank`, colonne que `parcel_logs` n'a **jamais**
  eue (migration de 2022). L'insertion échouait donc à tous les coups : le colis
  était créé, son journal raté, et le `catch` rendait « une erreur est survenue »
  en laissant le colis derrière. Le drapeau est déjà porté par
  `parcels.parcel_bank` ; l'affectation est retirée.
- **Le même journal lisait `$request->merchant_id`**, alors que le colis est
  rattaché à `$request->merchant_id ?? $merchant_id`. Le formulaire web poste
  bien ce champ, donc rien ne cassait en production, mais c'est la mécanique
  exacte de W5, à un appelant près. Le journal suit maintenant le colis.

### Ce que ça couvre

`tests/Feature/ParcelWalletBalanceGuardTest` — 7 tests : refus à un franc près
sans rien écrire, contenu du refus, frontière du solde exact, marchand hors
portefeuille jamais bloqué, solde qui ne descend jamais sous zéro quel que soit
le nombre de tentatives, duplication refusée sans rien laisser derrière,
duplication acceptée avec son journal.

## 15. Un cinquième chemin de création, qui ne débite rien du tout

Relevé en fermant §14, **non corrigé** : il appelle une décision, pas un
correctif d'office.

`App\Imports\ParcelImport` — l'import Excel en masse, disponible côté
back-office **et** côté panneau marchand — crée les colis directement, sans
passer par aucun repository. Il ne touche **jamais** le portefeuille : ni
contrôle, ni débit. Pour un marchand au portefeuille, le débit à la création
*est* la facturation ; un import de deux cents colis n'est donc facturé nulle
part.

C'est la même famille que W5, mais l'écart est plus grand : ailleurs il fallait
réparer un débit cassé, ici il faudrait en **introduire un**. Ça change ce que
paient les marchands qui utilisent déjà l'import, et ça ne se décide pas dans un
correctif de revue. Trois questions, dans cet ordre :

1. L'import doit-il débiter comme la création unitaire, ou est-il volontairement
   hors portefeuille (réservé aux gros comptes réglant au relevé) ?
2. S'il débite : un solde insuffisant refuse-t-il **tout** le fichier, ou
   n'importe-t-il que les lignes couvertes ?
3. Que fait-on des imports déjà passés en production, s'il y en a ?

Le correctif serait court une fois la réponse connue — `WalletDebit::apply()`
existe et s'applique à un colis — mais il n'a de sens qu'après.

## 16. Ce qui reste, et n'est pas un constat

- Les tests absents des étapes comptables, par ordre d'exposition financière :
  `parcelDelivered`, la livraison partielle, les retraits, les reversements.
- Le correctif du débit (W5) **ne rattrape pas le passé** : si la production
  tourne déjà, des colis créés depuis l'app peuvent n'avoir jamais été débités.
  Un rapprochement `parcels` / `wallets` reste à faire, hors code.
