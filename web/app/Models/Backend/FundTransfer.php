<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Backend\Account;

class FundTransfer extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['from_account','to_account'];

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
        ->useLogName('FundTransfer')
        ->logOnly(['fromAccount.account_no', 'toAccount.account_no'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get single row in User table.
    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account', 'id');
    }
    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account', 'id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
