<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Categorys extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'name',
            'slug',
            'description',
        ];
        return LogOptions::defaults()
        ->useLogName('Categories')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }


}
