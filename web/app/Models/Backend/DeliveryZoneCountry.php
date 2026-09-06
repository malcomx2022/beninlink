<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pays d'une zone d'export, et son **forfait** (**D4**).
 *
 * Décision du métier : un envoi CEDEAO se facture au pays — Togo, Nigeria,
 * Burkina — et non au poids. Le forfait vit donc ici, pas dans le barème.
 */
class DeliveryZoneCountry extends Model
{
    use HasFactory;

    protected $fillable = ['zone_id', 'code', 'name', 'flat_amount', 'status'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }
}
