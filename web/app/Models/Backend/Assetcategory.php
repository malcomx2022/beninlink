<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Assetcategory extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'title',
        'position',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'title',
            'position',
        ];
        return LogOptions::defaults()
        ->useLogName('Asset Category')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
