<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantDeliveryCharge\MerchantDeliveryChargeRequest;
use App\Models\Backend\DeliveryCharge;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class MerchantDeliveryChargeController extends Controller
{
    protected $repo;
    protected $repoMerchant;

    public function __construct(MerchantDeliveryChargeInterface $repo,MerchantInterface $repoMerchant )
    {
            $this->repo=$repo;
            $this->repoMerchant=$repoMerchant;

    }
    public function index($id){
        $singleMerchant          = $this->repoMerchant->get($id);
        $merchantDeliveryCharges = $this->repo->all($id);
        if(blank($singleMerchant)){
            abort(404);
        }
        return view('backend.merchant.delivery-charge.index',compact('singleMerchant','merchantDeliveryCharges'));
    }

    public function create($id){
        $deliveryCharges = $this->repo->delivery_charges_get();
        $singleMerchant  = $this->repoMerchant->get($id);

        if(blank($singleMerchant)){
            abort(404);
        }
        return view('backend.merchant.delivery-charge.create',compact('deliveryCharges','singleMerchant'));
    }

    public function store(MerchantDeliveryChargeRequest $request, $merchant){

        if($this->repo->store($request,$merchant)){
            Toastr::success(__('merchant.delivery_charge_added_msg'),__('message.success'));
            return redirect()->route('merchant.deliveryCharge.index',$merchant);
        }else{
            Toastr::error(__('merchant.delivery_charge_error_msg'),__('message.error'));
            return redirect()->back()->withInput();
        }
    }

    public function edit($merchant,$id){
        $deliveryCharges        = $this->repo->delivery_charges_get();
        $merchantDeliveryCharge = $this->repo->get($merchant,$id);
        $singleMerchant         = $this->repoMerchant->get($merchant);
        if(blank($singleMerchant) || blank($merchantDeliveryCharge)){
            abort(404);
        }
        return view('backend.merchant.delivery-charge.edit', compact('deliveryCharges','merchantDeliveryCharge','singleMerchant'));
    }

    public function update(MerchantDeliveryChargeRequest $request,$merchant,$id){

        if($this->repo->update($request,$id,$merchant)){
            Toastr::success(__('merchant.delivery_charge_update_msg'),__('message.success'));
            return redirect()->route('merchant.deliveryCharge.index',$merchant);
        }else{
            Toastr::error(__('merchant.delivery_charge_update_msg'),__('message.error'));
            return redirect()->back()->withInput();
        }
    }
    public function delete($merchant,$id){
        $this->repo->delete($id,$merchant);
        Toastr::success(__('merchant.delivery_charge_delete_msg'),__('message.success'));
        return back();
    }

    public function deliveryChargeInfo(Request $request)
    {
        if (request()->ajax()) {
            if ($request->delivery_charge_id) {
                $deliveryCharge = DeliveryCharge::find($request->delivery_charge_id);
                if (!blank($deliveryCharge)) {
                    return view('backend.merchant.delivery-charge.deliveryChargeInfo', compact('deliveryCharge'));
                }
                return '';
            }
        }
        return '';
    }


}
