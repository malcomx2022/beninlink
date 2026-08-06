<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\FundTransfer\StoreRequest;
use App\Http\Requests\FundTransfer\UpdateRequest;
use App\Models\Backend\FundTransfer;
use App\Repositories\FundTransfer\FundTransferInterface;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
class FundTransferController extends Controller
{
    protected $repo,$account;
    public function __construct(FundTransferInterface $repo, AccountInterface $account)
    {
        $this->repo    = $repo;
        $this->account = $account;
    }

    public function index(Request $request)
    {
        $fund_transfers = $this->repo->all();
        $accounts       = $this->account->all();
        return view('backend.fund_transfer.index',compact('fund_transfers','request','accounts'));
    }

    public function create()
    {
        $accounts = $this->repo->accounts();
        return view('backend.fund_transfer.create',compact('accounts'));
    }

    public function store(StoreRequest $request)
    {
        $result = $this->repo->store($request);
        if($result == 2){
            Toastr::warning(__('fund_transfer.not_enough_balance'),__('message.warning'));
            return redirect()->back()->withInput();
        }
        elseif($result == 3){
            Toastr::warning(__('fund_transfer.more_than_0tk'),__('message.warning'));
            return redirect()->back()->withInput();
        }
        elseif($result == 1){
            Toastr::success(__('fund_transfer.added_msg'),__('message.success'));
            return redirect()->route('fund-transfer.index');
        }
        else{
            Toastr::error(__('fund_transfer.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $accounts = $this->repo->accounts();
        $fund_transfer = $this->repo->get($id);
        $account = $this->account->get($fund_transfer->from_account);
        $current_balance = $account->balance + $fund_transfer->amount;
        return view('backend.fund_transfer.edit',compact('fund_transfer','accounts','current_balance'));
    }

    public function update($id, UpdateRequest $request)
    {
        $result = $this->repo->update($id, $request);
        if($result == 2){
            Toastr::warning(__('fund_transfer.not_enough_balance'),__('message.warning'));
            return redirect()->back()->withInput();
        }
        elseif($result == 3){
            Toastr::warning(__('fund_transfer.more_than_0tk'),__('message.warning'));
            return redirect()->back()->withInput();
        }
        elseif($result == 1){
            Toastr::success(__('fund_transfer.update_msg'),__('message.success'));
            return redirect()->route('fund-transfer.index');
        }else{
            Toastr::error(__('fund_transfer.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('fund_transfer.delete_msg'),__('message.success'));
            return back();
        }else{
            Toastr::error(__('fund_transfer.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function fundTransferSpecificSearch(Request $request){
        $fund_transfers = $this->repo->fundTransferSearch($request)->paginate(10);
        $search         = $this->repo->fundTransferSearch($request)->get()->pluck('id')->toArray();
        $accounts       = $this->account->all();
        return view('backend.fund_transfer.index',compact('fund_transfers','request','search','accounts'));
    }

    public function fundTransferSearchFilterPrint(Request $request){

        $fund_transfers = FundTransfer::whereIn('id',$request->ids)->get();
        return view('backend.fund_transfer.print',compact('fund_transfers'));
    }

    public function fundTransferFilter(Request $request){
        $fund_transfers = $this->repo->fundTransferFilter($request)->paginate(10);
        $search         = $this->repo->fundTransferFilter($request)->get()->pluck('id')->toArray();
        $accounts       = $this->account->all();
        return view('backend.fund_transfer.index',compact('fund_transfers','request','search','accounts'));
    }


}
