<?php
namespace App\Repositories\Income;

use App\Enums\AccountHeads;
use App\Enums\UserType;
use App\Repositories\Income\IncomeInterface;
use App\Models\Backend\Income;
use App\Models\Backend\Upload;
use App\Models\Backend\Account;
use App\Models\Backend\AccountHead;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\User;
use Carbon\Carbon;

class IncomeRepository implements IncomeInterface {

    public function all(){
        return Income::companywise()->with('merchant','merchant.user','deliveryman','deliveryman.user','account','parcel')->orderByDesc('id')->paginate(10);
    }

    public function filter($request){
        return Income::companywise()->with('merchant','merchant.user','deliveryman','deliveryman.user','account','parcel')->where(function($query)use($request){
            if($request->account_id){
                $query->where('account_id',$request->account_id);
            }
            if($request->date):
                if($request->date) {
                    $query->where(['date' => date('Y-m-d', strtotime($request->date))]);
                }
            endif;

        })->orderByDesc('id')->paginate(10);
    }

    public function accountHeads(){
        return AccountHead::where('type',AccountHeads::INCOME)->orderBy('id','ASC')->get();
    }

    // get single row
    public function get($id){
        return Income::with('parcel')->find($id);
    }
    // All request data store in User tabel.
    public function store($request)
    {

        try {
            // check account balance
            if($request->account_head_id     == 1):

                //merchant statements
                $merchant                           = Merchant::find($request->merchant_id);
                $merchant_statment                  = new MerchantStatement();
                $merchant_statment->company_id      = settings()->id;
                $merchant_statment->merchant_id     = $request->merchant_id;
                $merchant_statment->type            = AccountHeads::INCOME;
                $merchant_statment->amount          = $request->amount;
                $merchant_statment->date            = $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $merchant_statment->note        =  $request->title;
                else:
                    $accountH                       = AccountHead::find($request->account_head_id);
                    $merchant_statment->note        =  @$accountH->name;
                endif;
                $merchant_statment->save();
                $merchant->current_balance = ($merchant->current_balance + $request->amount);
                $merchant->save();

            elseif($request->account_head_id == 2):

                //deliveryman  statements
                $deliveryman                           = DeliveryMan::find($request->delivery_man_id);
                $deliveryman_statment                  = new DeliverymanStatement();
                $deliveryman_statment->company_id      = settings()->id;
                $deliveryman_statment->delivery_man_id = $request->delivery_man_id;
                $deliveryman_statment->type            = AccountHeads::INCOME;
                $deliveryman_statment->amount          = $request->amount;
                $deliveryman_statment->date            = $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $deliveryman_statment->note        =  $request->title;
                else:
                    $accountH                          = AccountHead::find($request->account_head_id);
                    $deliveryman_statment->note        =  @$accountH->name;
                endif;
                $deliveryman_statment->save();
                $deliveryman->current_balance          = ($deliveryman->current_balance + $request->amount);
                $deliveryman->save();
            elseif($request->account_head_id == 7):
                //hub statements
                $hub                           = Hub::find($request->hub_id);
                $hub_statment                  = new HubStatement();
                $hub_statment->company_id      = settings()->id;
                $hub_statment->hub_id          = $request->hub_id;
                $hub_statment->type            = AccountHeads::INCOME;
                $hub_statment->amount          = $request->amount;
                $hub_statment->date            = $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $hub_statment->note        =  $request->title;
                else:
                    $accountH                  = AccountHead::find($request->account_head_id);
                    $hub_statment->note        =  @$accountH->name;
                endif;
                $hub_statment->save();
                $hub->current_balance          = ($hub->current_balance + $request->amount);
                $hub->save();

            endif;
            //end balance check
            $account                   = Account::find($request->account_id);
            $income                    = new Income();
            $income->company_id        = settings()->id;
            $income->account_head_id   = $request->account_head_id;
            $income->from              = $request->account_head_id;
            if($request->account_head_id     == 1):
                $income->merchant_id         = $request->merchant_id;
            elseif($request->account_head_id == 2):
                $income->delivery_man_id     = $request->delivery_man_id;
                // $income->hub_id           = $request->hub_id;
            elseif($request->account_head_id == 7):
                $income->merchant_id         = null;
                $income->user_id             = null;
                $income->delivery_man_id     = null;
                $income->hub_id              = $request->hub_id;
                $income->hub_user_id         = $request->hub_users;
                $income->hub_user_account_id = $request->hub_user_accounts;
            elseif($request->account_head_id == 3):
                if($request->user_id):
                $income->user_id       = $request->user_id;
                endif;
            $income->title             = $request->title;
            endif;
            if($request->parcel_id !== ''):
                $income->parcel_id         = $request->parcel_id ;
            endif;
            $income->account_id        = $request->account_id;
            $income->amount            = $request->amount;
            $income->date              = Carbon::parse($request->date)->format('Y-m-d');
            $income->receipt           = $this->file('', $request->receipt);
            $income->note              = $request->note;
            $income->save();

            //add balance in accounts
            if($income):
                $account->balance      = $account->balance + $request->amount;
                $account->save();
            endif;

            // From hub account balance increment
            if($request->account_head_id == 7){
                $account          = Account::find($request->hub_user_accounts);
                $account->balance = $account->balance - $request->amount;
                $account->save();
            }

            //add bank transaction
            if($income && $account){
                $bank_transaction                   =  new BankTransaction();
                $bank_transaction->company_id       = settings()->id;
                $bank_transaction->account_id       =  $request->account_id;
                $bank_transaction->type             =  AccountHeads::INCOME;
                $bank_transaction->amount           =  $income->amount;
                $bank_transaction->date             =  $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $bank_transaction->note         =  $request->title;
                else:
                    $bank_transaction->note         =  @$income->accounthead->name;
                endif;
                $bank_transaction->income_id        =  $income->id;
                $bank_transaction->save();

                //add courier statements
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->income_id            = $income->id;
                $courierStatement->amount               = $request->amount;
                $courierStatement->type                 = AccountHeads::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                if($request->account_head_id == 3):
                    $courierStatement->note                 = $request->title;
                else:
                    $accountHn                          = AccountHead::find($request->account_head_id);
                    $courierStatement->note        =  @$accountHn->name;
                endif;
                $courierStatement->save();
            }

            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }
    public function hubCheck($request){

        if($request->from     == 1):
            return $user    = Merchant::find($request->merchant);
        elseif($request->from == 2):
            return $user    = DeliveryMan::find($request->deliveryman);
        elseif($request->from == 7):
            return $user    = Hub::find($request->hub);
        else:
            return $user    = null;
        endif;

    }
    public function hubUserAccounts($request){

        return Account::companywise()->where('user_id',$request->id)->get();

    }
    public function hubUsers($id){
        return User::companywise()->where('hub_id', $id)->where('user_type',UserType::ADMIN)->get();
    }
    // All request data update in User tabel.
    public function update($id, $request)
    {
        try {
            //update previus
            $inc                                      = Income::find($id);
            if($inc->account_head_id     == 1):
                //merchant statements
                $prevmerchant                         = Merchant::find($inc->merchant_id);
                $merchantstatment                     = new MerchantStatement();
                $merchantstatment->company_id         = settings()->id;
                $merchantstatment->merchant_id        = $inc->merchant_id;
                $merchantstatment->type               = AccountHeads::EXPENSE;
                $merchantstatment->amount             = $inc->amount;
                $merchantstatment->date               = $inc->date;
                if($inc->account_head_id == 3):
                    $merchantstatment->note         =  $inc->bankTransaction->note;
                else:
                    $accountHd                      = AccountHead::find($inc->account_head_id);
                    $merchantstatment->note         =  @$accountHd->name;
                endif;
                $prevmerchant->current_balance = ($prevmerchant->current_balance - $inc->amount) ;
                $prevmerchant->save();
                $merchantstatment->save();

            elseif($inc->account_head_id == 2):

                $pdeliveryman                         = DeliveryMan::find($inc->delivery_man_id);
                $deliverymanstatment                  = new DeliverymanStatement();
                $deliverymanstatment->company_id      = settings()->id;
                $deliverymanstatment->delivery_man_id = $inc->delivery_man_id;
                $deliverymanstatment->type            = AccountHeads::EXPENSE;
                $deliverymanstatment->amount          = $inc->amount;
                $deliverymanstatment->date            = $inc->date;
                if($request->account_head_id == 3):
                    $deliverymanstatment->note        =  $inc->bankTransaction->note;
                else:
                    $accountHd                        = AccountHead::find($inc->account_head_id);
                    $deliverymanstatment->note        =  @$accountHd->name;
                endif;
                $pdeliveryman->current_balance        = ($pdeliveryman->current_balance - $inc->amount) ;
                $deliverymanstatment->save();
                $pdeliveryman->save();

            elseif($inc->account_head_id == 7):
                // Hub statement
                $hub                           = Hub::find($inc->hub_id);
                $hub_statment                  = new HubStatement();
                $hub_statment->company_id      = settings()->id;
                $hub_statment->hub_id          = $inc->hub_id;
                $hub_statment->type            = AccountHeads::EXPENSE;
                $hub_statment->amount          = $inc->amount;
                $hub_statment->date            = $inc->date;
                if($request->account_head_id   == 3):
                    $hub_statment->note        =  $inc->bankTransaction->note;
                else:
                    $accountHd                 = AccountHead::find($inc->account_head_id);
                    $hub_statment->note        =  @$accountHd->name;
                endif;
                $hub->current_balance          = ($hub->current_balance - $inc->amount) ;
                $hub_statment->save();
                $hub->save();

            endif;
            //update account balance
            if($request->account_head_id     == 1):
                //merchant statements
                $merchant                           = Merchant::find($request->merchant_id);
                $merchant_statment                  = new MerchantStatement();
                $merchant_statment->company_id      = settings()->id;
                $merchant_statment->merchant_id     = $request->merchant_id;
                $merchant_statment->type            = AccountHeads::INCOME;
                $merchant_statment->amount          = $request->amount;
                $merchant_statment->date            = $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $merchant_statment->note        =  $request->title;
                else:
                    $accountH                       = AccountHead::find($request->account_head_id);
                    $merchant_statment->note        =  @$accountH->name;
                endif;
                $merchant_statment->save();
                $merchant->current_balance = ($merchant->current_balance + $request->amount);
                $merchant->save();

            elseif($request->account_head_id == 2):

                $deliveryman             = DeliveryMan::find($request->delivery_man_id);
                //deliveryman  statements
                $deliveryman_statment                  = new DeliverymanStatement();
                $deliveryman_statment->company_id      = settings()->id;
                $deliveryman_statment->delivery_man_id = $request->delivery_man_id;
                $deliveryman_statment->type            = AccountHeads::INCOME;
                $deliveryman_statment->amount          = $request->amount;
                $deliveryman_statment->date            = $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $deliveryman_statment->note        =  $request->title;
                else:
                    $accountH                          = AccountHead::find($request->account_head_id);
                    $deliveryman_statment->note        =  @$accountH->name;
                endif;
                $deliveryman_statment->save();
                $deliveryman->current_balance = ($deliveryman->current_balance + $request->amount);
                $deliveryman->save();


            elseif($request->account_head_id == 7):

                $hub                           = Hub::find($request->hub_id);
                //hub  statements
                $hub_statment                  = new HubStatement();
                $hub_statment->company_id      = settings()->id;
                $hub_statment->hub_id          = $request->hub_id;
                $hub_statment->type            = AccountHeads::INCOME;
                $hub_statment->amount          = $request->amount;
                $hub_statment->date            = $request->date.date(' H:i:s');
                if($request->account_head_id == 3):
                    $hub_statment->note        =  $request->title;
                else:
                    $accountH                  = AccountHead::find($request->account_head_id);
                    $hub_statment->note        =  @$accountH->name;
                endif;
                $hub_statment->save();
                $hub->current_balance = ($hub->current_balance + $request->amount);
                $hub->save();


            endif;
            // check accont balance
            $account                   =  Account::find($inc->account_id);
            $income                    =  Income::find($id);
            if($request->account_head_id == 7):
                //hub user account
                $hub_user_account          =  Account::find($income->hub_user_account_id);
                $hub_user_account->balance = $hub_user_account->balance + $income->amount;
                $hub_user_account->save();
                $account->balance          = $account->balance - $income->amount;
            else:
                //account  balance minus from account
                $account->balance          = $account->balance - $income->amount;
            endif;
            $account->save();
            //add bank transaction
            $income->account_head_id   = $request->account_head_id;
            $income->from              = $request->account_head_id;
            if($request->account_head_id     == 1):
                $income->merchant_id       = $request->merchant_id;
                $income->delivery_man_id   = null;
                $income->user_id           = null;
            elseif($request->account_head_id == 2):
                $income->merchant_id       = null;
                $income->user_id           = null;
                $income->delivery_man_id   = $request->delivery_man_id;
            elseif($request->account_head_id == 7):
                $income->merchant_id         = null;
                $income->user_id             = null;
                $income->delivery_man_id     = null;
                $income->hub_id              = $request->hub_id;
                $income->hub_user_id         = $request->hub_users;
                $income->hub_user_account_id = $request->hub_user_accounts;
            elseif($request->account_head_id == 3):

                if($request->user_id):
                    $income->user_id       = $request->user_id;
                endif;
                $income->title             = $request->title;
                $income->delivery_man_id   = null;
                $income->merchant_id       = null;
            endif;
            if($request->parcel_id !== ''):
            $income->parcel_id         = $request->parcel_id;
            endif;
            $income->account_id        = $request->account_id;
            $income->amount            = $request->amount;
            $income->date              = $request->date;
            if(isset($request->receipt) && $request->receipt != null):
                $income->receipt = $this->file($income->receipt, $request->receipt);
            endif;
            $income->note              = $request->note;
            $income->save();

            if($income):
                // add  balance in account
                if($request->account_head_id == 7):
                    //hub user
                    $hub_user_account          =  Account::find($income->hub_user_account_id);
                    $hub_user_account->balance =  $hub_user_account->balance - $request->amount;
                    $hub_user_account->save();
                endif;

                $account                      = Account::find($request->account_id);
                $account->balance          = $account->balance + $request->amount;
                $account->save();
            endif;

            // add bank transaction
            if($income && $account){

                if($income && $account){
                    $bank_transaction                   =  BankTransaction::where('income_id',$income->id)->first();
                    $bank_transaction->amount           =  $income->amount;
                    $bank_transaction->date             =  $request->date.date(' H:i:s');
                    if($request->account_head_id == 3):
                        $bank_transaction->note         =  $request->title;
                    else:
                        $bank_transaction->note         =  @$income->accounthead->name;
                    endif;
                    $bank_transaction->save();
                }

                // add courier statements
                $courierStatement                       = CourierStatement::where('income_id',$income->id)->first();
                $courierStatement->income_id            = $income->id;
                $courierStatement->amount               = $request->amount;
                $courierStatement->type                 = AccountHeads::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                if($request->account_head_id == 3):
                    $courierStatement->note             = $request->title;
                else:
                    $accountHn                          = AccountHead::find($request->account_head_id);
                    $courierStatement->note             =  @$accountHn->name;
                endif;
                $courierStatement->save();
            }

            return true;
        }
        catch (\Exception $e) {

            return false;
        }
    }

    // Delete single row in User Model with Delete single row in Upload model and delete image in public/upload/user folder..
    public function delete($id){
        try {
            $income = Income::with('upload')->find($id);
            if($income->company_id == settings()->id):
                if($income->account_head_id     == 1):
                    //merchant statements
                        $merchant                           = Merchant::find($income->merchant_id);
                        $merchant_statment                  = new MerchantStatement();
                        $merchant_statment->company_id      = settings()->id;
                        $merchant_statment->merchant_id     = $income->merchant_id;
                        $merchant_statment->type            = AccountHeads::EXPENSE;
                        $merchant_statment->amount          = $income->amount;
                        $merchant_statment->date            = $income->date.date(' H:i:s');
                        if($income->account_head_id == 3):
                            $merchant_statment->note         =  $income->title;
                        else:
                            $accountH                        = AccountHead::find($income->account_head_id);
                            $merchant_statment->note         =  @$accountH->name;
                        endif;
                        $merchant_statment->save();
                        $merchant->current_balance = ($merchant->current_balance - $income->amount);
                        $merchant->save();

                elseif($income->account_head_id == 2):

                    //deliveryman  statements
                        $deliveryman                           = DeliveryMan::find($income->delivery_man_id);
                        $deliveryman_statment                  = new DeliverymanStatement();
                        $deliveryman_statment->company_id      = settings()->id;
                        $deliveryman_statment->delivery_man_id = $income->delivery_man_id;
                        $deliveryman_statment->type            = AccountHeads::EXPENSE;
                        $deliveryman_statment->amount          = $income->amount;
                        $deliveryman_statment->date            = $income->date.date(' H:i:s');
                        if($income->account_head_id == 3):
                            $deliveryman_statment->note        =  $income->title;
                        else:
                            $accountH                          = AccountHead::find($income->account_head_id);
                            $deliveryman_statment->note        =  @$accountH->name;
                        endif;
                        $deliveryman_statment->save();
                        $deliveryman->current_balance = ($deliveryman->current_balance - $income->amount);
                        $deliveryman->save();


                elseif($income->account_head_id == 7):

                        //Hub  statements
                        $hub                           = Hub::find($income->hub_id);
                        $hub_statment                  = new HubStatement();
                        $hub_statment->company_id      = settings()->id;
                        $hub_statment->hub_id          = $income->hub_id;
                        $hub_statment->type            = AccountHeads::EXPENSE;
                        $hub_statment->amount          = $income->amount;
                        $hub_statment->date            = $income->date.date(' H:i:s');
                        if($income->account_head_id == 3):
                            $hub_statment->note        =  $income->title;
                        else:
                            $accountH                  = AccountHead::find($income->account_head_id);
                            $hub_statment->note        =  @$accountH->name;
                        endif;
                        $hub_statment->save();
                        $hub->current_balance = ($hub->current_balance - $income->amount);
                        $hub->save();


                endif;
                //end balance check

                $account = Account::find($income->account_id);
                //account  balance minus from account
                $account->balance          = ($account->balance - $income->amount);
                $account->save();

                if($income->hub_user_account_id):
                    // From hub account balance minus
                    $account          = Account::find($income->hub_user_account_id);
                    $account->balance = ($account->balance + $income->amount);
                    $account->save();
                endif;
                //add courier statements
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->income_id            = $income->id;
                $courierStatement->amount               = $income->amount;
                $courierStatement->type                 = AccountHeads::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                if($income->account_head_id == 3):
                    $courierStatement->note                 = $income->title;
                else:
                    $accountHn                          = AccountHead::find($income->account_head_id);
                    $courierStatement->note        =  @$accountHn->name;
                endif;
                $courierStatement->save();

                //add bank transaction
                if($income->receipt != null){
                    Upload::destroy($income->upload->id);
                    if(file_exists($income->upload->original))
                        unlink($income->upload->original);
                }
                $income->delete();
                return true;
            endif;
            return false;
        }
        catch (\Exception $e) {

            return false;
        }
    }

    // Request image Store in Upload Model and image copy file attach in public/upload/user folder.
    public function file($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/income');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/income/'.$profileImage;
            }
            if(blank($image_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                if(file_exists($upload->original))
                {
                    unlink($upload->original);
                }
            }
            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }
}
