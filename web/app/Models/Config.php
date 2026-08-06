<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Config extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'company_id',
        'key',
        'value'

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'key',
            'value'
        ];
        return LogOptions::defaults()
        ->useLogName('Config')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
