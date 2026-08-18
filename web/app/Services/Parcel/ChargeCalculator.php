<?php

namespace App\Services\Parcel;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Packaging;

/**
 * Calcul des montants d'un colis — **côté serveur, seule source de vérité**.
 *
 * Corrige le constat S2 de la cartographie : jusqu'ici le socle enregistrait
 * `json_decode($request->chargeDetails)` **tel quel**, c'est-à-dire des frais,
 * une TVA et un net à reverser calculés en JavaScript par le navigateur. Un
 * client qui modifiait ce champ choisissait sa propre facture.
 *
 * Les règles reproduisent **à l'identique** celles de `public/backend/js/parcel/create.js`
 * — c'était la seule spécification existante :
 *
 *   frais COD      = encaissement × taux COD du marchand (selon la zone)
 *   sous-total     = frais livraison + frais COD + emballage + fragile
 *   TVA            = sous-total × taux de TVA du marchand
 *   net à reverser = encaissement − (sous-total + TVA)
 *
 * ⚠️ `total_delivery_amount` porte le **sous-total hors TVA**, conformément au
 * comportement d'origine. Le changer modifierait les factures déjà émises.
 *
 * Aucune donnée monétaire ne vient plus de la requête : seuls des **choix**
 * (catégorie, poids, type de livraison, emballage) y sont lus, et chaque tarif
 * est relu en base.
 */
class ChargeCalculator
{
    /** Types de livraison partageant le tarif COD « intra-ville ». */
    private const INSIDE_CITY_TYPES = [1, 2];
    private const SUB_CITY_TYPE = 3;
    private const OUTSIDE_CITY_TYPE = 4;

    /**
     * @return array{
     *   delivery_charge: float, cod_charge: float, cod_amount: float,
     *   vat: float, vat_amount: float, packaging_amount: float,
     *   liquid_fragile_amount: float, total_delivery_amount: float,
     *   current_payable: float
     * }
     */
    public function calculate(
        Merchant $merchant,
        int $deliveryTypeId,
        ?int $categoryId,
        $weight,
        float $cashCollection,
        ?int $packagingId = null,
        bool $fragileLiquid = false
    ): array {
        $deliveryCharge = $this->deliveryCharge($merchant, $categoryId, $weight, $deliveryTypeId);

        $codRate = $this->codRate($merchant, $deliveryTypeId);
        $codAmount = $this->percentage($cashCollection, $codRate);

        $packagingAmount = $this->packagingAmount($packagingId);
        // `settingHelper` lit la table `configs`, scopée par société.
        $fragileAmount = $fragileLiquid ? (float) settingHelper('fragile_liquid_charge') : 0.0;

        // Sous-total hors TVA — c'est bien ce que le socle stocke dans
        // `total_delivery_amount`.
        $subTotal = $deliveryCharge + $codAmount + $packagingAmount + $fragileAmount;

        $vatRate = (float) ($merchant->vat ?? 0);
        $vatAmount = $this->percentage($subTotal, $vatRate);

        return [
            'delivery_charge' => $deliveryCharge,
            'cod_charge' => $codRate,
            'cod_amount' => $codAmount,
            'vat' => $vatRate,
            'vat_amount' => $vatAmount,
            'packaging_amount' => $packagingAmount,
            'liquid_fragile_amount' => $fragileAmount,
            'total_delivery_amount' => $subTotal,
            'current_payable' => $cashCollection - ($subTotal + $vatAmount),
        ];
    }

    /**
     * Tarif de livraison : barème propre au marchand, sinon barème de la société.
     *
     * Corrige deux défauts du socle relevés en S8 et S9 :
     *   - le repli `DeliveryCharge::where(...)` **ignorait le poids**, servant la
     *     première ligne de la catégorie — un colis lourd passait au tarif le plus bas ;
     *   - côté administration il **n'était pas scopé par société**, exposant le
     *     barème d'un autre locataire.
     */
    private function deliveryCharge(Merchant $merchant, ?int $categoryId, $weight, int $deliveryTypeId): float
    {
        $charges = MerchantDeliveryCharge::where('merchant_id', $merchant->id)
            ->where('category_id', $categoryId)
            ->where('weight', $weight)
            ->first();

        if (blank($charges)) {
            $charges = DeliveryCharge::companywise()
                ->where('category_id', $categoryId)
                ->where('weight', $weight)
                ->first();
        }

        // Dernier recours : la catégorie sans le poids, comme le socle le faisait
        // toujours. Conservé pour ne pas facturer 0 sur un barème incomplet, mais
        // le poids reste prioritaire.
        if (blank($charges)) {
            $charges = DeliveryCharge::companywise()->where('category_id', $categoryId)->first();
        }

        if (blank($charges)) {
            return 0.0;
        }

        return (float) match ($deliveryTypeId) {
            1 => $charges->same_day,
            2 => $charges->next_day,
            self::SUB_CITY_TYPE => $charges->sub_city,
            self::OUTSIDE_CITY_TYPE => $charges->outside_city,
            default => 0,
        };
    }

    /** Taux COD du marchand, en pourcentage, selon la zone de livraison. */
    private function codRate(Merchant $merchant, int $deliveryTypeId): float
    {
        $rates = $merchant->cod_charges ?? [];

        $key = match (true) {
            in_array($deliveryTypeId, self::INSIDE_CITY_TYPES, true) => 'inside_city',
            $deliveryTypeId === self::SUB_CITY_TYPE => 'sub_city',
            $deliveryTypeId === self::OUTSIDE_CITY_TYPE => 'outside_city',
            default => null,
        };

        return $key !== null ? (float) ($rates[$key] ?? 0) : 0.0;
    }

    private function packagingAmount(?int $packagingId): float
    {
        if (!$packagingId) {
            return 0.0;
        }
        $packaging = Packaging::companywise()->find($packagingId);

        return $packaging ? (float) $packaging->price : 0.0;
    }

    private function percentage(float $amount, float $rate): float
    {
        return $amount * ($rate / 100);
    }
}
