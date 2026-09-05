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

    public function index(\App\Services\Payments\FedaPayGateway $fedapay){
        // F2 — l'ecran doit dire d'un coup d'oeil sur quel environnement et sur
        // quel compte la passerelle tourne : c'est precisement ce qu'aucun
        // administrateur ne pouvait savoir tant que le `.env` etait la seule
        // surface de reglage.
        $companyId = settings()->id;

        return view('backend.setting.payout_setup.index', [
            'fedapayIsLive'           => $fedapay->isLive(),
            'fedapayUsesOwnAccount'   => $fedapay->usesOwnAccount($companyId),
            'fedapayHasWebhookSecret' => (bool) \App\Models\Backend\Setting::where('company_id', $companyId)
                                            ->where('key', 'fedapay_webhook_secret')->value('value'),
            'fedapayStatusActive'     => $fedapay->isEnabled($companyId),
        ]);
    }

    public function PayoutSetupUpdate(Request $request,$paymentMethod){

        if($this->repo->update($paymentMethod,$request)):
            Toastr::success(__('menus.payout_setup_updated'),__('message.success'));
            return redirect()->back();
        else:
            // Le repository depose une raison precise quand il en a une
            // (FedaPay : cle sans secret de webhook) ; sinon message generique.
            Toastr::error(session('payout_setup_error') ?: __('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function onlinePaymentList(){
            $payments =  $this->MOPmodel::orderByDesc('id')->paginate(10);
            return view('backend.online_payment.online_payment_list',compact('payments'));
    }

}
