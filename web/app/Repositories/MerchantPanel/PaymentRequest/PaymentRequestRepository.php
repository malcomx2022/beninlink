<?php
namespace App\Repositories\MerchantPanel\PaymentRequest;

use App\Enums\UserType;
use App\Models\Backend\Payment;
use App\Models\Backend\Merchant;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * S7 — une demande de retrait n'est lisible, modifiable et supprimable que
 * par le marchand qui l'a déposée. Le socle lisait `Payment::where('id')`
 * nu (montant, compte destinataire, statut d'un autre marchand).
 */
class PaymentRequestRepository implements PaymentRequestInterface {

    private function ownedPayments(){
        return Payment::companywise()->where('merchant_id', Auth::user()->merchant?->id);
    }

    public function all(){
        //
    }

    public function get($id){
        return $this->ownedPayments()->find($id);
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
            $payment                   = $this->ownedPayments()->find($request->id);
            if (blank($payment)) {
                DB::rollBack();
                return false;
            }
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
        return $this->ownedPayments()->whereKey($id)->delete();
    }

}
