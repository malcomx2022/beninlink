<?php
namespace App\Repositories\Parcel;

use App\Enums\ApprovalStatus;
use App\Enums\BooleanStatus;
use App\Enums\ParcelStatus;
use App\Enums\DeliveryType;
use App\Enums\DeliveryTime;
use App\Enums\SmsSendStatus;
use App\Enums\StatementType;
use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Http\Resources\MerchantParcelExportResource;
use App\Http\Services\PushNotificationService;
use App\Http\Services\SmsService;
use App\Services\Sms\SmsTemplate;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Packaging;
use App\Models\Backend\DeliveryDelay;
use App\Models\MerchantShops;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\VatStatement;
use App\Repositories\Parcel\ParcelInterface;

use App\Models\Backend\ParcelLogs;
use App\Models\Backend\Setting;
use App\Models\Config;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Parcel\PartialDeliveryRequest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class ParcelRepository implements ParcelInterface {

    protected $walletRepo;
    public function __construct(WalletInterface $walletRepo)
    {
        $this->walletRepo    = $walletRepo;
    }

    public function all(){
        $userHubID = auth()->user()->hub_id;

        if(!blank($userHubID)){
            return Parcel::companywise()->where('company_id',settings()->id)->with('parcelEvent')->where('hub_id',$userHubID)->orderBy('priority_type_id')->orderBy('id','desc')->paginate(10);
        }else{
           return Parcel::companywise()->with('parcelEvent')->orderBy('priority_type_id')->orderBy('id','desc')->paginate(10);
          
        }

    }

    /**
     * Colis confiés au livreur connecté (livraison ou ramassage), et eux seuls.
     */
    private function deliveryManParcels(){
        $deliverymanId = auth()->user()->deliveryman?->id;

        return Parcel::companywise()->whereHas('parcelEvent', function ($queryParcelEvent) use ($deliverymanId) {
            $queryParcelEvent->where(function ($q) use ($deliverymanId) {
                $q->where('delivery_man_id', $deliverymanId)
                  ->orWhere('pickup_man_id', $deliverymanId);
            });
        });
    }

    public function deliveryManParcel(){

        return $this->deliveryManParcels()->orderBy('updated_at')->orderBy('priority_type_id')->get();
    }

    /**
     * S7 — un livreur n'agit que sur les colis qui lui sont confiés. Le socle
     * laissait `details`, `delivered` et `partial-delivered` travailler sur
     * n'importe quel colis de la société (adresse, téléphone, montant COD).
     */
    public function deliveryManOwns($id){
        return $this->deliveryManParcels()->whereKey($id)->exists();
    }

    public function deliveryTypes(){
        $types=[
            'same_day',
            'next_day',
            'sub_city',
            'outside_City',
        ];
        return Config::companywise()->whereIn('key',$types)->where('value',1)->get();
    }


    public function filter($request){

        $userHubID = auth()->user()->hub_id;
        if($request->parcel_date) {
            $date = explode('To', $request->parcel_date);
            $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
            $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
        }
        if(!blank($userHubID)){
            return Parcel::companywise()->with('parcelEvent')->where('hub_id',$userHubID)->orderBy('updated_at')->orderBy('priority_type_id')->orderBy('id','desc')->where(function( $query ) use ( $request ) {
                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }

                }

                if($request->parcel_status ) {
                    if($request->parcel_status == ParcelStatus::DELIVERY_MAN_ASSIGN){
                        $query->whereIn('status',   [$request->parcel_status,ParcelStatus::DELIVERY_RE_SCHEDULE]);
                    }else{
                        $query->where('status',$request->parcel_status);
                    }
                }

                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

                if($request->parcel_deliveryman_id || $request->parcel_pickupman_id){

                    $query->whereHas('parcelEvent', function ($queryParcelEvent)use($request) {

                        if($request->parcel_deliveryman_id) {
                            $queryParcelEvent->where(['delivery_man_id' => $request->parcel_deliveryman_id]);
                        }

                        if($request->parcel_pickupman_id) {
                            $queryParcelEvent->where(['pickup_man_id' => $request->parcel_pickupman_id]);
                        }
                    });
                }
                if($request->invoice_id) {
                    $query->where('invoice_no', 'like', '%' . $request->invoice_id . '%');
                }
            })->paginate(10);
        }else{
            return Parcel::companywise()->with('parcelEvent')->orderBy('updated_at')->orderBy('priority_type_id')->orderBy('id','desc')->where(function( $query ) use ( $request ) {

                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
                }

                if($request->parcel_status ) {
                    if($request->parcel_status == ParcelStatus::DELIVERY_MAN_ASSIGN){
                        $query->whereIn('status',   [$request->parcel_status,ParcelStatus::DELIVERY_RE_SCHEDULE]);
                    }else{
                        $query->where('status',$request->parcel_status);
                    }
                }

                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

                if($request->parcel_deliveryman_id || $request->parcel_pickupman_id){
                    $query->whereHas('parcelEvent', function ($queryParcelEvent)use($request) {

                        if($request->parcel_deliveryman_id) {
                            $queryParcelEvent->where(['delivery_man_id' => $request->parcel_deliveryman_id]);
                        }

                        if($request->parcel_pickupman_id) {
                            $queryParcelEvent->where(['pickup_man_id' => $request->parcel_pickupman_id]);
                        }
                    });
                }
                if($request->invoice_id) {
                    $query->where('invoice_no', 'like', '%' . $request->invoice_id . '%');
                }

            })->paginate(10);

        }

    }


    public function filterPrint($request){
        $userHubID = auth()->user()->hub_id;
        if(!blank($userHubID)){
            return Parcel::companywise()->with('parcelEvent')->where('hub_id',$userHubID)->orderBy('updated_at')->orderBy('priority_type_id')->where(function( $query ) use ( $request ) {
                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
                }
                if($request->parcel_status ) {
                    if($request->parcel_status == ParcelStatus::DELIVERY_MAN_ASSIGN){
                        $query->whereIn('status',   [$request->parcel_status,ParcelStatus::DELIVERY_RE_SCHEDULE]);
                    }else{
                        $query->where('status',$request->parcel_status);
                    }
                }

                if($request->pickup_date) {
                    $query->where(['pickup_date' => date('Y-m-d', strtotime($request->pickup_date))]);
                }

                if($request->delivery_date) {
                    $query->where(['delivery_date' => date('Y-m-d', strtotime($request->delivery_date))]);
                }

                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

                if($request->parcel_deliveryman_id || $request->parcel_pickupman_id){
                    $query->whereHas('parcelEvent', function ($queryParcelEvent)use($request) {
                        if($request->parcel_deliveryman_id) {
                            $queryParcelEvent->where(['delivery_man_id' => $request->parcel_deliveryman_id]);
                        }
                        if($request->parcel_pickupman_id) {
                            $queryParcelEvent->where(['pickup_man_id' => $request->parcel_pickupman_id]);
                        }
                    });
                }


            })->get();
        }else{
            return Parcel::companywise()->with('parcelEvent')->orderBy('updated_at')->orderBy('priority_type_id')->where(function( $query ) use ( $request ) {
                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
                }

                if($request->parcel_status ) {
                    if($request->parcel_status == ParcelStatus::DELIVERY_MAN_ASSIGN){
                        $query->whereIn('status',   [$request->parcel_status,ParcelStatus::DELIVERY_RE_SCHEDULE]);
                    }else{
                        $query->where('status',$request->parcel_status);
                    }
                }
                if($request->pickup_date) {
                    $query->where(['pickup_date' => date('Y-m-d', strtotime($request->pickup_date))]);
                }

                if($request->delivery_date) {
                    $query->where(['delivery_date' => date('Y-m-d', strtotime($request->delivery_date))]);
                }

                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

                if($request->parcel_deliveryman_id || $request->parcel_pickupman_id){
                    $query->whereHas('parcelEvent', function ($queryParcelEvent)use($request) {

                        if($request->parcel_deliveryman_id) {
                            $queryParcelEvent->where(['delivery_man_id' => $request->parcel_deliveryman_id]);
                        }

                        if($request->parcel_pickupman_id) {
                            $queryParcelEvent->where(['pickup_man_id' => $request->parcel_pickupman_id]);
                        }
                    });
                }

            })->get();

        }

    }




    public function get($id) {
        $userHubID = auth()->user()->hub_id;
        if(!blank($userHubID)){
            return Parcel::companywise()->where(['id'=>$id,'hub_id'=>$userHubID])->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->first();
        }else{
            return Parcel::companywise()->where(['id'=>$id])->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->first();
        }
    }


    public function parcelEvents($id) {
        return ParcelEvent::with(['deliveryMan','pickupman', 'transferDeliveryman', 'hub', 'user'])->where('parcel_id',$id)->orderBy('created_at','desc')->get();
    }
    public function parcelTracking($request) {
        return Parcel::where(function($query){
            if(tenant()): 
                $query->where('company_id',settings()->id);
            endif;
        })->where('tracking_id',$request->tracking_id)->first();
    }

    public function details($id) {
        $userHubID = auth()->user()->hub_id;
        if(!blank($userHubID)){
            return Parcel::companywise()->where(['id'=> $id,'hub_id'=>$userHubID])->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->first();
        }else {
            return Parcel::companywise()->where(['id'=> $id])->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->first();
        }
    }

    /**
     * Poser un statut sur un colis, depuis l'administration.
     *
     * ⚠️ `Parcel::find($id)` etait **nu** : aucun scope de societe. Un
     * administrateur de la societe A pouvait donc, en forgeant l'identifiant,
     * changer le statut d'un colis de la societe B — et par la le faire entrer,
     * ou non, dans un releve de reglement qui ne le regarde pas. La permission
     * `parcel_status_update` protegeait l'acces a la route, jamais la portee.
     *
     * Le repli sur `companywise()` suffit ici : cote administration, la societe
     * vient de la session, contrairement au webhook de paiement qui n'en a pas.
     */
    public function statusUpdate($id, $status_id) {
        $parcel = Parcel::companywise()->find($id);
        if (blank($parcel)) {
            return false;
        }

        $parcel->status = $status_id;
        $parcel->save();

        return true;
    }

    public function deliveryCharges(){
        return DeliveryCharge::companywise()->distinct('category_id')->pluck('category_id');
    }

    public function deliveryCategories(){
        return pluck(Deliverycategory::where(function($query){
            $query->companywise();
            $query->orWhere('id',1);
        })->get(), 'obj', 'id');
    }


    public function packaging(){
        return Packaging::companywise()->where('status',Status::ACTIVE)->get();
    }


    public function RandomTrackingID(){
        return Str::upper(settings()->par_track_prefix).random_int(11111111,99999999);  
    }

    /** La categorie partagee de la plateforme — voir `DeliveryCategoryRepository::all()`. */
    private const CATEGORIE_DE_PLATEFORME = 1;

    /**
     * S46 — un catalogue applique au colis doit etre celui de la maison.
     *
     * Trois identifiants voyagent dans le corps de la requete et n'avaient
     * aucun perimetre, alors que chacun se convertit en argent ou en adresse :
     *
     * · `category_id` — categorie de livraison ; c'est une des deux cles du
     *   bareme, donc du prix de la course ;
     * · `shop_id` — boutique de ramassage. `merchant_shops` ne porte AUCUNE
     *   colonne `company_id` (S26) : son perimetre passe par le MARCHAND, et
     *   pas seulement par la societe — la boutique d'un confrere de la maison
     *   n'est pas davantage la sienne. `backend/parcel/bulk_print` imprime
     *   `merchantShop->contact_no` : une boutique etrangere mettait son
     *   telephone sur notre etiquette ;
     * · `packaging_id` — son PRIX etait deja garde (`ChargeCalculator`), son
     *   ECRITURE non : un emballage etranger s'inscrivait sur le colis et
     *   etait facture zero.
     *
     * Ne sont PAS gardes ici, et c'est mesure : `zone_id` l'est deja deux fois
     * (`ChargeCalculator` et la regle `DeliveryRoutePriced`), `delivery_type_id`
     * est une constante de plateforme, `priority_id` un drapeau ecrit en dur
     * par le controleur, et `delay_id` se garde dans le resolveur — seul
     * endroit ou sa surcharge est lue.
     *
     * Chacun reste FACULTATIF : un champ absent laisse le colis sans catalogue,
     * comme avant. Seul un identifiant fourni ET etranger fait refuser.
     */
    private function catalogueHorsPerimetre($merchant, $request): bool
    {
        // ⚠️ La categorie 1 est celle de la PLATEFORME, partagee par toutes les
        // societes. Ce n'est pas une supposition : `DeliveryCategoryRepository`
        // ecrit la regle deux fois — `all()` rend « `id = 1` OU `company_id =
        // settings()->id` », et `get()` la reprend telle quelle. Les six lignes
        // semees par le socle n'ont d'ailleurs AUCUN `company_id`. Une garde
        // qui l'ignorerait refuserait le catalogue commun, donc la creation de
        // colis la plus banale.
        if (filled($request->category_id)
            && (int) $request->category_id !== self::CATEGORIE_DE_PLATEFORME
            && blank(Deliverycategory::companywise()->find($request->category_id))) {
            return true;
        }

        if (filled($request->shop_id)
            && blank(MerchantShops::where('merchant_id', $merchant->id)->find($request->shop_id))) {
            return true;
        }

        if (filled($request->packaging_id)
            && blank(Packaging::companywise()->find($request->packaging_id))) {
            return true;
        }

        return false;
    }

    public function store($request) {

        try {
         
            DB::beginTransaction();
            $merchant                       = Merchant::companywise()->with('user')->find($request->merchant_id);
            // S33 — le marchand facture vient du formulaire et n'etait pas scope :
            // le colis se creait dans MA societe (`company_id = settings()->id`) mais
            // au nom du marchand d'une AUTRE, dont il recopiait l'entrepot et a qui il
            // debitait frais, TVA et net a reverser. Le formulaire ne propose que mes
            // marchands ; rien n'obligeait le navigateur a s'y tenir.
            if (blank($merchant)) {
                DB::rollBack();
                return false;
            }

            // S46 — les CATALOGUES du colis. Le marchand etait garde depuis S33 ;
            // ce qu'on applique au colis ne l'etait pas.
            if ($this->catalogueHorsPerimetre($merchant, $request)) {
                DB::rollBack();
                return false;
            }
            $parcel                         = new Parcel();
            $parcel->company_id             = settings()->id;
            $parcel->merchant_id            = $request->merchant_id;
            $parcel->first_hub_id           = $merchant->user->hub_id;//merchant hub id
            $parcel->hub_id                 = $merchant->user->hub_id;
            $parcel->category_id            = $request->category_id;
            if($request->weight !==""){
                $parcel->weight                 = $request->weight;
            }
            $parcel->invoice_no             = $request->invoice_no;
            $parcel->cash_collection        = $request->cash_collection;
            if($request->selling_price){
                $parcel->selling_price          = $request->selling_price;
            }
            $parcel->merchant_shop_id       = $request->shop_id;
            $parcel->pickup_phone           = $request->pickup_phone;
            $parcel->pickup_address         = $request->pickup_address;

            $parcel->pickup_lat             = $request->pickup_lat;
            $parcel->pickup_long            = $request->pickup_long;
 
            $parcel->customer_name          = $request->customer_name;
            $parcel->customer_phone         = $request->customer_phone;
            $parcel->customer_address       = $request->customer_address;
            // Chantier 5 — vide ou BJ : colis domestique. L'alerte douaniere
            // eventuelle est emise par ParcelCustomsObserver a l'enregistrement.
            $parcel->destination_country    = $request->destination_country;
            $parcel->customs_category       = $request->customs_category;
            $parcel->customer_lat           = $request->lat;
            $parcel->customer_long          = $request->long;
            $parcel->delivery_type_id       = $request->delivery_type_id;
            // D4, etape 5 bis : la zone et le delai du colis. Nuls tant que
            // le formulaire ne les envoie pas — le colis suit alors le
            // bareme herite, et `delivery_type_id` reste sa seule route.
            $parcel->zone_id                = $request->zone_id ?: null;
            $parcel->delay_id               = $request->delay_id ?: null;
            $parcel->priority_type_id       = $request->priority_id;
            // Pickup & Delivery Time
            if($request->delivery_type_id == DeliveryType::SAMEDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d');
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::NEXTDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +2 day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::SUBCITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY .' day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY + 1 .' day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::OUTSIDECITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY .' day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY + 1 .' day'));
                }
            }
            // End Pickup & Delivery Time
            // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
            // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
            // a reverser fabriques par le navigateur : le client choisissait sa facture.
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                $merchant,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                (float) $request->cash_collection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                isset($request->fragileLiquid) && $request->fragileLiquid == 'on',
                // D4, etape 6 : la route du colis, desormais son seul tarif.
                $request->zone_id ? (int) $request->zone_id : null,
                $request->delay_id ? (int) $request->delay_id : null,
                $request->destination_country
            );
            $parcel->vat                    = $charges['vat'];
            $parcel->vat_amount             = $charges['vat_amount'];
            $parcel->delivery_charge        = $charges['delivery_charge'];
            $parcel->cod_charge             = $charges['cod_charge'];
            $parcel->cod_amount             = $charges['cod_amount'];
            $parcel->total_delivery_amount  = $charges['total_delivery_amount'];
            $parcel->current_payable        = $charges['current_payable'];
            $parcel->note                   = $request->note;
            if($request->packaging_id){
                $parcel->packaging_id           = $request->packaging_id;
                $parcel->packaging_amount       = $charges['packaging_amount'];
            }
            if(isset($request->fragileLiquid) && $request->fragileLiquid =='on'){
                $parcel->liquid_fragile_amount      = $charges['liquid_fragile_amount'];
            }
            $parcel->save();
           
            $parcel->tracking_id               = $this->RandomTrackingID(); 
            $parcel->save();


            // W5 / controle de solde — un seul point de debit, partage par les
            // quatre chemins de creation : voir Services\Parcel\WalletDebit.
            // Il verrouille la ligne marchand, refuse si le solde ne couvre pas
            // les frais, puis debite. Plus de `catch` vide : un echec doit
            // annuler la creation, pas la laisser facturee a personne.
            app(\App\Services\Parcel\WalletDebit::class)->apply($parcel);
  
            // Parcel logs
            $log                         = new ParcelLogs;
            $log->merchant_id            = $request->merchant_id;
            $log->hub_id                 = $merchant->user->hub_id;
            $log->parcel_id              = $parcel->id;
            $log->pickup_address         = $request->pickup_address;
            $log->pickup_phone           = $request->pickup_phone;
            $log->customer_name          = $request->customer_name;
            $log->customer_phone         = $request->customer_phone;
            $log->customer_address       = $request->customer_address;
            $log->invoice_no             = $request->invoice_no;
            $log->cash_collection        = $request->cash_collection;
            if($request->selling_price){
                $log->selling_price          = $request->selling_price;
            }
            $log->total_delivery_amount  = $charges['total_delivery_amount'];
            $log->current_payable        = $charges['current_payable'];
            $log->note                   = $request->note;
            $log->save();

            // dd($parcel,$parcel->merchant->user->email);
            try {
                app(PushNotificationService::class)->sendStatusPushNotification($parcel, $parcel->merchant->user->email,'','');
            } catch (\Exception $exception) {
            }

            DB::commit();
            if(SmsSendSettingHelper(SmsSendStatus::PARCEL_CREATE)) {
                $sms = SmsTemplate::forCompany($parcel->company_id);
                $msg = $sms->render('parcel_created', [
                    'customer' => $parcel->customer_name,
                    'tracking' => $parcel->tracking_id,
                    'merchant' => $parcel->merchant->business_name,
                    'amount'   => $sms->amount($parcel->cash_collection),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);

            }
            return true;
        }
        // Le refus de solde traverse : le controleur le transforme en message
        // clair, la ou un `false` ne disait que « une erreur est survenue ».
        catch (InsufficientWalletBalance $e) {
            DB::rollBack();
            throw $e;
        }
        catch (\Exception $e) { 
            DB::rollBack();
            return false;
        }
    }

    public function duplicateStore($request)
    {
        try {
           
            DB::beginTransaction();
            $merchant                       = Merchant::companywise()->with('user')->find($request->merchant_id);
            // S33 — le marchand facture vient du formulaire et n'etait pas scope :
            // le colis se creait dans MA societe (`company_id = settings()->id`) mais
            // au nom du marchand d'une AUTRE, dont il recopiait l'entrepot et a qui il
            // debitait frais, TVA et net a reverser. Le formulaire ne propose que mes
            // marchands ; rien n'obligeait le navigateur a s'y tenir.
            if (blank($merchant)) {
                DB::rollBack();
                return false;
            }

            // S46 — les CATALOGUES du colis. Le marchand etait garde depuis S33 ;
            // ce qu'on applique au colis ne l'etait pas.
            if ($this->catalogueHorsPerimetre($merchant, $request)) {
                DB::rollBack();
                return false;
            }
            $duplicate_parcel               = $this->get($request->parcel_id);
            $parcel                         = new Parcel();
            $parcel->company_id             = settings()->id;
            $parcel->merchant_id            = $request->merchant_id;
            $parcel->first_hub_id           = $merchant->user->hub_id;//merchant hub_id
            $parcel->hub_id                 = $merchant->user->hub_id;
            $parcel->category_id            = $request->category_id;
            if($request->weight !==""){
                $parcel->weight                 = $request->weight;
            }
            $parcel->invoice_no             = $request->invoice_no;
            $parcel->cash_collection        = $request->cash_collection;
            if($request->selling_price){
                $parcel->selling_price      = $request->selling_price;
            }
            $parcel->merchant_shop_id       = $request->shop_id;
            $parcel->pickup_phone           = $request->pickup_phone;
            $parcel->pickup_address         = $request->pickup_address;

            $parcel->pickup_lat             = $request->pickup_lat;
            $parcel->pickup_long            = $request->pickup_long;

 
            $parcel->customer_name          = $request->customer_name;
            $parcel->customer_phone         = $request->customer_phone;
            $parcel->customer_address       = $request->customer_address;
            // Chantier 5 — vide ou BJ : colis domestique. L'alerte douaniere
            // eventuelle est emise par ParcelCustomsObserver a l'enregistrement.
            $parcel->destination_country    = $request->destination_country;
            $parcel->customs_category       = $request->customs_category;
            $parcel->customer_lat           = $request->lat;
            $parcel->customer_long          = $request->long;
            $parcel->delivery_type_id       = $request->delivery_type_id;
            // D4, etape 5 bis : la zone et le delai du colis. Nuls tant que
            // le formulaire ne les envoie pas — le colis suit alors le
            // bareme herite, et `delivery_type_id` reste sa seule route.
            $parcel->zone_id                = $request->zone_id ?: null;
            $parcel->delay_id               = $request->delay_id ?: null;
            $parcel->note                   = $request->note;
            $parcel->status                 = ParcelStatus::PENDING;
            // Pickup & Delivery Time
            if($request->delivery_type_id == DeliveryType::SAMEDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d');
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::NEXTDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +2 day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::SUBCITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY .' day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY + 1 .' day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::OUTSIDECITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY .' day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY + 1 .' day'));
                }
            }
            // End Pickup & Delivery Time
            // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
            // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
            // a reverser fabriques par le navigateur : le client choisissait sa facture.
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                $merchant,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                (float) $request->cash_collection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                isset($request->fragileLiquid) && $request->fragileLiquid == 'on',
                // D4, etape 6 : la route du colis, desormais son seul tarif.
                $request->zone_id ? (int) $request->zone_id : null,
                $request->delay_id ? (int) $request->delay_id : null,
                $request->destination_country
            );
            $parcel->vat                    = $charges['vat'];
            $parcel->vat_amount             = $charges['vat_amount'];
            $parcel->delivery_charge        = $charges['delivery_charge'];
            $parcel->cod_charge             = $charges['cod_charge'];
            $parcel->cod_amount             = $charges['cod_amount'];
            $parcel->total_delivery_amount  = $charges['total_delivery_amount'];
            $parcel->current_payable        = $charges['current_payable'];
            if($request->packaging_id){
                $parcel->packaging_id               = $request->packaging_id;
                $parcel->packaging_amount           = $charges['packaging_amount'];
            }
            if(isset($request->fragileLiquid) && $request->fragileLiquid=='on'){
                $parcel->liquid_fragile_amount      = $charges['liquid_fragile_amount'];
            }else {
                $parcel->liquid_fragile_amount      = null;
            }
            $parcel->save();
           
            $parcel->tracking_id               = $this->RandomTrackingID();  
            $parcel->save();

            // W5 / controle de solde — un seul point de debit, partage par les
            // quatre chemins de creation : voir Services\Parcel\WalletDebit.
            // Il verrouille la ligne marchand, refuse si le solde ne couvre pas
            // les frais, puis debite. Plus de `catch` vide : un echec doit
            // annuler la creation, pas la laisser facturee a personne.
            app(\App\Services\Parcel\WalletDebit::class)->apply($parcel);


            // Parcel logs
            $log                         = new ParcelLogs;
            $log->merchant_id            = $request->merchant_id;
            $log->hub_id                 = $merchant->user->hub_id;
            $log->parcel_id              = $parcel->id;
            $log->pickup_address         = $request->pickup_address;
            $log->pickup_phone           = $request->pickup_phone;
            $log->customer_name          = $request->customer_name;
            $log->customer_phone         = $request->customer_phone;
            $log->customer_address       = $request->customer_address;
            $log->invoice_no             = $request->invoice_no;
            $log->cash_collection        = $request->cash_collection;
            if($request->selling_price){
                $log->selling_price          = $request->selling_price;
            }
            $log->total_delivery_amount  = $charges['total_delivery_amount'];
            $log->current_payable        = $charges['current_payable'];
            $log->note                       = $request->note;
            $log->save();
            DB::commit();
            return true;
        }
        catch (InsufficientWalletBalance $e) {
            DB::rollBack();
            throw $e;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function update($id, $request) {

        try {
            DB::beginTransaction();
            // S33 — DEUX lectures nues ici, et la seconde etait la plus grave :
            //  · `Parcel::find($id)` acceptait le colis d'une AUTRE societe, alors que
            //    `get()` et `details()` juste au-dessus sont `companywise()` ;
            //  · `Merchant::find($request->merchant_id)` acceptait le marchand de
            //    n'importe quelle societe, et la ligne `$parcel->merchant_id = ...`
            //    REAFFECTAIT donc le colis a un marchand d'ailleurs — en recopiant au
            //    passage son `hub_id` dans `first_hub_id` et `hub_id`.
            // Le meme identifiant etait relu une TROISIEME fois, sans perimetre, pour
            // le calcul des montants : les trois lectures passent desormais par ce
            // seul marchand verifie.
            $merchant                       = Merchant::companywise()->with('user')->find($request->merchant_id);
            $parcel                         = Parcel::companywise()->find($id);

            if (blank($parcel) || blank($merchant)) {
                DB::rollBack();
                return false;
            }

            // S46 — les CATALOGUES du colis. Le marchand etait garde depuis S33 ;
            // ce qu'on applique au colis ne l'etait pas.
            if ($this->catalogueHorsPerimetre($merchant, $request)) {
                DB::rollBack();
                return false;
            }
            // $parcel->company_id             = settings()->id;
            $parcel->merchant_id            = $request->merchant_id;
            $parcel->first_hub_id           = $merchant->user->hub_id;//merchant hub_id
            $parcel->hub_id                 = $merchant->user->hub_id;
            $parcel->category_id            = $request->category_id;
            if($request->weight !==""){
                $parcel->weight                 = $request->weight;
            }
            $parcel->invoice_no             = $request->invoice_no;
            $parcel->cash_collection        = $request->cash_collection;
            if($request->selling_price){
                $parcel->selling_price          = $request->selling_price;
            }
            $parcel->merchant_shop_id       = $request->shop_id;
            $parcel->pickup_phone           = $request->pickup_phone;
            $parcel->pickup_address         = $request->pickup_address;
 
            $parcel->pickup_lat             = $request->pickup_lat;
            $parcel->pickup_long            = $request->pickup_long;

            $parcel->customer_name          = $request->customer_name;
            $parcel->customer_phone         = $request->customer_phone;
            $parcel->customer_lat           = $request->lat;
            $parcel->customer_long          = $request->long;
            $parcel->customer_address       = $request->customer_address;
            // Chantier 5 — vide ou BJ : colis domestique. L'alerte douaniere
            // eventuelle est emise par ParcelCustomsObserver a l'enregistrement.
            $parcel->destination_country    = $request->destination_country;
            $parcel->customs_category       = $request->customs_category;
            $parcel->delivery_type_id       = $request->delivery_type_id;
            // D4, etape 5 bis : la zone et le delai du colis. Nuls tant que
            // le formulaire ne les envoie pas — le colis suit alors le
            // bareme herite, et `delivery_type_id` reste sa seule route.
            $parcel->zone_id                = $request->zone_id ?: null;
            $parcel->delay_id               = $request->delay_id ?: null;
            // Pickup & Delivery Time
            if($request->delivery_type_id == DeliveryType::SAMEDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d');
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::NEXTDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +2 day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::SUBCITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY .' day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY + 1 .' day'));
                }
            }
            elseif($request->delivery_type_id == DeliveryType::OUTSIDECITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->pickup_date      = date('Y-m-d');
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY .' day'));
                }
                else{
                    $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY + 1 .' day'));
                }
            }
            // End Pickup & Delivery Time
            $parcel->note                       = $request->note;
            // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
            // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
            // a reverser fabriques par le navigateur : le client choisissait sa facture.
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                $merchant,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                (float) $request->cash_collection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                isset($request->fragileLiquid) && $request->fragileLiquid == 'on',
                // D4, etape 6 : la route du colis, desormais son seul tarif.
                $request->zone_id ? (int) $request->zone_id : null,
                $request->delay_id ? (int) $request->delay_id : null,
                $request->destination_country
            );
            $parcel->vat                    = $charges['vat'];
            $parcel->vat_amount             = $charges['vat_amount'];
            $parcel->delivery_charge        = $charges['delivery_charge'];
            $parcel->cod_charge             = $charges['cod_charge'];
            $parcel->cod_amount             = $charges['cod_amount'];
            $parcel->total_delivery_amount  = $charges['total_delivery_amount'];
            $parcel->current_payable        = $charges['current_payable'];
            if($request->packaging_id){
                $parcel->packaging_id           = $request->packaging_id;
                $parcel->packaging_amount       = $charges['packaging_amount'];
            }
            if(isset($request->fragileLiquid) && $request->fragileLiquid=='on'){
                $parcel->liquid_fragile_amount      = $charges['liquid_fragile_amount'];
            }else {
                $parcel->liquid_fragile_amount      = null;
            }
            $parcel->save();
            DB::commit();
            return true;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function delete($id) {
        $parcel = Parcel::find($id);
        if($parcel->company_id == settings()->id):
            return Parcel::destroy($id);
        endif;
        return false;
    }

    //parcel events
    public function pickupdatemanAssigned($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }

        // ...et le livreur nomme doit l'etre aussi : sinon une societe paie la
        // course d'un livreur qu'elle n'emploie pas, sur les colis d'une autre.
        if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
            return false;
        }
        try {

            // Meme regle cote ramassage : une seule affectation en lice, sinon
            // c'est le premier ramasseur nomme qui touche la course.
            ParcelEvent::where('parcel_id',$id)->whereIn('parcel_status',[ParcelStatus::PICKUP_ASSIGN,ParcelStatus::PICKUP_RE_SCHEDULE])->delete();

            $pickupAsisgn                = new ParcelEvent();
            $pickupAsisgn->parcel_id     = $id;
            $pickupAsisgn->pickup_man_id = $request->delivery_man_id;
            $pickupAsisgn->note          = $request->note;
            $pickupAsisgn->parcel_status = ParcelStatus::PICKUP_ASSIGN;
            $pickupAsisgn->created_by    = Auth::user()->id;
            $pickupAsisgn->save();
            $parcel                      = Parcel::find($id);
            $parcel->status              = ParcelStatus::PICKUP_ASSIGN;
            $parcel->save();
            if($request->send_sms_pickuman == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('pickup_assigned_agent', [
                    'agent'    => $pickupAsisgn->pickupman->user->name,
                    'tracking' => $parcel->tracking_id,
                    'merchant' => $parcel->merchant->business_name,
                    'phone'    => $parcel->merchant->user->mobile,
                    'address'  => $parcel->merchant->address,
                    'date'     => dateFormat($parcel->pickup_date),
                ]);
                $response =  app(SmsService::class)->sendSms($pickupAsisgn->pickupman->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$pickupAsisgn->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$pickupAsisgn->pickupman->user->email,$msgNotification,'deliveryMan');
            }catch (\Exception $exception){

            }


            if($request->send_sms_merchant  == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('pickup_assigned_merchant', [
                    'merchant'    => $parcel->merchant->business_name,
                    'tracking'    => $parcel->tracking_id,
                    'agent'       => $pickupAsisgn->pickupman->user->name,
                    'agent_phone' => $pickupAsisgn->pickupman->user->mobile,
                    'url'         => url('/'),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->merchant->user->mobile, $msg);
            }
            try{

                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Pickup man assign from '.settings()->name.'. Assign by '.$pickupAsisgn->pickupman->user->name.', '.$pickupAsisgn->pickupman->user->mobile.' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }

            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }




    public function PickupReSchedule($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }

        // ...et le livreur nomme doit l'etre aussi : sinon une societe paie la
        // course d'un livreur qu'elle n'emploie pas, sur les colis d'une autre.
        if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
            return false;
        }
        try {

            $pickupassignevents                = ParcelEvent::where('parcel_id',$id)->whereIn('parcel_status',[parcelStatus::PICKUP_ASSIGN,parcelStatus::PICKUP_RE_SCHEDULE])->delete();

            $date                            = Carbon::parse($request->date);
            $pickupReshcedule                = new ParcelEvent();
            $pickupReshcedule->parcel_id     = $id;
            $pickupReshcedule->pickup_man_id = $request->delivery_man_id;
            $pickupReshcedule->note          = $request->note;
            $pickupReshcedule->parcel_status = ParcelStatus::PICKUP_RE_SCHEDULE;
            $pickupReshcedule->created_by    = Auth::user()->id;
            $pickupReshcedule->save();
            $parcel                          = Parcel::find($id);
            $parcel->pickup_date             = $request->date;
            //Pickup & Delivery Time
            if($parcel->delivery_type_id == DeliveryType::SAMEDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->delivery_date    = $request->date;
                }
                else{
                    $parcel->delivery_date    = $date->add(1,'day')->format('Y-m-d');
                }
            }
            elseif($parcel->delivery_type_id == DeliveryType::NEXTDAY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->delivery_date    = $date->add(1,'day')->format('Y-m-d');
                }
                else{
                    $parcel->delivery_date    = $date->add(2,'day')->format('Y-m-d');;
                }
            }
            elseif($parcel->delivery_type_id == DeliveryType::SUBCITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->delivery_date    = $date->add(DeliveryTime::SUBCITY,'day')->format('Y-m-d');
                }
                else{
                    $parcel->delivery_date    = $date->add(DeliveryTime::SUBCITY + 1,'day')->format('Y-m-d');
                }
            }
            elseif($parcel->delivery_type_id == DeliveryType::OUTSIDECITY){
                if(date('H') < DeliveryTime::LAST_TIME){
                    $parcel->delivery_date    = $date->add(DeliveryTime::OUTSIDECITY,'day')->format('Y-m-d');
                }
                else{
                    $parcel->delivery_date    = $date->add(DeliveryTime::OUTSIDECITY + 1,'day')->format('Y-m-d');
                }
            }
            // End Pickup & Delivery Time
            $parcel->status = ParcelStatus::PICKUP_RE_SCHEDULE;
            $parcel->save();

            if($request->send_sms_pickuman == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('pickup_assigned_agent', [
                    'agent'    => $pickupReshcedule->pickupman->user->name,
                    'tracking' => $parcel->tracking_id,
                    'merchant' => $parcel->merchant->business_name,
                    'phone'    => $parcel->merchant->user->mobile,
                    'address'  => $parcel->merchant->address,
                    'date'     => dateFormat($parcel->pickup_date),
                ]);
                $response = app(SmsService::class)->sendSms($pickupReshcedule->pickupman->user->mobile, $msg);
            }

            if($request->send_sms_merchant  == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('pickup_assigned_merchant', [
                    'merchant'    => $parcel->merchant->business_name,
                    'tracking'    => $parcel->tracking_id,
                    'agent'       => $pickupReshcedule->pickupman->user->name,
                    'agent_phone' => $pickupReshcedule->pickupman->user->mobile,
                    'url'         => url('/'),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->merchant->user->mobile, $msg);
            }

            try{
                $msgNotification = 'Dear '.$pickupReshcedule->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$pickupReshcedule->pickupman->user->email,$msgNotification,'deliveryMan');
            }catch (\Exception $exception){

            }
            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Pickup man assign from '.settings()->name.'. Assign by '.$pickupReshcedule->pickupman->user->name.', '.$pickupReshcedule->pickupman->user->mobile.' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function receivedBypickupman($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }
        try {
            $receivedPickupman                = new ParcelEvent();
            $receivedPickupman->parcel_id     = $id;
            $receivedPickupman->note          = $request->note;
            $receivedPickupman->parcel_status = ParcelStatus::RECEIVED_BY_PICKUP_MAN;
            $receivedPickupman->created_by    = Auth::user()->id;
            $receivedPickupman->save();
            $parcel                           = Parcel::find($id);
            $parcel->status                   = ParcelStatus::RECEIVED_BY_PICKUP_MAN;
            $parcel->save();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function receivedByHub($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }
        try {

            $receivedByhub                = new ParcelEvent();
            $receivedByhub->parcel_id     = $id;
            $receivedByhub->note          = $request->note;
            $receivedByhub->parcel_status = ParcelStatus::RECEIVED_BY_HUB;
            $receivedByhub->created_by    = Auth::user()->id;
            $receivedByhub->save();
            $parcel                       = Parcel::find($id);
            $parcel->hub_id               = $parcel->transfer_hub_id;
            $parcel->status               = ParcelStatus::RECEIVED_BY_HUB;
            $parcel->save();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function transferToHubMultipleParcel($request){
        try {
            // S45 — l'entrepot de destination vaut pour tout le lot : s'il n'est pas
            // des notres, aucun colis ne doit partir. Les colis restent chez nous,
            // mais sortent de toutes nos listes d'entrepot.
            if(blank(Hub::companywise()->find($request->hub_id))){
                return false;
            }
            // S45 — le livreur du transfert est facultatif ici : garde si nomme.
            if(filled($request->delivery_man_id) && blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
                return false;
            }

            foreach($request->parcel_ids as $id){
                // S38 — le colis venait d'une LISTE portee par le corps de la requete.
                // Aucune route a parametre ne porte ces identifiants : le filet ne
                // voyait pas ce chemin. On changeait le statut des colis d'un AUTRE
                // transporteur, on leur posait un evenement, et le SMS partait a SON
                // client. Un identifiant hors perimetre est simplement ignore : le
                // reste du lot, lui, passe.
                $parcel = Parcel::companywise()->find($id);
                if(blank($parcel)){
                    continue;
                }
                $transfertohub                           = new ParcelEvent();
                $transfertohub->parcel_id                = $id;
                $transfertohub->hub_id                   = $request->hub_id;
                $transfertohub->transfer_delivery_man_id = $request->delivery_man_id;
                $transfertohub->note                     = $request->note;
                $transfertohub->parcel_status            = ParcelStatus::TRANSFER_TO_HUB;
                $transfertohub->created_by               = Auth::user()->id;
                $transfertohub->save();
                $parcel->transfer_hub_id                  = $request->hub_id;
                $parcel->status                          = ParcelStatus::TRANSFER_TO_HUB;
                $parcel->save();
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function deliveryManAssignMultipleParcel($request){
        try {
            // S45 — S38 a ferme l'axe du COLIS sur ce chemin et l'a inscrit comme
            // prouve. L'axe de l'AGENT restait ouvert : `delivery_man_id` designe
            // un livreur d'une AUTRE societe, credite de la course sur NOS ecritures.
            // Un identifiant de lot hors perimetre est ignore ; celui-ci vaut pour
            // TOUT le lot, donc on refuse le lot entier.
            if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
                return false;
            }
            $deliveryUser  = DeliveryMan::find($request->delivery_man_id);
            foreach($request->parcel_ids_ as $id){
                // S38 — le colis venait d'une LISTE portee par le corps de la requete.
                // Aucune route a parametre ne porte ces identifiants : le filet ne
                // voyait pas ce chemin. On changeait le statut des colis d'un AUTRE
                // transporteur, on leur posait un evenement, et le SMS partait a SON
                // client. Un identifiant hors perimetre est simplement ignore : le
                // reste du lot, lui, passe.
                $parcel = Parcel::companywise()->find($id);
                if(blank($parcel)){
                    continue;
                }
                $deliveryMan                           = new ParcelEvent();
                $deliveryMan->parcel_id                = $id;
                $deliveryMan->delivery_man_id          = $request->delivery_man_id;
                $deliveryMan->note                     = $request->note;
                $deliveryMan->delivery_lat             = $deliveryUser->delivery_lat;
                $deliveryMan->delivery_long            = $deliveryUser->delivery_long;
                $deliveryMan->parcel_status            = ParcelStatus::DELIVERY_MAN_ASSIGN;
                $deliveryMan->created_by               = Auth::user()->id;
                $deliveryMan->save();
                $parcel->status                            = ParcelStatus::DELIVERY_MAN_ASSIGN;
                $parcel->save();

                if($request->send_sms == 'on'){
                    $sms = SmsTemplate::forCompany($parcel->company_id);
                    $msg = $sms->render('deliveryman_assigned_customer', [
                        'customer'    => $parcel->customer_name,
                        'tracking'    => $parcel->tracking_id,
                        'merchant'    => $parcel->merchant->business_name,
                        'agent'       => $deliveryMan->deliveryMan->user->name,
                        'agent_phone' => $deliveryMan->deliveryMan->user->mobile,
                        'url'         => url('/'),
                        'amount'      => $sms->amount($parcel->cash_collection),
                    ]);
                    $response =  app(SmsService::class)->sendSms($parcel->customer_phone,$msg);
                }


            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function transfertohub($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }
        // S45 — l'entrepot de destination doit etre des notres. Un `hub_id`
        // etranger ne montre rien a l'autre societe (les listes restent
        // `companywise()`), il fait PIRE pour nous : le colis quitte toutes les
        // listes d'entrepot de la maison et devient introuvable a nos agents.
        if(blank(Hub::companywise()->find($request->hub_id))){
            return false;
        }
        // S45 — le livreur du transfert est FACULTATIF ici (le controleur n'exige
        // que `hub_id`) : on ne le garde donc que lorsqu'il est nomme.
        if(filled($request->delivery_man_id) && blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
            return false;
        }
        try {

            $transfertohub                           = new ParcelEvent();
            $transfertohub->parcel_id                = $id;
            $transfertohub->hub_id                   = $request->hub_id;
            $transfertohub->transfer_delivery_man_id = $request->delivery_man_id;
            $transfertohub->note                     = $request->note;
            $transfertohub->parcel_status            = ParcelStatus::TRANSFER_TO_HUB;
            $transfertohub->created_by               = Auth::user()->id;
            $transfertohub->save();
            $parcel                                  = Parcel::find($id);
            $parcel->transfer_hub_id                 = $request->hub_id;
            $parcel->status                          = ParcelStatus::TRANSFER_TO_HUB;
            $parcel->save();

            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }
 
    public function deliverymanAssign($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }

        // ...et le livreur nomme doit l'etre aussi : sinon une societe paie la
        // course d'un livreur qu'elle n'emploie pas, sur les colis d'une autre.
        if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
            return false;
        }

        try {
            $deliveyrman = DeliveryMan::find($request->delivery_man_id);

            // ⚠️ Un seul pretendant a la course. Le socle empilait les
            // affectations, alors que la reprogrammation, elle, effacait la
            // precedente. Les cinq endroits qui relisent cet evenement prennent
            // `->first()` : reaffecter un colis payait donc LE PREMIER livreur
            // nomme — credite de la course et debite d'un encaissement qu'il
            // n'avait jamais eu — pendant que celui qui avait livre ne touchait
            // rien. Meme geste que `deliveryReschedule()`.
            ParcelEvent::where('parcel_id',$id)->whereIn('parcel_status',[ParcelStatus::DELIVERY_MAN_ASSIGN,ParcelStatus::DELIVERY_RE_SCHEDULE])->delete();

            $deliverymanAssign                  = new ParcelEvent();
            $deliverymanAssign->parcel_id       = $id;
            $deliverymanAssign->delivery_man_id = $request->delivery_man_id;
            $deliverymanAssign->note            = $request->note;  
            $deliverymanAssign->delivery_lat   = $deliveyrman->delivery_lat;
            $deliverymanAssign->delivery_long  = $deliveyrman->delivery_long;
            $deliverymanAssign->parcel_status   = ParcelStatus::DELIVERY_MAN_ASSIGN;
            $deliverymanAssign->created_by      = Auth::user()->id;
            $deliverymanAssign->save();
            $parcel         = Parcel::find($id);
            $parcel->status = ParcelStatus::DELIVERY_MAN_ASSIGN;
            $parcel->save();
            if($request->send_sms == 'on'){

                $sms = SmsTemplate::forCompany($parcel->company_id);
                $msg = $sms->render('deliveryman_assigned_customer', [
                    'customer'    => $parcel->customer_name,
                    'tracking'    => $parcel->tracking_id,
                    'merchant'    => $parcel->merchant->business_name,
                    'agent'       => $deliverymanAssign->deliveryMan->user->name,
                    'agent_phone' => $deliverymanAssign->deliveryMan->user->mobile,
                    'url'         => url('/'),
                    'amount'      => $sms->amount($parcel->cash_collection),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->customer_phone,$msg);
            }

            try{
                $msgNotification = 'Dear '.$deliverymanAssign->deliveryMan->user->name.', your  parcel with ID '.$parcel->tracking_id .' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$deliverymanAssign->deliveryMan->user->email,$msgNotification,'deliveryMan');
            }catch (\Exception $exception){

            }

            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }


    public function deliveryReschedule($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }

        // ...et le livreur nomme doit l'etre aussi : sinon une societe paie la
        // course d'un livreur qu'elle n'emploie pas, sur les colis d'une autre.
        if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
            return false;
        }
        try {

            $deliveryManStatement                = ParcelEvent::where('parcel_id',$id)->whereIn('parcel_status',[parcelStatus::DELIVERY_MAN_ASSIGN,parcelStatus::DELIVERY_RE_SCHEDULE])->delete();

            $deliveryReschedule                  = new ParcelEvent();
            $deliveryReschedule->parcel_id       = $id;
            $deliveryReschedule->delivery_man_id = $request->delivery_man_id;
            $deliveryReschedule->note            = $request->note;
            $deliveryReschedule->parcel_status   = ParcelStatus::DELIVERY_RE_SCHEDULE;
            $deliveryReschedule->created_by      = Auth::user()->id;
            $deliveryReschedule->save();
            $parcel                = Parcel::find($id);
            $parcel->delivery_date = $request->date;
            $parcel->status        = ParcelStatus::DELIVERY_RE_SCHEDULE;
            $parcel->save();
            if($request->send_sms == 'on'){
                $sms = SmsTemplate::forCompany($parcel->company_id);
                $msg = $sms->render('delivery_rescheduled_customer', [
                    'customer'    => $parcel->customer_name,
                    'tracking'    => $parcel->tracking_id,
                    'merchant'    => $parcel->merchant->business_name,
                    'agent'       => $deliveryReschedule->deliveryMan->user->name,
                    'agent_phone' => $deliveryReschedule->deliveryMan->user->mobile,
                    'url'         => url('/'),
                    'amount'      => $sms->amount($parcel->cash_collection),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->customer_phone,$msg);
            }
            try{
                $msgNotification = 'Dear '.$deliveryReschedule->deliveryMan->user->name.', your  parcel with ID '.$parcel->tracking_id .' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$deliveryReschedule->deliveryMan->user->email,$msgNotification,'deliveryMan');
            }catch (\Exception $exception){

            }
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }


    public function receivedWarehouse($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }
        // S45 — l'entrepot de destination doit etre des notres. Un `hub_id`
        // etranger ne montre rien a l'autre societe (les listes restent
        // `companywise()`), il fait PIRE pour nous : le colis quitte toutes les
        // listes d'entrepot de la maison et devient introuvable a nos agents.
        if(blank(Hub::companywise()->find($request->hub_id))){
            return false;
        }
        try {

            DB::beginTransaction();
            $receivedWarehouse                 = new ParcelEvent();
            $receivedWarehouse->parcel_id      = $id;
            $receivedWarehouse->hub_id         = $request->hub_id;
            $receivedWarehouse->note           = $request->note;
            $receivedWarehouse->parcel_status  = ParcelStatus::RECEIVED_WAREHOUSE;
            $receivedWarehouse->created_by     = Auth::user()->id;
            $receivedWarehouse->save();
            $parcel                   = Parcel::find($id);
            $parcel->hub_id           = $request->hub_id;
            //pickup charge
            $pickupreschedule = ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::PICKUP_RE_SCHEDULE)->first();

            if($pickupreschedule){
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $pickupreschedule->pickupman->id;
                $deliveryManStatement->amount               = $pickupreschedule->pickupman->pickup_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.received_warehouse_deliveryman_statement');
                $deliveryManStatement->save();
                //pickup man balance add
                if($deliveryManStatement){
                    $pickupman                     = DeliveryMan::find($pickupreschedule->pickupman->id);
                    $pickupman->current_balance    = $pickupman->current_balance + $deliveryManStatement->amount;
                    $pickupman->save();
                }
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.received_warehouse_courier_statement');
                $courierStatement->save();
            }else{
                $pickupAssign=ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::PICKUP_ASSIGN)->first();
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $pickupAssign->pickupman->id;
                $deliveryManStatement->amount               = $pickupAssign->pickupman->pickup_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.received_warehouse_deliveryman_statement');
                $deliveryManStatement->save();
                //pickup man balance add
                if($deliveryManStatement){
                    $pickupman                     = DeliveryMan::find($pickupAssign->pickupman->id);
                    $pickupman->current_balance    = $pickupman->current_balance + $deliveryManStatement->amount;
                    $pickupman->save();
                }
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.received_warehouse_courier_statement');
                $courierStatement->save();
            }

            $parcel->status = ParcelStatus::RECEIVED_WAREHOUSE;
            $parcel->save();

            DB::commit();

            if($request->send_sms_customer == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('warehouse_received_customer', [
                    'customer' => $parcel->customer_name,
                    'tracking' => $parcel->tracking_id,
                    'merchant' => $parcel->merchant->business_name,
                    'url'      => url('/'),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);


            }

            if($request->send_sms_merchant  == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('warehouse_received_merchant', [
                    'merchant' => $parcel->merchant->business_name,
                    'tracking' => $parcel->tracking_id,
                    'hub'      => $receivedWarehouse->hub->name,
                    'url'      => url('/'),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->merchant->user->mobile, $msg);
            }

            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Received to Warehouse '.$receivedWarehouse->hub->name .'. Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }

            return true;
        } catch (\Throwable $th) {

            DB::rollBack();
            return false;
        }
    }


    /**
     * Annulation de la reception en entrepot.
     *
     * ⚠️ Le controle de statut n'entourait que la suppression de l'evenement :
     * les ecritures du ramasseur, elles, s'executaient a chaque appel. Annuler
     * deux fois lui reprenait sa course deux fois. La transaction, elle,
     * existait deja.
     */
    public function receivedWarehouseCancel($id,$request){

        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status !== ParcelStatus::RECEIVED_WAREHOUSE){
            return false;
        }

        try {
            DB::beginTransaction();
            $parcel = Parcel::find($id);
            if($parcel->status == ParcelStatus::RECEIVED_WAREHOUSE ){
                $pickupAsisgn  = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->first();
                ParcelEvent::destroy($pickupAsisgn->id);
            }

            $receivedPickupman = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::RECEIVED_BY_PICKUP_MAN])->first();
            $pickupreschedule   = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::PICKUP_RE_SCHEDULE])->first();
            $pickupAssign      = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::PICKUP_ASSIGN])->first();

            if($pickupreschedule){
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $pickupreschedule->pickupman->id;
                $deliveryManStatement->amount               = $pickupreschedule->pickupman->pickup_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.received_warehouse_deliveryman_statement_cancel');
                $deliveryManStatement->save();
                //pickup man balance add
                if($deliveryManStatement){
                    $pickupman                     = DeliveryMan::find($pickupreschedule->pickupman->id);
                    $pickupman->current_balance    = $pickupman->current_balance - $deliveryManStatement->amount;
                    $pickupman->save();
                }
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.received_warehouse_courier_statement_cancel');
                $courierStatement->save();
            }else{
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $pickupAssign->pickupman->id;
                $deliveryManStatement->amount               = $pickupAssign->pickupman->pickup_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.received_warehouse_deliveryman_statement_cancel');
                $deliveryManStatement->save();
                //pickup man balance
                if($deliveryManStatement){
                    $pickupman                     = DeliveryMan::find($pickupAssign->pickupman->id);
                    $pickupman->current_balance    = $pickupman->current_balance - $deliveryManStatement->amount;
                    $pickupman->save();
                }
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.received_warehouse_courier_statement_cancel');
                $courierStatement->save();
            }

            if($receivedPickupman){
                $parcel->status = ParcelStatus::RECEIVED_BY_PICKUP_MAN;
            }elseif($pickupreschedule){
                $parcel->status = ParcelStatus::PICKUP_RE_SCHEDULE;
            }else{
                $parcel->status = ParcelStatus::PICKUP_ASSIGN;
            }
            $parcel->save();
            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }




    public function returntoQourier($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }
        try {

            $returntocourier                = new ParcelEvent();
            $returntocourier->parcel_id     = $id;
            $returntocourier->note          = $request->note;

            // S106 (R8) — signature de celui qui reprend le colis, facultative,
            // même champ et même dossier que la livraison (`parcelDelivered`) :
            // le marchand la voit dans le suivi du colis (`parcel/logs`).
            if ($request->hasFile('signatureImage')) {
                $signatureImage     = $request->file('signatureImage');
                $destinationPath    = public_path('uploads/parcel/signature/');
                $signatureImageName = date('YmdHis') . uniqid() . rand(5, 10) . '.' . safeUploadExtension($signatureImage);
                $signatureImage->move($destinationPath, $signatureImageName);
                $returntocourier->signature_image = 'uploads/parcel/signature/' . $signatureImageName;
            }
            $returntocourier->parcel_status = ParcelStatus::RETURN_TO_COURIER;
            $returntocourier->created_by    = Auth::user()->id;
            $returntocourier->save();
            $parcel         = Parcel::find($id);
            $parcel->status = ParcelStatus::RETURN_TO_COURIER;
            $parcel->return_to_courier    = BooleanStatus::YES;
            $parcel->save();

            return true;
        } catch (\Throwable $th) {

            return false;
        }

    }


    public function returntoQourierCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::RETURN_TO_COURIER){
                $pickupAsisgn          = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
                $deliverymanReschedule = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=> ParcelStatus::DELIVERY_RE_SCHEDULE])->get();
            }
            if($deliverymanReschedule){
                $parcel->status   = ParcelStatus::DELIVERY_RE_SCHEDULE;
            }else{
                $parcel->status   = ParcelStatus::DELIVERY_MAN_ASSIGN;
            }
            $parcel->return_to_courier    = BooleanStatus::NO;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }




    public function returnAssignToMerchant($id,$request){
        try {
            DB::beginTransaction();

            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet ne voyait pas ce
            // chemin. On passait le colis d'un AUTRE transporteur en retour au
            // marchand, on debitait SON livreur du frais de retour, et le SMS de
            // retour partait a SON marchand.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                DB::rollBack();
                return false;
            }

            // S45 — S38 a ferme l'axe du COLIS ici et l'a inscrit comme prouve.
            // L'axe de l'AGENT restait ouvert, et c'est le plus cher : le solde du
            // livreur nomme est CREDITE du frais de retour, et deux releves portant
            // `company_id = settings()->id` sont attaches a son identifiant. Nomme
            // un livreur d'une autre societe, et nos livres payent son employe.
            if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
                DB::rollBack();
                return false;
            }

            $returnassigntomerchant                  = new ParcelEvent();
            $returnassigntomerchant->parcel_id       = $id;
            $returnassigntomerchant->delivery_man_id = $request->delivery_man_id;
            $returnassigntomerchant->note            = $request->note;
            $returnassigntomerchant->parcel_status   = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
            $returnassigntomerchant->created_by      = Auth::user()->id;
            $returnassigntomerchant->save();
            // Delivery man current balance update
            $deliveryMan                                = DeliveryMan::find($request->delivery_man_id);
            $deliveryMan->current_balance               = $deliveryMan->current_balance + $deliveryMan->return_charge;
            $deliveryMan->save();
            // Courier statement
            $deliveryManStatement                       = new DeliverymanStatement();
            $deliveryManStatement->company_id           = settings()->id;
            $deliveryManStatement->parcel_id            = $id;
            $deliveryManStatement->delivery_man_id      = $request->delivery_man_id;
            $deliveryManStatement->amount               = $deliveryMan->return_charge;
            $deliveryManStatement->type                 = StatementType::INCOME;
            $deliveryManStatement->date                 = date('Y-m-d H:i:s');
            $deliveryManStatement->note                 = __('statementNote.returned_to_merchant_income');
            $deliveryManStatement->save();
            // Courier statement
            $courierStatement                           = new CourierStatement();
            $courierStatement->company_id               = settings()->id;
            $courierStatement->parcel_id                = $id;
            $courierStatement->delivery_man_id          = $request->delivery_man_id;
            $courierStatement->amount                   = $deliveryMan->return_charge;
            $courierStatement->type                     = StatementType::EXPENSE;
            $courierStatement->date                     = date('Y-m-d H:i:s');
            $courierStatement->note                     = __('statementNote.returned_to_merchant_expense');
            $courierStatement->save();
            // End
            $parcel->delivery_date = $request->date;
            $parcel->status        = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
            $parcel->save();
            DB::commit();
            if($request->send_sms == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('returned_to_merchant', [
                    'merchant'    => $parcel->merchant->business_name,
                    'tracking'    => $parcel->tracking_id,
                    'agent'       => $returnassigntomerchant->deliveryMan->user->name,
                    'agent_phone' => $returnassigntomerchant->deliveryMan->user->mobile,
                    'url'         => url('/'),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', parcel with ID '.$parcel->tracking_id .' is return to you by '.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.'. visit:'.url('/').'  -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }

            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }

    }

    /**
     * Annulation de l'affectation d'un retour au marchand.
     *
     * Ses ecritures etaient deja enfermees dans le controle de statut, et la
     * transaction existait : seul le scope societe manquait.
     */
    public function returnAssignToMerchantCancel($id,$request){

        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status !== ParcelStatus::RETURN_ASSIGN_TO_MERCHANT){
            return false;
        }

        try {
            DB::beginTransaction();

            $parcel = Parcel::find($id);
            if($parcel->status == ParcelStatus::RETURN_ASSIGN_TO_MERCHANT){
                $event = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->first();
                ParcelEvent::destroy($event->id);
                // Delivery man current balance update
                $deliveryMan                                = DeliveryMan::find($event->delivery_man_id);
                $deliveryMan->current_balance               = $deliveryMan->current_balance - $deliveryMan->return_charge;
                $deliveryMan->save();
                // Courier statement
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $event->delivery_man_id;
                $deliveryManStatement->amount               = $deliveryMan->return_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.returned_to_merchant_expense_cancel');
                $deliveryManStatement->save();
                // Courier statement
                $courierStatement                           = new CourierStatement();
                $courierStatement->company_id               = settings()->id;
                $courierStatement->parcel_id                = $id;
                $courierStatement->delivery_man_id          = $event->delivery_man_id;
                $courierStatement->amount                   = $deliveryMan->return_charge;
                $courierStatement->type                     = StatementType::INCOME;
                $courierStatement->date                     = date('Y-m-d H:i:s');
                $courierStatement->note                     = __('statementNote.returned_to_merchant_income_cancel');
                $courierStatement->save();
                // End

            }
            $parcel->status = ParcelStatus::RETURN_TO_COURIER;
            $parcel->save();

            DB::commit();
            return true;
        } catch (\Throwable $th) {

            DB::rollBack();
            return false;
        }
    }

    public function returnAssignToMerchantReschedule($id,$request){

        // Le colis doit etre chez nous. Le socle lisait `Parcel::find($id)` nu :
        // un administrateur faisait avancer le colis d'un autre transporteur —
        // et designait au passage qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis)){
            return false;
        }
        // S45 — l'agent nomme doit etre des notres. Le colis etait garde ; celui
        // qu'on PAIE pour le mouvement ne l'etait pas : `DeliveryMan::find()` nu
        // creditait le solde d'un livreur d'une AUTRE societe et attachait nos
        // releves a son identifiant.
        if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
            return false;
        }
        try {

            $returnassigntomerchant                  = new ParcelEvent();
            $returnassigntomerchant->parcel_id       = $id;
            $returnassigntomerchant->delivery_man_id = $request->delivery_man_id;
            $returnassigntomerchant->note            = $request->note;
            $returnassigntomerchant->parcel_status   = ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE;
            $returnassigntomerchant->created_by      = Auth::user()->id;
            $returnassigntomerchant->save();
            $parcel                = Parcel::find($id);
            $parcel->delivery_date = $request->date;
            $parcel->status        = ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE;
            $parcel->save();

            if($request->send_sms == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('returned_to_merchant', [
                    'merchant'    => $parcel->merchant->business_name,
                    'tracking'    => $parcel->tracking_id,
                    'agent'       => $returnassigntomerchant->deliveryMan->user->name,
                    'agent_phone' => $returnassigntomerchant->deliveryMan->user->mobile,
                    'url'         => url('/'),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }

    }

    public function returnAssignToMerchantRescheduleCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE){
                $merchantReschedule = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
            }
            $parcel->status  = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    /**
     * Retour recu par le marchand — l'etape facture au marchand le retour de
     * son colis (`merchants.return_charges` est un POURCENTAGE du tarif de
     * livraison) et paie au livreur sa course de retour.
     *
     * Elle deplace donc de l'argent reel, et rejoint le 2026-09-07 les quatre
     * etapes deja mises aux normes de **D8** — elle n'en faisait pas partie :
     *
     * 1. **Une seule fois.** Aucun controle de statut : rappeler l'etape
     *    debitait le marchand une seconde fois du meme retour.
     * 2. **Chez soi.** Deja acquis (`companywise()`), au contraire de
     *    l'annulation qui lisait encore `Parcel::find($id)` nu.
     * 3. **Tout ou rien.** Les trois jeux d'ecritures — marchand, livreur,
     *    transporteur — s'ecrivaient hors transaction.
     */
    public function returnReceivedByMerchant($id,$request){

        // Le colis doit etre chez nous, et ne pas avoir deja ete rendu. Le
        // socle lisait `Parcel::find($id)` nu : un administrateur faisait
        // avancer le colis d'un autre transporteur — et designait au passage
        // qui y serait paye.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status === ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
            return false;
        }
        try {
            return DB::transaction(function () use ($id, $request) {
            $returnReceived                 = new ParcelEvent();
            $returnReceived->parcel_id      = $id;
            $returnReceived->note           = $request->note;
            $returnReceived->parcel_status  = ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
            $returnReceived->created_by     = Auth::user()->id;
            $returnReceived->save();
            $parcel                         = Parcel::find($id);
            //delivery charge
            $reSceduleDeliveryman           = ParcelEvent::Where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_RE_SCHEDULE)->first();
            if($reSceduleDeliveryman){
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                $deliveryManStatement->amount               = $reSceduleDeliveryman->deliveryMan->return_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.return_to_merchant_deliveryman_statement');
                $deliveryManStatement->save();
                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                        = DeliveryMan::find($reSceduleDeliveryman->deliveryMan->id);
                    $deliveryMan->current_balance       = ($deliveryMan->current_balance + $deliveryManStatement->amount);
                    $deliveryMan->save();
                }
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.return_to_merchant_courier_statement');
                $courierStatement->save();
            }else{
                $deliveryManAssign=ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->first();
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = $deliveryManAssign->deliveryMan->return_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.return_to_merchant_deliveryman_statement');
                $deliveryManStatement->save();
                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                            = DeliveryMan::find($deliveryManAssign->deliveryMan->id);
                    $deliveryMan->current_balance           = ($deliveryMan->current_balance + $deliveryManStatement->amount);
                    $deliveryMan->save();
                }

                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.return_to_merchant_courier_statement');
                $courierStatement->save();


            }
            //total delivery charge
            $merchant=Merchant::find($parcel->merchant_id);
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = ($parcel->delivery_charge / 100) * $merchant->return_charges;
            $merchantStatement->type             = StatementType::EXPENSE;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.return_received_by_merchant_statment');
            $merchantStatement->save();
            // total charge minus from merchant current balance
            $merchantCost=Merchant::find($parcel->merchant_id);
            //return delivery charge calculation
            $return_delivery_charge = ($parcel->delivery_charge / 100) * $merchantCost->return_charges;
            //end return  delivery charge calculation

            // D2, question 6 (S73) : le retour est une prestation TAXABLE. La TVA
            // suit le frais, au franc, sur sa propre ligne — comme la livraison
            // écrit sa TVA à part — et s'inscrit sur le colis pour que le relevé
            // facture ce qui a été prélevé, jamais un recalcul.
            $tvaRetour = \App\Services\Parcel\ReturnVat::montant($parcel, $merchantCost, (float) $return_delivery_charge);
            if ($tvaRetour > 0) {
                $tvaStatement                   = new MerchantStatement();
                $tvaStatement->company_id       = settings()->id;
                $tvaStatement->merchant_id      = $parcel->merchant_id;
                $tvaStatement->parcel_id        = $id;
                $tvaStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
                $tvaStatement->amount           = $tvaRetour;
                $tvaStatement->type             = StatementType::EXPENSE;
                $tvaStatement->date             = date('Y-m-d H:i:s');
                $tvaStatement->note             = __('statementNote.return_vat_merchant_statement');
                $tvaStatement->save();

                $vat                            = new VatStatement();
                $vat->company_id                = settings()->id;
                $vat->parcel_id                 = $id;
                $vat->amount                    = $tvaRetour;
                $vat->type                      = StatementType::INCOME;
                $vat->date                      = date('Y-m-d H:i:s');
                $vat->note                      = __('statementNote.return_vat_merchant_statement');
                $vat->save();
            }

            $current=((double)$merchantCost->current_balance - $return_delivery_charge - $tvaRetour);
            $merchantCost->current_balance = $current;
            $merchantCost->save();
            //end merchant expense vat + total charge amount
            //courier statement
            $courier_statement                  = new CourierStatement();
            $courier_statement->company_id      = settings()->id;
            $courier_statement->parcel_id       = $id;
            $courier_statement->delivery_man_id = $merchantStatement->delivery_man_id;
            $courier_statement->amount          = $return_delivery_charge;
            $courier_statement->type            = StatementType::INCOME;
            $courier_statement->date            = date('Y-m-d H:i:s');
            $courier_statement->note            = __('statementNote.return_received_by_statement');
            $courier_statement->save();
            $parcel->return_charges = $return_delivery_charge;
            $parcel->return_vat_amount = $tvaRetour;
            $parcel->status = ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
            $parcel->save();

            return true;

            });
        } catch (\Throwable $th) {
            Log::error('Retour recu par le marchand abandonne', ['parcel_id' => $id, 'message' => $th->getMessage()]);

            return false;
        }

    }

    /**
     * Annulation du retour recu — le miroir de l'etape ci-dessus.
     *
     * Le socle n'inversait **rien** : il supprimait l'evenement et reculait le
     * statut. Le marchand restait debite de son frais de retour, le livreur
     * gardait sa course, le transporteur gardait son produit. Or reception du
     * retour → annulation → reception a nouveau tient en deux clics dans le
     * back-office : le marchand payait alors deux fois le meme retour, sans
     * qu'aucun ecran ne le dise.
     *
     * Deux choix, pris comme ailleurs dans le socle :
     *
     * - **on inverse, on n'efface pas.** Chaque mouvement recoit sa
     *   contrepartie de sens oppose, comme `parcelDeliveredCancel`. Les
     *   releves restent lisibles pour qui veut comprendre apres coup ;
     * - **on rend ce qui a ete preleve, pas ce qu'on recalculerait.** Le
     *   montant vient de `parcels.return_charges`, ecrit au moment du retour,
     *   jamais d'un nouveau calcul : `merchants.return_charges` est un
     *   pourcentage qui peut avoir change entre-temps, et un taux revise
     *   laisserait un residu au marchand. C'est la lecon des 14,40 F de la
     *   livraison partielle.
     *
     * Le frais est aussi **efface du colis** : le releve rassemble les colis en
     * retour par statut — `RETURN_ASSIGN_TO_MERCHANT` en fait partie — et
     * facture `parcels.return_charges`. Le laisser en place aurait rendu
     * l'argent au solde pour le reprendre au prochain releve.
     */
    public function returnReceivedByMerchantCancel($id,$request){

        // On n'annule que ce qui a eu lieu, et seulement chez nous.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status !== ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
            return false;
        }

        try {
            return DB::transaction(function () use ($id) {

            $parcel       = Parcel::find($id);
            $pickupAsisgn = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->first();
            if($pickupAsisgn){
                ParcelEvent::destroy($pickupAsisgn->id);
            }

            // La course de retour reprise au livreur — celui-la meme que
            // l'aller a paye, retrouve par le meme chemin.
            $reSceduleDeliveryman = ParcelEvent::Where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_RE_SCHEDULE)->first();
            $affectation = $reSceduleDeliveryman
                ?: ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->first();

            $livreur = $affectation->deliveryMan;

            $deliveryManStatement                       = new DeliverymanStatement();
            $deliveryManStatement->company_id           = settings()->id;
            $deliveryManStatement->parcel_id            = $id;
            $deliveryManStatement->delivery_man_id      = $livreur->id;
            $deliveryManStatement->amount               = $livreur->return_charge;
            $deliveryManStatement->type                 = StatementType::EXPENSE;
            $deliveryManStatement->date                 = date('Y-m-d H:i:s');
            $deliveryManStatement->note                 = __('statementNote.return_to_merchant_deliveryman_statement');
            $deliveryManStatement->save();

            $deliveryMan                                = DeliveryMan::find($livreur->id);
            $deliveryMan->current_balance               = $deliveryMan->current_balance - $deliveryManStatement->amount;
            $deliveryMan->save();

            $courierStatement                       = new CourierStatement();
            $courierStatement->company_id           = settings()->id;
            $courierStatement->parcel_id            = $id;
            $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
            $courierStatement->amount               = $deliveryManStatement->amount;
            $courierStatement->type                 = StatementType::INCOME;
            $courierStatement->date                 = date('Y-m-d H:i:s');
            $courierStatement->note                 = __('statementNote.return_to_merchant_deliveryman_statement');
            $courierStatement->save();

            // Le frais de retour rendu au marchand — au montant preleve.
            $return_delivery_charge = (double) $parcel->return_charges;

            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $return_delivery_charge;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             = date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.return_received_by_merchant_statment');
            $merchantStatement->save();

            // ... et sa TVA (D2 q.6, S73), au montant prélevé lui aussi.
            $tvaRetour = (float) $parcel->return_vat_amount;
            if ($tvaRetour > 0) {
                $tvaStatement                   = new MerchantStatement();
                $tvaStatement->company_id       = settings()->id;
                $tvaStatement->merchant_id      = $parcel->merchant_id;
                $tvaStatement->parcel_id        = $id;
                $tvaStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
                $tvaStatement->amount           = $tvaRetour;
                $tvaStatement->type             = StatementType::INCOME;
                $tvaStatement->date             = date('Y-m-d H:i:s');
                $tvaStatement->note             = __('statementNote.return_vat_merchant_statement');
                $tvaStatement->save();

                $vat                            = new VatStatement();
                $vat->company_id                = settings()->id;
                $vat->parcel_id                 = $id;
                $vat->amount                    = $tvaRetour;
                $vat->type                      = StatementType::EXPENSE;
                $vat->date                      = date('Y-m-d H:i:s');
                $vat->note                      = __('statementNote.return_vat_merchant_statement');
                $vat->save();
            }

            $merchantCost                  = Merchant::find($parcel->merchant_id);
            $merchantCost->current_balance = ((double) $merchantCost->current_balance + $return_delivery_charge + $tvaRetour);
            $merchantCost->save();

            $courier_statement                  = new CourierStatement();
            $courier_statement->company_id      = settings()->id;
            $courier_statement->parcel_id       = $id;
            $courier_statement->delivery_man_id = $merchantStatement->delivery_man_id;
            $courier_statement->amount          = $return_delivery_charge;
            $courier_statement->type            = StatementType::EXPENSE;
            $courier_statement->date            = date('Y-m-d H:i:s');
            $courier_statement->note            = __('statementNote.return_received_by_statement_cancel');
            $courier_statement->save();

            $returnreschedule     = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE])->first();
            if($returnreschedule){
                $parcel->status   = ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE;
            }else{
                $parcel->status   = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
            }
            // Sans quoi le prochain releve refacturerait le retour annule.
            $parcel->return_charges = 0;
            $parcel->return_vat_amount = 0;
            $parcel->save();

            return true;

            });
        } catch (\Throwable $th) {
            Log::error('Annulation de retour recu abandonnee', ['parcel_id' => $id, 'message' => $th->getMessage()]);

            return false;
        }
    }

    /**
     * Livraison d'un colis — l'etape la plus lourde du socle sur le plan
     * comptable : elle deplace quatre jeux de comptes d'un coup (marchand,
     * livreur, transporteur, TVA).
     *
     * Trois garanties, ajoutees le 2026-09-05 en la couvrant de tests, et qui
     * ne font qu'appliquer ici des decisions deja prises ailleurs :
     *
     * 1. **Une seule fois.** Le socle ne verifiait pas le statut : relivrer le
     *    meme colis doublait TOUS les comptes — marchand credite deux fois du
     *    meme encaissement, livreur endette deux fois, TVA declaree en double.
     *    Ce n'est pas theorique : l'app livreur appelle cette etape
     *    (`POST deliveryman/parcel/delivered/{id}`) et un renvoi sur une
     *    connexion instable suffit. Meme defaut que F1 sur les recharges.
     * 2. **Chez soi.** `Parcel::find($id)` nu laissait un administrateur
     *    crediter le marchand d'une AUTRE societe en changeant l'identifiant
     *    poste. Meme correction que W1 sur le statut de colis.
     * 3. **Tout ou rien.** Les quatre jeux d'ecritures s'ecrivaient hors
     *    transaction : un incident au milieu laissait des livres a moitie
     *    faits, impossibles a rattraper puisque relancer l'etape doublait
     *    l'autre moitie.
     *
     * Les notifications sortent de la transaction : une passerelle SMS en
     * panne ne doit pas defaire une livraison qui a eu lieu — c'est deja la
     * regle posee pour la validation des recharges.
     */
    public function parcelDelivered($id,$request){

        // Le colis doit etre chez nous, et ne pas etre deja livre.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status === ParcelStatus::DELIVERED){
            return false;
        }

        try {
            $parcel = DB::transaction(function () use ($id, $request) {

            $parcelDelivered                = new ParcelEvent();
            $parcelDelivered->parcel_id     = $id;
            $parcelDelivered->note          = $request->note;

            // `hasFile()` plutôt que `$_FILES` : même comportement en production,
            // et la requête reste testable (le superglobal n'y est pas alimenté).
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $destinationPath   = public_path('uploads/parcel/image/');
                $imageName         = date('YmdHis') .uniqid() . rand(5, 10).".".safeUploadExtension($image);
                $image->move($destinationPath, $imageName);
                $delivered_image            = 'uploads/parcel/image/'.$imageName;
                $parcelDelivered->delivered_image  = $delivered_image;

            }

            if ($request->hasFile('signatureImage')) {
                $signatureImage = $request->file('signatureImage');
                $destinationPath   = public_path('uploads/parcel/signature/');
                $signatureImageName         = date('YmdHis') .uniqid() . rand(5, 10).".".safeUploadExtension($signatureImage);
                $signatureImage->move($destinationPath, $signatureImageName);
                $signature_image            = 'uploads/parcel/signature/'.$signatureImageName;
                $parcelDelivered->signature_image  = $signature_image;

            }

            $parcelDelivered->parcel_status    = ParcelStatus::DELIVERED;

            $parcelDelivered->created_by    = Auth::user()->id;
            $parcelDelivered->save();
            $parcel                         = Parcel::find($id);
            //delivery charge
            $reSceduleDeliveryman           = ParcelEvent::Where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_RE_SCHEDULE)->get()->last();

            if($reSceduleDeliveryman){
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                $deliveryManStatement->amount               = $reSceduleDeliveryman->deliveryMan->delivery_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();
                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                        = DeliveryMan::find($reSceduleDeliveryman->deliveryMan->id);
                    $deliveryMan->current_balance       = $deliveryMan->current_balance + $deliveryManStatement->amount;
                    $deliveryMan->save();
                }

                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $courierStatement->save();
                //cash collection income from customer for store (- amount)
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                $deliveryManStatement->amount               = ($parcel->cash_collection);
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //cash collection added  delivery man balance
                $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance + (- $parcel->cash_collection);
                $deliveryManBalance->save();
            }else{

                $deliveryManAssign=ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->first();

                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = $deliveryManAssign->deliveryMan->delivery_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();
                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                            = DeliveryMan::find($deliveryManAssign->deliveryMan->id);
                    $deliveryMan->current_balance           = $deliveryMan->current_balance + $deliveryManStatement->amount;
                    $deliveryMan->save();
                }
                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::EXPENSE;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $courierStatement->save();
                //cash collection income from customer for store (- amount)
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = ($parcel->cash_collection);
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();
                //cash collection added  delivery man balance
                $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance + (- $parcel->cash_collection);
                $deliveryManBalance->save();

            }


            //merchant statment
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $parcel->cash_collection;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();

            //merchant balance add
            if($merchantStatement){
                $merchant=Merchant::find($parcel->merchant_id);
                $merchant->current_balance = $merchant->current_balance + $parcel->cash_collection;
                $merchant->save();
            }

            //merchant expense vat + total charge amount
            //total delivery charge
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $parcel->total_delivery_amount;
            $merchantStatement->type             = StatementType::EXPENSE;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();

            //vat
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $parcel->vat_amount;
            $merchantStatement->type             = StatementType::EXPENSE;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();

            //vat and total charge minus from merchant current balance
            $deliveryCost = $parcel->total_delivery_amount + $parcel->vat_amount;
            $merchantCost=Merchant::find($parcel->merchant_id);
            $merchantCost->current_balance = $merchantCost->current_balance - $deliveryCost;
            $merchantCost->save();

            //end merchant expense vat + total charge amount


            //courier statement
            $courier_statement                  = new CourierStatement();
            $courier_statement->company_id      = settings()->id;
            $courier_statement->parcel_id       = $id;
            $courier_statement->delivery_man_id = $merchantStatement->delivery_man_id;
            $courier_statement->amount          = $parcel->total_delivery_amount;
            $courier_statement->type            = StatementType::INCOME;
            $courier_statement->date            = date('Y-m-d H:i:s');
            $courier_statement->note            = __('statementNote.delivered_merchant_courier_statement');
            $courier_statement->save();

            // Vat statement
            $vat                            = new VatStatement();
            $vat->company_id                = settings()->id;
            $vat->parcel_id                 = $id;
            $vat->amount                    = $parcel->vat_amount;
            $vat->type                      = StatementType::INCOME;
            $vat->date                      = date('Y-m-d H:i:s');
            $vat->note                      = __('parcel.delivered_success');
            $vat->save();

            $parcel->status = ParcelStatus::DELIVERED;
            $parcel->priority_type_id = 2;
            $parcel->save();

            return $parcel;

            });
        } catch (\Throwable $th) {
            // Les livres sont annules en entier : rien n'a bouge, l'etape peut
            // etre relancee telle quelle.
            Log::error('Livraison annulee', ['parcel_id' => $id, 'message' => $th->getMessage()]);

            return false;
        }

        // A partir d'ici la livraison est acquise. Ce qui suit previent le
        // monde exterieur, et ne peut plus la defaire.
        try {
            if($request->send_sms_customer == 'on') {

                $msg = SmsTemplate::forCompany($parcel->company_id)->render('delivered_customer', [
                    'customer' => $parcel->customer_name,
                    'tracking' => $parcel->tracking_id,
                    'url'      => url('/'),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);
            }
            if($request->send_sms_merchant  == 'on'){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('delivered_merchant', [
                    'merchant' => $parcel->merchant->business_name,
                    'tracking' => $parcel->tracking_id,
                    'customer' => $parcel->customer_name,
                    'phone'    => $parcel->customer_phone,
                    'url'      => url('/'),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear Merchant, your  parcel with ID '.$parcel->tracking_id .' is successfully delivered. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }
        } catch (\Throwable $th) {
            // Notification en echec : on le note, on ne defait pas la livraison.
            Log::warning('Livraison notifiee en echec', ['parcel_id' => $id, 'message' => $th->getMessage()]);
        }

        return true;
    }



    /**
     * Annulation d'une livraison — le miroir de `parcelDelivered()`.
     *
     * Elle porte les memes obligations que l'etape qu'elle inverse (D8), et le
     * socle n'en tenait aucune. Deux consequences, constatees avant correction :
     *
     * 1. **Annuler deux fois inversait deux fois** : le marchand se retrouvait
     *    debite d'un encaissement qu'il n'avait jamais eu, le livreur credite
     *    d'une course qu'il n'avait pas faite.
     * 2. **Annuler une livraison qui n'a jamais eu lieu** ecrivait la
     *    contrepartie dans le vide. C'est plus grave qu'une livraison en
     *    double : rien ne signalait qu'il n'y avait rien a annuler.
     */
    public function parcelDeliveredCancel($id,$request){

        // On n'annule que ce qui a eu lieu, et seulement chez nous.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status !== ParcelStatus::DELIVERED){
            return false;
        }

        try {
            $parcel = DB::transaction(function () use ($id) {

            $parcel                                            = Parcel::find($id);
            if($parcel->status == ParcelStatus::DELIVERED ){
                $pickupAsisgn = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->first();

                ParcelEvent::destroy($pickupAsisgn->id);
            }

            $reSceduleDeliveryman = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::DELIVERY_RE_SCHEDULE])->first();


            if($reSceduleDeliveryman){

                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                $deliveryManStatement->amount               = $reSceduleDeliveryman->deliveryMan->delivery_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                        = DeliveryMan::find($reSceduleDeliveryman->deliveryMan->id);
                    $deliveryMan->current_balance       = $deliveryMan->current_balance - $deliveryManStatement->amount;
                    $deliveryMan->save();
                }

                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $courierStatement->save();

                //cash collection income from customer for store (- amount)
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                $deliveryManStatement->amount               = ($parcel->cash_collection);
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //cash collection added  delivery man balance
                $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance +   $parcel->cash_collection;
                $deliveryManBalance->save();

            }else{

                $deliveryManAssign=ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->first();

                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = $deliveryManAssign->deliveryMan->delivery_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                            = DeliveryMan::find($deliveryManAssign->deliveryMan->id);
                    $deliveryMan->current_balance           = $deliveryMan->current_balance - $deliveryManStatement->amount;
                    $deliveryMan->save();
                }

                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $courierStatement->save();

                //cash collection income from customer for store (- amount)
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = ($parcel->cash_collection);
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();
                //cash collection added  delivery man balance
                $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance +   $parcel->cash_collection;
                $deliveryManBalance->save();

            }


            //merchant statment
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $parcel->cash_collection;
            $merchantStatement->type             = StatementType::EXPENSE;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();

            //merchant balance add
            if($merchantStatement){
                $merchant=Merchant::find($parcel->merchant_id);
                $merchant->current_balance = $merchant->current_balance - $parcel->cash_collection;
                $merchant->save();
            }

            //merchant expense vat + total charge amount
            //total delivery charge
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $parcel->total_delivery_amount;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();

            //vat
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $parcel->vat_amount;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();

            //vat and total charge minus from merchant current balance
            $deliveryCost = $parcel->total_delivery_amount + $parcel->vat_amount;
            $merchantCost=Merchant::find($parcel->merchant_id);
            $merchantCost->current_balance = $merchantCost->current_balance + $deliveryCost;
            $merchantCost->save();

            //end merchant expense vat + total charge amount


            //courier statement
            $courier_statement                  = new CourierStatement();
            $courier_statement->company_id      = settings()->id;
            $courier_statement->parcel_id       = $id;
            $courier_statement->delivery_man_id = $merchantStatement->delivery_man_id;
            $courier_statement->amount          = $parcel->total_delivery_amount;
            $courier_statement->type            = StatementType::EXPENSE;
            $courier_statement->date            = date('Y-m-d H:i:s');
            $courier_statement->note            = __('statementNote.delivered_merchant_courier_statement');
            $courier_statement->save();

            // Vat statement
            $vat                            = new VatStatement();
            $vat->company_id                = settings()->id;
            $vat->parcel_id                 = $id;
            $vat->amount                    = $parcel->vat_amount;
            $vat->type                      = StatementType::EXPENSE;
            $vat->date                      = date('Y-m-d H:i:s');
            $vat->note                      = __('parcel.delivered_success');
            $vat->save();




            $dreschedule                        = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::DELIVERY_RE_SCHEDULE])->get();
            if(count($dreschedule) > 0 ){
                $parcel->status = ParcelStatus::DELIVERY_RE_SCHEDULE;
            }else{
                $parcel->status = ParcelStatus::DELIVERY_MAN_ASSIGN;
            }
            $parcel->save();

            return $parcel;

            });
        } catch (\Throwable $th) {
            Log::error('Annulation de livraison annulee', ['parcel_id' => $id, 'message' => $th->getMessage()]);

            return false;
        }

        // A partir d'ici l'annulation est acquise.
        try {
            if(SmsSendSettingHelper(SmsSendStatus::DELIVERED_CANCEL_CUSTOMER)) {
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('delivery_cancelled_customer', [
                    'customer' => $parcel->customer_name,
                    'tracking' => $parcel->tracking_id,
                    'merchant' => $parcel->merchant->business_name,
                    'url'      => url('/'),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);
            }

            if(SmsSendSettingHelper(SmsSendStatus::DELIVERED_CANCEL_MERCHANT)){
                $msg = SmsTemplate::forCompany($parcel->company_id)->render('delivery_cancelled_merchant', [
                    'merchant' => $parcel->merchant->business_name,
                    'tracking' => $parcel->tracking_id,
                    'customer' => $parcel->customer_name,
                    'phone'    => $parcel->customer_phone,
                    'url'      => url('/'),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .'  Delivered cancel. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }
        } catch (\Throwable $th) {
            Log::warning('Annulation de livraison notifiee en echec', ['parcel_id' => $id, 'message' => $th->getMessage()]);
        }

        return true;
    }


    /**
     * Livraison partielle — le client ne prend qu'une partie du colis et paie
     * moins que prevu.
     *
     * C'est la seule etape qui **recalcule les frais** au moment de la
     * livraison : les frais COD suivent la somme reellement encaissee, la TVA
     * suit les frais, et le montant convenu au depart est conserve dans
     * `old_cash_collection`.
     *
     * Memes garanties que `parcelDelivered()`, et pour les memes raisons : une
     * seule fois, chez soi, tout ou rien, notifications hors transaction. Ici
     * la relance etait meme plus perverse — elle recalculait les frais sur le
     * montant DEJA reduit.
     */
    public function parcelPartialDelivered($id,$request){
        // Toutes les entrées, même une invocation directe du repository, portent la même règle XOF.
        if (Validator::make($request->all(), (new PartialDeliveryRequest())->rules())->fails()) {
            return false;
        }

        // Le colis doit etre chez nous, et son sort ne doit pas etre deja scelle.
        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || in_array((int) $colis->status, [ParcelStatus::PARTIAL_DELIVERED, ParcelStatus::DELIVERED], true)){
            return false;
        }

        try {
            $parcel = DB::transaction(function () use ($id, $request) {

            // Parcel Event
            $parcelPartialDelivered                     = new ParcelEvent();
            $parcelPartialDelivered->parcel_id          = $id;
            $parcelPartialDelivered->note               = $request->note;
            $parcelPartialDelivered->parcel_status      = ParcelStatus::PARTIAL_DELIVERED;
        
            $parcelPartialDelivered->created_by         = Auth::user()->id;
            $parcelPartialDelivered->save();
            $parcel                           = Parcel::find($id);
            //calculations
            $cod_charges_amount           = round(($request->cash_collection / 100) * $parcel->cod_charge);
            $total_charges                = (
                $cod_charges_amount             +
                $parcel->delivery_charge        +
                $parcel->liquid_fragile_amount  +
                $parcel->packaging_amount
            );
            $vat_amount                   = round(($total_charges/100) * $parcel->vat);
            $chargeWithVat                = ($total_charges+$vat_amount);
            $totaldeliveryAmount          = ($request->cash_collection - $chargeWithVat);
            $current_payable              = ($request->cash_collection - $totaldeliveryAmount);
            //store
            $parcel->cod_amount            = $cod_charges_amount;
            $parcel->vat_amount            = $vat_amount;
            $parcel->total_delivery_amount = $total_charges;
            $parcel->current_payable       = $current_payable;

            // Prcel
            $parcel->status                             = ParcelStatus::PARTIAL_DELIVERED;
            $parcel->priority_type_id                   = 2;
            $parcel->old_cash_collection                = $parcel->cash_collection;
            $parcel->cash_collection                    = $request->cash_collection;
            $parcel->current_payable                    = $request->cash_collection - $chargeWithVat;
            $parcel->partial_delivered                  = BooleanStatus::YES;
            $parcel->save();

            if($parcel){

                //delivery charge
                $reSceduleDeliveryman            = ParcelEvent::Where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_RE_SCHEDULE)->first();
                if($reSceduleDeliveryman){

                    $deliveryManStatement                       = new DeliverymanStatement();
                    $deliveryManStatement->company_id           = settings()->id;
                    $deliveryManStatement->parcel_id            = $id;
                    $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                    $deliveryManStatement->amount               = $reSceduleDeliveryman->deliveryMan->delivery_charge;
                    $deliveryManStatement->type                 = StatementType::INCOME;
                    $deliveryManStatement->cash_collection      = 1;
                    $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                    $deliveryManStatement->note                 = __('statementNote.partial_delivered_deliveryman_statement');
                    $deliveryManStatement->save();

                    //delivery man balance add
                    if($deliveryManStatement){
                        $deliveryMan                            = DeliveryMan::find($reSceduleDeliveryman->deliveryMan->id);
                        $deliveryMan->current_balance           = $deliveryMan->current_balance + $deliveryManStatement->amount;
                        $deliveryMan->save();
                    }

                    $courierStatement                       = new CourierStatement();
                    $courierStatement->company_id           = settings()->id;
                    $courierStatement->parcel_id            = $id;
                    $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                    $courierStatement->amount               = $deliveryManStatement->amount;
                    $courierStatement->type                 = StatementType::EXPENSE;
                    $courierStatement->date                 = date('Y-m-d H:i:s');
                    $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                    $courierStatement->save();


                    //cash collection income from customer for store (- amount)
                    $deliveryManStatement                       = new DeliverymanStatement();
                    $deliveryManStatement->company_id           = settings()->id;
                    $deliveryManStatement->parcel_id            = $id;
                    $deliveryManStatement->delivery_man_id      = $reSceduleDeliveryman->deliveryMan->id;
                    $deliveryManStatement->amount               = ($parcel->cash_collection);
                    $deliveryManStatement->cash_collection      = 1;
                    $deliveryManStatement->type                 = StatementType::EXPENSE;
                    $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                    $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                    $deliveryManStatement->save();

                    //cash collection added  delivery man balance
                    $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                    $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance + (- $parcel->cash_collection);
                    $deliveryManBalance->save();

                }else{

                    $deliveryManAssign=ParcelEvent::where('parcel_id',$id)->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->first();
                    $deliveryManStatement                       = new DeliverymanStatement();
                    $deliveryManStatement->company_id           = settings()->id;
                    $deliveryManStatement->parcel_id            = $id;
                    $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                    $deliveryManStatement->amount               = $deliveryManAssign->deliveryMan->delivery_charge;
                    $deliveryManStatement->type                 = StatementType::INCOME;
                    $deliveryManStatement->cash_collection      = 1;
                    $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                    $deliveryManStatement->note                 = __('statementNote.partial_delivered_deliveryman_statement');
                    $deliveryManStatement->save();

                    //delivery man balance add
                    if($deliveryManStatement){
                        $deliveryMan                            = DeliveryMan::find($deliveryManAssign->deliveryMan->id);
                        $deliveryMan->current_balance           = $deliveryMan->current_balance + $deliveryManStatement->amount;
                        $deliveryMan->save();
                    }

                    $courierStatement                       = new CourierStatement();
                    $courierStatement->company_id           = settings()->id;
                    $courierStatement->parcel_id            = $id;
                    $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                    $courierStatement->amount               = $deliveryManStatement->amount;
                    $courierStatement->type                 = StatementType::EXPENSE;
                    $courierStatement->date                 = date('Y-m-d H:i:s');
                    $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                    $courierStatement->save();

                    //cash collection income from customer for store (- amount)
                    $deliveryManStatement                       = new DeliverymanStatement();
                    $deliveryManStatement->company_id           = settings()->id;
                    $deliveryManStatement->parcel_id            = $id;
                    $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                    $deliveryManStatement->amount               = ($parcel->cash_collection);
                    $deliveryManStatement->cash_collection      = 1;
                    $deliveryManStatement->type                 = StatementType::EXPENSE;
                    $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                    $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                    $deliveryManStatement->save();

                    //cash collection added  delivery man balance
                    $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                    $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance + (- $parcel->cash_collection);
                    $deliveryManBalance->save();

                }


                //merchant statment
                $merchantStatement                   = new MerchantStatement();
                $merchantStatement->company_id       = settings()->id;
                $merchantStatement->merchant_id      = $parcel->merchant_id;
                $merchantStatement->parcel_id        = $id;
                $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
                $merchantStatement->amount           = $parcel->cash_collection;
                $merchantStatement->type             = StatementType::INCOME;
                $merchantStatement->date             =  date('Y-m-d H:i:s');
                $merchantStatement->note             = __('statementNote.partial_delivered_merchant_statment');
                $merchantStatement->save();

                //merchant balance add
                if($merchantStatement){
                    $merchant=Merchant::find($parcel->merchant_id);
                    $merchant->current_balance = $merchant->current_balance + $parcel->cash_collection;
                    $merchant->save();
                }

                //merchant expense vat + total charge amount

                //total delivery charge
                $merchantStatement                   = new MerchantStatement();
                $merchantStatement->company_id       = settings()->id;
                $merchantStatement->merchant_id      = $parcel->merchant_id;
                $merchantStatement->parcel_id        = $id;
                $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
                $merchantStatement->amount           = $parcel->total_delivery_amount;
                $merchantStatement->type             = StatementType::EXPENSE;
                $merchantStatement->date             =  date('Y-m-d H:i:s');
                $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
                $merchantStatement->save();

                //vat
                $merchantStatement                   = new MerchantStatement();
                $merchantStatement->company_id       = settings()->id;
                $merchantStatement->merchant_id      = $parcel->merchant_id;
                $merchantStatement->parcel_id        = $id;
                $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
                $merchantStatement->amount           = $parcel->vat_amount;
                $merchantStatement->type             = StatementType::EXPENSE;
                $merchantStatement->date             =  date('Y-m-d H:i:s');
                $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
                $merchantStatement->save();

                //vat and total charge minus from merchant current balance
                $deliveryCost = $parcel->total_delivery_amount + $parcel->vat_amount;
                $merchantCost=Merchant::find($parcel->merchant_id);
                $merchantCost->current_balance = $merchantCost->current_balance - $deliveryCost;
                $merchantCost->save();

                //end merchant expense vat + total charge amount

                $courier_statement                  = new CourierStatement();
                $courier_statement->company_id      = settings()->id;
                $courier_statement->parcel_id       = $id;
                $courier_statement->delivery_man_id = $merchantStatement->delivery_man_id;
                $courier_statement->amount          = $parcel->total_delivery_amount;
                $courier_statement->type            = StatementType::INCOME;
                $courier_statement->date            = date('Y-m-d H:i:s');
                $courier_statement->note            = __('statementNote.partial_delivered_merchant_courier_statement');
                $courier_statement->save();


                // Vat statement
                $vat                                        = new VatStatement();
                $vat->company_id                            = settings()->id;
                $vat->parcel_id                             = $id;
                $vat->amount                                = $parcel->vat_amount;
                $vat->type                                  = StatementType::INCOME;
                $vat->date                                  = date('Y-m-d H:i:s');
                $vat->note                                  = __('parcel.partial_delivered_success');
                $vat->save();

            }

            return $parcel;

            });
        } catch (\Throwable $th) {
            Log::error('Livraison partielle annulee', ['parcel_id' => $id, 'message' => $th->getMessage()]);

            return false;
        }

        // A partir d'ici la livraison partielle est acquise.
        try {
            if($request->send_sms_customer == 'on') {

                $sms = SmsTemplate::forCompany($parcel->company_id);
                $msg = $sms->render('partial_delivered_customer', [
                    'customer' => $parcel->customer_name,
                    'tracking' => $parcel->tracking_id,
                    'url'      => url('/'),
                    'amount'   => $sms->amount($parcel->cash_collection),
                ]);
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);
            }

            if($request->send_sms_merchant  == 'on'){
                $sms = SmsTemplate::forCompany($parcel->company_id);
                $msg = $sms->render('partial_delivered_merchant', [
                    'merchant' => $parcel->merchant->business_name,
                    'tracking' => $parcel->tracking_id,
                    'customer' => $parcel->customer_name,
                    'phone'    => $parcel->customer_phone,
                    'url'      => url('/'),
                    'amount'   => $sms->amount($parcel->cash_collection),
                ]);
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' is Partials Delivered. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' taking amount('.$parcel->cash_collection.')  Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }
        } catch (\Throwable $th) {
            Log::warning('Livraison partielle notifiee en echec', ['parcel_id' => $id, 'message' => $th->getMessage()]);
        }

        return true;
    }


    /**
     * Annulation d'une livraison partielle — miroir de
     * `parcelPartialDelivered()`, memes obligations (D8).
     *
     * Elle doit aussi **restaurer** les montants d'origine du colis, que la
     * livraison partielle avait recalcules sur la somme reellement encaissee.
     */
    public function parcelPartialDeliveredCancel($id,$request){

        $colis = Parcel::companywise()->find($id);
        if(blank($colis) || (int) $colis->status !== ParcelStatus::PARTIAL_DELIVERED){
            return false;
        }

        try {
            $parcel = DB::transaction(function () use ($id) {

            $parcel                             = Parcel::find($id);
            //old info
            $old_cash_collection                = $parcel->cash_collection;
            $old_vat_amount                     = $parcel->vat_amount;
            $old_total_delivery_amount          = $parcel->total_delivery_amount;
            //end old
            $parcel->cash_collection            = $parcel->old_cash_collection;
            //calculations
            $cod_charges_amount             = round(($parcel->old_cash_collection / 100) * $parcel->cod_charge);
            $total_charges                  = (
                $cod_charges_amount             +
                $parcel->delivery_charge        +
                $parcel->liquid_fragile_amount  +
                $parcel->packaging_amount
            );
            $vat_amount                     = round(($total_charges/100) * $parcel->vat);
            $chargeWithVat                  = ($total_charges+$vat_amount);
            $totaldeliveryAmount            = ($parcel->old_cash_collection - $chargeWithVat);
            $current_payable                = ($parcel->old_cash_collection - $chargeWithVat);
            $current_vat                    = $parcel->vat_amount;
            //store
            $parcel->cod_amount             = $cod_charges_amount;
            $parcel->vat_amount             = $vat_amount;
            $parcel->total_delivery_amount  = $total_charges;
            $parcel->current_payable        = $current_payable;
            // Vat statement
            $vat                                           = new VatStatement();
            $vat->company_id                               = settings()->id;
            $vat->parcel_id                                = $id;
            $vat->amount                                   = $current_vat;
            $vat->type                                     = StatementType::EXPENSE;
            $vat->date                                     = date('Y-m-d H:i:s');
            $vat->note                                     = __('parcel.partial_delivered_cancel');
            $vat->save();

            if($parcel->status == ParcelStatus::PARTIAL_DELIVERED ){
                $pickupAsisgn  = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();

            }
            //statements
            $deliveryReschedule = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::DELIVERY_RE_SCHEDULE])->first();
            if($deliveryReschedule){

                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryReschedule->deliveryMan->id;
                $deliveryManStatement->amount               = $deliveryReschedule->deliveryMan->delivery_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.partial_delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                            = DeliveryMan::find($deliveryReschedule->deliveryMan->id);
                    $deliveryMan->current_balance           = $deliveryMan->current_balance - $deliveryManStatement->amount;
                    $deliveryMan->save();
                }

                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $courierStatement->save();

                //cash collection income from customer for store (- amount)
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryReschedule->deliveryMan->id;
                $deliveryManStatement->amount               = ($old_cash_collection);
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //cash collection added  delivery man balance
                $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance + $old_cash_collection;
                $deliveryManBalance->save();

            }else{

                $deliveryManAssign                          = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::DELIVERY_MAN_ASSIGN])->first();
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = $deliveryManAssign->deliveryMan->delivery_charge;
                $deliveryManStatement->type                 = StatementType::EXPENSE;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.partial_delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //delivery man balance add
                if($deliveryManStatement){
                    $deliveryMan                            = DeliveryMan::find($deliveryManAssign->deliveryMan->id);
                    $deliveryMan->current_balance           = $deliveryMan->current_balance - $deliveryManStatement->amount;
                    $deliveryMan->save();
                }

                $courierStatement                       = new CourierStatement();
                $courierStatement->company_id           = settings()->id;
                $courierStatement->parcel_id            = $id;
                $courierStatement->delivery_man_id      = $deliveryManStatement->delivery_man_id;
                $courierStatement->amount               = $deliveryManStatement->amount;
                $courierStatement->type                 = StatementType::INCOME;
                $courierStatement->date                 = date('Y-m-d H:i:s');
                $courierStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $courierStatement->save();

                //cash collection income from customer for store (- amount)
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $deliveryManAssign->deliveryMan->id;
                $deliveryManStatement->amount               = ($old_cash_collection);
                $deliveryManStatement->cash_collection      = 1;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.delivered_deliveryman_statement');
                $deliveryManStatement->save();

                //cash collection added  delivery man balance
                $deliveryManBalance                                = DeliveryMan::find ($deliveryMan->id);
                $deliveryManBalance->current_balance               = $deliveryManBalance->current_balance + $old_cash_collection;
                $deliveryManBalance->save();

            }

            //merchant statment
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $old_cash_collection;
            $merchantStatement->type             = StatementType::EXPENSE;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.partial_delivered_merchant_statment');
            $merchantStatement->save();

            //merchant balance add
            if($merchantStatement){
                $merchant=Merchant::find($parcel->merchant_id);
                $merchant->current_balance = $merchant->current_balance - $old_cash_collection;
                $merchant->save();
            }

            //merchant expense vat + total charge amount
            //total delivery charge
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $old_total_delivery_amount;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();
            //vat
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
            $merchantStatement->merchant_id      = $parcel->merchant_id;
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $old_vat_amount;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();
            // ⚠️ `$parcel->vat_amount` porte deja la TVA RECALCULEE sur le
            // montant d'origine, alors que la livraison partielle avait
            // preleve `$old_vat_amount`. Le solde du marchand etait donc
            // credite d'un montant que sa propre ligne de releve — juste, elle
            // — ne disait pas : livrer partiellement puis annuler laissait un
            // ecart, petit mais permanent, entre le solde et le releve.
            $deliveryCost = $old_total_delivery_amount + $old_vat_amount;
            $merchantCost = Merchant::find($parcel->merchant_id);
            $merchantCost->current_balance = $merchantCost->current_balance + $deliveryCost;
            $merchantCost->save();

            //end merchant expense vat + total charge amount
            $courier_statement                  = new CourierStatement();
            $courier_statement->company_id      = settings()->id;
            $courier_statement->parcel_id       = $id;
            $courier_statement->delivery_man_id = $merchantStatement->delivery_man_id;
            $courier_statement->amount          = $old_total_delivery_amount;
            $courier_statement->type            = StatementType::EXPENSE;
            $courier_statement->date            = date('Y-m-d H:i:s');
            $courier_statement->note            = __('statementNote.partial_delivered_merchant_courier_statement_cancel');
            $courier_statement->save();
            //end statements
            $dreschedule                            = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::DELIVERY_RE_SCHEDULE])->first();
            if($dreschedule !==null){
                $parcel->status                     = ParcelStatus::DELIVERY_RE_SCHEDULE;
            }else{
                $parcel->status                     = ParcelStatus::DELIVERY_MAN_ASSIGN;
            }
            $parcel->partial_delivered              = BooleanStatus::NO;
            $parcel->save();

            return $parcel;

            });
        } catch (\Throwable $th) {
            Log::error('Annulation de livraison partielle annulee', ['parcel_id' => $id, 'message' => $th->getMessage()]);

            return false;
        }

        return true;
    }

    public function pickupdatemanAssignedCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::PICKUP_ASSIGN){
                $pickupAsisgn = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->first();
                ParcelEvent::destroy($pickupAsisgn->id);
            }
            $parcel->status   = ParcelStatus::PENDING;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function PickupReScheduleCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::PICKUP_RE_SCHEDULE){
                $pickupReschedule = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
            }
            $parcel->status       = ParcelStatus::PICKUP_ASSIGN;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function receivedBypickupmanCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::RECEIVED_BY_PICKUP_MAN ){
                $pickupAsisgn = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->first();
                ParcelEvent::destroy($pickupAsisgn->id);
            }
            $parcel->status    = ParcelStatus::PICKUP_ASSIGN;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function deliverymanAssignCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::DELIVERY_MAN_ASSIGN ){
                $pickupAsisgn         = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
                $receivedByhub        = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::RECEIVED_BY_HUB])->delete();
                if($receivedByhub){
                    $parcel->status   = ParcelStatus::RECEIVED_BY_HUB;
                }else{
                    $parcel->status   = ParcelStatus::RECEIVED_WAREHOUSE;
                }
            }
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }
    public function deliveryReScheduleCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::DELIVERY_RE_SCHEDULE ){
                $deliverymanReschedule = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
            }
            $parcel->status   = ParcelStatus::RECEIVED_WAREHOUSE;

            $parcel->save();

            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }


    public function transfertoHubCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::TRANSFER_TO_HUB ){
                $transfertohub = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
            }
            $parcel->transfer_hub_id     = null;
            $parcel->status              = ParcelStatus::RECEIVED_WAREHOUSE;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function receivedByHubCancel($id,$request){
        try {
            // S38 — lecture NUE. L'identifiant vient du CORPS de la requete, donc
            // aucune route a parametre ne le porte : le filet d'isolation ne voyait
            // pas ce chemin. On reculait le statut du colis d'un AUTRE transporteur
            // et on SUPPRIMAIT ses evenements — la chronologie que lit son client.
            $parcel = Parcel::companywise()->find($id);
            if(blank($parcel)){
                return false;
            }
            if($parcel->status == ParcelStatus::RECEIVED_BY_HUB ){
                $receivedByhub = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->delete();
            }
            $parcel->status    = ParcelStatus::TRANSFER_TO_HUB;
            $parcel->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function search($data){
        $result = Parcel::with('merchant','merchant.user','hub')->where('tracking_id',$data['search'])->where('status',ParcelStatus::RECEIVED_WAREHOUSE)->first();
        if($result == null){
            $result = Parcel::with('merchant','merchant.user','hub')->where('tracking_id',$data['search'])->where('status',ParcelStatus::RECEIVED_BY_HUB)->first();
        }

        if($result != null){
            return $result;
        }else{
            return 0;
        }
    }

    public function searchDeliveryManAssingMultipleParcel($data){
        $result = Parcel::with('merchant','merchant.user','hub')->where('tracking_id',$data['search'])->where('status',ParcelStatus::RECEIVED_WAREHOUSE)->first();
        if($result == null){
            $result = Parcel::with('merchant','merchant.user','hub')->where('tracking_id',$data['search'])->where('status',ParcelStatus::RECEIVED_BY_HUB)->first();
        }

        if($result != null){
            return $result;
        }else{
            return 0;
        }
    }

    public function searchExpense($data){
        $result = Parcel::with('merchant','merchant.user','hub')->where('tracking_id',$data['search'])->first();

        if($result != null){
            return $result;
        }else{
            return 0;
        }
    }

    public function searchIncome($data){
        $result = Parcel::with('merchant','merchant.user','hub')->where('tracking_id',$data['search'])->first();

        if($result != null){
            return $result;
        }else{
            return 0;
        }
    }

    public function parcelReceivedByMultipleHub($id,$request){
        try {
            // S38 — la LISTE etait lue sans perimetre : les identifiants viennent
            // du corps de la requete. On rapatriait dans NOTRE entrepot les colis
            // d'un autre transporteur — `hub_id` reecrit, statut recule.
            $parcels  = Parcel::companywise()->whereIn('id',$request->parcel_id)->get();
            foreach ($parcels as $key => $parcel) {
                $receivedByhub                = new ParcelEvent();
                $receivedByhub->parcel_id     = $parcel->id;
                $receivedByhub->parcel_status = ParcelStatus::RECEIVED_BY_HUB;
                $receivedByhub->note          = $request->note;
                $receivedByhub->created_by    = Auth::user()->id;
                $receivedByhub->save();

                $parcel->hub_id               = $parcel->transfer_hub_id;
                $parcel->status               = ParcelStatus::RECEIVED_BY_HUB;
                $parcel->save();
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function pickupdatemanAssignedBulk($request){

        try {
            // S45 — S38 a ferme l'axe du COLIS sur ce chemin et l'a inscrit comme
            // prouve. L'axe de l'AGENT restait ouvert : `delivery_man_id` designe
            // un livreur d'une AUTRE societe, credite de la course sur NOS ecritures.
            // Un identifiant de lot hors perimetre est ignore ; celui-ci vaut pour
            // TOUT le lot, donc on refuse le lot entier.
            if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
                return false;
            }
            foreach ($request->parcel_id as  $id) {
                // S38 — le colis venait d'une LISTE portee par le corps de la requete.
                // Aucune route a parametre ne porte ces identifiants : le filet ne
                // voyait pas ce chemin. On changeait le statut des colis d'un AUTRE
                // transporteur, on leur posait un evenement, et le SMS partait a SON
                // client. Un identifiant hors perimetre est simplement ignore : le
                // reste du lot, lui, passe.
                $parcel = Parcel::companywise()->find($id);
                if(blank($parcel)){
                    continue;
                }
                $pickupAsisgn                = new ParcelEvent();
                $pickupAsisgn->parcel_id     = $id;
                $pickupAsisgn->pickup_man_id = $request->delivery_man_id;
                $pickupAsisgn->note          = $request->note;
                $pickupAsisgn->parcel_status = ParcelStatus::PICKUP_ASSIGN;
                $pickupAsisgn->created_by    = Auth::user()->id;
                $pickupAsisgn->save();
                $parcel->status              = ParcelStatus::PICKUP_ASSIGN;
                $parcel->save();
                if($request->send_sms_pickuman == 'on'){
                    $msg = SmsTemplate::forCompany($parcel->company_id)->render('pickup_assigned_agent', [
                        'agent'    => $pickupAsisgn->pickupman->user->name,
                        'tracking' => $parcel->tracking_id,
                        'merchant' => $parcel->merchant->business_name,
                        'phone'    => $parcel->merchant->user->mobile,
                        'address'  => $parcel->merchant->address,
                        'date'     => dateFormat($parcel->pickup_date),
                    ]);
                    $response =  app(SmsService::class)->sendSms($pickupAsisgn->pickupman->user->mobile,$msg);
                }
                try{
                    $msgNotification = 'Dear '.$pickupAsisgn->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                    app(PushNotificationService::class)->sendStatusPushNotification($parcel,$pickupAsisgn->pickupman->user->email,$msgNotification,'deliveryMan');
                }catch (\Exception $exception){

                }
                if($request->send_sms_merchant  == 'on'){
                    $msg = SmsTemplate::forCompany($parcel->company_id)->render('pickup_assigned_merchant', [
                        'merchant'    => $parcel->merchant->business_name,
                        'tracking'    => $parcel->tracking_id,
                        'agent'       => $pickupAsisgn->pickupman->user->name,
                        'agent_phone' => $pickupAsisgn->pickupman->user->mobile,
                        'url'         => url('/'),
                    ]);
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                }
            }
            return true;

        } catch (\Throwable $th) {
            return false;
        }


    }
    public function AssignReturnToMerchantBulk($request){
        try {
            // S45 — S38 a ferme l'axe du COLIS sur ce chemin et l'a inscrit comme
            // prouve. L'axe de l'AGENT restait ouvert : `delivery_man_id` designe
            // un livreur d'une AUTRE societe, credite de la course sur NOS ecritures.
            // Un identifiant de lot hors perimetre est ignore ; celui-ci vaut pour
            // TOUT le lot, donc on refuse le lot entier.
            if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))){
                return false;
            }
            foreach ($request->parcel_id as $id) {
                // S38 — le colis venait d'une LISTE portee par le corps de la requete.
                // Aucune route a parametre ne porte ces identifiants : le filet ne
                // voyait pas ce chemin. On changeait le statut des colis d'un AUTRE
                // transporteur, on leur posait un evenement, et le SMS partait a SON
                // client. Un identifiant hors perimetre est simplement ignore : le
                // reste du lot, lui, passe.
                $parcel = Parcel::companywise()->find($id);
                if(blank($parcel)){
                    continue;
                }

                DB::beginTransaction();

                $returnassigntomerchant                  = new ParcelEvent();
                $returnassigntomerchant->parcel_id       = $id;
                $returnassigntomerchant->delivery_man_id = $request->delivery_man_id;
                $returnassigntomerchant->note            = $request->note;
                $returnassigntomerchant->parcel_status   = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
                $returnassigntomerchant->created_by      = Auth::user()->id;
                $returnassigntomerchant->save();
                // Delivery man current balance update
                $deliveryMan                                = DeliveryMan::find($request->delivery_man_id);
                $deliveryMan->current_balance               = $deliveryMan->current_balance + $deliveryMan->return_charge;
                $deliveryMan->save();
                // Courier statement
                $deliveryManStatement                       = new DeliverymanStatement();
                $deliveryManStatement->company_id           = settings()->id;
                $deliveryManStatement->parcel_id            = $id;
                $deliveryManStatement->delivery_man_id      = $request->delivery_man_id;
                $deliveryManStatement->amount               = $deliveryMan->return_charge;
                $deliveryManStatement->type                 = StatementType::INCOME;
                $deliveryManStatement->date                 = date('Y-m-d H:i:s');
                $deliveryManStatement->note                 = __('statementNote.returned_to_merchant_income');
                $deliveryManStatement->save();
                // Courier statement
                $courierStatement                           = new CourierStatement();
                $courierStatement->company_id               = settings()->id;
                $courierStatement->parcel_id                = $id;
                $courierStatement->delivery_man_id          = $request->delivery_man_id;
                $courierStatement->amount                   = $deliveryMan->return_charge;
                $courierStatement->type                     = StatementType::EXPENSE;
                $courierStatement->date                     = date('Y-m-d H:i:s');
                $courierStatement->note                     = __('statementNote.returned_to_merchant_expense');
                $courierStatement->save();
                // End
                $parcel->delivery_date = $request->date;
                $parcel->status        = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
                $parcel->save();
                DB::commit();
                if($request->send_sms == 'on'){
                    $msg = SmsTemplate::forCompany($parcel->company_id)->render('returned_to_merchant', [
                        'merchant'    => $parcel->merchant->business_name,
                        'tracking'    => $parcel->tracking_id,
                        'agent'       => $returnassigntomerchant->deliveryMan->user->name,
                        'agent_phone' => $returnassigntomerchant->deliveryMan->user->mobile,
                        'url'         => url('/'),
                    ]);
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                }

            }
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
    public function bulkParcels($ids){
        // S38 — liste d'identifiants venue du corps, lue sans perimetre. Les vues
        // d'impression et les SMS de masse s'alimentent ici : on rendait les
        // coordonnees client des colis d'un autre transporteur.
        return Parcel::companywise()->whereIn('id',$ids)->get();
    }
    //app dashboard
    public function deliverymanStatusParcel($status){

        if($status == ParcelStatus::DELIVERED):
            return Parcel::orderBy('updated_at')->orderBy('priority_type_id')->with(['merchant'])->withCount(['customsAlerts as customs_pending_count' => fn ($q) => $q->where('status', \App\Enums\CustomsAlertStatus::PENDING)])->whereIn('status',[ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])->where(function($query){
                $query->wherehas('parcelEvent',function($eventquery){
                    $eventquery->where('delivery_man_id',Auth::user()->deliveryman->id);
                });
            })->get();
        else:
            return Parcel::orderBy('updated_at')->orderBy('priority_type_id')->with(['merchant'])->withCount(['customsAlerts as customs_pending_count' => fn ($q) => $q->where('status', \App\Enums\CustomsAlertStatus::PENDING)])->where('status',$status)->where(function($query){
                $query->wherehas('parcelEvent',function($eventquery){
                    $eventquery->where('delivery_man_id',Auth::user()->deliveryman->id);
                });
            })->get();
        endif;
    }
    //end app dashboard
    public function parcelSearchs($request){
        // S57 — ⚠️ LA PIRE DIVULGATION DU LOT. Cette recherche n'avait AUCUN
        // perimetre : elle rendait les colis de toutes les societes, avec le
        // `customer_name`, le `customer_phone` et le `customer_address` — donc
        // les DONNEES PERSONNELLES des clients d'un transporteur concurrent.
        // Meme nature que le defaut de S28 (une carte des courses hors `auth`
        // qui versait nom, telephone et adresse dans la page).
        //
        // ⚠️ ET LE PIEGE : poser `companywise()` devant cette chaine ne l'aurait
        // PAS scopee. `where(A)->orWhere(B)` donne `company_id = X AND A OR B`,
        // et le OR sort du perimetre : un colis d'en face correspondant sur
        // `customer_phone` serait encore rendu. Le groupe de OR est donc
        // ENFERME dans une fermeture, pour que le perimetre domine l'ensemble.
        return Parcel::companywise()
            ->where(function ($query) use ($request) {
                $query->where('customer_name','Like','%'.$request->search.'%')
                    ->orWhere('customer_phone','Like','%'.$request->search.'%')
                    ->orWhere('customer_address','Like','%'.$request->search.'%')
                    ->orWhere('invoice_no','Like','%'.$request->search.'%')
                    ->orWhere('tracking_id','Like','%'.$request->search.'%')
                    ->orWhereHas('merchant',function($query) use($request){
                        $query->where('business_name','Like','%'.$request->search.'%');
                    });
            })
            ->paginate(10);
    }


    public function parcelMultiplePrintLabel($request){
        // S38 — meme forme : les etiquettes imprimaient nom, telephone et adresse
        // du client de n'importe quel transporteur.
        return Parcel::companywise()->whereIn('id',$request->parcels)->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->get();
    }

}
