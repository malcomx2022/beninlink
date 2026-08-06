<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AccountHeads;
use App\Models\Backend\Account;
use App\Models\CashReceivedFromDeliveryman;

class BankTransaction extends Model
{
    use HasFactory;

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    public function getAccountTypeAttribute()
    {
        if($this->type      == AccountHeads::INCOME){
            $type =  __('AccountHeads.'.AccountHeads::INCOME) ;
        }elseif($this->type == AccountHeads::EXPENSE){
            $type = __('AccountHeads.'.AccountHeads::EXPENSE);
        }
        return $type;
    }


    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }


    public function fundTransfer()
    {
        return $this->belongsTo(FundTransfer::class, 'fund_transfer_id', 'id');
    }

    public function income (){
        return $this->belongsTo(Income::class, 'income_id', 'id');
    }

    public function expense (){
        return $this->belongsTo(Expense::class, 'expense_id', 'id');
    }

    public function HubCashReceivedFromDeliveryman(){
        return $this->belongsTo(CashReceivedFromDeliveryman::class, 'cash_received_dvry', 'id');
    }

    public function hub(){
        return $this->belongsTo(Hub::class,'hub_id', 'id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
