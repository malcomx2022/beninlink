<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
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
            "merchant_id"       => $this->merchant_id,
            "name"              => $this->name,
            "contact_no"        => (string) $this->contact_no,
            "address"           => $this->address,
            "merchant_lat"      => $this->merchant_lat !=null ? $this->merchant_lat :'',
            "merchant_long"     => $this->merchant_long !=null  ? $this->merchant_long:'',
            "default_shop"      => (string) $this->default_shop,
            "status"            => (int)$this->status,
            "statusName"        => trans("status.".$this->status),
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }

}
