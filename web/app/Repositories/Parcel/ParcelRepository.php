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
use App\Http\Resources\MerchantParcelExportResource;
use App\Http\Services\PushNotificationService;
use App\Http\Services\SmsService;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Packaging;
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

    public function deliveryManParcel(){

        return Parcel::companywise()->orderBy('updated_at')->orderBy('priority_type_id')->where(function( $query ) {
            if(auth()->user()->deliveryman){
                $query->whereHas('parcelEvent', function ($queryParcelEvent) {
                    if(auth()->user()->deliveryman->id) {
                        $queryParcelEvent->where(['delivery_man_id' => auth()->user()->deliveryman->id]);
                        $queryParcelEvent->orWhere(['pickup_man_id' => auth()->user()->deliveryman->id]);
                    }
                });
            }
        })->get();
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

    public function statusUpdate($id, $status_id) {
        $parcel = Parcel::find($id);
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

    public function store($request) {

        try {
         
            DB::beginTransaction();
            $merchant                       = Merchant::with('user')->find($request->merchant_id);
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
            $parcel->customer_lat           = $request->lat;
            $parcel->customer_long          = $request->long;
            $parcel->delivery_type_id       = $request->delivery_type_id;
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
                \App\Models\Backend\Merchant::find($request->merchant_id),
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
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->customer_name.', আপনার পার্সেল সফলভাবে তৈরি করা হয়েছে । আপনার পার্সেলের আইডি ' . $parcel->tracking_id . ' পার্সেল পাঠিয়েছেন ' . $parcel->merchant->business_name . ' (' . $parcel->cash_collection . ' টাকা)';

                else:
                    $msg = 'Dear '.$parcel->customer_name.', Your parcel is successfully created. Your parcel with ID ' . $parcel->tracking_id . ' parcel from ' . $parcel->merchant->business_name . ' (' . $parcel->cash_collection . ')';
                endif;
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);

            }
            return true;
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
            $merchant                       = Merchant::with('user')->find($request->merchant_id);
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
            $parcel->customer_lat           = $request->lat;
            $parcel->customer_long          = $request->long;
            $parcel->delivery_type_id       = $request->delivery_type_id;
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
                \App\Models\Backend\Merchant::find($request->merchant_id),
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
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function update($id, $request) {

        try {
            DB::beginTransaction();
            $merchant                       = Merchant::with('user')->find($request->merchant_id);

            $parcel                         = Parcel::find($id);
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
            $parcel->note                       = $request->note;
            // S2 — montants calcules par le SERVEUR. Le socle enregistrait ici
            // json_decode(chargeDetails), c'est-a-dire des frais, une TVA et un net
            // a reverser fabriques par le navigateur : le client choisissait sa facture.
            $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
                \App\Models\Backend\Merchant::find($request->merchant_id),
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
        try {

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
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$pickupAsisgn->pickupman->user->name.', '.dateFormat($parcel->pickup_date).' তারিখের মধ্যে '.'পার্সেল পিকআপ করুন । পার্সেল আইডি '.$parcel->tracking_id .' । পার্সেল পাঠিয়েছে ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') - '.settings()->name;
                else:
                    $msg = 'Dear '.$pickupAsisgn->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                endif;
                $response =  app(SmsService::class)->sendSms($pickupAsisgn->pickupman->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$pickupAsisgn->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$pickupAsisgn->pickupman->user->email,$msgNotification,'deliveryMan');
            }catch (\Exception $exception){

            }


            if($request->send_sms_merchant  == 'on'){
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'সম্মানিত '.$parcel->merchant->business_name.',  আপনার পার্সেল আইডি -'.$parcel->tracking_id .' । '.settings()->name.' থেকে পিকআপ ম্যান নিয়োগ করা হয়েছে । প্রয়োজনে  পিকআপ ম্যান এর সাথে যোগাযোগ করুন । নিয়োগ দিয়েছেন '.$pickupAsisgn->pickupman->user->name.', '.$pickupAsisgn->pickupman->user->mobile.' । ট্র্যাক করুন: '.url('/').' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                else:
                    $msg = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Pickup man assign from '.settings()->name.'. Assign by '.$pickupAsisgn->pickupman->user->name.', '.$pickupAsisgn->pickupman->user->mobile.' Track here: '.url('/').' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                endif;
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
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$pickupReshcedule->pickupman->user->name.', '.dateFormat($parcel->pickup_date).' তারিখের মধ্যে '. 'পার্সেল পিকআপ করুন । পার্সেল আইডি - '.$parcel->tracking_id.' । পার্সেল পাঠিয়েছে ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.')'.' - '.settings()->name;
                    $response =  app(SmsService::class)->sendSms($pickupReshcedule->pickupman->user->mobile,$msg);
                else:
                    $msg = 'Dear '.$pickupReshcedule->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($pickupReshcedule->pickupman->user->mobile,$msg);
                endif;
            }

            if($request->send_sms_merchant  == 'on'){
                if(session()->has('locale') && session()->get('locale') == 'bn'):

                    $msg = 'সম্মানিত '.$parcel->merchant->business_name.', আপনার পার্সেল আইডি - '.$parcel->tracking_id .' , '.settings()->name.' থেকে পিকআপ ম্যান পুনরায় নিয়োগ করা হয়েছে । প্রয়োজনে  পিকআপ ম্যান এর সাথে যোগাযোগ করুন । নিয়োগ দিয়েছেন '.$pickupReshcedule->pickupman->user->name.', '.$pickupReshcedule->pickupman->user->mobile.' ট্র্যাক করুন: '.url('/').' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                else:
                    $msg = 'Dear'.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Pickup man assign from '.settings()->name.'. Assign by'.$pickupReshcedule->pickupman->user->name.', '.$pickupReshcedule->pickupman->user->mobile.' Track here: '.url('/').' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                endif;
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

            foreach($request->parcel_ids as $id){
                $transfertohub                           = new ParcelEvent();
                $transfertohub->parcel_id                = $id;
                $transfertohub->hub_id                   = $request->hub_id;
                $transfertohub->transfer_delivery_man_id = $request->delivery_man_id;
                $transfertohub->note                     = $request->note;
                $transfertohub->parcel_status            = ParcelStatus::TRANSFER_TO_HUB;
                $transfertohub->created_by               = Auth::user()->id;
                $transfertohub->save();
                $parcel                                  = Parcel::find($id);
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
            $deliveryUser  = DeliveryMan::find($request->delivery_man_id);
            foreach($request->parcel_ids_ as $id){
                $deliveryMan                           = new ParcelEvent();
                $deliveryMan->parcel_id                = $id;
                $deliveryMan->delivery_man_id          = $request->delivery_man_id;
                $deliveryMan->note                     = $request->note;
                $deliveryMan->delivery_lat             = $deliveryUser->delivery_lat;
                $deliveryMan->delivery_long            = $deliveryUser->delivery_long;
                $deliveryMan->parcel_status            = ParcelStatus::DELIVERY_MAN_ASSIGN;
                $deliveryMan->created_by               = Auth::user()->id;
                $deliveryMan->save();
                $parcel                                    = Parcel::find($id);
                $parcel->status                            = ParcelStatus::DELIVERY_MAN_ASSIGN;
                $parcel->save();

                if($request->send_sms == 'on'){
                    if(session()->has('locale') && session()->get('locale') == 'bn'):
                        $msg = 'প্রিয় '.$parcel->customer_name.', পার্সেল আইডি - '.$parcel->tracking_id .' ।  পার্সেলটি ডেলিভারির জন্য ডেলিভারি ম্যান  নিয়োগ করা হয়েছে ('.$deliveryMan->deliveryMan->user->name.', '.$deliveryMan->deliveryMan->user->mobile.') । পার্সেল পাঠিয়েছেন ('.$parcel->merchant->business_name.') পার্সেলের পরিষোদ মুল্য ('.$parcel->cash_collection.') টাকা । ট্র্যাক করুন:'.url('/').'  -'.settings()->name;
                    else:
                        $msg = 'Dear '.$parcel->customer_name.', parcel with ID '.$parcel->tracking_id .' from ('.$parcel->merchant->business_name.') TK('.$parcel->cash_collection.') delivery man assing by '.$deliveryMan->deliveryMan->user->name.', '.$deliveryMan->deliveryMan->user->mobile.'. Track here:'.url('/').'  -'.settings()->name;
                    endif;
                    $response =  app(SmsService::class)->sendSms($parcel->customer_phone,$msg);
                }


            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function transfertohub($id,$request){
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

        try {
            $deliveyrman = DeliveryMan::find($request->delivery_man_id);

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

                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->customer_name.', পার্সেল আইডি - '.$parcel->tracking_id .' ।  পার্সেলটি ডেলিভারির জন্য ডেলিভারি ম্যান  নিয়োগ করা হয়েছে ('.$deliverymanAssign->deliveryMan->user->name.', '.$deliverymanAssign->deliveryMan->user->mobile.') । পার্সেল পাঠিয়েছেন ('.$parcel->merchant->business_name.') পার্সেলের পরিষোদ মুল্য ('.$parcel->cash_collection.') টাকা । ট্র্যাক করুন:'.url('/').'  -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->customer_name.', parcel with ID '.$parcel->tracking_id .' from ('.$parcel->merchant->business_name.') TK('.$parcel->cash_collection.') delivery man assing by '.$deliverymanAssign->deliveryMan->user->name.', '.$deliverymanAssign->deliveryMan->user->mobile.'. Track here:'.url('/').'  -'.settings()->name;
                endif;
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
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->customer_name.', পার্সেল আইডি - '.$parcel->tracking_id .' ।  পার্সেলটি ডেলিভারির জন্য পূনরায় ডেলিভারি ম্যান  নিয়োগ করা হয়েছে ('.$deliveryReschedule->deliveryMan->user->name.', '.$deliveryReschedule->deliveryMan->user->mobile.') । পার্সেল পাঠিয়েছেন ('.$parcel->merchant->business_name.') পার্সেলের পরিষোদ মুল্য ('.$parcel->cash_collection.') টাকা । ট্র্যাক করুন:'.url('/').'  -'.settings()->name;

                else:
                    $msg = 'Dear '.$parcel->customer_name.', Your  parcel with ID '.$parcel->tracking_id .'  is re-schedule  from ('.$parcel->merchant->business_name.') TK('.$parcel->cash_collection.') delivery man assign by '.$deliveryReschedule->deliveryMan->user->name.', '.$deliveryReschedule->deliveryMan->user->mobile.'. Track here:'.url('/').'  -'.settings()->name;
                endif;
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
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->customer_name.', আমরা আইডি সহ একটি পার্সেল পেয়েছি , পার্সেল আইডি - '.$parcel->tracking_id .', পার্সেল পাঠিয়েছেন ('.$parcel->merchant->business_name.') এবং যত তাড়াতাড়ি সম্ভব বিতরণ করা হবে । ট্র্যাক করুন:'.url('/').'  -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->customer_phone,$msg);
                else:
                    $msg = 'Dear '.$parcel->customer_name.', we received a parcel with ID '.$parcel->tracking_id .' from ('.$parcel->merchant->business_name.') and will deliver as soon as possible. Track here:'.url('/').'  -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->customer_phone,$msg);
                endif;


            }

            if($request->send_sms_merchant  == 'on'){
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'সম্মানিত '.$parcel->merchant->business_name.', আপনার পার্সেল '.$receivedWarehouse->hub->name.' হাবের ওয়্যারহাউসে গ্রহন করা হয়েছে , পার্সেল আইডি - '.$parcel->tracking_id .' ।  ট্র্যাক করুন: '.url('/').' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                else:
                    $msg = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Received to Warehouse '.$receivedWarehouse->hub->name .'. Track here: '.url('/').' -'.settings()->name;
                    $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
                endif;
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


    public function receivedWarehouseCancel($id,$request){
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
        try {

            $returntocourier                = new ParcelEvent();
            $returntocourier->parcel_id     = $id;
            $returntocourier->note          = $request->note;
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
            $parcel = Parcel::find($id);
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
            $parcel                = Parcel::find($id);
            $parcel->delivery_date = $request->date;
            $parcel->status        = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
            $parcel->save();
            DB::commit();
            if($request->send_sms == 'on'){
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'সম্মানিত '.$parcel->merchant->business_name.', পার্সেল আইডি - '.$parcel->tracking_id .', আপনার পার্সেলটি ('.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.') দ্বারা আপনার কাছে পূনরায়  পাঠানো হয়েছে  '.','.'পরিদর্শন করুন:'.url('/').'  -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->merchant->business_name.', parcel with ID '.$parcel->tracking_id .' is return to you by '.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.'. visit:'.url('/').'  -'.settings()->name;
                endif;
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

    public function returnAssignToMerchantCancel($id,$request){
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
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->merchant->business_name.', পার্সেল আইডি -  '.$parcel->tracking_id.', আপনার পার্সেলটি পূনরায় ('.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.') দ্বারা আপনার কাছে পাঠানো হয়েছে , পরিদর্শন করুন: '.url('/').'  -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->merchant->business_name.', parcel with ID '.$parcel->tracking_id .' is return to you by '.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.'. visit: '.url('/').'  -'.settings()->name;
                endif;
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }

    }

    public function returnAssignToMerchantRescheduleCancel($id,$request){
        try {
            $parcel = Parcel::find($id);
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


    public function returnReceivedByMerchant($id,$request){
        try {
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
                $courierStatement->note                 = __('statementNote.return_to_merchant__deliveryman_statement');
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
                $courierStatement->note                 = __('statementNote.return_to_merchant_deliveryman_statement');
                $courierStatement->save();


            }
            //total delivery charge
            $merchant=Merchant::find($parcel->merchant_id);
            $merchantStatement                   = new MerchantStatement();
            $merchantStatement->company_id       = settings()->id;
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
            $current=((double)$merchantCost->current_balance - $return_delivery_charge);
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
            $parcel->status = ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
            $parcel->save();

            return true;
        } catch (\Throwable $th) {

            return false;
        }

    }

    public function returnReceivedByMerchantCancel($id,$request){
        try {
            $parcel = Parcel::find($id);
            if($parcel->status == ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
                $pickupAsisgn = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>$parcel->status])->first();
                ParcelEvent::destroy($pickupAsisgn->id);
            }

            $returnreschedule     = ParcelEvent::where(['parcel_id'=>$id,'parcel_status'=>ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE])->first();
            if($returnreschedule){
                $parcel->status   = ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE;
            }else{
                $parcel->status   = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
            }
            $parcel->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function parcelDelivered($id,$request){
 
        try {

            $parcelDelivered                = new ParcelEvent();
            $parcelDelivered->parcel_id     = $id;
            $parcelDelivered->note          = $request->note;

            if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
                $image = $request->file('image');
                $destinationPath   = public_path('uploads/parcel/image/');
                $imageName         = date('YmdHis') .uniqid() . rand(5, 10).".".$image->getClientOriginalExtension();
                $image->move($destinationPath, $imageName);
                $delivered_image            = 'uploads/parcel/image/'.$imageName;
                $parcelDelivered->delivered_image  = $delivered_image;

            }

            if (isset($_FILES['signatureImage']['name']) && $_FILES['signatureImage']['name']) {
                $signatureImage = $request->file('signatureImage');
                $destinationPath   = public_path('uploads/parcel/signature/');
                $signatureImageName         = date('YmdHis') .uniqid() . rand(5, 10).".".$signatureImage->getClientOriginalExtension();
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
            if($request->send_sms_customer == 'on') {

                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->customer_name.', আপনার পার্সেল আইডি - ' . $parcel->tracking_id . ' সফলভাবে বিতরণ করা হয়েছে । টাকা প্রদান করেছেন '.$parcel->cash_collection.' টাকা । আপনার অভিজ্ঞতা রেট করুন । পরিদর্শন করুন:' . url('/') . '  -'.settings()->name;
                else:
                    $msg = 'Dear Customer, Your parcel with ID ' . $parcel->tracking_id . ' is successfully delivered. To rate your experience visit:' . url('/') . '  -'.settings()->name;
                endif;
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);
            }
            if($request->send_sms_merchant  == 'on'){
                if(session()->has('locale') && session()->get('locale') == 'bn'):

                    $msg = 'সম্মানিত '.$parcel->merchant->business_name.', আপনার পার্সেল আইডি - '.$parcel->tracking_id .' সফলভাবে বিতরণ করা হয়েছে । ক্রেতা- '.$parcel->customer_name.', '.$parcel->customer_phone.' । এই পার্সেলটির ডেলিভারি ম্যান '.$parcel->cash_collection.' টাকা গ্রহন করেছেন । পরিদর্শন করুন: '.url('/').' -'.settings()->name;
                else:
                    $msg = 'Dear Merchant, your  parcel with ID '.$parcel->tracking_id .' is successfully delivered. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' Track here: '.url('/').' -'.settings()->name;
                endif;
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear Merchant, your  parcel with ID '.$parcel->tracking_id .' is successfully delivered. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }



    public function parcelDeliveredCancel($id,$request){
        try {
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

            if(SmsSendSettingHelper(SmsSendStatus::DELIVERED_CANCEL_CUSTOMER)) {
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->customer_name.', আপনার পার্সেল আইডি - ' . $parcel->tracking_id . ' । '.$parcel->merchant->business_name.' থেকে বাতিল করা হবে । ট্র্যাক করুন: '.url('/').' -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->customer_name.', Your parcel with ID ' . $parcel->tracking_id . ' from '.$parcel->merchant->business_name.'will be cancel. Track here: '.url('/').' -'.settings()->name;
                endif;
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);
            }

            if(SmsSendSettingHelper(SmsSendStatus::DELIVERED_CANCEL_MERCHANT)){
                if(session()->has('locale') && session()->get('locale') == 'bn'):
                    $msg = 'প্রিয় '.$parcel->merchant->business_name.', আপনার পার্সেল আইডি - '.$parcel->tracking_id .' বিতরণ করা বাতিল । ক্রেতা - '.$parcel->customer_name.', '.$parcel->customer_phone.' ট্র্যাক করুন: '.url('/').' -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .'  Delivered cancel. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' Track here: '.url('/').' -'.settings()->name;
                endif;
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .'  Delivered cancel. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function parcelPartialDelivered($id,$request){
        try {

            // Parcel Event
            $parcelPartialDelivered                     = new ParcelEvent();
            $parcelPartialDelivered->parcel_id          = $id;
            $parcelPartialDelivered->note               = $request->note;
            $parcelPartialDelivered->parcel_status      = ParcelStatus::PARTIAL_DELIVERED;
        
            $parcelPartialDelivered->created_by         = Auth::user()->id;
            $parcelPartialDelivered->save();
            $parcel                           = Parcel::find($id);
            //calculations
            $cod_charges_amount           = ($request->cash_collection / 100) * $parcel->cod_charge;
            $total_charges                = (
                $cod_charges_amount             +
                $parcel->delivery_charge        +
                $parcel->liquid_fragile_amount  +
                $parcel->packaging_amount
            );
            $vat_amount                   = ($total_charges/100) * $parcel->vat;
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

            if($request->send_sms_customer == 'on') {

                if(session()->has('locale') && session()->get('locale') == 'bn'):

                    $msg = 'প্রিয় '.$parcel->customer_name.', আপনার পার্সেল আইডি - ' . $parcel->tracking_id . ' আংশিক বিতরণ করা হয় । টাকা প্রদান করুন ('.$parcel->cash_collection.') টাকা এবং আপনার অভিজ্ঞতা রেট করুন । পরিদর্শন করুন:' . url('/') . '  -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->customer_name.', Your parcel with ID ' . $parcel->tracking_id . '  is Partials Delivered please giving amount('.$parcel->cash_collection.') by  To rate your experience visit:' . url('/') . '  -'.settings()->name;
                endif;
                $response = app(SmsService::class)->sendSms($parcel->customer_phone, $msg);
            }

            if($request->send_sms_merchant  == 'on'){
                if(session()->has('locale') && session()->get('locale') == 'bn'):

                    $msg = 'প্রিয় '.$parcel->merchant->business_name.', আপনার পার্সেল আইডি - '.$parcel->tracking_id .' আংশিক বিতরণ করা হয় । ক্রেতা '.$parcel->customer_name.', '.$parcel->customer_phone.' । এই পার্সেলটির ডেলিভারি ম্যান '.$parcel->cash_collection.' টাকা গ্রহন করেছেন । পরিদর্শন করুন: '.url('/').' -'.settings()->name;
                else:
                    $msg = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' is Partials Delivered. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' taking amount('.$parcel->cash_collection.')  Track here: '.url('/').' -'.settings()->name;
                endif;
                $response =  app(SmsService::class)->sendSms($parcel->merchant->user->mobile,$msg);
            }

            try{
                $msgNotification = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' is Partials Delivered. Customer '.$parcel->customer_name.', '.$parcel->customer_phone.' taking amount('.$parcel->cash_collection.')  Track here: '.url('/').' -'.settings()->name;
                app(PushNotificationService::class)->sendStatusPushNotification($parcel,$parcel->merchant->user->email,$msgNotification,'merchant');
            }catch (\Exception $exception){

            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function parcelPartialDeliveredCancel($id,$request){
        try {

            $parcel                             = Parcel::find($id);
            //old info
            $old_cash_collection                = $parcel->cash_collection;
            $old_vat_amount                     = $parcel->vat_amount;
            $old_total_delivery_amount          = $parcel->total_delivery_amount;
            //end old
            $parcel->cash_collection            = $parcel->old_cash_collection;
            //calculations
            $cod_charges_amount             = ($parcel->old_cash_collection / 100) * $parcel->cod_charge;
            $total_charges                  = (
                $cod_charges_amount             +
                $parcel->delivery_charge        +
                $parcel->liquid_fragile_amount  +
                $parcel->packaging_amount
            );
            $vat_amount                     = ($total_charges/100) * $parcel->vat;
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
            $merchantStatement->parcel_id        = $id;
            $merchantStatement->delivery_man_id  = $deliveryManStatement->delivery_man_id;
            $merchantStatement->amount           = $old_vat_amount;
            $merchantStatement->type             = StatementType::INCOME;
            $merchantStatement->date             =  date('Y-m-d H:i:s');
            $merchantStatement->note             = __('statementNote.delivered_merchant_statment');
            $merchantStatement->save();
            //vat and total charge plus from merchant current balance
            $deliveryCost = $old_total_delivery_amount + $parcel->vat_amount;
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
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }

    public function pickupdatemanAssignedCancel($id,$request){
        try {
            $parcel = Parcel::find($id);
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
            $parcel = Parcel::find($id);
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
            $parcel = Parcel::find($id);
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
            $parcel = Parcel::find($id);
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

            $parcel = Parcel::find($id);
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

            $parcel = Parcel::find($id);
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

            $parcel = Parcel::find($id);
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
            $parcels  = Parcel::whereIn('id',$request->parcel_id)->get();
            foreach ($parcels as $key => $parcel) {
                $receivedByhub                = new ParcelEvent();
                $receivedByhub->parcel_id     = $parcel->id;
                $receivedByhub->parcel_status = ParcelStatus::RECEIVED_BY_HUB;
                $receivedByhub->note          = $request->note;
                $receivedByhub->created_by    = Auth::user()->id;
                $receivedByhub->save();

                $parcel                       = Parcel::find($parcel->id);
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
            foreach ($request->parcel_id as  $id) {
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
                    if(session()->has('locale') && session()->get('locale') == 'bn'):
                        $msg = 'প্রিয় '.$pickupAsisgn->pickupman->user->name.', '.dateFormat($parcel->pickup_date).' তারিখের মধ্যে '.'পার্সেল পিকআপ করুন । পার্সেল আইডি '.$parcel->tracking_id .' । পার্সেল পাঠিয়েছে ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') - '.settings()->name;
                    else:
                        $msg = 'Dear '.$pickupAsisgn->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                    endif;
                    $response =  app(SmsService::class)->sendSms($pickupAsisgn->pickupman->user->mobile,$msg);
                }
                try{
                    $msgNotification = 'Dear '.$pickupAsisgn->pickupman->user->name.', Please pickup parcel with ID '.$parcel->tracking_id .' parcel from ('.$parcel->merchant->business_name.','.$parcel->merchant->user->mobile.','.$parcel->merchant->address.') within '.dateFormat($parcel->pickup_date).' -'.settings()->name;
                    app(PushNotificationService::class)->sendStatusPushNotification($parcel,$pickupAsisgn->pickupman->user->email,$msgNotification,'deliveryMan');
                }catch (\Exception $exception){

                }
                if($request->send_sms_merchant  == 'on'){
                    if(session()->has('locale') && session()->get('locale') == 'bn'):
                        $msg = 'সম্মানিত '.$parcel->merchant->business_name.',  আপনার পার্সেল আইডি -'.$parcel->tracking_id .' । '.settings()->name.' থেকে পিকআপ ম্যান নিয়োগ করা হয়েছে । প্রয়োজনে  পিকআপ ম্যান এর সাথে যোগাযোগ করুন । নিয়োগ দিয়েছেন '.$pickupAsisgn->pickupman->user->name.', '.$pickupAsisgn->pickupman->user->mobile.' । ট্র্যাক করুন: '.url('/').' -'.settings()->name;
                    else:
                        $msg = 'Dear '.$parcel->merchant->business_name.', your  parcel with ID '.$parcel->tracking_id .' Pickup man assign from '.settings()->name.'. Assign by'.$pickupAsisgn->pickupman->user->name.', '.$pickupAsisgn->pickupman->user->mobile.' Track here: '.url('/').' -'.settings()->name;
                    endif;
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
            foreach ($request->parcel_id as $id) {

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
                $parcel                = Parcel::find($id);
                $parcel->delivery_date = $request->date;
                $parcel->status        = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
                $parcel->save();
                DB::commit();
                if($request->send_sms == 'on'){
                    if(session()->has('locale') && session()->get('locale') == 'bn'):
                        $msg = 'সম্মানিত '.$parcel->merchant->business_name.', পার্সেল আইডি - '.$parcel->tracking_id .',  আপনার পার্সেলটি ('.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.') দ্বারা আপনার কাছে পূনরায় পাঠানো হয়েছে '.','.'পরিদর্শন করুন:'.url('/').'  -'.settings()->name;
                    else:
                        $msg = 'Dear '.$parcel->merchant->business_name.', parcel with ID '.$parcel->tracking_id .' is return to you by '.$returnassigntomerchant->deliveryMan->user->name.', '.$returnassigntomerchant->deliveryMan->user->mobile.'. visit:'.url('/').'  -'.settings()->name;
                    endif;
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
        return Parcel::whereIn('id',$ids)->get();
    }
    //app dashboard
    public function deliverymanStatusParcel($status){

        if($status == ParcelStatus::DELIVERED):
            return Parcel::orderBy('updated_at')->orderBy('priority_type_id')->with(['merchant'])->whereIn('status',[ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])->where(function($query){
                $query->wherehas('parcelEvent',function($eventquery){
                    $eventquery->where('delivery_man_id',Auth::user()->deliveryman->id);
                });
            })->get();
        else:
            return Parcel::orderBy('updated_at')->orderBy('priority_type_id')->with(['merchant'])->where('status',$status)->where(function($query){
                $query->wherehas('parcelEvent',function($eventquery){
                    $eventquery->where('delivery_man_id',Auth::user()->deliveryman->id);
                });
            })->get();
        endif;
    }
    //end app dashboard
    public function parcelSearchs($request){
        return  Parcel::where('customer_name','Like','%'.$request->search.'%')
            ->orWhere('customer_phone','Like','%'.$request->search.'%')
            ->orWhere('customer_address','Like','%'.$request->search.'%')
            ->orWhere('invoice_no','Like','%'.$request->search.'%')
            ->orWhere('tracking_id','Like','%'.$request->search.'%')
            ->orWhereHas('merchant',function($query) use($request){
                $query->where('business_name','Like','%'.$request->search.'%');
            })
            ->paginate(10);

    }


    public function parcelMultiplePrintLabel($request){
        return Parcel::whereIn('id',$request->parcels)->with('merchant', 'merchant.user','merchantShop','deliveryCategory','packaging')->get();
    }

}
