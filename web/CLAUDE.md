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
  La suite tourne aussi dans GitHub Actions : **rien ne part en production sans
  elle** (`.github/workflows/deploy.yml`, un push sur `main` déploie).
- `php artisan openapi:generate` après tout changement de `routes/api.php` ou de
  `resources/openapi/overlay.php` (régénère `public/openapi/v10.json`, versionné).
- `php artisan beninlink:pilote [--company=] [--reset]` : jeu de données béninois de recette
  (5 PME dont **PIL-002 au portefeuille prépayé**, recharge d'ouverture et colis débités :
  sans elle, la recette n'exerce ni le débit à la création ni `beninlink:colis-non-debites`)
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
- `php artisan beninlink:zones-tarifaires [--societe=] [--supplement=] [--installer]` —
  installe le modèle par **zones** (**D4**) : les quatre zones (Cotonou, Périphérie,
  Intérieur, CEDEAO), les trois délais avec le supplément « jour même » (300 F), et les
  **forfaits CEDEAO par pays** (Togo 12 000, Nigeria 18 000, Burkina Faso 15 000).
  Elle n'écrit **aucun montant de grille** : les tranches × zones se saisissent dans
  *Réglages → Zones et barème* (`admin/delivery-zone`) — ces montants appartiennent au
  transporteur. Les forfaits CEDEAO sont l'exception (tranchés par le métier) et restent
  **créés s'ils manquent, jamais réécrits**.
  Le **code** d'une zone se fixe à la création (il porte le rattachement du taux COD) et
  une zone qui porte des tarifs ne se supprime pas. Les définitions vivent en un seul
  endroit : `App\Services\Pricing\ZoneCatalog`.
  **Depuis l'étape 6 (2026-09-07), la route est le seul axe de tarification.** Un colis
  porte sa zone et son délai (`parcels.zone_id`, `parcels.delay_id`) ; sans zone,
  `ChargeCalculator` **refuse** (`UnpricedDeliveryException::sansZone()`) plutôt que de
  rendre zéro, et `zone_id` est `required` à la création. Une route non tarifée est
  refusée de même (règle `DeliveryRoutePriced`), jamais rabattue sur une voisine.
  L'import Excel lit une colonne **`zone_code`** (ou `zone_id`) ; les deux fichiers
  modèles de `public/sample-parcel/` la portent.
- `php artisan beninlink:tarification-prete [--societe=]` — dit, société par société,
  si la tarification est **en état de facturer** (**D4**) : zones absentes, tranches
  tarifées dans une zone et pas dans une autre, barèmes négociés sans zone, colis
  récents créés sans zone. **Sort en erreur** tant qu'une société n'est pas prête, pour
  qu'un déploiement s'arrête là. Ne corrige rien.
  (S'appelait `beninlink:bareme-herite` tant qu'elle gardait la porte de l'étape 6.)
- `php artisan beninlink:retours-annules [--societe=] [--marchand=] [--corriger] [--force]` —
  rend au marchand le frais de retour qu'une annulation ne lui a jamais rendu, et le
  reprend au livreur. Un colis dont le **relevé est déjà émis** est montré, jamais
  touché : un avoir se décide avec l'expert-comptable (**D8**, **D9**).
- `php artisan beninlink:journal-syscohada [--du=] [--au=] [--societe=] [--payes] [--fichier=]` —
  extrait des écritures d'une période, équilibre vérifié avant écriture. Le plan de
  comptes vit dans `config/syscohada.php` et reste une **proposition** tant que
  `docs/guides/comptabilite/plan-de-comptes.md` n'est pas signé (**D2**).
- `php artisan beninlink:file-attente [--seuil=5]` — état de la file des envois
  (SMS, push, e-mails) et détection d'un **worker arrêté** : la panne que la file
  introduit est silencieuse. Décision **D13**.
- **Version PHP : 8.2.** `composer.json` déclare `^8.1`, mais `composer.lock`
  verrouille `lcobucci/clock 2.3.0`, qui déclare `~8.1.0 || ~8.2.0` : un
  `composer install` depuis ce lock **refuse** de s'installer au-delà de 8.2.
  Trois autres paquets plafonnent à 8.3 (`ezyang/htmlpurifier`,
  `laminas-diactoros`, `nette/schema`). C'est la version que l'intégration
  continue utilise, et celle que le VPS doit servir : au-delà, le
  `composer install` de `deploy.sh` échouerait.
  Relever ce plafond demande de mettre à jour ces paquets — un chantier en soi,
  qui touche la chaîne JWT.

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
