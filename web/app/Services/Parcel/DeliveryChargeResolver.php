<?php

namespace App\Services\Parcel;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\MerchantDeliveryCharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Résolution du tarif de livraison — **un seul résolveur**, côté serveur.
 *
 * Le socle We Courier répétait la même recherche à quatre endroits
 * (`ChargeCalculator`, les deux AJAX `deliveryCharge` de l'administration et
 * du panneau marchand, l'import CSV) avec deux défauts, relevés en S8 et S9 :
 *
 *   - S8 : côté administration, le repli `DeliveryCharge::where(...)` n'était
 *     pas scopé par société — le barème d'un autre locataire pouvait servir ;
 *   - S9 : le repli ignorait le poids et rendait la **première ligne** de la
 *     catégorie — un colis lourd passait au tarif le plus léger, en silence.
 *
 * Règle appliquée ici, sans changer le schéma (une ligne = un poids entier) :
 * chaque ligne vaut « jusqu'à N kg ». On cherche le poids exact, sinon la
 * **tranche immédiatement supérieure**, sinon la **plus lourde** du barème.
 * Un colis n'est jamais facturé à une tranche plus légère que son poids.
 * Le barème négocié du marchand a priorité sur celui de la société.
 */
class DeliveryChargeResolver
{
    /** Colonne tarifaire par type de livraison (`App\Enums\DeliveryType`). */
    private const COLUMNS = [
        1 => 'same_day',
        2 => 'next_day',
        3 => 'sub_city',
        4 => 'outside_city',
    ];

    public function resolve(int $merchantId, ?int $categoryId, $weight, int $deliveryTypeId): float
    {
        $tier = $this->tier($merchantId, $categoryId, $weight);
        $column = self::COLUMNS[$deliveryTypeId] ?? null;

        if ($tier === null || $column === null) {
            return 0.0;
        }

        return (float) ($tier->{$column} ?? 0);
    }

    /** Ligne de barème applicable, ou null si aucun barème n'existe. */
    public function tier(int $merchantId, ?int $categoryId, $weight): ?Model
    {
        if ($categoryId === null) {
            return null;
        }

        $weight = max(0, (float) $weight);

        $merchant = MerchantDeliveryCharge::query()
            ->where('merchant_id', $merchantId)
            ->where('category_id', $categoryId);

        $company = DeliveryCharge::companywise()->where('category_id', $categoryId);

        return $this->pick($merchant, $weight)
            ?? $this->pick($company, $weight)
            ?? $company->orderByDesc('weight')->first();
    }

    /** Poids exact, sinon la tranche immédiatement supérieure. */
    private function pick(Builder $query, float $weight): ?Model
    {
        return (clone $query)
            ->where('weight', '>=', $weight)
            ->orderBy('weight')
            ->orderBy('id')
            ->first();
    }
}
