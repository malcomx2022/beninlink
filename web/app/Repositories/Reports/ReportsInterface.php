<?php
namespace App\Repositories\Reports;

interface  ReportsInterface{
    public function parcelReports($request);
    public function merchantParcelReports($request);
    public function parcelWiseProfitReports($request);
    public function salaryReports($request);
    public function salaryReportsPrint($request);
    public function MHDreports($request);
    public function MHDprint($request);
    public function MerchantExport($request);
    public function parcelTotalSummeryReports($request);
    public function commissionDeliveryman($request);
    public function cashReceivedDeliveryman($request);
    public function incomeExpense($type);
    public function hubincomeExpense($hub_id,$type);
    public function deliverymanreportParcels($request);
    public function deliverymanStatement($request);
    public function totalHubIncome($request);




}
