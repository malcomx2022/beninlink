<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class ParcelLogsResource extends JsonResource
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
            "id"                        => $this->id,
            "hub_name"                  => isset($this->hub)? $this->hub->name:'',
            "hub_phone"                 => (string)isset($this->hub)? $this->hub->phone:'',
            "pickup_man"                => isset($this->pickupman)? $this->pickupman->user->name:'',
            "pickup_phone"              => (string)isset($this->pickupman)? $this->pickupman->user->mobile:'',
            "delivery_man"              => isset($this->deliveryMan)? $this->deliveryMan->user->name:'',
            "transfer_delivery_man"     => isset($this->transferDeliveryman)? $this->transferDeliveryman->user->name:'',
            "delivery_phone"            => (string) isset($this->deliveryMan)? $this->deliveryMan->user->mobile:'',
            "transfer_delivery_phone"   => (string)isset($this->transferDeliveryman)? $this->transferDeliveryman->user->mobile:'',
            "description"               => $this->note,
            "parcel_status"             => (string)$this->parcel_status,
            "parcel_status_name"        => __('parcelLogs.'.$this->parcel_status),
            'date'                      => dateFormat($this->created_at) ,
            'time_date'                 => date('h:i a', strtotime($this->created_at)) ,
        ];
    }

}
