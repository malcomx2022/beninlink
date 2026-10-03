<?php

namespace App\Services\Parcel;

use App\Enums\VatStatus;
use App\Models\Backend\Merchant;

/**
 * Taux de TVA applicable à un marchand — décision métier du 2026-09-05 (**D1**),
 * complétée le 2026-10-03 (**R7 b**, S75).
 *
 * Le socle ne connaissait qu'un taux **par marchand** (`merchants.vat`,
 * défaut 0), donc un marchand sans saisie n'était jamais taxé. BeninLink
 * retient un taux **au niveau de la société** (`configs.vat_rate`, 18 % au
 * Bénin), que chaque marchand peut surcharger — et, depuis R7 b, un **statut**
 * explicite (`merchants.vat_status`, `VatStatus`) :
 *
 *   - `exempt`              → **0**, et le document dit « exonéré » ;
 *   - `merchants.vat` > 0   → ce taux (négocié, régime particulier) ;
 *   - sinon                 → le taux de la société ;
 *   - sans réglage          → 0, comme avant.
 *
 * `0` ne veut donc plus dire deux choses : un `0` **non renseigné** prend le
 * taux de la société ; un marchand **exonéré** le dit par son statut. C'est la
 * seule différence avec D1, et elle est visible partout où la TVA s'imprime.
 */
class VatRate
{
    public const CONFIG_KEY = 'vat_rate';

    public static function for(Merchant $merchant): float
    {
        if (self::estExonere($merchant)) {
            return 0.0;
        }

        $own = (float) ($merchant->vat ?? 0);
        if ($own > 0) {
            return $own;
        }

        return self::company();
    }

    /** Le statut explicite du marchand ; `unset` pour une ligne jamais renseignée. */
    public static function statut(Merchant $merchant): string
    {
        $statut = (string) ($merchant->vat_status ?? VatStatus::UNSET);

        return in_array($statut, VatStatus::TOUS, true) ? $statut : VatStatus::UNSET;
    }

    /** Exonéré **explicitement** — jamais déduit d'un taux à zéro (R7 b). */
    public static function estExonere(Merchant $merchant): bool
    {
        return self::statut($merchant) === VatStatus::EXEMPT;
    }

    /** Taux de la société courante (celle du compte connecté), 0 si absent. */
    public static function company(): float
    {
        return (float) settingHelper(self::CONFIG_KEY);
    }
}
