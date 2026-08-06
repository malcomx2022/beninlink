<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fraud extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'created_by',
        'phone',
        'name',
        'tracking_id',
        'details',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'createdby.name',
            'phone',
            'name',
            'tracking_id',
            'details',
        ];
        return LogOptions::defaults()
        ->useLogName('Fraud')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function createdby(){
        return $this->belongsTo(User::class,'created_by','id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
