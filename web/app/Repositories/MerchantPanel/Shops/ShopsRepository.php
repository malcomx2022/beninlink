<?php
namespace App\Repositories\MerchantPanel\Shops;

use App\Models\MerchantShops;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use App\Models\Backend\Merchant;

class ShopsRepository implements ShopsInterface{

    public function all($id){
        return MerchantShops::where('merchant_id',$id)->orderBy('id','desc')->paginate(10);
    }

    public function get($id){
        return MerchantShops::where('id',$id)->first();
    }

    public function getMerchant($id){
        return Merchant::where('user_id',$id)->first();
    }

    public function store($id, $request){
        try {
                $shop              = new MerchantShops();
                $shop->merchant_id = $id;
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

    public function update($id, $request){

        try {
                $shop               = MerchantShops::where('id',$id)->first();
                $shop->name         = $request->name;
                $shop->contact_no   = $request->contact_no;
                $shop->address      = $request->address;
                $shop->merchant_lat = $request->lat;
                $shop->merchant_long= $request->long;
                $shop->status       = $request->status;
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

