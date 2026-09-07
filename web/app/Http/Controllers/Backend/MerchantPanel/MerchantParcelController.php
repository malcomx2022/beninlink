<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Http\Controllers\Controller;
use App\Imports\ParcelImport;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\MerchantShops;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Parcel\StoreRequest;
use App\Http\Requests\MerchantPanel\Parcel\UpdateRequest;
use App\Models\Backend\DeliveryMan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Enums\ParcelStatus;
use App\Exports\MerchantParcelExport;
use App\Http\Resources\MerchantParcelExportResource;
use App\Models\Backend\ParcelEvent; 
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class MerchantParcelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $merchant;
    protected $repo;
    protected $shop;
    public function __construct(MerchantParcelInterface $repo, MerchantInterface $merchant, ShopsInterface $shop)
    {
        $this->merchant = $merchant;
        $this->repo = $repo;
        $this->shop = $shop;
    }
    public function index(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        $parcels = $this->repo->all($merchant->id);
        return view('backend.merchant_panel.parcel.index',compact('parcels','request' ));
    }
    public function parcelBank(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        $parcels = $this->repo->parcelBank($merchant->id);
        return view('backend.merchant_panel.parcel.parcel_bank',compact('parcels','request' ));
    }

    public function filter(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        if($this->repo->filter($merchant->id,$request)){
            $parcels      = $this->repo->filter($merchant->id,$request);
            return view('backend.merchant_panel.parcel.index',compact('parcels','request' ));
        }else{
            return redirect()->back();
        }
    }

    public function create()
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        $shops = $this->repo->getShops($merchant->id);
        $merchantShop = $shops[0];
        $deliveryCategories = $this->repo->deliveryCategories();
        $deliveryCharges = $this->repo->deliveryCharges();
        $packagings = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        // D4, etape 5 bis : la route du colis (voir ParcelController::create).
        $zones              = app(\App\Repositories\DeliveryZone\DeliveryZoneInterface::class)->zones();
        $delais             = app(\App\Repositories\DeliveryZone\DeliveryZoneInterface::class)->delais();
        return view('backend.merchant_panel.parcel.create',compact('merchant','merchantShop','deliveryTypes','shops','deliveryCategories','deliveryCharges','packagings','zones','delais'));
    }

    public function store(StoreRequest $request)
    {

        // Le montant compare vient du serveur, plus de `chargeDetails` : ce champ
        // etait rempli en JavaScript et n'est plus envoye depuis que l'ecran
        // affiche le devis. On garde la comparaison d'origine, sur le
        // sous-total HORS TVA.
        if(Auth::user()->merchant->wallet_use_activation == Status::ACTIVE):
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                Auth::user()->merchant,
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
            if($charges['total_delivery_amount'] > Auth::user()->merchant->wallet_balance):
                Toastr::error('You are low on balance. Please recharge', 'Error');
                return redirect()->route('merchant-panel.my.wallet.index');
            endif;
        endif;

        
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        try {
            if($this->repo->store($request,$merchant->id)){
                Toastr::success(__('parcel.added_msg'),__('message.success'));
                return redirect()->route('merchant-panel.parcel.index');
            }
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
        // Filet du controle fait plus haut : le devis a pu changer entre les
        // deux, ou deux creations simultanees viser le meme solde. Le refus
        // vient alors du debit lui-meme, verrou pose.
        catch (InsufficientWalletBalance $e) {
            return $this->versLaRecharge($e);
        }
    }

    public function duplicateStore(StoreRequest $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        try {
            if($this->repo->duplicateStore($request,$merchant->id)){
                Toastr::success(__('parcel.added_msg'),__('message.success'));
                return redirect()->route('merchant-panel.parcel.index');
            }
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
        // Dupliquer, c'est creer : le socle ne controlait pas le solde ici.
        catch (InsufficientWalletBalance $e) {
            return $this->versLaRecharge($e);
        }
    }

    /**
     * Dire au marchand ce qui manque, et l'emmener la ou il peut le regler.
     */
    private function versLaRecharge(InsufficientWalletBalance $e)
    {
        Toastr::error(
            __('parcel.wallet_insufficient', ['missing' => formatAmount($e->missing())]),
            __('message.error')
        );

        return redirect()->route('merchant-panel.my.wallet.index');
    }


    public function show($id)
    {
        //
    }

    // Parcel logs
    public function logs($id)
    {
        $parcel       = $this->repo->get($id);
        $parcelevents = $this->repo->parcelEvents($id);
        return view('backend.merchant_panel.parcel.logs', compact('parcel','parcelevents'));
    }

    // Parcel duplicate
    public function duplicate($id)
    {
        $parcel          = $this->repo->get($id);
        $merchant        = $this->merchant->get($parcel->merchant_id);
        $shops           = $this->shop->all($parcel->merchant_id);
        $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();

        $deliveryCategories      = $this->repo->deliveryCategories();
        $deliveryCategoryCharges = $this->repo->deliveryCharges();

        $packagings    = $this->repo->packaging();
        $deliveryTypes = $this->repo->deliveryTypes();
        return view('backend.merchant_panel.parcel.duplicate',compact('parcel','merchant','deliveryTypes','shops','deliveryCategories','deliveryCategoryCharges','deliveryCharges','packagings'));
    }

    // Parcel details
    public function details($id)
    {
        // return $this->repo->details($id);
        $parcel       = $this->repo->details($id);
        $parcelevents = $this->repo->parcelEvents($id);
        return view('backend.merchant_panel.parcel.details',compact('parcel','parcelevents'));
    }

    public function edit($id)
    {
        $userID = Auth::user()->id;
        $parcel = $this->repo->get($id);
        if($parcel->status == ParcelStatus::PENDING){
            $merchant = $this->repo->getMerchant($userID);
            $shops = $this->repo->getShops($merchant->id);
            $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();
            $deliveryCategories = $this->repo->deliveryCategories();
            $deliveryCategoryCharges = $this->repo->deliveryCharges();
            $packagings = $this->repo->packaging();
            $deliveryTypes      = $this->repo->deliveryTypes();
            return view('backend.merchant_panel.parcel.edit',compact('parcel','merchant','deliveryTypes','shops','deliveryCategories','deliveryCategoryCharges','deliveryCharges','packagings'));
        }
        else{
            Toastr::error(__('parcel.edit_error_message'),__('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }

    }


    // Parcel update
    public function statusUpdate($id, $status_id)
    {
        // La liste blanche des transitions marchand vit dans le repository :
        // l'ecran ne doit plus annoncer un succes quand rien n'a ete ecrit.
        if(!$this->repo->statusUpdate($id, $status_id)){
            Toastr::error(__('parcel.status_not_allowed'),__('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }
        Toastr::success(__('parcel.update_msg'),__('message.success'));
        return redirect()->route('merchant-panel.parcel.index');
    }

    public function update(StoreRequest $request,$id)
    {
        $userID = Auth::user()->id;
        if($this->repo->update($id, $request,$userID)){
            Toastr::success(__('parcel.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function destroy($id)
    {
        $userID = Auth::user()->id;
        $parcel = $this->repo->get($id);
        if($parcel->status == ParcelStatus::PENDING){
            $this->repo->delete($id,$userID);
            Toastr::success(__('parcel.delete_msg'),__('message.success'));
            return back();
        }
        else{
            Toastr::error(__('parcel.delete_error_message'),__('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }
    }

    public function parcelImportExport()
    {
        $deliveryCategories = $this->repo->deliveryCategories();
        return view('backend.merchant_panel.parcel.import',compact('deliveryCategories'));
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
        // Solde insuffisant en cours de fichier : Laravel Excel enveloppe
        // l'import dans une transaction, donc rien n'est entre. On dit ou ca a
        // bloque, et on emmene vers la recharge.
        catch (InsufficientWalletBalance $e) {
            Toastr::error(
                __('parcel.import_wallet_insufficient', [
                    'line'    => $import->ligneCourante(),
                    'missing' => formatAmount($e->missing()),
                ]),
                __('message.error')
            );

            return redirect()->route('merchant-panel.my.wallet.index');
        }
        catch (ValidationException $e) {
            $failures = $e->failures();
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
        return redirect()->route('merchant-panel.parcel.index');
    }

    public function merchantShops(Request $request)
    {
        if (request()->ajax()) {
            if ($request->id && $request->shop == 'true') {
                $merchantShops = [];
                $merchantShop = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::ACTIVE])->first();
                $merchantShops[]= $merchantShop;
                $merchantShopArray = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::INACTIVE])->get();
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
                $merchantShop = MerchantShops::find($request->id);
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
                    return view('backend.merchant_panel.parcel.deliveryWeight', compact('deliveryCharges'));
                }
                return '';
            }
        }
        return '';
    }

    public function parcelExport(Request $request){
        try {
            if($request->type && $request->type == 'csv'):
                return Excel::download(new MerchantParcelExport($this->repo->parcelExport($request)),'Parcels Export-csv-file-'.Carbon::now()->format('d-m-Y His').'.csv',\Maatwebsite\Excel\Excel::CSV, [
                    'Content-Type' => 'text/csv',
                ]);
            else:
                return Excel::download(new MerchantParcelExport($this->repo->parcelExport($request)),'Parcels Export-excel-file-'.Carbon::now()->format('d-m-Y His').'.xlsx');
            endif;
        } catch (\Throwable $th) {
            Toastr::error(__('parcel.delete_error_message'),__('message.error'));
            return redirect()->back();
        }
    }
}
