<?php
namespace App\Repositories\BankTransaction;
use App\Repositories\BankTransaction\BankTransactionInterface;
use App\Models\Backend\BankTransaction;
use Carbon\Carbon;

class BankTransactionRepository implements BankTransactionInterface{

    public function all(){
        return BankTransaction::companywise()->orderByDesc('id')->paginate(10);
    }

    public function filter($request){
        if($request->date && $request->type == null && $request->account == null) {
            $date = explode('To', $request->date);

            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            return BankTransaction::companywise()->orderByDesc('id')->whereBetween('date', [$from, $to])->paginate(10);
        }
        else if($request->type && $request->date == null && $request->account == null){
            return BankTransaction::companywise()->orderByDesc('id')->where('type', $request->type)->paginate(10);
        }
        else if($request->account && $request->type == null && $request->date == null){
            return BankTransaction::companywise()->orderByDesc('id')->where('account_id', $request->account)->paginate(10);
        }

        else if($request->date && $request->type && $request->account == null) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            return BankTransaction::companywise()->orderByDesc('id')->whereBetween('date', [$from, $to])->where('type',$request->type)->paginate(10);
        }

        else if($request->date == null && $request->type && $request->account) {
            return BankTransaction::companywise()->orderByDesc('id')->where('type',$request->type)->where('account_id',$request->account)->paginate(10);
        }

        else if($request->date && $request->type == null && $request->account) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            return BankTransaction::companywise()->orderByDesc('id')->whereBetween('date', [$from, $to])->where('account_id',$request->account)->paginate(10);
        }

        else if($request->date && $request->type && $request->account) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            return BankTransaction::companywise()->orderByDesc('id')->whereBetween('date', [$from, $to])->where('type',$request->type)->where('account_id',$request->account)->paginate(10);
        }
        else{
            return BankTransaction::companywise()->orderByDesc('id')->paginate(10);
        }

    }

    public function filterSearch($request){
        return  BankTransaction::companywise()->orderBy('id','desc')->where(function($query)use($request){
                $query->where('amount','like','%'.$request->search.'%');
                $query->orWhere('note','like','%'.$request->search.'%');
                $query->orWhereHas('account',function($query)use($request){
                    $query->where('account_holder_name','Like','%'.$request->search.'%');
                    $query->orWhere('account_no','Like','%'.$request->search.'%');
                    $query->orWhere('branch_name','Like','%'.$request->search.'%');
                    $query->orWhere('mobile','Like','%'.$request->search.'%');
                    $query->orWhere('account_type','Like','%'.$request->search.'%');
                    $query->orWhereHas('user',function($query) use($request){
                        $query->where('name','Like','%'.$request->search.'%');
                    });
                });
            });
    }


}
