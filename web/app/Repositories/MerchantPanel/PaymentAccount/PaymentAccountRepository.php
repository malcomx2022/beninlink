<?php
namespace App\Repositories\MerchantPanel\PaymentAccount;

use App\Models\Backend\Merchantpanel\PaymentAccount;
use App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountInterface;
use App\Enums\Merchant_panel\PaymentMethod;
use App\Models\MerchantPayment;
use Illuminate\Support\Facades\Auth;

/**
 * S7 — un compte de versement n'est lisible, modifiable et supprimable que
 * par le marchand qui le détient. Le socle lisait `MerchantPayment::where('id')`
 * nu : titulaire, numéro et banque d'un autre marchand étaient accessibles.
 */
class PaymentAccountRepository implements PaymentAccountInterface{

    private function ownedAccounts(){
        return MerchantPayment::where('merchant_id', Auth::user()->merchant?->id);
    }

    public function all(){
        return $this->ownedAccounts()->orderBy('id','desc')->paginate(10);
    }
    public function get($id){

    }
    public function store($request){

       try {
            $Account=new MerchantPayment();
            $Account->merchant_id        = Auth::user()->merchant->id;
            $Account->payment_method     = $request->payment_method;
            if($request->payment_method == PaymentMethod::bank){ 
                $Account->bank_name      = $request->bank_name;
                $Account->holder_name    = $request->holder_name;
                $Account->account_no     = $request->account_no;
                $Account->branch_name    = $request->branch_name;
                $Account->routing_no     = $request->routing_no; 
                $Account->save(); 
                return true; 

            }elseif($request->payment_method == PaymentMethod::mobile){

                $Account->holder_name    = $request->mobile_holder_name;
                $Account->mobile_company = $request->mobile_company;
                $Account->mobile_no      = $request->mobile_no;
                $Account->account_type   = $request->account_type;
                $Account->save();
                return true;
            }else{
                $Account->save();
                return true;
            }
            

       } catch (\Throwable $th) {
            return false;
       }

    }
    public function edit($id){
        return $this->ownedAccounts()->find($id);
    }
    public function update($request){
        try {

            $Account=$this->ownedAccounts()->find($request->id);
            if (blank($Account)) {
                return false;
            }
            $Account->merchant_id    = Auth::user()->merchant->id;
            $Account->payment_method = $request->payment_method;
            if($request->payment_method == PaymentMethod::bank){
                $Account->bank_name      = $request->bank_name;
                $Account->holder_name    = $request->holder_name;
                $Account->account_no     = $request->account_no;
                $Account->branch_name    = $request->branch_name;
                $Account->routing_no     = $request->routing_no;
                //mobile remove old data
                $Account->mobile_company = null;
                $Account->mobile_no      = null;
                $Account->account_type   = null;
                //end mobile
                $Account->save();
                return true;

            }elseif($request->payment_method == PaymentMethod::mobile){
                $Account->holder_name    = $request->mobile_holder_name;
                $Account->mobile_company = $request->mobile_company;
                $Account->mobile_no      = $request->mobile_no;
                $Account->account_type   = $request->account_type;
                //remove bank old data
                $Account->bank_name      = null;
                $Account->account_no     = null;
                $Account->branch_name    = null;
                $Account->routing_no     = null;
                //end bank old data
                $Account->save();
                return true;
            }elseif($request->payment_method == PaymentMethod::cash){
                $Account->save();
                return true;
            }

        } catch (\Throwable $th) {
            return false;
        }
    }
    public function delete($id){
        return $this->ownedAccounts()->whereKey($id)->delete();
    }

}
