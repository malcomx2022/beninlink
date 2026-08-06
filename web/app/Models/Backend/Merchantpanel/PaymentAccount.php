<?php

namespace App\Models\Backend\Merchantpanel;

use App\Models\Backend\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class PaymentAccount extends Model
{
    use HasFactory;

    /**
     * Activity Log
     */
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
        'status',
    ];

    public function getActivitylogOptions($LogAttributes): LogOptions
    {

        $LogAttributes = [
            'merchant.business_name',
            'payment_method',
            'bank_name',
            'holder_name',
            'account_no',
            'branch_name',
            'routing_no',
            'mobile_company',
            'mobile_no',
            'account_type'
        ];
        return LogOptions::defaults()
        ->useLogName('Payment Account')
        ->logOnly($LogAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    public function merchant(){
        return $this->belongsTo(Merchant::class,'merchant_id','id');
    }


}
