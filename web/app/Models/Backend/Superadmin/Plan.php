<?php

namespace App\Models\Backend\Superadmin;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $casts    = ['modules'=>'array'];
    
    public function getMyStatusAttribute()
    {
        if($this->status == Status::ACTIVE){
            $status = '<span class="badge badge-pill badge-success">'.trans("status." . $this->status).'</span>';
        }else {
            $status = '<span class="badge badge-pill badge-danger">'.trans("status." . $this->status).'</span>';
        }
        return $status;
    }


    public function getIntvalNameAttribute(){
        $days = $this->days_count;
        $name = $days.' Days';
        if($days >= 365):
            $year = ($days / 365); 
            $name = (int)$year.' Years'; 
        elseif($days >= 30):
            $month = $days/30; 
            $name =  (int) $month.' Month';
        endif;
        return $name;
    }
    
}
