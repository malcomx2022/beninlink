<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Zone de livraison (**D4**) : Cotonou, Périphérie, Intérieur, CEDEAO.
 *
 * Une zone est une **ligne**, plus une colonne : en ajouter une ne demande
 * ni migration ni passage sur les écrans.
 */
class DeliveryZone extends Model
{
    use HasFactory;

    public const COTONOU = 'cotonou';
    public const PERIPHERIE = 'peripherie';
    public const INTERIEUR = 'interieur';
    public const CEDEAO = 'cedeao';

    protected $fillable = ['company_id', 'code', 'name', 'position', 'status'];

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function countries(): HasMany
    {
        return $this->hasMany(DeliveryZoneCountry::class, 'zone_id');
    }

    /** La CEDEAO se tarife au pays, pas au poids. */
    public function isExport(): bool
    {
        return $this->code === self::CEDEAO;
    }
}
