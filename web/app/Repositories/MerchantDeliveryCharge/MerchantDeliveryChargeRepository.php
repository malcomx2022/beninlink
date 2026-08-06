<?php
namespace App\Repositories\MerchantDeliveryCharge;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\MerchantDeliveryCharge;

class MerchantDeliveryChargeRepository implements MerchantDeliveryChargeInterface{

        public function all($id){
           return MerchantDeliveryCharge::companywise()->where(['merchant_id'=>$id])->orderBy('weight')->paginate(10);
        }
        public function getAll($id){
           return MerchantDeliveryCharge::companywise()->where(['merchant_id'=>$id])->orderBy('weight')->get();
        }
        public function get($merchant_id,$id){
            return MerchantDeliveryCharge::companywise()->where(['id'=>$id,'merchant_id'=>$merchant_id])->first();
        }

        public function delivery_charges_get(){
            return DeliveryCharge::companywise()->orderBy('weight','asc')->get();
        }

        public function store($request,$merchant_id){

            try {
                $deliverycharge = DeliveryCharge::find($request->delivery_charge_id);
                $deliveryCharge                      = new MerchantDeliveryCharge();
                $deliveryCharge->company_id          = settings()->id;
                $deliveryCharge->merchant_id         = $merchant_id;
                $deliveryCharge->delivery_charge_id  = $request->delivery_charge_id;
                $deliveryCharge->category_id         = $deliverycharge->category_id;
                $deliveryCharge->weight              = $deliverycharge->weight;
                $deliveryCharge->same_day            = $request->same_day;
                $deliveryCharge->next_day            = $request->next_day;
                $deliveryCharge->sub_city            = $request->sub_city;
                $deliveryCharge->outside_city        = $request->outside_city;
                $deliveryCharge->status              = $request->status;
                $deliveryCharge->save();
                 return true;

            } catch (\Throwable $th) {
                return false;
            }
        }

        public function update($request,$id, $merchant_id){

            try {
                $deliverycharge = DeliveryCharge::find($request->delivery_charge_id);
                $deliveryCharge                       = MerchantDeliveryCharge::where(['id'=>$id,'merchant_id'=>$merchant_id])->first();
                $deliveryCharge->company_id          = settings()->id;
                $deliveryCharge->merchant_id         = $merchant_id;
                $deliveryCharge->delivery_charge_id  = $request->delivery_charge_id;
                $deliveryCharge->category_id         = $deliverycharge->category_id;
                $deliveryCharge->weight              = $deliverycharge->weight;
                $deliveryCharge->same_day            = $request->same_day;
                $deliveryCharge->next_day            = $request->next_day;
                $deliveryCharge->sub_city            = $request->sub_city;
                $deliveryCharge->outside_city        = $request->outside_city;
                $deliveryCharge->status              = $request->status;
                $deliveryCharge->save();

                 return true;

            } catch (\Throwable $th) {
                return false;
            }

        }

        public function delete($id,$merchant_id){
            $charge = MerchantDeliveryCharge::find($id);
            if($charge->company_id == settings()->id):
                return MerchantDeliveryCharge::destroy($id);
            endif;
            return false;
        }


}

