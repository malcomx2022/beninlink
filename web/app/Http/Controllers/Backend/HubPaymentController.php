<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\HubManage\Payment\ProcessRequest;
use App\Http\Requests\HubManage\Payment\StoreRequest;
use App\Http\Requests\HubManage\Payment\UpdateRequest;
use App\Models\Backend\Account;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\HubManage\HubPayment\HubPaymentInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Ramsey\Uuid\Type\Decimal;
use Brian2694\Toastr\Facades\Toastr;
class HubPaymentController extends Controller
{

   protected $hub;
   protected $account;
   protected $payment;

    public function __construct(
        AccountInterface $account,
        HubInterface $hub,
        HubPaymentInterface $payment
        )
    {
            $this->account  = $account;
            $this->payment  = $payment;
            $this->hub     = $hub;
    }
    public function index(){
        $payments = $this->payment->all();
        return view('backend.hub_payment.index',compact('payments'));
    }

    public function create(){
        $hubs        = $this->hub->all();
        $accounts   = $this->account->all();
        return view('backend.hub_payment.create',compact('hubs','accounts'));
    }


    //payment store
    public function paymentStore(StoreRequest $request){

        if($request->isprocess):
            // S51 — `Account::find()` etait nu : on LISAIT le solde du compte de
            // tresorerie d'une autre societe pour le comparer au montant. Le
            // depot refuse desormais ce versement, mais la comparaison se
            // faisait AVANT lui, et renseignait deja sur un solde d'en face.
            $courier_account = Account::companywise()->find($request->from_account);
            if(blank($courier_account)){
                Toastr::error(__('hub_payment.error_msg'),__('message.error'));
                return back()->withInput();
            }
            if((double) $request->amount > $courier_account->balance){
                Toastr::warning(__('hub_payment.not_enough_courier_balance'),__('message.warning'));
                return back()->withInput();
            }
        endif;

        if($this->payment->store($request)){
            Toastr::success(__('hub_payment.added_msg'),__('message.success'));
            return redirect()->route('hub.hub-payment.index');
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }

    }

    //edit
    public function edit($id){
        $singlePayment    = $this->payment->get($id);
        $hubs             = $this->hub->all();
        $accounts         = $this->account->all();
        return view('backend.hub_payment.edit',compact('singlePayment','hubs','accounts'));
    }

    public function update(UpdateRequest $request,$id){

        //courier account balance check
            // S57 — meme defaut que `paymentStore` (corrige en S51), dans les deux
            // methodes que ce lot-la n'avait pas couvertes : la lecture du solde
            // precede la garde du depot, donc elle renseignait sur la tresorerie
            // d'en face avant tout refus.
        if($request->isprocess):
            $courier_account = Account::companywise()->find($request->from_account);
            if(blank($courier_account)){
                Toastr::error(__('hub_payment.error_msg'),__('message.error'));
                return back()->withInput();
            }
            if((double) $request->amount > $courier_account->balance){
                Toastr::warning(__('hub_payment.not_enough_courier_balance'),__('message.warning'));
                return back()->withInput();
            }
        endif;

        if($this->payment->update($id,$request)){
            Toastr::success(__('hub_payment.update_msg'),__('message.success'));
            return redirect()->route('hub.hub-payment.index');
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }

    public function destroy($id){
        $this->payment->delete($id);
        Toastr::success(__('hub_payment.delete_msg'),__('message.success'));
        return back();
    }


    //process section
    public function reject($id){
        if($this->payment->reject($id)){
            Toastr::success(__('hub_payment.rejected_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function cancelReject($id){
        if($this->payment->cancelReject($id)){
            Toastr::success(__('hub_payment.cancel_rejected_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function process($id){

        try {
            // S29 — `findOrFail($id)` etait NU, dans le controleur meme : l'ecran qui
            // precede le DECAISSEMENT s'ouvrait sur le versement d'une autre societe.
            $payment  = HubPayment::companywise()->findOrFail($id);
            $accounts = $this->account->all();
            return view('backend.hub_payment.process',compact('payment','accounts'));
        } catch (\Exception $exception){
            return redirect()->back();
        }

    }

    public function cancelProcess($id){
        if($this->payment->cancelProcess($id)){
            Toastr::success(__('hub_payment.cancel_processed_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function processed(ProcessRequest $request){
        // S57 — deux lectures nues d'un coup : le VERSEMENT d'en face (son
        // montant) et le COMPTE d'en face (son solde), compares avant que le
        // depot ne refuse. `->amount` sur `null` rendait de surcroit 500.
        $payment                    = HubPayment::companywise()->where('id',$request->id)->first();
        $courier_account            = Account::companywise()->find($request->from_account);
        if(blank($payment) || blank($courier_account)){
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return back()->withInput();
        }
        if((double) $payment->amount > $courier_account->balance){
            Toastr::warning(__('hub_payment.not_enough_courier_balance'),__('message.warning'));
            return back()->withInput();
        }

        if($this->payment->processed($request)){
            Toastr::success(__('hub_payment.processed_msg'),__('message.success'));
            return redirect()->route('hub.hub-payment.index');
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
}
