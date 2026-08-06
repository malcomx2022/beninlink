<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliverymanUserResource extends JsonResource
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
            "name"              => $this->name,
            "email"             => $this->email,
            "phone"             => (string) $this->mobile,
            "user_type"         => (string) $this->user_type,
            "deliveryman"       => $this->deliveryman,
            "hub"               => $this->hub,
            "address"           => $this->address,
            "salary"            => (string) $this->salary,
            "status"            => (string) $this->status,
            "statusName"        => trans("status." . $this->status),
            "image"             => (string) $this->image,
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }
}
