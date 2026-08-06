<?php
namespace App\Repositories\FundTransfer;
use App\Models\Backend\FundTransfer;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Repositories\FundTransfer\FundTransferInterface;
use App\Enums\Status;
use App\Enums\AccountHeads;
use App\Enums\UserType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FundTransferRepository implements FundTransferInterface{
    public function all(){
        return FundTransfer::companywise()->orderByDesc('id')->with('fromAccount','fromAccount.user','fromAccount.user.upload','toAccount','toAccount.user','toAccount.user.upload')->paginate(10);
    }

    public function get($id){
        return FundTransfer::find($id);
    }

    public function accounts(){
        return Account::companywise()->get();
    }

    public function store($request){
        try {
            DB::beginTransaction();
            // check balance in from account and minus balance
            $from_account = Account::find($request->from_account);
            if($from_account->balance < $request->amount){
                return 2;
            }
            elseif($request->amount <= 0){
                return 3;
            }
            $from_account->balance              = $from_account->balance - $request->amount;
            $from_account->save();
            // add balance in to account
            $to_account                         = Account::find($request->to_account);
            $to_account->balance                = $to_account->balance + $request->amount;
            $to_account->save();
            // add row fund transter
            $fund_transfer                      = new FundTransfer();
            $fund_transfer->company_id          = settings()->id;
            $fund_transfer->from_account        = $request->from_account;
            $fund_transfer->to_account          = $request->to_account;
            $fund_transfer->amount              = $request->amount;
            $fund_transfer->date                = $request->date;
            $fund_transfer->description         = $request->description;
            $fund_transfer->save();
            // add row bank transactions (expense)
            $transaction                        = new BankTransaction();
            $transaction->company_id            = settings()->id;
            $transaction->fund_transfer_id      = $fund_transfer->id;
            $transaction->account_id            = $request->from_account;
            $transaction->type                  = AccountHeads::EXPENSE;
            $transaction->amount                = $request->amount;
            $transaction->date                  = $request->date;
            $transaction->note                  = __('fund_transfer.expense');
            $transaction->save();
            // add row bank transactions (income)
            $transaction                        = new BankTransaction();
            $transaction->company_id            = settings()->id;
            $transaction->fund_transfer_id      = $fund_transfer->id;
            $transaction->account_id            = $request->to_account;
            $transaction->type                  = AccountHeads::INCOME;
            $transaction->amount                = $request->amount;
            $transaction->date                  = $request->date;
            $transaction->note                  = __('fund_transfer.income');
            $transaction->save();
            DB::commit();
            return 1;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            DB::beginTransaction();
            // select fund transfer row
            $fund_transfer = FundTransfer::find($id);
            // return balance from account
            $from_account = Account::find($fund_transfer->from_account);
            $from_account->balance = $from_account->balance + $fund_transfer->amount;
            $from_account->save();
            // minus balance to account
            $to_account = Account::find($fund_transfer->to_account);
            $to_account->balance = $to_account->balance - $fund_transfer->amount;
            $to_account->save();
            // delete transaction rows
            $transactions           = BankTransaction::where('fund_transfer_id', $fund_transfer->id)->pluck('id')->all();
            BankTransaction::whereIn('id', $transactions)->delete();
            // from account check balance and minus balance
            $from_account = Account::find($request->from_account);
            if($from_account->balance < $request->amount){
                return 2;
            }
            elseif($request->amount <= 0){
                return 3;
            }
            $from_account->balance              = $from_account->balance - $request->amount;
            $from_account->save();
            // To account add balance
            $to_account = Account::find($request->to_account);
            $to_account->balance                = $to_account->balance + $request->amount;
            $to_account->save();
            // fund transfer row update
            $fund_transfer->from_account        = $request->from_account;
            $fund_transfer->to_account          = $request->to_account;
            $fund_transfer->amount              = $request->amount;
            $fund_transfer->date                = $request->date;
            $fund_transfer->description         = $request->description;
            $fund_transfer->save();
            // add row bank transactions (expense)
            $transaction                        = new BankTransaction();
            $transaction->company_id            = settings()->id;
            $transaction->fund_transfer_id      = $fund_transfer->id;
            $transaction->account_id            = $request->from_account;
            $transaction->type                  = AccountHeads::EXPENSE;
            $transaction->amount                = $request->amount;
            $transaction->date                  = $request->date;
            $transaction->note                  = __('fund_transfer.expense');
            $transaction->save();
            // add row bank transactions (income)
            $transaction                        = new BankTransaction();
            $transaction->company_id            = settings()->id;
            $transaction->fund_transfer_id      = $fund_transfer->id;
            $transaction->account_id            = $request->to_account;
            $transaction->type                  = AccountHeads::INCOME;
            $transaction->amount                = $request->amount;
            $transaction->date                  = $request->date;
            $transaction->note                  = __('fund_transfer.income');
            $transaction->save();
            DB::commit();
            return 1;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function delete($id){
        try {
            DB::beginTransaction();
            // select fund transfer row
            $fund_transfer         = FundTransfer::find($id);
            if($fund_transfer->company_id == settings()->id):
                // return balance in from account
                $from_account          = Account::find($fund_transfer->from_account);
                $from_account->balance = $from_account->balance + $fund_transfer->amount;
                $from_account->save();
                // minus balance in to account
                $to_account            = Account::find($fund_transfer->to_account);
                $to_account->balance   = $to_account->balance - $fund_transfer->amount;
                $to_account->save();
                // delete transactions row
                $transactions          = BankTransaction::where('fund_transfer_id', $fund_transfer->id)->pluck('id')->all();
                BankTransaction::whereIn('id', $transactions)->delete();
                // delete fund transfer row
                $fund_transfer->delete();
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


    public function fundTransferSearch($request){
        return FundTransfer::companywise()->whereHas('fromAccount',function($query) use($request){
            $query->where('account_holder_name','Like','%'.$request->search.'%');
            $query->orWhere('account_no','Like','%'.$request->search.'%');
            $query->orWhere('branch_name','Like','%'.$request->search.'%');
            $query->orWhere('mobile','Like','%'.$request->search.'%');
            $query->orWhere('account_type','Like','%'.$request->search.'%');
            $query->orWhereHas('user',function($query) use($request){
                $query->where('name','Like','%'.$request->search.'%');
            });
        })
        ->orWhereHas('toAccount',function($query) use($request){
            $query->where('account_holder_name','Like','%'.$request->search.'%');
            $query->orWhere('account_no','Like','%'.$request->search.'%');
            $query->orWhere('branch_name','Like','%'.$request->search.'%');
            $query->orWhere('mobile','Like','%'.$request->search.'%');
            $query->orWhere('account_type','Like','%'.$request->search.'%');
            $query->orWhereHas('user',function($query) use($request){
                $query->where('name','Like','%'.$request->search.'%');
            });
        });

    }

    public function fundTransferFilter($request){
        return FundTransfer::companywise()->orderByDesc('id')->where(function( $query ) use ( $request ) {
            if($request->date) {
                $date = explode('To', $request->date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            if(!blank($request->from_account)):
                $query->where('from_account',$request->from_account);
            endif; 
            if(!blank($request->to_account)):
                $query->where('to_account',$request->to_account);
            endif; 
        });
    }


}
