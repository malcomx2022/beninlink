<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscribe extends Model
{
    protected $fillable = ['email','company_id'];
    use HasFactory;

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
