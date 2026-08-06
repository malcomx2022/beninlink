<?php

namespace App\Models\Backend;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;

class Account extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['account_holder_name','account_no','gateway'];

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Account')
        ->logOnly(['account_holder_name','account_no', 'gateway'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get single row in User table.
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getMyStatusAttribute(){
        if($this->status == Status::ACTIVE):
            $status = '<span class="badge badge-pill badge-success">'.trans('status.' . $this->status).'</span>';
        else:
            $status = '<span class="badge badge-pill badge-danger">'.trans('status.' . $this->status).'</span>';
        endif;
        return $status;
    }
    public function getAccountTypesAttribute(){
        $type = '';
        if($this->account_type == '1'):
            $type   =  'Merchant';
        elseif($this->account_type == '2'):
            $type   =  'Personal';
        elseif($this->gateway == '2'):
            $type   =  'Bank';
        elseif($this->gateway == '1'):
                $type   =  'Cash';
        endif;
        return $type;
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
