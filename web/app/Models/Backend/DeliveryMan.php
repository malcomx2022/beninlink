<?php

namespace App\Models\Backend;

use App\Enums\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DeliveryMan extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'delivery_man';
    protected $fillable = ['company_id','user_id','status','delivery_charge','pickup_charge','return_charge','opening_balance','current_balance'];


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
        ->useLogName('DeliveryMan')
        ->logOnly(['user.name', 'current_balance',])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get active row this model.
    public function scopeActive($query)
    {
        $query->where('status', Status::ACTIVE);
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

    public function getDrivingLicenseImageAttribute()
    {
        if (!empty($this->uploadLicense->original['original']) && file_exists(public_path($this->uploadLicense->original['original']))) {
            return static_asset($this->uploadLicense->original['original']);
        }
        return static_asset('images/default/user.png');
    }

    public function uploadLicense()
    {
        return $this->belongsTo(Upload::class, 'driving_license_image_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
