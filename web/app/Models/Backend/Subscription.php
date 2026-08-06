<?php

namespace App\Models\Backend;

use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
 
    public function company(){
        return $this->belongsTo(GeneralSettings::class,'company_id','id');
    }

    public function plan(){
        return $this->belongsTo(Plan::class,'plan_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
   
}
