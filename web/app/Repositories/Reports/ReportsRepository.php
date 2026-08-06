<?php
namespace App\Repositories\Reports;

use App\Enums\AccountHeads;
use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Expense;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\Backend\Salary;
use App\Models\CashReceivedFromDeliveryman;
use App\Models\User;
use App\Repositories\Reports\ReportsInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Database\Seeders\ParcelSeeder;
use function Symfony\Component\Mime\Header\all;

class ReportsRepository implements ReportsInterface {

    public function parcelReports($request){
        $userHubID = $request->hub_id;
        if(!blank($userHubID)){
            $parcels =   Parcel::companywise()->with('parcelEvent')->where('hub_id',$userHubID)->orderBy('id','asc')->where(function( $query ) use ( $request ) {

                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
                }

                if($request->parcel_status) {
                    $query->whereIn('status',$request->parcel_status);

                }

                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

            })->orderBy('id','asc')->get();
            return $parcels->groupBy('status');

        }else{
            $parcels=  Parcel::companywise()->with('parcelEvent')->orderBy('id','asc')->where(function( $query ) use ( $request ) {

                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }

                }
                if($request->parcel_status) {
                    $query->whereIn('status',$request->parcel_status);
                }
                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }
            })->get();
            return $parcels->groupBy('status');
        }
    }

    public function merchantParcelReports($request){
        $parcels =   Parcel::companywise()->with('parcelEvent')->where('merchant_id',Auth::user()->merchant->id)->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if($request->parcel_status) {
                $query->whereIn('status',$request->parcel_status);
            }

            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }

        })->orderBy('id','asc')->get();
        return $parcels->groupBy('status');
    }

    public function parcelWiseProfitReports($request){
        $userHubID = $request->hub_id;
        if(!blank($userHubID)){
          return  Parcel::companywise()->with('parcelEvent')->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->where('hub_id',$userHubID)->orderBy('id','asc')->where(function( $query ) use ( $request ) {
                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('updated_at', [$from, $to]);
                    }
                }

                if($request->parcel_tracking_id) {
                    $query->whereIn('id',$request->parcel_tracking_id);
                }


                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

            })->orderBy('id','asc')->get();

        }else{
           return   Parcel::companywise()->with('parcelEvent')->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->orderBy('id','asc')->where(function( $query ) use ( $request ) {
                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('updated_at', [$from, $to]);
                    }
                }

                if($request->parcel_tracking_id) {
                    $query->whereIn('id',$request->parcel_tracking_id);
                }

                if($request->parcel_merchant_id) {
                    $query->where(['merchant_id' => $request->parcel_merchant_id]);
                }

            })->get();

        }
    }

    //salary reports
    public function salaryReports($request){

        $salaryPayment  = Salary::companywise()->with('user')->where(function($query)use($request){
            if($request->salary_date) {
                    $date = explode('To', $request->salary_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
            }
            if($request->month){
                $query->where('month',$request->month);
            }

            if($request->user_id):
                $query->whereIn('user_id',$request->user_id);
            endif;

        })->orderBy('month','asc')->get();
        $salary         = SalaryGenerate::companywise()->with('user')->where(function($query)use($request){
        if($request->salary_date) {
            $date = explode('To', $request->salary_date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                $query->whereBetween('created_at', [$from, $to]);
            }
        }
        if($request->month){
            $query->where('month',$request->month);
        }
        if($request->user_id):
            $query->whereIn('user_id',$request->user_id);
        endif;

    })->orderBy('month','asc')->get();

        $data['salaryPayment']   = $salaryPayment->groupBy('month');
        $data['salary']          = $salary->groupBy('month');
        return $data;
    }

    public function salaryReportsPrint($request){
        $data=[];
        $data['salaries'] = Salary::companywise()->whereIn('id',$request->salary_ids)->get()->groupBy('month');
        return $data;
    }
    //merchant hub delivery man reports
    public function MHDreports($request){
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                }
            }

            $data = [];
            if($request->user_type == 1):
            elseif($request->user_type == 2):

                $data['hub']                   =  Hub::find($request->hub_id);
                $data['hub_statements']        =  HubStatement::companywise()->where('hub_id',$request->hub_id)->get();
                //print need
                $data['hub_statement_ids']            = '';
                foreach ($data['hub_statements']->pluck('id') as $id) {
                    $data['hub_statement_ids']        = $id.','. $data['hub_statement_ids'];
                }
                //end print
            elseif($request->user_type == 3):
                $data['deliveryman']           =  DeliveryMan::find($request->delivery_man_id);
                $data['deliveryman_statements']=  DeliverymanStatement::companywise()->where('delivery_man_id',$request->delivery_man_id)->whereBetween('created_at', [$from, $to])->get();
                //print need
                    $data['deliveryman_statement_ids']            = '';
                    foreach ($data['deliveryman_statements']->pluck('id') as $id) {
                        $data['deliveryman_statement_ids']        = $id.','. $data['deliveryman_statement_ids'];
                    }
                //end print

            else:
                return [];
            endif;
            return $data;
        }


    public function MHDprint($request){
        $data=[];
        if($request->user_type == 3):
            $deliveryman_statement_ids = [];
            foreach (explode(',',$request->deliveryman_statement_ids) as  $id) {
                if($id !== ""):
                $deliveryman_statement_ids [] = $id;
                endif;
            }

            $data['deliveryman']           =  DeliveryMan::find($request->delivery_man_id);
            $data['deliveryman_statements']=  DeliverymanStatement::companywise()->where('delivery_man_id',$request->delivery_man_id)->whereIn('id',$deliveryman_statement_ids)->get();

        endif;

    return $data;
    }

    public function MerchantExport($request){
        //
    }
    //merchant reports
    public function parcelTotalSummeryReports($request){

        $parcels =   Parcel::companywise()->with('parcelEvent')->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if($request->user_type == 2){

                if($request->hub_id) {
                    $query->where('hub_id',$request->hub_id);
                }else{
                    $hubs=Hub::companywise()->get()->pluck('id');
                    $ids=[];
                    foreach ($hubs as  $id) {
                        $ids[]=$id;
                    }
                    $query->whereIn('hub_id',$ids);
                }

            }

            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }
            if($request->merchant_id) {
                $query->where(['merchant_id' => $request->merchant_id]);
            }

        })->orderBy('id','asc')->get();
        return $parcels;
    }

    public function commissionDeliveryman($request){

            $commissionDeliveryMan  =   Expense::companywise()->where('account_head_id',5)->where(function( $query ) use ( $request ) {
                if($request->parcel_date) {
                    $date = explode('To', $request->parcel_date);
                    if(is_array($date)) {
                        $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                        $query->whereBetween('date', [$from, $to]);
                    }
                }

                if($request->delivery_man_id) {
                    $query->where(['delivery_man_id' => $request->delivery_man_id]);
                }

            })->orderBy('id','asc')->get();
            return $commissionDeliveryMan;
    }


    public function totalHubIncome($request){
        $hubincome  =   HubStatement::companywise()->where('type',AccountHeads::INCOME)->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('date', [$from, $to]);
                }
            }
            if($request->hub_id) {
                $query->where(['hub_id' => $request->hub_id]);
            }

        })->orderBy('id','asc')->get();
        return $hubincome;
    }

    public function cashReceivedDeliveryman($request){
            $cashReceivedDeliveryMan  =   CashReceivedFromDeliveryman::companywise()->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('date', [$from, $to]);
                }
            }
            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }
            if($request->delivery_man_id) {
                $query->where(['delivery_man_id' => $request->delivery_man_id]);
            }

        })->orderBy('id','asc')->get();
        return $cashReceivedDeliveryMan;
    }


    public function incomeExpense($type){
        return BankTransaction::companywise()->where('type',$type)->orderByDesc('id')->sum('amount');
    }

    public function hubincomeExpense($hub_id,$type){
        return BankTransaction::companywise()->where('hub_id',$hub_id)->where('type',$type)->orderByDesc('id')->sum('amount');
    }
    //delivery man reports
    public function deliverymanreportParcels($request){

        $parcels_id   = $this->deliverymanStatement($request)->pluck('parcel_id');
        $ids=[];
        foreach ($parcels_id as $id) {
            $ids[]=$id;
        }
        $parcels =   Parcel::companywise()->whereIn('id',$ids)->whereIn('status',[parcelStatus::DELIVERED,parcelStatus::PARTIAL_DELIVERED,parcelStatus::RECEIVED_WAREHOUSE,parcelStatus::RETURN_ASSIGN_TO_MERCHANT])->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }
        })->orderBy('id','asc')->get();
        return $parcels;
    }
    public function deliverymanStatement($request){
        $deliveryStatement =   DeliverymanStatement::companywise()->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if($request->delivery_man_id) {
                $query->where(['delivery_man_id' => $request->delivery_man_id]);
            }

        })->orderBy('id','asc')->get();
        return $deliveryStatement;
    }
}
