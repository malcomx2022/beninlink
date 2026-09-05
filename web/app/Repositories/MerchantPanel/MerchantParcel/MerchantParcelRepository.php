<?php
namespace App\Repositories\MerchantPanel\MerchantParcel;

use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Enums\DeliveryType;
use App\Enums\DeliveryTime;
use App\Enums\Status;
use App\Http\Resources\MerchantParcelExportResource;
use App\Http\Services\PushNotificationService;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryCharge;

use App\Models\Backend\ParcelLogs;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Carbon\Carbon;
use App\Models\Backend\ParcelEvent;
use App\Models\Config;
use App\Models\Subscribe;
use App\Models\User;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MerchantParcelRepository implements MerchantParcelInterface {

    protected $walletRepo;
    public function __construct(WalletInterface $walletRepo)
    {
        $this->walletRepo    = $walletRepo;
    }

    public function all($merchant_id){
        return Parcel::where('merchant_id',$merchant_id)->orderByDesc('id')->paginate(10);
    }
    public function parcelAll($merchant_id){
        return Parcel::with('merchant')->where('merchant_id',$merchant_id)->orderByDesc('id')->get();
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

    public function parcelBank($merchant_id){
        return Parcel::where('parcel_bank', "on")->where('merchant_id',$merchant_id)->orderByDesc('id')->paginate(10);
    }

    public function filter($merchant_id,$request){

        return Parcel::where('merchant_id',$merchant_id)->orderByDesc('id')->where(function( $query ) use ( $request ) {

            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if($request->parcel_status) {
                $query->where('status',$request->parcel_status);
            }

            if($request->parcel_customer) {
                $query->where('customer_name', 'like', '%' . $request->parcel_customer . '%');
            }
            if($request->parcel_customer_phone) {
                $query->where('customer_phone', 'like', '%' . $request->parcel_customer_phone . '%');
            }
            if($request->invoice_id) {
                $query->where('invoice_no', 'like', '%' . $request->invoice_id . '%');
            }

        })->paginate(10);
    }

    /**
     * S17 — les colis du marchand connecte, et eux seuls.
     *
     * Ce repository sert le panneau marchand et l'API marchand. Le socle y
     * travaillait sur `Parcel::find($id)` nu : n'importe quel marchand
     * authentifie lisait le colis d'un autre en changeant l'identifiant dans
     * l'URL — nom, telephone et adresse du destinataire, montants — et pouvait
     * le modifier ou le supprimer.
     *
     * Toute lecture et toute ecriture de ce fichier passent desormais par ici.
     * Seule exception legitime : `parcelTrack()`, le suivi public par numero de
     * suivi, qui n'a pas d'utilisateur authentifie.
     */
    private function ownedParcels() {
        return Parcel::companywise()->where('merchant_id', Auth::user()->merchant?->id);
    }

    public function parcelEvents($id){
        return ParcelEvent::where('parcel_id',$id)
            ->whereIn('parcel_id', $this->ownedParcels()->select('id'))
            ->orderBy('created_at','desc')->get();
    }

    public function get($id) {
        return $this->ownedParcels()->find($id);
    }

    public function details($id) {
        return $this->ownedParcels()->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->find($id);
    }

    /**
     * Statuts qu'un marchand peut poser lui-meme sur son colis.
     *
     * La liste est **vide**, et c'est le constat, pas un oubli : dans le socle
     * We Courier, aucune etape du cycle de vie n'appartient au marchand. Chaque
     * transition est posee par une methode dediee du back-office ou du livreur,
     * qui ecrit l'evenement (`parcel_events`) et les ecritures comptables qui
     * vont avec (statements coursier, marchand, TVA, soldes). Le seul levier
     * legitime du marchand sur un colis est de le **supprimer** tant qu'il est
     * « En attente » : voir `Api\V10\ParcelController::destroy()`, qui refuse
     * en 422 au-dela.
     *
     * Y ajouter une transition un jour n'est pas qu'une ligne ici : il faudra
     * ecrire l'evenement et les ecritures correspondants, comme le font les
     * methodes du back-office.
     */
    public const MERCHANT_ALLOWED_STATUSES = [];

    /**
     * Poser un statut sur son propre colis.
     *
     * ⚠️ Cette methode etait un setter nu : `$parcel->status = $status_id`, sans
     * aucun controle de transition. Un marchand pouvait donc se declarer
     * lui-meme « Livre » (9) sur un colis jamais ramasse — sans `parcel_events`,
     * sans ecriture comptable — puis voir ce colis entrer au releve de reglement
     * (`InvoiceRepository::store()` selectionne les colis DELIVERED sans
     * `invoice_id`) et reclamer au transporteur un encaissement qui n'a jamais
     * eu lieu. Une valeur hors enumeration, `999`, etait ecrite telle quelle.
     *
     * @return bool `false` si le colis n'est pas a lui, ou si la transition ne
     *              lui appartient pas. L'appelant distingue les deux cas.
     */
    public function statusUpdate($id, $status_id) {
        $parcel         = $this->ownedParcels()->find($id);
        if(blank($parcel)){
            return false;
        }
        if(!in_array((int) $status_id, self::MERCHANT_ALLOWED_STATUSES, true)){
            return false;
        }
        $parcel->status = (int) $status_id;
        $parcel->save();
        return true;
    }

    public function getMerchant($id){
        return Merchant::where('user_id',$id)->first();
    }

    public function getShop($id){
        return MerchantShops::where('merchant_id',$id)->first();
    }
    public function getShops($id){
        $merchantShops      = [];
        $merchantShop       = MerchantShops::where(['merchant_id'=>$id,'default_shop'=>Status::ACTIVE])->first();
        $merchantShops[]    = $merchantShop;
        $merchantShopArray  = MerchantShops::where(['merchant_id'=>$id,'default_shop'=>Status::INACTIVE])->get();
        if(!blank($merchantShopArray)){
            foreach ($merchantShopArray as $shop){
                $merchantShops[] = $shop;
            }
        }
        return $merchantShops;
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

    public function store($request,$merchant_id) {

        try {

            /**
             * W5, décision du 2026-09-05 — la création et le débit du
             * portefeuille sont **atomiques**.
             *
             * Auparavant, le débit vivait dans un `try/catch` à part : un échec
             * laissait le colis créé et le marchand non facturé. Le transporteur
             * livrait alors gratuitement, sans que personne ne le sache.
             *
             * Les rendre indissociables coûte une disponibilité : si le
             * portefeuille est indisponible, le colis n'est pas créé et le
             * marchand réessaie. C'est le bon sens du métier : un colis
             * qu'on ne sait pas facturer ne doit pas partir.
             *
             * L'opération est sûre à annuler : tout ce que la création
             * déclenche s'écrit en base — l'alerte douanière de
             * `ParcelCustomsObserver` et la notification du fil marchand, qui
             * n'a que le canal `database`. Aucun SMS ni push n'est émis ici,
             * donc un `rollback` ne laisse rien derrière lui.
             */
            return DB::transaction(function () use ($request, $merchant_id) {

            $parcel                         = new Parcel();
            $parcel->company_id             = settings()->id;
            $parcel->merchant_id            = $request->merchant_id ?? $merchant_id;
            $parcel->first_hub_id           = auth()->user()->hub_id;
            $parcel->hub_id                 = auth()->user()->hub_id;
            $parcel->category_id            = $request->category_id;
            if($request->weight){
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
                \App\Models\Backend\Merchant::find(Auth::user()->merchant->id),
                (int) $request->delivery_type_id,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                (float) $request->cash_collection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                isset($request->fragileLiquid) && $request->fragileLiquid == 'on'
            );
            $parcel->vat                    = $charges['vat'];
            $parcel->vat_amount             = $charges['vat_amount'];
            $parcel->delivery_charge        = $charges['delivery_charge'];
            $parcel->cod_charge             = $charges['cod_charge'];
            $parcel->cod_amount             = $charges['cod_amount'];
            $parcel->total_delivery_amount  = $charges['total_delivery_amount'];
            $parcel->current_payable        = $charges['current_payable'];
            $parcel->note                   = $request->note;
            $parcel->parcel_bank            = $request->parcel_bank;
            $parcel->status                 = ParcelStatus::PENDING;
            if($request->packaging_id){
                $parcel->packaging_id               = $request->packaging_id;
                $parcel->packaging_amount           = $charges['packaging_amount'];
            }
            if(isset($request->fragileLiquid) && $request->fragileLiquid=='on'){
                $parcel->liquid_fragile_amount  = $charges['liquid_fragile_amount'];
            }
          
            $parcel->tracking_id             = $this->RandomTrackingID();
            
            $parcel->save();

            // Plus de `try/catch` autour du débit : un échec doit remonter et
            // annuler la création, au lieu d'être avalé ou seulement journalisé.
            //wallet
            if ($parcel) :
                // ⚠️ Le socle lisait `$request->merchant_id`, alors que le
                // colis lui-meme est rattache a `$request->merchant_id ??
                // $merchant_id`. Cree depuis l'app, la requete ne porte pas
                // ce champ — le marchand vient du compte authentifie — donc
                // `Merchant::find(null)` rendait `null`, la condition
                // n'etait jamais vraie (lire une propriete sur `null` est un
                // avertissement, pas une exception), et **le portefeuille
                // n'etait jamais debite** : les colis crees depuis l'app
                // n'etaient pas factures. On charge le marchand du colis.
                $w_merchant                 =  Merchant::find($parcel->merchant_id);
                if($w_merchant && $w_merchant->wallet_use_activation == Status::ACTIVE):
                    $m_user_id                  = $w_merchant->user_id;
                    $w_merchant->wallet_balance = $w_merchant->wallet_balance - $parcel->total_delivery_amount;
                    $w_merchant->save();

                    $walletExpense                 = new Request();
                    $walletExpense['user_id']      = $m_user_id;
                    $walletExpense['merchant_id']  = $parcel->merchant_id;
                    $walletExpense['tracking_id']  = $parcel->tracking_id;
                    $walletExpense['amount']       = $parcel->total_delivery_amount;
                    $this->walletRepo->expense($walletExpense);
                endif;
            endif;
            //end wallet

            return true;

            });
        }
        catch (\Throwable $e) {
            // La transaction est annulée : aucun colis n'a été créé, aucun solde
            // entamé. Le message reste neutre — ce `catch` couvre toute la
            // création, pas seulement le débit — mais il laisse de quoi
            // comprendre, là où le socle ne laissait rien.
            Log::error('Creation de colis annulee', [
                'merchant_id' => $request->merchant_id ?? $merchant_id,
                'message'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function duplicateStore($request,$merchant_id) {
        try {
            $duplicate_parcel = $this->get($request->parcel_id);

            $parcel                         = new Parcel();
            $parcel->company_id             = settings()->id;
            $parcel->merchant_id            = $request->merchant_id ?? $merchant_id;
            $parcel->first_hub_id           = auth()->user()->hub_id;
            $parcel->hub_id                 = auth()->user()->hub_id;
            $parcel->category_id            = $request->category_id;
            if($request->weight !=="" ){
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
            $parcel->note                   = $request->note;
            $parcel->parcel_bank            = $request->parcel_bank;
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
                \App\Models\Backend\Merchant::find(Auth::user()->merchant->id),
                (int) $request->delivery_type_id,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                (float) $request->cash_collection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                isset($request->fragileLiquid) && $request->fragileLiquid == 'on'
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
 
            $parcel->tracking_id             = $this->RandomTrackingID();
            
            $parcel->save();

            try { 
                //wallet
                if ($parcel) :
                    $w_merchant                 =  Merchant::find($request->merchant_id);
                    if($w_merchant->wallet_use_activation == Status::ACTIVE):
                        $m_user_id                  = $w_merchant->user_id;
                        $w_merchant->wallet_balance = $w_merchant->wallet_balance - $parcel->total_delivery_amount;
                        $w_merchant->save();
    
                        $walletExpense                 = new Request();
                        $walletExpense['user_id']      = $m_user_id;
                        $walletExpense['merchant_id']  = $request->merchant_id;
                        $walletExpense['tracking_id']  = $parcel->tracking_id;
                        $walletExpense['amount']       = $parcel->total_delivery_amount;
                        $this->walletRepo->expense($walletExpense);
                    endif;
                endif;
                //end wallet
            } catch (\Throwable $th) {
                 
            }

            // Parcel logs
            $log                         = new ParcelLogs;
            $log->merchant_id            = $request->merchant_id;
            $merchant = Merchant::find($request->merchant_id);
            $log->hub_id                  = $merchant->user->hub_id;
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
            $log->parcel_bank            = $request->parcel_bank;
            $log->save();

            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request,$merchant_id) {

        try {

            $parcel                         = $this->ownedParcels()->find($id);
            if(blank($parcel)){
                return false;
            }
            $parcel->company_id             = settings()->id;
            // S17 — le marchand n'est jamais relu dans la requete : le socle
            // prenait `$request->merchant_id`, ce qui permettait de reaffecter le
            // colis a un autre marchand (et plantait quand le champ manquait).
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

                $parcel->note                   = $request->note;
                $parcel->parcel_bank            = $request->parcel_bank;

                // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
                // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
                // a reverser fabriques par le navigateur : le client choisissait sa facture.
                $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                    \App\Models\Backend\Merchant::find(Auth::user()->merchant->id),
                    (int) $request->delivery_type_id,
                    $request->category_id ? (int) $request->category_id : null,
                    $request->weight,
                    (float) $request->cash_collection,
                    $request->packaging_id ? (int) $request->packaging_id : null,
                    isset($request->fragileLiquid) && $request->fragileLiquid == 'on'
                );
                $parcel->vat                    = $charges['vat'];
                $parcel->vat_amount             = $charges['vat_amount'];
                $parcel->delivery_charge        = $charges['delivery_charge'];
                $parcel->cod_charge             = $charges['cod_charge'];
                $parcel->cod_amount             = $charges['cod_amount'];
                $parcel->total_delivery_amount  = $charges['total_delivery_amount'];
                $parcel->current_payable        = $charges['current_payable'];

            if(isset($request->fragileLiquid) && $request->fragileLiquid=='on'){
                $parcel->liquid_fragile_amount      = $charges['liquid_fragile_amount'];
            }else{
                $parcel->liquid_fragile_amount      = null;
            }
            if($request->packaging_id){
                $parcel->packaging_id               = $request->packaging_id;
                $parcel->packaging_amount           = $charges['packaging_amount'];

            }
            $parcel->save();

            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id,$merchant_id) {
        return $this->ownedParcels()->where('id', $id)->delete();
    }


    public function parcelTrack($track_id){

        $parcel   = Parcel::where('tracking_id',$track_id)->with(['merchant'])->select('id','merchant_id','tracking_id','created_at')->first();
        $merchant = Merchant::find($parcel->merchant_id);
        $createdEvent = [
                'tracking_id'  => $parcel->tracking_id,
                'created_at'   => $parcel->created_at,
                'merchant_name'=>$merchant->user->name,
                'email'        => $merchant->user->email,
                'mobile'       => $merchant->user->mobile
        ];
        if($parcel):
            $data=[
                'parcel' => $createdEvent,
                'events'=>ParcelEvent::with(['deliveryMan','pickupman', 'transferDeliveryman', 'hub', 'user'])->where('parcel_id',$parcel->id)->orderBy('created_at','desc')->get()
            ];
            return $data;
        else:
            return false;
        endif;

    }


    public function subscribe($request){

        try {
            $exists  = Subscribe::where(['email'=>$request->email,'company_id'=>settings()->id])->first();
            if($exists):
                return 1;
            else:
                try {
                    Subscribe::create(['email'=>$request->email,'company_id'=>settings()->id]);
                    return true;
                } catch (\Throwable $th) { 
                    return false;
                }
            endif;
        } catch (\Throwable $th) { 
            return false;
        }
    }

    public function filterExport($merchant_id,$request){

        return Parcel::where('merchant_id',$merchant_id)->orderByDesc('id')->where(function( $query ) use ( $request ) {

            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if($request->parcel_status) {
                $query->where('status',$request->parcel_status);
            }
            if($request->parcel_customer) {
                $query->where('customer_name', 'like', '%' . $request->parcel_customer . '%');
            }
            if($request->parcel_customer_phone) {
                $query->where('customer_phone', 'like', '%' . $request->parcel_customer_phone . '%');
            }

        })->get();


    }

    public function parcelExport($request){
        try {
            if( $request->parcel_date !=="" || $request->parcel_status !=="" || $request->parcel_customer !=="" || $request->parcel_customer_phone !==""):
                $parcels  = $this->filterExport(Auth::user()->merchant->id,$request);
            else:
                $parcels = Parcel::where(['merchant_id'=>Auth::user()->merchant->id])->get();
            endif;

            return $parcels;
        } catch (\Throwable $th) {

            return collect([]);
        }
    }

    public function statusWiseParcelList($status){
        return Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',$status)->paginate(10);
    }


}
