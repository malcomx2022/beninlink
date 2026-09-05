# Recette pilote — préparation des tests avec les 5 PME

> Guide opérationnel pour monter l'environnement de recette, produire les
> applications de test et dérouler les scénarios avec les PME pilotes et les
> livreurs. Mis à jour le 2026-09-05.

## 1. Environnement de recette (`web/`)

Un **sous-domaine dédié**, `recette.beninlink.app`, servi par la même configuration
Nginx que la production (`docs/guides/infra/nginx/beninlink.conf` couvre déjà
`*.beninlink.app`). Une base de données **séparée**, jamais la base de production.

| Réglage `.env` | Valeur de recette | Pourquoi |
|---|---|---|
| `APP_ENV` | `staging` | `beninlink:pilote` refuse de tourner en `production` |
| `APP_URL` | `https://recette.beninlink.app` | liens signés (PDF), retours FedaPay |
| `APP_DEBUG` | `false` | même en recette : les erreurs vont dans les journaux |
| `API_KEY` | valeur **propre à la recette** (`php -r 'echo "blk_".bin2hex(random_bytes(16));'`) | embarquée dans les APK de recette, distincte de la production |
| `FEDAPAY_ENVIRONMENT` | `sandbox` | aucun argent réel ; clés sandbox du tableau de bord FedaPay |
| `FEDAPAY_PUBLIC_KEY` / `FEDAPAY_SECRET_KEY` / `FEDAPAY_WEBHOOK_SECRET` | clés **sandbox** | le webhook doit pointer sur `https://recette.beninlink.app/fedapay/webhook` |
| `QUEUE_CONNECTION` | `redis` (ou `sync` faute de Redis) | le socle envoie SMS et e-mails dans la requête ; en recette, `sync` suffit |
| `MAIL_MAILER` | `log` | aucun e-mail réel n'atteint les PME pendant la recette |

Le reste du `.env` suit `docs/guides/infra/.env.example`.

### Installation

```bash
cd /var/www/beninlink-recette/web
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
Abomey-Calavi, Porto-Novo, Parakou), un barème FCFA par tranche de poids (1, 3, 5,
10 kg × jour même / lendemain / périphérie / intérieur), 3 emballages, **5 PME
pilotes** avec IFU, RCCM et boutique, **3 livreurs**, et **35 colis** répartis sur les
statuts (en attente, ramassage, entrepôt, livreur assigné, livré, retour), avec
leurs événements de suivi. Les montants sont calculés par `ChargeCalculator`, comme
en production.

Comptes créés (mot de passe commun : `pilote2026`) :

| App | Identifiant | Compte |
|---|---|---|
| Marchand | `PIL-001` … `PIL-005` | Maison Kora Cosmétiques, Bio Fresh Bénin, Atelier Sênan Mode, Librairie Chabi, Électro Bio Parakou |
| Livreur | `LIV-001` … `LIV-003` | Kossi Agbodjan, Ismaël Yacoubou, Bernadette Sossou |

⚠️ Recette uniquement : la commande refuse `APP_ENV=production`.

## 4. Scénarios de recette

Cocher chaque scénario sur un appareil réel, en réseau mobile (pas seulement en Wi-Fi).

### Marchand (app `mobile/`)
- [ ] Connexion avec `PIL-001` / `pilote2026` ; l'écran affiche l'enseigne et l'agence.
- [ ] Création d'un colis : le devis (frais, TVA 18 %, net) s'affiche avant validation ; le montant enregistré est identique au devis.
- [ ] Colis vers un pays CEDEAO avec une catégorie sensible : l'alerte douanière s'affiche ; un produit interdit est refusé.
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
