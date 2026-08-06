<?php
namespace App\Repositories\Dashboard;

interface DashboardInterface {

    public function FromTo($request);
    public function Dates($request);
    public function incomeDate($request);
    public function expenseDate($request);
    public function bankTransaction($date);
    public function income($date);
    public function expense($date);
    public function merchantIncome($date);
    public function merchantExpense($date);
    public function deliverymanIncome($date);
    public function deliverymanExpense($date);
    public function courierIncome($date);
    public function courierExpense($date);
    public function parcelPosition($request,$status,$date);
    public function recentAccounts($request,$date);
    public function salary($date);
    public function salaries($date);
    public function salaryGenerated($date);

    public function balanceDetails();
    public function availableParcels();

    public function analyticsFromTo($date);
}
