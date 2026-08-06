<?php

namespace App\Models\Backend;

use App\Enums\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PushNotification extends Model
{
    use HasFactory,LogsActivity;

    protected $table       = 'push_notifications';
    use HasFactory,LogsActivity;
    protected $fillable = ['title', 'description'];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = ['title', 'description'];
        return LogOptions::defaults()
            ->useLogName('Push Notification')
            ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get single row in Upload table.
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'image_id', 'id');
    }

    public function getImageAttribute()
    {
        if (!empty($this->upload->original['original']) && file_exists(public_path($this->upload->original['original']))) {
            return static_asset($this->upload->original['original']);
        }
        return static_asset('images/default/logo.png');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }


}
