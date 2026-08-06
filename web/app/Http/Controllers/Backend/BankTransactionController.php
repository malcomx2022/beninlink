<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\BankTransaction;
use Illuminate\Http\Request;
use App\Repositories\BankTransaction\BankTransactionInterface;
use App\Repositories\Account\AccountInterface;

class BankTransactionController extends Controller
{
    protected $repo, $account;
    public function __construct(BankTransactionInterface $repo, AccountInterface $account)
    {
        $this->repo = $repo;
        $this->account = $account;
    }

    public function index(Request $request){
        $accounts = $this->account->all();
        $transactions = $this->repo->all();
        return view('backend.bank_transaction.index',compact('accounts','transactions','request'));
    }

    public function filter(Request $request){
        $accounts = $this->account->all();
        $transactions = $this->repo->filter($request);
        return view('backend.bank_transaction.index',compact('accounts','transactions','request'));
    }

    public function bankTransactionPrint(Request $request){
        $transactions   = BankTransaction::companywise()->whereIn('id',$request->ids)->get();
        return view('backend.bank_transaction.transaction_print',compact('transactions'));
    }

    public function bankTransactionSpecificSearch(Request $request){
        $accounts     =  $this->account->all();
        $transactions =  $this->repo->filterSearch($request)->paginate(10);
        $search       =  $this->repo->filterSearch($request)->get()->pluck('id')->toArray();
        return view('backend.bank_transaction.index',compact('accounts','transactions','request','search'));
    }

}
