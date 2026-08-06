<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantSetting extends Model
{
    use HasFactory;
    protected $fillable = ['key','value'];
    // public function scopeCompanywise($query){
    //     return $query->where('company_id',settings()->id);
    // }
}
