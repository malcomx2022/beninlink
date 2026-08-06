<?php

namespace App\Http\Controllers\Backend\HubPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\HubPaymentRequest\StoreRequest;
use App\Models\Backend\HubPayment;
use App\Repositories\HubPaymentRequest\HubPaymentRequestInterface;
use App\Repositories\HubManage\HubPayment\HubPaymentInterface;
use App\Enums\ApprovalStatus;
use Brian2694\Toastr\Facades\Toastr;
class HubPaymentRequestController extends Controller
{
    protected $repo;
    protected $hubPayments;
    public function __construct(HubPaymentInterface $hubPayments,HubPaymentRequestInterface $repo)
    {
        $this->hubPayments  = $hubPayments;
        $this->repo         = $repo;
    }
    public function index(){
        $payments   = $this->hubPayments->getSingleHubPayments(auth()->user()->hub_id);
        return view('backend.hub_panel.hub_payment_request.index',compact('payments'));
    }

    public function create(){
        return view('backend.hub_panel.hub_payment_request.create');
    }

    public function store(StoreRequest $request){

        if($this->repo->store($request)){
            Toastr::success(__('hub_payment_request.added_msg'),__('message.success'));
            return redirect()->route('hub-panel.payment-request.index');
        }else{
            Toastr::error(__('hub_payment_request.error_msg'),__('message.error'));
           return back();
        }
    }

    public function edit($id){
        $singlePayment      =   $this->repo->get($id);
        return view('backend.merchant_panel.payment_request.edit',compact('singlePayment'));
    }

    public function update(StoreRequest $request,$id){
        $payment = HubPayment::find($id);
        if(isset($payment) && $payment->status == ApprovalStatus::PENDING){
            if($this->repo->update($id,$request)){
                Toastr::success(__('hub_payment_request.update_msg'),__('message.success'));
                return redirect()->route('hub-panel.payment-request.index');
            }else{
                Toastr::error(__('hub_payment_request.error_msg'),__('message.error'));
                return back();
            }
        }else {
            Toastr::error(__('hub_payment_request.error_msg'),__('message.error'));
            return back();
        }

    }

    public function delete($id){
        $payment = HubPayment::find($id);
        if(isset($payment) && $payment->status == ApprovalStatus::PENDING) {
            if ($this->repo->delete($id)) {
                Toastr::success(__('hub_payment_request.deleted_msg'),__('message.success'));
            } else {
                Toastr::error(__('hub_payment_request.error_msg'),__('message.error'));
            }
        }else{
            Toastr::error(__('hub_payment_request.error_msg'),__('message.error'));
        }
        return back();

    }

}
