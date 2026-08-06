<?php
namespace App\Repositories\MerchantPanel\PaymentRequest;

use App\Enums\UserType;
use App\Models\Backend\Payment;
use App\Models\Backend\Merchant;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentRequestRepository implements PaymentRequestInterface {

    public function all(){
        //
    }

    public function get($id){
        return Payment::where('id',$id)->first();
    }

    public function store($request){
        try {
            DB::beginTransaction();
            $payment                   = new Payment();
            $payment->company_id       = settings()->id;
            $payment->merchant_id      = Auth::user()->merchant->id;
            $payment->amount           = $request->amount;
            $payment->merchant_account = $request->merchant_account;
            $payment->description      = $request->description;
            $payment->created_by       = UserType::MERCHANT;
            $payment->save();
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
    public function edit($id){
        //
    }

    public function update($request){
        try {
            DB::beginTransaction();
            $payment                   = Payment::where('id',$request->id)->first();
            $payment->merchant_id      = Auth::user()->merchant->id;
            $payment->amount           = $request->amount;
            $payment->merchant_account = $request->merchant_account;
            $payment->description      = $request->description;
            $payment->created_by       = UserType::MERCHANT;
            $payment->save();

            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function delete($id){
        $payment                   = Payment::where('id',$id)->first();
        if($payment->company_id == settings()->id):
            return Payment::destroy($id);
        endif;
        return false;
    }

}
