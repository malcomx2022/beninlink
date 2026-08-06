
@if(!isset($print))
<div class="row p-4 print-btn">
     <div class="col-12 text-right">
         <a href="{{ route('merchant.hub.deliveryman.reports.print-page',[
             'parcelsStatus'=>parcelsStatus($parcelsStatus,'true'),
             'parcels'      => idWiseParcels($parcels,'true'),
             'parcelProfit' => $parcelProfit,
             'parcelsTotal' => $parcelsTotal,
             'merchantTotalPayment'=>$merchantTotalPayment,
             'user_type'    => $request->user_type
         ]) }}" class="btn btn-primary" target="_blank">{{ __('reports.print') }}</a>
     </div>
</div>
@endif
<div class="row">
    <div class="col-md-4">
        <div class="card card-height">
            <span class="card-header pb-1">
                    <p class="float-left mb-0 ont-16 font-weight-bold">{{__('parcel.title')}} {{ __('levels.status') }}</p>
              </span>
            <ul class="list-group m-2">
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                    <span class="float-right">{{  __('reports.count') }}</span>
                </li>
                @foreach($parcelsStatus as $key=>$parcelCount)
                    <li class="list-group-item profile-list-group-item">
                        <span class="float-left font-weight-bold">{{ trans("parcelStatus." . $key) }}</span>
                        <span class="float-right" id="totalCashCollection">{{ $parcelCount->count() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-height">
            <span class="card-header pb-1">
                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.profit_info')}}</p>
            </span>
            <ul class="list-group m-2">
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                    <span class="float-right">{{ __('levels.amount') }}</span>
                </li>

                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_Delivery_Charge')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['totalDeliveryCharge'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.COD_Charge')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['totalCOD'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_Vat')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['totalVat'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.F./L.Charge')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['totalLiquidFragileAmount'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.P.Charge')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['packagingAmount'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_Profit')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['totalDeliveryChargeVat'] - $parcelsTotal['totalDeliveryIncome'],2) }}</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-height">
            <span class="card-header pb-1">
                    <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Hub_info')}}</p>
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
                    <span class="float-left font-weight-bold">{{ __('reports.Cash_received_from_delivery_man')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalCashReceivedDeliveryman'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('reports.Total_paid_to_Hub')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalHubIncome'],2) }}</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-height">
            <span class="card-header pb-1">
                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Bank_Cash_Info')}}</p>
            </span>
            <ul class="list-group m-2">
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                    <span class="float-right">{{ __('levels.amount') }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('income.title')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}}  {{ number_format($parcelsTotal['totalBankTransactionIncome'],2) }}</span>
                </li>
                <li class="list-group-item profile-list-group-item">
                    <span class="float-left font-weight-bold">{{ __('expense.title')  }}</span>
                    <span class="float-right" id="totalCashCollection">{{settings()->currency}}  {{ number_format($parcelsTotal['totalBankTransactionExpense'],2) }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
