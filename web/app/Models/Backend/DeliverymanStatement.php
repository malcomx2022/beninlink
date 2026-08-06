<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverymanStatement extends Model
{
    use HasFactory;
    public function parcel(){
        return $this->belongsTo(Parcel::class,'parcel_id','id');
    }
    public function deliveryman(){
        return $this->belongsTo(Deliveryman::class,'delivery_man_id','id');
    }
   
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
}
