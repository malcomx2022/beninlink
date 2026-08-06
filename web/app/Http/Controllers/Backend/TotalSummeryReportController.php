<?php

namespace App\Http\Controllers\Backend;
 
use App\Enums\StatementType;
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
use App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface;
use Illuminate\Http\Request;

class TotalSummeryReportController extends Controller
{
    protected $repo;
    protected $hub;
    protected $merchant;
    protected $deliveryman;
    protected $bankTransaction;
    public function __construct(TotalSummeryReportInterface $repo,HubInterface $hub, MerchantInterface $merchant,BankTransactionInterface $bankTransaction,DeliveryManInterface $deliveryman)
    {
        $this->repo         =  $repo;
        $this->hub          =  $hub;
        $this->merchant     =  $merchant;
        $this->deliveryman  =  $deliveryman;
        $this->bankTransaction     =  $bankTransaction;
    }

    public function parcelTotalSummery(Request $request){
        $hubs               = $this->hub->hubs();
        $merchant           = null;
        $totalParcels       = null;
        $parcelsStatus      = null;
        $parcels            = null;
        $date               = null;
        $parcelInfo         = null;
        return view('backend.reports.parcel-total.parcel_total_reports',compact('request','merchant','parcelInfo','parcelsStatus','parcels','hubs','date'));
    }

    public function parcelTotalSummeryFilter(Request $request){

        $date = explode('To', $request->parcel_date);
        $hubs                                           = $this->hub->hubs();
        $merchant                                       = $this->merchant->get($request->parcel_merchant_id);
        $totalParcels                                   = $this->repo->TotalparcelTotalSummeryReports($request);
        $parcelsStatus                                  = $totalParcels->groupBy('status');
        //parcel information
        $parcelInfo =[];
        $parcelInfo['total_created_parcels']                      = $this->repo->parcelTotalSummeryReports($request)->count();
        $parcelInfo['total_created_cash_collection']              = $this->repo->parcelTotalSummeryReports($request)->sum('cash_collection');
        $parcelInfo['total_delivered']                            = $this->repo->parcelTotalDelivered($request)->count();
        $parcelInfo['total_partial_delivered']                    = $this->repo->parcelTotalPartialDelivered($request)->count();
        $parcelInfo['total_delivered_cash_collection']            = $this->repo->parcelTotalDeliveredCashcollection($request)->sum('cash_collection');//with partial delivered
        $parcelInfo['total_in_transit']                           = $this->repo->parcelsInTransit($request)->count();
        $parcelInfo['total_in_transit_cash_collection']           = $this->repo->parcelsInTransit($request)->sum('cash_collection');
        $parcelInfo['total_returned_to_merchant']                 = $this->repo->parcelsReturntoMerchant($request)->count();
        $parcelInfo['total_returned_to_merchant_cash_collection'] = $this->repo->parcelsReturntoMerchant($request)->sum('cash_collection');
        $parcelInfo['total_returned_to_merchant_charges']         = $this->repo->parcelsReturntoMerchant($request)->sum('return_charges');
        $parcelInfo['total_returned_to_merchant_parcels']         = $this->repo->parcelsReturntoMerchant($request);
        //delivered and partial delivered profit
        $parcelInfo['total_delivered_cod_amount']                 = $this->repo->parcelTotalDeliveredCashcollection($request)->sum('cod_amount');
        $parcelInfo['total_delivery_charge_amount']               = $this->repo->parcelTotalDeliveredCashcollection($request)->sum('delivery_charge');
        $parcelInfo['total_vat_amount']                           = $this->repo->parcelTotalDeliveredCashcollection($request)->sum('vat_amount');
        $parcelInfo['total_liquid_fragile_amount']                = $this->repo->parcelTotalDeliveredCashcollection($request)->sum('liquid_fragile_amount');
        $parcelInfo['total_packaging_amount']                     = $this->repo->parcelTotalDeliveredCashcollection($request)->sum('packaging_amount');
        $parcelInfo['delivered_partials_delivered_parcels']       = $this->repo->parcelTotalDeliveredCashcollection($request);

        $parcelInfo['total_deliveryman_income']= 0;//delivered_partials_delivered_parcels
        $parcelInfo['total_charges'] = ($parcelInfo['total_delivered_cod_amount'] + $parcelInfo['total_delivery_charge_amount'] +  $parcelInfo['total_vat_amount'] + $parcelInfo['total_liquid_fragile_amount'] +  $parcelInfo['total_packaging_amount'] +  $parcelInfo['total_returned_to_merchant_charges']); //(cod + vat  + delivery charge) amount
        foreach ($parcelInfo['delivered_partials_delivered_parcels'] as $pStatements){
            if(!blank($pStatements->courierStatement)){
                foreach ($pStatements->courierStatement as $key => $Statement) {
                    if($Statement->type == StatementType::EXPENSE):
                        $parcelInfo['total_deliveryman_income'] += $Statement->amount;
                    endif;
                }
            }
        }
        foreach ($parcelInfo['total_returned_to_merchant_parcels'] as $rStatements){
            if(!blank($rStatements->courierStatement)){
                foreach ($rStatements->courierStatement as $key => $Sstatement) {
                    if($Sstatement->type == StatementType::EXPENSE):
                        $parcelInfo['total_deliveryman_income'] += $Sstatement->amount;
                    endif;
                }
            }
        }

        $parcelInfo['total_profit']  = ($parcelInfo['total_charges'] - $parcelInfo['total_deliveryman_income']);
        //bank transaction
        $bankTransaction =[];
        $bankTransaction ['total_account_balance']           = $this->repo->accounts($request)->sum('balance');
        $bankTransaction ['total_account_opening_balance']   = $this->repo->accounts($request)->sum('opening_balance');
        $bankTransaction ['total_fund_transfer_amount']      = $this->repo->fundTransfer($request)->sum('amount');
        //payable to merchant
        $payabletoMerchant['payable_to_merchant']                     =  $this->repo->parcelTotalDeliveredCashcollection($request)->sum('current_payable');
        $payabletoMerchant['total_paid_to_merchant_amount']           =  $this->repo->merchantpayment($request)->sum('amount');//payment amount with pending
        $payabletoMerchant['total_merchant_pending_payment_amount']   =  $this->repo->merchantPendingpayment($request)->sum('amount');//only pending payment amount
        $merchantPaymentWithoutPending = ($payabletoMerchant['total_paid_to_merchant_amount'] - $payabletoMerchant['total_merchant_pending_payment_amount']);
        $payabletoMerchant['total_merchant_due_amount']               = ( $payabletoMerchant['payable_to_merchant'] - $merchantPaymentWithoutPending);

        //end payable to merchant
        return view('backend.reports.parcel-total.parcel_total_reports',compact('request','payabletoMerchant','bankTransaction','merchant','parcelsStatus','hubs','date','parcelInfo'));

    }

}
