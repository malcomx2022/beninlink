<?php

namespace App\Http\Controllers;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapParcelController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @param $id
     * @param $status
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function parcelMap($id,$lat,$long,$status)
    {

        $user = User::find($id)->deliveryman->id;

        $parcels =  Parcel::companywise()->orderBy('updated_at')->orderBy('priority_type_id')->with(['merchant'])->where('status',$status)->where(function($query) use ($user){
            $query->wherehas('parcelEvent',function($eventquery)  use ($user) {
                $eventquery->where('delivery_man_id',$user);
            });
        })->get();
        $mapParcels = [];
        if(!blank($parcels)) {
            foreach($parcels as $key => $parcel) {
                $mapParcels[$key]['latitude'] = $parcel->customer_lat;
                $mapParcels[$key]['longitude'] = $parcel->customer_long;
                $mapParcels[$key]['customer_name'] = $parcel->customer_name;
                $mapParcels[$key]['customer_address'] = $parcel->customer_address;
                $mapParcels[$key]['customer_phone'] = $parcel->customer_phone;
                $mapParcels[$key]['merchant_business_name'] = $parcel->merchant->business_name;
                $mapParcels[$key]['merchant_phone'] = $parcel->merchant->user->mobile;
                $mapParcels[$key]['merchant_address'] = $parcel->merchant->address;
                $mapParcels[$key]['current_payable'] = $parcel->current_payable;
                $mapParcels[$key]['tracking_id'] = $parcel->tracking_id;
            }
        }

        return view('backend.deliveryman.parcel.parcel-map',compact('mapParcels','lat','long'));
    }
}
