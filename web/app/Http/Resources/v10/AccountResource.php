<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            "payment_method"    => $this->payment_method,
            "paymentMethodName" => __('merchant.'.$this->payment_method),
            "bank_name"         => $this->bank_name,
            "holder_name"       => $this->holder_name,
            "account_no"        => (string) $this->account_no,
            "branch_name"       => $this->branch_name,
            "routing_no"        => $this->routing_no,
            "mobile_company"    => $this->mobile_company,
            "mobile_no"         => (string) $this->mobile_no,
            "account_type"      => $this->account_type,
            "status"            => (int)$this->status,
            "statusName"        => trans("status.".$this->status),
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }

}
