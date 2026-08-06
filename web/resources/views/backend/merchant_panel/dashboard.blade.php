<!-- wrapper  -->

@extends('backend.partials.master')
@section('title')
    {{ __('merchant.dashboard') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content ">
        <!-- pageheader  -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ url('/') }}"
                                        class="breadcrumb-link">{{ __('merchant.dashboard') }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    {{ __('merchant.merchant_dashboard') }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end pageheader  -->
        <div class="ecommerce-widget merchant-dashboard-filter">
            <div class="row p-0 mb-3">
                <div class="col-12 col-md-6">
                    <p class="h3 d-inline">{{ __('merchant.merchant_dashboard') }}</p>
                </div>
                <div class="col-12 col-md-6 text-right  pt-2 pt-sm-0">
                    <form action="{{ route('merchant-panel.dashboard.filter') }}" method="POST"
                        class="d-flex justify-content-end">
                        @csrf
                        <input type="text" autocomplete="off" id="date" name="date"
                            class="input py-1 w200 date_range_picker w-50 form-control group-input "
                            value="{{ isset($request->date) ? $request->date : old('date') }}"
                            placeholder="{{ __('merchantPlaceholder.date') }}">
                        <button type="submit" class="btn btn-sm btn-primary group-btn"
                            style="margin-left: -5px!important"><i class="fa fa-search"></i>
                            {{ __('levels.filter') }}</button>
                    </form>
                </div>
            </div>


            {{-- parcel info --}}
            <div class="row merchant-panel header-summery">
                <div class="col-sm-6  col-lg-6 col-xl-3">
                    <a href="{{ route('merchant-panel.parcel.index') }}" class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex ">
                                    <label class="icon p-10px"><i class="fa fa-box-open text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class="m-0 text-primary">{{ __('dashboard.total_parcel') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $t_parcel }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-6  col-xl-3">
                    <a href="{{ route('merchant-panel.parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::DELIVERED]) }}"
                        class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex">
                                    <label class="icon  p-10px"><i class="fa fa-shipping-fast text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class=" m-0 text-primary">{{ __('dashboard.total_deliverd_') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $t_delivered }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class=" col-sm-6 col-lg-6 col-xl-3">
                    <a href="{{ route('merchant-panel.parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT]) }}"
                        class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex ">
                                    <label class="icon  p-10px"><i class="fa fa-dna text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class=" m-0 text-primary">{{ __('dashboard.total_return') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $t_return }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class=" col-sm-6 col-lg-6  col-xl-3">
                    <a href="{{ route('merchant-panel.parcel.index') }}" class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex ">
                                    <label class="icon  p-10px"><i class="fa fa-dolly text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class=" m-0 text-primary">{{ __('dashboard.total_transit') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $t_parcel - ($t_delivered + $t_return) }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>


            {{-- parcel details --}}
            <div class="row">
                <div class="col-12">
                    <div class="row py-3  ">
                        <div class="col-md-4">
                            <ul class="list-group mt-2 ">
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('dashboard.total_cash_collection') }}
                                    </span>
                                    <span class="float-right"> {{ settings()->currency }}{{ $t_cash_collection }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span
                                        class="float-left font-weight-bold">{{ __('dashboard.total_selling_price') }}</span>
                                    <span class="float-right"> {{ settings()->currency }}{{ $t_selling_price }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">
                                        {{ __('dashboard.net_profit_ammount') }}</span>
                                    <span class="float-right">
                                        {{ settings()->currency }}{{ $t_cash_collection - $t_selling_price }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="list-group mt-2 ">
                                <li class="list-group-item profile-list-group-item">
                                    <span
                                        class="float-left font-weight-bold">{{ __('dashboard.total_liquid_fragile_amount') }}</span>
                                    <span class="float-right">{{ settings()->currency }}{{ $t_liquid_fragile }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span
                                        class="float-left font-weight-bold">{{ __('dashboard.total_packaging_amount') }}</span>
                                    <span class="float-right">{{ settings()->currency }}{{ $t_packaging }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span class="float-left font-weight-bold">{{ __('dashboard.total_vat_amount') }}</span>
                                    <span class="float-right">{{ settings()->currency }}{{ $t_vat_amount }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class=" col-md-4 p-b-2 border-dark">
                            <ul class="list-group mt-2 ">
                                <li class="list-group-item profile-list-group-item">
                                    <span
                                        class="float-left font-weight-bold">{{ __('dashboard.total_delivery_charge') }}</span>
                                    <span class="float-right">{{ settings()->currency }}{{ $t_delivery_charge }}</span>
                                </li>
                                <li class="list-group-item profile-list-group-item">
                                    <span
                                        class="float-left font-weight-bold">{{ __('dashboard.total_cod_amount') }}</span>
                                    <span class="float-right">{{ settings()->currency }}{{ $t_cod_amount }}</span>
                                </li>

                                <li class="list-group-item profile-list-group-item">
                                    <span
                                        class="float-left font-weight-bold">{{ __('dashboard.total_total_delivery_amount') }}</span>
                                    <span class="float-right">{{ settings()->currency }}{{ $t_delivery_amount }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-lg-6  ">
                    <div class="card">
                        <div class="card-body" width="100%" height="200px">
                            <div class="apexcharts" id="apexparcels"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6  ">
                    <div class="card">
                        <div class="card-body" width="100%" height="200px">
                            <div class="apexcharts" id="apexparcelspiechart" style="padding-bottom:120px"></div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="h3">{{ __('dashboard.all_reports') }}</p>
            {{-- Accounts info --}}
            <div class="row merchant-panel header-summery all-reports">
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-hand-holding-usd text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.total_sales_amount') }} </h5>
                                    <p class="h3  text-primary">{{ settings()->currency }}{{ number_format($t_sale, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-hands-helping  text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.total_delivery_fees_paid') }}</h5>
                                    <p class="h3  text-primary">
                                        {{ settings()->currency }}{{ number_format($t_delivery_fee, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-dna text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('levels.total_vat') }}</h5>
                                    <p class="h3 text-primary">{{ settings()->currency }}{{ number_format($ts_vat, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-hockey-puck  text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.net_profit_ammount') }}</h5>
                                    <p class="h3  text-primary">
                                        {{ settings()->currency }}{{ number_format($t_sale - $t_delivery_fee - $ts_vat, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-credit-card text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.current_balance') }}</h5>
                                    <p class="h3 text-primary">
                                        {{ settings()->currency }}{{ number_format($merchant->current_balance, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-donate text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.opening_balance') }}</h5>
                                    <p class="h3 text-primary">
                                        {{ settings()->currency }}{{ number_format($merchant->opening_balance, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-dna text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0 text-left">{{ __('dashboard.vat') }}</h5>
                                    <p class="h3 text-primary">{{ settings()->currency }}{{ $merchant->vat }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-hourglass-half text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.payment_processing') }}</h5>
                                    <p class="h3 text-primary">{{ settings()->currency }}{{ $t_balance_proc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-database text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.paid_amount') }}</h5>
                                    <p class="h3 text-primary">{{ settings()->currency }}{{ $t_balance_paid }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Other info --}}
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-home text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.total_shop') }}</h5>
                                    <p class="h3 text-primary">{{ $t_shop }}</p>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-boxes text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.total_parcel_bank_items') }}</h5>
                                    <p class="h3 text-primary">{{ $t_parcel_bank }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body p-3 text-right">
                            <div class="d-flex justify-content-between">
                                <p class="h3"><i class="fa fa-history text-primary"></i></p>
                                <div>
                                    <h5 class=" text-primary m-0">{{ __('dashboard.total_payment_request') }}</h5>
                                    <p class="h3 text-primary">{{ settings()->currency }}{{ $t_request }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- end wrapper  -->
@endsection()

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
@push('scripts')
    <script type="text/javascript" src="{{ static_asset('backend/js/charts/apexcharts.js') }}"></script>
    @include('backend.merchant_panel.dashboard-chart')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}">
    </script>
@endpush
