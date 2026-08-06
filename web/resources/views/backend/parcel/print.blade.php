@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('parcel.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('parcel.index') }}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">Print</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="card" >
        <div class="card-body" >
            <div class="invoice" id="printablediv">
                <div class="row mt-3">
                    <div class="col-sm-6">
                        <h3>
                            {{ __('invoice.invoice') }} # <small>{{ $parcel->invoice_no }}</small>
                        </h3>
                    </div>
                    <div class="col-sm-6">
                        <h5 class="float-sm-right">Date: {{ dateFormat($parcel->created_at)}}</h5>
                    </div>
                </div>
                <hr>
                <div class="row invoice-info">
                    <div class="col-sm-4 invoice-col" style="float:left;">
                        From  <address class="font-weight-light">
                            <strong>{{ $parcel->merchant->business_name }}</strong><br>
                            {{ $parcel->merchant->merchant_unique_id }}<br>
                            Phone: {{ $parcel->merchant->user->mobile }} <br>
                            Email: {{ $parcel->merchant->user->email }}
                        </address>
                    </div>
                    <div class="col-sm-4 invoice-col" style="float:left;">
                        To
                        <address class="font-weight-light">
                            <strong>{{ $parcel->customer_name}}</strong><br>
                            Phone: {{ $parcel->customer_phone }}<br>
                            Address: {{ $parcel->customer_address }}
                        </address>
                    </div>

                    <div class="col-sm-4 font-weight-light" style="float:right;">
                        <b>Track ID:</b> {{ $parcel->tracking_id }}<br>
                        <b>Delivery Type:</b> {{ $parcel->delivery_type_name }}<br>
                        <b>Pickup Date:</b> {{ dateFormat($parcel->pickup_date)}}<br>
                        <b>Delivery Date:</b> {{ dateFormat($parcel->delivery_date) }}
                    </div>
                </div>

                <div class="row" style="margin-top: 20px">
                    <div class="col-sm-12 table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Weight</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td data-label="#">1</td>
                                <td data-label="Category">{{ $parcel->deliveryCategory->title }}</td>
                                <td data-label="Weight">{{ $parcel->weight }}</td>
                                <td data-label="Qty">1</td>
                                <td data-label="Total">{{ $parcel->cash_collection }}</td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="4"><span class="pull-right"><b>Delivery Amount</b></span></td>
                                <td><b>{{$parcel->total_delivery_amount}}</b></td>
                            </tr>
                            <tr>
                                <td colspan="4"><span class="pull-right"><b>Current Payable</b></span></td>
                                <td><b>{{$parcel->current_payable}}</b></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <hr>
            </div>
            <div class="row no-print">
                <div class="col-sm-12">
                    <div class="float-sm-right">
                        <button class="btn btn-primary m-1" onclick="printDiv('printablediv')"><i class="fa fa-download"></i>Print</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

@push('scripts')
    <script src="{{ static_asset('backend/js/parcel/print.js') }}"></script>
@endpush
