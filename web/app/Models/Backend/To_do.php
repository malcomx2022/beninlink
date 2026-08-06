<?php


namespace App\Models\Backend;
use App\Models\User;
use App\Enums\TodoStatus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class To_do extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'title',
            'description',
            'user.name',
            'date',
        ];
        return LogOptions::defaults()
        ->useLogName('ToDo')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get single row in User table.
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getTodoStatusAttribute()
    {
        if($this->status == TodoStatus::PENDING){
            $status = '<span class="badge badge-pill badge-danger">'.trans("to_do." . $this->status).'</span>';
        }
        elseif($this->status == TodoStatus::PROCESSING) {
            $status = '<span class="badge badge-pill badge-primary">'.trans("to_do." . $this->status).'</span>';
        }
        elseif($this->status == TodoStatus::COMPLETED) {
            $status = '<span class="badge badge-pill badge-info">'.trans("to_do." . $this->status).'</span>';
        }

        return $status;
    }
    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }


}
