<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreRequest;
use App\Repositories\Wallet\WalletInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $repo;
    public function __construct(WalletInterface $repo)
    {
        $this->repo   = $repo;
    }
    public function index(Request $request){
        $wallets                  = $this->repo->get($request);
        $recharge_transactions    = $this->repo->recharges($request);
        return view('backend.merchant_panel.mywallet.index',compact('wallets','request','recharge_transactions'));
    }
    public function recharge(){
        return view('backend.merchant_panel.mywallet.recharge');
    }

    public function rechargeAdd(Request $request){
         $validator = Validator::make($request->all(),[
            'amount'   => ['required','numeric','gt:0']
         ]);
        
         if($validator->fails()): 
            Toastr::error($validator->errors()->first(),__('message.error'));
            return redirect()->back()->withErrors($validator->errors());
         endif;
     
        if($wallet = $this->repo->store($request)):
            // $this->repo->approved($wallet->id);
            Toastr::success(__('parcel.wallet_addedd_successfully'),__('message.success'));
            return redirect()->back();
        endif;

        Toastr::error(__('parcel.error_msg'),__('message.error'));
        return redirect()->back();
    }
 
    //admin panel
    public function requestIndex(Request $request){
      
        $wallets                  = $this->repo->get($request);
        $recharge_transactions    = $this->repo->recharges($request);
        return view('backend.wallet_request.index',compact('wallets','request','recharge_transactions'));
    }
 
    public function approve($id){
        if($this->repo->approved($id)):
            Toastr::success(__('parcel.wallet_request_approved_successfully'));
            return redirect()->route('wallet.request.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
 
    public function reject($id){
        if($this->repo->rejected($id)):
            Toastr::success(__('parcel.wallet_request_rejected_successfully'));
            return redirect()->route('wallet.request.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }


    //admin wallet  
 
    public function adminstore(StoreRequest $request)
    {
      
        if($this->repo->adminstore($request)):
            Toastr::success(__('parcel.wallet_recharge_successfully'),__('message.success'));
            return redirect()->route('wallet.request.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
    
    public function delete($id)
    {
         
        if($this->repo->delete($id)):
            Toastr::success(__('parcel.wallet_recharge_update_successfully'),__('success'));
            return redirect()->back();
        else:
            Toastr::error(__('error'),__('errors'));
            return redirect()->back();
        endif;
    }


}
