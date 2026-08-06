<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Backend\Upload;
use App\Models\Backend\Account;
use App\Models\Backend\Parcel;
use App\Models\User;

class Expense extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [

        'merchant_id',
        'delivery_man_id',
        'parcel_id',
        'account_id',
        'amount',
        'date',
        'receipt',
        'note',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Expense')
        ->logOnly([

            'merchant.business_name',
            'parcel.tracking_id',
            'account.account_no',
            'amount',
            'date',
            'receipt',
            'note',
        ])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    // Get single row in Upload table.
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'receipt', 'id');
    }

    public function getImageAttribute()
    {
        if (!empty($this->upload->original['original']) && file_exists(public_path($this->upload->original['original']))) {
            return static_asset($this->upload->original['original']);
        }
        return static_asset('images/default/user.png');
    }

    public function merchant(){
        return $this->belongsTo(Merchant::class,'merchant_id','id');
    }

    public function deliveryman(){
        return $this->belongsTo(DeliveryMan::class,'delivery_man_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id', 'id');
    }
 
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
