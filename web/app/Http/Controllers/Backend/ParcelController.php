<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Exceptions\InsufficientWalletBalance;
use App\Exports\ParcelSampleExport;
use App\Http\Controllers\Controller;
use App\Imports\ParcelImport;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\MerchantShops;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Http\Request;
use App\Http\Requests\Parcel\StoreRequest;
use App\Http\Requests\Parcel\UpdateRequest;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\User;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;

class ParcelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $merchant;
    protected $repo;
    protected $shop;
    public function __construct(
        ParcelInterface $repo,
        MerchantInterface $merchant,
        ShopsInterface $shop,
        DeliveryManInterface $deliveryman,
        HubInterface $hub
        )
    {
        $this->merchant     = $merchant;
        $this->repo         = $repo;
        $this->shop         = $shop;
        $this->deliveryman  = $deliveryman;
        $this->hub          = $hub;
    }
    public function index(Request $request)
    {
      

        $parcels        = $this->repo->all();
        $deliverymans   = $this->deliveryman->all();
        $hubs           = $this->hub->all();
        return view('backend.parcel.index',compact('parcels','deliverymans','hubs','request'));
    }

    public function filter(Request $request)
    {

        if($this->repo->filter($request)){
            $parcels      = $this->repo->filter($request);
            $parcelsPrint = $this->repo->filterPrint($request);
            $deliverymans = $this->deliveryman->all();
            $hubs         = $this->hub->all();
            $request['filter']='on';
            return view('backend.parcel.index',compact('parcels','deliverymans','hubs','request','parcelsPrint'));
        }else{
            return redirect()->back();
        }
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $merchants          = $this->merchant->all();
        $deliveryCategories = $this->repo->deliveryCategories();
        $deliveryCharges    = $this->repo->deliveryCharges();
        $packagings         = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        // D4, etape 5 bis : la route du colis. Listes vides tant que la societe
        // n'a pas de zones — le formulaire n'affiche alors rien de plus.
        $zones              = app(\App\Repositories\DeliveryZone\DeliveryZoneInterface::class)->zones();
        $delais             = app(\App\Repositories\DeliveryZone\DeliveryZoneInterface::class)->delais();
        return view('backend.parcel.create',compact('merchants','deliveryCategories','deliveryCharges','deliveryTypes','packagings','zones','delais'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {


        $parcel_count = Parcel::companywise()->count(); 
        if(!settings()->subscription): 
            Toastr::error('Something went wrong!', 'Error');
            return redirect()->route('subscription.index');
        elseif(settings()->subscription && settings()->subscription->parcel_count <= $parcel_count ):
            Toastr::error('You have limited parcel manage. Please upgrade your package.', 'Error');
            return redirect()->back(); 
        endif;


        //wallet use checking
        $merchant      = Merchant::find($request->merchant_id);
        // Meme remarque que dans le panneau marchand : le montant vient du
        // serveur, `chargeDetails` n'est plus envoye par l'ecran.
        if ($merchant->wallet_use_activation == Status::ACTIVE) :
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                $merchant,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                (float) $request->cash_collection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                $request->fragileLiquid == 'on',
                // D4, etape 6 : la route du colis, desormais son seul tarif.
                $request->zone_id ? (int) $request->zone_id : null,
                $request->delay_id ? (int) $request->delay_id : null,
                $request->destination_country
            );
            if ($charges['total_delivery_amount'] > $merchant->wallet_balance) :
                Toastr::error('This merchant has a low balance.', 'Error');
                return redirect()->back()->withInput($request->all());
            endif;
        endif;
        // end wallet use checking
  
        try {
            if($this->repo->store($request)){
                Toastr::success(__('parcel.added_msg'),__('message.success'));
                return redirect()->route('parcel.index');
            }
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
        // Filet du controle fait plus haut : entre les deux, le solde a pu
        // bouger. Le refus vient alors du debit lui-meme, verrou pose.
        catch (InsufficientWalletBalance $e) {
            return $this->soldeInsuffisant($e, $request);
        }
    }


    public function duplicateStore(StoreRequest $request)
    {

        $parcel_count = Parcel::companywise()->count();
        if(!settings()->subscription): 
            Toastr::error('Something went wrong!', 'Error');
            return redirect()->back();
        elseif(settings()->subscription && settings()->subscription->parcel_count <= $parcel_count ):
            Toastr::error('You have limited parcel manage. Please upgrade your package.', 'Error');
            return redirect()->back(); 
        endif;
         
        try {
            if($this->repo->duplicateStore($request)){
                Toastr::success(__('parcel.added_msg'),__('message.success'));
                return redirect()->route('parcel.index');
            }
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
        // Dupliquer, c'est creer : le socle ne controlait pas le solde ici.
        catch (InsufficientWalletBalance $e) {
            return $this->soldeInsuffisant($e, $request);
        }
    }

    /**
     * Le solde du marchand ne couvre pas les frais : le dire, et rendre la
     * saisie plutot que de la perdre.
     */
    private function soldeInsuffisant(InsufficientWalletBalance $e, $request)
    {
        Toastr::error(
            __('parcel.merchant_wallet_insufficient', ['missing' => formatAmount($e->missing())]),
            __('message.error')
        );

        return redirect()->back()->withInput($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }


    // Parcel logs
    public function logs($id)
    {
        $parcel         = $this->repo->get($id);
        // S25 — `ParcelRepository::get()` est `companywise()`, `parcelEvents()`
        // ne l'est pas : sans cette garde, un colis hors périmètre rendait un
        // `$parcel` nul MAIS la chronologie de l'autre société — livreur, dates,
        // chemins de la photo et de la signature de livraison. On ne scope pas
        // `parcelEvents()` lui-même : le suivi PUBLIC par numéro l'appelle
        // (`Frontend\FrontendController`), et c'est sa raison d'être (cf. S17).
        abort_if(blank($parcel), 404);

        $parcelevents   = $this->repo->parcelEvents($parcel->id);
        return view('backend.parcel.logs', compact('parcel','parcelevents'));
    }

    // Parcel duplicate
    public function duplicate($id)
    {
        $parcel                  = $this->repo->get($id);
        // S33 — hors perimetre le depot rend `null` et la ligne suivante
        // dereferencait : 500 la ou le reste du module repond 404 (famille S15).
        abort_if(blank($parcel), 404);

        $merchant                = $this->merchant->get($parcel->merchant_id);
        $shops                   = $this->shop->all($parcel->merchant_id);
        $deliveryCharges         = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();
        $deliveryCategories      = $this->repo->deliveryCategories();
        $deliveryCategoryCharges = $this->repo->deliveryCharges();
        $packagings              = $this->repo->packaging();
        $deliveryTypes           = $this->repo->deliveryTypes();
        return view('backend.parcel.duplicate',compact('parcel','merchant','shops','deliveryCategories','deliveryTypes','deliveryCategoryCharges','deliveryCharges','packagings'));
    }

    // Parcel details
    public function details($id)
    {
        // return $this->repo->details($id);
        $parcel         = $this->repo->details($id);
        // S33 — sans ce garde, la vue rendait une page VIDE avec un 200 : elle
        // dereference `$parcel` partout avec l'operateur `@`, qui supprime l'erreur.
        // Un refus d'acces devenait donc une page blanche, sans message.
        abort_if(blank($parcel), 404);

        // ⚠️ `$parcelevents` etait construit avec l'identifiant BRUT, sans perimetre.
        // Verifie : `details.blade.php` ne l'utilise pas — il n'y avait donc pas de
        // fuite de chronologie par cet ecran, contrairement a ce que sa forme suggere.
        // On passe malgre tout le colis DEJA verifie, comme `logs()` et
        // `deliveredInfo()` depuis S25, pour que la forme ne redevienne pas un piege.
        $parcelevents   = $this->repo->parcelEvents($parcel->id);
        return view('backend.parcel.details',compact('parcel','parcelevents'));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $parcel          = $this->repo->get($id);
        // S33 — hors perimetre le depot rend `null` et la ligne suivante
        // dereferencait : 500 la ou le reste du module repond 404 (famille S15).
        abort_if(blank($parcel), 404);

        $merchant        = $this->merchant->get($parcel->merchant_id);
        $shops           = $this->shop->all($parcel->merchant_id);
        $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();

        $deliveryCategories      = $this->repo->deliveryCategories();
        $deliveryCategoryCharges = $this->repo->deliveryCharges();

        $packagings              = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        return view('backend.parcel.edit',compact('parcel','merchant','shops','deliveryCategories','deliveryTypes','deliveryCategoryCharges','deliveryCharges','packagings'));
    }

    // Parcel update
    public function statusUpdate($id, $status_id)
    {
        // Le repository refuse un colis d'une autre societe : ne pas annoncer
        // un succes quand rien n'a ete ecrit.
        if(!$this->repo->statusUpdate($id, $status_id)){
            Toastr::error(__('parcel.not_found'),__('message.error'));
            return redirect()->route('parcel.index');
        }
        Toastr::success(__('parcel.update_msg'),__('message.success'));
        return redirect()->route('parcel.index');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRequest $request, $id)
    {
        if($this->repo->update($id, $request)){
            Toastr::success(__('parcel.update_msg'),__('message.success'));
            return redirect()->route('parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // S33 — le depot refuse un colis d'une autre societe ; l'ecran annoncait
        // quand meme un succes. On repond 404.
        abort_unless($this->repo->delete($id), 404);

        Toastr::success(__('parcel.delete_msg'),__('message.success'));
        return back();
    }

    public function parcelImportExport()
    {
        $deliveryCategories = $this->repo->deliveryCategories();

        return view('backend.parcel.import',compact('deliveryCategories'));
    }

    public function parcelImport(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);
        $import = new ParcelImport();
        try {
            $import->import($request->file('file'));
        }
        // Idem cote back-office : rien n'est entre, et l'operateur a besoin de
        // savoir quel marchand bloque et de combien.
        catch (InsufficientWalletBalance $e) {
            Toastr::error(
                __('parcel.import_merchant_wallet_insufficient', [
                    'line'     => $import->ligneCourante(),
                    'merchant' => $e->merchant->business_name,
                    'missing'  => formatAmount($e->missing()),
                ]),
                __('message.error')
            );

            return back();
        }
        catch (ValidationException $e) {
            $failures     = $e->failures();
            $importErrors = [];
            foreach ($failures as $failure) {
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
                $importErrors[$failure->row()][] = $failure->errors()[0];
            }
            return back()->with('importErrors', $importErrors);
        }
        Toastr::success(__('parcel.added_msg'),__('message.success'));
        return redirect()->route('parcel.index');
    }

    public function getImportMerchant(Request $request){
        $search   = $request->search;
        $response = array();
        if($request->searchQuery == 'true'){
            if($search == ''){
                $merchants = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name','vat')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
            }else{
                $merchants = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name','vat')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
            }

            foreach($merchants as $merchant){
                $response[] = array(
                    "id"=>$merchant->id,
                    "text"=>$merchant->id.' = '.$merchant->business_name,
                );
            }
            return response()->json($response);
        }

    }

    public function getMerchant(Request $request){
        $search   = $request->search;
        $response = array();
        if($request->searchQuery == 'true'){
            if($search == ''){
                $merchants = [];
            }else{
                $merchants = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name','vat')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
            }

            foreach($merchants as $merchant){
                $response[] = array(
                    "id"=>$merchant->id,
                    "text"=>$merchant->business_name,
                );
            }
            return response()->json($response);
        }else {
            $merchant = Merchant::find($search);

            $response[] = array(
                "vat"         =>\App\Services\Parcel\VatRate::for($merchant),
                "cod_charges" =>$merchant->cod_charges,
            );
            return response()->json($response);
        }

    }


    // Hub search
    public function getHub(Request $request){
        $search   = $request->search;
        $response = array();
        if($request->searchQuery == 'true'){
            if($search == ''){
                $hubs = [];
            }
            else{
                $hubs = Hub::companywise()->where('status',Status::ACTIVE)->orderby('name','asc')->select('id','name')->where('name', 'like', '%' .$search . '%')->limit(10)->get();
            }
            foreach($hubs as $hub){
                $response[] = array(
                    "id"=>$hub->id,
                    "text"=>$hub->name,
                );
            }
            return response()->json($response);
        }
    }


    public function getMerchantCod(Request $request){


        if(request()->ajax()):
            $merchant = [];

            $merchant = Merchant::find($request->merchant_id);

            $merchant = [
                    'inside_city'  => $merchant->cod_charges['inside_city'],
                    'sub_city' => $merchant->cod_charges['sub_city'],
                    'outside_city' => $merchant->cod_charges['outside_city']
            ];
            return response()->json($merchant);
        endif;
        return '';


    }

    /**
     * S40 — le perimetre des boutiques passe par le MARCHAND.
     *
     * `merchant_shops` ne porte aucune colonne `company_id` : c'est le marchand
     * qui rattache la boutique a une societe. Un `companywise()` sur ce modele
     * n'existe donc pas, et ne peut pas exister — d'ou cette jointure.
     */
    private function boutiquesDeLaSociete()
    {
        return MerchantShops::whereHas('merchant', function($query){
            $query->where('company_id', settings()->id);
        });
    }

    /**
     * S40 — un evenement de colis, a condition que le COLIS soit chez nous.
     *
     * `parcel_events` n'a pas non plus de `company_id` : le perimetre vient du
     * colis. C'est la forme deja retenue en S25 pour la chronologie.
     */
    private function evenementDeLaSociete($parcelId, $statut)
    {
        return ParcelEvent::whereHas('parcel', function($query){
                $query->companywise();
            })
            ->where(['parcel_id'=>$parcelId,'parcel_status'=>$statut])
            ->first();
    }

    public function merchantShops(Request $request)
    {
        if (request()->ajax()) {
            if ($request->id && $request->shop == 'true') {
                $merchantShops          = [];
                $merchantShop           = $this->boutiquesDeLaSociete()->where(['merchant_id'=>$request->id,'default_shop'=>Status::ACTIVE])->first();
                // S40 — le socle empilait ce resultat SANS le verifier, et
                // `shops.blade.php` dereference `$shop->id`. Un marchand sans
                // boutique par defaut rendait donc un 500 ; avec le perimetre,
                // c'etait le cas de tout identifiant etranger — un refus
                // annonce comme une panne.
                if(!blank($merchantShop)){
                    $merchantShops[]    = $merchantShop;
                }
                $merchantShopArray      = $this->boutiquesDeLaSociete()->where(['merchant_id'=>$request->id,'default_shop'=>Status::INACTIVE])->get();
                if(!blank($merchantShopArray)){
                    foreach ($merchantShopArray as $shop){
                        $merchantShops[] = $shop;
                    }
                }

                if (!blank($merchantShops)) {
                    return view('backend.parcel.shops', compact('merchantShops'));
                }
                return '';
            }else {
                $merchantShop = $this->boutiquesDeLaSociete()->find($request->id);
                if (!blank($merchantShop)) {
                    return $merchantShop;
                }
                return '';
            }
        }
        return '';
    }


    public function deliveryWeight(Request $request)
    {
        if (request()->ajax()) {
            if ($request->category_id) {
                $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$request->category_id)->get();

                if (!blank($deliveryCharges)) {
                    return view('backend.parcel.deliveryWeight', compact('deliveryCharges'));
                }
                return '';
            }
        }
        return '';
    }




    //delivery man search

    public function transferHub(Request $request){


        // S40 — double fuite : l'evenement etait lu sans perimetre (celui du
        // colis d'un autre transporteur donc), et `Hub::orderByDesc('id')`
        // rendait les entrepots de tout le monde. Sur un colis inconnu,
        // `$parcelEvent->hub_id` sur `null` rendait en plus un 500.
        $parcelEvent = $this->evenementDeLaSociete($request->parcel_id, ParcelStatus::RECEIVED_WAREHOUSE);
        $hubs        = Hub::companywise()->orderByDesc('id')->whereNotIn('id',[optional($parcelEvent)->hub_id])->get();
             $response = '';
        foreach ($hubs as $hub){
            $response .= '<option value="'.$hub->id.'" selected> '.$hub->name.'</option>';
        }
        return $response;
    }


    public function deliverymanSearch(Request $request){

        $search = $request->search;
        if($request->single){
            // S40 — evenement lu sans perimetre : cette aide rendait le NOM du
            // livreur ou du ramasseur affecte au colis d'un autre transporteur.
            // Et sur un colis sans evenement, les deux branches dereferencaient
            // `null` — un 500 la ou un vide suffit.
            $deliveryMan  = $this->evenementDeLaSociete($request->parcel_id, $request->status);

            if(blank($deliveryMan)){
                return '';
            }
            if(isset($deliveryMan->deliveryMan) && !blank($deliveryMan->deliveryMan)){
                $response = '<option value="'.$deliveryMan->delivery_man_id.'" selected> '.$deliveryMan->deliveryMan->user->name.'</option>';

            }else {
                $response = '<option value="'.$deliveryMan->pickup_man_id.'" selected> '.$deliveryMan->pickupman->user->name.'</option>';

            }
            return $response;
        }else{
            if($search == ''){
                $deliverymans = [];
            }else{
                $deliverymans = User::companywise()->where('status',Status::ACTIVE)
                                      ->orderby('name','asc')
                                      ->select('id','name')
                                      ->where('name', 'like', '%' .$search . '%')
                                      ->where('user_type',UserType::DELIVERYMAN)->limit(10)->get();
            }
            $response=[];

            foreach($deliverymans as $deliveryman){
                $response[] = array(
                    "id"  => $deliveryman->deliveryman->id,
                    "text"=> $deliveryman->name,
                );
            }
            return response()->json($response);
        }


    }

    //parcel search in recived by hub
    public function parcelRecivedByHubSearch(Request $request){

        if($request->ajax()){
            $hub      = $request->hub_id;
            $track_id = $request->track_id;

            if($track_id && $hub){
                        $parcel      = Parcel::companywise()->with(['merchant','merchant.user'])->where([
                                                    'tracking_id'     => $request->track_id,
                                                    'transfer_hub_id' => $hub,
                                                    'status'          => ParcelStatus::TRANSFER_TO_HUB
                                                ])->first();
                    if($parcel){
                        return response()->json($parcel);
                    }else{
                        return 0;
                    }
            }
        }

    }

    public function transfertohubSelectedHub(Request $request){
        // S40 — `Parcel::find()` nu : on rendait le nom de l'entrepot auquel
        // est rattache le colis d'un autre transporteur.
        $parcel          = Parcel::companywise()->find($request->parcel_id);
        if($parcel){
            if($parcel->hub_id){
                return '<option selected disabled>'.$parcel->hub->name.'</option>';
            }else{
                return '<option selected disabled>Hub not found</option>';
            }
        }else{
                return '<option selected disabled>Hub not found</option>';

        }
    }

    public function PickupManAssigned(Request $request){


        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;
        if($this->repo->pickupdatemanAssigned($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_man_assigned'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }

    public function PickupManAssignedCancel(Request $request){

        if($this->repo->pickupdatemanAssignedCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_man_assigned'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function PickupReSchedule(Request $request){

        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'=>'required',
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->PickupReSchedule($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_scheduled'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }

    public function PickupReScheduleCancel(Request $request){

        if($this->repo->PickupReScheduleCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_reschedule_canceled'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function receivedBypickupman(Request $request){

        if($this->repo->receivedBypickupman($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_pickup_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function receivedByHub(Request $request){

        if($this->repo->receivedByHub($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_hub'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function receivedByHubCancel(Request $request){

        if($this->repo->receivedByHubCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_hub_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }




    public function receivedBypickupmanCancel(Request $request){

        if($this->repo->receivedBypickupmanCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_pickup_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function search(Request $data)
    {
        return $this->repo->search($data);
    }

    public function searchDeliveryManAssingMultipleParcel(Request $data)
    {
        return $this->repo->searchDeliveryManAssingMultipleParcel($data);
    }

    public function searchExpense(Request $data)
    {
        return $this->repo->searchExpense($data);
    }

    public function searchIncome(Request $data)
    {
        return $this->repo->searchIncome($data);
    }



    public function transferToHubMultipleParcel(Request $request){


        $validator=Validator::make($request->all(),[
            'hub_id'     => 'required',
            'parcel_ids' => 'required',
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->transferToHubMultipleParcel($request)){
            Toastr::success(__('parcel.transfer_to_hub_success'),__('message.success'));

            $deliveryman    = $this->deliveryman->get($request->delivery_man_id);
            $parcels        = $this->repo->bulkParcels($request->parcel_ids);
            $bulk_type      = ParcelStatus::TRANSFER_TO_HUB;
            $transfered_hub = Hub::find($request->hub_id);
            return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type','transfered_hub'));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }

    }


    public function deliveryManAssignMultipleParcel(Request $request){
        $validator=Validator::make($request->all(),[
            'delivery_man_id' => 'required',
            'parcel_ids_'     => 'required',
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->deliveryManAssignMultipleParcel($request)){
            Toastr::success(__('parcel.delivery_man_assign_success'),__('message.success'));
            $deliveryman= $this->deliveryman->get($request->delivery_man_id);
            $parcels    = $this->repo->bulkParcels($request->parcel_ids_);
            $bulk_type  = ParcelStatus::DELIVERY_MAN_ASSIGN;
            return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type'));

        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }
    }


    public function ParcelBulkAssignPrint(Request $request){
        try {

            $deliveryman  = $this->deliveryman->get($request->delivery_man_id);
            $parcels      = $this->repo->bulkParcels($request->parcels);
            $bulk_type    = ParcelStatus::DELIVERY_MAN_ASSIGN;
            $reprint = true;
            return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type','reprint'));

        } catch (\Throwable $th) {
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }




    public function transfertohub(Request $request){

        $validator=Validator::make($request->all(),[
            'hub_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->transfertohub($request->parcel_id, $request)){
            Toastr::success(__('parcel.transfer_to_hub_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function transfertoHubCancel(Request $request){

        if($this->repo->transfertoHubCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.transfer_to_hub_canceled'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }





    public function deliverymanAssign(Request $request){


        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->deliverymanAssign($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivery_man_assign_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function deliverymanAssignCancel(Request $request){

        if($this->repo->deliverymanAssignCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.deliveryman_assign_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect()->back();
        }
    }



    public function deliveryReschedule(Request $request){

        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'           => 'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->deliveryReschedule($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivery_reschedule_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function deliveryReScheduleCancel(Request $request){

        if($this->repo->deliveryReScheduleCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivery_re_schedule_cancel'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }



    public function receivedWarehouse(Request $request){
        $validator=Validator::make($request->all(),[
            'hub_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->receivedWarehouse($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_warehouse_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function receivedWarehouseCancel(Request $request){

        if($this->repo->receivedWarehouseCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_warehouse_cancel'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }



    public function returntoQourier(Request $request){
        if($this->repo->returntoQourier($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_to_qourier_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function returntoQourierCancel(Request $request){

        if($this->repo->returntoQourierCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_warehouse_cancel'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }





    public function returnAssignToMerchant(Request $request){
        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'           =>'required'

        ]);
        if($validator->fails()):
            toast(__('parcel.required'),'error');
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;
        if($this->repo->returnAssignToMerchant($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }
    }



    public function returnAssignToMerchantCancel(Request $request){

        if($this->repo->returnAssignToMerchantCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
    public function returnAssignToMerchantReschedule(Request $request){

        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'           =>'required'

        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->returnAssignToMerchantReschedule($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_reschedule_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function returnAssignToMerchantRescheduleCancel(Request $request){

        if($this->repo->returnAssignToMerchantRescheduleCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_reschedule_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }



    public function returnReceivedByMerchant(Request $request){
        if($this->repo->returnReceivedByMerchant($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_received_by_merchant'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function returnReceivedByMerchantCancel(Request $request){

        if($this->repo->returnReceivedByMerchantCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_received_by_merchant_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }






    public function parcelDelivered(Request $request){
        if($this->repo->parcelDelivered($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivered_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function parcelDeliveredCancel(Request $request){

        if($this->repo->parcelDeliveredCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivered_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }




    public function parcelPartialDelivered(Request $request){

        $validator = Validator::make($request->all(),[
            'cash_collection'       => 'required',
        ]);

        if($validator->fails()){
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }

        if($this->repo->parcelPartialDelivered($request->parcel_id, $request)){
            Toastr::success(__('parcel.partial_delivered_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function parcelPartialDeliveredCancel(Request $request){
        if($this->repo->parcelPartialDeliveredCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.partial_delivered_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function parcelPrint($id)
    {

        $parcel = $this->repo->get($id);
        abort_if(blank($parcel), 404); // S33
        $merchant = $this->merchant->get($parcel->merchant_id);
        $shops = $this->shop->all($parcel->merchant_id);
        return view('backend.parcel.print',compact('parcel','merchant','shops'));
    }

    public function parcelPrintLabel($id)
    {
        $parcel = $this->repo->get($id);
        abort_if(blank($parcel), 404); // S33
        $merchant = $this->merchant->get($parcel->merchant_id);
        $shops = $this->shop->all($parcel->merchant_id);
        return view('backend.parcel.print-label',compact('parcel','merchant','shops'));
    }


    //multiple parcel label print
      public function parcelMultiplePrintLabel(Request $request){
        $validator=Validator::make($request->all(),[
            'parcels' =>'required'
        ]);
        if($validator->fails()):
            Toastr::error('Must be select parcel.',__('message.error'));
            return redirect()->back();
        endif;
        $parcels = $this->repo->parcelMultiplePrintLabel($request);
        return view('backend.parcel.multiple-print-label',compact('parcels'));
    }
    //end multiple parcel label print

    public function parcelReceivedByMultipleHub(Request $request){
            if($this->repo->parcelReceivedByMultipleHub($request->parcel_id,$request)){
                Toastr::success(__('parcel.received_by_multiple_hub'),__('message.success'));
                return redirect(paginate_redirect($request));
            }else{
                Toastr::error(__('parcel.error_msg'),__('message.error'));
                return redirect(paginate_redirect($request));
            }
    }


    //Assign pickup bulk

    public function AssignPickupParcelSearch(Request $request){
        if($request->ajax()){
            $merchant_id      = $request->merchant_id;
            $tracking_id      = $request->tracking_id;

            if($merchant_id !== null && $tracking_id !== null){

                        $parcel      = Parcel::companywise()->with(['merchant','merchant.user'])->where([
                                                    'merchant_id'     => $merchant_id,
                                                    'tracking_id'     => $tracking_id,
                                                    'status'          => ParcelStatus::PENDING
                                                ])->first();

                    if($parcel){
                        return response()->json($parcel);
                    }else{
                        return 0;
                    }
            }else{

               return 0;
            }
        }

        return 0;
    }




    //assign pickup bulk store
    public function AssignPickupBulk(Request $request){
        $validator = Validator::make($request->all(),[
            'merchant_id'       => 'required',
            'delivery_man_id'   => 'required'
        ]);

        if($validator->fails()){
            Toastr::error(__('parcel.feild_required'),__('message.error'));
            return redirect(paginate_redirect($request));
        }

        if($this->repo->pickupdatemanAssignedBulk($request)){
            Toastr::success(__('parcel.pickup_man_assigned'),__('message.success'));
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }

    }


    //assign return to merchant

    //return to courier percel will be show
    public function AssignReturnToMerchantParcelSearch(Request $request){
        if($request->ajax()){
            $merchant_id      = $request->merchant_id;
            $tracking_id      = $request->tracking_id;

            if($merchant_id !== null && $tracking_id !== null){

                        $parcel      = Parcel::companywise()->with(['merchant','merchant.user'])->where([
                                                    'merchant_id'     => $merchant_id,
                                                    'tracking_id'     => $tracking_id,
                                                    'status'          => ParcelStatus::RETURN_TO_COURIER
                                                ])->first();

                    if($parcel){
                        return response()->json($parcel);
                    }else{
                        return 0;
                    }
            }else{

               return 0;
            }
        }

        return 0;
    }


    //assign return to merchant bulk store
    public function AssignReturnToMerchantBulk(Request $request){

            $validator = Validator::make($request->all(),[
                'merchant_id'       => 'required',
                'delivery_man_id'   => 'required',
                'date'              => 'required'
            ]);

            if($validator->fails()){
                Toastr::error(__('parcel.feild_required'),__('message.error'));
                return redirect(paginate_redirect($request));
            }


            if($this->repo->AssignReturnToMerchantBulk($request)){
                Toastr::success(__('parcel.return_assign_to_merchant_success'),__('message.success'));

                $deliveryman    = $this->deliveryman->get($request->delivery_man_id);
                $parcels        = $this->repo->bulkParcels($request->parcel_ids);
                $bulk_type      = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
                return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type'));

            }else{
                Toastr::error(__('parcel.error_msg'),__('message.error'));
                return redirect(paginate_redirect($request));
            }

    }


    //received warehouse hub auto selected
    public function warehouseHubSelected(Request $request){
        $hubs_list  = "";
        $hubs_list .= "<option>".__("menus.select")." ". __("hub.title") ."</option>";

        // S40 — `Hub::all()` rendait la liste des entrepots de TOUS les
        // transporteurs : leurs noms, dans une liste deroulante du back-office.
        if($request->hub_id):
            $hubs=Hub::companywise()->get();
            foreach ($hubs as $hub) {

                if($hub->id == $request->hub_id){
                    $hubs_list .= "<option selected value=".$hub->id." >".$hub->name."</option>";
                }else{
                    $hubs_list .= "<option   value='".$hub->id."' >".$hub->name."</option>";
                }
            }
          else:
            $hubs=Hub::companywise()->get();
            foreach ($hubs as $key => $hub) {

                $hubs_list .= "<option   value='".$hub->id."' >".$hub->name."</option>";

            }
          endif;

          return $hubs_list;
    }


    public function ParcelSearchs(Request $request)
    {

        if($this->repo->parcelSearchs($request)){
            $parcels          = $this->repo->parcelSearchs($request);
            $deliverymans = $this->deliveryman->all();
            $hubs         = $this->hub->all();
            // $request['search']='on';
            return view('backend.parcel.index',compact('parcels','request','deliverymans','hubs'));
        }else{
            return redirect()->back();
        }
    }


    //parcel sample export
    public function parcelSampleExport(){
          return Excel::download(new ParcelSampleExport,'invoice.xlsx');
    }



    public function priorityUpdate(Request $request){

        // S40 — `Parcel::where(['id'=>…])->first()` NU, suivi d'un `save()` : on
        // changeait la priorite du colis d'un AUTRE transporteur. Et sur un
        // identifiant inconnu, le dereferencement de `null` rendait un 500.
        $parcel = Parcel::companywise()->find($request->id);
        abort_if(blank($parcel), 404);

        if(1 == (int)$request->priority){
            $parcel->priority_type_id      =  2;
        }else {
            $parcel->priority_type_id      =  1;
        }
        $parcel->save();

        return $parcel;
    }
    // Parcel parcelDeliveryMan
    public function parcelDeliveryMan()
    {
        // $parcelEvents = ParcelEvent::with('parcel')->whereNotNull('delivery_man_id')->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->get();
        // $mapParcels = [];
        // $mapParcelslocations = [];
        // if(!blank($parcelEvents)) {
        //     foreach($parcelEvents as $key => $parcelEvent) {
        //         $mapParcelslocations[] = ['location'=> $parcelEvent->parcel->customer_address];

        //         $mapParcels[$key]['deliveryMan'] = optional($parcelEvent->deliveryMan->user)->name;
        //         $mapParcels[$key]['deliveryPhone'] = optional($parcelEvent->deliveryMan->user)->mobile;
        //         $mapParcels[$key]['deliveryImage'] = optional($parcelEvent->deliveryMan->user)->image;
        //         // $mapParcels[$key]['lat'] = $parcelEvent->delivery_lat;
        //         // $mapParcels[$key]['long'] = $parcelEvent->delivery_long;
        //         $mapParcels[$key]['lat'] = $parcelEvent->parcel->customer_lat;
        //         $mapParcels[$key]['long'] = $parcelEvent->parcel->customer_long;
        //         $mapParcels[$key]['customer_name'] = $parcelEvent->parcel->customer_name;
        //         $mapParcels[$key]['customer_address'] = $parcelEvent->parcel->customer_address;
        //         $mapParcels[$key]['customer_phone'] = $parcelEvent->parcel->customer_phone;
        //         $mapParcels[$key]['merchant_business_name'] = $parcelEvent->parcel->merchant->business_name;
        //         $mapParcels[$key]['merchant_phone'] = $parcelEvent->parcel->merchant->user->mobile;
        //         $mapParcels[$key]['merchant_address'] = $parcelEvent->parcel->merchant->address;
        //         $mapParcels[$key]['current_payable'] = $parcelEvent->parcel->current_payable;
        //         $mapParcels[$key]['tracking_id'] = $parcelEvent->parcel->tracking_id;
        //         $mapParcels[$key]['url'] = route('parcel.logs',$parcelEvent->parcel->id);
        //     }
        // }
        
        // $parcelsLocations = $mapParcelslocations;


        //parcel location 
            $mapParcels = [];
            $mapParcelslocations = [];
            $parcels = Parcel::where('status',[ParcelStatus::DELIVERY_MAN_ASSIGN,ParcelStatus::DELIVERY_RE_SCHEDULE])->get();
            if(!blank($parcels)) {
                foreach($parcels as $key => $parcel) {
                    $mapParcelslocations[] = ['location'=> $parcel->customer_address];
    
                    $mapParcels[$key]['lat'] = $parcel->customer_lat;
                    $mapParcels[$key]['long'] = $parcel->customer_long;
                    $mapParcels[$key]['customer_name'] = $parcel->customer_name;
                    $mapParcels[$key]['customer_address'] = $parcel->customer_address;
                    $mapParcels[$key]['customer_phone'] = $parcel->customer_phone;
                    $mapParcels[$key]['merchant_business_name'] = $parcel->merchant->business_name;
                    $mapParcels[$key]['merchant_phone'] = $parcel->merchant->user->mobile;
                    $mapParcels[$key]['merchant_address'] = $parcel->merchant->address;
                    $mapParcels[$key]['current_payable'] = $parcel->current_payable;
                    $mapParcels[$key]['tracking_id'] = $parcel->tracking_id;
                    $mapParcels[$key]['url'] = route('parcel.logs',$parcel->id);

                }
            }
        $parcelsLocations = $mapParcelslocations;
        //end parcel locations

        return view('backend.parcel.parcel-map-logs', compact('mapParcels','parcelsLocations'));
    }

    public function deliveredInfo($id)
    {
        $parcel         = $this->repo->get($id);
        abort_if(blank($parcel), 404);   // S25 — voir `logs()` ci-dessus

        $parcelevents   = $this->repo->parcelEvents($parcel->id);
        return view('backend.parcel.parcel-delivered-info', compact('parcel','parcelevents'));
    }

}

