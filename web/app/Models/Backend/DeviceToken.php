<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un appareil abonné aux notifications poussées.
 *
 * Le jeton est celui du service de push, pas un secret de la plateforme : il
 * identifie un appareil, il n'autorise rien. Il reste néanmoins scopé comme le
 * reste (`companywise()`), pour qu'une société ne puisse pas lister ni purger
 * les appareils d'une autre.
 */
class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'user_id', 'token', 'platform', 'app', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
