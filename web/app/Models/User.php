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
    /**
     * S135 — un mot de passe changé ferme les autres accès de l'API : quel que soit le chemin
     * (profil, réinitialisation, fiche modifiée par un agent), les jetons Sanctum du compte sont
     * révoqués, sauf celui de l'appareil qui vient de le changer. Sans cela, un jeton volé
     * survivait au changement de mot de passe qui devait l'éteindre. Les sessions web suivent
     * par `CloseSessionsOnPasswordChange` (groupe `web`).
     */
    /** S137 — durée de validité d'un code SMS (inscription, renvoi). */
    public const OTP_TTL_MINUTES = 10;

    protected static function booted(): void
    {
        // S137 — tout code posé (inscription, renvoi, inscription sociale) reçoit son échéance ;
        // un code effacé l'efface aussi.
        static::saving(function (User $user) {
            if ($user->isDirty('otp')) {
                $user->otp_expires_at = filled($user->otp) ? now()->addMinutes(self::OTP_TTL_MINUTES) : null;
            }
        });

        static::updated(function (User $user) {
            if (! $user->wasChanged('password')) {
                return;
            }
            $courant = request()->user()?->getKey() === $user->getKey()
                ? request()->user()->currentAccessToken()
                : null;
            $user->tokens()
                ->when($courant instanceof \Laravel\Sanctum\PersonalAccessToken, fn ($q) => $q->whereKeyNot($courant->getKey()))
                ->delete();

            // S139 — le cookie « se souvenir de moi » d'un autre navigateur rouvrait une session sans mot de
            // passe, après que la sienne avait expiré : le jeton de rappel change avec le mot de passe.
            if (filled($user->getRememberToken())) {
                $user->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->saveQuietly();
            }

            // La session web qui change son propre mot de passe reste ouverte : `CloseSessionsOnPasswordChange`
            // range l'empreinte du compte connecté, souvent une autre instance que celle écrite ici.
            $web = auth()->guard('web');
            if ($web->hasUser() && $web->id() === $user->getKey() && $web->user() !== $user) {
                $web->user()->setAttribute('password', $user->password)->syncOriginalAttribute('password');
            }
        });
    }

    /**
     * **S143** — le courriel de réinitialisation part en file (D13) : son lien, sa langue et sa marque sont figés
     * ici, dans la requête. Le lien vise le site où la demande a été faite ; la marque est celle de la société
     * **du compte** (F4 : par l'API, `settings()` n'aurait ni site ni session et retomberait sur la société 1).
     */
    public function sendPasswordResetNotification($token)
    {
        $lien = url(route('password.reset', ['token' => $token, 'email' => $this->getEmailForPasswordReset()], false));
        $marque = GeneralSettings::query()->whereKey($this->company_id)->value('name') ?: config('app.name');

        $this->notify((new \App\Notifications\ResetPasswordNotification($token, $lien, $marque))->locale(app()->getLocale()));
    }

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
        'otp_expires_at'       => 'datetime',
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

    /** Appareils de l'utilisateur abonnés aux notifications poussées. */
    public function deviceTokens(){
        return $this->hasMany(\App\Models\Backend\DeviceToken::class,'user_id','id');
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
