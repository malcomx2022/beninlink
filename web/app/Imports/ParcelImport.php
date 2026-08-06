<?php

namespace App\Imports;

use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Enums\DeliveryType;
use App\Enums\DeliveryTime;
use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

use Illuminate\Support\Str;


class ParcelImport implements ToModel, WithHeadingRow ,WithValidation , SkipsEmptyRows
{
    use Importable;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

     public function RandomTrackingID(){
        return Str::upper(settings()->par_track_prefix).random_int(11111111,99999999);  
    }

    public function model(array $row)
    {

        if(isset($row['merchant_id']) && !blank($row['merchant_id'])) {
            $merchant = Merchant::where('id',$row['merchant_id'])->first();
        }else {
            $merchant = Merchant::where('user_id',auth()->user()->id)->first();
        }
        if(auth()->user()->merchant):
            $category_id      = 1;
            $delivery_type_id = 2;
            $liquid_fragile   = null;
            $packaging_id     = null;
            $merchantshop     = MerchantShops::where(['merchant_id'=>$merchant->id,'default_shop'=>Status::ACTIVE])->first();
            $shop_id          = $merchantshop->id;
            $pickup_phone     = $merchantshop->contact_no;
            $pickup_address   = $merchantshop->address;
            $pickup_lat       = $merchantshop->merchant_lat;
            $pickup_long      = $merchantshop->merchant_long;
        else:
            $category_id      = $row['category_id'];
            $delivery_type_id = $row['delivery_type_id'];
            $liquid_fragile   = $row['liquid_fragile'];
            $packaging_id     = $row['packaging_id'];
            $shop_id          = $row['shop_id'];
            $pickup_phone     = $row['pickup_phone'];
            $pickup_address   = $row['pickup_address'];

            $pickup_lat       = $row['pickup_lat'];
            $pickup_long      = $row['pickup_long'];

        endif;

        $deliveryChargeAmount = $this->deliveryCharge($merchant->id,$category_id,$row['weight'],$delivery_type_id);
        $codChargeAmount      = $this->codCharge($merchant,$row['cash_collection'],$delivery_type_id);
        $liquidFragileAmount  = null;
        $packagingAmount      = 0;
        $codAmount            = $codChargeAmount['codAmount'];
        $merchantCodCharge    = $codChargeAmount['merchantCodCharge'];
        $vat                  = $merchant->vat;
        if($liquid_fragile){
            $liquidFragileAmount = SettingHelper('fragile_liquid_charge');
        }
        if(!blank($packaging_id)){
            $packagingAmount     = Packaging::findOrFail($row['packaging_id'])->price ?? 0;
        }
        $totalParcelAmount  = ($deliveryChargeAmount+$codAmount+$liquidFragileAmount+$packagingAmount);
        $vatTextAmount      = $this->percentage($totalParcelAmount,$vat);
        $totalCurrentAmount = ($row['cash_collection']-$totalParcelAmount)-$vatTextAmount;

        $deliveryTime = [
            'pickup'       =>date('Y-m-d'),
            'delivery'     =>date('Y-m-d'),
        ];

        // Pickup & Delivery Time
        if($delivery_type_id == DeliveryType::SAMEDAY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d'),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                ];
            }
        }
        elseif($delivery_type_id == DeliveryType::NEXTDAY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +2 day')),
                ];
            }
        }
        elseif($delivery_type_id == DeliveryType::SUBCITY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY .' day')),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY + 1 .' day')),
                ];
            }
        }
        elseif($delivery_type_id == DeliveryType::OUTSIDECITY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY .' day')),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY + 1 .' day')),
                ];
            }
        }
        // End Pickup & Delivery Time

        $n = (int)floor(microtime(true) * 1000) % 1000000000;
        $parcels = [
            'merchant_id'       => $merchant->id,
            'first_hub_id'      => $merchant->user->hub_id,
            'hub_id'            => $merchant->user->hub_id,
            'category_id'       => $category_id,
            'weight'            => $row['weight'],
            'invoice_no'        => $row['invoice_no'],
            'cash_collection'   => $row['cash_collection'],
            'selling_price'     => $row['selling_price'],
            'merchant_shop_id'  => $shop_id,
            'pickup_phone'      => $pickup_phone,
            'pickup_address'    => $pickup_address,
            'pickup_lat'        => $pickup_lat,
            'pickup_long'       => $pickup_long,
            'customer_name'     => $row['customer_name'],
            'customer_phone'    => $row['customer_phone'],
            'customer_address'  => $row['customer_address'],
            'customer_lat'      => $row['customer_lat'],
            'customer_long'     => $row['customer_long'],
            'delivery_type_id'  => $delivery_type_id,
            'pickup_date'       => $deliveryTime['pickup'],
            'delivery_date'     => $deliveryTime['delivery'],
            'vat'               => $vat,
            'vat_amount'        => $vatTextAmount,
            'delivery_charge'   => $deliveryChargeAmount,
            'cod_charge'        => $merchantCodCharge,
            'cod_amount'        => $codAmount,
            'total_delivery_amount'=> $totalParcelAmount,
            'current_payable'   => $totalCurrentAmount,
            'tracking_id'       =>  $this->RandomTrackingID(),
            'note'              => $row['note'],
            'packaging_id'      => $packaging_id,
            'packaging_amount'  => $packagingAmount,
            'liquid_fragile_amount' => $liquidFragileAmount,
            'status'            => ParcelStatus::PENDING,
            'created_at'        =>date('Y-m-d H:i:s'),
            'updated_at'        =>date('Y-m-d H:i:s'),
        ];
        $parcel = Parcel::create($parcels);
        return $parcel;
    }

    public function rules(): array
    {
        if(auth()->user()->merchant):
            $shop_id           = ['numeric'];
            $category_id       = ['numeric'];
            $delivery_type_id  = ['numeric'];
        else:
            $shop_id           = ['required','numeric'];
            $category_id       = ['required','numeric'];
            $delivery_type_id  = ['required','numeric'];
        endif;
        return [
            'shop_id'           => $shop_id,
            'cash_collection'   => ['required','numeric'],
            'category_id'       => $category_id,
            'delivery_type_id'  => $delivery_type_id,
            'customer_name'     => ['required','string','max:191'],
            'customer_address'  => ['required','string','max:191'],
        ];
    }

    private function deliveryCharge($merchant_id,$category_id,$weight,$delivery_type_id)
    {
        if ($merchant_id && $category_id && $weight !='0' && $delivery_type_id) {
            $charges = MerchantDeliveryCharge::where([
                'merchant_id'=>$merchant_id,
                'category_id'=>$category_id,
                'weight'=>$weight
            ])->first();
            if (blank($charges)) {
                $charges = DeliveryCharge::where(['category_id'=>$category_id])->first();
            }

        } else {
            $charges = MerchantDeliveryCharge::where(['merchant_id'=>$merchant_id,'category_id'=>$category_id,'weight'=>$weight])->first();
            if (blank($charges)) {
                $charges = DeliveryCharge::where(['category_id'=>$category_id])->first();
            }
        }

        if (!blank($charges)) {
            if($delivery_type_id == '1'){
                $chargeAmount = $charges->same_day;
            }elseif ($delivery_type_id == '2') {
                $chargeAmount = $charges->next_day;
            }elseif ($delivery_type_id == '3') {
                $chargeAmount = $charges->sub_city;
            }elseif ($delivery_type_id == '4') {
                $chargeAmount = $charges->outside_city;
            }else {
                $chargeAmount = 0;
            }

            return $chargeAmount;
        }
        return 0;
    }

    private function codCharge($merchant,$cash_collection,$delivery_type_id)
    {
        $data = [];
        $data['merchantCodCharge'] = 0;
        $data['codAmount'] = 0;

        if($delivery_type_id !=='' && $delivery_type_id =='1' || $delivery_type_id =='2'){
            $data['merchantCodCharge'] = $merchant->cod_charges['inside_city'];
            $data['codAmount'] = $this->percentage($cash_collection, $merchant->cod_charges['inside_city']);
        }else if($delivery_type_id !='' && $delivery_type_id =='3'){
            $data['merchantCodCharge'] = $merchant->cod_charges['sub_city'];
            $data['codAmount'] = $this->percentage($cash_collection, $merchant->cod_charges['sub_city']);
        }else if($delivery_type_id !='' && $delivery_type_id =='4') {
            $data['merchantCodCharge'] = $merchant->cod_charges['outside_city'];
            $data['codAmount'] = $this->percentage($cash_collection, $merchant->cod_charges['outside_city']);
        }else {
            $data['merchantCodCharge'] = 0;
            $data['codAmount'] = $this->percentage($cash_collection, 0);
        }
        return $data;
    }

    private function percentage($cash_collection,$cod_charges){
        return $cash_collection * ($cod_charges / 100);
    }
}
