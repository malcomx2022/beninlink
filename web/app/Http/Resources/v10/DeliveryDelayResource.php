<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un délai de livraison et son supplément (**D4**, étape 5).
 *
 * Le supplément est **global** : il s'ajoute au montant de la zone et ne
 * dépend pas d'elle. C'est ce que l'app doit afficher — un supplément recopié
 * dans chaque colonne redonnerait le mélange que la refonte défait.
 */
class DeliveryDelayResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'surcharge' => (string) (int) $this->surcharge,
            'position' => (int) $this->position,
        ];
    }
}
