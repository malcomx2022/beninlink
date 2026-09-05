# CLAUDE.md — web/ (backend Laravel)

> Chargé quand Claude Code travaille dans web/. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici (les clés vivent dans web/.env).

## Rôle
Cœur de BeninLink : socle We Courier, multi-tenant par sous-domaine, API consommée
par les apps `mobile/` et `mobile-livreur/`. **C'est le contrat** ; on le fait évoluer
avant les apps.

## Ne jamais casser
- La **logique multi-tenant** (scoping par sous-domaine) : toute requête reste tenant-aware.
- Les **flux de paiement existants** : il n'y a pas d'abstraction de passerelle dans
  We Courier (chaque gateway a son contrôleur) — on **ajoute** FedaPay à côté, on ne
  modifie pas les autres.
  Exception actée (S21, 2026-09-05) : **Aamarpay et SSLCommerz sont désactivées** via
  `config/payments.php` (`disabled_gateways`) — ne pas les réactiver sans corriger
  leur TLS.
  Exception actée (**D10**, 2026-09-05) : le **module « payout / paiement en ligne »**
  (Stripe, PayPal, bKash, Skrill, Razorpay) est coupé via
  `config('payments.online_payout')` / `onlinePayoutEnabled()`. Il déplaçait un solde
  sans écrire au relevé, facturait en BDT, ne scopait pas la société, et PayPal comme
  Razorpay ne vérifiaient rien. ⚠️ C'est le **module** qui est coupé, pas les
  passerelles : `stripe_status` sert aussi l'abonnement SaaS.
- Le **cycle de vie des colis** : on franchit ses états, on ne les redéfinit pas.
- Le **transport du push** (**D11**, 2026-09-05) : l'API FCM « legacy » du socle est
  arrêtée depuis 2024. Les notifications poussées passent par `config('push.driver')`
  → `App\Services\Push\PushGateway` (pilote `expo`, ou `null` pour ne rien envoyer).
  Ne pas rebrancher un appel direct à `fcm.googleapis.com`.
  Exception actée (**D12**, 2026-09-05) : le **push navigateur du back-office** est
  retiré — il inscrivait les agents au projet Firebase de l'éditeur. Ne pas remettre
  de SDK Firebase dans les vues ; `public/firebase-messaging-sw.js` ne sert plus qu'à
  désinscrire les navigateurs déjà abonnés.

## Décisions actées
- **FedaPay** = passerelle Mobile Money BJ. **Webhook signé = seule source de vérité**
  pour créditer/activer. Traitement **idempotent**.
- Devise **XOF** : montants **entiers**. Locale **FR** par défaut.
- **TVA** : taux **au niveau de la société** (`configs.vat_rate`, 18 % au Bénin), surcharge
  par marchand si `merchants.vat` > 0 — toujours via `VatRate::for()`. Registre des
  décisions métier : `docs/DECISIONS_METIER.md`.
- Statuts colis : En attente → Ramassage assigné → Entrepôt → Livreur assigné → Livré ;
  + Livraison partielle, Retour, Annulé.

## Chantiers (ordre)
1. Francisation + format FCFA (locale FR, devise XOF).
2. Identifiants légaux : **IFU, RCCM, CNSS** (migration + validation + factures).
3. **FedaPay** : brancher sur recharge wallet et abonnement SaaS.
4. Facturation **SYSCOHADA** + **relevés de règlement** marchand (COD − frais − TVA = net).
5. **Alertes douanières** (Module 4 TDR-L7) : règles UEMOA/CEDEAO, 3 niveaux.
6. Reporting SaaS : MRR, ARR, Churn, LTV, CAC.
7. Spec **OpenAPI/Swagger** sur l'API (contrat des apps mobiles).

## Commandes
- `composer install` · `php artisan migrate` · `php artisan test` (avant tout commit) · `php -l <fichier>`
- `php artisan openapi:generate` après tout changement de `routes/api.php` ou de
  `resources/openapi/overlay.php` (régénère `public/openapi/v10.json`, versionné).
- `php artisan beninlink:pilote [--company=] [--reset]` : jeu de données béninois de recette
- `php artisan beninlink:reglages-orphelins` — constate les lignes `settings`
  écrites sans société avant le correctif du `fillable` ; `--purge` pour les retirer.
  (refusé en production) — voir `docs/guides/recette-pilote/README.md`.
- `php artisan beninlink:colis-non-debites [--marchand=] [--regulariser] [--force]` —
  constate les colis de marchands au portefeuille jamais facturés (W5, `catch`
  vide, import Excel) ; `--regulariser` écrit les débits manquants (refusé en
  production sans `--force`). Décision **D7** de `docs/DECISIONS_METIER.md`.
- `php artisan beninlink:ecarts-marchands [--marchand=] [--corriger] [--force]` —
  rapproche `merchants.current_balance` de son relevé (`merchant_statements`).
  Ne corrige que les écarts **entièrement expliqués** par les annulations de
  livraisons partielles ; les autres, il les montre. Décision **D9**.
- `php artisan beninlink:file-attente [--seuil=5]` — état de la file des envois
  (SMS, push, e-mails) et détection d'un **worker arrêté** : la panne que la file
  introduit est silencieuse. Décision **D13**.
- Version PHP réelle : voir `web/composer.json`.

## Conventions
- Réutiliser les conventions We Courier (repérer un exemple avant d'écrire du neuf).
- Tout module de paiement modifié est couvert par des tests PHPUnit (dont idempotence webhook).
- Tout envoi sortant (SMS, push, e-mail) part **en file** (**D13**), jamais dans la
  requête : mettre en file dans le point d'entrée existant, garder ses appelants, et
  **porter la société dans le job** — hors requête, `settings()` retombe sur la
  société 1 et l'envoi partirait au nom d'un autre transporteur (F4).
- Toute étape qui écrit dans les comptes ou touche un solde suit **D8** de
  `docs/DECISIONS_METIER.md` : une seule fois (refuse d'être rejouée), `companywise()`,
  dans une transaction, notifications hors transaction, et chaque écriture nomme son tiers.
- Une notification poussée ne se décide pas dans un repository : le fil marchand passe
  par `MerchantFeed` et le canal `push` de `MerchantNotification`. Un push raté ne fait
  jamais échouer l'écriture métier, et un même fait ne donne **qu'une** notification.
- Toute route `/api/v10` à identifiant est inscrite dans `tests/Feature/IsolationCoverageTest`
  avec le test prouvant qu'un compte n'atteint pas la ressource d'un autre (S7).
- Jamais de clés en dur : `FEDAPAY_*` dans `web/.env`.

## Étape 0 — cartographie (à lire AVANT de coder ici)
Le relevé des points de branchement réels vit dans **`web/CARTOGRAPHIE.md`** (blocs A-K) :
multi-tenancy par `company_id`, **absence d'abstraction de paiement**,
`WalletRepository::approved()` seul point de crédit du wallet, abonnement Stripe codé
en dur, `ParcelStatus`, API `/api/v10`. Il contient aussi les constats de sécurité et
les routes mortes relevés au passage.
