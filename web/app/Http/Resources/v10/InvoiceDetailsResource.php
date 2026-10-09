<?php

namespace App\Http\Resources\v10;

use App\Services\Invoicing\SettlementStatement;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $statement = SettlementStatement::for($this->resource);
        $totals = $statement['totals'];
        // Les autres frais HT (emballage, fragile) font aussi partie du relevé figé.
        $otherFees = $totals['fees_ht'] - $totals['delivery_fee'] - $totals['cod_fee'] - $totals['return_fee'];

        return [
            "id"                    => $this->id,
            "invoice_id"            => $this->invoice_id,
            "status"                => $this->InvoiceStatus,
            "total_deliverd_amount" => $totals['collected'],
            "delivery_charge"       => $totals['delivery_fee'],
            "cod_amount"            => $totals['cod_fee'],
            "total_return_fee"      => $totals['return_fee'],
            "payable_amount"        => $totals['net_recorded'],
            "vat_amount"           => $totals['vat'],
            "other_fees"           => $otherFees,
            "fees_ht"              => $totals['fees_ht'],
            "fees_ttc"             => $totals['fees_ttc'],
            "statement_consistent" => $totals['consistent'],
            "invoice_date"          => dateFormat($this->invoice_date),
            "merchant_name"         => $this->merchant->business_name,
            "merchant_phone"        => $this->merchant->user->mobile,
            "merchant_address"      => $this->merchant->address,
            "total_parcels"         => count($statement['lines']),
            "parcels"               => null,

        ];
    }
}
