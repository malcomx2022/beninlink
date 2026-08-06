<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Hub extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['name','phone','address'];

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Hub')
        ->logOnly(['name', 'phone', 'address'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function getMyStatusAttribute()
    {
        return trans('status.' . $this->status);
    }

    public function parcels(){
        return $this->hasMany(Parcel::class,'hub_id','id');
    }
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
}
