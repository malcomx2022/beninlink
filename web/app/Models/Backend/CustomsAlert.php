<?php

namespace App\Models\Backend;

use App\Enums\CustomsAlertStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Alerte douaniere emise a la creation d'un colis d'export.
 *
 * Les colonnes de la regle (pays, categorie, niveau, document, message) sont
 * RECOPIEES ici volontairement : l'alerte doit continuer d'afficher ce qui a ete
 * annonce au marchand, meme si la regle est modifiee ou supprimee ensuite.
 */
class CustomsAlert extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'level' => 'integer',
        'status' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function scopeCompanywise($query){
        return $query->where('company_id', settings()->id);
    }

    public function parcel(){
        return $this->belongsTo(Parcel::class, 'parcel_id', 'id');
    }

    public function merchant(){
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function rule(){
        return $this->belongsTo(CustomsRule::class, 'customs_rule_id', 'id');
    }

    public function getLevelNameAttribute(): string
    {
        return trans('customs.level_' . $this->level);
    }

    public function getStatusNameAttribute(): string
    {
        return $this->status == CustomsAlertStatus::RESOLVED
            ? trans('customs.resolved')
            : trans('customs.pending');
    }
}
