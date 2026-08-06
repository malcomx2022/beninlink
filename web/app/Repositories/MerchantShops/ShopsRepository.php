<?php
namespace App\Repositories\MerchantShops;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use App\Repositories\MerchantShops\ShopsInterface;

class ShopsRepository implements ShopsInterface{

        public function all(){
           return MerchantShops::orderBy('id','desc')->paginate(10);
        }
        public function get($id){
            return MerchantShops::where('id',$id)->first();
        }

        public function merchant_shops_get($id){
            return MerchantShops::where('merchant_id',$id)->get();
        }

    public function defaultShop($merchant_id,$id) {
        $merchantShops              = MerchantShops::where(['default_shop'=>Status::ACTIVE,'merchant_id'=>$merchant_id])->get();
        foreach ($merchantShops as $merchant){
            $merchant->default_shop = Status::INACTIVE;
            $merchant->save();
        }
        $merchantShop               = MerchantShops::where(['id'=>$id,'merchant_id'=>$merchant_id])->first();
        $merchantShop->default_shop = Status::ACTIVE;
        $merchantShop->save();

        return true;
    }

    public function store($request){

        try {
            $shop                   = new MerchantShops();
            $shop->merchant_id      = $request->merchant_id;
            $shop->name             = $request->name;
            $shop->contact_no       = $request->contact_no;
            $shop->address          = $request->address;
            $shop->merchant_lat     = $request->lat;
            $shop->merchant_long    = $request->long;
            $shop->status           = $request->status;
            $shop->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }

        public function update($request){

            try {
                $shop              = MerchantShops::where('id',$request->id)->first();
                $shop->merchant_id = $request->merchant_id;
                $shop->name        = $request->name;
                $shop->contact_no  = $request->contact_no;
                $shop->address     = $request->address;
                $shop->merchant_lat= $request->lat;
                $shop->merchant_long= $request->long;
                $shop->status      = $request->status;
                $shop->save();
                return true;

            } catch (\Throwable $th) {
                return false;
            }

        }

        public function delete($id){
            return MerchantShops::destroy($id);
        }


}

