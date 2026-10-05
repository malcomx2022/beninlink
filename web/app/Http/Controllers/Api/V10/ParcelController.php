<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Http\Controllers\Controller;
use App\Http\Resources\v10\CustomsAlertResource;
use App\Http\Resources\v10\DeliveryChargeResource;
use App\Http\Resources\v10\ParcelLogsResource;
use App\Http\Resources\v10\ParcelResource;
use App\Imports\ParcelImport;
use App\Models\Backend\DeliveryCharge;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Parcel\StoreRequest;
use Illuminate\Support\Facades\Validator;
use App\Enums\ParcelStatus;
use App\Http\Resources\v10\StatusWiseParcelResource;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
class ParcelController extends Controller
{

    use ApiReturnFormatTrait;

    protected $merchant;
    protected $repo;
    protected $shop;
    protected $deliveryCharges;
    protected $merchantDeliveryCharges;
    public function __construct(MerchantParcelInterface $repo, MerchantInterface $merchant, ShopsInterface $shop,DeliveryChargeInterface $deliveryCharges, MerchantDeliveryChargeInterface $merchantDeliveryCharges)
    {
        $this->merchant = $merchant;
        $this->repo = $repo;
        $this->shop = $shop;
        $this->deliveryCharges = $deliveryCharges;
        $this->merchantDeliveryCharges = $merchantDeliveryCharges;
    }
    public function index(Request $request)
    {

        try {
            $userID = auth()->user()->id;
            $merchant = $this->repo->getMerchant($userID);
            $parcels = $this->repo->parcelAll($merchant->id);
            return $this->responseWithSuccess(__('parcel.title'), ['parcels'=>ParcelResource::collection($parcels)], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.title'), [], 500);

        }
    }


    public function filter(Request $request)
    {
        try {
            $userID = auth()->user()->id;
            $merchant = $this->repo->getMerchant($userID);
            $parcels      = $this->repo->filter($merchant->id,$request);
            return $this->responseWithSuccess(__('parcel.title'), ['parcels'=>$parcels], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.title'), [], 500);

        }
    }

    public function create()
    {

        try {
            $userID = auth()->user()->id;
            $merchant = $this->repo->getMerchant($userID);
            $shops = $this->repo->getShops($merchant->id);
            $deliveryCategories = $this->repo->deliveryCategories();
            $packagings = $this->repo->packaging();
            $deliveryTypes      = $this->repo->deliveryTypes();

            $codCharges = [];
            $i = 0;
            if(!blank($merchant)){
                foreach($merchant->cod_charges as $key => $charge){
                    $codCharges[$i]['name']       = __('merchant.'.$key);
                    $codCharges[$i]['charge']     = $charge;
                    $i++;
                }
            }

            $fragileLiquid = SettingHelper('fragile_liquid_charge');
            $deliveryCharges = DeliveryChargeResource::collection($this->merchantDeliveryCharges->getAll($merchant->id));


            // D4, etape 5 bis : la route proposee a l'ecran de creation. Listes
            // vides tant que la societe n'a pas de zones — l'app garde alors son
            // selecteur de type de livraison, et rien ne change.
            $zonesRepo = app(\App\Repositories\DeliveryZone\DeliveryZoneInterface::class);
            $zones = \App\Http\Resources\v10\DeliveryZoneResource::collection($zonesRepo->grilleMarchand($merchant->id));
            $delays = \App\Http\Resources\v10\DeliveryDelayResource::collection($zonesRepo->delais());

            return $this->responseWithSuccess(__('parcel.parcel_create'), ['merchant'=>$merchant,'shops'=>$shops,'deliveryCategories'=>$deliveryCategories,'deliveryCharges'=>$deliveryCharges,'codCharges'=>$codCharges,'packagings'=>$packagings, 'fragileLiquid'=>$fragileLiquid, 'deliveryTypes'=>$deliveryTypes, 'zones'=>$zones, 'delays'=>$delays], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_create'), [], 500);

        }
    }


    public function store(Request $request)
    {
        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('parcel.title'), ['message' => $validator->errors()], 422);
        }
        $userID = auth()->user()->id;
        $merchant = $this->repo->getMerchant($userID);

        /**
         * Controle de solde — la regle du socle, enfin appliquee ici.
         *
         * Les deux ecrans web refusaient deja la creation quand les frais
         * depassaient le portefeuille. Ce chemin-ci, celui de l'app, ne le
         * faisait pas : le colis partait et le solde passait en negatif, sans
         * plancher. Le controle vit maintenant avec le debit
         * (`Services\Parcel\WalletDebit`) et remonte jusqu'ici.
         *
         * 422 et non 500 : le marchand peut agir, il lui suffit de recharger.
         * On lui renvoie de quoi le faire — ce qui manque, en entiers XOF, pour
         * que l'app propose directement le bon montant.
         */
        try {
            if($this->repo->store($request,$merchant->id)){
                return $this->responseWithSuccess(__('parcel.added_msg'), [], 200);
            }

            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
        catch (InsufficientWalletBalance $e) {
            return $this->responseWithError(
                __('parcel.wallet_insufficient', ['missing' => formatAmount($e->missing())]),
                [
                    'wallet_balance' => (int) round($e->available),
                    'required'       => (int) round($e->required),
                    'missing'        => (int) round($e->missing()),
                ],
                422
            );
        }
    }

    /**
     * Devis d'un colis : les montants AVANT creation, rien n'est ecrit.
     *
     * Meme service que `store()` — `ChargeCalculator`, seule source des montants
     * depuis la correction de S2 — avec les memes entrees. L'ecran de creation
     * peut donc afficher le prix que le serveur appliquera vraiment, au lieu de
     * le recalculer de son cote : c'etait exactement la faille S2, et un second
     * bareme cote client finirait par diverger du premier.
     *
     * Le marchand vient du jeton, jamais de la requete : on ne devise pas au nom
     * d'un autre.
     *
     * Attention : `vat` et `cod_charge` sont des TAUX en pourcentage, pas des
     * montants. `total_delivery_amount` est le sous-total HORS TVA (comportement
     * d'origine du socle, conserve) ; `total_payable_charges` ajoute la TVA,
     * c'est ce que le marchand paie reellement.
     */
    public function quote(Request $request, \App\Services\Parcel\ChargeCalculator $calculator)
    {
        $validator = Validator::make($request->all(), [
            'category_id'      => ['required','numeric'],
            'delivery_type_id' => ['required','numeric'],
            'cash_collection'  => ['nullable','numeric','min:0'],
            'weight'           => ['nullable'],
            'packaging_id'     => ['nullable','numeric'],
            // D4 — la route du colis. Facultative : sans zone, le devis est
            // celui d'avant.
            'zone_id'          => ['nullable','numeric'],
            'delay_id'         => ['nullable','numeric'],
        ]);

        if ($validator->fails()) {
            return $this->responseWithError(__('parcel.quote'), ['message' => $validator->errors()], 422);
        }

        try {
            $merchant = $this->repo->getMerchant(auth()->user()->id);
            if (blank($merchant)) {
                return $this->responseWithError(__('parcel.error_msg'), [], 403);
            }

            try {
                $charges = $calculator->calculate(
                    $merchant,
                    $request->category_id ? (int) $request->category_id : null,
                    $request->weight,
                    (float) $request->cash_collection,
                    $request->packaging_id ? (int) $request->packaging_id : null,
                    $request->fragileLiquid == 'on',
                    $request->zone_id ? (int) $request->zone_id : null,
                    $request->delay_id ? (int) $request->delay_id : null,
                    $request->destination_country
                );
            } catch (\App\Exceptions\UnpricedDeliveryException $exception) {
                // D4 — la route n'est pas tarifee. 422 plutot qu'un montant
                // invente : l'app doit pouvoir le dire au marchand.
                return $this->responseWithError(__('delivery_zone.route_not_priced'), [], 422);
            }

            // Somme rendue par le serveur pour qu'aucun client n'ait a decider si
            // le sous-total porte la TVA ou non.
            $charges['total_payable_charges'] = $charges['total_delivery_amount'] + $charges['vat_amount'];

            // Chantier 5 — l'ecran de creation appelle deja ce devis a chaque
            // changement : il y trouve donc aussi la reponse douaniere, sans un
            // second aller-retour. `customs` vaut null pour un colis domestique.
            $customs = app(\App\Services\Customs\CustomsService::class)
                ->ruleFor($request->destination_country, $request->customs_category);

            $charges['customs'] = $customs ? [
                'level' => $customs->level,
                'level_name' => $customs->level_name,
                'blocking' => $customs->isBlocking(),
                'required_document' => $customs->required_document,
                'message' => $customs->message,
            ] : null;

            return $this->responseWithSuccess(__('parcel.quote'), $charges, 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }



    public function logs($id)
    {
        try {
            $parcel       = $this->repo->get($id);
            // S17 — le repository ne rend que les colis du marchand connecte ;
            // hors de son perimetre on repond 404, jamais les donnees d'un autre.
            if (blank($parcel)) {
                return $this->responseWithError(__('parcel.not_found'), [], 404);
            }
            $parcelevents = $this->repo->parcelEvents($id);
            return $this->responseWithSuccess(__('parcel.parcel_logs'), [
                'parcel'         => new ParcelResource($parcel),
                'parcelEvents'   => ParcelLogsResource::collection($parcelevents),
                'customs_alerts' => $this->alertesDouanieresDe($parcel), // S82 (M1)
            ], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_logs'), [], 500);

        }
    }


    public function details($id)
    {

        try {
            $parcel       = $this->repo->details($id);
            if (blank($parcel)) {
                return $this->responseWithError(__('parcel.not_found'), [], 404);
            }
            $parcelEvents = $this->repo->parcelEvents($id);
            return $this->responseWithSuccess(__('parcel.parcel_details'), [
                'parcel'         => new ParcelResource($parcel),
                'parcelEvents'   => ParcelLogsResource::collection($parcelEvents),
                'customs_alerts' => $this->alertesDouanieresDe($parcel), // S82 (M1)
            ], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_details'), [], 500);

        }
    }

    /**
     * S82 (M1) — les alertes douanieres DU colis, sur son detail et son suivi.
     *
     * S68 avait ecarte « l'alerte sur le detail du colis » : `customs/alerts` n'a
     * pas de filtre par colis, et filtrer cote client une liste paginee mentirait
     * des la deuxieme page. Plutot qu'un parametre d'identifiant de plus sur une
     * route d'API (une entree de plus a classer dans les filets), le bloc s'adosse
     * a une ressource dont la portee est deja etablie : le colis est au marchand
     * connecte (S17), ses alertes le suivent. Un colis domestique rend `[]`.
     */
    private function alertesDouanieresDe($parcel)
    {
        return CustomsAlertResource::collection(
            $parcel->customsAlerts()->orderByDesc('id')->get()
        );
    }


    public function edit($id)
    {
        $userID = auth()->user()->id;
        $parcel = $this->repo->get($id);
        if (blank($parcel)) {
            return $this->responseWithError(__('parcel.not_found'), [], 404);
        }
        if($parcel->status == ParcelStatus::PENDING){

            $merchant = $this->repo->getMerchant($userID);
            $shops = $this->repo->getShops($merchant->id);
            $deliveryCategories = $this->repo->deliveryCategories();
            $deliveryCategoryCharges = $this->repo->deliveryCharges();
            $packagings = $this->repo->packaging();

            $deliveryTypes      = $this->repo->deliveryTypes();
            $codCharges = [];
            $i = 0;
            if(!blank($merchant)){
                foreach($merchant->cod_charges as $key => $charge){
                    $codCharges[$i]['name']       = __('merchant.'.$key);
                    $codCharges[$i]['charge']     = $charge;
                    $i++;
                }
            }
            $fragileLiquid = SettingHelper('fragile_liquid_charge');
            $deliveryCharges = DeliveryChargeResource::collection($this->merchantDeliveryCharges->getAll($merchant->id));
            // D4, etape 5 bis : la route proposee a l'ecran de creation. Listes
            // vides tant que la societe n'a pas de zones — l'app garde alors son
            // selecteur de type de livraison, et rien ne change.
            $zonesRepo = app(\App\Repositories\DeliveryZone\DeliveryZoneInterface::class);
            $zones = \App\Http\Resources\v10\DeliveryZoneResource::collection($zonesRepo->grilleMarchand($merchant->id));
            $delays = \App\Http\Resources\v10\DeliveryDelayResource::collection($zonesRepo->delais());

            return $this->responseWithSuccess(__('parcel.parcel_edit'), ['merchant'=>$merchant,'shops'=>$shops,'deliveryCategories'=>$deliveryCategories,'codCharges'=>$codCharges,'deliveryCharges'=>$deliveryCharges,'packagings'=>$packagings,'fragileLiquid'=>$fragileLiquid,'deliveryTypes'=>$deliveryTypes,'deliveryCategoryCharges'=>$deliveryCategoryCharges, 'zones'=>$zones, 'delays'=>$delays], 200);
        }
        else{
            return $this->responseWithError(__('parcel.edit_error_message'), [], 422);
        }

    }

    /**
     * Poser un statut sur son propre colis.
     *
     * Meme forme que `destroy()` : 404 si le colis n'est pas a ce marchand (on
     * ne revele pas son existence), 422 si la transition ne lui appartient pas.
     * La liste blanche vit dans `MerchantParcelRepository`.
     */
    public function statusUpdate($id, $statusId)
    {
        try {
            $parcel = $this->repo->get($id);
            if (blank($parcel)) {
                return $this->responseWithError(__('parcel.not_found'), [], 404);
            }
            if (!$this->repo->statusUpdate($id, $statusId)) {
                return $this->responseWithError(__('parcel.status_not_allowed'), [], 422);
            }
            return $this->responseWithSuccess(__('parcel.update_msg'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }


    public function update(Request $request,$id)
    {

        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());
        if ($validator->fails()) {
            return $this->responseWithError(__('parcel.title'), ['message' => $validator->errors()], 422);
        }
        // Meme reponse que les autres routes hors perimetre : 404, et pas le 500
        // generique que rendrait le `false` du repository.
        if (blank($this->repo->get($id))) {
            return $this->responseWithError(__('parcel.not_found'), [], 404);
        }
        // ⚠️ S46 — le socle passait ici `auth()->user()->id`, c'est-a-dire un
        // identifiant d'UTILISATEUR la ou le depot attend celui du MARCHAND.
        // `store()` et `duplicateStore()`, deux methodes plus haut, passent bien
        // `$merchant->id`. La confusion etait latente : le depot ecrit
        // `$parcel->merchant_id = $request->merchant_id ?? $merchant_id`, donc
        // une requete SANS `merchant_id` inscrivait un identifiant d'utilisateur
        // dans la colonne du marchand — un colis rattache a personne. La garde
        // de perimetre de S46 l'a mis au jour en comparant les deux.
        $merchant = $this->repo->getMerchant(auth()->user()->id);

        if($this->repo->update($id, $request, $merchant->id)){
            return $this->responseWithSuccess(__('parcel.update_msg'), [], 200);
        }else{
            return $this->responseWithError(__('parcel.error_msg'), [], 500);

        }
    }


    public function destroy($id)
    {
        try {
            $userID = auth()->user()->id;
            $parcel = $this->repo->get($id);
            if (blank($parcel)) {
                return $this->responseWithError(__('parcel.not_found'), [], 404);
            }
            if($parcel->status == ParcelStatus::PENDING){
                $this->repo->delete($id,$userID);
                return $this->responseWithSuccess(__('parcel.delete_msg'), [], 200);
            }else{
                return $this->responseWithError(__('parcel.delete_error_message'), [], 422);
            }
        }catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);

        }
    }

    public function parcelTrackingLogs($track_id){

        try {
            $parcelEvent = $this->repo->parcelTrack($track_id);
            if($parcelEvent):
                return $this->responseWithSuccess('Successfully parcel event founded.', $parcelEvent, 200);
            else:
                return $this->responseWithError(__('parcel.error_msg'), [], 500);
            endif;

        }catch (\Exception $e) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }


    public function ContactUs(Request $request){
        // S13 — mêmes règles que le formulaire web : l'adresse du visiteur
        // devient l'adresse de réponse du message, elle doit être valide.
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
        ]);
        try {
            Mail::send(new ContactMail($data));
            return $this->responseWithSuccess('Successfully sended.', [],200);
        } catch (\Throwable $th) {
             return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }

    public function subscribe(Request $request){
        try {

           if($this->repo->subscribe($request) === true):
                return $this->responseWithSuccess('Successfully subscribed.', [],200);
           elseif($this->repo->subscribe($request) == 1):
                return $this->responseWithError('Already subscribed.', ['exists' => 'true'],200);
           endif;
        } catch (\Throwable $th) {
             return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }

    public function DeliveryCharges(){
        try {
                $delivery_charges = $this->deliveryCharges->allGet();
                return $this->responseWithSuccess('Successfully delviery charge found.', $delivery_charges ,200);

        } catch (\Throwable $th) {
             return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }



    public function parcelAllStatus(){
        $status    = [];
       
        $status[]  = ['id'=>ParcelStatus::PENDING,                  'status'=>__('parcelStatus.'.ParcelStatus::PENDING)];
        $status[]  = ['id'=>ParcelStatus::PICKUP_ASSIGN,            'status'=>__('parcelStatus.'.ParcelStatus::PICKUP_ASSIGN)];
        $status[]  = ['id'=>ParcelStatus::PICKUP_RE_SCHEDULE,        'status'=>__('parcelStatus.'.ParcelStatus::PICKUP_RE_SCHEDULE)];
        $status[]  = ['id'=>ParcelStatus::RECEIVED_WAREHOUSE,        'status'=>__('parcelStatus.'.ParcelStatus::RECEIVED_WAREHOUSE)];
        $status[]  = ['id'=>ParcelStatus::DELIVERY_MAN_ASSIGN,       'status'=>__('parcelStatus.'.ParcelStatus::DELIVERY_MAN_ASSIGN)];
        $status[]  = ['id'=>ParcelStatus::DELIVERY_RE_SCHEDULE,      'status'=>__('parcelStatus.'.ParcelStatus::DELIVERY_RE_SCHEDULE)];
        $status[]  = ['id'=>ParcelStatus::RETURN_TO_COURIER,         'status'=>__('parcelStatus.'.ParcelStatus::RETURN_TO_COURIER)];
        $status[]  = ['id'=>ParcelStatus::PARTIAL_DELIVERED,          'status'=>__('parcelStatus.'.ParcelStatus::PARTIAL_DELIVERED)];
        $status[]  = ['id'=>ParcelStatus::DELIVERED,                  'status'=>__('parcelStatus.'.ParcelStatus::DELIVERED)];
        $status[]  = ['id'=>ParcelStatus::RETURN_RECEIVED_BY_MERCHANT, 'status'=>__('parcelStatus.'.ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)];
        return response()->json($status);
    }

    public function statusWiseParcelList($status){ 
        $parcels  = $this->repo->statusWiseParcelList($status);
        return StatusWiseParcelResource::collection($parcels);
    }   


}
