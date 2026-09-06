<?php

namespace App\Services\Parcel;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
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

    /**
     * Tarif du **nouveau modèle** (D4) : une zone, un poids, un délai.
     *
     * Le métier a tranché le 2026-09-06 :
     *   - quatre zones (Cotonou, Périphérie, Intérieur, CEDEAO) ;
     *   - un **délai global** : le supplément dépend du délai, jamais de la
     *     zone — c'est ce qui empêche de revenir aux quatre colonnes ;
     *   - la **CEDEAO se facture au pays**, forfait, sans regarder le poids.
     *
     * Renvoie `null` quand la société n'a pas encore de zones : l'appelant
     * retombe alors sur `resolve()`, c'est-à-dire sur les quatre colonnes
     * d'origine. Une installation qui ne configure rien ne change pas de
     * tarif — la garantie que tient `DeliveryPricingBaselineTest`.
     */
    public function resolveByZone(
        int $merchantId,
        ?int $categoryId,
        $weight,
        int $zoneId,
        ?int $delayId = null,
        ?string $country = null,
    ): ?float {
        $zone = DeliveryZone::find($zoneId);
        if (blank($zone)) {
            return null;
        }

        if ($zone->isExport()) {
            $forfait = $this->forfaitPays($zone, $country);

            // Un pays inconnu n'est pas facturé au hasard : l'appelant doit
            // le voir, pas le découvrir sur la facture du marchand.
            return $forfait === null ? null : $forfait + $this->supplement($delayId);
        }

        $tranche = $this->trancheDeZone($merchantId, $categoryId, $weight, $zoneId);
        if ($tranche === null) {
            return null;
        }

        return (float) $tranche->amount + $this->supplement($delayId);
    }

    /** Forfait du pays dans une zone d'export, ou `null` s'il n'est pas tarifé. */
    private function forfaitPays(DeliveryZone $zone, ?string $country): ?float
    {
        if (blank($country)) {
            return null;
        }

        $ligne = DeliveryZoneCountry::where('zone_id', $zone->id)
            ->where('code', strtoupper($country))
            ->first();

        return $ligne === null ? null : (float) $ligne->flat_amount;
    }

    /** Supplément du délai — global, donc indépendant de la zone. */
    private function supplement(?int $delayId): float
    {
        if ($delayId === null) {
            return 0.0;
        }

        return (float) (DeliveryDelay::find($delayId)?->surcharge ?? 0);
    }

    /**
     * Ligne de barème d'une zone. Même règle de poids que l'historique : le
     * poids exact, sinon la tranche immédiatement supérieure, sinon la plus
     * lourde (S9). Le barème négocié du marchand garde sa priorité.
     */
    private function trancheDeZone(int $merchantId, ?int $categoryId, $weight, int $zoneId): ?Model
    {
        if ($categoryId === null) {
            return null;
        }

        $weight = max(0, (float) $weight);

        $marchand = MerchantDeliveryCharge::query()
            ->where('merchant_id', $merchantId)
            ->where('category_id', $categoryId)
            ->where('zone_id', $zoneId);

        $societe = DeliveryCharge::query()
            ->where('category_id', $categoryId)
            ->where('zone_id', $zoneId);

        return $this->pick($marchand, $weight)
            ?? $this->pick($societe, $weight)
            ?? $societe->orderByDesc('weight')->first();
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
