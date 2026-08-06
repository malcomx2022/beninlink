<?php
namespace App\Repositories\Reports\TotalSummeryReport;

interface  TotalSummeryReportInterface{


    public function parcelTotalSummeryReports($request);
    public function TotalparcelTotalSummeryReports($request);
    public function merchantparcelTotalSummeryReports($request);
    public function commissionDeliveryman($request);
    public function cashReceivedDeliveryman($request);
    public function incomeExpense($type);
    public function accounts($request);
    public function fundTransfer($request);
    public function parcelTotalDelivered($request);
    public function parcelTotalPartialDelivered($request);
    public function parcelTotalDeliveredCashcollection($request);
    public function parcelsInTransit($request);
    public function parcelsReturntoMerchant($request);
    public function merchantpayment($request);
    public function merchantPendingpayment($request);
    public function parcelTotalReceivedByWarehouse($request);



}
