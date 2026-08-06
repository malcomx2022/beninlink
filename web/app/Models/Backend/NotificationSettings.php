<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationSettings extends Model
{
    use HasFactory,LogsActivity;
    protected $fillable = ['fcm_secret_key', 'fcm_topic'];
    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = ['fcm_secret_key', 'fcm_topic'];
        return LogOptions::defaults()
        ->useLogName('Notification Settings')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
