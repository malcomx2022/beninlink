<?php

namespace App\Models\Backend;

use App\Enums\CustomsLevel;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Regle douaniere : pour un pays de destination et une categorie de
 * marchandise, le niveau d'alerte et le document attendu.
 *
 * Voir la migration pour le role de chaque colonne, et CustomsRuleSeeder pour
 * l'origine du referentiel livre.
 */
class CustomsRule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'level' => 'integer',
        'status' => 'integer',
    ];

    public function scopeCompanywise($query){
        return $query->where('company_id', settings()->id);
    }

    public function scopeActive($query){
        return $query->where('status', Status::ACTIVE);
    }

    /** Libelle traduit du niveau, pour l'API et les vues. */
    public function getLevelNameAttribute(): string
    {
        return trans('customs.level_' . $this->level);
    }

    public function isBlocking(): bool
    {
        return $this->level == CustomsLevel::BLOCKING;
    }
}
