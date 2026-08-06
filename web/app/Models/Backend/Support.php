<?php

namespace App\Models\Backend;

use App\Enums\SupportStatus;
use App\Models\User;
use App\Models\Backend\Upload;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Support extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'user_id',
        'department_id',
        'service',
        'priority',
        'subject',
        'description',
        'date',

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [  
            'user.name',
            'department.title',
            'service',
            'priority',
            'subject',
            'description',
            'date',

        ];
        return LogOptions::defaults() 
        ->useLogName('Support')
        ->logOnly($logAttributes)  
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }
 
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function file ()
    {
        return $this->belongsTo(Upload::class, 'attached_file', 'id');
    }
    // Get single row in Upload table.
    public function attached_file ()
    {
        return $this->belongsTo(Upload::class, 'attached_file', 'id');
    }
    public function getAttachedAttribute()
    {
        if (!empty($this->attached_file->original['original']) && file_exists(public_path($this->attached_file->original['original']))) {
            return static_asset($this->attached_file->original['original']);
        }
        return static_asset('images/default/user.png');
    }

    public function supportChats(){
        return $this->hasMany(SupportChat::class,'support_id','id');
    }

    public function getMyStatusAttribute(){
        $status ='';
        if(SupportStatus::PENDING        == $this->status):
            $status   = '<span class="badge badge-primary">'.__('levels.pending').'</span>';
        elseif(SupportStatus::PROCESSING == $this->status):
                $status   = '<span class="badge badge-warning">'.__('levels.processing').'</span>';
            elseif(SupportStatus::RESOLVED   == $this->status):
                $status   = '<span class="badge badge-success">'.__('levels.resolved').'</span>';
            elseif(SupportStatus::CLOSED     == $this->status):
                $status   = '<span class="badge badge-danger">'.__('levels.closed').'</span>';
        endif;
        return $status;
    } 

  

}
