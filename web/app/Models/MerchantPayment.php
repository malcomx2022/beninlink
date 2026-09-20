<?php

namespace App\Models;

use App\Models\Backend\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MerchantPayment extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [

        'merchant_id',
        'payment_method',
        'bank_name',
        'holder_name',
        'account_no',
        'branch_name',
        'routing_no',
        'mobile_company',
        'mobile_no',
        'account_type',

    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'merchant.business_name',
            'payment_method',
            'bank_name',
            'holder_name',
            'account_no',
            'branch_name',
            'routing_no',
            'mobile_company',
            'mobile_no',
            'account_type',
        ];

        return LogOptions::defaults()
        ->useLogName('MerchantPayment')
        ->logOnly($logAttributes)
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }
    // Merchant details
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    /**
     * S34 — ce scope **mentait**. La table `merchant_payments` ne porte pas de
     * colonne `company_id` (voir sa migration de 2022) : `where('company_id', …)`
     * y lève une erreur SQL. Personne ne l'appelait, donc personne ne l'avait
     * constaté — et il restait tendu comme un piège pour quiconque viendrait
     * « scoper » ce modèle en croyant qu'un scope existant est correct.
     *
     * Le rattachement d'un compte de versement à une société passe par son
     * **marchand**, seul porteur du `company_id`. Le scope le dit maintenant, et
     * `PaymentRepository` s'en sert.
     */
    public function scopeCompanywise($query){
        return $query->whereHas('merchant', function($query){
            $query->companywise();
        });
    }


}
