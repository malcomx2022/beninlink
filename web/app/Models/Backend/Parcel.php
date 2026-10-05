<?php

namespace App\Models\Backend;


use App\Models\MerchantShops;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\Packaging;
use App\Enums\ParcelStatus;
use App\Services\Parcel\ParcelStage;
use App\Enums\DeliveryType;
use App\Models\Backend\Merchantpanel\Invoice;
use DNS1D;
use DNS2D;
use Illuminate\Support\Facades\Auth;

class Parcel extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        // `company_id` manquait : l'import Excel, seul appelant de
        // `Parcel::create()`, creait donc des colis sans societe, invisibles a
        // tous les ecrans `companywise()`. Les repositories, eux, affectent la
        // propriete directement et n'etaient pas touches.
        'company_id',
        // D4, étape 6 : la route du colis. Sans elle dans le `fillable`,
        // l'import Excel créait des colis sans zone — donc, désormais, sans
        // tarif retrouvable.
        'zone_id', 'delay_id',
        'merchant_id', 'merchant_shop_id', 'pickup_address', 'pickup_phone', 'customer_name', 'customer_phone',
        'customer_address', 'invoice_no', 'category_id', 'weight', 'delivery_type_id', 'pickup_date', 'delivery_date', 'packaging_id','cash_collection','first_hub_id','hub_id',
        'selling_price','liquid_fragile_amount','packaging_amount','delivery_charge','cod_charge','cod_amount',
        'vat','vat_amount','total_delivery_amount','current_payable','note','tracking_id','status','created_at','updated_at','pickup_lat','pickup_long','customer_lat','customer_long'
    ];

    protected $table = 'parcels';
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('parcel')
        ->logOnly(['merchant.business_name','pickup_address','pickup_phone','customer_name','customer_phone','customer_address','invoice_no','cash_collection','selling_price','delivery_charge','total_delivery_amount','current_payable'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Merchant details
    public function merchant()
    {
        return $this->belongsTo(Merchant::class)->with('user');
    }

    // Merchant shop details
    public function merchantShop()
    {
        return $this->belongsTo(MerchantShops::class, 'merchant_shop_id', 'id');
    }

    // Delivery Category details
    /**
     * Zone et delai du colis (**D4**, etape 5 bis).
     *
     * Nuls sur un colis herite : il n'a que son `delivery_type_id`, et c'est
     * par lui qu'il a ete facture.
     */
    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id', 'id');
    }

    public function delay()
    {
        return $this->belongsTo(DeliveryDelay::class, 'delay_id', 'id');
    }

    /**
     * Alertes douanieres emises a la creation de ce colis (chantier 5, **S82 / M1**).
     *
     * Un colis domestique n'en a aucune ; un export en porte une par regle
     * appliquee. La portee est celle du colis : qui peut lire le colis peut lire
     * ses alertes, et personne d'autre.
     */
    public function customsAlerts()
    {
        return $this->hasMany(CustomsAlert::class, 'parcel_id', 'id');
    }

    public function deliveryCategory()
    {
        return $this->belongsTo(Deliverycategory::class, 'category_id', 'id');
    }

    // Delivery Category details
    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
    public function shop()
    {
        return $this->belongsTo(MerchantShops::class,'merchant_shop_id','id');
    }
    public function parcelEvent()
    {
        return $this->hasMany(ParcelEvent::class,'parcel_id','id');
    }

    public function deliverymanStatement()
    {
        return $this->hasMany(DeliverymanStatement::class,'parcel_id','id');
    }

    public function getMyItemTypeAttribute()
    {
        $itemType = '';
        foreach (trans("parcelType") as $key =>$value){
            if($this->item_type == $key){
                $itemType = $value;
            }
        }
        return $itemType;
    }

    public function getMyDeliveryTypeAttribute()
    {
        $deliveryType = '';
        foreach (trans("DeliveryType") as $key =>$value){
            if($this->delivery_type == $key){
                $deliveryType = $value;
            }
        }
        return $deliveryType;
    }



    /**
     * Pastille de statut, consommée par `{!! $parcel->parcel_status !!}` dans
     * **six vues vivantes** du back-office — liste des colis, vue d'un hub,
     * banque de colis, et les deux écrans de détail de facture, côté
     * transporteur comme côté marchand. C'est le badge principal du produit ;
     * il portait la même table fautive que `StatusParcel()` (voir le
     * commentaire là-bas). Il délègue.
     *
     * ⚠️ Ne pas confondre avec `parcel_events.parcel_status`, une COLONNE qui
     * porte le code brut d'une ligne de journal : c'est elle que lisent les
     * chronologies de suivi (`parcel/logs`, et la page publique), et elle ne
     * passe pas par cet accesseur.
     */
    public function getParcelStatusAttribute()
    {
        $pastille = ParcelStage::pill($this->status);

        // Sur un transfert entre hubs, le socle ajoutait la route parcourue.
        // L'information est utile à l'opérateur, donc elle reste — mais elle
        // était peinte en ROUGE, et un transfert n'est pas un incident ; et le
        // « To » anglais traînait dans une interface française.
        // Le socle lisait aussi `$this->hub->name` sans garde : un colis dont le
        // hub a été supprimé faisait tomber la page.
        if ((int) $this->status === ParcelStatus::TRANSFER_TO_HUB && $this->hub && $this->transferhub) {
            $pastille .= '<br><span class="bl-pill bl-pill--hub mt-1">'
                . e($this->hub->name) . ' → ' . e($this->transferhub->name)
                . '</span>';
        }

        return $pastille;
    }

    /**
     * Troisième copie de la même table dans le socle — et **morte** : aucune vue
     * ne lit `$parcel->status_parcel`, et la colonne n'existe pas. Un accesseur
     * Eloquent reçoit la valeur de sa colonne, pas un code de statut : appelé, il
     * recevait `null` et retombait sur « Undefined variable ».
     *
     * Conservée (rien n'est supprimé du socle) mais ramenée à une délégation :
     * la table fautive disparaît, la signature reste.
     */
    public function getStatusParcelAttribute($status_id)
    {
        return ParcelStage::pill($status_id);
    }

    public function getDeliveryTypeNameAttribute()
    {
        if($this->delivery_type_id == DeliveryType::SAMEDAY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        elseif($this->delivery_type_id == DeliveryType::NEXTDAY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        elseif($this->delivery_type_id == DeliveryType::SUBCITY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        elseif($this->delivery_type_id == DeliveryType::OUTSIDECITY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        return $delivery_type_id;
    }


    public function hub(){
        return $this->belongsTo(Hub::class,'hub_id','id');
    }
    public function transferhub(){
        return $this->belongsTo(Hub::class,'transfer_hub_id','id');
    }

    public function getBarcodePrintAttribute()
    {
        return DNS1D::getBarcodeHTML($this->tracking_id, 'C128',2,25); 
    }

    public function getQrcodePrintAttribute()
    {
        return 'data:image/png;base64,' .DNS2D::getBarcodePNG(url('/',$this->tracking_id), 'QRCODE',10,10,array(1,1,1),false);
    }

    public function getStatusNameAttribute(){
        return __('parcelStatus.'.$this->status);
    }

    public function getParcelInvoiceAttribute(){
        $invoice   = Invoice::where('merchant_id',Auth::user()->merchant->id)->get();
        $inv  = null;
        foreach ($invoice as $in) {
            if(in_array($this->id,$in->parcels_id) == true):
                $inv  = $in;
            endif;
        }
        return $inv;
    }

    public function getAdminParcelInvoiceAttribute(){
        $invoice   = Invoice::where('merchant_id',$this->merchant_id)->get();
        $inv  = null;
        foreach ($invoice as $in) {
            if(in_array($this->id,$in->parcels_id) == true):
                $inv  = $in;
            endif;
        }
        return $inv;
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
}
