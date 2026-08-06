<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionsResource extends JsonResource
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
            "merchant_id"       => (string) $this->merchant_id,
            "transaction_id"    => (string) $this->transaction_id,
            "merchantAccount"   => new PaymentAccountResource ($this->merchantAccount),
            "amount"            =>  number_format($this->amount,2),
            "currency"          => (string) settings()->currency,
            "status"            => (int)$this->status,
            "statusName"        => trans("approvalstatus.".$this->status),
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }

}
