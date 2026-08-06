<?php

namespace App\Http\Resources;

use App\Enums\BooleanStatus;
use App\Enums\ParcelStatus;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceParcelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
         
        $status ='';

        if( $this->parcel_status == ParcelStatus::RETURN_TO_COURIER ):
            $status .= trans("parcelStatus.24").', ';
        endif;

        if($this->parcel->partial_delivered == BooleanStatus::YES): 
            $status .= trans("parcelStatus.".ParcelStatus::PARTIAL_DELIVERED ); 
        else:
            if( $this->parcel->status != ParcelStatus::RETURN_TO_COURIER ):
                $status .= __('parcelStatus.'.$this->parcel->status);
            endif;
        endif;
   
        return [
            'customer_name'     => $this->parcel->customer_name,
            'zone'              => $this->parcel->customer_address,
            'status'            => trans("parcelStatus.".$this->parcel->status),
            'date'              => Carbon::parse($this->parcel->delivered_date)->format('d-m-Y H:i A'),   
            'cash_collection'   =>$this->parcel->cash_collection,
            'delivery_charge'   => $this->total_charge_amount
        ];

    }
}
