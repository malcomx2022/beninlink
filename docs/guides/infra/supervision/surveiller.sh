#!/usr/bin/env bash
# Exécute une commande de constat et alerte SI ELLE ÉCHOUE — pas autrement.
#
#   surveiller.sh "beninlink:file-attente" [arguments artisan…]
#
# Destinataire dans ALERTE_MAIL (variable d'environnement ou crontab).
# Marche à suivre et liste des commandes surveillables : README.md
set -uo pipefail          # PAS -e : on VEUT récupérer le code de sortie

RACINE=/var/www/beninlink/web
DESTINATAIRE="${ALERTE_MAIL:-}"
COMMANDE="$1"; shift

SORTIE=$(cd "$RACINE" && php artisan "$COMMANDE" "$@" 2>&1)
CODE=$?

# Le silence en cas de succès n'est pas une élégance : une alerte qui arrive
# tous les jours cesse d'être lue en une semaine.
if [ "$CODE" -eq 0 ]; then
    exit 0
fi

echo "$SORTIE"     # va dans le journal du cron, quoi qu'il arrive

if [ -n "$DESTINATAIRE" ] && command -v mail >/dev/null 2>&1; then
    printf '%s\n' "$SORTIE" | mail -s "[BeninLink] $COMMANDE — sortie $CODE" "$DESTINATAIRE"
elif [ -n "$DESTINATAIRE" ]; then
    echo "⚠️ ALERTE_MAIL est défini mais la commande « mail » est absente." >&2
    echo "   Installer bsd-mailx / mailutils, ou remplacer cette branche par un" >&2
    echo "   appel à la sonde de l'hébergeur." >&2
fi

exit "$CODE"
