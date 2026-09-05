# Revue du socle FedaPay et des workflows du projet

> Revue de lecture menée le 2026-09-05 sur `main` (`ffa518f`). **Aucun fichier de
> code n'a été modifié.** Les constats marqués **vérifié** ont été reproduits par
> des tests jetables exécutés sur la base de test, puis supprimés.
> Base de référence au moment de la revue : `vendor/bin/phpunit` — 127 tests,
> 824 assertions, au vert.
>
> **Mise à jour du 2026-09-05.** La revue elle-même ne corrigeait rien. Sept
> constats ont depuis été corrigés à la demande du porteur : **W1** et **F1**
> d'abord, puis **W2**, **W4** et **W5**, enfin **F2** et **F3**. Chaque section porte son correctif et
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
| **W3** | Le contrôle douanier est absent de la création côté back-office | Moyen | oui |
| **F4** | Le SMS de confirmation part sous l'identité de la société 1 | Moyen | oui |
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
| F4 | **Vérifié.** Dans le webhook, `settings()` renvoie la société 1. Le SMS de confirmation porte donc le nom commercial et la devise d'un autre locataire, et `smsSettings()` retombant sur `company_id = 1`, il part avec les identifiants d'opérateur SMS de la société 1, facturés à elle. Le crédit reste correct ; le fil de notifications est sain. | `WalletRepository::approved()` |
| F5 | Le chemin de crédit du portefeuille par webhook n'a **aucun test**, alors que `.claude/rules/payments.md` l'exige. L'abonnement, lui, est couvert. Les tests portefeuille s'arrêtent à l'initiation. | `tests/Feature/FedaPayWalletWebTest.php` |
| F6 | `IsolationCoverageTest` déclare `GET fedapay/status/{reference}` couvert par `FedaPayWebhookTest`, qui ne touche jamais cette route : ni `RefreshDatabase`, ni appel HTTP. Le code est correct, il scope par `merchant_id` ; c'est la déclaration de couverture qui est fausse. | `IsolationCoverageTest.php:58` |
| F7 | `FedaPayGateway::retrieve()` n'est appelé nulle part. Écrit « pour un rapprochement manuel », sans écran ni commande de rapprochement. | `FedaPayGateway` |
| F8 | Aucun écran n'expose `fedapay_transactions`. Une transaction refusée, en attente, ou dont le montant diffère, n'est visible que dans les journaux. | — |
| F9 | Les variables `FEDAPAY_*` sont documentées dans `docs/guides/infra/.env.example` mais absentes de `web/.env.example`, le fichier qu'un développeur copie. | `web/.env.example` |
| F10 | Le guide d'intégration porte encore « l'abonnement SaaS n'est pas encore câblé » : il a été livré depuis. | `docs/guides/fedapay-module/` |
| F11 | `SaasMetrics::subscriptionCashBetween()` ne compte que les encaissements FedaPay. Un locataire qui paie par Stripe n'apparaît pas dans `cash_collected`, alors que `bookings` le compte. L'écart est intentionnel et commenté, mais doit être affiché avec le chiffre. | `SaasMetrics.php` |

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

## 9. W3 — le contrôle douanier est absent de la création côté back-office

`app/Http/Requests/MerchantPanel/Parcel/StoreRequest.php` porte
`destination_country`, `customs_category` et la règle `CustomsAllowed` : un colis
bloquant est refusé côté API et côté panneau marchand.

`app/Http/Requests/Parcel/StoreRequest.php`, utilisé par le back-office, ne porte
**aucun de ces champs**. Un agent peut donc créer depuis l'administration un colis
que la règle douanière interdit. L'observer émet bien l'alerte a posteriori, mais
le blocage, lui, ne s'applique pas.

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
6. **W3**, **F4**, **F5**, **F6** — cohérence et filets de sécurité.
7. **À décider** : reprendre ou non les lignes de `settings` écrites avec un
   locataire nul avant le correctif du `fillable`.
7. Les tests absents des étapes comptables, par ordre d'exposition financière :
   `parcelDelivered`, la livraison partielle, les retraits, les reversements.
