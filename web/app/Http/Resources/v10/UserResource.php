<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
                "id"                => $this->id,
                "name"              => $this->name,
                "email"             => $this->email,
                "phone"             => (string)$this->mobile,
                "user_type"         => (string)$this->user_type,
                "hub_id"            => (string)$this->hub_id,
                "hub"               => $this->hub,
                "merchant"          => $this->merchant,
                "merchant_total_parcel"     => $this->merchant ? MerchantParcels($this->merchant->id)->total_parcels :null,
                "merchant_total_cash_amount"=> $this->merchant ? MerchantParcels($this->merchant->id)->total_cash_amount:null,
                "merchant_current_payable"  => $this->merchant ? MerchantParcels($this->merchant->id)->total_current_payable:null,
                "joining_date"      => $this->joining_date,
                "address"           => $this->address,
                "salary"            => (string)$this->salary,
                "status"            => (string)$this->status,
                "statusName"        => trans("status." . $this->status),
                "image"             => (string)$this->image,
                'created_at'        => $this->created_at->format('d M Y, h:i A'),
                'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
            ];
    }

}
