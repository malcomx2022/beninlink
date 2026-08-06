<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\v10\IncomeExpenseResource;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Expense;
use App\Models\Backend\Parcel;
use App\Models\CashReceivedFromDeliveryman;
use App\Repositories\Parcel\ParcelInterface;
use App\Repositories\Reports\ReportsInterface;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;



class DeliveryManIncomeExpenseController extends Controller
{

    use ApiReturnFormatTrait;

    protected $repo;
    protected $report;
    public function __construct(ParcelInterface $repo,ReportsInterface $report)
    {
        $this->repo = $repo;
        $this->report = $report;
    }


    public function deliverymanIncomeExpense()
    {
        try {
            $incomes                 = DeliverymanStatement::where(['type'=>StatementType::INCOME,'delivery_man_id'=>auth()->user()->deliveryman->id])->get();
            $expenses                = DeliverymanStatement::where(['type'=>StatementType::EXPENSE,'delivery_man_id'=>auth()->user()->deliveryman->id])->get();
            $totalIncome             = $incomes->sum('amount');
            $totalExpenses           = $incomes->sum('amount');
            $deliverymanTotalAmount  = $this->deliverymanTotalAmount($totalIncome,$totalExpenses);
            return $this->responseWithSuccess(__('income.income_expense'), ['income'=>IncomeExpenseResource::collection($incomes),'expense'=>IncomeExpenseResource::collection($expenses),'deliveryInfo'=>$deliverymanTotalAmount], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('income.error_msg'), [], 500);
        }
    }

    private function deliverymanTotalAmount($totalIncome,$totalExpenses){
        $statement  = DeliverymanStatement::where(['delivery_man_id'=>auth()->user()->deliveryman->id])->get();
        $parcels_id   = $statement->pluck('parcel_id');
        $ids=[];
        foreach ($parcels_id as $id) {
            $ids[]=$id;
        }
        $totalParcels =   Parcel::whereIn('id',$ids)->whereIn('status',[parcelStatus::DELIVERED,parcelStatus::PARTIAL_DELIVERED,parcelStatus::RECEIVED_WAREHOUSE,parcelStatus::RETURN_ASSIGN_TO_MERCHANT])->orderBy('id','asc')->get();

        $parcelsTotal =[];
        $parcelsTotal['currency']                       = settings()->currency;
        $parcelsTotal['totalPaybleAmount']              = 0;
        $parcelsTotal['totalCashCollection']            = 0;
        $parcelsTotal['totalDeliveryIncome']            = $totalIncome;
        $parcelsTotal['totalDeliveryExpense']           = number_format($totalExpenses,2);

        $totalCommissionDeliveryMan                     = Expense::where(['account_head_id'=>5,'delivery_man_id'=>auth()->user()->deliveryman->id])->get();
        $totalCashReceivedDeliveryman                   = CashReceivedFromDeliveryman::where(['delivery_man_id'=>auth()->user()->deliveryman->id])->get();

        $parcelsDelivered                               = $totalParcels->where('status',ParcelStatus::DELIVERED);
        $parcelsPartialDelivered                        = $totalParcels->where('partial_delivered',1);

        $parcelsTotal['totalCashCollection']           = number_format( $parcelsDelivered->sum('cash_collection')+$parcelsPartialDelivered->sum('cash_collection'),2);


        $parcelsTotal['totalCashReceivedDeliveryman']  = number_format($totalCashReceivedDeliveryman->sum('amount'),2);
        $parcelsTotal['totalDeliveryIncome']           += $totalCommissionDeliveryMan->sum('amount');
        $parcelsTotal['totalDeliveryIncome']           = number_format($parcelsTotal['totalDeliveryIncome'] ,2);
        return $parcelsTotal;
    }

}
