<?php

namespace App\Http\Resources\v10;

use App\Services\Parcel\ChargeCalculator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une zone de livraison, telle que l'app la reçoit (**D4**, étape 5).
 *
 * Les quatre colonnes héritées restent servies à côté, dans `deliveryCharges` :
 * l'API sert **les deux formes** pendant la transition, sinon un APK déjà
 * installé afficherait une grille vide. `zones` arrive vide tant qu'une société
 * n'a pas configuré ses zones — c'est le signal, pour l'app, de rester sur
 * l'ancien affichage.
 *
 * `cod_key` dit quelle entrée de `codCharges` s'applique à cette zone. Sans
 * elle, l'app devrait deviner la correspondance ; elle vient de
 * `ChargeCalculator::COD_KEY_BY_ZONE`, la table que le calcul lui-même utilise.
 * `null` sur la zone d'export : un encaissement à l'étranger n'est pas encore
 * tarifé, et le calcul rend 0 plutôt que d'emprunter le taux « hors ville ».
 */
class DeliveryZoneResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'position' => (int) $this->position,
            // La zone d'export ne se tarife pas au poids : son prix vit dans
            // `countries`, au forfait.
            'export' => (bool) $this->isExport(),
            'cod_key' => ChargeCalculator::COD_KEY_BY_ZONE[$this->code] ?? null,
            'rates' => collect($this->rates ?? [])->map(fn (array $ligne) => [
                'category_id' => (string) $ligne['category_id'],
                'weight' => (string) $ligne['weight'],
                'amount' => (string) (int) $ligne['amount'],
            ])->all(),
            'countries' => $this->countries->map(fn ($pays) => [
                'code' => (string) $pays->code,
                'name' => (string) $pays->name,
                'flat_amount' => (string) (int) $pays->flat_amount,
            ])->all(),
        ];
    }
}
