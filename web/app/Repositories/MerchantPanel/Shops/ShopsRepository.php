<?php
namespace App\Repositories\MerchantPanel\Shops;

use App\Models\MerchantShops;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use App\Models\Backend\Merchant;
use Illuminate\Support\Facades\Auth;

class ShopsRepository implements ShopsInterface{

    public function all($id){
        return MerchantShops::where('merchant_id',$id)->orderBy('id','desc')->paginate(10);
    }

    /**
     * Boutiques du marchand connecté, et rien d'autre.
     *
     * Le socle lisait `MerchantShops::where('id', $id)` sans filtre : depuis
     * l'API comme depuis le panneau marchand, changer l'identifiant dans l'URL
     * suffisait à lire, modifier ou supprimer la boutique d'un autre marchand
     * (même schéma que S17 sur les colis). Toute lecture et toute écriture
     * passent désormais par ce périmètre ; hors périmètre, on rend `null`.
     */
    private function ownedShops(){
        $merchant = Auth::user() ? Auth::user()->merchant : null;
        return MerchantShops::where('merchant_id', $merchant ? $merchant->id : 0);
    }

    public function get($id){
        return $this->ownedShops()->where('id',$id)->first();
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
                $shop               = $this->get($id);
                if(!$shop){
                    return false;
                }
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
        $shop = $this->get($id);
        return $shop ? $shop->delete() : false;
    }


}

