<?php

namespace App\Services\Parcel;

use App\Models\Backend\Merchant;

/**
 * Taux de TVA applicable à un marchand — décision métier du 2026-09-05.
 *
 * Le socle ne connaissait qu'un taux **par marchand** (`merchants.vat`,
 * défaut 0), donc un marchand sans saisie n'était jamais taxé. BeninLink
 * retient un taux **au niveau de la société** (`configs.vat_rate`, 18 % au
 * Bénin), que chaque marchand peut surcharger :
 *
 *   - `merchants.vat` > 0  → ce taux (négocié, régime particulier) ;
 *   - sinon                → le taux de la société ;
 *   - sans réglage         → 0, comme avant.
 *
 * Un marchand exonéré se déclare avec un taux propre… qui ne peut pas être 0
 * dans ce modèle (0 = « pas de saisie »). C'est la limite assumée : l'exonération
 * est rare et se règle en mettant le taux société à 0 pour cette société.
 */
class VatRate
{
    public const CONFIG_KEY = 'vat_rate';

    public static function for(Merchant $merchant): float
    {
        $own = (float) ($merchant->vat ?? 0);
        if ($own > 0) {
            return $own;
        }

        return self::company();
    }

    /** Taux de la société courante (celle du compte connecté), 0 si absent. */
    public static function company(): float
    {
        return (float) settingHelper(self::CONFIG_KEY);
    }
}
