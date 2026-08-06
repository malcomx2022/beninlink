@extends('backend.partials.master')
@section('title')
    {{ __('parcel.parcel_bank') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.parcel-bank.index') }}" class="breadcrumb-link">{{ __('parcel.parcel_bank') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <p class="h3">{{ __('parcel.parcel_bank') }} {{ __('levels.list') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('###') }}</th>
                                    <th>{{ __('parcel.tracking_id') }}</th>
                                    <th>{{ __('parcel.recipient_info') }}</th>
                                    <th>{{ __('parcel.amount')}}</th>
                                    <th>{{ __('parcel.status') }}</th>
                                    <th>{{ __('parcel.payment')}}</th>
                                    <th>{{ __('levels.actions')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=0; @endphp
                                @foreach($parcels as $parcel)
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{ $parcel->tracking_id }}</td>
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
                                     <td>
                                        <p>{!! $parcel->parcel_status !!}</p>
                                        <span>{{__('parcel.updated_on')}}: {{\Carbon\Carbon::parse($parcel->updated_at)->format('Y-m-d h:i:s A')}}</span>
                                    </td>
                                    <td>
                                        @php
                                            if($parcel->parcel_invoice !==null && $parcel->parcel_invoice->status == App\Enums\InvoiceStatus::PAID):
                                                $status  = $parcel->parcel_invoice->status;
                                            elseif($parcel->parcel_invoice !==null && $parcel->parcel_invoice->status == App\Enums\InvoiceStatus::UNPAID):
                                                $status  = App\Enums\InvoiceStatus::UNPAID;
                                            elseif($parcel->parcel_invoice !==null):
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
                                            {{ @$parcel->parcel_invoice->invoice_id }}<br/>
                                            @if ($parcel->parcel_invoice !==null && $parcel->parcel_invoice->status == App\Enums\InvoiceStatus::PAID)
                                                Paid At: {{ @dateFormat($parcel->parcel_invoice->updated_at) }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                <a href="{{ route('merchant-parcel.clone',$parcel->id) }}" class="dropdown-item"><i class="fas fa-clone" aria-hidden="true"></i> {{__('levels.clone')}}</a>
                                                @if ($parcel->status == App\Enums\ParcelStatus::PENDING)
                                                    <a href="{{route('merchant-panel.parcel.edit',$parcel->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{__('levels.edit')}}</a>
                                                    <form id="delete" value="Test" action="{{route('merchant-panel.parcel.delete',$parcel->id)}}" method="POST" data-title="{{ __('delete.parcel') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="Parcel" id="deleteTitle">
                                                        <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $parcels->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $parcels->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $parcels->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $parcels->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
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
@endpush
<!-- js  -->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
    <script>
        var dateParcel = '{{ $request->parcel_date }}';
    </script>
    <script src="{{ static_asset('backend/js/merchant_panel/parcel/filter.js') }}"></script>
@endpush


