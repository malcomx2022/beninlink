<?php
namespace App\Repositories\MerchantManage\Payment;

use App\Enums\AccountHeads;
use App\Enums\ApprovalStatus;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Payment;
use App\Models\Backend\Upload;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class PaymentRepository implements PaymentInterface{

    public function all(){
        return Payment::companywise()->orderBy('id','desc')->paginate(10);
    }

    public function get($id){
        return Payment::find($id);
    }
    public function store($request){
        try {
            DB::beginTransaction();
            $payment=new Payment(); 
            $payment->company_id              = settings()->id;
            $payment->merchant_id             = $request->merchant;
            $payment->amount                  = $request->amount;
            $payment->merchant_account        = $request->merchant_account;
            //is process
            if($request->isprocess){
                $payment->transaction_id      = $request->transaction_id;
                $payment->from_account        = $request->from_account;
                $payment->status              = ApprovalStatus::PROCESSED;
            }else{
                $payment->status              = ApprovalStatus::PENDING;
            }
            $payment->created_by              = UserType::ADMIN;
            if($request->reference_file){
                $payment->reference_file      = $this->file('',$request->reference_file);
            }
            $payment->description             = $request->description;
            $payment->save();

            if($request->isprocess):
                //merchant statement
                $merchantstatment                  = new MerchantStatement();
                $merchantstatment->company_id      = settings()->id;
                $merchantstatment->merchant_id     = $payment->merchant_id;
                $merchantstatment->type            = AccountHeads::EXPENSE;
                $merchantstatment->amount          = $payment->amount;
                $merchantstatment->date            = date('Y-m-d H:i:s');
                $merchantstatment->note            =  __('merchantmanage.payment_withdrawal');
                $merchantstatment->save();

                if($merchantstatment):
                    //minus amount from merchant
                    $merchant                      = Merchant::where('id',$request->merchant)->first();
                    $merchant->current_balance     = $merchant->current_balance - $payment->amount;
                    $merchant->save();
                endif;

                //bank transaction statements
                $bank_transaction                   =  new BankTransaction();
                $bank_transaction->company_id       =  settings()->id;
                $bank_transaction->account_id       =  $payment->from_account;
                $bank_transaction->type             =  AccountHeads::EXPENSE;
                $bank_transaction->amount           =  $payment->amount;
                $bank_transaction->date             =  date('Y-m-d H:i:s');
                $bank_transaction->note             =  __('merchantmanage.merchant_payment_withdrawal');
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

    public function update($request){
        try {
            DB::beginTransaction();
            $payment                      = Payment::where('id',$request->id)->first();
            $payment->merchant_id         = $request->merchant;
            $payment->amount              = $request->amount;
            $payment->merchant_account    = $request->merchant_account;
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
                //merchant statement
                $merchantstatment                  = new MerchantStatement();
                $merchantstatment->company_id      = settings()->id;
                $merchantstatment->merchant_id     = $payment->merchant_id;
                $merchantstatment->type            = AccountHeads::EXPENSE;
                $merchantstatment->amount          = $payment->amount;
                $merchantstatment->date            = date('Y-m-d H:i:s');
                $merchantstatment->note            =  __('merchantmanage.payment_withdrawal');
                $merchantstatment->save();

                if($merchantstatment):
                    //minus amount from merchant
                    $merchant                      = Merchant::where('id',$request->merchant)->first();
                    $merchant->current_balance     = $merchant->current_balance - $payment->amount;
                    $merchant->save();
                endif;

                //bank transaction statements
                $bank_transaction                   =  new BankTransaction();
                $bank_transaction->company_id       =  settings()->id;
                $bank_transaction->account_id       =  $payment->from_account;
                $bank_transaction->type             =  AccountHeads::EXPENSE;
                $bank_transaction->amount           =  $payment->amount;
                $bank_transaction->date             =  date('Y-m-d H:i:s');
                $bank_transaction->note             =  __('merchantmanage.merchant_payment_withdrawal');
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
            $payment                   = Payment::where('id',$id)->first();
            if($payment->company_id == settings()->id):
                return Payment::destroy($id);
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


    public function getSingleMerchantPayments($merchant_id){
        return Payment::companywise()->where('merchant_id',$merchant_id)->orderBy('id','desc')->paginate(10);
    }

    public function reject($id){

        try {
            DB::beginTransaction();
            $payment                   = Payment::where('id',$id)->first();
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
            DB::beginTransaction();
            $payment                   = Payment::where('id',$id)->first();
            $payment->status           = ApprovalStatus::PENDING;
            $payment->save(); 
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Regler une demande de retrait : le transporteur sort l'argent, la creance
     * du marchand s'eteint.
     *
     * Quatre garanties, ajoutees le 2026-09-05 en couvrant l'etape de tests, et
     * qui appliquent ici des decisions deja prises ailleurs :
     *
     * 1. **Une seule fois.** Aucun controle de statut : regler deux fois la
     *    meme demande sortait l'argent deux fois — creance du marchand sous
     *    zero, compte du transporteur debite du double. Meme defaut que F1 sur
     *    les recharges, du cote sortant.
     * 2. **Chez soi.** `Payment::where('id',...)` nu : un administrateur reglait
     *    la demande d'une AUTRE societe, depuis son propre compte bancaire.
     * 3. **Le compte payeur aussi.** `Account::find()` acceptait le compte d'un
     *    autre transporteur.
     * 4. **Tout ou rien**, et l'ecriture porte enfin le marchand : le socle ne
     *    renseignait pas `merchant_id` ICI (il le fait dans `store()` et
     *    `cancelProcess()`), si bien que le retrait n'apparaissait au releve
     *    d'aucun marchand — le solde baissait sans ligne pour l'expliquer.
     */
    public function processed($request){
        $payment = Payment::companywise()->find($request->id);
        // Seule une demande EN ATTENTE se regle : ni une deja reglee, ni une rejetee.
        if(blank($payment) || (int) $payment->status !== ApprovalStatus::PENDING){
            return false;
        }

        $courier_account = Account::companywise()->find($request->from_account);
        if(blank($courier_account)){
            return false;
        }

        try {
            DB::transaction(function () use ($request, $payment, $courier_account) {

            //merchant statement
            $merchantstatment                  = new MerchantStatement();
            $merchantstatment->company_id      = settings()->id;
            $merchantstatment->merchant_id     = $payment->merchant_id;
            $merchantstatment->type            = AccountHeads::EXPENSE;
            $merchantstatment->amount          = $payment->amount;
            $merchantstatment->date            = date('Y-m-d H:i:s');
            $merchantstatment->note            =  __('merchantmanage.payment_withdrawal');
            $merchantstatment->save();

            //minus amount from merchant
            $merchant                      = Merchant::where('id',$payment->merchant_id)->first();
            $merchant->current_balance     = $merchant->current_balance - $payment->amount;
            $merchant->save();

            //bank transaction statements
            $bank_transaction                   =  new BankTransaction();
            $bank_transaction->company_id       =  settings()->id;
            $bank_transaction->account_id       =  $courier_account->id;
            $bank_transaction->type             =  AccountHeads::EXPENSE;
            $bank_transaction->amount           =  $payment->amount;
            $bank_transaction->date             =  date('Y-m-d H:i:s');
            $bank_transaction->note             =  __('merchantmanage.merchant_payment_withdrawal');
            $bank_transaction->save();

            //minus amount from courier account
            $courier_account->balance       = $courier_account->balance - $payment->amount;
            $courier_account->save();

            $payment->transaction_id            = $request->transaction_id;
            $payment->from_account              = $courier_account->id;
            if($request->reference_file):
                if($payment->referencefile !==null && File::exists($payment->referencefile->original)):
                    unlink($payment->referencefile->original);
                endif;
                $payment->reference_file        = $this->file('',$request->reference_file);
            endif;
            $payment->status                    = ApprovalStatus::PROCESSED;
            $payment->save();

            });

            return true;

        } catch (\Throwable $th) {

            return false;
        }
    }

    /**
     * Annuler un reglement : l'argent revient des deux cotes.
     *
     * Memes gardes que `processed()`, et pour la meme raison : sans controle de
     * statut, annuler une demande JAMAIS reglee creditait le marchand d'un
     * argent qu'il n'avait pas recu et rechargeait le compte du transporteur.
     */
    public function cancelProcess($id){
        $payment = Payment::companywise()->find($id);
        // On n'annule que ce qui a ete regle.
        if(blank($payment) || (int) $payment->status !== ApprovalStatus::PROCESSED){
            return false;
        }

        try {
            DB::transaction(function () use ($payment) {

            //merchant statement
            $merchantstatment                  = new MerchantStatement();
            $merchantstatment->company_id      = settings()->id;
            $merchantstatment->merchant_id     = $payment->merchant_id;
            $merchantstatment->type            = AccountHeads::INCOME;
            $merchantstatment->amount          = $payment->amount;
            $merchantstatment->date            = date('Y-m-d H:i:s');
            $merchantstatment->note            =  __('merchantmanage.payment_withdrawal');
            $merchantstatment->save();

            if($merchantstatment):
                //plus amount from merchant
                $merchant                      = Merchant::where('id',$payment->merchant_id)->first();
                $merchant->current_balance     = $merchant->current_balance + $payment->amount;
                $merchant->save();
            endif;

            //bank transaction statements
            $bank_transaction                   =  new BankTransaction();
            $bank_transaction->company_id       =  settings()->id;
            $bank_transaction->account_id       =  $payment->from_account;
            $bank_transaction->type             =  AccountHeads::INCOME;
            $bank_transaction->amount           =  $payment->amount;
            $bank_transaction->date             =  date('Y-m-d H:i:s');
            $bank_transaction->note             =  __('merchantmanage.merchant_payment_withdrawal');
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

            });

            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }


    public function filter($request){
        return Payment::companywise()->where(function($query) use ($request){
            if($request->date) {
                $date = explode('To', $request->date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }

            if($request->merchant_id){
                $query->where('merchant_id',$request->merchant_id);
            }
            if($request->merchant_account){
                $query->where('merchant_account',$request->merchant_account);
            }
            if($request->from_account){
                $query->where('from_account',$request->from_account);
            }

        })->paginate(10);
    }


}
