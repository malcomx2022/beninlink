<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class LanguageManager
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
       
        if (session()->has('locale') && Schema::hasTable('settings')) {
            $demandee = session()->get('locale');

            // ⚠️ Le socle posait la valeur de session telle quelle. Avant le lot 5,
            // `LocalizationController` acceptait n'importe quelle chaîne : des
            // sessions portent donc peut-être encore `zh`, `bn` ou une locale
            // inventée, et l'interface s'y afficherait en clés brutes. On ne sert
            // que ce que `config/locales.php` déclare, et on NETTOIE la session au
            // passage — sinon l'utilisateur resterait coincé à chaque requête.
            if (array_key_exists($demandee, config('locales.supported', []))) {
                App::setLocale($demandee);
            } else {
                session()->forget('locale');
            }
        }

        return $next($request);
    }
}
