<?php

use App\Enums\AccountHeads;
use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\TodoStatus;
use App\Enums\UserType;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Expense;
use App\Models\Backend\FrontWeb\Section;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\GoogleMapSetting;
use App\Models\Backend\Hub;
use App\Models\Backend\HubInCharge;
use App\Models\Backend\Income;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\MerchantSetting;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\NewsOffer;
use App\Models\Backend\NotificationSettings;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\Setting;
use App\Models\Backend\SmsSendSetting;
use App\Models\Backend\SmsSetting;
use App\Models\Backend\Support;
use App\Models\Backend\SupportChat;
use App\Models\Config;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

if(!function_exists('pluck')) {
    function pluck( $array, $value, $key = null )
    {
        $returnArray = [];
        if(count($array)) {
            foreach($array as $item) {
                if($key != null) {
                    $returnArray[$item->$key] = strtolower($value) == 'obj' ? $item : $item->$value;
                } else {
                    if($value == 'obj') {
                        $returnArray[] = $item;
                    } else {
                        $returnArray[] = $item->$value;
                    }
                }
            }
        }
        return $returnArray;
    }
}

if (!function_exists('settingHelper')) {
    function settingHelper($key)
    {
        $data = Config::companywise()->where('key', $key)->first();
        if(!blank($data)):
            return $data->value;
        else:
            return '';
        endif;
    }
}

if (!function_exists('googleMapSettingKey')) {
    function googleMapSettingKey()
    {
        $data = GoogleMapSetting::companywise()->first();
        if(!blank($data)):
            return $data->map_key;
        else:
            return '';
        endif;
    }
}

if (!function_exists('SmsSendSettingHelper')) {

    function SmsSendSettingHelper($status)
    {
        $data = SmsSendSetting::companywise()->where(['sms_send_status'=> $status,'status'=>Status::ACTIVE])->first();
        if(!blank($data)):
            return true;
        else:
            return false;
        endif;
    }
}

//permission
if(!function_exists('hasPermission')){
    function hasPermission($permission=null){

        if(in_array($permission,Auth::user()->permissions)){
            return true;
        }
        return false;
    }
}

if(!function_exists('settings')){
    function settings(){ 
        return  GeneralSettings::with('rxlogo','rxfavicon')->where('status',Status::ACTIVE)->where(function($query){
            if(tenant() && tenant()->company_id):  
                $query->where('id',tenant()->company_id);
            elseif(Auth::user() && Auth::user()->user_type != UserType::SUPER_ADMIN): 
                $query->where('id',Auth::user()->company_id);
            else:
                $query->where('id',1);
            endif;
        })->first();
    }
}


if(!function_exists('isSuperadmin')){
    function isSuperadmin(){
        if(Auth::user() && Auth::user()->user_type == UserType::SUPER_ADMIN):
           return true;
        endif;
        return false;
    }
}

if(!function_exists('notificationSettings')){
    function notificationSettings(){
        return NotificationSettings::companywise()->first();
    }
}

if(!function_exists('setEnv')){
    function setEnv($name, $value){
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $name . '=' . env($name), $name . '=' . $value, file_get_contents($path)
            ));
        }
        return true;
    }
}
//todo
if(!function_exists('user')){
    function user(){

        $users = App\Models\User::companywise()->get();
        return $users;
    }
}

if(!function_exists('dateFormat')){
    function dateFormat($newDate=null){
        $day = date('dS', strtotime($newDate));
        $month = strtolower(date('F', strtotime($newDate)));
        $yearly = date('Y', strtotime($newDate));

        return  $day. ' '.$month.' '.$yearly;
    }
}

if(!function_exists('parcelStatus')){
    function parcelStatus($parcel,$request=null){
        $parcelStatus = '';
        $allowStatus = [];

        if ($parcel->status  == ParcelStatus::PENDING) {
            $allowStatus = [ParcelStatus::PICKUP_ASSIGN];
        } elseif ($parcel->status == ParcelStatus::PICKUP_ASSIGN   ) {
            $allowStatus = [
                        ParcelStatus::PICKUP_ASSIGN_CANCEL,
                        ParcelStatus::PICKUP_RE_SCHEDULE,
                        ParcelStatus::RECEIVED_WAREHOUSE,
                    ];
        }elseif(ParcelStatus::PICKUP_RE_SCHEDULE == $parcel->status){
            $allowStatus = [
                ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL,
                ParcelStatus::PICKUP_RE_SCHEDULE,
                ParcelStatus::RECEIVED_WAREHOUSE,
            ];

        }elseif($parcel->status == ParcelStatus::RECEIVED_WAREHOUSE || $parcel->status == ParcelStatus::RECEIVED_BY_HUB){

            if($parcel->status == ParcelStatus::RECEIVED_WAREHOUSE){
                $allowStatus= [parcelStatus::RECEIVED_WAREHOUSE_CANCEL,ParcelStatus::TRANSFER_TO_HUB,ParcelStatus::DELIVERY_MAN_ASSIGN];
            }else{
                $allowStatus= [parcelStatus::RECEIVED_BY_HUB_CANCEL,ParcelStatus::TRANSFER_TO_HUB,ParcelStatus::DELIVERY_MAN_ASSIGN];
            }


        }elseif($parcel->status == parcelStatus::TRANSFER_TO_HUB){
            $allowStatus = [parcelStatus::TRANSFER_TO_HUB_CANCEL,parcelStatus::RECEIVED_BY_HUB];
        }elseif($parcel->status == parcelStatus::RECEIVED_BY_HUB){
            $allowStatus = [parcelStatus::RECEIVED_BY_HUB_CANCEL];

        }elseif($parcel->status == ParcelStatus::DELIVERY_MAN_ASSIGN || $parcel->status == ParcelStatus::DELIVERY_RE_SCHEDULE){
                if($parcel->status == ParcelStatus::DELIVERY_MAN_ASSIGN){
                    $allowStatus = [parcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,parcelStatus::DELIVERY_RE_SCHEDULE,ParcelStatus::RETURN_TO_COURIER,ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED, ParcelStatus::DELIVERED];
                }else{
                    $allowStatus = [parcelStatus::DELIVERY_RE_SCHEDULE_CANCEL,parcelStatus::DELIVERY_RE_SCHEDULE,ParcelStatus::RETURN_TO_COURIER,ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED, ParcelStatus::DELIVERED];
                }
        }
        elseif($parcel->status == parcelStatus::RETURN_TO_COURIER){
            $allowStatus=[ParcelStatus::RETURN_TO_COURIER_CANCEL,ParcelStatus::RETURN_ASSIGN_TO_MERCHANT];
        }
        elseif($parcel->status == ParcelStatus::RETURN_ASSIGN_TO_MERCHANT){
            $allowStatus= [ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL,ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT];
        }
        elseif($parcel->status == ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE){
            $allowStatus= [ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL,ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT];
        }
        elseif($parcel->status == ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
        }
        elseif($parcel->status == parcelStatus::DELIVERED){
        }
        elseif($parcel->status == parcelStatus::PARTIAL_DELIVERED){
        }

        $parcelStatusArray = [];
        if (!blank($allowStatus)) {
            foreach (trans('parcelStatus') as $key => $status) {
                if (in_array($key, $allowStatus)) {
                    $parcelStatusArray[$key] = $status;
                }
            }
        }


        if(!blank($parcelStatusArray)){
            foreach($parcelStatusArray as $key => $status) {
                if($key == ParcelStatus::PICKUP_ASSIGN_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item pickup-man-assign-cancel" data-title="pickup assign" data-url="'.route("parcel.pickup.man-assigned-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item pickup-reschedule-cancel" data-title="Pickup re-schedule" data-url="'.route("parcel.pickup.re-schedule-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item receved-by-pickupman-cancel" data-title="Received by pickup-man" data-url="'.route("parcel.pickup.man-received-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RECEIVED_WAREHOUSE_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item receved-warehouse-cancel" data-title="Received warehouse" data-url="'.route("parcel.received-warehouse-cancel").'" data-parcel="'. $parcel->id.'"   href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item delivery-man-assign-cancel" data-title="Delivery man assign " data-url="'.route("parcel.delivery-man-assign-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item delivery-re-schedule-cancel" data-title="Delivery re-schedule " data-url="'.route("parcel.delivery-re-schedule-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::TRANSFER_TO_HUB_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item transfer-to-hub-cancel" data-title="Transfer to hub " data-url="'.route("parcel.transfer-to-hub-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RECEIVED_BY_HUB_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item received-by-hub-cancel" data-title="Received by hub " data-url="'.route("parcel.received-by-hub-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RETURN_TO_COURIER_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item return-to-courier-cancel" data-title="Return to courier" data-url="'.route("parcel.return-to-courier-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item return-assign-to-merchant-cancel" data-title="Return assign to merchant" data-url="'.route("parcel.return-assign-to-merchant-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item return-assign-re-schedule-merchant-cancel" data-title="Return merchant Re-Schedule Cancel" data-url="'.route("parcel.return-assign-re-schedule-to-merchant-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item return-received-by-merchant-cancel" data-title="Return received by merchant" data-url="'.route("parcel.return-received-by-merchant-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::DELIVERED_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item delivered-cancel" data-title="Delivered cancel" data-url="'.route("parcel.delivered-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }elseif($key == ParcelStatus::PARTIAL_DELIVERED_CANCEL){
                    $parcelStatus .= '<a  class="dropdown-item partial-delivered-cancel" data-title="Partial delivered cancel" data-url="'.route("parcel.partial-delivered-cancel").'" data-parcel="'. $parcel->id.'"  href="#">'.$status.'</a>';
                }else {
                    if($key == ParcelStatus::PICKUP_RE_SCHEDULE){
                        $parcelStatus .= '<a  class="dropdown-item parcel-id-pickup-man" data-parcelstatus="'.ParcelStatus::PICKUP_ASSIGN.'" data-parcel="'.$parcel->id.'" data-toggle="modal" data-target="#parcelstatus'.$key.'" href="#">'.$status.'</a>';
                    }elseif($key == ParcelStatus::TRANSFER_TO_HUB){
                        $parcelStatus .= '<a  class="dropdown-item parcel-id-transfer-hub" data-parcel="'.$parcel->id.'" data-toggle="modal" data-target="#parcelstatus'.$key.'" href="#">'.$status.'</a>';
                    }elseif($key == ParcelStatus::DELIVERY_RE_SCHEDULE){
                        $parcelStatus .= '<a  class="dropdown-item parcel-id-delivery-man" data-parcelstatus="'.ParcelStatus::DELIVERY_MAN_ASSIGN.'" data-parcel="'.$parcel->id.'" data-toggle="modal" data-target="#parcelstatus'.$key.'" href="#">'.$status.'</a>';
                    }elseif($key == ParcelStatus::RECEIVED_WAREHOUSE){

                        $parcelStatus .= '<a  class="dropdown-item parcel-id received_warehouse" data-parcel="'.$parcel->id.'" data-toggle="modal" data-parcel="'. $parcel->id.'"  data-hub="'.$parcel->hub_id.'" data-url="'.route('parcel.received.warehouse.hub.select').'" data-target="#parcelstatus'.$key.'" href="#">'.$status.'</a>';
                    }
                    else{

                        $parcelStatus .= '<a  class="dropdown-item parcel-id" data-parcel="'.$parcel->id.'" data-toggle="modal" data-target="#parcelstatus'.$key.'" href="#">'.$status.'</a>';

                    }

                }
            }
        }

        return $parcelStatus;
    }

}


if (!function_exists('StatusParcel')) {
     function StatusParcel($status_id)
    {
        if($status_id == ParcelStatus::PENDING){
            $status = '<span class="badge badge-pill badge-danger">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::PICKUP_ASSIGN) {
            $status = '<span class="badge badge-pill badge-primary">'.trans("parcelStatus." .$status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::RECEIVED_WAREHOUSE) {
            $status = '<span class="badge badge-pill badge-info">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::DELIVERY_MAN_ASSIGN) {
            $status = '<span class="badge badge-pill badge-warning">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::DELIVERY_RE_SCHEDULE) {
            $status = '<span class="badge badge-pill badge-info">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::RETURN_TO_COURIER) {
            $status = '<span class="badge badge-pill badge-info">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::RETURN_ASSIGN_TO_MERCHANT) {
            $status = '<span class="badge badge-pill badge-dark">'.trans("parcelStatus." . $status_id).'</span>';
        } elseif($status_id == ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE) {
            $status = '<span class="badge badge-pill badge-dark">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::RETURN_RECEIVED_BY_MERCHANT) {
            $status = '<span class="badge badge-pill badge-success">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::DELIVER) {
            $status = '<span class="badge badge-pill badge-success">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::DELIVERED) {
            $status = '<span class="badge badge-pill badge-success">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::PARTIAL_DELIVERED) {
            $status = '<span class="badge badge-pill badge-success">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::RETURN_WAREHOUSE) {
            $status = '<span class="badge badge-pill badge-info">'.trans("parcelStatus." . $status_id).'</span>';
        }
        elseif($status_id == ParcelStatus::ASSIGN_MERCHANT) {
            $status = '<span class="badge badge-pill badge-secondary">'.trans("parcelStatus." . $status_id).'</span>';
        }

        elseif($status_id == ParcelStatus::RETURNED_MERCHANT) {
            $status = '<span class="badge badge-pill badge-dark">'.trans("parcelStatus." . $status_id).'</span>';
        }elseif($status_id == ParcelStatus::PICKUP_RE_SCHEDULE){
            $status = '<span class="badge badge-pill badge-dark">'.trans("parcelStatus." . $status_id).'</span>';
        }elseif($status_id == ParcelStatus::RECEIVED_BY_PICKUP_MAN){
            $status = '<span class="badge badge-pill badge-success">'.trans("parcelStatus." . $status_id).'</span>';
        }elseif($status_id == ParcelStatus::TRANSFER_TO_HUB){
            $status = '<span class="badge badge-pill badge-info">'.trans("parcelStatus." . $status_id).'</span>';

        }elseif($status_id == ParcelStatus::RECEIVED_BY_HUB){
            $status = '<span class="badge badge-pill badge-info">'.trans("parcelStatus." . $status_id).'</span>';
        }
        return $status;
    }
}

if (!function_exists('merchantPayments')) {
    function merchantPayments($merchantID)
    {
        $totalMerchantPayments['paidAmount'] = 0;
        $totalMerchantPayments['pendingAmount'] = 0;
        $totalMerchantPayments['paidAmount']    =  Payment::companywise()->whereIn('merchant_id',$merchantID)->where(['status'=>\App\Enums\ApprovalStatus::APPROVED])->sum('amount');
        $totalMerchantPayments['pendingAmount'] =  Payment::companywise()->whereIn('merchant_id',$merchantID)->where(['status'=>\App\Enums\ApprovalStatus::PENDING])->sum('amount');
        return $totalMerchantPayments;
    }
}

if (!function_exists('parcelExpense')) {
    function parcelExpense($id)
    {

        $income  = DeliverymanStatement::companywise()->where('parcel_id',$id)->where('type',AccountHeads::INCOME)->where('cash_collection',0)->sum('amount');
        $expense = DeliverymanStatement::companywise()->where('parcel_id',$id)->where('type',AccountHeads::EXPENSE)->where('cash_collection',0)->sum('amount');
        return ($income-$expense);
    }
}

if (!function_exists('parcelExpenseTotal')) {
    function parcelExpenseTotal($ids)
   {
        $income  = DeliverymanStatement::companywise()->whereIn('parcel_id',$ids)->where('type',AccountHeads::INCOME)->where('cash_collection',0)->sum('amount');
        $expense = DeliverymanStatement::companywise()->whereIn('parcel_id',$ids)->where('type',AccountHeads::EXPENSE)->where('cash_collection',0)->sum('amount');
        return ($income - $expense);
}
}

if (!function_exists('totalParcelsCashcollection')) {
    function totalParcelsCashcollection($parcels)
   {
        $total_cash_collection = 0;
        foreach ($parcels as $key => $parcel) {
            $total_cash_collection += $parcel->sum('cash_collection');
        }
        return $total_cash_collection;
   }
}

if (!function_exists('withoutUser')) {

    function withoutUser($ids)
    {

       $user=User::companywise()->WhereNotIn('id',$ids)->WhereNotIn('user_type',[UserType::DELIVERYMAN,UserType::MERCHANT])->where('status',Status::ACTIVE)->get();
        if(!blank($user)):
            return $user;
        else:
            return [];
        endif;
    }
}

if (!function_exists('unpaidUser')) {
    function unpaidUser($ids)
    {
       $users=User::companywise()->WhereIn('id',$ids)->WhereNotIn('user_type',[UserType::DELIVERYMAN,UserType::MERCHANT])->where('status',Status::ACTIVE)->get();
        if(!blank($users)):
            return $users;
        else:
            return [];
        endif;
    }
}


if (!function_exists('user')) {
    function user($id)
    {
       $user=User::find($id);
        if(!blank($user)):
            return $user;
        else:
            return '';
        endif;
    }
}

if (!function_exists('singleUser')) {
    function singleUser($id)
    {
       $user=User::find($id);
        if(!blank($user)):
            return $user;
        else:
            return '';
        endif;
    }
}

    if (!function_exists('todoStatus')) {
        function TodoStatus($todo)
        {
            $todoStatus = '';
            $allowStatus = [];

            if ($todo->status  == todoStatus::PENDING) {
                $allowStatus = [todoStatus::PROCESSING];
            } elseif ($todo->status == todoStatus::PROCESSING   ) {
                $allowStatus = [
                    todoStatus::COMPLETED,
                ];
            }
            elseif($todo->status  == todoStatus::COMPLETED){
                $allowStatus = [];
            }
            else{
                $allowStatus = [todoStatus::PENDING];
            }
            $todoStatusArray = [];
            if (!blank($allowStatus)) {
                foreach (trans('to_do') as $key => $status) {
                    if (in_array($key, $allowStatus)) {
                        $todoStatusArray[$key] = $status;
                    }
                }
            }
            if(!blank($todoStatusArray)){
                foreach($todoStatusArray as $key => $status) {
                    if($key == todoStatus::PENDING){
                        $todoStatus .= '<a  class="dropdown-item pending" data-title="pending" data-id="'.$todo->id.'" id="todo_btn" data-url="'.route("todo.processing").'" data-toggle="modal" data-target="#todoStatus'.$key.'"  href="#">'.$status.'</a>';
                    }elseif($key == todoStatus::PROCESSING){
                        $todoStatus .= '<a  class="dropdown-item processing" data-id="'.$todo->id.'" id="todo_btn" data-title="processing" data-url="'.route("todo.processing").'" data-toggle="modal" data-target="#todoStatus'.$key.'"  href="#">'.$status.'</a>';
                    }
                    else{
                        $todoStatus .= '<a  class="dropdown-item completed" data-title="completed" data-id="'.$todo->id.'" id="todo_btn" data-url="'.route("todo.completed").'" data-toggle="modal" data-target="#todoStatus1'.$key.'"  href="#">'.$status.'</a>';
                    }
                }
                return $todoStatus;
            }
        }
    }

    //income
    if (!function_exists('dayIncomeCount')) {
        function dayIncomeCount($date)
        {
            $date       = Carbon::parse($date)->format('Y-m-d');
            $income     = Income::companywise()->where('date',$date)->get();
            if(!blank($income)):
                return $income->sum('amount');
            else:
                return 0;
            endif;
        }
    }

    //expense
    if (!function_exists('dayExpenseCount')) {
        function dayExpenseCount($date)
        {
            $date     = Carbon::parse($date)->format('Y-m-d');
            $Expense  = Expense::companywise()->where('date',$date)->get();
            if(!blank($Expense)):
                return $Expense->sum('amount');
            else:
                return 0;
            endif;
        }
    }

    //merchant reve income
    if (!function_exists('dayMerchantRevIncomeCount')) {

        function dayMerchantRevIncomeCount($date)
        {
            $date     = Carbon::parse($date)->format('Y-m-d');
            $merchant  = MerchantStatement::companywise()->where('type',AccountHeads::INCOME)->where('date',$date)->get();
            if(!blank($merchant)):
                return $merchant->sum('amount');
            else:
                return 0;
            endif;
        }
    }

    //merchant reve expense
    if (!function_exists('dayMerchantRevExpenseCount')) {

        function dayMerchantRevExpenseCount($date)
        {
            $date     = Carbon::parse($date)->format('Y-m-d');
            $merchant  = MerchantStatement::companywise()->where('type',AccountHeads::EXPENSE)->where('date',$date)->get();
            if(!blank($merchant)):
                return $merchant->sum('amount');
            else:
                return 0;
            endif;
        }
    }

    //deliveryman reve income
    if (!function_exists('dayDeliverymanRevIncomeCount')) {

        function dayDeliverymanRevIncomeCount($date)
        {
            $date     = Carbon::parse($date)->format('Y-m-d');
            $merchant  = DeliverymanStatement::companywise()->where('type',AccountHeads::INCOME)->where('date',$date)->get();
            if(!blank($merchant)):
                return $merchant->sum('amount');
            else:
                return 0;
            endif;
        }
    }

    //deliveryman reve expense
    if (!function_exists('dayDeliverymanRevExpenseCount')) {

        function dayDeliverymanRevExpenseCount($date)
        {
            $date     = Carbon::parse($date)->format('Y-m-d');
            $merchant  = DeliverymanStatement::companywise()->where('type',AccountHeads::EXPENSE)->where('date',$date)->get();
            if(!blank($merchant)):
                return $merchant->sum('amount');
            else:
                return 0;
            endif;
        }
    }
     //end dashboard

    if (!function_exists('parcelsStatus')) {

        function parcelsStatus($parcels,$ids='',$parcel_ids='')
        {

            if($parcel_ids ==''):
                $parcel_ids=[];
                foreach ($parcels as $parcls) {
                    foreach ($parcls as $key => $parcel) {
                        $parcel_ids[]=$parcel->id;
                    }
                }
            endif;
            $parcels=Parcel::companywise()->whereIn('id',$parcel_ids)->get();
            if($ids !==''):
                return $parcel_ids;

            else:
                return $parcels->groupBy('status');

            endif;
        }
    }

    if (!function_exists('idWiseParcels')) {

        function idWiseParcels($parcels,$neeId='',$IdParcels='')
        {

            if($IdParcels !==''):
                return Parcel::companywise()->whereIn('id',$IdParcels)->get();
            elseif($neeId !==''):

                $p_ids=$parcels->pluck('id')->toArray();

                return $p_ids;
            endif;

        }
    }


    if (!function_exists('hubs')) {

        function hubs()
        {
           return Hub::companywise()->get();
        }
    }

    if (!function_exists('hubIncharge')) {

        function hubIncharge()
        {
            $hub = HubInCharge::where('user_id', Auth::user()->id)->first();
            if($hub != null){
                return $hub->hub_id;
            }
            else{
                return 0;
            }
        }
    }


    if (!function_exists('salaryPayments')) {

        function salaryPayments($user_id='',$salaryPayments = [])
        {
            $amount=0;
            foreach ($salaryPayments as $key => $payment) {
                    if($payment->user_id == $user_id && $payment->amount > 0):
                        $amount +=$payment->amount;
                    endif;
            }
            return $amount;
        }
    }


    if (!function_exists('oldLogDetails')) {

        function oldLogDetails($oldLogs,$newLogs)
        {
            foreach ($oldLogs as $key => $value) {
                if($newLogs == $key){
                        return $value;
                }
            }
        }
    }

    //notifications
    if (!function_exists('notifications')) {

        function notifications()
        {
            $types='';
            $notifications     = [];
            //suports
            $sevendaysBefore    = \Carbon\Carbon::today()->subDays(7)->startOfDay()->toDateTimeString();
            $today              = \Carbon\Carbon::today()->endOfDay()->toDateTimeString();

            $supports          = Support::whereNot('user_id',Auth::user()->id)->orderBy('created_at', 'DESC')->select('id','user_id','subject','created_at')->whereBetween('created_at',[$sevendaysBefore,$today])->get();

            foreach ($supports as  $support) {
                $notifications[] = [

                                    'type'      => 'support',
                                    'support_id'=> $support->id,
                                    'user_id'   => $support->user_id,
                                    'subject'   => $support->subject,
                                    'created_at'=> $support->created_at->format('Y-m-d H:i:s'),
                                ];

            }

            $supportsChats          = SupportChat::orderBy('created_at', 'DESC')->select('id','support_id','user_id','created_at')->whereBetween('created_at',[$sevendaysBefore,$today])->get();
            $supportChats            =  $supportsChats->groupBy('support_id');
            foreach ($supportChats as  $key=>$chats) {
                $supportCheck       = Support::find($key);
                if($supportCheck->user_id == Auth::user()->id):
                    foreach ($chats as  $chat) {
                        if($chat->user_id !== Auth::user()->id):
                            $notifications []= [
                                'type'      => 'support',
                                'support_id'=> $chat->support_id,
                                'user_id'   => $chat->user_id,
                                'subject'   => $chat->support->subject,
                                'created_at'=> $chat->created_at->format('Y-m-d H:i:s'),
                            ];
                        endif;
                    }
                else:
                       $chats_users   = $chats->pluck('user_id')->toArray();
                        if(in_array(Auth::user()->id,$chats_users)):
                            $firstChatCheck = SupportChat::where(['support_id'=>$key,'user_id'=>Auth::user()->id])->first();

                            foreach ($chats as  $chat) {
                                $firstChatDate = strtotime(Carbon::parse($firstChatCheck->created_at)->format('Y-m-d H:i:s'));
                                if($chat->user_id !== Auth::user()->id  ):
                                    $chatDateTime  = strtotime(Carbon::parse($chat->created_at)->format('Y-m-d H:i:s'));
                                    if($firstChatDate <= $chatDateTime):
                                        $notifications []= [
                                            'type'      => 'support',
                                            'support_id'=> $chat->support_id,
                                            'user_id'   => $chat->user_id,
                                            'subject'   => $chat->support->subject,
                                            'created_at'=> $chat->created_at->format('Y-m-d H:i:s'),
                                        ];
                                    endif;
                                endif;
                            }
                        endif;
                endif;
            }

            //news and offers
             $news_offer      = NewsOffer::companywise()->orderBy('created_at','DESC')->limit(5)->get();
             foreach ($news_offer as  $newsoffer) {

                $notifications []= [
                    'type'      => 'newsoffer',
                    'user_id'   => $newsoffer->author,
                    'subject'   => $newsoffer->title,
                    'created_at'=> $newsoffer->created_at->format('Y-m-d H:i:s'),
                ];

             }
            //end news and offers
            return collect($notifications)->sortByDesc('created_at');

        }

    }


    if (!function_exists('calendarnewsoffer')) {
        function calendarnewsoffer($date)
        {
            $from  = Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
            $to    = Carbon::parse($date)->endOfDay()->format('Y-m-d H:i:s');
            return NewsOffer::companywise()->whereBetween('created_at',[$from,$to])->orderBy('id','desc')->first();
        }
    }


    if (!function_exists('static_asset')) {
        function static_asset($path = ''){

            if(tenant()):
                if (strpos(php_sapi_name(), 'cli') !== false || defined('LARAVEL_START_FROM_PUBLIC')) {
                    return  url('/').'/'.$path;
                }else{
                    return   url('/').'/public/'.$path;
                }
            else:
                if (strpos(php_sapi_name(), 'cli') !== false || defined('LARAVEL_START_FROM_PUBLIC')) {
                    return  app('url')->asset($path);
                }else{
                    return  app('url')->asset('public/'.$path);
                }
            endif; 
        }
    }

    if (!function_exists('scheme_name')) {
        function scheme_name($domain = ''){ 
            if (strpos(request()->schemeAndHttpHost(), 'https') != false):
                $scheme = 'https://';
            elseif (strpos(request()->schemeAndHttpHost(), 'https') === 0): 
                $scheme = 'https://';
            else:
                $scheme = 'http://';
            endif;
            return $scheme.$domain;
        }
    }
    if (!function_exists('get_host')) {
        function get_host(){
            if(request()->getHost() == '127.0.0.1'): 
                $getHost = 'localhost'.request()->getBaseUrl();  
            else: 
                $getHost = request()->getHost().request()->getBaseUrl(); 
            endif;
            return $getHost;
        }
    }

    if (!function_exists('paginate_redirect')) {
        function paginate_redirect($request){
              return   $request->page ? 'admin/parcel/index?page='.$request->page : 'admin/parcel/index';
        }
    }

    if (!function_exists('globalSettings')) {
        function globalSettings($key){
            if(Auth::user() && !isSuperadmin()):
                $settings   = Setting::companywise()->where('key',$key)->first();
                if($settings):
                     return $settings->value;
                endif;
                return null;
            else:
                $settings   = Setting::where('company_id',1)->where('key',$key)->first();
                if($settings):
                     return $settings->value;
                endif;
                return null;
            endif;
        }
    }

    if (!function_exists('smsSettings')) {
        function smsSettings($key){

            if(Auth::user() && !isSuperadmin()):
                $settings   = SmsSetting::companywise()->where('key',$key)->first();
                if($settings):
                    return $settings->value;
                endif;
                return null;
            else:
                $settings   = SmsSetting::where('company_id',1)->where('key',$key)->first();
                if($settings):
                    return $settings->value;
                endif;
                return null;
            endif;
        }
    }

    if (!function_exists('MerchantSettings')) {
        function MerchantSettings($key){
                $settings   = MerchantSetting::where(['merchant_id'=>Auth::user()->merchant->id,'key'=>$key])->first();
                if($settings):
                     return $settings->value;
                endif;
                return null;

        }
    }

    if (!function_exists('MerchantSearchSettings')) {
        function MerchantSearchSettings($merchant_id,$key){
                $settings   = MerchantSetting::where(['merchant_id'=>$merchant_id,'key'=>$key])->first();
                if($settings):
                     return $settings->value;
                endif;
                return null;

        }
    }


    if (!function_exists('statusIcon')) {
        function statusIcon($status){
              switch ($status) {
                case ParcelStatus::PENDING:
                    return 'fas fa-hourglass-end';
                    break;
                case ParcelStatus::PICKUP_ASSIGN:
                    return 'fas fa-truck';
                     break;
                case ParcelStatus::PICKUP_RE_SCHEDULE:
                    return 'fas fa-truck';
                     break;
                case ParcelStatus::RECEIVED_WAREHOUSE:
                    return 'fas fa-warehouse';
                     break;
                case ParcelStatus::TRANSFER_TO_HUB:
                    return 'fa fa-right-left';
                     break;
                case ParcelStatus::RECEIVED_BY_HUB:
                    return 'fa fa-warehouse';
                    break;
                case ParcelStatus::DELIVERY_MAN_ASSIGN:
                    return 'fa fa-people-carry';
                     break;
                case ParcelStatus::DELIVERY_RE_SCHEDULE:
                    return 'fa fa-people-carry';
                     break;
                case ParcelStatus::DELIVERED:
                    return 'fas fa-handshake';
                     break;
                case ParcelStatus:: PARTIAL_DELIVERED:
                    return 'fas fa-handshake';
                    break;
                case ParcelStatus::RETURN_TO_COURIER:
                    return 'fa fa-warehouse';
                     break;
                case ParcelStatus::RETURN_ASSIGN_TO_MERCHANT:
                    return 'fas fa-truck';
                     break;
                case ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE:
                    return 'fas fa-truck';
                     break;
                case ParcelStatus:: RETURN_RECEIVED_BY_MERCHANT:
                    return 'fas fa-store';
                    break;
              }
        }
    }

    if (!function_exists('MerchantParcels')) {
        function MerchantParcels($merchant_id){
                $data=[];
                $data['total_parcels']         = Parcel::companywise()->where('merchant_id', $merchant_id)->count();
                $data['total_cash_amount']     = Parcel::companywise()->where('merchant_id', $merchant_id)->sum('cash_collection');
                $data['total_current_payable'] = Parcel::companywise()->where('merchant_id', $merchant_id)->sum('current_payable');
              return (object)$data;

        }
    }

    if (!function_exists('section')) {
        function section($type, $key){
                $all_sections = Section::companyWise()->with('upload')->select('type', 'key', 'value')->get();
                $sections = [];
                foreach($all_sections as $section) {
                    if(str_contains($section->key, 'image') || str_contains($section->key, 'banner')) {
                        $sections[$section->type][$section->key] = $section->image;
                    } else {
                        $sections[$section->type][$section->key] = $section->value;
                    }
                }
            return data_get($sections, $type.'.'.$key, '');
        }
    }


    if (!function_exists('subscriptionCheck')) {
        function subscriptionCheck($user=null){
            if($user):
                if($user->subscription):
                    $todayDate       = Carbon::today()->startOfDay()->toDateTimeString();
                    $expired_date    =  $user->subscription->expired_date;
                    $today_strtotime = strtotime($todayDate);
                    $end_strtotime   = strtotime($expired_date);
                    if($today_strtotime <= $end_strtotime):
                        return Carbon::now()->diffInDays($expired_date);//subscribed
                    endif;
                endif;
                return false;
            else:
                if(Auth::user()->subscription):
                    $todayDate       = Carbon::today()->startOfDay()->toDateTimeString();
                    $expired_date    =  Auth::user()->subscription->expired_date;
                    $today_strtotime = strtotime($todayDate);
                    $end_strtotime   = strtotime($expired_date);
                    if($today_strtotime <= $end_strtotime):
                        return true;//subscribed
                    endif;
                endif;
                return false;
            endif;
        }
    }



    if (!function_exists('specialCharactersReplace')) {
        function specialCharactersReplace($string=''){
            return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Montants — XOF (FCFA)
    |--------------------------------------------------------------------------
    | Le franc CFA n'a pas de subdivision : tout montant est un entier.
    | Ces trois fonctions sont le SEUL endroit où un montant est mis en forme.
    | Pour changer la position du symbole ou le séparateur, modifier ici.
    */

    if (!function_exists('amountValue')) {
        /**
         * Valeur numérique d'un montant : entier, sans mise en forme.
         * À utiliser pour l'API et tout ce qui doit rester analysable.
         */
        function amountValue($amount = 0){
            return (int) round((float) $amount);
        }
    }

    if (!function_exists('currencySymbol')) {
        /**
         * Symbole de la société courante (colonne general_settings.currency),
         * avec repli sur FCFA tant qu'aucune valeur n'est enregistrée.
         */
        function currencySymbol(){
            $symbol = settings()->currency ?? null;
            return blank($symbol) ? 'FCFA' : $symbol;
        }
    }

    if (!function_exists('formatRate')) {
        /**
         * Taux en pourcentage (ex. merchants.vat) : « 5 % » ou « 5,5 % ».
         * À ne pas confondre avec formatAmount() — un taux n'est pas un montant
         * et ne porte jamais de symbole monétaire.
         *
         * Les décimales inutiles sont retirées, la colonne étant en decimal(16,2)
         * alors que les taux sont presque toujours entiers.
         */
        function formatRate($rate = 0){
            $rate = (float) $rate;
            $decimals = ($rate == (int) $rate) ? 0 : 2;
            return number_format($rate, $decimals, ',', "\xC2\xA0") . "\xC2\xA0%";
        }
    }

    if (!function_exists('formatAmount')) {
        /**
         * Montant destiné à l'affichage : entier, milliers séparés par une
         * espace insécable, symbole suffixé selon l'usage francophone
         * (« 1 500 FCFA »).
         *
         * @param  mixed $amount
         * @param  bool  $withCurrency  false pour n'obtenir que le nombre.
         */
        function formatAmount($amount = 0, $withCurrency = true){
            $formatted = number_format(amountValue($amount), 0, ',', "\xC2\xA0");
            if($withCurrency):
                return $formatted . "\xC2\xA0" . currencySymbol();
            endif;
            return $formatted;
        }
    }
