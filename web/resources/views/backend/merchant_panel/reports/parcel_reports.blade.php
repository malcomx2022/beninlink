@extends('backend.partials.master')
@section('title')
    {{ __('reports.title') }} {{ __('reports.parcel_reports') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('dashboard.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('reports.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant-panel.parcel.reports') }}" class="breadcrumb-link">{{ __('reports.parcel_reports') }}</a></li>
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
                    <form action="{{route('merchant-panel.parcel.filter.reports')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-md-4 col-sm-6">
                                <label for="parcel_date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="parcel_date" class="form-control date_range_picker" value="{{ old('parcel_date',$request->parcel_date) }}">
                                @error('parcel_date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-4  col-sm-6 marchantStatusReportsSelect">
                                <label for="parcelStatus">{{ __('parcel.status') }}</label>
                                <select style="width: 100%" id="parcelStatus"  name="parcel_status[]" class="form-control @error('parcel_status') is-invalid @enderror" multiple="multiple" >
                                    @foreach (trans('parcelStatusFilter') as $key => $status)
                                        <option value="{{ $key}}"   @if($request->parcel_status !== null && in_array($key,$request->parcel_status))  selected @endif>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('parcel_status')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12 col-md-4  col-sm-6">
                                <div class="form-group d-inline-block  pt-1">
                                    <div class="col-xl-12 col-lg-12 col-md-12 pl-0 col-sm-12 col-12 pt-4 d-flex justify-content">
                                        <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('merchant-panel.parcel.reports') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
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
                    <div class="row px-4">
                        <div class="col-md-6">
                            <h3>{{ __('reports.parcel_reports') }}</h3>
                        </div>
                        <div class="col-md-6 text-right pr-2">
                            @if(!blank($parcel_ids))
                                <button type="button" id="exportTable" data-title="Parcel Status Reports" data-filename="ParcelStatusReports" class="btn btn-success">{{ __('menus.export') }}</button>
                                <a href="{{ route('merchant-panel.parcel.reports.print.page',$parcel_ids) }}" class="btn btn-primary" target="_blank">{{ __('reports.print') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:unset">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('###') }}</th>
                                    <th>{{ __('parcel.status') }}</th>
                                    <th>{{ __('reports.count') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($parcels as $key=>$parcel)
                                    <tr>
                                        <th>  {{ $i++ }} </th>
                                        <td>{!! StatusParcel($key)   !!}</td>
                                        <td>{{ $parcel->count() }}</td>
                                    </tr>
                                @endforeach
                                <tr class="totalCalculationHead bg-primary"  >
                                    <td> </td>
                                    <td> <span class="text-dark">{{ __('reports.total_cash_collection') }}</span></td>
                                    <td> {{ settings()->currency }} {{ totalParcelsCashcollection($parcels) }}  </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
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

    .select2-selection.select2-selection--multiple{
        border-radius: 30px!important;
        border: 1px solid #d2d2e4!important;
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



