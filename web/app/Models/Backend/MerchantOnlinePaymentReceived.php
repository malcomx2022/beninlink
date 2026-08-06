<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantOnlinePaymentReceived extends Model
{
    use HasFactory;
    protected $fillable = ['created_at', 'updated_at'];

    public function Merchant (){
        return $this->belongsTo(Merchant::class,'merchant_id','id');
    }

    public function account() {
        return $this->belongsTo(Account::class,'account_id','id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
