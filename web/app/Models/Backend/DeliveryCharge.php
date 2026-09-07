<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Status;
use App\Models\Backend\Deliverycategory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeliveryCharge extends Model
{
    use HasFactory,LogsActivity;


        protected $fillable = [
                'category_id',
                // D4 — le barème par zone : une ligne = (catégorie, zone, tranche).
                'zone_id',
                'amount',
                'weight',
                'position',
            ];

        public function getActivitylogOptions(): LogOptions
        {

            $logAttributes = [
                'category.name',
                'weight',
                'position',
            ];
            return LogOptions::defaults()
            ->useLogName('DeliveryCharge')
            ->logOnly($logAttributes)
                ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
        }


    /** La zone de la ligne (D4) — une ligne de barème = catégorie × zone × tranche. */
    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
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

     // Get single row in Delivery Category table.
     public function category()
     {
         return $this->belongsTo(Deliverycategory::class, 'category_id', 'id');
     }
     
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
}
