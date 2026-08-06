<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Models\Backend\MerchantOnlinePayment;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\MerchantOnlinePaymentSetup\PaymentSetupInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantOnlinePaymentSetupController extends Controller
{
    protected $repo,$MOPrepo;
    public function __construct(PaymentSetupInterface $repo, MerchantOnlinePaymentReceived $MOPRmodel){
        $this->repo     = $repo;
        $this->MOPRmodel  = $MOPRmodel;
    }
    public function index(){
        return view('backend.merchant_panel.settings.online_payment_setup.index');
    }
    public function paymentReceivedSetupUpdate(Request $request,$paymentMethod){

        if($this->repo->update($paymentMethod,$request)):
            Toastr::success(__('menus.payout_setup_updated'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
}
