<?php

namespace App\Models\Backend;

use App\Enums\Wallet\WalletStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Backend\Merchant;
class Wallet extends Model
{
    use HasFactory;

    /** Origine d'une recharge approuvée par le transporteur, écrite telle quelle en base. */
    public const SOURCE_RECHARGE = 'Wallet Recharge';

    /**
     * L'origine du mouvement, dans la langue du lecteur (**S123**).
     *
     * `source` reste écrit tel que le socle l'écrit : c'est la clé qui relie un débit à son colis
     * (`WalletDebit::SOURCE` + numéro de suivi, lue par `beninlink:colis-non-debites`). On traduit à
     * l'affichage, jamais en base — même règle que les notes de relevé (S118).
     */
    public function getSourceLabelAttribute(): ?string
    {
        $source = $this->source;
        if ($source === self::SOURCE_RECHARGE) {
            return __('wallet.source_recharge');
        }
        if (is_string($source) && str_starts_with($source, \App\Services\Parcel\WalletDebit::SOURCE)) {
            return __('wallet.source_parcel_charge', ['tracking' => substr($source, strlen(\App\Services\Parcel\WalletDebit::SOURCE))]);
        }

        return $source;
    }

    
    public function merchant(){
        return $this->belongsTo(Merchant::class, 'merchant_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function getMyStatusAttribute()
    {
        if($this->status == WalletStatus::PENDING){
            $status = '<span class="badge badge-pill badge-warning">'.trans("WalletStatus." . $this->status).'</span>';
        }elseif($this->status == WalletStatus::APPROVED){
            $status = '<span class="badge badge-pill badge-success">'.trans("WalletStatus." . $this->status).'</span>';
        }elseif($this->status == WalletStatus::REJECTED){
            $status = '<span class="badge badge-pill badge-danger">'.trans("WalletStatus." . $this->status).'</span>';
        }
        return $status;
    }

    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
}
