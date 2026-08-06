<?php

namespace App\Models\Backend;

use App\Models\User;
use App\Models\Backend\Payroll\SalaryGenerate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Salary extends Model
{
    use HasFactory,LogsActivity;

    protected  $fillable = [
        'user_id',
        'month',
        'account',
        'amount',
        'date',
        'note',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'user.name',
            'month',
            'account',
            'amount',
            'date',
            'note',
        ];
        return LogOptions::defaults()
        ->useLogName('Salary')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }


    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function account(){
        return $this->belongsTo(Account::class,'account_id','id');
    }

    public function getSalary(){
        return $this->hasMany(SalaryGenerate::class,'user_id','user_id');
    }

    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    


}
