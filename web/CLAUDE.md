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
- L'**installateur du socle** (**S88**, 2026-10-05) : ses trois routes (`install`, `installing`,
  `finish`) portent `IsNotInstalled`, et le garde tient pour installée toute base dont la table
  `users` n'est pas vide, drapeau `APP_INSTALLED` ou pas. Le socle ne gardait que l'écran :
  `GET /finish` **supprimait chaque table** et réamorçait la base, sans authentification, sur une
  installation terminée. Ne pas sortir une route de ce groupe ; ne pas ramener `installee()` au
  seul drapeau. `verifier-env.sh` refuse un `.env` sans `APP_INSTALLED=yes` (`InstallerLockTest`,
  `RecetteDeploymentTest`).

## Décisions actées
- **FedaPay** = passerelle Mobile Money BJ. **Webhook signé = seule source de vérité**
  pour créditer/activer. Traitement **idempotent**.
- Devise **XOF** : montants **entiers**. Locale **FR** par défaut.
- **TVA** : taux **au niveau de la société** (`configs.vat_rate`, 18 % au Bénin), surcharge
  par marchand si `merchants.vat` > 0 — toujours via `VatRate::for()`. Registre des
  décisions métier : `docs/DECISIONS_METIER.md`.
  **Exonéré ≠ non renseigné** (**R7 b**, S75) : `merchants.vat_status` (`App\Enums\VatStatus`)
  porte `unset` / `taxable` / `exempt` ; `VatRate::for()` rend 0 pour un exonéré **par
  statut**, jamais pour un 0 non renseigné. Ne jamais déduire une exonération d'un taux à zéro.
  Le **frais de retour est taxable** (**D2 q.6**, S73) : `ReturnVat` prélève sa TVA au
  retour, au franc, sur sa propre ligne de relevé marchand, et l'écrit sur
  `parcels.return_vat_amount` ; le relevé la facture telle quelle. Ne pas remettre le
  `vat_amount = 0` du socle dans `InvoiceRepository`.
- **Plan de comptes SYSCOHADA** (**D2**, tranché S73) : `config/syscohada.php` est **figé**
  par `PlanDeComptesSigneTest` — on change le test avec la décision. Auxiliaire par
  marchand **par défaut** ; trois numéros restent « à valider » par l'expert-comptable.
  Un relevé **marqué payé** reçoit `invoices.paid_on` : c'est la date de l'écriture de
  banque, donc on marque payé **le jour du virement**.
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
  elle** (`.github/workflows/deploy.yml`, un push sur `main` déploie) — **ni sans la
  répétition de déploiement** (**S80**) : un job `--no-dev` contre MySQL qui déroule
  l'installation d'une recette puis les commandes de `deploy.sh` dans son ordre, sur
  chaque pull request. `DeploymentRehearsalTest` lit `deploy.sh` et le workflow : une
  étape ajoutée au script se répète dans le job, sinon rouge. Depuis **S76**
  le même workflow déploie aussi le **vhost de recette** (`/var/www/beninlink-recette`,
  secrets `RECETTE_SSH_*`, job qui se saute sans eux) par le même
  `docs/guides/infra/deploy/deploy.sh`, dont le chemin vient de `DEPLOY_PATH`. Avant de
  couper le site, `verifier-env.sh` refuse un `.env` hors production qui porterait
  FedaPay en live ou une clé `_live_`, et depuis **S77** tout `.env` **sans `API_KEY`**
  ou portant la clé publique du socle (`RecetteDeploymentTest` l'exécute). Depuis **S84** un
  job `apps` (matrice `mobile` / `mobile-livreur`) rejoue `tsc`, `expo lint` et `npm test` sur
  chaque pull request ; il **ne conditionne pas** `deploy` — les apps partent par EAS.
  ⚠️ Le job **ne pose aucune zone à la main** (**S86**) : les semences posent le cadre de
  chaque société, et c'est précisément ce que `tarification-prete` mesure là.
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
- `php artisan beninlink:zones-tarifaires [--societe=] [--supplement=] [--installer] [--grille=]` —
  installe le modèle par **zones** (**D4**) : les quatre zones (Cotonou, Périphérie,
  Intérieur, CEDEAO), les trois délais avec le supplément « jour même » (300 F), et les
  **forfaits CEDEAO par pays** (Togo 12 000, Nigeria 18 000, Burkina Faso 15 000).
  Sans `--grille`, elle n'écrit **aucun montant de grille** : les tranches × zones se
  saisissent dans *Réglages → Zones et barème* (`admin/delivery-zone`) — ces montants
  appartiennent au transporteur. Les forfaits CEDEAO sont l'exception (tranchés par le
  métier) et restent **créés s'ils manquent, jamais réécrits**.
  Avec `--grille=database/bareme/grille-nationale.csv` (**S72**), elle pose aussi la
  grille nationale lue dans ce fichier (`categorie;poids_max;cotonou;peripherie;interieur`,
  FCFA entiers), que le jeu `beninlink:pilote` lit **lui aussi** : recette et production
  partent de la même grille. Même règle que les forfaits : une ligne **déjà en base n'est
  jamais réécrite** — le fichier est un point de départ, le transporteur réajuste les
  montants **à tout moment** depuis l'écran, et la relance au déploiement suivant ne les
  ramène pas à la valeur du fichier (`GridFileTest`). Un fichier fautif refuse tout et
  n'écrit rien, zones comprises. Lecture et pose : `App\Services\Pricing\GridFile`.
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
  extrait des écritures d'une période, équilibre vérifié avant écriture. Depuis **S73**
  chaque écriture tombe dans la période de **sa** date (`SyscohadaJournal::periode()`) :
  ventes et compensation à l'émission, banque à `paid_on`, **recharges de portefeuille**
  (avances reçues) à l'approbation, **remises d'espèces des livreurs** (transit) à leur
  date ; aucune écriture pour les abonnements SaaS. Sociétés lues dans la liste, jamais
  `settings()` (F4). Le plan de comptes vit dans `config/syscohada.php`
  (`docs/guides/comptabilite/plan-de-comptes.md`, **D2**).
- `php artisan beninlink:retours-sans-tva [--societe=] [--marchand=]` — **constate** les
  relevés émis avant S73 qui ont facturé un frais de retour **sans TVA** (D2 q.6) : frais,
  TVA manquante au taux du colis, statut. **Aucune option d'écriture**, et un test
  l'interdit : un relevé émis ne se modifie pas (D8, D9), la régularisation attend
  l'expert-comptable.
- `php artisan invoice:generate [--societe=]` — les relevés de règlement dus,
  **société par société**. Sans option, toutes les sociétés actives : c'est ce que
  fait le planificateur (13 h). La cadence reste celle du marchand
  (`merchants.payment_period`, en jours) : la commande propose, la fiche dispose.
  ⚠️ Elle bouclait sur `settings()->id` : hors requête, la règle **F4** s'applique
  ici aussi — `settings()` retombe sur la société 1, et les autres transporteurs
  n'avaient **jamais** de relevé. La société vient désormais de la liste des
  sociétés et, pour chaque relevé, du **marchand**.
  Voir `docs/guides/comptabilite/reprise-des-releves.md`.
- `php artisan test --filter RecettePiloteRepetitionTest` — la **répétition générale** de la
  recette pilote (**S71**) : la moitié serveur de 25 des 34 scénarios du guide, jouée sur le
  jeu `beninlink:pilote` par les routes des deux apps. À relancer sur la version déployée
  avant de distribuer les APK ; un rouge est un défaut du serveur, pas de l'app.
- `php artisan beninlink:comptes-amorcage` — **constate** les comptes d'amorçage du socle
  (`App\Services\Install\SeedAccounts::COMPTES`) dont le mot de passe est encore celui de son
  code source (`12345678`) ; **sort en erreur** s'il en reste. `deploy.sh` l'exécute **avant**
  de couper le site (**S87**). Ne lit aucune société, n'écrit rien.
- `php artisan beninlink:file-attente [--seuil=5]` — état de la file des envois
  (SMS, push, e-mails) et détection d'un **worker arrêté** : la panne que la file
  introduit est silencieuse. Décision **D13**.
- **Dépendances de dev** (**S79**) : `deploy.sh` installe `--no-dev`. Rien de ce qui tourne en
  production — `app/`, `config/`, `database/` (les semences comprises : `db:seed` est du
  déploiement), `routes/`, les vues — ne référence un paquet de `require-dev`. Douzième
  filet, `tests/Feature/DevDependencyLeakTest`, qui le mesure depuis `composer.lock`. La
  barre de débogage est la seule tolérance : `AppServiceProvider::registerDebugbar()`, sous
  `class_exists` **et** `app.debug`, et le paquet est dans `dont-discover`. `fakerphp/faker`
  est en `require` parce que sept semences le lisent. Avant tout ajout de paquet : il va
  dans `require` s'il est lu hors de `tests/`. ⚠️ `laravel/pint` embarque un `App\` à lui :
  un préfixe de dev qui est aussi l'un des nôtres n'est pas une fuite.
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
  Le **courriel** (S67) suit la même forme : `EmailChannel`, jumeau de `PushChannel`,
  qui met en file un `Mailable` (**D13** — le canal `mail` du socle enverrait dans la
  requête). ⚠️ Il est **sélectif par liste** (`MerchantNotification::COURRIEL`, aujourd'hui
  la seule alerte douanière) : brancher les six familles enverrait un courriel par
  changement de statut de colis. Et sa marque vient du **destinataire**, pas de
  `settings()` — l'observer peut se déclencher hors requête (**F4**).
- Toute route `/api/v10` qui **pagine** répond par `responseWithPage()` (**S78**) : l'enveloppe
  gagne un bloc `page` **à la racine** (`current`, `per_page`, `last`, `total`), `data` garde sa
  forme. Onzième filet, `tests/Feature/ApiPaginationContractTest`, qui **énumère** les méthodes
  d'API paginant directement **ou par leur dépôt** (un niveau suivi) : l'inscrire dans
  `PAGINEES`, répondre par `responseWithPage()`, documenter par `$paged` dans l'overlay et
  régénérer la spec. ⚠️ Un dépôt partagé avec les tables du back-office pagine par 10 : quatre
  routes (boutiques, hubs, fraudes, tickets) servaient dix lignes **sans le dire** — c'est ce
  filet qui l'a mesuré. Côté app, un module lit `page` par `api.getPaged()` et rend
  `{items, hasMore}` (`mobile/src/api/pagination.ts`) ; une liste affichée entière passe par
  `fetchAllPages()` ; un écran ne compare jamais une longueur à une constante.
- Les **alertes douanières d'un colis voyagent avec le colis** (**S82 / M1**) : `parcel/details/{id}`
  et `parcel/logs/{id}` portent `customs_alerts` (vide pour un colis domestique), rendu par
  `CustomsAlertResource`, portée du colis (S17). Un besoin « les alertes de X » s'ajoute à la
  ressource X déjà bornée plutôt que par un paramètre d'identifiant sur `customs/alerts`, qui
  serait une entrée de plus à prouver dans les filets. `CustomsAlertTest` le fixe.
- Un écran de **modification** relit la ressource par l'identifiant **que le formulaire envoie**
  (`$request->id`, ou le paramètre d'URL), jamais par un identifiant ambiant (**S83**) : le
  socle relisait une demande de retrait par `Auth::user()->merchant->id` — le plus souvent
  rien, parfois une AUTRE demande du marchand, jamais celle ouverte. Forme S7 : `get()`,
  `abort_if(blank(...), 404)`, puis les règles. Un dépôt gardé ne rend pas un écran juste ;
  `MerchantPayoutRequestEditTest` le prouve par la route.
- **Une installation neuve réussit son premier déploiement** (**S86**). `db:seed` crée deux
  sociétés ; `DeliveryChargeSeeder` pose le **cadre** de zones (zones, délais, forfaits CEDEAO,
  par `ZoneCatalog::installer()`) pour **chacune**, et la grille de démonstration pour la seule
  société 2 — les montants appartiennent au transporteur. Jusqu'à S86 la société 1 restait sans
  zone et `tarification-prete`, exécuté par `deploy.sh` avant de migrer, refusait **tout** premier
  déploiement ; guide et job de répétition le contournaient à la main. `FreshInstallReadinessTest`
  joue le constat sur une base qui vient d'être amorcée ; `DeploymentRehearsalTest` refuse qu'un
  `zones-tarifaires` revienne dans le job. Une société créée par une semence reçoit son cadre dans
  la semence, jamais dans un script de déploiement. ⚠️ En test, **une zone se cherche par société
  et code, jamais par code seul** (`unique(['company_id', 'code'])`) : onze fixtures
  `DeliveryZone::where('code', COTONOU)` ont trouvé la zone de la société 1 dès qu'elle en a eu.
- **Les comptes d'amorçage n'ont pas de mot de passe public hors `local` et `testing`** (**S87**).
  Une semence qui crée un compte pose `Hash::make(SeedAccounts::motDePasse($email))` et
  l'annonce par `SeedAccounts::annoncer($this->command, $email)` : le public du socle en local et
  en test, un mot de passe tiré au sort partout ailleurs, affiché une fois dans `db:seed`, gardé
  nulle part. Un compte ajouté aux semences s'inscrit dans `SeedAccounts::COMPTES`, sinon le
  constat ne le voit pas. `SeedAccountsPasswordTest` joue les semences en production simulée
  (`$this->app->detectEnvironment(fn () => 'production')`, `db:seed --force`). ⚠️ Un
  `PendingCommand` de test ne s'exécute qu'à `run()` ou à sa destruction : assigné à une
  variable, il tourne **après** les lignes qui le suivent.
- **Les deux apps ont leur filet de contrat** (**S85**). L'app marchand : `OpenApiSpecTest`
  (inventaire), `ParcelStageTest` (statuts), `MerchantAppCustomsContractTest` (pages, douane).
  L'app livreur : `DeliverymanAppContractTest` — inventaire dans la spec et jamais réservé au
  type marchand (`x-user-type`, S5), appels ↔ inventaire, les trois issues (`ApiParcelStatus`,
  `switch` de `parcelStatusUpdate`, table de l'app) identiques, aucune route livreur qui pagine,
  couverture par la répétition de recette. ⚠️ Un chemin d'API se cherche **exactement**, pas par
  préfixe : `deliveryman/parcel-status` est le début de `…-status-update`.
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
  ⚠️ `PUT super-admin/currency/update` est **exemptée** (métier, 24/09) : `currencies`
  ne porte aucune `company_id` — catalogue de **plateforme**, comme `categorys`.
  **S55 a déplacé les six routes `currency` sous `super-admin/`** (`panel:super-admin`) :
  elles vivaient sous `admin/`, et seule la **donnée** des permissions en tenait le
  locataire écarté (`currency_*` n'est que dans `SuperAdminPermission`, absent des 57
  entrées de sa table `permissions`). La garantie est désormais **structurelle**.
  `CurrencyPanelScopeTest` le prouve ; deux témoins dans `UserAndSettingsScopeTest`
  mordent si `currencies` ou `categorys` gagne une société.
  Ce filet énumère les **contrôleurs**, pas les routes : il lit la source de la méthode de
  contrôleur **et celle de la méthode de dépôt qu'elle appelle**. ⚠️ Le second niveau est
  indispensable — un contrôleur qui passe `$request` tel quel au dépôt ne lit aucun
  identifiant lui-même (`POST admin/assign-pickup/bulk`), et sort de l'énumération sans lui.
  Un test-témoin garde ce point.
- Tout ce qui tourne **hors requête HTTP** — commandes Artisan, tâches planifiées,
  jobs en file, observateurs, notifications — est inscrit dans
  `tests/Feature/OffRequestScopeCoverageTest` (**S56**) : **aucune** résolution
  ambiante du périmètre (`settings()`, `companywise()`, `Auth::`, `auth()`), ou une
  exemption motivée. ⚠️ C'est la famille **F4** : `settings()` résout par sous-domaine
  ou utilisateur connecté, et faute des deux **retombe sur la société 1** — mesuré par
  le filet. Elle a déjà mordu `invoice:generate` (seule la société 1 avait ses relevés)
  et menaçait `SendSms`. La société se prend **explicitement** : lue dans une liste,
  passée en option, ou dérivée de la **ligne** traitée.
  Les cinq autres filets énumèrent `Route::getRoutes()` : cette surface leur est
  invisible.
- Toute route **`GET` sans paramètre d'URL** qui lit un champ de la requête est inscrite
  dans `tests/Feature/SearchSurfaceCoverageTest` (**S58**) : prouvée (avec son test),
  exemptée (avec motif), ou à l'arriéré (plafond **36** à l'ouverture, 46 routes sur 203).
  ⚠️ Le trou était **structurel** : chacun des cinq filets précédents excluait cette forme
  par sa propre définition — routes à paramètre d'URL, verbes d'écriture, droits, API,
  hors requête. Défaut fondateur : `fund-transfer/search/flter/print` lisait
  `FundTransfer::whereIn('id', $request->ids)` **sans périmètre** quand son jumeau
  `bankTransactionPrint()` écrit `BankTransaction::companywise()->whereIn(...)` — la vue
  rend les **coordonnées bancaires** (numéro, banque, agence, mobile) et le nom et
  l'e-mail du titulaire, des **deux** comptes du virement.
  ⚠️ **Lu n'est pas prouvé** : une route lue et jugée correcte reste à l'arriéré tant
  qu'un test ne l'établit pas.
- ⚠️ **Un contrôle positif ne vaut que si son marqueur ne peut venir QUE d'une ligne
  de résultat.** Les écrans de filtre rendent un `<select>` des comptes portant
  `account_holder_name`, `account_no` **et** `branch_name` de tous nos comptes,
  résultat ou pas : un marqueur pris là passe au vert **avec zéro ligne rendue**
  (**S60**, prouvé par un sabotage resté vert). Vérifier aussi le format attendu :
  `Income`/`Expense::filter` lisent `date` comme une date **unique** (`strtotime`),
  une plage « …To… » y devient `1970-01-01` et ne rend rien.
- Toute **lecture nue d'un identifiant de requête** (`Model::find($request->x)`) est
  inscrite dans `tests/Feature/NakedReadCoverageTest` (**S65**, huitième filet) :
  24 occurrences, toutes classées — 19 derrière `online_payout` (**inatteignables**,
  pas correctes : rouvrir le module les rend failles), 3 flux OTP, 1 super-admin,
  1 sûre **par l'ordre**. Une garde compte si elle **précède** la lecture, porte sur
  le **même champ lu sur `$request`** (**S81** : `$fund_transfer->from_account`, la colonne
  d'un modèle déjà chargé, absolvait `Account::find($request->from_account)`), ou est une
  aide dont la **carte** (lue dans `app/Traits/`) le couvre.
- ⚠️ **`estIdentifiant()` reconnaît une CONVENTION DE NOM, pas un rôle.** `id`, `ids`,
  `*_id`, `*_ids`, depuis **S65** `key` et `slug`, et depuis **S81 (T5)** une **liste
  explicite** des dix noms libres du socle (`NOMS_LIBRES` : `account`, `from_account`,
  `to_account`, `merchant`, `merchant_account`, `editid`, `hub`, `account_head`, `merchantId`,
  `accountId`). Règle : un champ de requête qui désigne une ressource s'appelle `*_id`, ou
  s'ajoute à la liste **avec sa ressource** — jamais un troisième nom. Mesuré en l'ajoutant :
  `FundTransferRepository::update()` lisait ses deux comptes nus, le panneau marchand
  écrivait le compte de versement d'un autre marchand (`FreeNamedIdentifierScopeTest`).
- ⚠️ **Le piège du `orWhere` — `companywise()` peut être là et ne rien couvrir.**
  `companywise()->where(A)->orWhere(B)` donne `company_id = X AND A OR B` : le `OR`
  de premier niveau **sort** du périmètre (**S57**, puis **S60**). Le groupe de `OR`
  doit vivre dans une **fermeture**. Depuis **S61** un septième filet le surveille —
  `OrScopeEscapeCoverageTest` — et il redétecte le défaut de S60 si on le réintroduit ;
  l'instrument des lectures nues, lui, ne verra jamais cette famille (il cherche une
  lecture, pas une structure de requête).
- ⚠️ **Une garde ne protège que ce qu'elle NOMME, et seulement en AVAL d'elle-même.**
  L'instrument de **S57** sautait toute méthode contenant une garde — granularité
  méthode, alors que la famille poursuivie depuis **S45** est « ressource gardée,
  second identifiant nu » *dans la même méthode*. **S59** exige donc : garde **avant**
  la lecture, sur le **même champ**, et pour une aide `*HorsPerimetre()` que ce champ
  soit dans sa **carte** (lue dans `app/Traits/`, jamais recopiée). 35 → **39**, rien
  de perdu. Témoin : le `Parcel::companywise()->count()` d'un **quota d'abonnement**
  absolvait le `Merchant::find($request->merchant_id)` onze lignes plus bas.
- ⚠️ **Un marqueur de garde qui se trompe ne fait pas du bruit : il fait SILENCE.**
  Deux formes reconnaissent une portée : `companywise()` et `where('company_id', …)`
  écrit à la main (trois dépôts le font — ignorer cette seconde forme a produit
  **11 fausses alertes sur 13** en S58). Mais `'company_id' => settings()->id` n'en est
  **pas** une : c'est un **champ écrit** dans un tableau de création. Inscrite par erreur
  dans `docs/outils/instrument-lectures-nues.py`, elle y absolvait **neuf** lectures nues
  des passerelles de paiement. On ne valide un marqueur de garde qu'en regardant, une par
  une, **ce qu'il retire**.
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
- Toute route déclarée vise une **méthode de contrôleur qui existe** : neuvième filet,
  `tests/Feature/LegacyDeadCodeCleanupTest` (**S69**), qui monte les routes du locataire
  et lit chaque `Classe@methode`. Le bloc G de la cartographie avait trouvé deux routes
  PDF sur une méthode inexistante **à la main** ; `route:list` ne le signale pas.
- Le **code mort du socle** se neutralise, il ne s'efface pas (0 fichier supprimé, cf.
  `docs/guides/socle/`) : un fichier mort est rendu **juste** (S69 — `InvoicePDFSend`
  suit D13 et F4, la vue `invoice_pdf` délègue au relevé officiel), une méthode morte
  se retire. Un doublon « corps pour corps » n'est pas inoffensif : il avale les
  correctifs et les sabotages (S32).
- Une vue d'un panneau ne **nomme** pas une route de l'autre : dixième filet,
  `tests/Feature/MerchantPanelCrossLinkTest` (**S70**), qui lit les `route('…')` des vues
  de `merchant_panel/` et refuse toute URI `admin/`. S41 l'avait affirmé sans le mesurer ;
  cinq liens vivants répondaient 403. Et les scripts du back-office rejoués au panneau
  marchand lisent leurs globaux (`merchantUrl`, `hubUrl`) sous `typeof` — une
  `ReferenceError` dans un `document.ready` coupe tout ce qui suit.
- Le **relevé de règlement part par courriel à l'émission** (**R9**, S75) : `StatementMailer`,
  appelé par `InvoiceRepository::store()` **hors transaction** (D8), met `InvoicePDFSend` en file
  (D13) avec la marque de la société **du relevé** (F4). Le PDF a **un seul point de rendu**,
  `SettlementPdf` — téléchargement, lien signé et pièce jointe sont le même fichier, et un
  test refuse un second `loadView` de la vue. **Aucune route n'écrit sur un relevé émis** : une
  correction est un nouvel état, jamais un PDF remplacé (`StatementEmailTest`).
- Les **catalogues de plateforme** (`currencies` depuis S55, `categorys` depuis **S75 / R6**)
  vivent sous `super-admin/` avec `panel:super-admin` : la garantie est **structurelle**. Un
  catalogue sans `company_id` ne se monte pas sous `admin/`. Les droits `category_*` ne sont
  plus offerts au locataire (semences) ; une migration les a portés aux super-admins existants.
- La garde d'un **menu** liste exactement les droits que ses entrées lisent (**R5**, S75) :
  visibilité en **OU**, gardes d'écriture des routes inchangées. `SettingsMenuGuardTest`
  compare la garde du menu Réglages à son sous-menu — ajouter une entrée, c'est ajouter son
  droit à la garde.
- Jamais de clés en dur : `FEDAPAY_*` dans `web/.env`. La **clé d'API** des apps aussi
  (**S77 / T7**) : `config('rxcourier.api_key')` vaut `env('API_KEY')` **sans repli**, et
  `CheckApiKeyMiddleware` **refuse fermé** (clé configurée vide → 400 pour tous,
  `hash_equals`, en-tête vide refusé). Ne pas remettre de valeur par défaut « pour que ça
  marche » : c'est la clé publique du socle que ça remettrait (`ApiKeyFailClosedTest`).

## Étape 0 — cartographie (à lire AVANT de coder ici)
Le relevé des points de branchement réels vit dans **`web/CARTOGRAPHIE.md`** (blocs A-K) :
multi-tenancy par `company_id`, **absence d'abstraction de paiement**,
`WalletRepository::approved()` seul point de crédit du wallet, abonnement Stripe codé
en dur, `ParcelStatus`, API `/api/v10`. Il contient aussi les constats de sécurité et
les routes mortes relevés au passage.
