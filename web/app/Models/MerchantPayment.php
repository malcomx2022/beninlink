<?php

namespace App\Models;

use App\Models\Backend\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MerchantPayment extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [

        'merchant_id',
        'payment_method',
        'bank_name',
        'holder_name',
        'account_no',
        'branch_name',
        'routing_no',
        'mobile_company',
        'mobile_no',
        'account_type',

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'merchant.business_name',
            'payment_method',
            'bank_name',
            'holder_name',
            'account_no',
            'branch_name',
            'routing_no',
            'mobile_company',
            'mobile_no',
            'account_type',
        ];

        return LogOptions::defaults()
        ->useLogName('MerchantPayment')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }
    // Merchant details
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }


}
