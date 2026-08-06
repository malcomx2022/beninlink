<?php
namespace App\Repositories\Dashboard;

use App\Enums\AccountHeads;
use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Expense;
use App\Models\Backend\Income;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\Backend\Salary;
use App\Repositories\Dashboard\DashboardInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardRepository implements DashboardInterface {

    public function FromTo($request){

        if($request->days     == 'today'):
            $startDate                      = Carbon::today()->format('Y-m-d');//today date
            $endDate                        = Carbon::today()->format('Y-m-d'); // today date
            $subDays                        = Carbon::parse(trim($startDate))->startOfDay()->toDateTimeString();//from
            $todayDate                      = Carbon::parse(trim($endDate))->endOfDay()->toDateTimeString();//to
        elseif($request->days     == 'week'):
            $subDays                        = Carbon::parse(Carbon::today()->subDays(7)->format('Y-m-d'))->startOfDay()->toDateTimeString(); // from today to 7 days previus date
            $todayDate                      = Carbon::parse(Carbon::today()->format('Y-m-d'))->endOfDay()->toDateTimeString();//to today date
        elseif($request->days     == '15days'):
            $subDays                        = Carbon::parse(Carbon::today()->subDays(15)->format('Y-m-d'))->startOfDay()->toDateTimeString(); //from today to 15days previus date
            $todayDate                      = Carbon::parse(Carbon::today()->format('Y-m-d'))->endOfDay()->toDateTimeString();//to today date
        elseif($request->days == 'month'):
            $subDays                        = Carbon::parse(Carbon::today()->subDays(30)->format('Y-m-d'))->startOfDay()->toDateTimeString(); //from  today to 30 days previus date
            $todayDate                      = Carbon::parse(Carbon::today()->format('Y-m-d'))->endOfDay()->toDateTimeString();//to today date
        elseif($request->days == 'yesterday'):
            $subDays                        = Carbon::parse(Carbon::today()->subDays(1)->format('Y-m-d'))->startOfDay()->toDateTimeString(); // yesterday
            $todayDate                      = Carbon::parse(Carbon::today()->subDays(1)->format('Y-m-d'))->endOfDay()->toDateTimeString(); // yesterday
        elseif($request->days == 'custom'):
            $date = explode('To', $request->filter_date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $subDays   = $from;
            $todayDate = $to;

        else:
            $subDays                        = Carbon::parse(Carbon::today()->subDays(7)->format('Y-m-d'))->startOfDay()->toDateTimeString(); //start time today to 7 days previus date
            $todayDate                      = Carbon::parse(Carbon::today()->addDays(1)->format('Y-m-d'))->endOfDay()->toDateTimeString();// end time today date
        endif;

        $data=[];
        $data['from']                   = $subDays;
        $data['to']                     = $todayDate;

        return $data;
    }

    public function Dates($request){
        if($request->days     == 'today'):
            $startDate                      = Carbon::today()->format('Y-m-d');//today date
            $endDate                        = Carbon::today()->format('Y-m-d'); // today date
            $subDays                        = Carbon::parse(trim($startDate))->startOfDay()->toDateTimeString();
            $todayDate                      = Carbon::parse(trim($endDate))->endOfDay()->toDateTimeString();

        elseif($request->days     == 'week'):
            $todayDate                      = Carbon::today()->addDays(1)->format('Y-m-d');//today date
            $subDays                        = Carbon::today()->subDays(7)->format('Y-m-d'); // today to 7 days previus date

        elseif($request->days     == '15days'):
            $todayDate                      = Carbon::today()->addDays(1)->format('Y-m-d');//today date
            $subDays                        = Carbon::today()->subDays(15)->format('Y-m-d'); // today to 15days previus date
        elseif($request->days == 'month'):
            $todayDate                      = Carbon::today()->addDays(1)->format('Y-m-d');//today date
            $subDays                        = Carbon::today()->subDays(30)->format('Y-m-d'); // today to 30 days previus date
        elseif($request->days == 'yesterday'):
            $todayDate                      = Carbon::today()->format('Y-m-d');//yesterday date
            $subDays                        = Carbon::today()->subDays(1)->format('Y-m-d'); // yesterday
        elseif($request->days == 'custom'):
            $date = explode('To', $request->filter_date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $subDays   = $from;
            $todayDate = $to;
        else:
            $todayDate                      = Carbon::today()->format('Y-m-d');//today date
            $subDays                        = Carbon::today()->subDays(7)->format('Y-m-d'); // today to 7 days previus date
        endif;
        $totaldays                         = Carbon::parse($subDays)->diffInDays($todayDate);//today date to previus date total days
        $d=$subDays;
        $dates=[];
        for ($i=0; $i < $totaldays; $i++) {
            $dates[] = Carbon::parse($d)->addHours(24)->format('d-m-Y');
            $d       = Carbon::parse($d)->addHours(24)->format('d-m-Y');
        }


        return $dates;
    }

    public function incomeDate($request){
         //
    }
    public function expenseDate($request){
         //
    }

    public function parcelPosition($request,$status,$date){

        return Parcel::companywise()->where('status',$status)->whereBetween('updated_at',[$date['from'],$date['to']])->get();
    }

    public function recentAccounts($request,$date){
        return Account::companywise()->where('status',Status::ACTIVE)->whereBetween('updated_at',[$date['from'],$date['to']])->orderBy('id','desc')->limit(3)->get();//recent accounts
    }

    public function salaryGenerated($date){


          return SalaryGenerate::companywise()->whereBetween('updated_at',[$date['from'],$date['to']])->get();
    }
    public function salary($date){
        return Salary::companywise()->whereBetween('updated_at',[$date['from'],$date['to']])->get();
    }

    public function salaries($date){
        return Salary::companywise()->whereBetween('updated_at',[$date['from'],$date['to']]);
    }


    public function bankTransaction($date){
        return BankTransaction::companywise()->whereBetween('updated_at',[$date['from'],$date['to']])->orderByDesc('id')->limit(5)->get();
    }


    public function income($date){
        return Income::companywise()->whereBetween('date',[$date['from'],$date['to']])->sum('amount');//total income
    }
    public function expense($date){
        return Expense::companywise()->whereBetween('date',[$date['from'],$date['to']])->sum('amount');//total expense
    }
    public function merchantIncome($date){
        return MerchantStatement::companywise()->whereBetween('date',[$date['from'],$date['to']])->where('type',AccountHeads::INCOME)->sum('amount');//merchant total income
    }
    public function merchantExpense($date){
        return  MerchantStatement::companywise()->whereBetween('date',[$date['from'],$date['to']])->where('type',AccountHeads::EXPENSE)->sum('amount');//merchant total expense
    }
    public function deliverymanIncome($date){
        return DeliverymanStatement::companywise()->whereBetween('date',[$date['from'],$date['to']])->where('type',AccountHeads::INCOME)->sum('amount');//Deliveryman total income
    }
    public function deliverymanExpense($date){
        return DeliverymanStatement::companywise()->whereBetween('date',[$date['from'],$date['to']])->where('type',AccountHeads::EXPENSE)->sum('amount');//Deliveryman total expense
    }

    public function courierIncome($date){
        return CourierStatement::companywise()->whereBetween('date',[$date['from'],$date['to']])->where('type',AccountHeads::INCOME)->sum('amount');//Courier total income
    }
    public function courierExpense($date){
        return CourierStatement::companywise()->whereBetween('date',[$date['from'],$date['to']])->where('type',AccountHeads::EXPENSE)->sum('amount');//Courier total Expense
    }
 
        //dashbord balance
        public function balanceDetails() { 
            $data = []; 

            $parcel_ids   = []; 
            $invoices = Invoice::companywise()->where('merchant_id',Auth::user()->merchant->id)->where('status',InvoiceStatus::PAID)->pluck('parcels_id');
            foreach($invoices as $invoice):
                if(!blank($invoice)):
                    $parcel_ids = array_merge($parcel_ids,$invoice);
                endif;
            endforeach; 
            $parcels =  Parcel::companywise()->where('merchant_id',Auth::user()->merchant->id)->whereNotIn('id',$parcel_ids)->whereIn('status',[ParcelStatus::PARTIAL_DELIVERED,ParcelStatus::DELIVERED]);
            $data['amount_delivered']           = $parcels->sum('cash_collection');
            $data['payable_delivery_charge']    = $parcels->sum('delivery_charge');
            $data['sub_total']                  = $data['amount_delivered'] - $data['payable_delivery_charge'];
            $data['vat_amount']                 = $parcels->sum('vat_amount');
            $data['cod_charge']                 = $parcels->sum('cod_amount'); 
            $data['available_balance']          = ($data['sub_total'] - $data['vat_amount']) - $data['cod_charge'];
            $data['clearable_parcels']          = $parcels->count();
            return $data;
        }
        public function availableParcels(){
            $parcel_ids   = []; 
            $invoices = Invoice::companywise()->where('merchant_id',Auth::user()->merchant->id)->where('status',InvoiceStatus::PAID)->pluck('parcels_id');
            foreach($invoices as $invoice):
                if(!blank($invoice)):
                    $parcel_ids = array_merge($parcel_ids,$invoice);
                endif;
            endforeach; 
            $parcels =  Parcel::companywise()->where('merchant_id',Auth::user()->merchant->id)->whereNotIn('id',$parcel_ids)->whereIn('status',[ParcelStatus::PARTIAL_DELIVERED,ParcelStatus::DELIVERED])->get();
            return $parcels;
        } 


        
    public function analyticsFromTo($date){
        $date = explode('To', $date);
        $data = [];
        $data['from']   = '';
        $data['to']     = '';
        if(is_array($date)) {
            $data['from']   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
            $data['to']     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
        } 

        return $data;
    }

}
