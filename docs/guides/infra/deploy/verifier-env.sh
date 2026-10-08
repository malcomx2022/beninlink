#!/usr/bin/env bash
# Garde du `.env` avant déploiement — BeninLink (S76, E2 ; S77, T7).
#
# Usage : verifier-env.sh <chemin du .env>
#
# Deux risques qu'il ferme :
# - une recette (APP_ENV=staging) qui encaisse de l'argent réel parce qu'une clé
#   FedaPay « live » a été recopiée depuis la production. `FedaPayGateway` lit
#   FEDAPAY_ENVIRONMENT (live / sandbox) ; FedaPay préfixe ses clés par `_live_`
#   ou `_sandbox_`. Les deux sont vérifiés : l'un peut être juste et l'autre faux ;
# - (S77) un `.env` sans API_KEY, ou avec la clé publique du socle We Courier.
# - (S88) un `.env` sans APP_INSTALLED=yes : le site ne servirait que l'installateur.
#   Depuis S77 la config n'a plus de repli : sans clé, `CheckApiKeyMiddleware`
#   refuse toute requête d'API et les deux apps sont coupées. Mieux vaut le lire
#   ici, avant la coupure du site, que dans les téléphones.
#
# Sorties :
#   0 — le .env est cohérent (un avertissement peut être imprimé) ;
#   1 — refus : API_KEY absente ou égale à la clé publique du socle ; hors
#       production avec FedaPay en live, ou une clé live ; ou .env absent.
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
api_key="$(lire API_KEY)"
fedapay_env="$(lire FEDAPAY_ENVIRONMENT)"
cles="$(lire FEDAPAY_PUBLIC_KEY) $(lire FEDAPAY_SECRET_KEY) $(lire FEDAPAY_WEBHOOK_SECRET)"

# S77 (T7) — la clé d'API, dans tous les environnements.
if [ -z "$api_key" ]; then
    echo "❌ API_KEY absente du .env : sans elle l'API refuse toutes les requêtes (plus de repli depuis S77), les apps seraient coupées." >&2
    echo "   Générer : php -r 'echo \"blk_\".bin2hex(random_bytes(16)), PHP_EOL;' puis la poser dans API_KEY et dans EXPO_PUBLIC_API_KEY des apps. Rien n'a été touché." >&2
    exit 1
fi
if [ "$api_key" = "123456rx-ecourier123456" ]; then
    echo "❌ API_KEY vaut la clé publique du socle We Courier, connue de toutes ses installations : à remplacer par une clé propre. Rien n'a été touché." >&2
    exit 1
fi

# S88 — le drapeau d'installation, dans tous les environnements. Sans lui le site
# ne monte que l'installateur, dont l'action finale recrée la base ; la première
# installation par `migrate` + `db:seed` ne l'écrit pas, seul l'installateur web
# le faisait.
app_installed="$(lire APP_INSTALLED)"
if [ "$app_installed" != "yes" ]; then
    echo "❌ APP_INSTALLED n'est pas « yes » (${app_installed:-absent}) : le site ne servirait que l'installateur, et son action finale recrée la base (S88)." >&2
    echo "   Après migrate + db:seed, poser APP_INSTALLED=yes dans le .env. Rien n'a été touché." >&2
    exit 1
fi

# S97 — le cache doit être partagé entre requêtes. Le limiteur de connexion
# (S96) compte les essais dans le cache : avec CACHE_DRIVER=array (ou null)
# chaque requête repart de zéro et la force brute passe ; les verrous et les
# compteurs du socle ne tiendraient pas davantage. `file` (défaut) ou
# `database` conviennent sur un serveur ; absent vaut `file`.
cache_driver="$(lire CACHE_DRIVER)"
if [ "$cache_driver" = "array" ] || [ "$cache_driver" = "null" ]; then
    echo "❌ CACHE_DRIVER=${cache_driver} : le limiteur de connexion (S96) ne compterait rien, chaque requête repartirait de zéro — la force brute passerait." >&2
    echo "   Mettre CACHE_DRIVER=file (ou database), puis php artisan config:clear. Rien n'a été touché." >&2
    exit 1
fi

# S99 — le mode debug en production. Une page d'erreur de Laravel en debug montre
# la pile, la requête et des variables d'environnement (clés, mot de passe de
# base) à qui provoque l'erreur. Refusé en production ; en recette, dit mais
# laissé (une recette se débogue). Absent vaut false.
app_debug="$(lire APP_DEBUG)"
if [ "$app_env" = "production" ] && [ "$app_debug" = "true" ]; then
    echo "❌ APP_ENV=production mais APP_DEBUG=true : une page d'erreur montrerait la pile et des variables d'environnement à n'importe quel visiteur." >&2
    echo "   Mettre APP_DEBUG=false, puis php artisan config:clear. Rien n'a été touché." >&2
    exit 1
fi

# S132 — le cookie de session sans `secure` part aussi sur une requête en clair (le premier appel
# en http avant la redirection, un lien ancien) : qui écoute le réseau reprend la session. Absent,
# il vaut `secure` dès que APP_URL est en https (config/session.php) ; `false` est refusé en production.
session_secure="$(lire SESSION_SECURE_COOKIE)"
if [ "$app_env" = "production" ] && [ "$session_secure" = "false" ]; then
    echo "❌ APP_ENV=production mais SESSION_SECURE_COOKIE=false : le cookie de session partirait sur une connexion en clair." >&2
    echo "   Retirer la ligne (elle suit alors APP_URL en https) ou mettre SESSION_SECURE_COOKIE=true, puis php artisan config:clear. Rien n'a été touché." >&2
    exit 1
fi

if [ "$app_env" != "production" ]; then
    if [ "$app_debug" = "true" ]; then
        echo "⚠️  APP_ENV=${app_env:-vide} avec APP_DEBUG=true : les pages d'erreur montrent la pile et l'environnement — acceptable en recette, jamais en production." >&2
    fi
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
