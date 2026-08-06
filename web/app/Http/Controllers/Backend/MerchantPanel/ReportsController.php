<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\AccountHeads;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Http\Controllers\Controller;
use App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    protected $repo;
    public function __construct(TotalSummeryReportInterface $repo){
        $this->repo = $repo;
    }

    public function TotalSummeryReports(Request $request){
        $parcelsStatus=null;
        $parcels=null;
        return view('backend.merchant_panel.reports.total_summery',compact('request','parcelsStatus','parcels'));
    }

    public function TotalSummeryReportsFilter(Request $request){

        $merchant                                          = Auth::user()->merchant;
        $totalParcels                                      = $this->repo->merchantparcelTotalSummeryReports($request);
        $accounts                                       = Auth::user()->accounts;
        $parcelsStatus                                  = $totalParcels->groupBy('status');
        $parcelsMerchant                                = $totalParcels->groupBy('merchant_id');
        $parcels                                        = $totalParcels;
        $parcelsDelivered                               = $totalParcels->where('status',ParcelStatus::DELIVERED);
        $parcelsPartialDelivered                        = $totalParcels->where('partial_delivered',1);
        $parcelsTotal['totalBankOpeningBalance']        = $accounts->sum('opening_balance');
        $parcelsTotal['totalBankBalance']               = $accounts->sum('balance');
        $parcelsTotal['totalPaybleAmount']              = 0;
        $parcelsTotal['totalCashCollection']            = 0;
        $parcelsTotal['totalSellingPrice']              = 0;
        $parcelsTotal['totalDeliveryIncome']            = 0;
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
        $parcelsTotal['totalSellingPrice']          = $parcelsDelivered->sum('selling_price')+$parcelsPartialDelivered->sum('selling_price');

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

        return view('backend.merchant_panel.reports.total_summery',compact('request','merchant','parcelsStatus','parcelProfit','parcelsTotal','merchantTotalPayment','parcels'));
    }

}
