<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountInterface;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\PaymentAccount\StoreRequest;
use App\Http\Requests\MerchantPanel\PaymentAccount\UpdateRequest;
use Illuminate\Support\Facades\Redirect;
use Brian2694\Toastr\Facades\Toastr;
class PaymentAccountController extends Controller
{

    protected $repo;
    public function __construct(PaymentAccountInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index(){

        $accounts=$this->repo->all();
        return view('backend.merchant_panel.payment_account.index',compact('accounts'));
    }

    public function create (){
        return view('backend.merchant_panel.payment_account.create');
    }

    public function store(StoreRequest $request){

            if($this->repo->store($request)){

                Toastr::success(__('account.added_msg'),__('message.success'));
                return redirect()->route('merchant.accounts.payment-account.index');
            }else{
                Toastr::error(__('account.error_msg'),__('message.error'));
               return back();
            }
    }

    public function edit($id){
        $editaccount=$this->repo->edit($id);

        return view('backend.merchant_panel.payment_account.edit',compact('editaccount'));
    }


    public function update(UpdateRequest $request){
         if($this->repo->update($request)){
            Toastr::success(__('account.update_msg'),__('message.success'));
            return redirect()->route('merchant.accounts.payment-account.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return back();
        }
    }

    public function delete($id){
        if($this->repo->delete($id)){
            Toastr::success(__('account.delete_msg'),__('message.success'));
            return back();
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
           return back();
        }
    }

 

}
