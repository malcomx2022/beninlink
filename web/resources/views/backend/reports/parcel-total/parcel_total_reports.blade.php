@extends('backend.partials.master')
@section('title','Parcels Total Summery | List')
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
                    <form action="{{route('parcel.filter.total.summery')}}"  method="GET">
               
                        <div class="row">
                            <div class="form-group col-xl-3 col-md-4  col-sm-6">
                                <label for="parcel_date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="parcel_date" class="form-control date_range_picker" value="{{ old('parcel_date',$request->parcel_date) }}">
                                @error('parcel_date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group  col-xl-3 col-md-4  col-sm-6">
                                <label for="parcelMerchantid">{{ __('parcel.merchant') }}</label>
                                <select style="width: 100%" id="parcelMerchantid"  name="parcel_merchant_id" class="form-control @error('parcel_merchant_id') is-invalid @enderror" data-url="{{ route('parcel.merchant.shops') }}">
                                    <option value=""> {{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                </select>
                                @error('parcel_merchant_id')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group  col-xl-3 col-md-4  col-sm-6">
                                <label for="parcelhub">{{ __('parcel.hub') }}</label>
                                <select style="width: 100%" id="parcelhub"  name="hub_id" class="form-control  "  >
                                    <option value="" selected> {{ __('menus.select') }} {{ __('hub.title') }}</option>
                                    @foreach ($hubs as  $hub)
                                    <option value="{{ $hub->id }}" @if(old('hub_id',$hub->id) == $request->hub_id) selected @endif>{{ $hub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-xl-3 col-md-4 col-sm-6">
                                <div class="form-group d-inline-block pt-1">
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 d-flex justify-content pl-0">
                                        <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('parcel.total.summery.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                @if(!blank($parcelInfo))
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold">
                                    {{ __('reports.parcel_info')}}
                                </p>
                            </span>
                            <ul class="list-group m-2">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_created_parcel')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{  $parcelInfo['total_created_parcels'] }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_created_cash_collection')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}}  {{  number_format($parcelInfo['total_created_cash_collection'],2)}}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_delivered')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{  $parcelInfo['total_delivered']}}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_partial_delivered')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{  $parcelInfo['total_partial_delivered'] }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_delivered_cash_collection')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelInfo['total_delivered_cash_collection'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_in_transit')  }}</span>
                                    <span class="float-right" id="totalCashCollection">  {{ $parcelInfo['total_in_transit']  }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_in_transit')  }} {{ __('levels.cash_collection') }}</span>
                                    <span class="float-right" id="totalCashCollection"> {{ settings()->currency }}  {{ number_format($parcelInfo['total_in_transit_cash_collection'],2)  }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_returned_to_merchant')  }}  </span>
                                    <span class="float-right" id="totalCashCollection"> {{ $parcelInfo['total_returned_to_merchant'] }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_returned_to_merchant')  }} {{ __('levels.cash_collection') }}</span>
                                    <span class="float-right" id="totalCashCollection">{{ settings()->currency }} {{ number_format($parcelInfo['total_returned_to_merchant_cash_collection'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
                @if(!blank($parcelInfo))
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('parcel.title')}} {{ __('levels.status') }}</p>
                                </span>
                            <ul class="list-group m-2" style="overflow-y: scroll">
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
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold"> {{ __('reports.profit')  }} ({{ __('reports.delivered_partial_delivered') }}) </p>
                            </span>
                            <ul class="list-group m-2">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                    <span class="float-right">{{ __('levels.amount') }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_cod_amount')  }}  </span>
                                    <span class="float-right" id="totalCashCollection">{{ settings()->currency }} {{ number_format($parcelInfo['total_delivered_cod_amount'],2) }}</span>
                                </li>
                                    <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_delivery_charge_amount')  }}  </span>
                                    <span class="float-right" id="totalCashCollection">{{ settings()->currency }} {{ number_format($parcelInfo['total_delivery_charge_amount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_vat_amount')  }}  </span>
                                    <span class="float-right" id="totalCashCollection">{{ settings()->currency }} {{ number_format($parcelInfo['total_vat_amount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total') }} {{ __('reports.F./L.Charge')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelInfo['total_liquid_fragile_amount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total') }} {{ __('reports.P.Charge')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelInfo['total_packaging_amount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total') }} {{ __('reports.return_charges')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelInfo['total_returned_to_merchant_charges'],2) }}</span>
                                </li>

                                <li class="list-group-item profile-list-group-item" style="background-color: #00000014;font-weight:bold">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_charges')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelInfo['total_charges'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold"> {{ __('reports.delivery_man_income')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($parcelInfo['total_deliveryman_income'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item" style="background-color: #00000014;font-weight:bold">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_Profit')  }} </span> <small style="margin-left: 5px"> ({{ __('reports.total_charges')  }} - {{ __('reports.delivery_man_income')  }})</small>
                                    <span class="float-right" id="totalCashCollection">{{ settings()->currency }} {{ number_format( $parcelInfo['total_profit'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-height">
                            <span class="card-header pb-1">
                                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.Payable_to_Merchant')}} ( {{ __('reports.delivered_partial_delivered') }}) </p>
                                </span>
                            <ul class="list-group m-2">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                    <span class="float-right">{{ __('reports.amount') }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_payable_merchant')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($payabletoMerchant['payable_to_merchant'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Total_paid_to_merchant(with Pending)')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($payabletoMerchant['total_paid_to_merchant_amount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_due_amount')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($payabletoMerchant['total_merchant_due_amount'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.Pending_Payments')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($payabletoMerchant['total_merchant_pending_payment_amount'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
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
                                    <span class="float-left font-weight-bold">{{ __('reports.total_account_balance')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($bankTransaction['total_account_balance'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_account_opening_balance')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($bankTransaction['total_account_opening_balance'],2) }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('reports.total_fund_transfer_amount')  }}</span>
                                    <span class="float-right" id="totalCashCollection">{{settings()->currency}} {{ number_format($bankTransaction['total_fund_transfer_amount'],2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
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



