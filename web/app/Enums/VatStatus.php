<?php

namespace App\Enums;

/**
 * Statut TVA d'un marchand — **R7 b (S75, 2026-10-03)**.
 *
 * Jusqu'ici `merchants.vat` seul portait l'information, et `0` voulait dire
 * « pas saisi » : un marchand exonéré ne pouvait pas se déclarer (D1, limite
 * assumée). Le porteur a tranché : l'exonération est un **statut explicite**,
 * distinct de l'absence de saisie, visible partout où la TVA s'imprime.
 *
 *   - `unset`   : rien de saisi → le taux de la **société** s'applique (D1) ;
 *   - `taxable` : un taux **propre** au marchand (`merchants.vat`) ;
 *   - `exempt`  : **exonéré** — aucune TVA, et le document le dit.
 *
 * Les lignes antérieures gardent leur comportement : `vat > 0` est devenu
 * `taxable` à la migration (c'est exactement ce que le calcul faisait déjà),
 * tout le reste est `unset`. Aucun marchand n'a été reclassé exonéré.
 */
final class VatStatus
{
    public const UNSET = 'unset';
    public const TAXABLE = 'taxable';
    public const EXEMPT = 'exempt';

    public const TOUS = [self::UNSET, self::TAXABLE, self::EXEMPT];
}
