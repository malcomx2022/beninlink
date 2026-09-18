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

        // S28 — `User::find($id)` etait NU : n'importe quel identifiant
        // d'utilisateur, de n'importe quelle societe, etait accepte, et la route
        // qui menait ici vivait hors du groupe `auth`. La vue verse
        // `@json($mapParcels)` dans la page : nom, telephone et adresse des
        // clients, plus le montant a encaisser. La route est retiree ; la portee
        // est posee ici aussi pour que la remonter ne rouvre pas la fuite.
        $livreur = User::companywise()->with('deliveryman')->find($id);
        abort_if(blank($livreur) || blank($livreur->deliveryman), 404);

        $user = $livreur->deliveryman->id;

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
