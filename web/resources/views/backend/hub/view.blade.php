@extends('backend.partials.master')
@section('title')
    {{ __('hub.title') }} {{ __('levels.view') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{url('/')}}" class="breadcrumb-link">{{__('levels.dashboard')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{__('hub.title')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('levels.view')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- sales  -->
        <div class="col-lg-12 p-l-0">
            <form action="{{ route('hub.view',$id) }}" method="get">
                @csrf
                <div class="form-group  col-xl-8  ">
                    <div class="d-flex justify-content-end">
                        <input type="text" autocomplete="off" id="date" name="parcel_date" placeholder="Enter Date" class="w-md-25 form-control date_range_picker group-input" value="{{ old('parcel_date',$request->parcel_date) }}">
                        <button class="btn btn-primary group-btn ml-0" type="submit">{{ __('levels.filter') }}</button>
                        <a href="{{ route('hub.view',$id) }}" class="btn btn-secondary text-white" >{{ __('levels.clear') }}</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-xl-8">
            <div class="row hub-view">
                 <div class="col-6 col-xl-3 col-lg-4 col-md-4">
                    <div class="card border-3 border-top border-top-primary total-card-color  ">
                        <div class="card-body total-card-body">
                            <div class="text-center">
                                <label class="icon">
                                    <i class="fa fa-box-open"></i>
                                </label>
                                <div class="box-content">
                                    <h5 class="text-muted">{{ __('dashboard.total_parcel') }}</h5>
                                    <div class="metric-value d-inline-block">
                                        <h1 class="mb-1">{{ $data['total_parcels'] }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @foreach ($data['parcelsGrouped'] as $key=>$parcels)
                    <div class="col-6 col-xl-3 col-lg-4 col-md-4">
                        <div class="card border-3 border-top border-top-primary total-card-color">
                            <div class="card-body total-card-body">
                                <div class="text-center">
                                    <label class="icon">
                                        <i class="{{ statusIcon($key) }}"></i>
                                    </label>
                                    <div class="box-content">
                                        <h5 class="text-muted">{{ __('parcelStatus.'.$key) }}</h5>
                                        <div class="metric-value d-inline-block">
                                            <h1 class="mb-1"> {{ $parcels->count() }}</h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-xl-4">
            <div class="mb-5">
                <div class=" ">
                    <div class="list-group">
                        <li class="list-group-item profile-list-group-item">
                            <h3 class="text-center m-0"><span class="font-weight-bold">{{ __('reports.total') }}</span></h3>
                        </li>
                        <li class="list-group-item profile-list-group-item">
                            <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                            <span class="float-right">{{ __('levels.amount') }}</span>
                        </li>
                        <li class="list-group-item profile-list-group-item">
                            <span class="float-left font-weight-bold">{{ __('levels.cash_collection') }}</span>
                            <span class="float-right">{{ settings()->currency }} {{ number_format($data['total_cash_collection'],2) }}</span>
                        </li>
                        <li class="list-group-item profile-list-group-item">
                            <span class="float-left font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERED) }}  {{ __('levels.cash_collection') }} </span>
                            <span class="float-right">{{ settings()->currency }} {{ number_format($data['total_delivered_cash_collection'],2) }}</span>
                        </li>
                        <li class="list-group-item profile-list-group-item">
                            <span class="float-left font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::PARTIAL_DELIVERED) }}  {{ __('levels.cash_collection') }} </span>
                            <span class="float-right">{{ settings()->currency }} {{ number_format($data['total_partials_delivered_cash_collection'],2) }}</span>
                        </li>
                        <li class="list-group-item profile-list-group-item">
                            <span class="float-left font-weight-bold">{{ __('levels.delivery_charge') }}</span>
                            <span class="float-right">{{ settings()->currency }} {{ number_format($data['total_delivery_charges'],2) }}</span>
                        </li>
                        <li class="list-group-item profile-list-group-item">
                            <span class="float-left font-weight-bold">{{ __('levels.vat_amount') }}</span>
                            <span class="float-right">{{ settings()->currency }} {{ number_format($data['total_vat_amount'],2) }}</span>
                        </li>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="row">
        <!-- data table  -->
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('levels.view') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table" class="table  parcelTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.track_id') }}</th>
                                    <th>{{ __('parcel.recipient_info') }}</th>
                                    <th>{{ __('parcel.merchant') }}</th>
                                    <th>{{ __('levels.amount')}}</th>
                                    <th>{{ __('parcel.status') }}</th>
                                    <th>{{ __()}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=1;
                                @endphp
                                @foreach($data['parcels'] as $parcel)
                                <tr>
                                    <td> {{__('levels.track_id')}}: <span class="active">{{ $parcel->tracking_id }}</td>
                                    <td class="merchantpayment">
                                        <div class="w150">
                                            <div class="d-flex">
                                                <i class="fa fa-user"></i>&nbsp;<p>{{$parcel->customer_name}}</p>
                                            </div>
                                            <div class="d-flex">
                                                <i class="fas fa-phone"></i>&nbsp;<p>{{$parcel->customer_phone}}</p>
                                            </div>
                                            <div class="d-flex">
                                                <i class="fas fa-map-marker-alt"></i>&nbsp;<p>{{$parcel->customer_address}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="merchantpayment">
                                        <p>{{$parcel->merchant->business_name}}</p>
                                        <p>{{$parcel->merchant->user->mobile}}</p>
                                        <p>{{$parcel->merchant->address}}</p>
                                    </td>
                                    <td>
                                        <div class="w250">
                                                {{__('levels.cod')}}: <span class="text-dark">{{settings()->currency}}{{$parcel->cash_collection}}</span>
                                            <br>
                                                {{__('levels.total_delivery_amount')}}: <span class="text-dark">{{settings()->currency}}{{$parcel->total_delivery_amount}}</span>
                                            <br>
                                                {{__('levels.vat_amount')}}: <span class="text-dark">{{settings()->currency}}{{$parcel->vat_amount}}</span>
                                            <br>
                                                {{__('levels.current_payable')}}: <b>{{settings()->currency}}{{$parcel->current_payable}}</b>
                                            <br>
                                        </div>
                                    </td>
                                    <td>{!! $parcel->parcel_status !!} <br>
                                    @if($parcel->partial_delivered && $parcel->status != \App\Enums\ParcelStatus::PARTIAL_DELIVERED)
                                        <span class="badge badge-pill badge-success mt-2">{{trans("parcelStatus." . \App\Enums\ParcelStatus::PARTIAL_DELIVERED)}}</span>
                                    @endif
                                    <br/>
                                    <span>{{__('parcel.updated_on')}}: {{\Carbon\Carbon::parse($parcel->updated_at)->format('Y-m-d h:i:s A')}}</span>
                                    </td>

                                    <td>
                                        @php
                                            if($parcel->admin_parcel_invoice !==null && $parcel->admin_parcel_invoice->status == App\Enums\InvoiceStatus::PAID):
                                                $status  = $parcel->admin_parcel_invoice->status;
                                            elseif($parcel->admin_parcel_invoice !==null && $parcel->admin_parcel_invoice->status == App\Enums\InvoiceStatus::UNPAID):
                                                $status  = App\Enums\InvoiceStatus::UNPAID;
                                            elseif($parcel->admin_parcel_invoice !==null):
                                                if($parcel->status == App\Enums\ParcelStatus::DELIVERED || $parcel->status == App\Enums\ParcelStatus::PARTIAL_DELIVERED):
                                                    $status  = App\Enums\InvoiceStatus::PROCESSING;
                                                else:
                                                    $status  = App\Enums\InvoiceStatus::UNPAID;
                                                endif;
                                            else:
                                                $status  = App\Enums\InvoiceStatus::UNPAID;
                                            endif;
                                        @endphp
                                        <p>{{ __('invoice.'.$status) }}</p>
                                        <span>
                                            {{ @$parcel->admin_parcel_invoice->invoice_id }}<br/>
                                            @if ($parcel->admin_parcel_invoice !==null && $parcel->admin_parcel_invoice->status == App\Enums\InvoiceStatus::PAID)
                                                Paid At: {{ @dateFormat($parcel->admin_parcel_invoice->updated_at) }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <span>{{ $data['parcels']->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $data['parcels']->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $data['parcels']->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $data['parcels']->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->


<script src="{{static_asset('backend')}}/vendor/jquery/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>

<script type="text/javascript">
        "use strict";
        $(document).ready(function() {
                    function cb(start, end) {
                        $('.date_range_picker').val(start.format('MM/DD/YYYY') + ' To ' + end.format('MM/DD/YYYY'));
                    }

                    $('.date_range_picker').daterangepicker({
                        startDate: moment(),
                        endDate: moment(),
                        autoUpdateInput: false,
                        locale: {
                            format: 'MM/DD/YYYY',
                            separator: " To ",
                            cancelLabel: 'Clear'
                        },
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, cb);

                    $('.date_range_picker').on('apply.daterangepicker', function(ev, picker) {
                        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' To ' + picker.endDate.format('MM/DD/YYYY'));
                    });

                    $('.date_range_picker').on('cancel.daterangepicker', function (ev, picker) {
                        $(this).val('');
                    });


                });

</script>
@endsection
<!-- css  -->
@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush


