<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SmsSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['company_id','key','value'];
    protected $table = 'sms_settings';

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('smsSettings')
        ->logOnly(['key', 'value'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
