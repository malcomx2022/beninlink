<?php

namespace App\Http\Resources\v10;

use App\Enums\BooleanStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use Carbon\Carbon;
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
        $total_deliverd         = Parcel::companywise()->whereIn('id',$this->parcels_id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('cash_collection');
        $partials_deliverd      = Parcel::companywise()->whereIn('id',$this->parcels_id)->whereIn('status',[ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,ParcelStatus::RETURN_TO_COURIER])->where('partial_delivered',BooleanStatus::YES)->sum('cash_collection');
        $total_delivery_charge  = Parcel::companywise()->whereIn('id',$this->parcels_id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('delivery_charge');
        $total_cod_charge       = Parcel::companywise()->whereIn('id',$this->parcels_id)->sum('cod_amount');
        $total_return_fee       = Parcel::companywise()->whereIn('id',$this->parcels_id)->whereIn('status',[ParcelStatus::RETURN_TO_COURIER,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,ParcelStatus::RETURN_ASSIGN_TO_MERCHANT])->sum('return_charges');
        $total_return_charge    = Parcel::companywise()->whereIn('id',$this->parcels_id)->whereIn('status',[ParcelStatus::RETURN_TO_COURIER,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,ParcelStatus::RETURN_ASSIGN_TO_MERCHANT])->sum('delivery_charge');
        $total_delivered_amount = ($total_deliverd + $partials_deliverd);
        $payable_amount         = ((($total_delivered_amount - $total_delivery_charge) - $total_cod_charge)  - $total_return_fee - $total_return_charge);
        $total_parcels          = Parcel::companywise()->whereIn('id',$this->parcels_id)->count();

        return [
            "id"                    => $this->id,
            "invoice_id"            => $this->invoice_id,
            "status"                => $this->InvoiceStatus,
            "total_deliverd_amount" => $total_delivered_amount,
            "delivery_charge"       => $total_delivery_charge,
            "cod_amount"            => $total_cod_charge, 
            "total_return_fee"      => $total_return_fee,
            "payable_amount"        => $payable_amount,
            "invoice_date"          => Carbon::parse($this->invoice_date)->format('d M Y'),
            "merchant_name"         => $this->merchant->business_name,
            "merchant_phone"        => $this->merchant->user->mobile,
            "merchant_address"      => $this->merchant->address,
            "total_parcels"         => $total_parcels,   
            "parcels"               => $this->InvoiceParcelList,
             
        ];
    }
}
