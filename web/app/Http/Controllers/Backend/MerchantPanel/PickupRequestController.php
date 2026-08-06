<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\PickupRequest\StoreRequest;
use App\Repositories\MerchantPanel\PickupRequest\PickupRequestInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
class PickupRequestController extends Controller
{
    protected $repo;
    public function __construct(PickupRequestInterface $repo){
        $this->repo = $repo;
    }
    public function regularStore(Request $request){
        if($this->repo->regularStore($request)):
            Toastr::success(__('pickupRequest.pickup_requested_succesfully'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function expressStore(Request $request){
        $validator = Validator::make($request->all(),[
            'weight'     => ['numeric'],
            'phone'      => ['numeric'],
            'cod_amount' => ['numeric'],
        ]);

        if($validator->fails()):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;

        if($this->repo->expressStore($request)):
            Toastr::success(__('pickupRequest.pickup_requested_succesfully'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

}
