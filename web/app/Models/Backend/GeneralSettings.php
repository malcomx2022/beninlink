<?php

namespace App\Models\Backend;

use App\Models\Backend\Superadmin\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GeneralSettings extends Model
{
    use HasFactory,LogsActivity;


    protected $fillable = [ 
        'phone',
        'name',
        'tracking_id',
        'details',
        'prefix',
        'purchase_code'
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [

            'phone',
            'name',
            'tracking_id',
            'details',
            'prefix'
        ];
        return LogOptions::defaults()
        ->useLogName('General Settings')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get single row in Upload table.
    public function rxlogo()
    {
        return $this->belongsTo(Upload::class, 'logo', 'id');
    }
    public function lightlogo()
    {
        return $this->belongsTo(Upload::class, 'light_logo', 'id');
    }
    public function rxfavicon()
    {
        return $this->belongsTo(Upload::class, 'favicon', 'id');
    }

    public function getLogoImageAttribute()
    {
        if (!empty($this->rxlogo->original['original']) && file_exists(public_path($this->rxlogo->original['original']))) {
            return static_asset($this->rxlogo->original['original']);
        }
        return static_asset('images/default/logo.png');
    }

    public function getPLogoImageAttribute()
    {
        if (!empty($this->rxlogo->original['original']) && file_exists(public_path($this->rxlogo->original['original']))) {
            return public_path($this->rxlogo->original['original']);
        }
        return public_path('images/default/logo.png');
    }

    public function getLightLogoImageAttribute()
    {
        if (!empty($this->lightlogo->original['original']) && file_exists(public_path($this->lightlogo->original['original']))) {
            return static_asset($this->lightlogo->original['original']);
        }
        return static_asset('images/default/light-logo.png');
    }

    public function getFaviconImageAttribute()
    {
        if (!empty($this->rxfavicon->original['original']) && file_exists(public_path($this->rxfavicon->original['original']))) {
            return static_asset($this->rxfavicon->original['original']);
        }
        return static_asset('images/default/favicon.png');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by','id');
    }

    public function excenseRate(){
        return $this->belongsTo(Currency::class,'currency','symbol');
    }

    public function plan(){
        return $this->belongsTo(Plan::class,'plan_id','id');
    }

    public function subscription(){
        return $this->belongsTo(Subscription::class,'subscription_id','id');
    }
 
}
