<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Délai de livraison (**D4**) : jour même, lendemain, standard.
 *
 * Le métier a choisi un **délai global** : la même liste partout, et un
 * supplément par délai qui ne dépend pas de la zone. C'est ce qui évite de
 * revenir au mélange d'origine — quatre colonnes pour deux axes.
 */
class DeliveryDelay extends Model
{
    use HasFactory;

    public const SAME_DAY = 'same_day';
    public const NEXT_DAY = 'next_day';
    public const STANDARD = 'standard';

    protected $fillable = ['company_id', 'code', 'name', 'surcharge', 'position', 'status'];

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }
}
