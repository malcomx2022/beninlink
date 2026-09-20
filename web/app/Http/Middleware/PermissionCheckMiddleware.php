<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Garde d'accès des routes du back-office : `hasPermission:x`.
 *
 * **S36** l'a étendu sur trois points, sans toucher à ce qu'il faisait déjà —
 * les 197 déclarations existantes se comportent exactement comme avant.
 *
 * 1. **Plusieurs permissions.** Il n'en acceptait qu'une, et certaines routes
 *    sont des aides AJAX partagées par des écrans aux droits différents :
 *    `expense/search-account/{id}` est appelée par l'écran des dépenses, celui
 *    des revenus, celui des salaires **et** le panneau du chef de hub. Aucune
 *    permission unique ne conviendrait — `expense_create` fermerait
 *    l'encaissement livreur à tous les chefs de hub. La forme
 *    `hasPermission:a|b|c` passe si l'utilisateur porte **l'une** d'elles.
 *
 * 2. **`permissions` à `null`.** `in_array($p, null)` lève une `TypeError` en
 *    PHP 8 : un compte dont le jeu de permissions est nul — et il y en a, le
 *    `fillable` du socle ne l'exige pas — recevait une **erreur 500** sur
 *    chaque route gardée, au lieu d'un refus. `(array)` et le défaut `[]` le
 *    ferment.
 *
 * 3. **Le refus d'un appel AJAX.** Le socle répondait par `redirect('/')` —
 *    donc **200 avec le HTML du tableau de bord** dans le gestionnaire de
 *    succès d'un `$.ajax`. Un refus ne doit pas ressembler à une réponse. Les
 *    appels AJAX et JSON reçoivent **403** ; la navigation de page garde la
 *    redirection, parce que la changer toucherait les 197 autres déclarations.
 *
 * ⚠️ L'`abort('403')` qui suivait le `return redirect('/')` était du **code
 * mort** — jamais atteint. Il est retiré, et le 403 est posé là où il sert.
 */
class PermissionCheckMiddleware
{
    public function handle(Request $request, Closure $next, $permission = null)
    {
        if (Auth::check() && $this->porte(Auth::user()->permissions, $permission)) {
            return $next($request);
        }

        // Un refus adressé à du JavaScript doit se lire comme un refus.
        if ($request->ajax() || $request->expectsJson()) {
            abort(403);
        }

        return redirect('/');
    }

    /**
     * L'utilisateur porte-t-il la permission demandée — ou **l'une** d'elles ?
     *
     * @param  mixed  $portees  le jeu de l'utilisateur, potentiellement `null`
     * @param  string|null  $demandees  `x`, ou `a|b|c`
     */
    private function porte($portees, $demandees): bool
    {
        if (blank($demandees)) {
            return false;
        }

        $portees = array_filter((array) $portees, 'is_string');

        foreach (explode('|', $demandees) as $demandee) {
            $demandee = trim($demandee);

            if ($demandee !== '' && in_array($demandee, $portees, true)) {
                return true;
            }
        }

        return false;
    }
}
