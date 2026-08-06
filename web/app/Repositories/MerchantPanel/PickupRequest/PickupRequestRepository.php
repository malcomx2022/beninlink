<?php
namespace App\Repositories\MerchantPanel\PickupRequest;

use App\Enums\PickupRequestType;
use App\Models\PickupRequest;
use App\Repositories\MerchantPanel\PickupRequest\PickupRequestInterface;
use Illuminate\Support\Facades\Auth;

class PickupRequestRepository implements PickupRequestInterface {
    public function getRegular (){
       return PickupRequest::companywise()->where('request_type',PickupRequestType::REGULAR)->orderByDesc('id')->paginate(15);
    }
    public function getExpress (){
        return PickupRequest::companywise()->where('request_type',PickupRequestType::EXPRESS)->orderByDesc('id')->paginate(15);
    }

    public function regularStore($request){

        try {
            $pickup_request                   = new PickupRequest();
            $pickup_request->company_id       = settings()->id;
            $pickup_request->request_type     = PickupRequestType::REGULAR;
            $pickup_request->merchant_id      = Auth::user()->merchant->id;
            $pickup_request->address          = Auth::user()->merchant->address;
            $pickup_request->note             = $request->note;
            if($request->parcel_quantity !== ''):
                $pickup_request->parcel_quantity  = $request->parcel_quantity;
            endif;
            $pickup_request->save();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function expressStore($request){
        try {
            $pickup_request                   = new PickupRequest();
            $pickup_request->company_id       = settings()->id;
            $pickup_request->request_type     = PickupRequestType::EXPRESS;
            $pickup_request->merchant_id      = Auth::user()->merchant->id;
            $pickup_request->address          = $request->address;
            $pickup_request->name             = $request->name;
            $pickup_request->phone            = $request->phone;
            $pickup_request->cod_amount       = $request->cod_amount;
            $pickup_request->invoice          = $request->invoice;
            $pickup_request->weight           = $request->weight;
            if($request->exchange):
                $pickup_request->exchange    = 1;
            endif;

            $pickup_request->note             = $request->note;
            $pickup_request->save();

            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }
}
