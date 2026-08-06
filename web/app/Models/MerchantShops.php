<?php

namespace App\Models;

use App\Models\Backend\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MerchantShops extends Model
{

    use HasFactory,LogsActivity;

    protected $fillable = [

        'merchant_id',
        'name',
        'contact_no',
        'address',

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'merchant.business_name',
            'name',
            'contact_no',
            'address',
        ];
        return LogOptions::defaults()
        ->useLogName('MerchantShops')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Merchant details
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }


}
