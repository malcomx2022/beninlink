<?php

namespace App\Http\Controllers\Backend;

use App\Enums\AccountHeads;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Exports\ParcelStatusReports;
use App\Exports\ReportExports;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\MHDreportsRequest;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\BankTransaction\BankTransactionInterface;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use App\Repositories\Reports\ReportsInterface;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MerchantReports; 
use Dotenv\Parser\Value;

class ReportsController extends Controller
{
    protected $repo;
    protected $hub;
    protected $merchant;
    protected $deliveryman;
    protected $bankTransaction;
    public function __construct(ReportsInterface $repo,HubInterface $hub, MerchantInterface $merchant,BankTransactionInterface $bankTransaction,DeliveryManInterface $deliveryman)
    {
        $this->repo         =  $repo;
        $this->hub          =  $hub;
        $this->merchant     =  $merchant;
        $this->deliveryman  =  $deliveryman;
        $this->bankTransaction     =  $bankTransaction;
    }
    public function parcelReports(Request $request){
        $parcels = [];
        $hubs     = $this->hub->hubs();
        return view('backend.reports.parcel_reports',compact('request','parcels','hubs'));
    }

    public function parcelSReports(Request $request){

        if($this->repo->parcelReports($request)){
            $parcels      =  $this->repo->parcelReports($request);
            $hubs         = $this->hub->hubs();
            $print        =   true;
            $parcel_ids   = '';
            foreach ($parcels as $key=>$parcel) {
                foreach ($parcel as $key => $parcl) {

                    $parcel_ids  = $parcl->id.','.$parcel_ids;
                }
            }
            return view('backend.reports.parcel_reports',compact('parcels','request','hubs','print','parcel_ids'));
        }else{
            return redirect()->back();
        }
    }


    public function parcelReportsPrint(Request $request,$array){

            $parcel_ids  = [];
            foreach (explode(',',$array) as  $id) {
                if($id !== ""):
                $parcel_ids [] = $id;
                endif;
            }
            $parcels    = Parcel::companywise()->whereIn('id',$parcel_ids)->orderBy('id')->get();
            $parcels    = $parcels->groupBy('status');

            return view('backend.reports.parcel_reports_print',compact('parcels'));
    }

    public function parcelWiseReports(Request $request){
        $hubs         = $this->hub->hubs();
        return view('backend.reports.parcel_wise_profit',compact('request','hubs'));
    }

    public function reportsTrackingParcels(Request $request){

        if(request()->ajax()):


            $parcels = [];
            $parcls   = Parcel::companywise()->where('tracking_id','like','%'.$request->search.'%')->paginate(10);
            foreach ($parcls as $key => $parcel) {
                $parcels []= [
                        'id'  => $parcel->id,
                        'text'=> $parcel->tracking_id
                ];
            }
            return response()->json($parcels);
        endif;
        return '';
    }

    public function ParcelWiseProfitReports(Request $request){

        if($this->repo->parcelWiseProfitReports($request)){
            $parcels      =  $this->repo->parcelWiseProfitReports($request);
            $hubs         = $this->hub->hubs();
            $print        =   true;
            $parcel_ids   = '';
            foreach ($parcels as $key=>$parcel) {
                $parcel_ids  = $parcel->id.','.$parcel_ids;
            }
            return view('backend.reports.parcel_wise_profit',compact('parcels','request','hubs','print','parcel_ids'));
        }else{
            return redirect()->back();
        }
    }

    public function parcelWiseProfitPrint($array){

        $parcel_ids  = [];
        foreach (explode(',',$array) as  $id) {
            if($id !== ""):
            $parcel_ids [] = $id;
            endif;
        }
        $parcels    = Parcel::companywise()->whereIn('id',$parcel_ids)->orderBy('id')->get();

        return view('backend.reports.parcel_wise_profit_print',compact('parcels'));
    }

    //salary reports
    public function salaryReports(Request $request){
            return view('backend.reports.salary_reports',compact('request'));
    }

    public function ReportssalaryReports(Request $request){
         $totalSalary       = $this->repo->salaryReports($request);
         $salaries          = $totalSalary['salary'];
         $salaryPayments    = $totalSalary['salaryPayment'];


        return view('backend.reports.salary_reports',compact('request','salaries','salaryPayments'));
    }

    public function SalaryReportPrint(Request $request){

        $totalSalary       = $this->repo->salaryReports($request);
        $salaries          = $totalSalary['salary'];
        $salaryPayments    = $totalSalary['salaryPayment'];

        return view('backend.reports.salary_reports_print',compact('request','salaries','salaryPayments'));
    }
    //merchant hub delivery man reports
    public function MerchantHubDeliverymanReports(Request $request){
        $hubs        = $this->hub->all();
        $merchants   = $this->merchant->all();
        $deliverymans = $this->deliveryman->all();
        return view('backend.reports.merchant_hub_delivery_reports',compact('request','hubs','merchants','deliverymans'));
    }

    public function MHDreports(MHDreportsRequest $request){


        if($request->user_type == 1 || $request->user_type == 2):
            //end merchant reports
            $hubs                                           = $this->hub->hubs();
            $merchant                                       = $this->merchant->get($request->parcel_merchant_id);
            $totalParcels                                   = $this->repo->parcelTotalSummeryReports($request);
            $totalCommissionDeliveryMan                     = $this->repo->commissionDeliveryman($request);
            $totalCashReceivedDeliveryman                   = $this->repo->cashReceivedDeliveryman($request);

            if($request->user_type == 2){

                $totalBankTransactionIncome                     = $this->repo->hubincomeExpense($request->hub_id,AccountHeads::INCOME);
                $totalBankTransactionExpense                    = $this->repo->hubincomeExpense($request->hub_id,AccountHeads::EXPENSE);

            }else{
                $totalBankTransactionIncome                     = $this->repo->incomeExpense(AccountHeads::INCOME);
                $totalBankTransactionExpense                    = $this->repo->incomeExpense(AccountHeads::EXPENSE);
            }


            $parcelsStatus                                  = $totalParcels->groupBy('status');

            $parcelsMerchant                                = $totalParcels->groupBy('merchant_id');
            $parcels                                        = $totalParcels;
            $parcelsDelivered                               = $totalParcels->where('status',ParcelStatus::DELIVERED);
            $parcelsPartialDelivered                        = $totalParcels->where('partial_delivered',1);

            $parcelsTotal['totalBankTransactionIncome']     = $totalBankTransactionIncome;
            $parcelsTotal['totalBankTransactionExpense']    = $totalBankTransactionExpense;
            $parcelsTotal['totalPaybleAmount']              = 0;
            $parcelsTotal['totalCashCollection']            = 0;
            $parcelsTotal['totalDeliveryIncome']            = 0;
            $parcelsTotal['totalHubIncome']                 = 0;
            $parcelsTotal['totalDeliveryExpense']           = 0;

            $parcelProfit['totalDeliveryChargeVat']         = 0;
            $parcelProfit['totalDeliveryCharge']            = 0;
            $parcelProfit['totalCOD']                       = 0;
            $parcelProfit['totalVat']                       = 0;
            $parcelProfit['totalLiquidFragileAmount']       = 0;
            $parcelProfit['packagingAmount']                = 0;

            $merchantID = [];
            foreach ($parcelsMerchant as $key => $value){
                $merchantID[]= $key;
            }
            $merchantTotalPayment                       = merchantPayments($merchantID);
            $parcelsTotal['totalCashCollection']        = $parcelsDelivered->sum('cash_collection')+$parcelsPartialDelivered->sum('cash_collection');
            $parcelsTotal['totalPaybleAmount']          = $parcelsDelivered->sum('current_payable')+$parcelsPartialDelivered->sum('current_payable');

            foreach ($parcels as $parcel){
                if(!blank($parcel->deliverymanStatement)){
                    $parcelProfit['totalDeliveryChargeVat']     += $parcel->total_delivery_amount;
                    $parcelProfit['totalDeliveryCharge']        += $parcel->delivery_charge;
                    $parcelProfit['totalCOD']                   += $parcel->cod_amount;
                    $parcelProfit['totalVat']                   += $parcel->vat_amount;
                    $parcelProfit['totalLiquidFragileAmount']   += $parcel->liquid_fragile_amount;
                    $parcelProfit['packagingAmount']            += $parcel->packaging_amount;

                    foreach ($parcel->deliverymanStatement as $deliveryStatement){
                        if($deliveryStatement->type == StatementType::INCOME){
                            $parcelsTotal['totalDeliveryIncome'] += $deliveryStatement->amount;
                        }else {
                            $parcelsTotal['totalDeliveryExpense'] += $deliveryStatement->amount;
                        }
                    }

                }
            }

            $parcelsTotal['totalCashReceivedDeliveryman']            = $totalCashReceivedDeliveryman->sum('amount');
            $parcelsTotal['totalHubIncome']                         += $this->repo->totalHubIncome($request)->sum('amount');
            $merchants       = $this->merchant->all();
            $deliverymans    = $this->deliveryman->all();
            return view('backend.reports.merchant_hub_delivery_reports',compact('request','merchant','parcelsStatus','parcelProfit','parcelsTotal','merchantTotalPayment','parcels','hubs','merchants','deliverymans'));
            //merchant reports

        elseif($request->user_type == 3):
            $totalParcels  = $this->repo->deliverymanreportParcels($request);
            $hubs          = $this->hub->all();
            $merchants     = $this->merchant->all();
            $deliverymans  = $this->deliveryman->all();
            $parcelsTotal  =[];
            $parcelProfit  =[];

            $totalCommissionDeliveryMan                     = $this->repo->commissionDeliveryman($request);
            $totalCashReceivedDeliveryman                   = $this->repo->cashReceivedDeliveryman($request);
            $parcelsDelivered                               = $totalParcels->where('status',ParcelStatus::DELIVERED);
            $parcelsPartialDelivered                        = $totalParcels->where('partial_delivered',1);
            $parcelsTotal['totalPaybleAmount']              = 0;
            $parcelsTotal['totalCashCollection']            = 0;
            $parcelsTotal['totalDeliveryIncome']            = 0;
            $parcelsTotal['totalDeliveryExpense']           = 0;
            $parcelProfit['totalDeliveryChargeVat']         = 0;
            $parcelProfit['totalDeliveryCharge']            = 0;
            $parcelProfit['totalCOD']                       = 0;
            $parcelProfit['totalVat']                       = 0;
            $parcelProfit['totalLiquidFragileAmount']       = 0;
            $parcelProfit['packagingAmount']                = 0;
            $parcelsTotal['totalCashCollection']            = $parcelsDelivered->sum('cash_collection')+$parcelsPartialDelivered->sum('cash_collection');

            $deliverymanStatement = $this->repo->deliverymanStatement($request);
            foreach ($totalParcels as $parcel){
                if(!blank($deliverymanStatement)){
                    $parcelProfit['totalDeliveryChargeVat']     += $parcel->total_delivery_amount;
                    $parcelProfit['totalDeliveryCharge']        += $parcel->delivery_charge;
                    $parcelProfit['totalCOD']                   += $parcel->cod_amount;
                    $parcelProfit['totalVat']                   += $parcel->vat_amount;
                    $parcelProfit['totalLiquidFragileAmount']   += $parcel->liquid_fragile_amount;
                    $parcelProfit['packagingAmount']            += $parcel->packaging_amount;

                }
            }

            foreach ($deliverymanStatement as $deliveryStatement){
                if($deliveryStatement->type == StatementType::INCOME){
                    $parcelsTotal['totalDeliveryIncome'] += $deliveryStatement->amount;
                }else {
                    $parcelsTotal['totalDeliveryExpense'] += $deliveryStatement->amount;
                }
            }
            $parcelsTotal['totalCashReceivedDeliveryman']            = $totalCashReceivedDeliveryman->sum('amount');
            $parcelsTotal['totalDeliveryIncome']                     += $totalCommissionDeliveryMan->sum('amount');
            $parcels       = true;
            $parcelsStatus  =  $this->repo->deliverymanreportParcels($request)->groupBy('status');
            return view('backend.reports.merchant_hub_delivery_reports',compact('request','totalParcels','parcelProfit','parcels','parcelsTotal','parcelsStatus','hubs','merchants','deliverymans'));
        endif;

        $MHDreports   = $this->repo->MHDreports($request);
        $hubs         = $this->hub->all();
        $merchants    = $this->merchant->all();
        $deliverymans = $this->deliveryman->all();
        return view('backend.reports.merchant_hub_delivery_reports',compact('request','MHDreports','hubs','merchants','deliverymans'));

    }

    public function MHDPrintPage(Request $request){
        $MHDreports     = $this->repo->MHDprint($request);
        $user_type      = $request->user_type;
        $report_title   = $request->report_title;
        return view('backend.reports.merchant-hub-deliveryman.print',compact('MHDreports','user_type','report_title'));
    }

    //export
    public function MerchantReportExport(Request $request){
        return  Excel::download(new MerchantReports, 'MerchantReports.xlsx');
    }


    public function mhdPDF(Request $request){
        return '';
    }

    //merchant deliveryman and hub report print page
    public function MerchantHubDeliveryReportsPrintPage(Request $request){
        //  merchant reports
        $parcelsStatus=[];
        if($request->parcelsStatus):
        $parcelsStatus   = parcelsStatus('','',$request->parcelsStatus);
        endif;
        $parcels=[];
        if($request->parcels):
        $parcels         = idWiseParcels('','',$request->parcels);
        endif;

        $parcelProfit=[];
        foreach ($request->parcelProfit as $key => $value) {
            $parcelProfit[$key]=(int)$value;
        }

        $parcelsTotal=[];
        foreach ($request->parcelsTotal as $key => $value) {
            $parcelsTotal[$key] = (int)$value;
        }
        $merchantTotalPayment=[];
        if($request->merchantTotalPayment):
            foreach ($request->merchantTotalPayment as $key => $value) {
                $merchantTotalPayment[$key] = (int)$value;
            }
        endif;
        $print= true;
        return view('backend.reports.merchant-hub-deliveryman.print.mhd_print',compact('parcelsStatus','parcels','parcelProfit','parcelsTotal','merchantTotalPayment','request','print'));
    }
}
