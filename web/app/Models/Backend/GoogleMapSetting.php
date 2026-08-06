<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GoogleMapSetting extends Model
{
    use HasFactory,LogsActivity;
    protected $fillable = ['map_key'];
    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = ['map_key'];
        return LogOptions::defaults()
        ->useLogName('Google map Settings')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
