<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id"                 => $this->id,
            "merchant_id"        => (string)$this->merchant_id,
            "category_id"        => (string)$this->category_id,
            "delivery_charge_id" => (string)$this->delivery_charge_id,
            "category"           => $this->deliveryCharge->category->title,
            "weight"             => (string)$this->deliveryCharge->weight ?? '0',
            // D4, étape 6 : une ligne = une zone et un montant. Les quatre
            // colonnes ont disparu du barème, elles disparaissent du contrat.
            "zone_id"            => $this->zone_id === null ? null : (string)$this->zone_id,
            "zone_code"          => $this->zone?->code,
            "amount"             => (string)$this->amount,
            "status"             => (string)$this->status,
            "statusName"         => trans("status." . $this->status),
        ];
    }

}
