<?php

namespace App\Models;

use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CashReceivedFromDeliveryman extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'user_id',
        'hub_id',
        'account_id',
        'delivery_man_id',
        'amount',
        'date',
        'note',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'user.name',
            'user.hub.name',
            'account.account_no',
            'deliveryman.user.name',
            'amount',
            'date',
            'note',
        ];
        return LogOptions::defaults()
        ->useLogName('CashReceivedFromDeliveryman')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }


    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function deliveryman(){
        return $this->belongsTo(DeliveryMan::class,'delivery_man_id','id');
    }

    // Get single row in Upload table.
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'receipt', 'id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
