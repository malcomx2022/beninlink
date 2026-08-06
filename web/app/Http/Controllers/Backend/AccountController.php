<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Account\StoreRequest;
use App\Http\Requests\Account\UpdateRequest;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
class AccountController extends Controller
{
    protected $repo;
    public function __construct(AccountInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $accounts = $this->repo->all();
        return view('backend.account.index',compact('accounts','request'));
    }
    public function filter(Request $request)
    {
        $accounts = $this->repo->filter($request);
        return view('backend.account.index',compact('accounts','request'));
    }

    public function create()
    {
        $users = $this->repo->users();
        return view('backend.account.create',compact('users'));
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('account.added_msg_'),__('message.success'));
            return redirect()->route('accounts.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $users = $this->repo->users();
        $account = $this->repo->get($id);
        return view('backend.account.edit',compact('account','users'));
    }

    public function update($id, UpdateRequest $request)
    {
        if($this->repo->update($id, $request)){
            Toastr::success(__('account.update_msg'),__('message.success'));
            return redirect()->route('accounts.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('account.delete_msg'),__('message.success'));
        return back();
    }

    public function currentBalance(Request $data)
    {
        return $this->repo->currentBalance($data);
    }


}
