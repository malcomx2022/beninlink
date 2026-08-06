<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class ParcelPaymentLogs extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
                    'id'             => $this->id,
                    'name'           => $this->deliveryman->user->name,
                    'type'           => $this->type,
                    'cash_collection'=>$this->cash_collection,
                    'date'           => $this->date,
                    'note'           => $this->note,
                    'created_at'     => $this->created_at,
                    'updated_at'     => $this->updated_at

        ];
    }
}
