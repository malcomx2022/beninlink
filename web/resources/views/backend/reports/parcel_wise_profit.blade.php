@extends('backend.partials.master')
@section('title')
   {{ __('reports.parcel_wise_profit') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('reports.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('parcel.reports') }}" class="breadcrumb-link">{{ __('reports.parcel_wise_profit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('parcel.wise.profit.reports')}}"  method="GET">
                    
                        <div class="row">
                            <div class="form-group col-12 col-xl-2 col-lg-4 col-md-4 col-sm-6 ">
                                <label for="parcel_date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="parcel_date" class="form-control date_range_picker" value="{{ old('parcel_date',$request->parcel_date) }}">
                                @error('parcel_date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group  col-12  col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <label for="parcelMerchantid">{{ __('parcel.merchant') }}</label>
                                <select style="width: 100%" id="parcelMerchantid"  name="parcel_merchant_id" class="form-control @error('parcel_merchant_id') is-invalid @enderror" data-url="{{ route('parcel.merchant.shops') }}">
                                    <option value=""> {{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                </select>
                                @error('parcel_merchant_id')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12  col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <label for="parcelhub">{{ __('parcel.hub') }}</label>
                                <select style="width: 100%" id="parcelhub"  name="hub_id" class="form-control  "  >
                                    <option value="" selected> {{ __('menus.select') }} {{ __('hub.title') }}</option>
                                    @foreach ($hubs as  $hub)
                                    <option value="{{ $hub->id }}" @if(old('hub_id',$hub->id) == $request->hub_id) selected @endif>{{ $hub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-12 col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <label for="parcel_profit">{{ __('reports.parcel_tracking_id') }}</label>
                                <select style="width: 100%" id="parcel_profit"   name="parcel_tracking_id[]" class="form-control @error('parcel_tracking_id') is-invalid @enderror" multiple="multiple" >
                                </select>
                                @error('parcel_tracking_id')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12  col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <div class="form-group d-inline-block  pt-1">
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pl-0 pt-4 d-flex justify-content pl-0">
                                        <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('parcel.reports') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @if(isset($print))
            <div class="card">
                <div class="card-header">
                    <div class="row  ">
                        <div class="col-lg-6">
                            <h3>{{ __('reports.parcel_wise_profit') }}</h3>
                        </div>
                        <div class="col-lg-6 text-right pr-2">
                            @if(!blank($parcel_ids))
                            <button type="button" id="exportTable" data-title="Parcel Wise Profit" data-filename="ParcelWiseProfit" class="btn btn-success">{{ __('menus.export') }}</button>
                            <a href="{{ route('parcel.wise.profit.print.page',$parcel_ids) }}" class="btn btn-primary" target="_blank">{{ __('reports.print') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive"  >
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('###') }}</th>
                                    <th>{{ __('reports.details') }}</th>
                                    <th>{{ __('reports.income') }}</th>
                                    <th>{{ __('reports.expense') }}</th>
                                    <th>{{ __('reports.profit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                    @foreach($parcels as $key=>$parcel)
                                    <tr>
                                        <td>
                                            {{ $i++ }}
                                        </td>
                                        <td >
                                            <div class="width300px">
                                                <b>Tracking Id :</b> <a class="active" href="{{ route('parcel.details',$parcel->id) }}" target="_blank">{{ $parcel->tracking_id }}</a><br/>
                                                 <span><b>Merchant :</b>  {{$parcel->merchant->business_name}}</span><br>
                                                 <span><b>Customer :</b>  {{$parcel->customer_name}}</span><br>
                                            </div>
                                        </td>
                                        <td> {{ settings()->currency }} {{ $parcel->total_delivery_amount }}</td>
                                        <td> {{ settings()->currency }} {{ parcelExpense($parcel->id) }}</td>
                                        <td>{{ settings()->currency }} {{  ($parcel->total_delivery_amount - parcelExpense($parcel->id)) }}    </td>
                                    </tr>
                                    @endforeach
                                    <tr class="totalCalculationHead bg-primary text-white "  >
                                        <td ></td>
                                        <td class="text-white font-weight-bold">{{ __('reports.total') }}    : </td>
                                        <td class="text-white font-weight-bold"> {{ settings()->currency }}  {{ $parcels->sum('total_delivery_amount') }} </td>
                                        <td class="text-white font-weight-bold"> {{ settings()->currency }}  {{ parcelExpenseTotal($parcels->pluck('id')) }} </td>
                                        <td class="text-white font-weight-bold">  {{ settings()->currency }}  {{ ($parcels->sum('total_delivery_amount') - parcelExpenseTotal($parcels->pluck('id')) ) }}</td>
                                    </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

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

