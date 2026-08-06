<?php

namespace App\Models\Backend\Payroll;

use App\Enums\SalaryStatus;
use App\Enums\Status;
use App\Models\User;
use App\Models\Backend\Salary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryGenerate extends Model
{
    use HasFactory,LogsActivity;

   protected $fillable = [
        'user_id',
        'month',
        'amount',
        'status',
        'due',
        'advance',
        'note'
    ];


    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'user.name',
            'month',
            'amount',
            'due',
            'advance',
            'note',
        ];
        return LogOptions::defaults()
        ->useLogName('Salary Generate')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }


    // Get single row in User table.
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    //for status index blade
    public function getMyStatusAttribute()
    {
        if($this->status == SalaryStatus::PAID){
            $status = '<span class="badge badge-pill badge-success">'.trans("SalaryStatus." . $this->status).'</span>';
        }elseif($this->status == SalaryStatus::UNPAID){
            $status = '<span class="badge badge-pill badge-danger">'.trans("SalaryStatus." . $this->status).'</span>';
        }elseif($this->status == SalaryStatus::PARTIAL_PAID){
            $status = '<span class="badge badge-pill badge-success">'.trans("SalaryStatus." . $this->status).'</span>';
        }
        return $status;
    }

    public function salary()
    {
        return $this->hasMany(Salary::class,'user_id','id');
    }
    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
