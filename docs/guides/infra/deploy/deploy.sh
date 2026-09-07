#!/usr/bin/env bash
# Déploiement BeninLink (backend web/) — exécuté sur le VPS par GitHub Actions.
set -euo pipefail
cd /var/www/beninlink/web

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
remonter_le_site() {
    echo "❌ Déploiement interrompu — remise en service immédiate." >&2
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

# Vérifier AVANT de migrer, pas pendant. La migration de l'étape 6 est
# irréversible : si l'installation n'est pas convertie, mieux vaut s'arrêter
# ici — site remonté par le filet, base intacte — que de l'apprendre à
# mi-chemin. La commande sort en erreur tant qu'une société ne peut pas
# facturer par zones (D4).
php artisan beninlink:tarification-prete

php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Les workers en cours tournent avec l'ANCIEN code : `queue:restart` leur
# demande de s'arrêter proprement à la fin du job courant ; supervisor les
# relance sur le nouveau (D13).
php artisan queue:restart

php artisan up
trap - ERR
echo "✅ Déploiement terminé."
