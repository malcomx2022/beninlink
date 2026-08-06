@if(!isset($print))
<div class="row p-4 print-btn">

     <div class="col-12 text-right">
         <a href="{{ route('merchant.hub.deliveryman.reports.print-page',[
             'parcelProfit' => $parcelProfit,
             'parcelsTotal' => $parcelsTotal,
             'user_type'    => $request->user_type
         ]) }}" class="btn btn-primary" target="_blank">{{ __('reports.print') }}</a>
     </div>
</div>
@endif
<div class="row">
    <div class="col-md-4">
        <div class="card card-height">
            <span class="card-header pb-1">
                <p class="float-left mb-0 ont-16 font-weight-bold"> {{ __('income.title') }} / {{ __('expense.title') }} </p>
            </span>
            <ul class="list-group m-2">
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                    <span class="float-right">{{ __('levels.amount') }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('income.title') }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalDeliveryIncome'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('expense.title') }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalDeliveryExpense'],2) }}</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-height">
            <span class="card-header pb-1">
                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Delivery_man_info')}}</p>
            </span>
            <ul class="list-group m-2">
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                    <span class="float-right">{{ __('Amount(Tk)') }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.total_cash_collection')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalCashCollection'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_Income')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalDeliveryIncome'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_paid_to_hub')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalCashReceivedDeliveryman'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_Payable_to_hub')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalDeliveryExpense']-$parcelsTotal['totalDeliveryIncome'],2)   }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
