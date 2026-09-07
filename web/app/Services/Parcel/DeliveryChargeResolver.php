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
 * La règle de poids qui corrigeait S9 **survit au changement de modèle**, et
 * c'est délibéré : une ligne vaut « jusqu'à N kg », on cherche le poids exact,
 * sinon la tranche immédiatement supérieure, sinon la plus lourde. Un colis
 * n'est jamais facturé à une tranche plus légère que son poids. Le barème
 * négocié du marchand garde sa priorité sur celui de la société.
 *
 * Depuis l'**étape 6** (2026-09-07), il n'y a plus qu'un seul chemin : la
 * route. Les quatre colonnes héritées et le `resolve()` qui les lisait ont
 * disparu avec elles.
 */
class DeliveryChargeResolver
{
    /**
     * Tarif du **nouveau modèle** (D4) : une zone, un poids, un délai.
     *
     * Le métier a tranché le 2026-09-06 :
     *   - quatre zones (Cotonou, Périphérie, Intérieur, CEDEAO) ;
     *   - un **délai global** : le supplément dépend du délai, jamais de la
     *     zone — c'est ce qui empêche de revenir aux quatre colonnes ;
     *   - la **CEDEAO se facture au pays**, forfait, sans regarder le poids.
     *
     * Renvoie `null` quand la route n'est pas tarifée — zone sans grille, ou
     * pays d'export sans forfait. Il n'y a plus de repli depuis l'étape 6 :
     * l'appelant **refuse** le calcul plutôt que d'emprunter un montant
     * voisin. On ne devine pas un prix.
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
