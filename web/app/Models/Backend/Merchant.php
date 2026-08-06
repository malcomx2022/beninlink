<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;
use App\Models\Backend\Upload;
use App\Enums\Status;
use App\Models\MerchantShops;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PhpParser\Node\Stmt\Static_;

class Merchant extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['title','business_name','current_balance','user_id'];

    protected $casts = [
        "cod_charges"      => 'array',
    ];

    // Get all row. Descending order using scope.
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
        ->useLogName('Merchant')
        ->logOnly(['user.name','business_name','current_balance'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get active row this model.
    public function scopeActive($query)
    {
        $query->where('status', Status::ACTIVE);
    }

    // Get single row in User table.
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Get single row in Upload table.
    public function licensefile()
    {
        return $this->belongsTo(Upload::class, 'trade_license', 'id');
    }
    public function getTradeAttribute()
    {
        if (!empty($this->licensefile->original['original']) && file_exists(public_path($this->licensefile->original['original']))) {
            return static_asset($this->licensefile->original['original']);
        }
        return static_asset('images/default/user.png');
    }

    // Get single row in Upload table.
    public function nidfile()
    {
        return $this->belongsTo(Upload::class, 'nid_id', 'id');
    }
    public function getNidAttribute()
    {
        if (!empty($this->nidfile->original['original']) && file_exists(public_path($this->nidfile->original['original']))) {
            return static_asset($this->nidfile->original['original']);
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
    public function getWalletStatusAttribute()
    {
        if($this->wallet_use_activation == Status::ACTIVE){
            $status = '<span class="badge badge-pill badge-success">'.trans("status." . $this->wallet_use_activation).'</span>';
        }else {
            $status = '<span class="badge badge-pill badge-danger">'.trans("status." . $this->wallet_use_activation).'</span>';
        }
        return $status;
    }

    public function getMyCodChargesAttribute()
    {
        $data = '';
         foreach ($this->cod_charges as $key=>$value){
            $data.=__('merchant.'.$key).'= '.$value.', ';
        }
        return $data;
    }

    public function parcels(){
        return $this->hasMany(Parcel::class,'merchant_id','id');
    }

    public function merchantShops(){
        return $this->hasMany(MerchantShops::class,'merchant_id','id');
    }
    public function getActiveShopAttribute(){
        return MerchantShops::where(['merchant_id'=>$this->id,'default_shop'=>Status::ACTIVE])->first();
    }
    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
