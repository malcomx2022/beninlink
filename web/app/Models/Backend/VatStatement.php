<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatStatement extends Model
{
    use HasFactory;

    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
