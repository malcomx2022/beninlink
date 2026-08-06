<?php

namespace App\Models;

use App\Models\Backend\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    use HasFactory;
    public function merchant(){
        return $this->belongsTo(Merchant::class,'merchant_id','id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
