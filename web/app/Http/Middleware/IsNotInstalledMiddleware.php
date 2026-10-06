<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use DB;

/**
 * Le garde de l'installateur : ses routes n'existent que pour une base vierge.
 *
 * **S88** — le socle ne posait ce garde que sur l'écran `GET /install`, et ne
 * reconnaissait une installation que par `APP_INSTALLED=yes` dans le `.env`.
 * `POST /installing` et surtout `GET /finish` restaient joignables **sans
 * authentification** sur une installation terminée — et `finish()` supprime
 * **chaque table**, réamorce la base et pose le mot de passe du compte n° 1
 * depuis la requête. Les trois routes portent désormais ce garde, et une base
 * qui porte déjà des utilisateurs est tenue pour installée, drapeau ou pas :
 * le drapeau devient une seconde serrure, plus la seule.
 *
 * Trois réponses, selon ce qui frappe :
 *  - les deux actions qui écrivent (`installing`, `final`) : **404**, la page
 *    n'existe pas pour une base installée ;
 *  - l'écran, drapeau posé : redirection vers `/` (le comportement du socle) ;
 *  - l'écran, drapeau absent mais base peuplée : **403** qui dit quoi poser —
 *    une redirection vers `/` boucle, `IsInstalled` renverrait ici.
 */
class IsNotInstalledMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $th) {
             return response()->view('installer.index');
        }

        if (self::installee()) {
            if ($request->routeIs('installing', 'final')) {
                abort(404);
            }
            if (Config::get('app.app_installed') == 'yes') {
                return redirect('/');
            }
            // Une réponse nue : la page 403 du socle n'affiche pas le message d'un abort().
            return response('Cette base est déjà installée : poser APP_INSTALLED=yes dans le .env. L\'installateur ne la recrée pas.', 403);
        }

        return $next($request);
    }

    /**
     * Une base est installée quand ses tables existent **et** que le `.env` le
     * dit — ou qu'elle porte déjà des utilisateurs (S88).
     */
    public static function installee(): bool
    {
        if (! (Schema::hasTable('settings') && Schema::hasTable('general_settings') && Schema::hasTable('users'))) {
            return false;
        }

        return Config::get('app.app_installed') == 'yes' || DB::table('users')->exists();
    }
}
