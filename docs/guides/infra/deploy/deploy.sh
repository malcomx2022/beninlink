#!/usr/bin/env bash
# Déploiement BeninLink (backend web/) — exécuté sur le VPS par GitHub Actions.
set -euo pipefail
cd /var/www/beninlink/web
php artisan down --retry=15 || true
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
# npm ci && npm run build   # si We Courier compile ses assets
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
