<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Income\StoreIncomeRequest;
use App\Http\Requests\Income\UpdateIncomeRequest;
use App\Models\User;
use App\Repositories\Income\IncomeInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Hub\HubInterface;
use Brian2694\Toastr\Facades\Toastr;
class IncomeController extends Controller
{
    protected $repo;
    protected $account;
    protected $hub;
    public function __construct(
        IncomeInterface $repo,
        ParcelInterface $parcel,
        MerchantInterface $merchant,
        DeliveryManInterface $deliveryman,
        AccountInterface $account,
        HubInterface $hub,
        )
    {
        $this->repo        = $repo;
        $this->parcel      = $parcel;
        $this->merchant    = $merchant;
        $this->deliveryman = $deliveryman;
        $this->account     = $account;
        $this->hub         = $hub;
    }


    public function IncomeUsers(Request $request){
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
    public function index(Request $request)
    {
        $incomes = $this->repo->all();
        $accounts     = $this->account->all();
        return view('backend.income.index',compact('incomes','accounts','request'));

    }
    public function filter(Request $request)
    {
        $incomes = $this->repo->filter($request);
        $accounts     = $this->account->all();
        return view('backend.income.index',compact('incomes','accounts','request'));

    }

    public function create()
    {
        $hubs         = $this->hub->all();
        $accounts     = $this->account->all();
        $accountHeads = $this->repo->accountHeads();
        return view('backend.income.create',compact('accounts','accountHeads','hubs'));
    }

    public function searchAccount($id,Request $request){

        return $this->account->get($request);
    }

    public function balanceCheck(Request $request){
        $marchenHubDeliveryman = $this->repo->hubCheck($request);
        $users = $this->repo->hubUsers($marchenHubDeliveryman->id);

        return ['mhd' => $marchenHubDeliveryman, 'users' => $users];
    }

    public function hubUserAccounts(Request $request){
        return $this->repo->hubUserAccounts($request);
    }

    public function store(StoreIncomeRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('income.added_msg'),__('message.success'));
            return redirect()->route('income.index');
        }else{
            Toastr::error(__('income.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $income       = $this->repo->get($id);
        $parcel       = $this->parcel->get($income->parcel_id);
        $accounts     = $this->account->all();
        $t_balance    = $this->account->get($income->account_id);
        $deliveryman  = $this->deliveryman->get($income->delivery_man_id);
        $hub          = $this->hub->get($income->hub_id);
        $accountHeads = $this->repo->accountHeads();
        $hubs         = $this->hub->all();
        return view('backend.income.edit',compact('income','hubs','accounts','parcel','t_balance','deliveryman','accountHeads','hub'));
    }

    public function update($id, UpdateIncomeRequest $request)
    {
        if($this->repo->update($id, $request)){
            Toastr::success(__('income.update_msg'),__('message.success'));
            return redirect()->route('income.index');
        }else{
            Toastr::error(__('income.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('income.delete_msg'),__('message.success'));
            return back();
        }
        else{
            Toastr::error(__('income.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
}
