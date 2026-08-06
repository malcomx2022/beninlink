<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class ParcelResource extends JsonResource
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
            "id"                    => $this->id,
            "tracking_id"           => $this->tracking_id,
            "merchant_id"           => $this->merchant_id,
            "merchant_name"         => $this->merchant->business_name,
            "merchant_user_name"    => $this->merchant->user->name,
            "merchant_user_email"   => $this->merchant->user->email,
            "merchant_mobile"       => $this->merchant->user->mobile,
            "merchant_address"      => $this->merchant->address,
            "customer_name"         => $this->customer_name,
            "customer_phone"        => (string)$this->customer_phone,
            "customer_address"      => $this->customer_address,
            "invoice_no"            => (string) $this->invoice_no,
            "weight"                => (string) $this->weight. ' '.optional($this->deliveryCategory)->title,
            "total_delivery_amount" => $this->total_delivery_amount,
            "cod_amount"            => $this->cod_amount,
            "vat_amount"            => $this->vat_amount,
            "current_payable"       => $this->current_payable,
            "cash_collection"       => $this->cash_collection,
            "delivery_type_id"      => (int) $this->delivery_type_id,
            "deliveryType"          => trans("deliveryType.".$this->delivery_type_id),
            "status"                => (int) $this->status,
            "statusName"            => trans("parcelStatus.".$this->status),
            'pickup_date'           => dateFormat($this->pickup_date),
            'delivery_date'         => dateFormat($this->delivery_date),
            'created_at'            => $this->created_at->format('d M Y, h:i A'),
            'updated_at'            => $this->updated_at->format('d M Y, h:i A'),
            'parcel_date'           => dateFormat($this->created_at) ,
            'parcel_time'           => date('h:i a', strtotime($this->created_at)) ,
        ];
    }

}
