<?php

namespace App\Models\Backend;

use App\Enums\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HubPayment extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'hub_id',
        'amount',
        'transaction_id',
        'from_account',
        'reference_file',
        'description',

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'hub.name',
            'amount',
            'transaction_id',
            'fromPayment.account_no',
            'description',
           
        ];
        return LogOptions::defaults()
        ->useLogName('HubPayment')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }


    protected $table = 'hub_payments';

    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    public function hub(){
        return $this->belongsTo(Hub::class,'hub_id','id');
    }

    public function fromPayment(){
        return $this->belongsTo(Account::class,'from_account','id');
    }
    public function referenceFile(){
        return $this->belongsTo(Upload::class,'reference_file','id');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by','id');
    }

       
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
