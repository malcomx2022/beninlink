<?php

namespace App\Http\Middleware;

use App\Support\BeninPhone;
use Closure;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * **S133** — un numéro saisi entre au format rangé (`BeninPhone`) avant toute validation,
 * sur le web comme dans l'API : les formulaires, `Validator::make($request->all())` de l'API
 * et les dépôts qui lisent `$request->mobile` voient la même valeur.
 *
 * Seules les écritures sont touchées : un filtre de recherche en GET (`?phone=97000`) garde
 * ce que l'on a tapé, il cherche par morceau dans des numéros rangés avant S133.
 */
class NormalizePhoneNumbers extends TransformsRequest
{
    /** Champs qui portent un numéro de téléphone dans les formulaires et l'API. */
    public const CHAMPS = ['mobile', 'phone', 'contact_no', 'mobile_no', 'customer_phone'];

    public function handle($request, Closure $next)
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    protected function transform($key, $value)
    {
        $champ = substr((string) $key, (int) strrpos('.' . $key, '.'));

        return in_array($champ, self::CHAMPS, true) ? BeninPhone::normalize($value) : $value;
    }
}
