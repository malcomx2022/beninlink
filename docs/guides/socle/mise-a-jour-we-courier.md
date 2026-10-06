# Mettre à jour le socle We Courier

> On ne « met pas à jour » ce socle : on le **re-fusionne**. Ce guide dit avec
> quoi comme base de fusion, où tomberont les conflits, ce qu'il ne faut jamais
> laisser l'éditeur réécrire, et ce qui **prouve** que la fusion n'a rien défait.
> Écrit le 2026-09-11, sur `main` (`a3f68cb`).

La règle du projet est l'appropriation, pas la réécriture : la valeur s'ajoute
**autour** de We Courier. La conséquence se paie ici, le jour où l'éditeur publie
une nouvelle version.

## La bonne nouvelle : la base de fusion existe déjà

Le **premier commit du dépôt**, `eb56a37` (6 août 2026), porte le socle
**intact**. Vérifié plutôt que supposé : à ce commit, `config/payments.php`
n'existe pas, `app/Console/Commands/` ne contient que les deux commandes de
l'éditeur (`DatabaseAutoBackup`, `Invoice`), et `lang/fr` compte ses 76 fichiers
d'origine — l'éditeur livrait déjà un français, que nous avons corrigé (16
fichiers) et complété (11 de plus, 87 aujourd'hui).

Une montée de version est donc une **fusion à trois points**, celle que git sait
faire :

| Côté | Ce que c'est |
|---|---|
| **base** | `eb56a37` — le socle tel que reçu |
| **leur côté** | le nouveau paquet de l'éditeur, importé tel quel |
| **notre côté** | `main` |

Sans cette base, il n'y aurait que le choix entre écraser nos correctifs et
recopier les leurs à la main. Avec elle, git sait distinguer « l'éditeur a
changé cette ligne » de « nous l'avons changée ».

## La carte des conflits, avant de commencer

Ce que nous avons touché depuis le socle d'origine, mesuré sur l'arbre :

| | Fichiers |
|---|---|
| Fichiers du socle **modifiés** | **238** |
| Fichiers **ajoutés** | **196** |
| Fichiers **supprimés** | **0** |

Le zéro n'est pas un hasard, c'est la règle du projet devenue mesurable : rien
n'a été retiré du socle, tout a été corrigé sur place ou ajouté à côté.

Où vivent les 238 :

| Dossier | Fichiers modifiés |
|---|---|
| `resources/views` | 95 |
| `app/Http` | 53 |
| `app/Repositories` | 20 |
| `lang/fr` | 16 modifiés, 11 ajoutés — 76 fichiers d'origine, 87 aujourd'hui |
| `database/seeders` | 9 |
| `lang/en` | 8 |
| `app/Models` | 6 |
| `public/backend`, `config` | 4 chacun |
| `routes`, `app/Mail`, `app/Console` | 3 chacun |

**Lecture.** Les conflits se concentreront dans les **vues** et la **couche
HTTP** — exactement là où l'éditeur change aussi le plus d'une version à
l'autre. Attendez-vous à une vraie fusion, pas à une avance rapide.

**Et une bonne surprise :** nos **196 fichiers ajoutés ne seront pas touchés**.
Ils n'existent ni dans la base ni du côté de l'éditeur : une fusion à trois
points n'a aucune raison d'y toucher. Tout ce qui est propre à BeninLink —
services, commandes, tests — traverse la montée sans un conflit.

## La méthode

```bash
cd /home/vous/beninlink

# 1. Une branche qui ne contient QUE le socle de l'éditeur, posée sur la base.
git checkout -b socle-vX eb56a37
rm -rf web
cp -R /chemin/vers/le-paquet-decompresse web
git add -A web
git commit -m "socle We Courier vX (import brut, sans retouche)"

# 2. La fusion, sur une branche de travail — jamais sur main.
git checkout -b chantier-socle-vX main
git merge socle-vX
```

⚠️ **Importer le paquet sans le retoucher.** La tentation est d'« arranger » deux
ou trois choses en passant : c'est exactement ce qui rend la fusion suivante
impossible. Cette branche est une photographie de l'éditeur, rien d'autre.

⚠️ **Les fichiers que l'éditeur a supprimés** apparaîtront comme des suppressions
de son côté, et git proposera de les retirer aussi. C'est correct — mais à
relire : si l'un d'eux est un fichier que nous avons modifié, la suppression
emporte notre correctif.

## Les fichiers à ne jamais laisser l'éditeur réécrire

Ceux-là portent une décision actée. Si la version de l'éditeur gagne, rien
n'échoue — et c'est le problème.

| Fichier | Ce qui disparaît si l'éditeur gagne |
|---|---|
| `app/Console/Kernel.php` | la sauvegarde du socle **redevient planifiée** : une tâche échoue chaque nuit, dans `/dev/null` |
| `app/Console/Commands/DatabaseAutoBackup.php` | le dump SQL non échappé, expédié **par courriel**, revient — et ne se révèle inutilisable qu'à la restauration |
| `app/Console/Commands/Invoice.php` | le cron ne génère plus les relevés que pour la **société 1** |
| `app/Repositories/Invoice/InvoiceRepository.php` | idem, plus la facture **à moitié remplie** quand la boucle échoue (pas de transaction) |
| `app/Repositories/Wallet/WalletRepository.php` | le crédit du portefeuille perd son **idempotence** |
| `app/Repositories/Parcel/{ParcelRepository,ParcelInterface}.php` | la tarification **par zones** (D4) et le débit à la création |
| `app/Repositories/PushNotification/PushNotificationRepository.php` | l'API FCM **arrêtée par Google en 2024** revient, bloquante, avec son `die()` (D11) |
| `public/firebase-messaging-sw.js` | les navigateurs des agents se réinscrivent au projet Firebase **de l'éditeur** (D12) |
| `config/app.php` | `app_installed`, et la locale **FR** |
| `config/rxcourier.php`, `config/merchantpayment.php` | la clé d'API propre à l'installation, et les passerelles coupées (S21) |
| `database/seeders/` | les **deux seeders qui ne compilaient plus**, et la grille zonée |
| les gardes de locataire (douze endroits, entre `app/Http/` et `app/Repositories/`) | `find($id)` nu redevient la règle, et un administrateur atteint la ressource d'un autre transporteur |

`config/payments.php` n'est pas dans la liste : il est **ajouté**, donc hors
conflit. Mais ses appelants, eux, sont des fichiers du socle modifiés.

## Ce qui prouve que la fusion n'a rien défait

C'est le rôle de la suite, et c'est la seule raison pour laquelle cette montée
est tenable.

```bash
cd web
# AVANT la fusion — pour savoir d'où l'on part
vendor/bin/phpunit                       # doit être vert

# APRÈS la fusion
composer install --no-dev --optimize-autoloader
composer check-platform-reqs --no-dev    # l'éditeur a pu bouger le verrou
vendor/bin/phpunit
php artisan openapi:generate             # puis `git diff` : le contrat a-t-il bougé ?
```

**Un test qui rougit après une fusion n'est pas une flake : il nomme ce que la
version de l'éditeur vient de défaire.** Quelques-uns parlent plus fort que les
autres :

| Test | Ce qu'il rattrape |
|---|---|
| `IsolationCoverageTest` | une route `/api/v10` à identifiant qui a perdu son scope |
| l'analyse syntaxique des *seeders* | un seeder redevenu non exécutable |
| `InvoiceGenerateTenantTest` | la génération des relevés retombée sur la société 1 |
| les tests de refus de la migration (étape 6) | une migration de l'éditeur qui remet les colonnes héritées |
| les étapes comptables (D8) | une écriture qui accepte d'être rejouée |

Et après la fusion, avant tout déploiement : `beninlink:tarification-prete` et
les trois constats (`infra/reprise/`, §8). Une montée de socle est exactement le
genre d'événement après lequel un constat non vide n'est pas du bruit.

## Les migrations de l'éditeur se lisent une par une

C'est le seul endroit où la prudence ne peut pas être déléguée à un test. Le
nouveau paquet apportera ses propres migrations, et elles peuvent **remettre des
colonnes que l'étape 6 a retirées** (`same_day`, `next_day`, `sub_city`,
`outside_city`) ou renommer ce sur quoi la tarification par zones repose.

À lire avant `migrate`, et à écarter si elles touchent la grille. La migration de
l'étape 6, elle, refusera de se jouer si une société n'est pas convertie — voir
`infra/reprise/`, qui dit aussi pourquoi `migrate:rollback` n'est **pas** la
sortie de secours qu'on croit.

## Faut-il monter ? — la question qu'on ne pose pas assez

> **Tranché le 2026-10-06 (S107, D15) : on ne monte pas.** Le fork est le produit. La grille
> ci-dessous se relit à chaque version de l'éditeur ; seul un correctif **qui nous manque** sur
> ce que nous utilisons rouvre la question, et la méthode de ce guide est alors prête.

Sur un fork aussi modifié, « rester à jour » n'est pas une vertu en soi. Le
paquet de l'éditeur apporte trois sortes de choses :

1. **Des correctifs que nous avons déjà faits**, souvent autrement — onze
   constats de revue, cinq défauts trouvés depuis.
2. **Des fonctionnalités que nous n'utilisons pas** : le module de paiement en
   ligne est coupé à dessein (**D10**), Aamarpay et SSLCommerz aussi (**S21**).
3. **Des correctifs qui nous manquent** — et ceux-là seuls justifient la montée.

Lisez le journal des modifications de l'éditeur **contre la liste ci-dessus**.
Si rien n'y touche ce que nous utilisons, ne montez pas : le coût est une vraie
fusion sur 238 fichiers, concentrée dans les vues, et chaque fusion est une
occasion de défaire un correctif en silence.

Quand vous montez, montez **sur une branche, avec la suite avant et après**, et
passez par la recette (`recette-pilote/`) avant la production. Une montée de
socle n'est pas un correctif : c'est un chantier.
