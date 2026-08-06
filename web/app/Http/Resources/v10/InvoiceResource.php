<?php

namespace App\Http\Resources\v10;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            "invoice_id"        => $this->invoice_id,
            "status"            => $this->InvoiceStatus,
            "amount"            => ((($this->parcels->sum('current_payable') + $this->PartialParcelsReturnMerchant->sum('current_payable'))  - $this->parcels_return_merchant_fees->sum('current_payable')) - $this->parcels_return_merchant_fees->sum('return_charges')),
           "invoice_date"        =>Carbon::parse($this->invoice_date)->format('d M Y'),
 
        ];
    }
}
