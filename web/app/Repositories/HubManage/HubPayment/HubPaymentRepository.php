<?php
namespace App\Repositories\HubManage\HubPayment;

use App\Enums\AccountHeads;
use App\Enums\ApprovalStatus;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Payment;
use App\Models\Backend\Upload;
use App\Repositories\HubManage\HubPayment\HubPaymentInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class HubPaymentRepository implements HubPaymentInterface{

    public function all(){
        return HubPayment::companywise()->orderBy('id','desc')->paginate(10);
    }

    public function get($id){
        return HubPayment::find($id);
    }
    public function store($request){
        try {
            DB::beginTransaction();
            $payment                              = new HubPayment();
            $payment->company_id                  = settings()->id;
            $payment->hub_id                      = $request->hub_id;
            $payment->amount                      = $request->amount;
            //is process
            if($request->isprocess){
                $payment->transaction_id           = $request->transaction_id;
                $payment->from_account             = $request->from_account;
                $payment->status                   = ApprovalStatus::PROCESSED;
            }else{
                $payment->status                   = ApprovalStatus::PENDING;
            }
            $payment->created_by                   = UserType::ADMIN;

            if($request->reference_file){
                $payment->reference_file           = $this->file('',$request->reference_file);
            }
            $payment->description                  = $request->description;
            $payment->save();

            if($request->isprocess):
                //bank transaction statements
                $bank_transaction                   =  new BankTransaction();
                $bank_transaction->company_id       =  settings()->id;
                $bank_transaction->account_id       =  $payment->from_account;
                $bank_transaction->type             =  AccountHeads::EXPENSE;
                $bank_transaction->amount           =  $payment->amount;
                $bank_transaction->date             =  date('Y-m-d H:i:s');
                $bank_transaction->note             =  $request->description;
                $bank_transaction->save();
                if($bank_transaction):
                    //minus amount from courier account
                    $courier_account                = Account::find($payment->from_account);
                    $courier_account->balance       = $courier_account->balance - $payment->amount;
                    $courier_account->save();
                endif;
            endif;
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

    public function update($id,$request){
        try {
            DB::beginTransaction();
            $payment                      = HubPayment::where('id',$id)->first();
            $payment->company_id          = settings()->id;
            $payment->hub_id              = $request->hub_id;
            $payment->amount              = $request->amount;
            if($request->isprocess){
                $payment->transaction_id  = $request->transaction_id;
                $payment->from_account    = $request->from_account;
                $payment->status          = ApprovalStatus::PROCESSED;
            }
            $payment->created_by          = UserType::ADMIN;
            if($request->reference_file){
                if($payment->referencefile !==null && File::exists($payment->referencefile->original)  ):
                    unlink($payment->referencefile->original);
                endif;
                $payment->reference_file  = $this->file('',$request->reference_file);
            }
            $payment->description         = $request->description;
            $payment->save();

            if($request->isprocess):

                $bank_transaction                   =  new BankTransaction();
                $bank_transaction->company_id       =  settings()->id;
                $bank_transaction->account_id       =  $payment->from_account;
                $bank_transaction->type             =  AccountHeads::EXPENSE;
                $bank_transaction->amount           =  $payment->amount;
                $bank_transaction->date             =  date('Y-m-d H:i:s');
                $bank_transaction->note             =  $request->description;
                $bank_transaction->save();
                if($bank_transaction):
                    //minus amount from courier account
                    $courier_account                = Account::find($payment->from_account);
                    $courier_account->balance       = $courier_account->balance - $payment->amount;
                    $courier_account->save();
                endif;

            endif;
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function delete($id){

        $hubpayment = HubPayment::find($id);
        if($hubpayment->company_id == settings()->id):
            return HubPayment::destroy($id);
       endif;
       return false;
    }

    // Request image Store in Upload Model and image copy file attach in public/upload/user folder.
    public function file($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/reference');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/reference/'.$profileImage;
            }

            if(blank($image_id)){
                $upload                = new Upload();
            }else{
                $upload                = Upload::find($image_id);
                unlink($upload->original);
            }
            $upload->original          = $image_name;
            $upload->save();
            return $upload->id;
        }
        catch (\Exception $e) {
            return false;
        }
    }


    public function getSingleHubPayments($hub_id){
        return HubPayment::companywise()->where('hub_id',$hub_id)->orderBy('id','desc')->paginate(10);
    }

    public function reject($id){

        try {
            DB::beginTransaction();
            $payment                   = HubPayment::where('id',$id)->first();
            if($payment->company_id == settings()->id):
                $payment->status           = ApprovalStatus::REJECT;
                $payment->save();
    
                DB::commit();
                return true;
            endif;
            return false;
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function cancelReject($id){

        try {

            $payment                   = HubPayment::where('id',$id)->first();
            if($payment->company_id == settings()->id):
                $payment->status           = ApprovalStatus::PENDING;
                $payment->save();
                return true;
            endif;
            return false;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function processed($request){
        try {
            DB::beginTransaction();
            $payment                           = HubPayment::where('id',$request->id)->first();
            //bank transaction statements
            $bank_transaction                   =  new BankTransaction();
            $bank_transaction->company_id       =  settings()->id;
            $bank_transaction->account_id       =  $request->from_account;
            $bank_transaction->type             =  AccountHeads::EXPENSE;
            $bank_transaction->amount           =  $payment->amount;
            $bank_transaction->date             =  date('Y-m-d H:i:s');
            $bank_transaction->note             =  __('hub_payment.hub_payment_withdrawal');
            $bank_transaction->save();

            if($bank_transaction):
                //minus amount from courier account
                $courier_account                = Account::find($request->from_account);
                $courier_account->balance       = $courier_account->balance - $payment->amount;
                $courier_account->save();
            endif;

            $payment->transaction_id            = $request->transaction_id;
            $payment->from_account              = $request->from_account;
            if($request->reference_file):
                if(isset($payment->referencefile) && File::exists($payment->referencefile->original)):
                    unlink($payment->referencefile->original);
                endif;
                $payment->reference_file        = $this->file('',$request->reference_file);
            endif;
            $payment->status                    = ApprovalStatus::PROCESSED;
            $payment->save();

            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function cancelProcess($id){

        try {
            DB::beginTransaction();
            $payment                            = HubPayment::where('id',$id)->first();

            $bank_transaction                   =  new BankTransaction();
            $bank_transaction->company_id       =  settings()->id;
            $bank_transaction->account_id       =  $payment->from_account;
            $bank_transaction->type             =  AccountHeads::INCOME;
            $bank_transaction->amount           =  $payment->amount;
            $bank_transaction->date             =  date('Y-m-d H:i:s');
            $bank_transaction->note             =  __('hub_payment.cancel_hub_payment_withdrawal');
            $bank_transaction->save();
            if($bank_transaction):
                //plus amount from courier account
                $courier_account                = Account::find($payment->from_account);
                $courier_account->balance       = $courier_account->balance + $payment->amount;
                $courier_account->save();
            endif;

            $payment->status           = ApprovalStatus::PENDING;
            $payment->transaction_id   = null;
            $payment->from_account     = null;
            $payment->save();
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
}
