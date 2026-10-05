<?php

namespace App\Http\Middleware;

use App\Traits\ApiReturnFormatTrait;
use Closure;
use Illuminate\Http\Request;

/**
 * Porte d'entrée de l'API `/api/v10` : l'en-tête `apiKey` doit valoir la clé de
 * l'installation (`config('rxcourier.api_key')`, lue dans `API_KEY`).
 *
 * Ce n'est pas une authentification (la clé voyage dans chaque requête et vit
 * dans le bundle des apps) : les données restent derrière `auth:sanctum`. C'est
 * un filtre, et un filtre doit au moins filtrer — d'où **S77 (T7)** :
 *
 * - **refus fermé** : clé configurée vide (variable absente) → 400 pour tout le
 *   monde, y compris la clé publique du socle que la config livrait en repli ;
 *   le socle laissait passer un en-tête vide contre une clé vide (`==`) ;
 * - comparaison **stricte et à temps constant** (`hash_equals`) : le `==` du
 *   socle convertissait les deux côtés, et une comparaison de chaînes qui
 *   s'arrête au premier octet différent renseigne sur la clé.
 *
 * `ApiKeyFailClosedTest` fige les trois cas.
 */
class CheckApiKeyMiddleware
{
    use ApiReturnFormatTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $attendue = config('rxcourier.api_key');
        $recue = $request->header('apiKey');

        if (is_string($attendue) && $attendue !== ''
            && is_string($recue) && $recue !== ''
            && hash_equals($attendue, $recue)) {
            return $next($request);
        }

        return $this->responseWithError('Invalid Api Key', [], 400);
    }
}
