<?php
namespace App\Repositories\MerchantPanel\MerchantParcel;

use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Enums\DeliveryType;
use App\Enums\DeliveryTime;
use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
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

    /**
     * Les étiquettes en lot (S70) : même forme que `ParcelRepository::parcelMultiplePrintLabel()`,
     * mais le périmètre est celui du MARCHAND connecté. Règle de S38 pour un lot : un
     * identifiant hors périmètre est ignoré, le reste du lot passe.
     */
    public function parcelMultiplePrintLabel($request){
        return $this->ownedParcels()->whereIn('id', (array) $request->parcels)
            ->with('merchant', 'merchant.user', 'merchantShop', 'deliveryCategory', 'packaging')->get();
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

    /** La categorie partagee de la plateforme — voir `DeliveryCategoryRepository::all()`. */
    private const CATEGORIE_DE_PLATEFORME = 1;

    /**
     * S46 — le panneau MARCHAND : la meme famille d'identifiants, l'autre porte.
     *
     * Le depot du back-office a recu sa garde ; celui-ci lisait les memes
     * champs sans en verifier aucun. Il en porte un de plus, et c'est le pire :
     *
     * ⚠️ `$parcel->merchant_id = $request->merchant_id ?? $merchant_id` — le
     * formulaire envoie `merchant_id` dans un champ **cache**, et un champ
     * cache est tenu par le navigateur, pas par nous. C'est mot pour mot la
     * forme du defaut S33 cote back-office : « le formulaire ne propose que mes
     * marchands ; rien n'obligeait le navigateur a s'y tenir. » Un marchand
     * pouvait donc, depuis SON panneau, attribuer un colis a n'importe quel
     * autre marchand — y compris d'une autre societe — pendant que
     * `company_id` restait la notre : le colis atterrissait dans les releves et
     * le solde de l'autre. La requete du panneau ne valide meme pas ce champ.
     *
     * Les trois autres sont ceux du back-office, meme motif : la boutique se
     * garde par le MARCHAND (`merchant_shops` n'a pas de `company_id`, S26),
     * l'emballage et la categorie par la societe. La zone et le delai, eux, se
     * gardent en aval — `ChargeCalculator` et `DeliveryChargeResolver`.
     */
    private function catalogueHorsPerimetre($merchant_id, $request): bool
    {
        if (filled($request->merchant_id) && (int) $request->merchant_id !== (int) $merchant_id) {
            return true;
        }

        // ⚠️ Deux ecarts avec la garde du back-office, tous deux mesures.
        //
        // 1. La categorie 1 est celle de la PLATEFORME, partagee par toutes les
        //    societes. `DeliveryCategoryRepository` ecrit la regle deux fois —
        //    `all()` rend « `id = 1` OU `company_id = settings()->id` » — et les
        //    six lignes semees par le socle n'ont aucun `company_id`.
        // 2. Le perimetre est celui du MARCHAND, pas du locataire ambiant.
        //    `companywise()` lirait `settings()`, or ce depot est entre par
        //    l'API mobile et le panneau autant que par le web : le marchand
        //    authentifie est la source de verite, pas l'hote de la requete.
        //    Meme decision que pour le supplement de delai dans
        //    `DeliveryChargeResolver`, et pour la meme raison.
        $societe = Merchant::find($merchant_id)?->company_id;

        if (filled($request->category_id)
            && (int) $request->category_id !== self::CATEGORIE_DE_PLATEFORME
            && blank(Deliverycategory::where('company_id', $societe)->find($request->category_id))) {
            return true;
        }

        if (filled($request->shop_id)
            && blank(MerchantShops::where('merchant_id', $merchant_id)->find($request->shop_id))) {
            return true;
        }

        if (filled($request->packaging_id)
            && blank(Packaging::where('company_id', $societe)->find($request->packaging_id))) {
            return true;
        }

        return false;
    }

    public function store($request,$merchant_id) {

        try {
            // S46 — les catalogues du colis, et le marchand facture.
            if ($this->catalogueHorsPerimetre($merchant_id, $request)) {
                return false;
            }


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

            // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
            // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
            // a reverser fabriques par le navigateur : le client choisissait sa facture.
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                \App\Models\Backend\Merchant::find(Auth::user()->merchant->id),
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

            // W5 / controle de solde — un seul point de debit, partage par les
            // quatre chemins de creation : voir Services\Parcel\WalletDebit.
            // Il verrouille la ligne marchand, refuse si le solde ne couvre pas
            // les frais, puis debite. Plus de `catch` vide : un echec doit
            // annuler la creation, pas la laisser facturee a personne.
            app(\App\Services\Parcel\WalletDebit::class)->apply($parcel);

            return true;

            });
        }
        // Un solde insuffisant n'est pas une panne : il remonte tel quel jusqu'a
        // l'appelant, qui sait le dire au marchand — et lui dire ce qui manque.
        catch (InsufficientWalletBalance $e) {
            throw $e;
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
            // S46 — les catalogues du colis, et le marchand facture.
            if ($this->catalogueHorsPerimetre($merchant_id, $request)) {
                return false;
            }

            // Dupliquer un colis, c'est en creer un : meme atomicite que
            // `store()`. Le socle ecrivait le colis, puis tentait le debit dans
            // un `catch` vide — un echec laissait un colis a facturer a
            // personne.
            return DB::transaction(function () use ($request, $merchant_id) {

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
            // D4, etape 5 bis : la zone et le delai du colis. Nuls tant que
            // le formulaire ne les envoie pas — le colis suit alors le
            // bareme herite, et `delivery_type_id` reste sa seule route.
            $parcel->zone_id                = $request->zone_id ?: null;
            $parcel->delay_id               = $request->delay_id ?: null;
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
 
            $parcel->tracking_id             = $this->RandomTrackingID();
            
            $parcel->save();

            // W5 / controle de solde — un seul point de debit, partage par les
            // quatre chemins de creation : voir Services\Parcel\WalletDebit.
            // Il verrouille la ligne marchand, refuse si le solde ne couvre pas
            // les frais, puis debite. Plus de `catch` vide : un echec doit
            // annuler la creation, pas la laisser facturee a personne.
            app(\App\Services\Parcel\WalletDebit::class)->apply($parcel);

            // Parcel logs
            // Meme correction que pour le debit : le marchand vient du COLIS.
            // Le formulaire du panneau poste bien `merchant_id`, mais le colis
            // lui-meme est rattache a `$request->merchant_id ?? $merchant_id` —
            // tout appelant qui ne poste pas ce champ tombait ici sur
            // `Merchant::find(null)`, puis sur une lecture de propriete de
            // `null`. Le journal suit desormais le colis, pas la requete.
            $log                         = new ParcelLogs;
            $log->merchant_id            = $parcel->merchant_id;
            $merchant = Merchant::find($parcel->merchant_id);
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
            // `parcel_logs` n'a pas de colonne `parcel_bank` — elle n'a jamais
            // existe (voir la migration de 2022). L'insertion echouait donc a
            // TOUS les coups : dupliquer un colis depuis le panneau marchand
            // creait le colis, ratait son journal, et rendait « une erreur est
            // survenue » en laissant le colis derriere. Le drapeau est deja
            // porte par `parcels.parcel_bank` ; le journal n'en a pas besoin.
            $log->save();

            return true;

            });
        }
        catch (InsufficientWalletBalance $e) {
            throw $e;
        }
        catch (\Throwable $e) {
            Log::error('Duplication de colis annulee', [
                'merchant_id' => $request->merchant_id ?? $merchant_id,
                'message'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function update($id, $request,$merchant_id) {

        try {
            // S46 — les catalogues du colis, et le marchand facture.
            if ($this->catalogueHorsPerimetre($merchant_id, $request)) {
                return false;
            }


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

                $parcel->note                   = $request->note;
                $parcel->parcel_bank            = $request->parcel_bank;

                // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
                // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
                // a reverser fabriques par le navigateur : le client choisissait sa facture.
                $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                    \App\Models\Backend\Merchant::find(Auth::user()->merchant->id),
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
