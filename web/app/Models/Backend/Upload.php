<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Upload extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['original','one','two','three'];

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Upload')
        ->logOnly(['original'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }
}
