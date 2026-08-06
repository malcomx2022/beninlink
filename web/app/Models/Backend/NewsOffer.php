<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Status;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity; 
class NewsOffer extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [  
        'title',
        'description',
        'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        
        $logAttributes = [  
            'title', 
            'description',
            'date', 
        ];
        return LogOptions::defaults() 
        ->useLogName('News Offer')
        ->logOnly($logAttributes)  
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }
    
    // Get single row in Upload table.
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'file', 'id');
    }

    public function getImageAttribute()
    {
        if (!empty($this->upload->original['original']) && file_exists(public_path($this->upload->original['original']))) {
            return static_asset($this->upload->original['original']);
        }
        return static_asset('images/default/user.png');
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

    public function user()
    {
        return $this->belongsTo(User::class, 'author', 'id');
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
}
