<?php

namespace App\Models\Backend;

use App\Enums\Wallet\WalletStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Backend\Merchant;
class Wallet extends Model
{
    use HasFactory;

    
    public function merchant(){
        return $this->belongsTo(Merchant::class, 'merchant_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function getMyStatusAttribute()
    {
        if($this->status == WalletStatus::PENDING){
            $status = '<span class="badge badge-pill badge-warning">'.trans("WalletStatus." . $this->status).'</span>';
        }elseif($this->status == WalletStatus::APPROVED){
            $status = '<span class="badge badge-pill badge-success">'.trans("WalletStatus." . $this->status).'</span>';
        }elseif($this->status == WalletStatus::REJECTED){
            $status = '<span class="badge badge-pill badge-danger">'.trans("WalletStatus." . $this->status).'</span>';
        }
        return $status;
    }

    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
