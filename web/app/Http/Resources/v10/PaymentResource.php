<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            "transaction_id"    => (string)$this->transaction_id,
            "description"       => $this->description,
            "amount"            => (string) $this->amount,
            "currency"          => settings()->currency,
            "payment_method"    => $this->merchantAccount->payment_method,
            "paymentMethodName" => __('merchant.'.$this->merchantAccount->payment_method),
            "bank_name"         => $this->merchantAccount->bank_name,
            "holder_name"       => $this->merchantAccount->holder_name,
            "account_no"        => (string) $this->merchantAccount->account_no,
            "branch_name"       => $this->merchantAccount->branch_name,
            "routing_no"        => (string) $this->merchantAccount->routing_no,
            "mobile_company"    => $this->merchantAccount->mobile_company,
            "mobile_no"         => $this->merchantAccount->mobile_no,
            "account_type"      => $this->merchantAccount->account_type,
            "status"            => (int) $this->status,
            "statusName"        => trans("approvalstatus.".$this->status),
            'request_date'      => $this->created_at->format('d M Y, h:i A'),
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }

}
