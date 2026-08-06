<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ApprovalStatus;
use App\Enums\DeliveryType;
use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Repositories\Dashboard\DashboardInterface;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    public function __construct(DashboardInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index(Request $request){
        //it is date wise 
        $request_date = $request->date ?? Carbon::now()->format('Y-m-d').' To '.Carbon::now()->format('Y-m-d');
        $request['date'] = $request_date;
        $date         = $this->repo->analyticsFromTo($request_date); 
       
        $td_delivered        = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $t_returned_merchant = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $t_pending           = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PENDING)->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $t_in_transit        = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereNotIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED,ParcelStatus::PENDING,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $t_balance_pending   = Payment::where('merchant_id',Auth::user()->merchant->id)->where('status',ApprovalStatus::PENDING)->whereBetween('updated_at',[ $date['from'],$date['to']])->sum('amount'); 
        
        //delivered parcel
        $total_delivered_count        = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $t_delivered_collected_amount = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->sum('cash_collection');
        $delivered_delivery_charge    = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->sum('total_delivery_amount');
        $delivered_cod                = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->sum('cod_amount');
        //end delivered parcel
        //partial delivered parcel
        $total_par_delivered_count        = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $t_par_delivered_collected_amount = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->sum('cash_collection');
        $par_delivered_delivery_charge    = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->sum('total_delivery_amount');
        $par_delivered_cod                = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->whereBetween('updated_at',[$date['from'],$date['to']])->sum('cod_amount');
        //end partial delivered parcel



        //inside dhaka parcel
        $inside_dhaka_parcel_count               = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('delivery_type_id',[DeliveryType::SAMEDAY,DeliveryType::NEXTDAY])->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $inside_dhaka_parcel_amount              = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('delivery_type_id',[DeliveryType::SAMEDAY,DeliveryType::NEXTDAY])->whereBetween('updated_at',[$date['from'],$date['to']])->sum('cash_collection');
        $inside_dhaka_parcel_delivery_charge     = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('delivery_type_id',[DeliveryType::SAMEDAY,DeliveryType::NEXTDAY])->whereBetween('updated_at',[$date['from'],$date['to']])->sum('total_delivery_amount');
        //end inside dhaka parcel

        //outside dhaka parcel
        $outside_dhaka_parcel_count               = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('delivery_type_id',[DeliveryType::SUBCITY,DeliveryType::OUTSIDECITY])->whereBetween('updated_at',[$date['from'],$date['to']])->count();
        $outside_dhaka_parcel_amount              = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('delivery_type_id',[DeliveryType::SUBCITY,DeliveryType::OUTSIDECITY])->whereBetween('updated_at',[$date['from'],$date['to']])->sum('cash_collection');
        $outside_dhaka_parcel_delivery_charge     = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('delivery_type_id',[DeliveryType::SUBCITY,DeliveryType::OUTSIDECITY])->whereBetween('updated_at',[$date['from'],$date['to']])->sum('total_delivery_amount');
        //end outside dhaka parcel
        
        //last 24 hours parcel
        $nowDateTime           = Carbon::now()->format('Y-m-d H:i:s');
        $prev24HoursDateTime   = Carbon::parse(Carbon::now()->format('Y-m-d H:i:s'))->subHours(24)->format('Y-m-d H:i:s');
        $last24HoursDateTime   = $this->repo->analyticsFromTo($request_date);

        $last24HParcel_count             = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at',[$last24HoursDateTime['from'],$last24HoursDateTime['to']])->count();
        $last24HParcel_amount            = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at',[$last24HoursDateTime['from'],$last24HoursDateTime['to']])->sum('cash_collection');
        $last24HParcel_delivery_charge   = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at',[$last24HoursDateTime['from'],$last24HoursDateTime['to']])->sum('total_delivery_amount');
        
        //end last 24 hours parcel
        
        //end date wise 
 
        //not filter wise it is all parcel info
        $merchant        = Merchant::find(Auth::user()->merchant->id); 
        $t_parcel        = Parcel::where('merchant_id',Auth::user()->merchant->id)->count();
        $total_amount    = Parcel::where('merchant_id',Auth::user()->merchant->id)->sum('cash_collection');
        $t_return        = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',Auth::user()->merchant->id)->count();
        $return_fees     = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',Auth::user()->merchant->id)->sum('return_charges');
        $t_delivered     = Parcel::whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->where('merchant_id',Auth::user()->merchant->id)->count();
        $delivered_amount= Parcel::whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->where('merchant_id',Auth::user()->merchant->id)->sum('cash_collection'); 
        $t_delivery_fee  = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('total_delivery_amount');
        $t_cod_amount    = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('cod_amount');

        $data = [];

        $data['request']             = $request->all();
        $data['t_pending']           = $t_pending;
        $data['t_in_transit']        = $t_in_transit;
        $data['td_delivered']        = $td_delivered;
        $data['t_balance_pending']   = $t_balance_pending;
        $data['t_returned_merchant'] = $t_returned_merchant;

        $data['total_delivered_count']        = $total_delivered_count;
        $data['t_delivered_collected_amount'] = $t_delivered_collected_amount;
        $data['delivered_delivery_charge']    = $delivered_delivery_charge;
        $data['delivered_cod'] = $delivered_cod;
        $data['total_par_delivered_count']    = $total_par_delivered_count;
        $data['t_par_delivered_collected_amount'] = $t_par_delivered_collected_amount;
        $data['par_delivered_delivery_charge'] = $par_delivered_delivery_charge;
        $data['par_delivered_cod']            = $par_delivered_cod;
        $data['inside_dhaka_parcel_count']    = $inside_dhaka_parcel_count;
        $data['inside_dhaka_parcel_amount']   = $inside_dhaka_parcel_amount;
        $data['inside_dhaka_parcel_delivery_charge'] = $inside_dhaka_parcel_delivery_charge;
        $data['outside_dhaka_parcel_count']   = $outside_dhaka_parcel_count;
        $data['outside_dhaka_parcel_amount']  = $outside_dhaka_parcel_amount;
        $data['outside_dhaka_parcel_delivery_charge'] = $outside_dhaka_parcel_delivery_charge;
        $data['last24HParcel_count']          = $last24HParcel_count;
        $data['last24HParcel_amount']         = $last24HParcel_amount;
        $data['last24HParcel_delivery_charge'] = $last24HParcel_delivery_charge;
        $data['merchant_balance']             = (int)$merchant->current_balance;
        $data['t_parcel']                     = $t_parcel;
        $data['total_amount']                 = $total_amount;
        $data['t_delivered']                  = $t_delivered;
        $data['delivered_amount']             = $delivered_amount;
        $data['t_return']                     = $t_return;
        $data['return_fees']                  = $return_fees;
        $data['t_delivery_fee']               = $t_delivery_fee;
        $data['t_cod_amount']                 = $t_cod_amount;


        if($data){
            return $this->responseWithSuccess('Data founded Success!',$data, 200);
        }else{
            return $this->responseWithError('Something went wrong', [], 500);
        }
      
    }
}
