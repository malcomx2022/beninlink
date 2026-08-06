<?php

namespace App\Http\Controllers;

use App\Enums\AccountHeads;
use App\Enums\UserType;
use App\Enums\ParcelStatus;
use App\Enums\ApprovalStatus;
use App\Enums\BooleanStatus;
use App\Models\Backend\Role;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\VatStatement;
use App\Models\User;
use App\Enums\StatementType;
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
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\MerchantShops;
use App\Models\SuperAdminPermission;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\Dashboard\DashboardInterface;
 
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
 
class DashbordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     protected $repo;
     public function __construct(DashboardInterface $repo)
     {
        $this->repo    = $repo;

        
     }
    public function index(Request $request)
    {
    
        if(Auth::user()->user_type == UserType::SUPER_ADMIN){ 
       
            $data=[];
            $data['total_company']              = User::where('company_owner',BooleanStatus::YES)->where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->count();
            $data['total_plans']                = Plan::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->count();
            $data['total_subscription']         = Subscription::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->count();
            $data['total_subscription_amount']  = Subscription::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->sum('price');


            $data['subscriptions']  = Subscription::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->orderBy('id','desc')->limit(10)->get();


            $data['recent_companies']      = User::where('company_owner',BooleanStatus::YES)->where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->orderBy('id','desc')->limit(10)->get();



            $data['request']   = $request;
            return view('backend.super-admin.dashboard',$data);
        }elseif(Auth::user()->user_type == UserType::MERCHANT){

            if(!subscriptionCheck()):
                return redirect()->route('subscribe.index');
            endif;
 
            $t_parcel       = Parcel::where('merchant_id',Auth::user()->merchant->id)->count();
            $t_delivered    = Parcel::where('status',ParcelStatus::DELIVERED)->where('merchant_id',Auth::user()->merchant->id)->count();
            $t_return       = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',Auth::user()->merchant->id)->count();
            $t_shop         = MerchantShops::where('merchant_id',Auth::user()->merchant->id)->count();
            $t_parcel_bank  = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('parcel_bank','on')->count();
            $merchant       = Merchant::where('id',Auth::user()->merchant->id)->first();
            $parcels        = Parcel::where('merchant_id',Auth::user()->merchant->id)->get();

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

                $total         = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('updated_at','like', $date.'%')->count();
                $pending       = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PENDING)->where('updated_at','like', $date.'%')->count();
                $delivered     = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->where('updated_at','like', $date.'%')->count();
                $par_delivered = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->where('updated_at','like', $date.'%')->count();
                $returned      = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('updated_at','like', $date.'%')->count();

                array_push($dates, $date);
                array_push($totals, $total);
                array_push($pendings, $pending);
                array_push($delivers, $delivered);
                array_push($par_delivers, $par_delivered);
                array_push($returns, $returned);
            }


            $t_sale         = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('cash_collection');
            $ts_vat         = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('vat_amount');
            $t_delivery_fee = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('total_delivery_amount');

            $t_balance_proc = Payment::where('merchant_id',Auth::user()->merchant->id)->where('status',ApprovalStatus::PENDING)->sum('amount');
            $t_balance_paid = Payment::where('merchant_id',Auth::user()->merchant->id)->where('status',ApprovalStatus::PROCESSED)->sum('amount');
            $t_request      = Payment::where('merchant_id',Auth::user()->merchant->id)->count();
            $t_fraud        = Fraud::where('created_by',Auth::user()->id)->count();

            $fromTo                         = $this->repo->FromTo($request);//from/to date
            //pie charts total
            $piedata = [];
            $piedata['total_parcels']          = Parcel::where(['merchant_id'=>Auth::user()->merchant->id])->count();
            $piedata['total_pending']          = Parcel::where(['merchant_id'=>Auth::user()->merchant->id,'status'=>ParcelStatus::PENDING])->count();
            $piedata['total_delivered']        = Parcel::where(['merchant_id'=> Auth::user()->merchant->id,'status'=>ParcelStatus::DELIVERED])->count();
            $piedata['total_partial_delivered']= Parcel::where(['merchant_id'=> Auth::user()->merchant->id,'status'=>ParcelStatus::PARTIAL_DELIVERED])->count();
            $piedata['total_return']           = Parcel::where(['merchant_id'=> Auth::user()->merchant->id,'status'=>ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->count();
            return view('backend.merchant_panel.dashboard',
            compact(
                't_parcel',
                't_delivered',
                't_return',
                't_sale',
                't_delivery_fee',
                'ts_vat',
                't_balance_proc',
                't_balance_paid',
                't_request',
                'merchant',
                't_fraud',
                't_shop',
                't_parcel_bank',

                't_cash_collection',
                't_selling_price',
                't_liquid_fragile',
                't_vat_amount',
                't_delivery_charge',
                't_cod_amount',
                't_packaging',
                't_delivery_amount',
                't_current_payable',

                'dates',
                'totals',
                'pendings',
                'delivers',
                'par_delivers',
                'returns',
                'piedata'
            ));
        }else{

            if(!subscriptionCheck()):
                return redirect()->route('subscribe.index');
            endif;
            
            $c_income       = CourierStatement::companywise()->whereNot('parcel_id',null)->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $c_expense      = CourierStatement::companywise()->whereNot('parcel_id',null)->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $d_income       = DeliverymanStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $d_expense      = DeliverymanStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $m_income       = MerchantStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $m_expense      = MerchantStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $v_income       = VatStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $v_expense      = VatStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $b_income       = BankTransaction::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $b_expense      = BankTransaction::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $h_income       = HubStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $h_expense      = HubStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $data           = [];

            $data['recent_parcels']             = Parcel::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->orderByDesc('id')->limit(5)->get();
            $data['total_parcel']               = Parcel::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total parcel
            $data['total_user']                 = User::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total user
            $data['total_merchant']             = Merchant::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total merchant
            $data['total_delivery_man']         = DeliveryMan::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total delivery man
            $data['total_hubs']                 = Hub::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total hubs
            $data['total_accounts']             = Account::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total accounts
            //status wise parcel count
            $data['total_deliveryman_assigned'] = $this->repo->parcelPosition($request,ParcelStatus::DELIVERY_MAN_ASSIGN,$this->repo->FromTo($request))->count();
            $data['total_partial_deliverd']     = $this->repo->parcelPosition($request,ParcelStatus::PARTIAL_DELIVERED,$this->repo->FromTo($request))->count();
            $data['total_deliverd']             = $this->repo->parcelPosition($request,ParcelStatus::DELIVERED,$this->repo->FromTo($request))->count();
            //end status wise parcel count
            $data['hub_parcels']                = Hub::with(['parcels'])->whereBetween('updated_at',$this->repo->FromTo($request))->limit(4)->get();
            //end salary

            $dates                           =  $this->repo->Dates($request);// 7days
            $data['incomeDates']             =   $dates;
            $data['expenseDates']            =   $dates;
            $data['merchantRevDates']        =   $dates;
            $data['DeliverymanRevDates']     =   $dates;

            $fromTo                         = $this->repo->FromTo($request);//from/to date
            $data['income']                 = $this->repo->income($fromTo);
            $data['expense']                = $this->repo->expense($fromTo);
            $data['merchantIncome']         = $this->repo->merchantIncome($fromTo);
            $data['merchantExpense']        = $this->repo->merchantExpense($fromTo);
            $data['deliverymanIncome']      = $this->repo->deliverymanIncome($fromTo);
            $data['deliverymanExpense']     = $this->repo->deliverymanExpense($fromTo);
            $data['bank_transactions']      = $this->repo->bankTransaction($fromTo);
            $data['courier_income']         = $this->repo->courierIncome($fromTo);
            $data['courier_expense']         = $this->repo->courierExpense($fromTo);

            return view('backend.dashboard', compact('c_income','c_expense','d_income','d_expense','m_income','m_expense','v_income','v_expense','b_income','b_expense','h_income','h_expense','data','request'));
        }
    }

    public function searchCharts(Request $request){
        $data    = [];
        $data['dates']                      = $this->repo->dates($request);
        $fromTo                             = $this->repo->FromTo($request);
        if($request->type     == 'income_expense'):
            $data['income']                 = $this->repo->income($fromTo);
            $data['expense']                = $this->repo->expense($fromTo);
        elseif($request->type == 'merchant'):
            $data['merchantIncome']         = $this->repo->merchantIncome($fromTo);
            $data['merchantExpense']        = $this->repo->merchantExpense($fromTo);
        elseif($request->type == 'deliveryman'):
            $data['deliverymanIncome']      = $this->repo->deliverymanIncome($fromTo);
            $data['deliverymanExpense']     = $this->repo->deliverymanExpense($fromTo);
        endif;

        return $data;

    }


    public function merchantDashboardFilter(Request $request){
        $from = date('Y-m-d');
        $to   = date('Y-m-d');
        if($request->date) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
        }

        $merchant       = Merchant::where('id',Auth::user()->merchant->id)->first();
        $t_fraud        = Fraud::where('created_by',Auth::user()->id)->count();
        $t_shop         = MerchantShops::where('merchant_id',Auth::user()->merchant->id)->count();

        $t_parcel       = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->count();
        $t_delivered    = Parcel::where('status',ParcelStatus::DELIVERED)->where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->count();
        $t_return       = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->count();
        $t_parcel_bank  = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('parcel_bank','on')->whereBetween('updated_at', [$from, $to])->count();
        $t_sale         = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->where('status',ParcelStatus::DELIVERED)->orwhere('status',ParcelStatus::PARTIAL_DELIVERED)->sum('cash_collection');
        $t_delivery_fee = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->where('status',ParcelStatus::DELIVERED)->orwhere('status',ParcelStatus::PARTIAL_DELIVERED)->sum('total_delivery_amount');
        $t_balance_proc = Payment::where('merchant_id',Auth::user()->merchant->id)->where('status',ApprovalStatus::PENDING)->whereBetween('updated_at', [$from, $to])->sum('amount');
        $t_balance_paid = Payment::where('merchant_id',Auth::user()->merchant->id)->where('status',ApprovalStatus::PROCESSED)->whereBetween('updated_at', [$from, $to])->sum('amount');
        $t_request      = Payment::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->count();
        $parcels        = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereBetween('updated_at', [$from, $to])->get();
        $ts_vat         = Parcel::where('merchant_id',Auth::user()->merchant->id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->whereBetween('updated_at', [$from, $to])->sum('vat_amount');
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
        $new_to_date   = substr($to,0,10);
        $time          = strtotime($new_to_date);
        $diff          = Carbon::parse($new_from_date)->diffInDays($new_to_date);

        for($i = $diff; $i >= 0; $i--){
            $date = date('Y-m-d', strtotime(' -'. $i .' day', $time));
            $total         = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('updated_at','like', $date.'%')->count();
            $pending       = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PENDING)->where('updated_at','like', $date.'%')->count();
            $delivered     = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::DELIVERED)->where('updated_at','like', $date.'%')->count();
            $par_delivered = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->where('updated_at','like', $date.'%')->count();
            $returned      = Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('updated_at','like', $date.'%')->count();

            array_push($dates, $date);
            array_push($totals, $total);
            array_push($pendings, $pending);
            array_push($delivers, $delivered);
            array_push($par_delivers, $par_delivered);
            array_push($returns, $returned);
        }



        //pie charts total
        $piedata = [];
        $piedata['total_parcels']          = Parcel::where(['merchant_id'=>Auth::user()->merchant->id])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_pending']          = Parcel::where(['merchant_id'=>Auth::user()->merchant->id,'status'=>ParcelStatus::PENDING])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_delivered']        = Parcel::where(['merchant_id'=> Auth::user()->merchant->id,'status'=>ParcelStatus::DELIVERED])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_partial_delivered']= Parcel::where(['merchant_id'=> Auth::user()->merchant->id,'status'=>ParcelStatus::PARTIAL_DELIVERED])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_return']           = Parcel::where(['merchant_id'=> Auth::user()->merchant->id,'status'=>ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->whereBetween('updated_at', [$from, $to])->count();
          
               
        return view('backend.merchant_panel.dashboard',
        compact(
            'request',
            'ts_vat',
            'piedata',
            't_parcel',
            't_delivered',
            't_return',
            't_sale',
            't_delivery_fee',
            't_balance_proc',
            't_balance_paid',
            't_request',
            'merchant',
            't_fraud',
            't_shop',
            't_parcel_bank',
            't_cash_collection',
            't_selling_price',
            't_liquid_fragile',
            't_vat_amount',
            't_delivery_charge',
            't_cod_amount',
            't_packaging',
            't_delivery_amount',
            't_current_payable',
            'dates',
            'totals',
            'pendings',
            'delivers',
            'par_delivers',
            'returns',
        ));
    }

 
}
