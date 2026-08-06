<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\MerchantOnlinePayment;
use App\Repositories\PayoutSetup\PayoutSetupInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class PayoutSetupController extends Controller
{

    protected $repo,$MOPrepo;
    public function __construct(PayoutSetupInterface $repo, MerchantOnlinePayment $MOPmodel){
        $this->repo     = $repo;
        $this->MOPmodel  = $MOPmodel;
    }

    public function index(){
        return view('backend.setting.payout_setup.index');
    }

    public function PayoutSetupUpdate(Request $request,$paymentMethod){

        if($this->repo->update($paymentMethod,$request)):
            Toastr::success(__('menus.payout_setup_updated'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function onlinePaymentList(){
            $payments =  $this->MOPmodel::orderByDesc('id')->paginate(10);
            return view('backend.online_payment.online_payment_list',compact('payments'));
    }

}
