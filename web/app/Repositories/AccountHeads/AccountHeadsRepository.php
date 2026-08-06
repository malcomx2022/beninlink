<?php
namespace App\Repositories\AccountHeads;

use App\Models\Backend\AccountHead;
use App\Repositories\AccountHeads\AccountHeadsInterface;

class AccountHeadsRepository implements AccountHeadsInterface{

    public function all(){
        return AccountHead::orderBy('id','ASC')->paginate(10);
    }
    public function get($id){
        return AccountHead::find($id);
    }
    public function store($request){

        try {
            $account_head           = new AccountHead();
            $account_head->type     = $request->type;
            $account_head->name     = $request->name;
            $account_head->status   = $request->status;
            $account_head->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }
    public function update($id,$request){

        $account_head           = AccountHead::find($id);
        $account_head->type     = $request->type;
        $account_head->name     = $request->name;
        $account_head->status   = $request->status;
        $account_head->save();
        return true;
    }
    public function delete($id){
        return AccountHead::destroy($id);
    }

}
