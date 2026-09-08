#!/usr/bin/env bash
# Sauvegarde BeninLink — base, fichiers téléversés, .env.
# À lancer sous l'utilisateur `deploy`. Marche à suivre : README.md
set -euo pipefail

RACINE=/var/www/beninlink/web
DESTINATION="${1:-/var/backups/beninlink}"
RETENTION_JOURS="${RETENTION_JOURS:-14}"
HORODATAGE=$(date -u +%Y%m%d-%H%M%S)

# ---------------------------------------------------------------------------
# Les identifiants viennent du .env, jamais de la ligne de commande : un
# `-p<mot de passe>` est visible de tout le serveur dans `ps aux`, le temps que
# la commande tourne. On les passe par un fichier temporaire que seul le
# propriétaire peut lire, supprimé à la sortie quoi qu'il arrive.
# ---------------------------------------------------------------------------
lire_env() { grep -E "^$1=" "$RACINE/.env" | head -1 | cut -d= -f2- | tr -d '"'"'"'"'; }

BASE=$(lire_env DB_DATABASE)
UTILISATEUR=$(lire_env DB_USERNAME)
MOT_DE_PASSE=$(lire_env DB_PASSWORD)
HOTE=$(lire_env DB_HOST); HOTE=${HOTE:-127.0.0.1}
PORT=$(lire_env DB_PORT);  PORT=${PORT:-3306}

IDENTIFIANTS=$(mktemp)
chmod 600 "$IDENTIFIANTS"
nettoyer() { rm -f "$IDENTIFIANTS"; }
trap nettoyer EXIT
printf '[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n' \
    "$UTILISATEUR" "$MOT_DE_PASSE" "$HOTE" "$PORT" > "$IDENTIFIANTS"

mkdir -p "$DESTINATION"
# 700 : la sauvegarde contient des pièces d'identité de marchands
# (`uploads/merchant/nid`), des numéros de téléphone et les clés FedaPay.
chmod 700 "$DESTINATION"

echo "→ Base $BASE"
mysqldump --defaults-extra-file="$IDENTIFIANTS" \
          --single-transaction --routines --triggers --events \
          "$BASE" | gzip > "$DESTINATION/base-$HORODATAGE.sql.gz"

echo "→ Fichiers téléversés"
# `public/uploads/` n'est PAS dans git (chaque sous-dossier porte un .gitignore
# `*` + `!.gitignore`). Ces fichiers n'existent donc QUE sur ce serveur —
# dont `parcel/signature/`, les preuves de livraison signées.
tar -czf "$DESTINATION/fichiers-$HORODATAGE.tar.gz" -C "$RACINE" public/uploads

echo "→ .env"
# Non versionné par construction. Sans lui, une restauration redémarre sur des
# clés FedaPay vides : les webhooks sont refusés et aucun wallet n'est crédité.
cp "$RACINE/.env" "$DESTINATION/env-$HORODATAGE"
chmod 600 "$DESTINATION/env-$HORODATAGE"

echo "→ Purge au-delà de $RETENTION_JOURS jours"
find "$DESTINATION" -maxdepth 1 -type f \
     \( -name 'base-*.sql.gz' -o -name 'fichiers-*.tar.gz' -o -name 'env-*' \) \
     -mtime +"$RETENTION_JOURS" -delete

echo
echo "✅ $HORODATAGE"
du -h "$DESTINATION"/*-"$HORODATAGE"* 2>/dev/null || true
echo
echo "⚠️  Une sauvegarde qui reste sur ce serveur ne protège pas de sa perte."
echo "   La copier ailleurs, et lire README.md § « L'exercice de restauration »."
