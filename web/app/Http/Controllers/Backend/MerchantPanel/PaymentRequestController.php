<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPanel\PaymentRequest\StoreRequest;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use Brian2694\Toastr\Facades\Toastr;
class PaymentRequestController extends Controller
{
    protected $repo;
    protected $merchantPayments;
    public function __construct(PaymentInterface $merchantPayments,PaymentRequestInterface $repo)
    {
        $this->merchantPayments=$merchantPayments;
        $this->repo=$repo;
    }
    public function index(){
        $payments=$this->merchantPayments->getSingleMerchantPayments(Auth::user()->merchant->id);
        return view('backend.merchant_panel.payment_request.index',compact('payments'));
    }

    public function create(){
        $merchant        = Merchant::find(Auth::user()->merchant->id);
        $merchantaccounts=MerchantPayment::where('merchant_id',$merchant->id)->get();
        return view('backend.merchant_panel.payment_request.create',compact('merchantaccounts','merchant'));
    }

    public function store(StoreRequest $request){
        $account = Auth::user()->merchant;
        $balance = (double) $account->current_balance;
        if((double) $request->amount > $balance){
            Toastr::warning(__('merchantmanage.not_enough_balance'),__('message.warning'));
            return back();
        }

        if($this->repo->store($request)){
            Toastr::success(__('paymentrequest.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.payment-request.index');
        }else{
            Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
           return back();
        }
    }

    public function edit($id){
            $singlePayment=$this->repo->get($id);
            $merchantaccounts=MerchantPayment::where('merchant_id',Auth::user()->merchant->id)->get();
            return view('backend.merchant_panel.payment_request.edit',compact('singlePayment','merchantaccounts'));
    }

    public function update(StoreRequest $request){

        $payment = $this->repo->get(Auth::user()->merchant->id);
        if($payment->status == ApprovalStatus::PENDING){

            $account=Auth::user()->merchant;
            $balance=(double) $account->current_balance;
            if((double) $request->amount > $balance){
                Toastr::warning(__('merchantmanage.not_enough_balance'),__('message.warning'));
                return back();
            }

            if($this->repo->update($request)){
                Toastr::success(__('paymentrequest.update_msg'),__('message.success'));
                return redirect()->route('merchant-panel.payment-request.index');
            }else{
                Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
            return back();
            }

        }
        else{
            Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
        }
        return back();
    }

    public function delete($id){
        $payment = $this->repo->get($id);
        if($payment->status == ApprovalStatus::PENDING){
            $this->repo->delete($id);
            Toastr::success(__('paymentrequest.deleted_msg'),__('message.success'));
        }
        else{
            Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
        }
        return back();
    }

}
