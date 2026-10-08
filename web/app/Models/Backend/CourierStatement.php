<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TranslatesStoredNote;
use Illuminate\Database\Eloquent\Model;

class CourierStatement extends Model
{
    use HasFactory;
    use TranslatesStoredNote;
    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
}
