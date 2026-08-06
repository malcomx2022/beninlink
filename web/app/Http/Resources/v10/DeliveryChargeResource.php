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
            "same_day"           => (string)$this->same_day,
            "next_day"           => (string)$this->next_day,
            "sub_city"           => (string)$this->sub_city,
            "outside_city"       => (string)$this->outside_city,
            "status"             => (string)$this->status,
            "statusName"         => trans("status." . $this->status),
        ];
    }

}
