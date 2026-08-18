# FedaPay — intégration BeninLink (chantier 3)

> ✅ **Implémenté le 2026-08-18** dans `web/`. Ce document décrit ce qui est en
> place et ce qui reste à faire à la mise en service.
> Les fichiers voisins de ce dossier étaient l'esquisse initiale ; le code réel
> vit désormais dans `web/`.

## Ce qui est en place

| Élément | Emplacement |
|---|---|
| Configuration deux environnements | `web/config/fedapay.php` |
| Passerelle HTTP | `web/app/Services/Payments/FedaPayGateway.php` |
| Contrôleur (initiation, webhook, retour, état) | `web/app/Http/Controllers/Payment/FedaPayController.php` |
| Journal des transactions | `web/database/migrations/2026_08_18_100000_create_fedapay_transactions_table.php` |
| Modèle | `web/app/Models/Backend/FedaPayTransaction.php` |
| Tests de signature | `web/tests/Feature/FedaPayWebhookTest.php` (8 tests) |

**Intégration en HTTP direct via Guzzle**, sans le SDK `fedapay/fedapay-php` :
le socle n'a aucune abstraction de passerelle (chaque gateway a son contrôleur),
une dépendance de plus n'apporterait rien. Les routes suivent la documentation
officielle.

## Les deux environnements

Le basculement ne touche **que le `.env`** — aucun code à modifier :

| Variable | Sandbox | Production |
|---|---|---|
| `FEDAPAY_ENVIRONMENT` | `sandbox` | `live` |
| Base d'API (automatique) | `https://sandbox-api.fedapay.com/v1` | `https://api.fedapay.com/v1` |
| `FEDAPAY_SECRET_KEY` | clé sandbox | clé live |
| `FEDAPAY_WEBHOOK_SECRET` | secret sandbox | secret live |

⚠️ Les clés sandbox et live sont **distinctes** : une clé sandbox en production
échoue, et inversement. Toute valeur d'environnement inconnue retombe sur
`sandbox` — une faute de frappe ne peut pas faire basculer en production.

## Parcours

1. **`POST /api/v10/fedapay/initiate`** (marchand connecté) — crée une ligne
   `wallets` en `PENDING` et une transaction FedaPay, renvoie `payment_url`.
   Ne crédite rien.
2. L'app ouvre `payment_url` en **WebView** ; le client choisit MTN ou Moov et
   valide par USSD. **L'app ne voit jamais les clés.**
3. **`POST /fedapay/webhook`** — vérifie `X-FEDAPAY-SIGNATURE`, puis crédite.
4. **`GET /api/v10/fedapay/status/{reference}`** — l'app interroge l'état et le
   solde après fermeture de la WebView.

`GET /fedapay/callback` n'accorde rien : il informe l'utilisateur que la
confirmation est en cours.

## Les garde-fous, et pourquoi

**Le webhook signé est la seule source de vérité.** Le retour de WebView ne
crédite jamais : un utilisateur peut l'ouvrir à la main.

**Signature vérifiée avant tout traitement** — HMAC-SHA256 de
`horodatage.charge utile`, comparaison en **temps constant** (`hash_equals`) et
**tolérance d'horodatage** (300 s par défaut) contre le rejeu. Sans
`FEDAPAY_WEBHOOK_SECRET`, aucun webhook n'est accepté : c'est volontaire.

**Idempotence par verrou de ligne.** La cartographie (bloc C) a établi que
`WalletRepository::approved()` n'est ni transactionnelle ni idempotente : deux
appels créditent deux fois. Le garde-fou vit donc dans
`fedapay_transactions.provider_transaction_id` (index **unique**) et dans un
`lockForUpdate()` : un webhook rejoué ressort sans créditer.

**Le webhook n'utilise pas `settings()`.** Hors requête authentifiée, il
retomberait sur la société 1 (bloc A). Le locataire vient de la transaction.

**Le crédit passe par `WalletRepository::approved()`** — le service existant du
socle — pour ne pas dupliquer l'incrément du solde ni l'envoi du SMS.

**Le montant crédité est celui attendu**, jamais celui annoncé par l'événement ;
un écart est journalisé pour rapprochement manuel.

## À faire à la mise en service

1. **Créer un compte FedaPay** et relever les clés sandbox, puis live.
2. **Déclarer l'URL de webhook** dans le Workbench FedaPay :
   `https://<domaine>/fedapay/webhook` — et relever le secret de signature.
3. **Renseigner le `.env`** (voir `docs/guides/infra/.env.example`).
4. **Tester en sandbox** un cycle complet : initiation, paiement, webhook,
   crédit du solde, puis **rejouer le même webhook** pour vérifier qu'il ne
   crédite pas deux fois.
5. Basculer `FEDAPAY_ENVIRONMENT=live` avec les clés de production.

## Limites connues

- **Non testé contre l'API réelle** : aucune clé n'était disponible au
  développement. La forme des requêtes suit la documentation officielle, mais un
  premier essai en sandbox reste indispensable.
- **Seule la recharge de wallet est branchée.** L'abonnement SaaS
  (`purpose = subscription`) est prévu dans le schéma mais pas encore câblé :
  il passe aujourd'hui par Stripe codé en dur (bloc E). La faille S1 qui
  l'accompagnait — activation sans vérification du paiement — est corrigée.
- ✅ **Idempotence vérifiée en conditions réelles le 2026-08-18** : deux webhooks
  `transaction.approved` identiques et signés — le premier crédite 7 500 FCFA, le
  second répond `already processed` et le solde reste inchangé.
- **Tests d'idempotence automatisés à compléter** : `phpunit.xml` pointe sur une SQLite en
  mémoire vide ; il faut y ajouter `RefreshDatabase` pour couvrir le double
  webhook. Les 8 tests actuels couvrent la vérification de signature.
- **Clés par locataire** : `FedaPayGateway::credentialsFor()` lit
  `settings.fedapay_secret_key` par `company_id`, mais aucun écran ne les saisit
  encore — le socle passe par `PayoutSetupRepository`, non scopé (constat S6).
