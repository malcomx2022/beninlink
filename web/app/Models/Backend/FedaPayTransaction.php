<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

/**
 * Transaction FedaPay. Voir la migration pour le rôle de chaque colonne.
 *
 * Statuts alignés sur les événements FedaPay (`transaction.approved`, etc.)
 * plutôt que sur une énumération maison : cela évite une table de correspondance
 * de plus entre le fournisseur et nous.
 */
class FedaPayTransaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELED = 'canceled';

    public const PURPOSE_WALLET = 'wallet_recharge';
    public const PURPOSE_SUBSCRIPTION = 'subscription';

    /**
     * Nom de table fixé explicitement. Laravel déduirait `feda_pay_transactions`
     * du nom de classe — il coupe entre « Feda » et « Pay » — alors que la table
     * créée par la migration est `fedapay_transactions`.
     */
    protected $table = 'fedapay_transactions';

    protected $guarded = ['id'];

    protected $casts = [
        'last_event' => 'array',
        'approved_at' => 'datetime',
        'amount' => 'integer',
    ];

    /**
     * Scope tenant, comme le reste du socle. ⚠️ À ne PAS utiliser dans le
     * webhook : celui-ci n'a pas de session, `settings()` y retomberait sur la
     * société 1. Le webhook retrouve sa ligne par `provider_transaction_id`.
     */
    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
