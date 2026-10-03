<?php

namespace App\Services\Parcel;

use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;

/**
 * La TVA sur le **frais de retour** — décision **D2, question 6** (2026-10-03).
 *
 * Le socle facturait le retour au marchand (`parcels.return_charges`) mais
 * forçait sa TVA à zéro : le retour était traité **hors champ** sans que
 * personne l'ait décidé. Le porteur a tranché : un retour est une prestation
 * rendue contre rémunération, **taxable au taux normal**.
 *
 * Le taux est celui du colis (`parcels.vat`, fixé à la création comme pour la
 * livraison) ; à défaut, celui du marchand ou de la société (`VatRate`). Le
 * montant est **arrondi au franc** comme toute TVA depuis le 2026-09-18
 * (question 7), au seul endroit où un taux devient des francs.
 *
 * Il est écrit sur le colis (`return_vat_amount`) **au moment du retour**, à
 * côté du frais, et le relevé le relit tel quel — jamais de recalcul après
 * coup, pour la même raison que le frais lui-même : un taux révisé entre le
 * retour et le relevé laisserait un résidu.
 */
class ReturnVat
{
    /** Taux appliqué au retour : celui du colis, sinon celui du marchand. */
    public static function taux(Parcel $parcel, Merchant $merchant): float
    {
        $propre = (float) ($parcel->vat ?? 0);

        return $propre > 0 ? $propre : VatRate::for($merchant);
    }

    /** La TVA du frais de retour, en FCFA entiers. */
    public static function montant(Parcel $parcel, Merchant $merchant, float $fraisHT): int
    {
        return self::arrondi($fraisHT, self::taux($parcel, $merchant));
    }

    /** Même règle que `ChargeCalculator::percentage()` : au franc le plus proche. */
    public static function arrondi(float $montant, float $taux): int
    {
        return (int) round($montant * ($taux / 100));
    }
}
