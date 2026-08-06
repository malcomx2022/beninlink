<?php
namespace App\Repositories\Reports\TotalSummeryReport;

use App\Enums\AccountHeads;
use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\Expense;
use App\Models\Backend\FundTransfer;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\CashReceivedFromDeliveryman;
use Carbon\Carbon;
use Database\Seeders\ParcelSeeder;
use Illuminate\Support\Facades\Auth;
class TotalSummeryReportRepository implements TotalSummeryReportInterface {
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
            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }
        })->orderBy('id','asc')->get();
        return $parcels;
    }
    public function TotalparcelTotalSummeryReports($request){
        $parcels =   Parcel::companywise()->with('parcelEvent')->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);

            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }

        })->orderBy('id','asc')->get();
        return $parcels;
    }

    public function parcelTotalDelivered($request){
        $parcels =   Parcel::companywise()->where('status',ParcelStatus::DELIVERED)->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }
        })->select('id')->orderBy('id','asc')->get();
        return $parcels;
    }
    public function parcelsInTransit($request){
        $parcels =   Parcel::companywise()->whereNotIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }

            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }
        })->select('id','cash_collection')->orderBy('id','asc')->get();
        return $parcels;
    }
    public function parcelsReturntoMerchant($request){
        $parcels =   Parcel::companywise()->with('parcelEvent')->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }

            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }

            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }
        })->select('id','merchant_id','hub_id','cash_collection','return_charges')->with('deliverymanStatement')->orderBy('id','asc')->get();
        return $parcels;
    }


    public function parcelTotalPartialDelivered($request){

        $parcels =   Parcel::companywise()->with('parcelEvent')->where('status',ParcelStatus::PARTIAL_DELIVERED)->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }

        })->select('id')->orderBy('id','asc')->get();
        return $parcels;
    }
    public function parcelTotalDeliveredCashcollection($request){
        $parcels =   Parcel::companywise()->with('parcelEvent')->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }

            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }

        })->orderBy('id','asc')->get();
        return $parcels;

    }

    public function parcelTotalReceivedByWarehouse($request){
        $parcels =   Parcel::companywise()->with('parcelEvent')->whereNotIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED,ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }

            if($request->hub_id) {
                $query->where('hub_id',$request->hub_id);
            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }

        })->orderBy('id','asc')->get();
        return $parcels;
    }

    public function merchantpayment($request){
        $payment =   Payment::companywise()->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }

            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }
        })->select('id','merchant_id','amount','created_at','updated_at')->orderBy('id','asc')->get();
        return $payment;
    }

    public function merchantPendingpayment($request){
        $payment =   Payment::companywise()->where('status',ApprovalStatus::PENDING)->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            if($request->parcel_merchant_id) {
                $query->where(['merchant_id' => $request->parcel_merchant_id]);
            }

        })->select('id','merchant_id','amount','created_at','updated_at')->orderBy('id','asc')->get();
        return $payment;
    }

    //merchant total summery
    public function merchantparcelTotalSummeryReports($request){
        $parcels =   Parcel::companywise()->with('parcelEvent')->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
            $query->where(['merchant_id' => Auth::user()->merchant->id]);
        })->orderBy('id','asc')->get();
        return $parcels;
    }

    //end merchant total summery
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

        })->orderBy('id','asc')->get();
        return $commissionDeliveryMan;
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

        })->orderBy('id','asc')->get();
        return $cashReceivedDeliveryMan;
    }

    public function accounts($request){
        $accounts  =   Account::companywise()->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
        })->orderBy('id','asc')->get();
        return $accounts;
    }

    public function fundTransfer($request){
        $fund_transfer  =   FundTransfer::companywise()->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
        })->select('id','created_at','updated_at','amount')->orderBy('id','asc')->get();
        return $fund_transfer;
    }

    public function incomeExpense($type){
        return BankTransaction::companywise()->where('type',$type)->orderByDesc('id')->sum('amount');
    }

}
