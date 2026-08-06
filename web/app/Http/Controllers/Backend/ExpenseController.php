<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Account\AccountInterface;
use App\Repositories\AccountHeads\AccountHeadsInterface;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
class ExpenseController extends Controller
{
    protected $repo;
    public function __construct(
        ExpenseInterface $repo,
        ParcelInterface $parcel,
        MerchantInterface $merchant,
        DeliveryManInterface $deliveryman,
        AccountInterface $account,
        AccountHeadsInterface $account_heads,
        )
    {
        $this->repo        = $repo;
        $this->parcel      = $parcel;
        $this->merchant    = $merchant;
        $this->deliveryman = $deliveryman;
        $this->account     = $account;
        $this->account_heads = $account_heads;
    }

    public function index(Request $request)
    {
        $expenses     = $this->repo->all();
        $accounts     = $this->account->all();

        return view('backend.expense.index',compact('expenses','request','accounts'));
    }
     public function filter(Request $request)
        {
            $expenses     = $this->repo->filter($request);
            $accounts     = $this->account->all();
            return view('backend.expense.index',compact('expenses','request','accounts'));
        }

    public function create()
    {
        $account_heads = $this->account_heads->all();
        $accounts     = $this->account->all();
        return view('backend.expense.create',compact('accounts','account_heads'));
    }

    public function searchAccount($id){
        return $this->account->get($id);
    }

    public function store(StoreExpenseRequest $request)
    {
        $result = $this->repo->store($request);
        if($result == 2){
            Toastr::warning(__('expense.not_enough_balence'),__('message.warning'));
            return redirect()->route('expense.index');
        }
        if($result == 1){
            Toastr::success(__('expense.added_msg'),__('message.success'));
            return redirect()->route('expense.index');
        }
        else{
            Toastr::error(__('expense.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $account_heads = $this->account_heads->all();
        $expense      = $this->repo->get($id);
        $parcel       = $this->parcel->get($expense->parcel_id);
        $accounts     = $this->account->all();
        $t_balance    = $this->account->get($expense->account_id);
        $old_amount   = $t_balance->balance + $expense->amount;
        $deliveryman  = $this->deliveryman->get($expense->delivery_man_id);
        return view('backend.expense.edit',compact('account_heads','expense','accounts','parcel','old_amount','deliveryman'));
    }

    public function update($id, UpdateExpenseRequest $request)
    {
        $result = $this->repo->update($id, $request);
        if($result == 2){
            Toastr::warning(__('expense.not_enough_balence'),__('message.warning'));
            return redirect()->route('expense.index');
        }
        else if($result == 1){
            Toastr::success(__('expense.update_msg'),__('message.success'));
            return redirect()->route('expense.index');
        }else{
            Toastr::error(__('expense.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('expense.delete_msg'),__('message.success'));
            return back();
        }
        else{
            Toastr::error(__('expense.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function ExpenseUsers(Request $request){
        if($request->ajax()):
            $users = User::where('name','like','%'.$request->search.'%')->paginate(10);
            $response = [];
            foreach ($users as  $user) {
                $response [] = [
                    'id'  => $user->id,
                    'text'=> $user->name
                ];
            }
            return response()->json($response);
        endif;
    }

}
