@extends('backend.partials.master')
@section('title')
{{ __('menus.invoice') }} {{ __('levels.details') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('menus.invoice') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ @$invoice->invoice_id }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row  ">
                        <div class="col-12 ">
                            <div class="d-flex justify-content-end mt-2 mt-md-0">
 
                                <a href="{{ route('merchant.panel.invoice.csv',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-success btn-sm " data-toggle="tooltip" data-placement="top" title="Add"><i class="fa fa-download"></i> CSV</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    
                                    <th>{{  __('levels.id') }}</th>
                                    <th>{{ __('menus.date') }}</th>
                                    <th>{{ __('invoice.invoice') }}</th>
                                    <th>{{ __('levels.track_id') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('parcel.customer_info')}}</th>

                                    <th>{{ __('parcel.cash_collection')}}</th>
                                    <th>{{ __('parcel.delivery_charge')}}</th> 
                                    <th>{{ __('Return Charge')}}</th>
                                    <th>{{ __('parcel.cod_charges')}}</th>
                                    <th>{{ __('parcel.vat')}}</th>
                                    <th>{{ __('parcel.Total_Charge')}}</th>
                                    <th>{{ __('invoice.paid_out')}}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach ($invoiceParcels as $invoiceParcel)
                                    <tr>
                                        <td>{{++$i}}</td>
                                        <td>{{\Carbon\Carbon::parse($invoiceParcel->updated_at)->format('d-m-Y')}}</td>
                                        <td>{{@$invoiceParcel->parcel->invoice_no}}</td>
                                        <td>{{@$invoiceParcel->parcel->tracking_id}}</td>
                                        <td> 
                                            @if( $invoiceParcel->parcel_status == \App\Enums\ParcelStatus::RETURN_TO_COURIER )
                                                <span class="badge badge-pill badge-info mb-2">{{ trans("parcelStatus.24")}}</span> <br/>
                                            @endif
                                            @if($invoiceParcel->parcel->partial_delivered == \App\Enums\BooleanStatus::YES)   
                                                <span class="badge badge-pill badge-success mt-2">{{ trans("parcelStatus.".\App\Enums\ParcelStatus::PARTIAL_DELIVERED )}}</span>
                                            @else
                                                @if( $invoiceParcel->parcel->status != \App\Enums\ParcelStatus::RETURN_TO_COURIER )
                                                    {!! @$invoiceParcel->parcel->parcel_status !!}
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <span>{{@$invoiceParcel->parcel->customer_name}}</span><br/>
                                            <span>{{ @$invoiceParcel->parcel->customer_phone }}</span>
                                        </td> 
                                        <td>{{ settings()->currency }}{{@$invoiceParcel->collected_amount}}  </td>
                                        <td>{{ settings()->currency }}{{@$invoiceParcel->total_delivery_amount}}  </td>
                                        <td>{{ settings()->currency }}{{@$invoiceParcel->return_charge}}  </td>
                                        <td>{{ settings()->currency }}{{@$invoiceParcel->cod_amount}} </td>
                                        <td>{{ settings()->currency }}{{@$invoiceParcel->vat_amount}} </td>
                                        <td>{{ settings()->currency }}{{ @$invoiceParcel->total_charge_amount }}</td>
                                        <td>{{ settings()->currency }}{{ @$invoiceParcel->current_payable }}</td>

                                    </tr>
                                @endforeach
                                <tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <span>{{ $invoiceParcels->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $invoiceParcels->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $invoiceParcels->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $invoiceParcels->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
