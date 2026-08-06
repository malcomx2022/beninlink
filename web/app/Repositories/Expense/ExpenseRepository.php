<?php
namespace App\Repositories\Expense;
use App\Repositories\Expense\ExpenseInterface;
use App\Models\Backend\Expense;
use App\Models\Backend\Upload;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\AccountHead;
use Illuminate\Support\Facades\DB;
use App\Enums\AccountHeads;
use App\Enums\StatementType;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\CourierStatement;

class ExpenseRepository implements ExpenseInterface{

    public function all(){
        return Expense::companywise()->with('merchant','merchant.user','deliveryman','deliveryman.user','account','parcel')->orderByDesc('id')->paginate(10);
    }

    public function filter($request){
        return Expense::companywise()->with('merchant','merchant.user','deliveryman','deliveryman.user','account','parcel')->where(function($query)use($request){
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
    // get single row
    public function get($id){
        return Expense::companywise()->with('parcel')->find($id);
    }
    // All request data store in User tabel.
    public function store($request)
    {
        try {
            DB::beginTransaction();
            // check accont balance
            $account = Account::find($request->account_id);
            if($account->balance < $request->amount){
                return 2;
            }
            // data insert expense table
            $expense                    = new Expense();
            $expense->company_id        = settings()->id;
            $expense->account_head_id   = $request->account_head;
            $expense->merchant_id       = $request->merchant_id;
            if($request->delivery_man_id):
            $expense->delivery_man_id   = $request->delivery_man_id;
            endif;
            if($request->user_id !== ''):
            $expense->user_id           = $request->user_id;
            endif;
            if($request->parcel_id !== ''):
            $expense->parcel_id         = $request->parcel_id;
            endif;
            $expense->account_id        = $request->account_id;
            $expense->amount            = $request->amount;
            $expense->date              = $request->date;
            $expense->receipt           = $this->file('', $request->receipt);
            $expense->note              = $request->note;
            $expense->title             = $request->title;
            $expense->save();
            // amount minus
            $account->balance           = $account->balance - $request->amount;
            $account->save();
            // add row bank transactions
            if($request->account_head == 6){
                $note = $request->title;
            }
            else{
                $head = AccountHead::find($request->account_head);
                $note = $head->name;
            }
            $transaction                       = new BankTransaction();
            $transaction->company_id           = settings()->id;
            $transaction->expense_id           = $expense->id;
            $transaction->account_id           = $request->account_id;
            $transaction->type                 = AccountHeads::EXPENSE;
            $transaction->amount               = $request->amount;
            $transaction->date                 = $request->date;
            $transaction->note                 = $note;
            $transaction->save();
            // add row courier statement
            $courierStatement                       = new CourierStatement();
            $courierStatement->company_id           = settings()->id;
            $courierStatement->expense_id           = $expense->id;
            if($request->parcel_id !== ''):
            $courierStatement->parcel_id            = $request->parcel_id;
            endif;
            if($request->delivery_man_id):
            $courierStatement->delivery_man_id      = $request->delivery_man_id;
            endif;
            $courierStatement->amount               = $request->amount;
            $courierStatement->type                 = StatementType::EXPENSE;
            $courierStatement->date                 = date('Y-m-d H:i:s');
            $courierStatement->note                 = $note;
            $courierStatement->save();
            if($request->account_head == 4){
                $merchant                          = Merchant::find($request->merchant_id);
                $merchant->current_balance         = $merchant->current_balance - $request->amount;
                $merchant->save();
                //Merchant statment
                $m_statement                       = new MerchantStatement();
                $m_statement->company_id           = settings()->id;
                $m_statement->expense_id           = $expense->id;
                $m_statement->merchant_id          = $request->merchant_id;
                $m_statement->amount               = $request->amount;
                $m_statement->type                 = StatementType::INCOME;
                $m_statement->date                 = date('Y-m-d H:i:s');
                $m_statement->note                 = $note;
                $m_statement->save();
            }
            else if($request->account_head == 5){
                $d_man                             = DeliveryMan::find($request->delivery_man_id);
                $d_man->current_balance            = $d_man->current_balance - $request->amount;
                $d_man->save();
                // Delivery man statement
                $d_statement                       = new DeliverymanStatement();
                $d_statement->company_id           = settings()->id;
                $d_statement->expense_id           = $expense->id;
                $d_statement->delivery_man_id      = $request->delivery_man_id;
                $d_statement->amount               = $request->amount;
                $d_statement->type                 = StatementType::INCOME;
                $d_statement->date                 = date('Y-m-d H:i:s');
                $d_statement->note                 = $note;
                $d_statement->save();
            }
            DB::commit();
            return 1;
        }
        catch (\Exception $e) { 
            DB::rollBack();
            return false;
        }
    }

    // All request data update in User tabel.
    public function update($id, $request)
    {
        try {
            DB::beginTransaction();
            $expense                    = Expense::find($id);
            // return balance
            $account                    = Account::find($expense->account_id);
            $account->balance           = $account->balance + $expense->amount;
            $account->save();
            if($expense->account_head_id == 4){
                $merchant                   = Merchant::find($expense->merchant_id);
                $merchant->current_balance  = $merchant->current_balance + $expense->amount;
                $merchant->save();
            }
            else if($expense->account_head_id == 5){
                $d_man                      = DeliveryMan::find($expense->delivery_man_id);
                $d_man->current_balance     = $d_man->current_balance + $expense->amount;
                $d_man->save();
            }
            // end return balance
            // check accont balance
            $account = Account::find($request->account_id);
            if($account->balance < $request->amount){
                return 2;
            }

            $expense->account_head_id   = $request->account_head;
            $expense->merchant_id       = $request->merchant_id;
            if($request->delivery_man_id):
            $expense->delivery_man_id   = $request->delivery_man_id;
            endif;
            if($request->user_id !== ''):
            $expense->user_id           = $request->user_id;
            endif;

            if($request->parcel_id !== ''):
            $expense->parcel_id         = $request->parcel_id;
            endif;
            $expense->account_id        = $request->account_id;
            $expense->amount            = $request->amount;
            $expense->date              = $request->date;

            if(isset($request->receipt) && $request->receipt != null)
            {
                $expense->receipt = $this->file($expense->receipt, $request->receipt);
            }
            $expense->note              = $request->note;
            $expense->title             = $request->title;
            $expense->save();
            // amount minus
            $account->balance           = $account->balance - $request->amount;
            $account->save();
            // add row bank transactions
            if($request->account_head == 6){
                $note = $request->title;
            }
            else{
                $head = AccountHead::find($request->account_head);
                $note = $head->name;
            }
            $transaction                       = BankTransaction::where('expense_id',$id)->first();
            $transaction->account_id           = $request->account_id;
            $transaction->type                 = AccountHeads::EXPENSE;
            $transaction->amount               = $request->amount;
            $transaction->date                 = $request->date;
            $transaction->note                 = $note;
            $transaction->save();
            // add row courier statement
            $courierStatement                       = CourierStatement::where('expense_id',$id)->first();
            if($request->parcel_id !== ''):
            $courierStatement->parcel_id            = $request->parcel_id;
            endif;
            if($request->delivery_man_id):
            $courierStatement->delivery_man_id      = $request->delivery_man_id;
            endif;
            $courierStatement->amount               = $request->amount;
            $courierStatement->type                 = StatementType::EXPENSE;
            $courierStatement->date                 = date('Y-m-d H:i:s');
            $courierStatement->note                 = $note;
            $courierStatement->save();
            if($request->account_head == 4){
                $merchant                          = Merchant::find($request->merchant_id);
                $merchant->current_balance         = $merchant->current_balance - $request->amount;
                $merchant->save();
                //Merchant statment
                $m_statement                       = MerchantStatement::where('expense_id',$id)->first();
                if($m_statement == null){
                    $m_statement                   = new MerchantStatement();
                    $m_statement->company_id       = settings()->id;
                    $d = DeliverymanStatement::where('expense_id',$id)->first();
                    if($d){
                        $d->delete();
                    }
                }
                $m_statement->expense_id           = $expense->id;
                $m_statement->merchant_id          = $request->merchant_id;
                $m_statement->amount               = $request->amount;
                $m_statement->type                 = StatementType::INCOME;
                $m_statement->date                 = date('Y-m-d H:i:s');
                $m_statement->note                 = $note;
                $m_statement->save();
            }
            else if($request->account_head == 5){
                $d_man                             = DeliveryMan::find($request->delivery_man_id);
                $d_man->current_balance            = $d_man->current_balance - $request->amount;
                $d_man->save();
                // Delivery man statement
                $d_statement                       = DeliverymanStatement::where('expense_id',$id)->first();
                if($d_statement == null){
                    $d_statement                   = new DeliverymanStatement();
                    $d_statement->company_id       = settings()->id;
                    $m = MerchantStatement::where('expense_id',$id)->first();
                    if($m){
                        $m->delete();
                    }
                }
                $d_statement->expense_id           = $expense->id;
                $d_statement->delivery_man_id      = $request->delivery_man_id;
                $d_statement->amount               = $request->amount;
                $d_statement->type                 = StatementType::INCOME;
                $d_statement->date                 = date('Y-m-d H:i:s');
                $d_statement->note                 = $note;
                $d_statement->save();
            }
            else if($request->account_head == 6){
                $m = MerchantStatement::where('expense_id',$id)->first();
                if($m){
                    $m->delete();
                }
                $d = DeliverymanStatement::where('expense_id',$id)->first();
                if($d){
                    $d->delete();
                }
            }
            DB::commit();
            return 1;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    // Delete single row in User Model with Delete single row in Upload model and delete image in public/upload/user folder..
    public function delete($id){
        try {
            DB::beginTransaction();
            $expense = Expense::with('upload')->find($id);

            if($expense->company_id == settings()->id): 
                if($expense->receipt != null){
                    Upload::destroy($expense->upload->id);
                    if(file_exists($expense->upload->original))
                        unlink($expense->upload->original);
                }
                $expense->delete();
                // balance return in from account
                $account                    = Account::find($expense->account_id);
                $account->balance           = $account->balance + $expense->amount;
                $account->save();
                $account_head = AccountHead::find($expense->account_head_id);
                // add row bank transection
                $transaction                       = new BankTransaction();
                $transaction->company_id           = settings()->id;
                $transaction->expense_id           = $id;
                $transaction->account_id           = $expense->account_id;
                $transaction->type                 = AccountHeads::INCOME;
                $transaction->amount               = $expense->amount;
                $transaction->date                 = date('Y-m-d H:i:s');
                $transaction->note                 = $account_head->name . " cancel.";
                $transaction->save();
                // add row courier statement
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->expense_id           = $id;
                $courierStatement->parcel_id            = $expense->parcel_id;
                $courierStatement->delivery_man_id      = $expense->delivery_man_id;
                $courierStatement->amount               = $expense->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = $account_head->name . " cancel.";
                $courierStatement->save();
                if($expense->account_head_id == 4){
                    // balance minus in merchant account
                    $merchant                   = Merchant::find($expense->merchant_id);
                    $merchant->current_balance  = $merchant->current_balance + $expense->amount;
                    $merchant->save();
                    //Merchant statment
                    $m_statement                       = new MerchantStatement();
                    $m_statement->company_id           = settings()->id;
                    $m_statement->expense_id           = $id;
                    $m_statement->delivery_man_id      = $expense->delivery_man_id;
                    $m_statement->amount               = $expense->amount;
                    $m_statement->type                 = StatementType::EXPENSE;
                    $m_statement->date                 = date('Y-m-d H:i:s');
                    $m_statement->note                 = $account_head->name . " cancel.";
                    $m_statement->save();
                }
                else if($expense->account_head_id == 5){
                    // balance minus in delivery man account
                    $d_man                      = DeliveryMan::find($expense->delivery_man_id);
                    $d_man->current_balance     = $d_man->current_balance + $expense->amount;
                    $d_man->save();
                    // Delivery man statement
                    $d_statement                       = new DeliverymanStatement();
                    $d_statement->company_id           = settings()->id;
                    $d_statement->expense_id           = $id;
                    $d_statement->delivery_man_id      = $expense->delivery_man_id;
                    $d_statement->amount               = $expense->amount;
                    $d_statement->type                 = StatementType::EXPENSE;
                    $d_statement->date                 = date('Y-m-d H:i:s');
                    $d_statement->note                 = $account_head->name . " cancel.";
                    $d_statement->save();
                }
                DB::commit();
                return true;
            endif;
            return false;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
    // Request image Store in Upload Model and image copy file attach in public/upload/user folder.
    public function file($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/expense');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/expense/'.$profileImage;
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
