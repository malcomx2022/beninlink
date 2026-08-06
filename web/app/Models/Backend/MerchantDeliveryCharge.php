<?php

namespace App\Models\Backend;

use App\Enums\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MerchantDeliveryCharge extends Model
{
    use HasFactory,LogsActivity;

    protected $table = 'merchant_delivery_charges';
    protected $fillable = ['merchant_id','status','delivery_charge_id','weight','same_day','next_day','sub_city','outside_city'];


    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'merchant.business_name',
            'deliveryCharge.category.title',
            'weight',
            'same_day',
            'next_day',
            'sub_city',
            'outside_city'
        ];
        return LogOptions::defaults()
        ->useLogName('MerchantDeliveryCharge')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }



    // Get active row this model.
    public function scopeActive($query)
    {
        $query->where('status', Status::ACTIVE);
    }


    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function deliveryCharge()
    {
        return $this->belongsTo(DeliveryCharge::class, 'delivery_charge_id', 'id');
    }

    public function getMyStatusAttribute()
    {
        if($this->status == Status::ACTIVE){
            $status = '<span class="badge badge-pill badge-success">'.trans("status." . $this->status).'</span>';
        }else {
            $status = '<span class="badge badge-pill badge-danger">'.trans("status." . $this->status).'</span>';
        }
        return $status;
    }

      
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
