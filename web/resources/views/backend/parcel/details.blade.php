@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}    {{ __('levels.view') }}
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
                            <li class="breadcrumb-item"><a href="{{route('parcel.index')}}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('levels.details')}}</a></li>
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
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('invoice.invoice') }} : #{{ @$parcel->invoice_no }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.cash_on_delivery') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table    table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.delivery_fee')}}</td>
                                            <td>{{ settings()->currency }} {{@number_format(($parcel->total_delivery_amount - $parcel->cod_amount),2)}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.cod')}}</td>
                                            <td>{{$parcel->cod_amount}}</td>
                                        </tr>
                                        <tr>
                                            <td  ><strong>{{__('levels.total_cost')}}</strong></td>
                                            <td  ><strong>{{ settings()->currency }} {{@$parcel->total_delivery_amount}}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.delivery_info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table  table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.delivery_type')}}</td>
                                            <td>
                                                {{ @$parcel->delivery_type_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.weight')}}</td>
                                            <td>{{@$parcel->weight}} {{@$parcel->deliveryCategory->title}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.amount_to_collect')}}</td>
                                            <td>{{@$parcel->cash_collection}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.sender_info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table    table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.business_name')}}</td>
                                            <td>
                                                {{ @$parcel->merchant->business_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.mobile')}}</td>
                                            <td> {{@$parcel->merchant->user->mobile}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.email')}}</td>
                                            <td>{{@$parcel->merchant->user->email}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.recipient_info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.name')}}</td>
                                            <td>
                                                {{ @$parcel->customer_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.phone')}}</td>
                                            <td> {{@$parcel->customer_phone}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.address')}}</td>
                                            <td>{{@$parcel->customer_address}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- nd wrapper  -->
@endsection()


