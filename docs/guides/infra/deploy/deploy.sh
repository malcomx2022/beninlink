#!/usr/bin/env bash
# Déploiement BeninLink (backend web/) — exécuté sur le VPS par GitHub Actions.
set -euo pipefail

# S76 (E2) : le même script sert la production (/var/www/beninlink) et le vhost
# de recette (/var/www/beninlink-recette). Le chemin vient du workflow, par la
# variable DEPLOY_PATH ; sans elle, c'est la production — comme avant.
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/beninlink}"
cd "$DEPLOY_PATH/web"

# ---------------------------------------------------------------------------
# Le serveur sert-il la bonne version de PHP ?
#
# `composer.json` fixe `config.platform.php` à 8.3.0 : composer résout et
# installe **comme si** la machine était en 8.3, sans jamais regarder sa vraie
# version. Sur un serveur resté en 8.2, `composer install` réussirait donc sans
# broncher, et l'application casserait au premier appel d'un paquet qui exige
# 8.3 — en production, pas ici.
#
# Ce garde est le seul maillon de la chaîne qui lise le PHP *réellement*
# exécuté : `composer.json` et le workflow ne décrivent qu'une intention.
# Il passe AVANT la coupure — mieux vaut ne pas déployer que couper le service
# pour rien — et donc avant le filet, qui n'aurait rien à remonter.
#
# ⚠️ Il a DEUX bornes, et la seconde compte autant que la première. Le verrou
# ne couvre pas 8.4 : `ezyang/htmlpurifier` s'arrête à 8.3 (`~8.3.0`), et
# `nette/utils` déclare `>=8.0 <8.4`. Or `config.platform` fait résoudre
# composer *comme si* la machine était en 8.3 : sur un serveur en 8.4,
# `composer install` réussit sans broncher. Un plancher seul laisserait donc
# passer exactement ce que ce garde existe pour arrêter — dans l'autre sens.
# Le contrôle qui tranche, lui, lit la vraie version :
# `composer check-platform-reqs --no-dev`.
# ---------------------------------------------------------------------------
php -r 'exit(PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80400 ? 0 : 1);' || {
    echo "❌ Ce serveur exécute PHP $(php -r 'echo PHP_VERSION;') ; ce dépôt s'installe en 8.3, et en 8.3 seulement." >&2
    echo "   Trop ancien : installer php8.3-fpm et ses extensions, puis basculer le pool nginx." >&2
    echo "   Trop récent : le verrou ne couvre pas 8.4 (htmlpurifier, nette/utils) — et composer," >&2
    echo "   qui résout via config.platform, ne s'en apercevra pas ; rester en 8.3." >&2
    echo "   Pour le vérifier : composer check-platform-reqs --no-dev (toutes les lignes en success)." >&2
    echo "   Rien n'a été touché : le site n'a pas été coupé." >&2
    exit 1
}

# ---------------------------------------------------------------------------
# Le .env est-il celui de cet environnement ? (S76, E2)
#
# Une recette qui porte une clé FedaPay « live » encaisse de l'argent réel. Le
# garde refuse tout .env hors production dont FedaPay est en live ou dont une
# clé est live, et avertit une production restée en sandbox. Il passe AVANT
# la coupure, pour la même raison que le garde PHP : ne pas déployer vaut
# mieux que couper pour s'arrêter ensuite. Script autonome, testé par
# `RecetteDeploymentTest`.
# ---------------------------------------------------------------------------
bash "$DEPLOY_PATH/docs/guides/infra/deploy/verifier-env.sh" .env

# ---------------------------------------------------------------------------
# Le filet : si quoi que ce soit échoue après la coupure, le site remonte.
#
# Sans lui, un échec entre `down` et `up` laisse l'application **en
# maintenance**, indéfiniment. Ce n'était qu'un risque théorique jusqu'à
# l'étape 6 de D4 : depuis le 2026-09-07, la migration qui retire les quatre
# colonnes héritées **refuse délibérément** de s'exécuter quand une société en
# dépend encore. Sans ce filet, la sécurité qu'on a mise dans la migration
# deviendrait une panne de production.
# ---------------------------------------------------------------------------
# S89 — remonter le site ne suffit pas : les gardes qui peuvent refuser un
# déploiement (`comptes-amorcage`, `tarification-prete`, `migrate`) tournent
# APRÈS `git pull` et `composer install`, parce qu'ils vivent dans la version
# déployée. Sur refus, le serveur aurait le NOUVEAU code sur l'ANCIEN schéma,
# et le site serait de nouveau servi dans cet état. Le filet revient donc à la
# révision servie avant le déploiement — tant que la migration n'a pas été
# appliquée : après elle, c'est l'ancien code qui serait faux.
REVISION_SERVIE="$(git rev-parse HEAD)"
MIGRE=0
remonter_le_site() {
    echo "❌ Déploiement interrompu — remise en service immédiate." >&2
    if [ "$MIGRE" = 0 ] && [ "$(git rev-parse HEAD)" != "$REVISION_SERVIE" ]; then
        echo "   Retour à la révision servie avant le déploiement ($REVISION_SERVIE) : le nouveau code ne reste pas sur l'ancien schéma." >&2
        git reset --hard "$REVISION_SERVIE" || true
        composer install --no-dev --optimize-autoloader --no-interaction || true
        php artisan optimize:clear || true
        php artisan config:cache || true
        php artisan route:cache || true
        php artisan view:cache || true
    elif [ "$MIGRE" = 1 ]; then
        echo "   La migration a été appliquée : le code déployé reste en place (l'ancien ne connaît pas le nouveau schéma)." >&2
    fi
    php artisan up || true
}
trap remonter_le_site ERR

php artisan down --retry=15 || true

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
# npm ci && npm run build   # si We Courier compile ses assets

# Les caches de configuration, de routes et de vues pointent encore sur
# l'ANCIEN code. Les vider avant d'exécuter la moindre commande artisan, sinon
# la vérification ci-dessous et la migration tournent sur un décor périmé.
php artisan optimize:clear

# S87 — aucun compte d'amorçage du socle ne garde le mot de passe de son code
# source (`12345678`, partagé par toutes les installations We Courier). Après la
# mise à jour du code et le vidage des caches — la commande vit dans la version
# qu'on déploie, pas dans celle du serveur (S88 l'a appris : appelée avant
# `git pull`, « Command not defined ») — et avant de migrer : une base amorcée
# avant S87 s'arrête ici, coupée puis remontée par le filet, intacte, jusqu'à
# ce que ces comptes soient changés ou supprimés.
php artisan beninlink:comptes-amorcage

# Vérifier AVANT de migrer, pas pendant. La migration de l'étape 6 est
# irréversible : si l'installation n'est pas convertie, mieux vaut s'arrêter
# ici — site remonté par le filet, base intacte — que de l'apprendre à
# mi-chemin. La commande sort en erreur tant qu'une société ne peut pas
# facturer par zones (D4).
php artisan beninlink:tarification-prete

php artisan migrate --force
MIGRE=1   # S89 — à partir d'ici, revenir à l'ancien code serait faux
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Les workers en cours tournent avec l'ANCIEN code : `queue:restart` leur
# demande de s'arrêter proprement à la fin du job courant ; supervisor les
# relance sur le nouveau (D13).
php artisan queue:restart

php artisan up
trap - ERR
echo "✅ Déploiement terminé."
