<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Enums\UserType;
use App\Enums\ParcelStatus;
use App\Enums\ApprovalStatus;
use App\Models\Backend\Role;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\VatStatement;
use App\Models\User;
use App\Enums\StatementType;
use App\Http\Resources\v10\ParcelResource;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Expense;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Income;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\Fraud;
use App\Models\MerchantShops;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\Dashboard\DashboardInterface;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    use ApiReturnFormatTrait;
     protected $repo;
    public $data = [];
     public function __construct(DashboardInterface $repo)
     {
        $this->repo    = $repo;
     }
    public function index(Request $request)
    {

        try {

            $t_parcel       = Parcel::where('merchant_id',auth()->user()->merchant->id)->count();
            $t_delivered    = Parcel::where('status',ParcelStatus::DELIVERED)->where('merchant_id',auth()->user()->merchant->id)->count();
            $t_return       = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',auth()->user()->merchant->id)->count();
            $t_shop         = MerchantShops::where('merchant_id',auth()->user()->merchant->id)->count();
            $t_parcel_bank  = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('parcel_bank','on')->count();
            $merchant       = Merchant::where('id',auth()->user()->merchant->id)->first();

            $parcels        = Parcel::where('merchant_id',auth()->user()->merchant->id)->get();

            $t_cash_collection   = 0;
            $t_selling_price     = 0;
            $t_liquid_fragile    = 0;
            $t_vat_amount        = 0;
            $t_delivery_charge   = 0;
            $t_cod_amount        = 0;
            $t_packaging         = 0;
            $t_delivery_amount   = 0;
            $t_current_payable   = 0;


            foreach($parcels as $parcel){

                if($parcel->status != ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
                    $t_cash_collection = $t_cash_collection + $parcel->cash_collection;
                    $t_selling_price   = $t_selling_price   + $parcel->selling_price;
                    $t_current_payable = $t_current_payable + $parcel->current_payable;
                }
                $t_liquid_fragile  = $t_liquid_fragile  + $parcel->liquid_fragile_amount;
                $t_vat_amount      = $t_vat_amount      + $parcel->vat_amount;
                $t_delivery_charge = $t_delivery_charge + $parcel->delivery_charge;
                $t_cod_amount      = $t_cod_amount      + $parcel->cod_amount;
                $t_packaging       = $t_packaging       + $parcel->packaging_amount;
                $t_delivery_amount = $t_delivery_amount + $parcel->total_delivery_amount;

            }

            $dates        = [];
            $totals       = [];
            $pendings     = [];
            $delivers     = [];
            $par_delivers = [];
            $returns      = [];

            for($i = 7; $i >= 0; $i--){

                $date = date('Y-m-d', strtotime(' -'. $i .' day'));

                $total         = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('created_at','like', $date.'%')->count();
                $pending       = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::PENDING)->where('created_at','like', $date.'%')->count();
                $delivered     = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->where('created_at','like', $date.'%')->count();
                $par_delivered = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->where('created_at','like', $date.'%')->count();
                $returned      = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('created_at','like', $date.'%')->count();

                array_push($dates, $date);
                array_push($totals, $total);
                array_push($pendings, $pending);
                array_push($delivers, $delivered);
                array_push($par_delivers, $par_delivered);
                array_push($returns, $returned);
            }

            $t_sale         = Parcel::where('merchant_id',auth()->user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('cash_collection');
            $t_delivery_fee = Parcel::where('merchant_id',auth()->user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('total_delivery_amount');
            $t_balance_proc = Payment::where('merchant_id',auth()->user()->merchant->id)->where('status',ApprovalStatus::PENDING)->sum('amount');
            $t_balance_paid = Payment::where('merchant_id',auth()->user()->merchant->id)->where('status',ApprovalStatus::PROCESSED)->sum('amount');
            $t_request      = Payment::where('merchant_id',auth()->user()->merchant->id)->count();
            $t_fraud        = Fraud::where('created_by',auth()->user()->id)->count();


            $this->data['t_parcel'] = (int) $t_parcel;
            $this->data['t_delivered'] = (int) $t_delivered;
            $this->data['t_return'] = (int) $t_return;
            $this->data['t_sale'] = (string) $t_sale;
            $this->data['t_delivery_fee'] = (string) $t_delivery_fee;
            $this->data['t_balance_proc'] = (string) number_format($t_balance_proc,2,'.','');
            $this->data['t_balance_paid'] = (string) number_format( $t_balance_paid, 2,'.','');
            $this->data['t_request'] = (int) $t_request;
            $this->data['merchant'] = $merchant;
            $this->data['t_fraud'] = (int) $t_fraud;
            $this->data['t_shop'] = (int) $t_shop;
            $this->data['t_parcel_bank'] = (int) $t_parcel_bank;
            $this->data['t_cash_collection'] = (string) number_format($t_cash_collection,2,'.','');
            $this->data['t_selling_price'] = (string) number_format($t_selling_price,2,'.','');
            $this->data['t_liquid_fragile'] = (string) number_format($t_liquid_fragile,2,'.','');
            $this->data['t_vat_amount'] = (string) number_format($t_vat_amount,2,'.','');
            $this->data['t_delivery_charge'] = (string) number_format($t_delivery_charge,2,'.','');
            $this->data['t_cod_amount'] = (string) number_format($t_cod_amount,2,'.','');
            $this->data['t_packaging'] = (string) number_format($t_packaging, 2,'.','');
            $this->data['t_delivery_amount'] = (string) number_format($t_delivery_amount,2,'.','');
            $this->data['t_current_payable'] = (string) number_format($t_current_payable,2,'.','');
            $this->data['dates'] = $dates;
            $this->data['totals'] = $totals;
            $this->data['pendings'] = $pendings;
            $this->data['delivers'] = $delivers;
            $this->data['par_delivers'] = $par_delivers;
            $this->data['returns'] = $returns;

            return $this->responseWithSuccess(__('dashboard.title'), $this->data, 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('dashboard.title'), [], 500);

        }
    }


    public function filter(Request $request){
     try{
        $from = date('Y-m-d');
        $to   = date('Y-m-d');
        if($request->date) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
        }

        $merchant       = Merchant::where('id',auth()->user()->merchant->id)->first();
        $t_fraud        = Fraud::where('created_by',auth()->user()->id)->count();
        $t_shop         = MerchantShops::where('merchant_id',auth()->user()->merchant->id)->count();

        $t_parcel       = Parcel::where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->count();
        $t_delivered    = Parcel::where('status',ParcelStatus::DELIVERED)->where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->count();
        $t_return       = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->count();
        $t_parcel_bank  = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('parcel_bank','on')->whereBetween('created_at', [$from, $to])->count();
        $t_sale         = Parcel::where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->where('status',ParcelStatus::DELIVERED)->orwhere('status',ParcelStatus::PARTIAL_DELIVERED)->sum('cash_collection');
        $t_delivery_fee = Parcel::where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->where('status',ParcelStatus::DELIVERED)->orwhere('status',ParcelStatus::PARTIAL_DELIVERED)->sum('total_delivery_amount');
        $t_balance_proc = Payment::where('merchant_id',auth()->user()->merchant->id)->where('status',ApprovalStatus::PENDING)->whereBetween('created_at', [$from, $to])->sum('amount');
        $t_balance_paid = Payment::where('merchant_id',auth()->user()->merchant->id)->where('status',ApprovalStatus::PROCESSED)->whereBetween('created_at', [$from, $to])->sum('amount');
        $t_request      = Payment::where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->count();

        $parcels        = Parcel::where('merchant_id',auth()->user()->merchant->id)->whereBetween('created_at', [$from, $to])->get();

        $t_cash_collection   = 0;
        $t_selling_price     = 0;
        $t_liquid_fragile    = 0;
        $t_vat_amount        = 0;
        $t_delivery_charge   = 0;
        $t_cod_amount        = 0;
        $t_packaging         = 0;
        $t_delivery_amount   = 0;
        $t_current_payable   = 0;

        foreach($parcels as $parcel){
            if($parcel->status != ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
                $t_cash_collection = $t_cash_collection + $parcel->cash_collection;
                $t_selling_price   = $t_selling_price   + $parcel->selling_price;
                $t_current_payable = $t_current_payable + $parcel->current_payable;
            }
            $t_liquid_fragile  = $t_liquid_fragile  + $parcel->liquid_fragile_amount;
            $t_vat_amount      = $t_vat_amount      + $parcel->vat_amount;
            $t_delivery_charge = $t_delivery_charge + $parcel->delivery_charge;
            $t_cod_amount      = $t_cod_amount      + $parcel->cod_amount;
            $t_packaging       = $t_packaging       + $parcel->packaging_amount;
            $t_delivery_amount = $t_delivery_amount + $parcel->total_delivery_amount;
        }

        $dates        = [];
        $totals       = [];
        $pendings     = [];
        $delivers     = [];
        $par_delivers = [];
        $returns      = [];


        $new_from_date = substr($from,0,10);
        $new_to_date = substr($to,0,10);

        $time = strtotime($new_to_date);
        $diff = Carbon::parse($new_from_date)->diffInDays($new_to_date);


        for($i = $diff; $i >= 0; $i--){

            $date = date('Y-m-d', strtotime(' -'. $i .' day', $time));

            $total         = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('created_at','like', $date.'%')->count();
            $pending       = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::PENDING)->where('created_at','like', $date.'%')->count();
            $delivered     = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->where('created_at','like', $date.'%')->count();
            $par_delivered = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->where('created_at','like', $date.'%')->count();
            $returned      = Parcel::where('merchant_id',auth()->user()->merchant->id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('created_at','like', $date.'%')->count();

            array_push($dates, $date);
            array_push($totals, $total);
            array_push($pendings, $pending);
            array_push($delivers, $delivered);
            array_push($par_delivers, $par_delivered);
            array_push($returns, $returned);
        }

         $this->data['t_parcel'] = (int) $t_parcel;
         $this->data['t_delivered'] = (int) $t_delivered;
         $this->data['t_return'] = (int) $t_return;
         $this->data['t_sale'] = (string) $t_sale;
         $this->data['t_delivery_fee'] = (string) $t_delivery_fee;
         $this->data['t_balance_proc'] = (string) number_format($t_balance_proc,2,'.','');
         $this->data['t_balance_paid'] = (string) number_format( $t_balance_paid, 2,'.','');
         $this->data['t_request'] = (int) $t_request;
         $this->data['merchant'] = $merchant;
         $this->data['t_fraud'] = (int) $t_fraud;
         $this->data['t_shop'] = (int) $t_shop;
         $this->data['t_parcel_bank'] = (int) $t_parcel_bank;
         $this->data['t_cash_collection'] = (string) number_format($t_cash_collection,2,'.','');
         $this->data['t_selling_price'] = (string) number_format($t_selling_price,2,'.','');
         $this->data['t_liquid_fragile'] = (string) number_format($t_liquid_fragile,2,'.','');
         $this->data['t_vat_amount'] = (string) number_format($t_vat_amount,2,'.','');
         $this->data['t_delivery_charge'] = (string) number_format($t_delivery_charge,2,'.','');
         $this->data['t_cod_amount'] = (string) number_format($t_cod_amount,2,'.','');
         $this->data['t_packaging'] = (string) number_format($t_packaging, 2,'.','');
         $this->data['t_delivery_amount'] = (string) number_format($t_delivery_amount,2,'.','');
         $this->data['t_current_payable'] = (string) number_format($t_current_payable,2,'.','');
        $this->data['dates'] = $dates;
        $this->data['totals'] = $totals;
        $this->data['pendings'] = $pendings;
        $this->data['delivers'] = $delivers;
        $this->data['par_delivers'] = $par_delivers;
        $this->data['returns'] = $returns;

        return $this->responseWithSuccess(__('dashboard.title'), $this->data, 200);
     }catch (\Exception $exception){
        return $this->responseWithError(__('dashboard.title'), [], 500);

     }
    }
 
    //dashbord balance
    public function balanceDetails() {
        $getInvoice = $this->repo->balanceDetails(); 
        return response()->json($getInvoice);
    }
    //available parcels
    public function availableParcels() {
        
        $available_parcel = $this->repo->availableParcels(); 
        $parcels = ParcelResource::collection($available_parcel);
        return $parcels;
    }

}
