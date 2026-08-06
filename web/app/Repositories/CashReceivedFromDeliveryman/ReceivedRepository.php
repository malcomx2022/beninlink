<?php
namespace App\Repositories\CashReceivedFromDeliveryman;

use App\Enums\AccountHeads;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Upload;
use App\Models\CashReceivedFromDeliveryman;
use App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface;
use Database\Seeders\HubSeeder;
use Illuminate\Support\Facades\Auth;

class ReceivedRepository implements ReceivedInterface {

    public function all(){
        return CashReceivedFromDeliveryman::companywise()->where('hub_id',Auth::user()->hub_id)->orderByDesc('id')->get();
    }

    public function get($id){
        return CashReceivedFromDeliveryman::find($id);
    }
    public function store($request){
        try {
            if(Auth::user()->hub_id):
                $deliveryman  = DeliveryMan::find($request->delivery_man_id);
                $account      = Account::find($request->account_id);
                $hub          = Hub::find(Auth::user()->hub_id);
                 //add hub statements
                 $cash_received                   = new CashReceivedFromDeliveryman();
                 $cash_received->company_id       = settings()->id;
                 $cash_received->user_id          = Auth::user()->id;
                 $cash_received->hub_id           = $hub->id;
                 $cash_received->account_id       = $request->account_id;
                 $cash_received->delivery_man_id  = $request->delivery_man_id;
                 $cash_received->amount           = $request->amount;
                 $cash_received->date             = $request->date;
                 $cash_received->receipt          = $this->file('',$request->receipt);
                 $cash_received->note             = __('permissions.cash_received_from_delivery_man');
                 $cash_received->save();

                //add hub statements
                $hub_statements                   = new HubStatement();
                $hub_statements->company_id       = settings()->id;
                $hub_statements->user_id          = Auth::user()->id;
                $hub_statements->hub_id           = $hub->id;
                $hub_statements->account_id       = $request->account_id;
                $hub_statements->delivery_man_id  = $request->delivery_man_id;
                $hub_statements->type             = AccountHeads::EXPENSE;
                $hub_statements->amount           = $request->amount;
                $hub_statements->date             = $request->date;
                $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
                $hub_statements->save();
                //add (-) amount
                $hub->current_balance             = $hub->current_balance +(-$request->amount) ;
                $hub->save();

                //Account
                //hub bank account statements
                $bank_statments                    = new BankTransaction();
                $bank_statments->company_id       = settings()->id;
                $bank_statments->account_id        = $account->id;
                $bank_statments->user_type         = UserType::HUB;
                $bank_statments->hub_id            = $hub->id;
                $bank_statments->type              = AccountHeads::INCOME;
                $bank_statments->amount            = $request->amount;
                $bank_statments->date              = $request->date;
                $bank_statments->cash_received_dvry = $cash_received->id;
                $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
                $bank_statments->save();
                //add (+) amount in account
                $account->balance          = $account->balance + $request->amount;
                $account->save();


                 //Delivery man
                 //add delivery man statements
                 $deliveryman_statment                  = new DeliverymanStatement();
                 $deliveryman_statment->company_id       = settings()->id;
                 $deliveryman_statment->delivery_man_id = $request->delivery_man_id;
                 $deliveryman_statment->hub_id          = $hub->id;
                 $deliveryman_statment->type            = AccountHeads::INCOME;
                 $deliveryman_statment->amount          = $request->amount;
                 $deliveryman_statment->date            = $request->date;
                 $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
                 $deliveryman_statment->save();

                 //add (+) amount
                 $deliveryman->current_balance         = $deliveryman->current_balance + $request->amount;
                 $deliveryman->save();

                 return true;
             else:
                return false;
             endif;
        } catch (\Throwable $th) {

           return false;
        }
    }
    public function update($request){

        //start first reverse
        $cash_received = CashReceivedFromDeliveryman::find($request->id);
        // $cash_received
        $deliveryman  = DeliveryMan::find($cash_received->delivery_man_id);
        $account      = Account::find($cash_received->account_id);
        $hub          = Hub::find(Auth::user()->hub_id);
        //add hub statements
        $hub_statements                   = new HubStatement();
        $hub_statements->company_id       = settings()->id;
        $hub_statements->user_id          = Auth::user()->id;
        $hub_statements->hub_id           = $hub->id;
        $hub_statements->account_id       = $cash_received->account_id;
        $hub_statements->delivery_man_id  = $cash_received->delivery_man_id;
        $hub_statements->type             = AccountHeads::INCOME;
        $hub_statements->amount           = $cash_received->amount;
        $hub_statements->date             = $cash_received->date;
        $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
        $hub_statements->save();
        //add (+) amount
        $hub->current_balance             = $hub->current_balance +(+$cash_received->amount) ;
        $hub->save();
        //hub bank account statements
        $bank_statments                    = new BankTransaction();
        $bank_statments->company_id        = settings()->id;
        $bank_statments->account_id        = $account->id;
        $bank_statments->user_type         = UserType::HUB;
        $bank_statments->hub_id            = $hub->id;
        $bank_statments->type              = AccountHeads::EXPENSE;
        $bank_statments->amount            = $cash_received->amount;
        $bank_statments->date              = $cash_received->date;
        $bank_statments->cash_received_dvry = $cash_received->id;
        $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
        $bank_statments->save();

        //add (-) amount in account
        $account->balance          = $account->balance - $cash_received->amount;
        $account->save();
        //add delivery man statements
        $deliveryman_statment                  = new DeliverymanStatement();
        $deliveryman_statment->company_id      = settings()->id;
        $deliveryman_statment->delivery_man_id = $cash_received->delivery_man_id;
        $deliveryman_statment->hub_id          = $hub->id;
        $deliveryman_statment->type            = AccountHeads::EXPENSE;
        $deliveryman_statment->amount          = $cash_received->amount;
        $deliveryman_statment->date            = $cash_received->date;
        $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
        $deliveryman_statment->save();
        //add (-) amount
        $deliveryman->current_balance         = $deliveryman->current_balance - $cash_received->amount;
        $deliveryman->save();
        // end all reverse
        //again store
        $deliveryman  = DeliveryMan::find($request->delivery_man_id);
        $account      = Account::find($request->account_id);
        $hub          = Hub::find(Auth::user()->hub_id);

         //cash received from delivery man table
        //add hub statements
        $cash_received->user_id          = Auth::user()->id;
        $cash_received->hub_id           = $hub->id;
        $cash_received->account_id       = $request->account_id;
        $cash_received->delivery_man_id  = $request->delivery_man_id;
        $cash_received->amount           = $request->amount;
        $cash_received->date             = $request->date;
        $cash_received->receipt          = $this->file('',$request->receipt);
        $cash_received->note             = __('permissions.cash_received_from_delivery_man');
        $cash_received->save();

        //add hub statements
        $hub_statements                   = new HubStatement();
        $hub_statements->user_id          = Auth::user()->id;
        $hub_statements->company_id       = settings()->id;
        $hub_statements->hub_id           = $hub->id;
        $hub_statements->account_id       = $request->account_id;
        $hub_statements->delivery_man_id  = $request->delivery_man_id;
        $hub_statements->type             = AccountHeads::EXPENSE;
        $hub_statements->amount           = $request->amount;
        $hub_statements->date             = $request->date;
        $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
        $hub_statements->save();

        //add (-) amount
        $hub->current_balance             = $hub->current_balance +(-$request->amount) ;
        $hub->save();

        //hub bank account statements
        $bank_statments                    = new BankTransaction();
        $bank_statments->company_id        = settings()->id;
        $bank_statments->account_id        = $account->id;
        $bank_statments->user_type         = UserType::HUB;
        $bank_statments->hub_id            = $hub->id;
        $bank_statments->type              = AccountHeads::INCOME;
        $bank_statments->amount            = $request->amount;
        $bank_statments->date              = $request->date;
        $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
        $bank_statments->save();

        //add (+) amount in account
        $account->balance                  = $account->balance + $request->amount;
        $account->save();

        //add delivery man statements
        $deliveryman_statment                  = new DeliverymanStatement();
        $deliveryman_statment->company_id      = settings()->id;
        $deliveryman_statment->delivery_man_id = $request->delivery_man_id;
        $deliveryman_statment->hub_id          = $hub->id;
        $deliveryman_statment->type            = AccountHeads::INCOME;
        $deliveryman_statment->amount          = $request->amount;
        $deliveryman_statment->date            = $request->date;
        $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
        $deliveryman_statment->save();
        //add (+) amount
        $deliveryman->current_balance         = $deliveryman->current_balance + $request->amount;
        $deliveryman->save();
        return true;
    }
    public function delete($id){

        if(Auth::user()->hub_id):

            try {

                $cash_received = CashReceivedFromDeliveryman::find($id);
                if($cash_received->company_id == settings()->id):

                    $deliveryman   = DeliveryMan::find($cash_received->delivery_man_id);
                    $account       = Account::find($cash_received->account_id);
                    $hub           = Hub::find(Auth::user()->hub_id);
                    //add hub statements
                    $hub_statements                   = new HubStatement();
                    $hub_statements->company_id       = settings()->id;
                    $hub_statements->user_id          = Auth::user()->id;
                    $hub_statements->hub_id           = $hub->id;
                    $hub_statements->account_id       = $cash_received->account_id;
                    $hub_statements->delivery_man_id  = $cash_received->delivery_man_id;
                    $hub_statements->type             = AccountHeads::INCOME;
                    $hub_statements->amount           = $cash_received->amount;
                    $hub_statements->date             = $cash_received->date;
                    $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
                    $hub_statements->save();
                    //add (-) amount
                    $hub->current_balance             = $hub->current_balance +(+$cash_received->amount) ;
                    $hub->save();

                    //hub bank account statements
                    $bank_statments                    = new BankTransaction();
                    $bank_statments->company_id        = settings()->id;
                    $bank_statments->account_id        = $account->id;
                    $bank_statments->user_type         = UserType::HUB;
                    $bank_statments->hub_id            = $hub->id;
                    $bank_statments->type              = AccountHeads::EXPENSE;
                    $bank_statments->amount            = $cash_received->amount;
                    $bank_statments->date              = $cash_received->date;
                    $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
                    $bank_statments->save();

                    //add (+) amount in account
                    $account->balance          = $account->balance - $cash_received->amount;
                    $account->save();

                    //add delivery man statements
                    $deliveryman_statment                  = new DeliverymanStatement();
                    $deliveryman_statment->company_id      = settings()->id;
                    $deliveryman_statment->delivery_man_id = $cash_received->delivery_man_id;
                    $deliveryman_statment->hub_id          = $hub->id;
                    $deliveryman_statment->type            = AccountHeads::EXPENSE;
                    $deliveryman_statment->amount          = $cash_received->amount;
                    $deliveryman_statment->date            = $cash_received->date;
                    $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
                    $deliveryman_statment->save();
                    //add (+) amount
                    $deliveryman->current_balance         = $deliveryman->current_balance - $cash_received->amount;
                    $deliveryman->save();
                    return CashReceivedFromDeliveryman::destroy($cash_received->id);
                endif;
                return false;
            } catch (\Throwable $th) {
                return false;
            }
        else:
            return false;
        endif;

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
