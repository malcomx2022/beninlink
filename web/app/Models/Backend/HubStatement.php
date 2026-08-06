<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HubStatement extends Model
{
    use HasFactory;

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

    
}
