<?php

namespace App\Models\Backend;

use App\Enums\AccountHeads;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory;


    public function getMyTypeAttribute()
    {
        if($this->type      == AccountHeads::INCOME){
            $type =  __('AccountHeads.'.AccountHeads::INCOME) ;
        }elseif($this->type == AccountHeads::EXPENSE){
            $type = __('AccountHeads.'.AccountHeads::EXPENSE);
        }
        return $type;
    }
    public function getMyStatusAttribute()
    {
        if($this->status == Status::ACTIVE){
            $status = '<span class="badge badge-pill badge-success">'.trans("status." . $this->status).'</span>';
        }else {
            $status = '<span class="badge badge-pill badge-danger">'.trans("status." . $this->status).'</span>';
        }
        return $status;
    }

}
