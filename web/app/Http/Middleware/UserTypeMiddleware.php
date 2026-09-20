<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Models\User;
use App\Traits\ApiReturnFormatTrait;
use Closure;
use Illuminate\Http\Request;

/**
 * S5 — cloisonne l'API entre marchand et livreur.
 *
 * Le socle We Courier plaçait `deliveryman/*` et les routes marchand dans le
 * même groupe `auth:sanctum`, sans garde sur `user_type` et avec des jetons
 * sans `abilities` : un jeton marchand appelait les endpoints livreur, et
 * inversement (le contrôleur marchand répondait alors 500, pas un refus).
 *
 * Deux verrous, dans cet ordre :
 *  1. `user_type` du compte connecté — la vérité de la base ;
 *  2. l'ability portée par le jeton (émise à la connexion) — un jeton émis
 *     pour un livreur ne sert pas un marchand, même si le compte a changé de
 *     type entre-temps. Les jetons antérieurs (`['*']`) passent ce second
 *     verrou : le premier suffit à les cloisonner.
 *
 * ⚠️ **Le pendant WEB est `PanelAccessMiddleware` (S41), et c'est une autre classe.**
 * Le meme trou existait sur `routes/web.php` — `admin/*` et `merchant/*` dans le meme
 * groupe `auth`, sans garde de type — et il y est reste ouvert jusqu'a S41. La forme ne
 * pouvait pas etre partagee : une session web n'a pas de jeton a interroger, un humain
 * n'attend pas l'enveloppe JSON de `responseWithError`, et `scopeOf()` / `abilitiesFor()`
 * ci-dessous servent a EMETTRE les abilities a la connexion — y ajouter un type
 * « back-office » changerait les jetons emis aux administrateurs. Toucher aux portees de
 * ce middleware oblige donc a relire le pendant web : un test le rappelle
 * (`WebPanelSeparationTest::test_the_measurement_behind_each_panel_still_holds`).
 *
 * Usage : `userType:merchant`, `userType:deliveryman`, `userType:merchant,deliveryman`.
 */
class UserTypeMiddleware
{
    use ApiReturnFormatTrait;

    /** Portée du jeton → type de compte. */
    public const SCOPES = [
        'merchant'    => UserType::MERCHANT,
        'deliveryman' => UserType::DELIVERYMAN,
    ];

    public function handle(Request $request, Closure $next, string ...$scopes)
    {
        $user = $request->user();
        $allowed = array_intersect_key(self::SCOPES, array_flip($scopes));

        if (!$user || !in_array((int) $user->user_type, $allowed, true)) {
            return $this->responseWithError(__('auth.forbidden_user_type'), [], 403);
        }

        $scope = self::scopeOf($user);
        if ($scope !== null && !$user->tokenCan($scope)) {
            return $this->responseWithError(__('auth.forbidden_user_type'), [], 403);
        }

        return $next($request);
    }

    /** Portée d'un compte (`merchant`, `deliveryman`) ou null s'il n'en a pas. */
    public static function scopeOf(User $user): ?string
    {
        $scope = array_search((int) $user->user_type, self::SCOPES, true);

        return $scope === false ? null : $scope;
    }

    /** Abilities à donner à un jeton émis pour ce compte. */
    public static function abilitiesFor(User $user): array
    {
        $scope = self::scopeOf($user);

        return $scope === null ? ['*'] : [$scope];
    }
}
