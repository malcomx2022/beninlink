@extends('backend.partials.master')
@section('title')
    {{ __('reports.title') }} {{ __('reports.parcel_total_summery') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('reports.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('reports.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('parcel.reports') }}" class="breadcrumb-link">{{ __('reports.parcel_total_summery') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('merchant.parcel.filter.total.summery')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group"  >
                                <div class="d-flex col-12 col-md-12 col-lg-8 col-sm-12">
                                    <input type="text" autocomplete="off" id="date" name="parcel_date" placeholder="Enter Date" class="form-control date_range_picker group-input w-50" value="{{ old('parcel_date',$request->parcel_date) }}">
                                    <button type="submit" class="btn btn-space btn-primary group-btn ml-0"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('parcel.total.summery.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                                @error('parcel_date')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="form-group d-inline-block   pt-1">
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 pl-0 col-12 pt-4 d-flex justify-content">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                @if(!blank($parcelsStatus))
                <div class="col-xl-4 col-lg-6 col-md-6">
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
                @endif
                @if(!blank($parcels))
                    <div class="col-xl-4 col-lg-6 col-md-6">
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
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalCashCollection'] - $parcelsTotal['totalSellingPrice'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Cash_Collection_Info')}}</p>
                            </span>
                            <ul class="list-group m-2">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                    <span class="float-right">{{ __('levels.amount') }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('dashboard.total_cash_collection')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalCashCollection'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('dashboard.total_selling_price')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalSellingPrice'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Payable_to_Merchant')}}</p>
                            </span>
                            <ul class="list-group m-2">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                    <span class="float-right">{{ __('reports.amount') }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_payable_merchant(COD)')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalPaybleAmount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_paid_to_merchant(with Pending)')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format(0,2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_paid_by_Merchant')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($merchantTotalPayment['paidAmount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_Delivery_Charge(Including VAT)')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelProfit['totalDeliveryChargeVat'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Pending_Payments')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($merchantTotalPayment['pendingAmount'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-8 col-lg-12 col-md-12">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Bank_Cash_Info')}}</p>
                            </span>
                            <ul class="list-group m-2">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                    <span class="float-right">{{ __('reports.amount') }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_paid_to_merchant')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($merchantTotalPayment['paidAmount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Pending_Payments')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($merchantTotalPayment['pendingAmount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_bank_opening_balance')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelsTotal['totalBankOpeningBalance'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Current_Cash_Balance')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}}  {{ number_format($parcelsTotal['totalBankBalance'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        #selectAssignType .select2-container .select2-selection--single {
        height: 32px !important;
    }
    </style>
@endpush
<!-- js  -->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
    <script>
        var merchantUrl = '{{ route('parcel.merchant.get') }}';
        var merchantID = '{{ $request->parcel_merchant_id }}';
        var deliveryManID = '{{ $request->parcel_deliveryman_id }}';
        var pickupManID = '{{ $request->parcel_pickupman_id }}';
        var dateParcel = '{{ $request->parcel_date }}';
    </script>
    <script src="{{ static_asset('backend/js/parcel/filter.js') }}"></script>
    <script src="{{ static_asset('backend/js/reports/print.js') }}"></script>
    <script src="{{ static_asset('backend/js/reports/jquery.table2excel.min.js') }}"></script>
    <script src="{{ static_asset('backend/js/reports/reports.js') }}"></script>
 @endpush



