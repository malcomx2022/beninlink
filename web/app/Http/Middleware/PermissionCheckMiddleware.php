<?php

namespace App\Http\Middleware;

use Brian2694\Toastr\Facades\Toastr;
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
 * 3. **Le refus d'un appel AJAX.** Le socle répondait par `redirect('/')` — donc
 *    **200 avec du HTML** dans le gestionnaire de succès d'un `$.ajax`. Un refus
 *    ne doit pas ressembler à une réponse. Les appels AJAX et JSON reçoivent
 *    **403**.
 *
 * ⚠️ L'`abort('403')` qui suivait le `return redirect('/')` était du **code
 * mort** — jamais atteint. Il est retiré, et le 403 est posé là où il sert.
 *
 * **S39** a fini le travail que S36 avait laissé de côté : la **destination**
 * d'un refus de navigation de page.
 *
 * Le socle renvoyait vers `/`. Mesuré : sur un domaine de locataire, `/` n'est
 * pas le tableau de bord mais la **page publique du site** — elle est déclarée
 * dans le groupe `frontend`, hors de `auth`. Un opérateur refusé était donc
 * **éjecté du back-office vers la vitrine commerciale**, sans un mot : il
 * n'apprenait ni qu'il avait été refusé, ni pourquoi, et devait se reconnecter
 * au tableau de bord à la main.
 *
 * Trois gestes, dans cet ordre d'importance :
 *
 *  - il **reste dans le back-office** : retour sur la page précédente, et à
 *    défaut le **tableau de bord** (qui n'exige aucune permission) ;
 *  - il **sait pourquoi** : un message d'erreur, dans l'idiome du socle (Toastr) ;
 *  - la page précédente vient de la **session**, pas de l'en-tête `Referer` :
 *    celui-ci est fourni par le client, et s'y fier ouvrirait une redirection
 *    vers n'importe quelle adresse.
 *
 * ⚠️ Le code HTTP d'une navigation reste **302**, pas 403 : `errors/403.blade.php`
 * existe, mais une page d'erreur perdrait le contexte de travail de l'opérateur —
 * et un refus de droit, dans un back-office, n'est pas une impasse : c'est
 * « pas ici ». La machine reçoit 403 (cas AJAX), l'humain reçoit son écran et un
 * message. C'est un choix, et il est inscrit dans un test.
 */
class PermissionCheckMiddleware
{
    public function handle(Request $request, Closure $next, $permission = null)
    {
        if (Auth::check() && $this->porte(Auth::user()->permissions, $permission)) {
            return $next($request);
        }

        // Un refus adressé à du JavaScript doit se lire comme un refus (S36).
        if ($request->ajax() || $request->expectsJson()) {
            abort(403);
        }

        // S39 — et un refus adressé à un humain doit le dire, sans le sortir du
        // back-office. `redirect('/')` faisait l'inverse des deux.
        Toastr::error(__('message.permission_denied'), __('message.error'));

        return redirect()->to($this->ouRenvoyer($request));
    }

    /**
     * Où renvoyer un opérateur refusé : la page d'où il vient, sinon le tableau
     * de bord. **Jamais** `/`, qui est la page publique du site.
     *
     * ⚠️ La page précédente est lue dans la **session**, et surtout **pas** par
     * `url()->previous()` : contrairement à ce que son nom suggère, cette méthode
     * lit **d'abord l'en-tête `Referer`** et ne retombe sur la session qu'à défaut
     * (`UrlGenerator::previous()`). Or le `Referer` vient du client : rediriger
     * vers sa valeur ferait de cette garde une **redirection ouverte** — une page
     * tierce qui pointe vers une route refusée renverrait le navigateur chez elle.
     *
     * La valeur de session, elle, est écrite par le socle à partir des navigations
     * réellement servies. L'hôte est vérifié malgré tout : une garde ne s'appuie
     * pas sur la seule provenance de sa donnée.
     */
    private function ouRenvoyer(Request $request): string
    {
        $repli = route('dashboard.index');
        $precedente = $request->hasSession() ? $request->session()->previousUrl() : null;

        if (blank($precedente)) {
            return $repli;
        }

        // Un autre hôte n'est pas une page de ce back-office.
        if (!str_starts_with($precedente, $request->getSchemeAndHttpHost() . '/')
            && $precedente !== $request->getSchemeAndHttpHost()) {
            return $repli;
        }

        // Se renvoyer sur la page qui vient d'être refusée boucle.
        if ($precedente === $request->fullUrl() || $precedente === $request->url()) {
            return $repli;
        }

        return $precedente;
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
