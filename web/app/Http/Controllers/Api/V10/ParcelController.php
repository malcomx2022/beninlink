<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\Status;
use App\Http\Controllers\Controller;
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


            return $this->responseWithSuccess(__('parcel.parcel_create'), ['merchant'=>$merchant,'shops'=>$shops,'deliveryCategories'=>$deliveryCategories,'deliveryCharges'=>$deliveryCharges,'codCharges'=>$codCharges,'packagings'=>$packagings, 'fragileLiquid'=>$fragileLiquid, 'deliveryTypes'=>$deliveryTypes], 200);
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
        if($this->repo->store($request,$merchant->id)){
            return $this->responseWithSuccess(__('parcel.added_msg'), [], 200);
        }else{
            return $this->responseWithError(__('parcel.error_msg'), [], 500);

        }
    }



    public function logs($id)
    {
        try {
            $parcel       = $this->repo->get($id);
            $parcelevents = $this->repo->parcelEvents($id);
            return $this->responseWithSuccess(__('parcel.parcel_logs'), ['parcel'=> new ParcelResource ($parcel),'parcelEvents'=>ParcelLogsResource::collection($parcelevents) ], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_logs'), [], 500);

        }
    }


    public function details($id)
    {

        try {
            $parcel       = $this->repo->details($id);
            $parcelEvents = $this->repo->parcelEvents($id);
            return $this->responseWithSuccess(__('parcel.parcel_details'), ['parcel'=> new ParcelResource ($parcel),'parcelEvents'=>ParcelLogsResource::collection($parcelEvents) ], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_details'), [], 500);

        }
    }


    public function edit($id)
    {
        $userID = auth()->user()->id;
        $parcel = $this->repo->get($id);
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
            return $this->responseWithSuccess(__('parcel.parcel_edit'), ['merchant'=>$merchant,'shops'=>$shops,'deliveryCategories'=>$deliveryCategories,'codCharges'=>$codCharges,'deliveryCharges'=>$deliveryCharges,'packagings'=>$packagings,'fragileLiquid'=>$fragileLiquid,'deliveryTypes'=>$deliveryTypes,'deliveryCategoryCharges'=>$deliveryCategoryCharges], 200);
        }
        else{
            return $this->responseWithError(__('parcel.edit_error_message'), [], 422);
        }

    }

    public function statusUpdate($id, $statusId)
    {
        try {
            $this->repo->statusUpdate($id, $statusId);
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
        if($this->repo->update($id, $request,auth()->user()->id)){
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
        try {
            $data = $request->all();
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
