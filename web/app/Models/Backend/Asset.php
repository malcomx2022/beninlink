<?php

namespace App\Models\Backend;
use App\Models\Backend\Hub;
use App\Models\Backend\Assetcategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'author',
        'name',
        'assetcategory_id',
        'hub_id',
        'supplyer_name',
        'quantity',
        'warranty',
        'invoice_no',
        'amount',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'user.name',
            'name',
            'assetcategorys.title',
            'hubs.name',
            'supplyer_name',
            'quantity',
            'warranty',
            'invoice_no',
            'amount',
            'description',
        ];
        return LogOptions::defaults()
        ->useLogName('Asset')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }


    public function assetcategorys()
    {
        return $this->belongsTo(Assetcategory::class, 'assetcategory_id', 'id');
    }

    public function hubs()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class,'author','id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
