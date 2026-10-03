#!/usr/bin/env bash
# Garde du `.env` avant déploiement — BeninLink (S76, E2).
#
# Usage : verifier-env.sh <chemin du .env>
#
# Le risque qu'il ferme : une recette (APP_ENV=staging) qui encaisse de l'argent
# réel parce qu'une clé FedaPay « live » a été recopiée depuis la production.
# `FedaPayGateway` lit FEDAPAY_ENVIRONMENT (live / sandbox) ; FedaPay préfixe
# ses clés par `_live_` ou `_sandbox_`. Les deux sont vérifiés : l'un peut être
# juste et l'autre faux.
#
# Sorties :
#   0 — le .env est cohérent (un avertissement peut être imprimé) ;
#   1 — refus : hors production avec FedaPay en live, ou une clé live ; ou .env absent.
#
# Il s'exécute AVANT `php artisan down` dans deploy.sh : mieux vaut ne pas
# déployer que couper le site pour s'arrêter ensuite. Il est autonome pour
# qu'un test puisse l'exercer sur des fichiers fabriqués.
set -euo pipefail

fichier="${1:-}"
if [ -z "$fichier" ] || [ ! -f "$fichier" ]; then
    echo "❌ Aucun .env à vérifier (${fichier:-chemin manquant}) : rien n'est déployé." >&2
    exit 1
fi

# Lit la valeur d'une clé, sans guillemets ni espaces, vide si absente.
lire() {
    # `|| true` : une clé absente n'est pas une erreur (grep rend 1 sans ligne).
    { grep -E "^[[:space:]]*$1=" "$fichier" || true; } | tail -n 1 | sed -E "s/^[[:space:]]*$1=//; s/^[\"']//; s/[\"'][[:space:]]*$//; s/[[:space:]]+$//"
}

app_env="$(lire APP_ENV)"
fedapay_env="$(lire FEDAPAY_ENVIRONMENT)"
cles="$(lire FEDAPAY_PUBLIC_KEY) $(lire FEDAPAY_SECRET_KEY) $(lire FEDAPAY_WEBHOOK_SECRET)"

if [ "$app_env" != "production" ]; then
    if [ "$fedapay_env" = "live" ]; then
        echo "❌ APP_ENV=${app_env:-vide} mais FEDAPAY_ENVIRONMENT=live : une recette encaisserait de l'argent réel." >&2
        echo "   Mettre FEDAPAY_ENVIRONMENT=sandbox et les clés sandbox du tableau de bord FedaPay. Rien n'a été touché." >&2
        exit 1
    fi
    if printf '%s' "$cles" | grep -q "_live_"; then
        echo "❌ APP_ENV=${app_env:-vide} mais une clé FedaPay « live » est dans le .env : une recette encaisserait de l'argent réel." >&2
        echo "   Remplacer par les clés sandbox. Rien n'a été touché." >&2
        exit 1
    fi
    echo "✅ .env hors production (${app_env:-vide}) : FedaPay en sandbox, aucune clé live."
    exit 0
fi

if [ "$fedapay_env" != "live" ]; then
    echo "⚠️  APP_ENV=production mais FEDAPAY_ENVIRONMENT=${fedapay_env:-vide} : les paiements ne seront pas réels. Déploiement poursuivi." >&2
fi
echo "✅ .env de production vérifié."
exit 0
