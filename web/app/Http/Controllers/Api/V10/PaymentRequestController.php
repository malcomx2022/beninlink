<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPanel\PaymentRequest\StoreRequest;
use App\Http\Resources\v10\PaymentResource;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use Illuminate\Support\Facades\Validator;

class PaymentRequestController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    protected $merchantPayments;
    public function __construct(PaymentInterface $merchantPayments,PaymentRequestInterface $repo)
    {
        $this->merchantPayments=$merchantPayments;
        $this->repo=$repo;
    }
    public function index(){
        try {
            $payments  =  $this->merchantPayments->getSingleMerchantPayments(auth()->user()->merchant->id);
            return $this->responseWithSuccess(__('paymentrequest.title'), ['payments'=> PaymentResource::collection($payments)], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('paymentrequest.title'), [], 500);

        }
    }

    public function create(){
        $merchant           = Merchant::find(auth()->user()->merchant->id);
        $merchantAccounts   = MerchantPayment::where('merchant_id',$merchant->id)->get();

        try {
            return $this->responseWithSuccess(__('paymentrequest.title'), ['merchant'=> $merchant, 'merchantAccounts' => $merchantAccounts], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('paymentrequest.title'), [], 500);
        }

    }

    public function store(Request $request){

        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('paymentrequest.title'), ['message' => $validator->errors()], 422);
        }
        $account = auth()->user()->merchant;
        $balance = (double) $account->current_balance;
        if((double) $request->amount > $balance){
            return $this->responseWithError(__('merchantmanage.not_enough_balance'), [], 422);
        }

        if($this->repo->store($request)){
            return $this->responseWithSuccess(__('paymentrequest.added_msg'), [], 200);
        }else{
            return $this->responseWithError(__('paymentrequest.error_msg'), [], 500);
        }

    }

    public function edit($id){
        $singlePayment      = $this->repo->get($id);
        $merchantAccounts   = MerchantPayment::where('merchant_id',auth()->user()->merchant->id)->get();
        return $this->responseWithSuccess(__('paymentrequest.title'), ['payment'=> new PaymentResource($singlePayment), 'merchantAccounts'=> $merchantAccounts], 200);
    }

    public function update(Request $request,$id){

        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('paymentrequest.title'), ['message' => $validator->errors()], 422);
        }

        $payment            = $this->repo->get($id);
        if($payment->status == ApprovalStatus::PENDING){
            $account=auth()->user()->merchant;
            $balance=(double) $account->current_balance;

            if((double) $request->amount > $balance){
                return $this->responseWithError(__('merchantmanage.not_enough_balance'), [], 422);
            }

            if($this->repo->update($request)){
                return $this->responseWithSuccess(__('paymentrequest.update_msg'), [], 200);
            }else{
                return $this->responseWithError(__('paymentrequest.error_msg'), [], 500);
            }

        } else {
            return $this->responseWithError(__('paymentrequest.error_msg'), [], 500);
        }

    }

    public function delete($id){

        try {

            $payment = $this->repo->get($id);
            if($payment->status == ApprovalStatus::PENDING){
                $this->repo->delete($id);
                return $this->responseWithSuccess(__('paymentrequest.delete_msg'), [], 200);
            } else {
                return $this->responseWithError(__('paymentrequest.error_msg'), [], 500);
            }
        }catch (\Exception $exception) {
            return $this->responseWithError(__('paymentrequest.error_msg'), [], 500);

        }
    }

}
