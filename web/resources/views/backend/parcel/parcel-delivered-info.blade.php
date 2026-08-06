@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}    {{ __('View Proof of Delivery') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('View Proof of Delivery')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <section class="mt-5 pt-5">
        <div class="row">
            <div class="col-xl-8 ">
                <section class="cd-timeline js-cd-timeline">
                    <div class="cd-timeline__container">
                        @foreach ($parcelevents as $log)
                            @switch($log->parcel_status)
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
                                            <div class="row mt-2">
                                                <div class="col-md-6">
                                                    <strong class="mb-2">Delivered Photo</strong>
                                                    <img src="{{ static_asset(@$log->delivered_image) }}" style="width: 100%;height: 300px;" alt="delivered_image" class="img-responsive mt-2" >
                                                </div>
                                                <div class="col-md-6">
                                                    <strong class="mb-2">Signature</strong>
                                                    <img src="{{static_asset(@$log->signature_image)}}" style="width: 100%;height: 150px;"  alt="signature_image" class="img-responsive mt-2" >
                                                </div>
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
                                @default
                                    <div class="cd-timeline__block js-cd-block"></div>
                            @endswitch
                        @endforeach

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


