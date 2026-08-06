@extends('backend.partials.master')
@section('title')
    {{ __('levels.delivery_type') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.delivery_type') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                        <p class="h3">{{ __('levels.delivery_type') }}</p>
                    </div>

                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.title') }}</th>
                                    @if(hasPermission('delivery_type_status_change'))
                                    <th>{{ __('levels.status') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{ __('deliveryType.'.\App\Enums\DeliveryType::SAMEDAY) }}</td>
                                    @if(hasPermission('delivery_type_status_change') == true)
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input switch-id" type="checkbox" data-url="{{ route('delivery-type.status') }}" id="switch-id" role="switch" value="same_day"  @if(SettingHelper('same_day') == \App\Enums\Status::ACTIVE) checked @endif>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{ __('deliveryType.'.\App\Enums\DeliveryType::NEXTDAY) }}</td>
                                    @if(hasPermission('delivery_type_status_change') == true)
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input switch-id" type="checkbox" data-url="{{ route('delivery-type.status') }}" id="switch-id" role="switch" value="next_day"   @if(SettingHelper('next_day') == \App\Enums\Status::ACTIVE) checked @endif>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{ __('deliveryType.'.\App\Enums\DeliveryType::SUBCITY) }}</td>
                                    @if(hasPermission('delivery_type_status_change') == true)
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input switch-id" type="checkbox" data-url="{{ route('delivery-type.status') }}" id="switch-id" role="switch" value="sub_city"  @if(SettingHelper('sub_city') == \App\Enums\Status::ACTIVE) checked @endif>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{ __('deliveryType.'.\App\Enums\DeliveryType::OUTSIDECITY) }}</td>
                                    @if(hasPermission('delivery_type_status_change') == true)
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input switch-id" type="checkbox" data-url="{{ route('delivery-type.status') }}" id="switch-id" role="switch" value="outside_city"   @if(SettingHelper('outside_city') == \App\Enums\Status::ACTIVE) checked @endif>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
    <link rel="stylesheet" href="{{static_asset('backend')}}/css/switch.css">
@endpush
<!-- js  -->
@push('scripts')
    <script src="{{ static_asset('backend/js/deliverytype/status.js') }}"></script>
@endpush


