# Recette pilote — préparation des tests avec les 5 PME

> Guide opérationnel pour monter l'environnement de recette, produire les
> applications de test et dérouler les scénarios avec les PME pilotes et les
> livreurs. Mis à jour le 2026-09-09.

## 1. Environnement de recette (`web/`)

Un **sous-domaine dédié**, `recette.beninlink.app`, servi par la même configuration
Nginx que la production (`docs/guides/infra/nginx/beninlink.conf` couvre déjà
`*.beninlink.app`). Une base de données **séparée**, jamais la base de production.

| Réglage `.env` | Valeur de recette | Pourquoi |
|---|---|---|
| `APP_ENV` | `staging` | `beninlink:pilote` refuse de tourner en `production` |
| `APP_URL` | `https://recette.beninlink.app` | liens signés (PDF), retours FedaPay |
| `APP_DEBUG` | `false` | même en recette : les erreurs vont dans les journaux |
| `APP_INSTALLED` | `yes` | sans elle, `IsInstalledMiddleware` renvoie **chaque requête** vers `/install` : la recette sert l'installateur We Courier, qui propose de recréer la base |
| `API_KEY` | valeur **propre à la recette** (`php -r 'echo "blk_".bin2hex(random_bytes(16));'`) | embarquée dans les APK de recette, distincte de la production |
| `FEDAPAY_ENVIRONMENT` | `sandbox` | aucun argent réel ; clés sandbox du tableau de bord FedaPay |
| `FEDAPAY_PUBLIC_KEY` / `FEDAPAY_SECRET_KEY` / `FEDAPAY_WEBHOOK_SECRET` | clés **sandbox** | le webhook doit pointer sur `https://recette.beninlink.app/fedapay/webhook` |
| `QUEUE_CONNECTION` | `database` (ou `sync` sans worker) | depuis **D13** les envois (SMS, push, e-mails) passent par la file ; en `database`, lancer le worker (`docs/guides/infra/supervisor/`) et le surveiller avec `php artisan beninlink:file-attente` — sinon plus rien ne part, en silence |
| `MAIL_MAILER` | `log` | aucun e-mail réel n'atteint les PME pendant la recette |

Le reste du `.env` suit `docs/guides/infra/.env.example`, commenté ligne à ligne
dans `docs/guides/infra/env/`.

### Installation

Le serveur de recette a **les mêmes prérequis que la production**, et se monte
dans le même ordre : `infra/php/` (**PHP 8.3** depuis la n° 62 — `deploy.sh`
s'arrête sur un serveur en 8.2), `infra/mysql/` (base `utf8mb4_unicode_ci`,
séparée de la production), `infra/env/`, puis `infra/nginx/`. Monter la recette
sur un socle plus permissif ne prouverait rien de la production.

```bash
cd /var/www/beninlink-recette/web
composer check-platform-reqs --no-dev   # PHP 8.3 + extensions : tout doit dire success
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force            # socle We Courier (société, rôles, permissions)
php artisan beninlink:pilote           # jeu de données béninois — voir §3
php artisan config:cache && php artisan route:cache
```

Ajouter la société de recette dans `domains` (page super-admin « Sociétés ») avec le
sous-domaine `recette`, puis régler sur la page « Liquide/Fragile & TVA » le taux de
TVA (18 %) — la migration l'a déjà posé, vérifier seulement.

## 2. Applications de test (EAS)

Les deux apps ont un `eas.json` avec deux profils :

| Profil | Sortie | API | Distribution |
|---|---|---|---|
| `recette` | APK Android | `https://recette.beninlink.app/api/v10` | interne (lien EAS, QR code) |
| `production` | AAB Android | `https://beninlink.app/api/v10` | Play Store |

La **clé d'API** ne vit pas dans `eas.json` (fichier versionné) : la déclarer une fois
par app et par profil dans les variables d'environnement EAS :

```bash
cd mobile            # puis cd mobile-livreur
eas init                                 # une seule fois : lie le projet au compte EAS
eas env:create --environment preview --name EXPO_PUBLIC_API_KEY --value blk_…   # clé de recette
eas build -p android --profile recette   # → lien de téléchargement de l'APK
```

⚠️ `EXPO_PUBLIC_*` est embarqué en clair dans l'APK : la clé de recette ne protège
rien, elle filtre. Les données restent protégées par les jetons Sanctum.

Avant chaque build : `npm run typecheck && npx expo lint` dans l'app concernée.

## 3. Jeu de données béninois

```bash
php artisan beninlink:pilote                 # société du premier administrateur
php artisan beninlink:pilote --company=2     # société explicite
php artisan beninlink:pilote --reset         # repartir de zéro
```

Crée, dans la société choisie : 5 agences (Cotonou Ganhi, Cotonou Akpakpa,
Abomey-Calavi, Porto-Novo, Parakou), **le modèle par zones au complet** (voir
ci-dessous), 3 emballages, **5 PME pilotes** avec IFU, RCCM et boutique,
**3 livreurs**, et **35 colis** répartis sur les statuts (en attente, ramassage,
entrepôt, livreur assigné, livré, retour), avec leurs événements de suivi. Les
montants sont calculés par `ChargeCalculator`, comme en production.

**PIL-002 règle par portefeuille prépayé** (`wallet_use_activation`), avec une
recharge d'ouverture de **150 000 FCFA** écrite par le vrai chemin de crédit ; ses
colis sont débités à la création, comme en production. Les quatre autres PME
règlent au relevé. Les deux modes se testent donc côté recette — et sans ce
marchand, `beninlink:colis-non-debites` s'arrête sur « aucun marchand ne règle par
portefeuille », c'est-à-dire sans rien vérifier.

Comptes créés (mot de passe commun : `pilote2026`) :

| App | Identifiant | Compte |
|---|---|---|
| Marchand | `PIL-001` … `PIL-005` | Maison Kora Cosmétiques, Bio Fresh Bénin, Atelier Sênan Mode, Librairie Chabi, Électro Bio Parakou |
| Livreur | `LIV-001` … `LIV-003` | Kossi Agbodjan, Ismaël Yacoubou, Bernadette Sossou |

⚠️ Recette uniquement : la commande refuse `APP_ENV=production`.

### Ce que le jeu pose côté tarification (D4, étape 6)

Depuis l'étape 6, **un colis sans zone n'a pas de tarif du tout** : le jeu
installe donc le cadre avant les colis, par le même chemin que
`beninlink:zones-tarifaires` (`ZoneCatalog::installer()`). Une recette montée
sur l'ancien vocabulaire — « jour même / lendemain / périphérie / intérieur »
comme axes de prix — ne décrit plus le produit : le délai est devenu un
supplément, la **route** est le seul axe.

| Élément | Ce que le jeu crée |
|---|---|
| Zones | Cotonou, Périphérie, Intérieur, **CEDEAO** |
| Délais | Jour même (**+300 FCFA**), Lendemain, Standard |
| Grille nationale | 4 tranches (jusqu'à 1, 3, 5, 10 kg) × 3 zones = **12 lignes** |
| Forfaits CEDEAO | Togo 12 000 · Nigeria 18 000 · Burkina Faso 15 000 FCFA — **au pays, forfait, sans regarder le poids** |

Grille nationale, en FCFA (Cotonou / Périphérie / Intérieur) : 800 / 1 500 /
2 500 à 1 kg, 1 200 / 2 000 / 3 500 à 3 kg, 1 700 / 2 800 / 4 500 à 5 kg,
2 500 / 4 000 / 6 500 à 10 kg.

Les colis **tournent sur les trois zones nationales et sur les trois délais** :
la recette les exerce tous, pas seulement le premier. La zone CEDEAO, elle, ne
porte aucun colis du jeu — l'export se teste à la main (§4, scénario douane), et
c'est là que le forfait au pays se vérifie.

## 3 bis. Régularisations (D7, D9 et D8)

Trois commandes rattrapent ce que le code d'avant les correctifs a pu laisser en
base. Elles **constatent** par défaut et n'écrivent que sur demande explicite.

```bash
php artisan beninlink:colis-non-debites                      # constat
php artisan beninlink:colis-non-debites --marchand=13 --regulariser
php artisan beninlink:ecarts-marchands                       # constat
php artisan beninlink:ecarts-marchands --corriger
php artisan beninlink:retours-annules                        # constat
php artisan beninlink:retours-annules --societe=2 --corriger
```

Sur un jeu pilote fraîchement créé, les trois doivent répondre :

```
Aucun colis non débité : cette installation est à jour.
Aucun écart : chaque solde répond à son relevé.
Aucun retour annulé sans réversion : chaque frais prélevé est dû ou a été rendu.
```

C'est le point de départ : **un constat non vide, sur ce jeu de données, est un
vrai problème**, pas du bruit historique.

### Vérifier que les deux commandes font bien leur travail

Rejoué le 2026-09-06 sur le jeu pilote, en injectant les deux dégâts d'origine :

| Dégât injecté | Ce que le constat affiche | Après correction |
|---|---|---|
| Un colis créé sans débit du portefeuille (chemin W5 de l'API mobile) | `1 colis jamais facturés, pour 2 035 FCFA` — Bio Fresh Bénin | `1 débit(s) écrit(s)` ; portefeuille 130 565 → 128 530 F |
| Une annulation de livraison partielle qui crédite la TVA **recalculée** (216) au lieu de celle prélevée (201,60) | `Écart 14,40 · Partielles annulées : 1 colis / 14,40 · Expliqué : oui` | `1 solde(s) réaligné(s)` ; solde 14,40 → 0 |

Puis les deux commandes reviennent à « rien à régulariser ». La colonne
**Expliqué** est ce qui autorise `--corriger` : un écart qui ne correspond pas,
au centime près, aux annulations de partielles est **montré et laissé tel quel** —
il vient d'ailleurs (retrait par passerelle en ligne, fiche marchand
ré-enregistrée avec un solde d'ouverture), et c'est à un humain de trancher.

⚠️ En production, `--regulariser` et `--corriger` demandent `--force`. Régulariser
**marchand par marchand** (`--marchand=<id>`) plutôt que d'un bloc : le constat se
relit, une écriture ne se relit pas.

## 4. Scénarios de recette

Cocher chaque scénario sur un appareil réel, en réseau mobile (pas seulement en Wi-Fi).

### Marchand (app `mobile/`)
- [ ] Connexion avec `PIL-001` / `pilote2026` ; l'écran affiche l'enseigne et l'agence.
- [ ] Création d'un colis : le devis (frais, TVA 18 %, net) s'affiche avant validation ; le montant enregistré est identique au devis.
- [ ] Colis vers un pays CEDEAO avec une catégorie sensible : l'alerte douanière s'affiche ; un produit interdit est refusé ; le prix est le **forfait du pays** (Togo 12 000 F), identique quel que soit le poids saisi.
- [ ] Suivi : la timeline reflète les statuts posés par le livreur (voir ci-dessous).
- [ ] Portefeuille : recharge Mobile Money **sandbox** ; le solde ne bouge qu'après le webhook (quelques secondes), jamais au retour de page.
- [ ] Retrait : demande vers son propre compte Mobile Money ; un compte d'un autre marchand est refusé.
- [ ] Factures : ouverture du relevé PDF (lien valable 15 min).
- [ ] Notifications : un changement de statut apparaît dans le fil avec le compteur.
- [ ] Inscription d'une 6ᵉ PME : IFU (13 chiffres) et RCCM obligatoires, code SMS reçu (ou lu dans les journaux si le SMS est désactivé).
- [ ] Mot de passe oublié : lien reçu, nouveau mot de passe accepté.

### Livreur (app `mobile-livreur/`)
- [ ] Connexion avec `LIV-001` / `pilote2026` ; un identifiant marchand est refusé.
- [ ] Mes courses : les onglets En cours / Retours / Livrés correspondent au jeu de données ; Appeler et Itinéraire ouvrent le téléphone et la carte.
- [ ] Détail : marchand, adresse d'enlèvement, infos colis, destinataire, montant à encaisser.
- [ ] Livré avec photo : la photo part avec la déclaration ; le colis passe dans Livrés ; le marchand voit le statut.
- [ ] Livré avec signature : le destinataire signe au doigt (le défilement se bloque pendant le tracé) ; « Effacer » remet le canevas à blanc ; photo et signature apparaissent dans le suivi du colis côté marchand (app et administration).
- [ ] Retour marchand à consigner : la signature est-elle utile ou superflue pour vos clients ? (elle reste facultative ; l'issue décide de son maintien dans l'écran).
- [ ] Livraison partielle : montant encaissé obligatoire ; le net du colis est recalculé côté serveur.
- [ ] Retour : le colis passe dans Retours.
- [ ] Partager ma position : autorisation demandée une fois ; refus géré sans blocage.
- [ ] Gains : solde, gains, COD à reverser ; encaissements listés après une livraison.
- [ ] Changement de mot de passe, puis reconnexion.

### Administration (`web/`)
- [ ] Tableau de bord de la société : colis pilotes visibles, filtrables par statut et agence.
- [ ] Génération d'un relevé de règlement pour `PIL-001` : numérotation `PREFIXE-2026-NNNNNN`, PDF, CSV et journal SYSCOHADA.
- [ ] Reporting SaaS (super-admin) : MRR / ARR / churn ; CAC « non disponible » tant qu'aucune dépense « Marketing et acquisition clients » n'est saisie, puis calculé après saisie.
- [ ] Liste noire (fraude) : une fiche créée par `PIL-001` est visible par `PIL-002`, modifiable par `PIL-001` seulement.
- [ ] Paiements : Aamarpay et SSLCommerz absents des réglages et des écrans (S21).

### Sécurité (à rejouer après chaque livraison)
- [ ] Un jeton marchand sur `/api/v10/deliveryman/dashboard` → 403 ; un jeton livreur sur `/api/v10/parcel/index` → 403.
- [ ] `parcel/details/{id}` d'un colis d'une autre PME → 404.
- [ ] `php artisan test` vert sur la version déployée (`web/`).
- [ ] `php artisan beninlink:colis-non-debites`, `beninlink:ecarts-marchands` et `beninlink:retours-annules` : trois constats vides (voir §3 bis). ⚠️ Ces trois-là se **lisent** : les deux premières sortent en **succès même quand elles trouvent des écarts** (`infra/supervision/`). Cocher sur le texte affiché, jamais sur le code de sortie.
- [ ] `php artisan beninlink:tarification-prete` : sort en **succès** — chaque société peut facturer (**D4**, étape 6).
- [ ] Un colis créé **sans zone** est refusé sur le champ, à l'écran comme par l'API : depuis l'étape 6, la route est le seul axe de tarification.
- [ ] Import Excel : le fichier modèle porte la colonne `zone_code`, et un fichier sans elle n'importe rien.
- [ ] `php artisan beninlink:file-attente` : file traitée, worker vivant (D13). Celle-ci, au contraire, **s'écoute** : sa sortie 1 veut dire « le worker est arrêté », et c'est l'une des trois seules commandes branchables sur une alerte.

## 5. Critères de sortie de recette

- Les scénarios marchand et livreur passent sur **au moins deux téléphones Android**
  différents, en réseau mobile.
- Aucune anomalie bloquante ouverte ; les anomalies mineures sont consignées avec
  l'écran, l'identifiant du compte et le numéro de suivi du colis.
- Les 5 PME ont chacune créé au moins un colis réel de bout en bout (création →
  livraison → relevé).
- Le webhook FedaPay sandbox a crédité au moins une recharge par PME.

## 6. Collecte des retours

Un tableau partagé par PME avec, pour chaque retour : date, app et écran, ce qui
était attendu, ce qui s'est passé, gravité (bloquant / gênant / cosmétique), capture.
Les retours se traitent par PR sur `main`, comme les chantiers.
