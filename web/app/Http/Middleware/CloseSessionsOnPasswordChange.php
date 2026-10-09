<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * **S135** — une session web ouverte ailleurs se ferme quand le mot de passe du compte change.
 *
 * Même principe que `AuthenticateSession` de Laravel (l'empreinte du mot de passe est rangée dans la
 * session et comparée à chaque requête), mais l'empreinte est **liée au compte** : une session qui
 * porte un autre compte la remplace au lieu de se fermer. La session qui change elle-même son mot de
 * passe reste ouverte (`User::booted` resynchronise le compte connecté avant que l'empreinte soit
 * rangée). L'exception d'authentification renvoie à la connexion, ou répond 401 à un appel JSON.
 */
class CloseSessionsOnPasswordChange
{
    public const CLE = 'beninlink.empreinte_mot_de_passe';

    public function handle(Request $request, Closure $next)
    {
        $compte = $request->hasSession() ? $request->user() : null;
        if ($compte) {
            $vu = $request->session()->get(self::CLE);
            if (is_array($vu)
                && ($vu['id'] ?? null) === $compte->getAuthIdentifier()
                && ! hash_equals((string) ($vu['empreinte'] ?? ''), (string) $compte->getAuthPassword())) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException('Unauthenticated.', ['web']);
            }
        }

        return tap($next($request), function () use ($request) {
            if ($request->hasSession() && ($compte = $request->user())) {
                $request->session()->put(self::CLE, [
                    'id' => $compte->getAuthIdentifier(),
                    'empreinte' => (string) $compte->getAuthPassword(),
                ]);
            }
        });
    }
}
