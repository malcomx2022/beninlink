<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * **S90 (T6)** — l'API négocie sa langue par `Accept-Language`.
 *
 * Le socle ignorait l'en-tête : chaque `message` de l'enveloppe sortait dans la
 * locale du serveur (`fr`), quoi que l'app demande. Le produit est français, les
 * deux apps aussi — mais une langue se **négocie**, elle ne se devine pas : ce
 * garde lit `Accept-Language`, en retient la première langue que
 * `config/locales.php` déclare (`fr`, `en`), et laisse la locale du serveur
 * sinon. Rien d'autre ne change : une app qui n'envoie rien reçoit le français.
 *
 * Posé sur le groupe `api` seulement. Le web garde `LanguageManager` (session).
 */
class ApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        $langue = self::negocier((string) $request->header('Accept-Language', ''), array_keys(config('locales.supported', [])));

        if ($langue === null) {
            return $next($request);
        }

        // La locale est un état de l'application, pas de la requête : on la
        // remet après la réponse (Octane, files, et les tests qui enchaînent
        // plusieurs requêtes dans le même processus).
        $precedente = App::getLocale();
        App::setLocale($langue);
        try {
            return $next($request);
        } finally {
            App::setLocale($precedente);
        }
    }

    /**
     * La première langue de l'en-tête (ordre de préférence `q`) que l'installation sert.
     *
     * `en-GB,en;q=0.8,fr;q=0.5` → `en` ; `zh` → null ; vide → null.
     *
     * @param  array<int, string>  $servies
     */
    public static function negocier(string $entete, array $servies): ?string
    {
        $candidats = [];
        foreach (array_filter(array_map('trim', explode(',', $entete))) as $position => $morceau) {
            [$etiquette, $q] = array_pad(explode(';', $morceau, 2), 2, null);
            $poids = 1.0;
            if ($q !== null && preg_match('/^\s*q=([0-9.]+)\s*$/i', $q, $m)) {
                $poids = (float) $m[1];
            }
            // Le sous-code principal : `en-GB` et `en_US` sont de l'anglais.
            $langue = strtolower((string) preg_split('/[-_]/', trim($etiquette))[0]);
            if ($langue === '' || $langue === '*' || $poids <= 0) {
                continue;
            }
            $candidats[] = [$langue, $poids, $position];
        }

        // Par poids décroissant, puis par ordre d'apparition.
        usort($candidats, fn ($a, $b) => $b[1] <=> $a[1] ?: $a[2] <=> $b[2]);

        foreach ($candidats as [$langue]) {
            if (in_array($langue, $servies, true)) {
                return $langue;
            }
        }

        return null;
    }
}
