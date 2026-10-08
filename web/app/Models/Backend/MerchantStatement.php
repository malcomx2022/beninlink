<?php

namespace App\Models\backend;

use App\Models\Backend\Parcel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TranslatesStoredNote;
use Illuminate\Database\Eloquent\Model;

class MerchantStatement extends Model
{
    use HasFactory;
    use TranslatesStoredNote;
    public function parcel(){
        return $this->belongsTo(Parcel::class,'parcel_id','id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
