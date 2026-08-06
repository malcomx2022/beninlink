<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Hub\StoreHubRequest;
use App\Http\Requests\Hub\UpdateHubRequest;
use App\Models\Backend\Parcel;
use App\Repositories\Hub\HubInterface;
use Brian2694\Toastr\Facades\Toastr;
class HubController extends Controller
{
    protected $repo;
    public function __construct(HubInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $hubs = $this->repo->all();
        return view('backend.hub.index',compact('hubs','request'));
    }
    public function filter(Request $request)
    {
        $hubs = $this->repo->filter($request);
        return view('backend.hub.index',compact('hubs','request'));
    }

    public function create()
    {
        return view('backend.hub.create');
    }

    public function store(StoreHubRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('hub.added_msg'),__('message.success'));
            return redirect()->route('hubs.index');
        }else{
            Toastr::error(__('hub.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $hub = $this->repo->get($id);
        return view('backend.hub.edit',compact('hub'));
    }

    public function update(UpdateHubRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('hub.update_msg'),__('message.success'));
            return redirect()->route('hubs.index');
        }else{
            Toastr::error(__('hub.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('hub.delete_msg'),__('message.success'));
        return back();
    }


    public function view(Request $request,$id){

        $data['parcels']                = $this->repo->parcelFilter($request,$id)->paginate(15);
        $data['t_parcels']              = $this->repo->parcelFilter($request,$id)->get();
        $data['total_parcels']          = $data['t_parcels']->count();
        $data['total_cash_collection']  = $data['t_parcels']->sum('cash_collection');
        $data['total_delivered_cash_collection']           = $this->repo->parcelFilter($request,$id)->where('status',ParcelStatus::DELIVERED)->get()->sum('cash_collection');
        $data['total_partials_delivered_cash_collection']  = $this->repo->parcelFilter($request,$id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->get()->sum('cash_collection');
        $data['total_in_transit_cash_collection']          = $this->repo->parcelFilter($request,$id)->whereNotIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->get()->sum('cash_collection');
        $data['total_delivery_charges']  = $data['t_parcels']->sum('delivery_charge');
        $data['total_vat_amount']        = $data['t_parcels']->sum('vat_amount');
        $data['parcelsGrouped']          = $data['t_parcels']->groupBy('status');
        return view('backend.hub.view',compact('data','request','id'));
    }

}
