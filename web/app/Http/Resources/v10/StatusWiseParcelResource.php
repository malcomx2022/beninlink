<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class StatusWiseParcelResource extends JsonResource
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
            "id"                => $this->id,
            "invoice"           => (string) $this->invoice_no,
            "tracking_id"       => (string) $this->tracking_id,
            "customer_name"     => $this->customer_name,
            "customer_phone"    => $this->customer_phone,   
            "cash_collection"   => (string) $this->cash_collection,  
            "charge"            => (string) $this->total_delivery_amount,  
            "current_payable"   => (string) $this->current_payable,
            "status_name"       => trans("parcelStatus.".$this->status), 
            'date'              => date('d-m-Y', strtotime($this->updated_at)) ,
        ];
    }
}
