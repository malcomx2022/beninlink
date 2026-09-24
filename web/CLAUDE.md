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
- L'**interface web de l'éditeur de `.env`** (**S27**, 2026-09-18) : les onze routes
  `/env-editor` du paquet auto-découvert `geo-sot/laravel-env-editor` répondaient **200
  sans authentification** (identifiants de base, `APP_KEY`, clés FedaPay). Elles sont
  fermées en **404** par `BlockEnvEditorRoutes`, déclaré dans `config/env-editor.php`.
  Ne pas retirer ce garde, et ne pas désinstaller le paquet : `InstallerController`
  écrit `.env` par sa **façade** pendant l'installation. C'est l'interface qui est
  coupée, pas la bibliothèque.

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
- `php artisan invoice:generate [--societe=]` — les relevés de règlement dus,
  **société par société**. Sans option, toutes les sociétés actives : c'est ce que
  fait le planificateur (13 h). La cadence reste celle du marchand
  (`merchants.payment_period`, en jours) : la commande propose, la fiche dispose.
  ⚠️ Elle bouclait sur `settings()->id` : hors requête, la règle **F4** s'applique
  ici aussi — `settings()` retombe sur la société 1, et les autres transporteurs
  n'avaient **jamais** de relevé. La société vient désormais de la liste des
  sociétés et, pour chaque relevé, du **marchand**.
  Voir `docs/guides/comptabilite/reprise-des-releves.md`.
- `php artisan beninlink:file-attente [--seuil=5]` — état de la file des envois
  (SMS, push, e-mails) et détection d'un **worker arrêté** : la panne que la file
  introduit est silencieuse. Décision **D13**.
- **Version PHP : 8.3.** Elle est écrite à **trois** endroits, et ils doivent
  rester d'accord : `config.platform.php` dans `composer.json` (ce que composer
  résout), `php-version` dans `.github/workflows/deploy.yml` (ce que l'intégration
  exécute), et le garde en tête de `deploy.sh` (ce que le serveur exécute
  vraiment). Le troisième est le seul qui puisse constater un désaccord : les
  deux premiers décrivent une intention, lui lit le `php` de la machine.
  **Ne pas lire `composer.json` seul pour deviner la version** : sa clause
  `require.php` a longtemps dit `^8.1` alors que le lock imposait 8.2, et c'est
  exactement ce qui a fait tomber la première exécution de l'intégration continue.
  Les deux disent maintenant la même chose.
  Le plafond 8.2 tenait à `lcobucci/clock 2.3.0`, tiré par `lcobucci/jwt`, tiré
  par le seul `vonage/client` — le SDK SMS, pas l'authentification, qui passe par
  Sanctum. Monter Vonage (4.0 → 4.3) a fait disparaître `lcobucci/clock` du lock.
  **8.4 reste fermé** : `nette/utils` déclare `<8.4`, `ezyang/htmlpurifier` et
  `nette/schema` s'arrêtent à 8.3, et `nette/*` remonte à `league/commonmark`,
  donc au cœur de Laravel. C'est un autre chantier.

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
- Toute route **web** à paramètre est inscrite dans `tests/Feature/WebIsolationCoverageTest` :
  prouvée (avec son test), exemptée (avec le motif pour lequel son paramètre ne désigne
  pas la ressource d'autrui), ou publique à dessein (avec le motif). **L'arriéré est
  fermé depuis S35** (171 → 0 en dix passes) : `HERITAGE` doit rester **vide**, il n'y a
  plus de liste d'attente. Une route de locataire porte `auth` —
  c'est l'invariant de **S28**, une carte des courses déclarée hors du groupe `auth` qui
  versait dans la page le nom, le téléphone et l'adresse des clients. Et un paquet ne
  monte pas de routes web sans être déclaré — invariant de **S27**.
  Pour appeler une route de locataire dans un test : `Tests\Concerns\MountsTenantRoutes`
  (semer le domaine ne suffit pas — l'appel doit viser un hôte **non central**, sinon 404).
  ⚠️ Et il faut un **abonnement en cours** : sans lui, `subscriptionCheckMiddleware`
  renvoie **302 vers `/subscription`** avant le contrôleur. Un test qui attend un refus
  lirait ce 302 comme une preuve et passerait sans exécuter la ligne qu'il couvre — d'où
  la règle : le contrôle négatif attend **200**, pas « autre chose qu'un 404 ».
  ⚠️ Ce filet n'énumère que les routes **à paramètre d'URL**. Un identifiant porté par le
  **corps** de la requête lui est invisible — c'était l'angle mort du chantier, fermé par
  **S38**.
- Toute route d'**écriture sans paramètre d'URL** qui lit un identifiant dans le **corps**
  de la requête est inscrite dans `tests/Feature/BodyIdentifierCoverageTest` (**S38**) :
  prouvée (avec son test), ou exemptée (avec le motif pour lequel l'identifiant ne désigne
  pas une ressource de locataire). **L'arriéré est fermé depuis S53** (90 → 0) :
  `HERITAGE` reste **vide**, et le plafond à `0` l'interdit.
  ⚠️ `PUT admin/currency/update` est **exemptée** (métier, 24/09) : `currencies` ne
  porte aucune `company_id` — catalogue de **plateforme**, comme `categorys`. Il
  reste **partagé et modifiable** (renommer une devise la renomme pour toutes) :
  l'exemption ferme la question du **filet**, pas celle de l'**accès**. Deux témoins
  dans `UserAndSettingsScopeTest` mordent si l'une des tables gagne une société.
  Ce filet énumère les **contrôleurs**, pas les routes : il lit la source de la méthode de
  contrôleur **et celle de la méthode de dépôt qu'elle appelle**. ⚠️ Le second niveau est
  indispensable — un contrôleur qui passe `$request` tel quel au dépôt ne lit aucun
  identifiant lui-même (`POST admin/assign-pickup/bulk`), et sort de l'énumération sans lui.
  Un test-témoin garde ce point.
- Les chemins **en lot** sont le cas le plus dangereux de cette famille : leur identifiant
  est une **liste**, et rien dans la signature d'une route ne la porte. Règle de forme
  (**S38**) : dans un lot, un identifiant hors périmètre est **ignoré** et la boucle
  continue — le reste du lot passe ; sur un chemin à identifiant unique, on **refuse**.
  Et une annulation de statut ne recule pas qu'un statut : elle **supprime les
  `ParcelEvent`**, c'est-à-dire la chronologie que lit le client. L'assertion porte donc
  sur le **nombre d'évènements**, pas seulement sur le statut.
- Le **panneau** d'une route se garde par **type de compte**, avant toute permission
  (**S41**) : `panel:back-office` (ADMIN + SUPER_ADMIN), `panel:merchant` (MERCHANT),
  `panel:super-admin`. La garde se pose sur le **groupe**, et c'est le **préfixe d'URI**
  qui dit le panneau — **pas le nom de route** : une cinquantaine de routes nommées
  `merchant.*` vivent sous `admin/` (ce sont les écrans du back-office *à propos* des
  marchands). ⚠️ Sans elle, le seul séparateur entre les deux panneaux était le
  `hasPermission` route par route : là où il manquait, **tout compte authentifié**
  passait — un marchand écrivait par `POST admin/parcel/priority/update`. Le pendant
  **API** de cette garde est `userType` (**S5**) : même concept, implémentation distincte
  (jeton et enveloppe JSON), et les deux docblocs se renvoient l'un à l'autre.
  ⚠️ `GET /dashboard` est **partagé** par les trois types (`DashbordController::index()`
  branche sur `user_type`) : il est hors des deux préfixes à dessein, un test l'inscrit.
- La **garde d'accès** d'une route du back-office se pose avec `hasPermission:x`, et
  `hasPermission:a|b|c` quand plusieurs écrans aux droits différents appellent la même
  aide AJAX (**S36**). Deux règles tirées de ce lot : les **paires sœurs** portent le
  même droit — la liste et le détail, l'impression unitaire et l'impression en lot —
  et une garde posée sur l'écran **sans** l'être sur l'écriture ne garde rien (le
  clone de colis contournait ainsi `parcel_create` par son `clone-store`).
  Avant d'en poser une : **mesurer qui perd l'accès**, rôle par rôle, y compris le jeu
  **fixe** du chef de hub (`UserRepository::hubPermissions()`), que `RoleSeeder` ne
  montre pas.
  Un **refus** se dit (**S39**) : la machine reçoit **403** (appel AJAX ou JSON),
  l'humain reste dans le back-office — page précédente, à défaut le tableau de bord,
  **jamais `/`** qui est la page publique du site — avec un message
  (`message.permission_denied`). ⚠️ Ne pas lire la page précédente par
  `url()->previous()` : elle lit **d'abord l'en-tête `Referer`**, donc une donnée du
  client, ce qui ouvrirait une redirection vers n'importe quelle adresse.
- Toute route `admin/*` est inscrite dans `tests/Feature/WebAdminPermissionCoverageTest`
  (**S44**) : **gardée** (`hasPermission`), **exemptée** (avec le motif) ou **à l'arriéré**
  (sous un plafond qui ne peut que baisser). Le droit d'une **aide AJAX** est celui des
  **écrans qui l'appellent**, et ⚠️ ces appelants vivent dans
  `public/backend/js/**/custom.js`, **pas dans les vues** — c'est ce qui avait fait
  survivre 60 routes à S36. ⚠️ Chercher l'URI **courte** (`parcel/filter`) attrape aussi
  `merchant/parcel/filter` : la déduction mécanique donne une **piste, pas un verdict**.
  ⚠️ Le jeu de droits du **super-administrateur** vient d'une autre table
  (`SuperAdminPermission`) : il est déjà refusé sur les routes gardées du back-office
  locataire, donc une route liée depuis **son** menu ne se garde pas par un droit de
  locataire mais par le **type de compte** (S41).
- Un **sabotage vert** interroge le test — **et d'abord son propre ancrage** (**S44**) :
  vérifier que le fichier a changé ne suffit pas, il faut vérifier qu'il a changé **là**.
  Un ancrage non unique (`hasPermission:parcel_update`) frappe une autre ligne et rend le
  vert crédible.
- Jamais de clés en dur : `FEDAPAY_*` dans `web/.env`.

## Étape 0 — cartographie (à lire AVANT de coder ici)
Le relevé des points de branchement réels vit dans **`web/CARTOGRAPHIE.md`** (blocs A-K) :
multi-tenancy par `company_id`, **absence d'abstraction de paiement**,
`WalletRepository::approved()` seul point de crédit du wallet, abonnement Stripe codé
en dur, `ParcelStatus`, API `/api/v10`. Il contient aussi les constats de sécurité et
les routes mortes relevés au passage.
