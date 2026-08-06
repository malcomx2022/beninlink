<?php
namespace App\Repositories\HubPaymentRequest;

use App\Enums\UserType;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Hub;
use App\Repositories\HubPaymentRequest\HubPaymentRequestInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HubPaymentRequestRepository implements HubPaymentRequestInterface {

    public function all(){
        //
    }

    public function get($id){
        return HubPayment::where('id',$id)->first();
    }

    public function store($request){
        try {
            $payment                    = new HubPayment();
            $payment->company_id        = settings()->id;
            $payment->hub_id            = auth()->user()->hub_id;
            $payment->amount            = $request->amount;
            $payment->description       = $request->description;
            $payment->created_by        = UserType::INCHARGE;
            $payment->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }
    public function edit($id){
        //
    }

    public function update($id,$request){
        try {

            $payment                   = HubPayment::find($id);
            $payment->hub_id           = auth()->user()->hub_id;
            $payment->amount           = $request->amount;
            $payment->description      = $request->description;
            $payment->created_by       = UserType::INCHARGE;
            $payment->save(); 
            return true;

        } catch (\Throwable $th) {
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

}
