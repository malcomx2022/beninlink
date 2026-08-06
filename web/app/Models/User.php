<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Backend\Upload;
use App\Models\Backend\Hub;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Enums\Status;
use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Role;
use App\Models\Backend\Salary;
use App\Models\Backend\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'hub_id',
        'image_id',
        'facebook_id',
        'google_id',
        'user_type'

    ];


    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('User')
        ->logOnly(['name', 'email'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at'    => 'datetime',
        'permissions'          => 'array', 
    ];

    // Get single row in Hub table.
    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    // Get single row in Upload table.
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'image_id', 'id');
    }

    // Get single row in Department table.
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    // Get single row in Designation table.
    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
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

    public function merchant(){
        return $this->belongsTo(Merchant::class,'id','user_id');
    }

    public function role(){
        return $this->belongsTo(Role::class,'role_id','id');
    }

    public function deliveryman(){
        return $this->belongsTo(DeliveryMan::class,'id','user_id');
    }

    public function salary()
    {
        return $this->hasMany(Salary::class,'user_id','id');
    }

    public function accounts(){
        return $this->hasMany(Account::class,'user_id','id');
    }

    public function company(){
        return $this->belongsTo(GeneralSettings::class,'company_id','id');
    }

    public function getSubscriptionAttribute(){
        return Subscription::where('company_id',$this->company_id)->orderBy('id','desc')->first();
    }
    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

    public function tenantDetails(){
        return $this->belongsTo(Tenant::class,'company_id','company_id')->with('domains');
    }


}
