<?php
namespace App\Repositories\MerchantShops;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use App\Repositories\MerchantShops\ShopsInterface;

class ShopsRepository implements ShopsInterface{

        /**
         * S26 — le périmètre société de ce dépôt, qui n'en avait aucun.
         *
         * `MerchantShops` ne porte pas de `company_id` : le rattachement passe
         * par son marchand. Les quatre lectures et écritures de cette classe
         * faisaient `where('id', …)` nu — un compte authentifié atteignait donc
         * la boutique d'un marchand de n'importe quelle société : adresse,
         * téléphone, et pour `defaultShop()` la possibilité de **changer** la
         * boutique par défaut d'un concurrent.
         *
         * ⚠️ Ne pas confondre avec `MerchantPanel\Shops\ShopsRepository`, que
         * **S18** a scopé sur le marchand CONNECTÉ : celui-ci sert le
         * back-office, où l'opérateur agit légitimement sur les boutiques de
         * tous les marchands — mais de SA société seulement.
         */
        private function boutiquesDeLaSociete(){
            return MerchantShops::whereHas('merchant', function ($query) {
                $query->companywise();
            });
        }

        public function all(){
           return $this->boutiquesDeLaSociete()->orderBy('id','desc')->paginate(10);
        }
        public function get($id){
            return $this->boutiquesDeLaSociete()->where('id',$id)->first();
        }

        public function merchant_shops_get($id){
            return $this->boutiquesDeLaSociete()->where('merchant_id',$id)->get();
        }

    public function defaultShop($merchant_id,$id) {
        // La boutique visée d'abord : hors périmètre, on ne touche à RIEN. Le
        // socle basculait les anciennes boutiques par défaut AVANT de chercher
        // la nouvelle, puis faisait une erreur fatale sur `null` — laissant le
        // marchand sans boutique par défaut du tout.
        $merchantShop = $this->boutiquesDeLaSociete()
            ->where(['id' => $id, 'merchant_id' => $merchant_id])->first();

        if (blank($merchantShop)) {
            return false;
        }

        $this->boutiquesDeLaSociete()
            ->where(['default_shop' => Status::ACTIVE, 'merchant_id' => $merchant_id])
            ->get()
            ->each(function ($boutique) {
                $boutique->default_shop = Status::INACTIVE;
                $boutique->save();
            });

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
                // S29 — ⚠️ TROU DE S26 : ce lot-la avait scope les QUATRE lectures du
                // depot (`all`, `get`, `merchant_shops_get`, `defaultShop`) et laisse
                // `update()` et `delete()` nues. Ici, la boutique d'un marchand d'une
                // AUTRE societe se reecrivait — et la ligne `merchant_id` juste en
                // dessous permettait en plus de la RATTACHER a un autre marchand.
                $shop              = $this->boutiquesDeLaSociete()->where('id',$request->id)->first();

                if (blank($shop)) {
                    return false;
                }

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
            // S29 — meme trou que `update()` : suppression nue.
            $shop = $this->boutiquesDeLaSociete()->where('id',$id)->first();

            return $shop ? $shop->delete() : 0;
        }


}

