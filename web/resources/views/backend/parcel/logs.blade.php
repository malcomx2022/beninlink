@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}    {{ __('levels.logs') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('levels.logs')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <section class="mt-5 pt-5">
        <div class="row">
            <div class='col-xl-8'>
                <ul class="progressbar">
                    <li class="@if($parcel->status >= \App\Enums\ParcelStatus::PENDING  ) active @endif">
                        <i class="fas fa-hourglass-start"></i>
                        <i class="fa fa-check"></i>
                        <label>{{ __('parcel.pending') }}</label>
                    </li>
                    <li class="@if($parcel->status >= \App\Enums\ParcelStatus::PICKUP_ASSIGN ||
                    $parcel->status >= \App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE ) active @endif" >
                        <i class="fas fa-truck-loading"></i>
                        <i class="fa fa-check"></i>
                        <label>{{ __('parcel.in_progress') }}</label>
                    </li>
                    <li class="@if($parcel->status >= \App\Enums\ParcelStatus::RECEIVED_WAREHOUSE  ) active @endif"  >
                         <i class="fas fa-warehouse"></i>
                        <i class="fa fa-check"></i>
                        <label>{{ __('parcel.warehouse') }}</label>
                    </li>
                    <li  class="@if($parcel->status >= \App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN || $parcel->status >= \App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE ) active @endif" >
                        <i class="fa fa-truck"></i>
                        <i class="fa fa-check"></i>
                        <label>{{ __('parcel.deliveryman_assigned') }}</label>
                    </li>
                    <li class="@if($parcel->status >= \App\Enums\ParcelStatus::DELIVERED || $parcel->status >= \App\Enums\ParcelStatus::PARTIAL_DELIVERED ) active @endif" >
                        @if($parcel->status >= \App\Enums\ParcelStatus::RETURN_TO_COURIER && $parcel->status != \App\Enums\ParcelStatus::PARTIAL_DELIVERED )
                        <i class="fas fa-undo-alt"></i>
                        @else
                        <i class="fas fa-handshake"></i>
                        @endif
                        <i class="fa fa-check"></i>
                        @if($parcel->status >= \App\Enums\ParcelStatus::RETURN_TO_COURIER )
                        <label>{{ __('parcel.return_courier') }}r</label>
                        @else
                        <label>{{ __('parcel.delivered') }}</label>
                        @endif
                    </li>
                </ul>
            </div>
            <div class="col-xl-4 ">
                <section class="cd-timeline js-cd-timeline">
                    <div class="cd-timeline__container">
                        @foreach ($parcelevents as $log)
                            @switch($log->parcel_status)
                                @case(\App\Enums\ParcelStatus::PICKUP_ASSIGN)
                                    <div class="cd-timeline__block js-cd-block">
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('parcel.pickup_man')}}: {{isset($log->pickupman)? @$log->pickupman->user->name:''}}</span><br>
                                            <span>{{__('levels.mobile')}}: {{isset($log->pickupman)? @$log->pickupman->user->mobile:''}}</span><br>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div class="">
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('parcel.pickup_man')}}: {{isset($log->pickupman)? @$log->pickupman->user->name:''}}</span><br>
                                            <span>{{__('levels.mobile')}}: {{isset($log->pickupman)? @$log->pickupman->user->mobile:''}}</span><br>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::RECEIVED_BY_PICKUP_MAN)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('parcelLogs.hub_name')}}: {{@$log->hub->name}}</span><br>
                                            <span>{{__('levels.mobile')}}: {{@$log->hub->phone}}</span><br/>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::TRANSFER_TO_HUB)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('parcelLogs.hub_name')}}: {{@$log->hub->name}}</span><br>
                                            <span>{{__('parcelLogs.hub_phone')}}: {{@$log->hub->phone}}</span><br/>
                                            <span>{{__('parcelLogs.delivery_man')}}: {{ isset($log->transferDeliveryman) ? @$log->transferDeliveryman->user->name:''}}</span><br/>
                                            <span>{{__('parcelLogs.delivery_man_phone')}}: {{ isset($log->transferDeliveryman) ? @$log->transferDeliveryman->user->mobile:''}}</span><br/>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('parcelLogs.delivery_man')}}: {{isset($log->deliveryMan)? @$log->deliveryMan->user->name:''}}</span><br>
                                            <span>{{__('levels.phone')}}: {{isset($log->deliveryMan)? @$log->deliveryMan->user->mobile:''}}</span><br/>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('parcelLogs.delivery_man')}}: {{isset($log->deliveryMan)? @$log->deliveryMan->user->name:''}}</span><br>
                                            <span>{{__('levels.phone')}}: {{isset($log->deliveryMan)? @$log->deliveryMan->user->mobile:''}}</span><br/>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::DELIVERED)
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::PARTIAL_DELIVERED)
                                    <div class="active cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @case(\App\Enums\ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE)
                                    <div class=" active cd-timeline__block js-cd-block">
                                    <!-- cd-timeline__img -->
                                    <div class="cd-timeline__content js-cd-content">
                                        <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                        <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                        <div >
                                            <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                            <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                        </div>
                                    </div>
                                    <!-- cd-timeline__content -->
                                    </div>
                                @break
                                @default
                                    <div class="cd-timeline__block js-cd-block">
                                        <!-- cd-timeline__img -->
                                        <div class="active cd-timeline__content js-cd-content">
                                            <strong>{{__('parcelLogs.'.@$log->parcel_status)}}</strong><br>
                                            <span>{{__('levels.note')}}: {{@$log->note}}</span><br/>
                                            <div >
                                                <strong>{!! @dateFormat($log->created_at) !!}</strong><br>
                                                <small>{!! @date('h:i a', strtotime($log->created_at)) !!}</small>
                                            </div>
                                        </div>
                                        <!-- cd-timeline__content -->
                                    </div>
                            @endswitch
                        @endforeach

                        <div class="cd-timeline__block js-cd-block">
                            <!-- cd-timeline__img -->
                            <div class="active cd-timeline__content js-cd-content">
                                <strong>{{__('parcel.parcel_create')}}</strong><br>
                                <span>{{__('levels.name')}}: {{@$parcel->merchant->user->name}}</span><br>
                                <span>{{__('levels.email')}}: {{@$parcel->merchant->user->email}}</span><br>
                                <span>{{__('levels.mobile')}}: {{@$parcel->merchant->user->mobile}}</span><br/>
                                <div >
                                    <strong>{!! @dateFormat($parcel->created_at) !!}</strong><br>
                                    <small>{!! @date('h:i a', strtotime($parcel->created_at)) !!}</small>
                                </div>
                            </div>
                            <!-- cd-timeline__content -->
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>
    <!-- end timeline  -->
</div>
<!-- end wrapper  -->
@endsection()
<!-- css  -->
@push('styles')
    <link rel="stylesheet" href="{{static_asset('backend')}}/css/logs.css">
@endpush


