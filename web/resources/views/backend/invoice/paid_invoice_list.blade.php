@extends('backend.partials.master')
@section('title')
{{ __('invoice.paid_invoice') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('invoice.paid_invoice') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">  {{ __('invoice.paid_invoice') }} {{ __('levels.list') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                          <a href="#" class="nav-link @if($request->paid_invoice_page ) active show @elseif($request->processing_invoice_page || $request->unpaid_invoice_page) @else active @endif" id="paid-invoice-tab" data-toggle="tab" data-target="#paid-invoice" type="button" role="tab" aria-controls="paid-invoice" aria-selected="true">{{ __('invoice.'.\App\Enums\InvoiceStatus::PAID) }}</a>
                          <a href="#" class="nav-link @if($request->processing_invoice_page) active show  @endif" id="processing-invoice-tab" data-toggle="tab" data-target="#processing-invoice" type="button" role="tab" aria-controls="processing-invoice" aria-selected="false">{{ __('invoice.'.\App\Enums\InvoiceStatus::PROCESSING) }}</a>
                          <a href="#" class="nav-link  @if($request->unpaid_invoice_page) active show  @endif" id="unpaid-invoice-tab" data-toggle="tab" data-target="#unpaid-invoice" type="button" role="tab" aria-controls="unpaid-invoice" aria-selected="false">{{ __('invoice.'.\App\Enums\InvoiceStatus::UNPAID) }}</a>
                        </div>
                      </nav>
                      <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade   @if($request->paid_invoice_page) active show @elseif($request->processing_invoice_page || $request->unpaid_invoice_page) @else active show @endif" id="paid-invoice" role="tabpanel" aria-labelledby="paid-invoice-tab">
                            <div class="table-responsive">
                                <table class="table   " style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{{  __('levels.id') }}</th>
                                            <th>{{ __('merchant.title')}}</th>
                                            <th>{{ __('menus.invoice') .' '. __('invoice.id')}}</th>
                                            <th>{{ __('menus.invoice') . ' '. __('levels.date')}}</th>
                                            <th>{{ __('parcel.cash_collection')}}</th>
                                            <th>{{ __('parcel.Total_Charge')}}</th>
                                            <th>{{ __('parcel.current_payable')}}</th>
                                            <th>{{ __('parcel.status')}}</th>
                                            <th>{{ __('levels.actions')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $i=0;
                                        @endphp
                                        @foreach ($invoices as $invoice)
                                            <tr>
                                                <td>{{++$i}}</td>
                                                <td>{{@$invoice->merchant->business_name}}</td>
                                                <td>{{@$invoice->invoice_id}}</td>
                                                <td>{{@$invoice->invoice_date}}</td>
                                                <td>{{ settings()->currency }}{{@$invoice->cash_collection}}</td>
                                                <td>{{ settings()->currency }}{{@$invoice->total_charge}}</td>
                                                <td>{{ settings()->currency }}{{@$invoice->current_payable}}</td>
                                                <td>{!! $invoice->my_status !!}</td>
                                                <td>
                                                    <a href="{{ route('merchant.invoice.details',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-sm btn-primary mt-1"><i class="fa fa-eye"></i> View</a>
                                                    <a href="{{ route('merchant.invoice.csv',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-sm btn-success mt-1"><i class="fa fa-download"></i> CSV</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-3 d-flex flex-row-reverse align-items-center">
                                <span>{{ $invoices->appends(['paid_invoice_page'=>$request->paid_invoice_page])->links() }}</span>
                                <p class="p-2 small">
                                    {!! __('Showing') !!}
                                    <span class="font-medium">{{ $invoices->firstItem() }}</span>
                                    {!! __('to') !!}
                                    <span class="font-medium">{{ $invoices->lastItem() }}</span>
                                    {!! __('of') !!}
                                    <span class="font-medium">{{ $invoices->total() }}</span>
                                    {!! __('results') !!}
                                </p>
                            </div>
                        </div>
                        <div class="tab-pane fade @if($request->processing_invoice_page) active show  @endif" id="processing-invoice" role="tabpanel" aria-labelledby="processing-invoice-tab">
                                <div class="table-responsive">
                                    <table class="table   " style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>{{  __('levels.id') }}</th>
                                                <th>{{ __('merchant.title')}}</th>
                                                <th>{{ __('menus.invoice') .' '. __('invoice.id')}}</th>
                                                <th>{{ __('menus.invoice') . ' '. __('levels.date')}}</th>
                                                <th>{{ __('parcel.cash_collection')}}</th>
                                                <th>{{ __('parcel.Total_Charge')}}</th>
                                                <th>{{ __('parcel.current_payable')}}</th>
                                                <th>{{ __('parcel.status')}}</th>
                                                <th>{{ __('levels.actions')}}</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $i=0;
                                            @endphp
                                            @foreach ($processInvoices as $invoice)
                                                <tr>
                                                    <td>{{++$i}}</td>
                                                    <td>{{@$invoice->merchant->business_name}}</td>
                                                    <td>{{@$invoice->invoice_id}}</td>
                                                    <td>{{@$invoice->invoice_date}}</td>
                                                    <td>{{ settings()->currency }}{{@$invoice->cash_collection}}</td>
                                                    <td>{{ settings()->currency }}{{@$invoice->total_charge}}</td>
                                                    <td>{{ settings()->currency }}{{@$invoice->current_payable}}</td>
                                                    <td>{!! $invoice->my_status !!}</td>
                                                    <td>
                                                        <a href="{{ route('merchant.invoice.details',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-sm btn-primary mt-1"><i class="fa fa-eye"></i> View</a>
                                                        <a href="{{ route('merchant.invoice.csv',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-sm btn-success mt-1"><i class="fa fa-download"></i> CSV</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-3 d-flex flex-row-reverse align-items-center">
                                    <span>{{ $processInvoices->appends(['processing_invoice_page'=>$request->processing_invoice_page])->links() }}</span>
                                    <p class="p-2 small">
                                        {!! __('Showing') !!}
                                        <span class="font-medium">{{ $processInvoices->firstItem() }}</span>
                                        {!! __('to') !!}
                                        <span class="font-medium">{{ $processInvoices->lastItem() }}</span>
                                        {!! __('of') !!}
                                        <span class="font-medium">{{ $processInvoices->total() }}</span>
                                        {!! __('results') !!}
                                    </p>
                                </div>
                        </div>
                        <div class="tab-pane fade @if($request->unpaid_invoice_page) active show  @endif" id="unpaid-invoice" role="tabpanel" aria-labelledby="unpaid-invoice-tab">
                            <div class="table-responsive">
                                <table class="table   " style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{{  __('levels.id') }}</th>
                                            <th>{{ __('merchant.title')}}</th>
                                            <th>{{ __('menus.invoice') .' '. __('invoice.id')}}</th>
                                            <th>{{ __('menus.invoice') . ' '. __('levels.date')}}</th>
                                            <th>{{ __('parcel.cash_collection')}}</th>
                                            <th>{{ __('parcel.Total_Charge')}}</th>
                                            <th>{{ __('parcel.current_payable')}}</th>
                                            <th>{{ __('parcel.status')}}</th>
                                            <th>{{ __('levels.actions')}}</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $i=0;
                                        @endphp
                                        @foreach ($unpaidInvoices as $invoice)
                                            <tr>
                                                <td>{{++$i}}</td>
                                                <td>{{@$invoice->merchant->business_name}}</td>
                                                <td>{{@$invoice->invoice_id}}</td>
                                                <td>{{@$invoice->invoice_date}}</td>
                                                <td>{{ settings()->currency }}{{@$invoice->cash_collection}}</td>
                                                <td>{{ settings()->currency }}{{@$invoice->total_charge}}</td>
                                                <td>{{ settings()->currency }}{{@$invoice->current_payable}}</td>
                                                <td>{!! $invoice->my_status !!}</td>
                                                <td>

                                                    <a href="{{ route('merchant.invoice.details',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-sm btn-primary mt-1"><i class="fa fa-eye"></i> View</a>
                                                    <a href="{{ route('merchant.invoice.csv',[$invoice->merchant_id,$invoice->invoice_id]) }}" class="btn btn-sm btn-success mt-1"><i class="fa fa-download"></i> CSV</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-3 d-flex flex-row-reverse align-items-center">
                                <span>{{ $unpaidInvoices->appends(['unpaid_invoice_page'=>$request->unpaid_invoice_page])->links() }}</span>
                                <p class="p-2 small">
                                    {!! __('Showing') !!}
                                    <span class="font-medium">{{ $unpaidInvoices->firstItem() }}</span>
                                    {!! __('to') !!}
                                    <span class="font-medium">{{ $unpaidInvoices->lastItem() }}</span>
                                    {!! __('of') !!}
                                    <span class="font-medium">{{ $unpaidInvoices->total() }}</span>
                                    {!! __('results') !!}
                                </p>
                            </div>
                        </div>
                      </div>
                </div>
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
