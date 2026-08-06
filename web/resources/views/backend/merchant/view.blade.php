@extends('backend.partials.master')
@section('title')
    {{ __('merchant.title') }}  {{ __('levels.view') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('merchant.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.view') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                    </div>
                </div>
                <div class="card-body">
                   <div class="row">
                    <div class="col-md-4 col-lg-12 col-xl-3 ">
                        <div class="card card-fluid">
                            <div class="card-body text-center">
                                <a href="#" class="user-avatar user-avatar-xl my-3">
                                  <img src="{{$singleMerchant->user->image}}" alt="User Avatar" class="rounded-circle user-avatar-xl">
                                </a>
                                <h3 class="card-title mb-2 text-truncate">
                                    <a href="#">{{$singleMerchant->user->name}}</a>
                                </h3>
                                <h6 class="card-subtitle text-muted mb-3"> {{ __('levels.email') }}: {{$singleMerchant->user->email}}</h6>
                                <h6 class="card-subtitle text-muted mb-3"> {{ __('levels.phone') }}: {{$singleMerchant->user->mobile}}</h6>

                            </div>
                            <div class="list-group list-group-flush merchant-view">
                                <a href="{{ route('merchant.view',$singleMerchant->id) }}" class="list-group-item list-group-item-action {{ (request()->is('admin/merchant/view/'.$singleMerchant->id)) ? 'active' : '' }}">{{ __('merchant.company_information') }}</a>
                            @if(hasPermission('merchant_delivery_charge_read') == true )
                                <a href="{{ route('merchant.deliveryCharge.index',$singleMerchant->id) }}" class="list-group-item list-group-item-action {{ (request()->is('admin/merchant/'.$singleMerchant->id.'/delivery-charge*')) ? 'active' : '' }}">{{ __('merchant.delivery_charge') }}</a>
                            @endif
                            @if(hasPermission('merchant_shop_read') == true )
                                <a href="{{ route('merchant.shops.index',$singleMerchant->id) }}" class="list-group-item list-group-item-action {{ (request()->is('admin/merchant/'.$singleMerchant->id.'/shops*','admin/merchant/shops*')) ? 'active' : '' }}">{{ __('merchant.shop') }}</a>
                            @endif
                            @if(hasPermission('merchant_payment_read') == true )
                                <a href="{{ route('merchant.paymentaccount.index',$singleMerchant->id) }}" class="list-group-item list-group-item-action {{ (request()->is('admin/merchant/'.$singleMerchant->id.'/payment*')) ? 'active' : '' }}">{{ __('merchant.payment_account') }}</a>
                            @endif
                            @if(hasPermission('invoice_read') == true )
                                <a href="{{ route('merchant.invoice.index',$singleMerchant->id) }}" class="list-group-item list-group-item-action {{ (request()->is('admin/merchant/'.$singleMerchant->id.'/invoice*')) ? 'active' : '' }}">{{ __('menus.invoice') }}</a>
                            @endif
                            </div>
                   

                            <div class="row mt-3 mb-3 w-100  " style="margin:autocomplete">
                                <div class="col-md-6 pr-0 pt-2">
                                    <div class="metric btn btn-primary w-100">
                                        <h6 class="metric-value text-white"> {{ $singleMerchant->parcels->count()}} </h6>
                                        <p class="metric-label font-size-12 text-white">Total {{__('parcel.title')}} </p>
                                    </div>
                                </div>

                                <div class="col-md-6 pr-0 pt-2">
                                    <div class="metric btn btn-primary w-100">
                                        <h6 class="metric-value text-white"> {{ settings()->currency }}  {{ $singleMerchant->parcels->sum('cash_collection') }} </h6>
                                        <p class="metric-label font-size-12 text-white"> {{ __('merchant.amount') }} </p>
                                    </div>

                                </div>
                                <div class="col-md-6 pr-0 pt-2">
                                    <div class="metric btn btn-primary w-100">
                                        <h6 class="metric-value text-white"> {{ $singleMerchant->parcels->whereIn('status',[App\Enums\ParcelStatus::DELIVERED,App\Enums\ParcelStatus::PARTIAL_DELIVERED])->count()}} </h6>
                                        <p class="metric-label font-size-12 text-white">Total Delivered</p>
                                    </div>
                                </div>
                                <div class="col-md-6 pr-0 pt-2 ">
                                    <div class="metric btn btn-primary w-100">
                                        <h6 class="metric-value text-white">{{ settings()->currency }} {{ $singleMerchant->current_balance }}</h6>
                                        <p class="metric-label font-size-12 text-white">{{ __('merchant.payble_amount') }} </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-md-8 col-lg-12 col-xl-9  ">
                        @yield('backend.merchant.layout.list')
                    </div>
                   </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()
