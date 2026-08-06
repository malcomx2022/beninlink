<?php
namespace App\Repositories\MerchantPayment;

use App\Models\Backend\Merchant;
use App\Models\MerchantPayment;
use App\Repositories\MerchantPayment\PaymentInterface;

class PaymentRepository implements PaymentInterface{

    public function all(){
         //
    }

    public function get($id){
        return MerchantPayment::where('merchant_id',$id)->get();
    }

    public function edit($id){
        return MerchantPayment::where('id',$id)->first();
    }


    public function bankstore($request){
        try {
            if($request->editid){
                $delete      = MerchantPayment::destroy($request->editid);
            }
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $request->merchant_id;
            $merchantpayment->payment_method = $request->payment_method_name;
            if($request->payment_method_name == 'cash'){

            }else{
                $merchantpayment->bank_name      = $request->bank_name;
                $merchantpayment->holder_name    = $request->holder_name;
                $merchantpayment->account_no     = $request->account_no;
                $merchantpayment->branch_name    = $request->branch_name;
                $merchantpayment->routing_no     = $request->routing_no;
                $merchantpayment->status         = $request->status;
            }
            $merchantpayment->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }
    public function mobilestore($request){
        try {
            if($request->editid){
                $delete     = MerchantPayment::destroy($request->editid);
            }
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $request->merchant_id;
            $merchantpayment->payment_method = $request->payment_method_name;
            $merchantpayment->holder_name    = $request->mobile_holder_name;
            $merchantpayment->mobile_company = $request->mobile_company;
            $merchantpayment->mobile_no      = $request->mobile_no;
            $merchantpayment->account_type   = $request->account_type;
            $merchantpayment->status         = $request->status;
            $merchantpayment->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function update($id,$request){
        //
    }



    public function bankUpdate($request){

        try {

            $delete                          = MerchantPayment::destroy($request->editid);
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $request->merchant_id;
            $merchantpayment->payment_method = $request->payment_method_name;
            if($request->payment_method_name == 'cash'){
            }else{
                $merchantpayment->bank_name      = $request->bank_name;
                $merchantpayment->holder_name    = $request->holder_name;
                $merchantpayment->account_no     = $request->account_no;
                $merchantpayment->branch_name    = $request->branch_name;
                $merchantpayment->routing_no     = $request->routing_no;
                $merchantpayment->status         = $request->status;
            }
            $merchantpayment->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function mobileUpdate($request){

        try {
            $delete                          = MerchantPayment::destroy($request->editid);
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $request->merchant_id;
            $merchantpayment->payment_method = $request->payment_method_name;
            $merchantpayment->holder_name    = $request->mobile_holder_name;
            $merchantpayment->mobile_company = $request->mobile_company;
            $merchantpayment->mobile_no      = $request->mobile_no;
            $merchantpayment->account_type   = $request->account_type;
            $merchantpayment->status         = $request->status;
            $merchantpayment->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id){
        return MerchantPayment::destroy($id);
    }
}
