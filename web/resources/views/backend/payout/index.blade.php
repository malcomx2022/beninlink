@extends('backend.partials.master')
@section('title')
   {{ __('menus.payout') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('menus.payout') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-4  col-lg-12 col-sm-12 col-12">
            <div class="row  ">
                <div class="col-12  ">
                    <div class="card">
                        <div class="card-body">
                            <p class="h3">{{ __('menus.payout') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{route('payout.merchant.payout')}}"  method="GET">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-lg-12">
                                        <label for="parcelMerchantid">{{ __('parcel.merchant') }}</label>
                                        <select style="width: 100%" id="parcelMerchantid"  name="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror" data-url="{{ route('parcel.merchant.shops') }}">
                                            <option value=""> {{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                        </select>
                                        @error('merchant_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group col-lg-12 text-right">
                                        <button type="submit" class="btn btn-sm btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.search') }}</button>
                                        <a href="{{ route('payout.index') }}" class="btn btn-sm btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @if(isset($merchant_id))
                    @if(MerchantSearchSettings($merchant_id,'paypal_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6">
                            <a href="{{ route('payout.paypal.index',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/paypal.png') }}" alt="stripe.png" width="150px" style="margin:-5px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                    @if(MerchantSearchSettings($merchant_id,'stripe_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6">
                            <a href="{{ route('payout.merchant.stripe',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/stripe.png') }}" alt="stripe.png" width="150px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                        @if(MerchantSearchSettings($merchant_id,'razorpay_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6">
                            <a href="{{ route('payout.merchant.razorpay',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/razorpay.png') }}" alt="razorpay.png" width="150px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                    @if(MerchantSearchSettings($merchant_id,'skrill_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6">
                            <a href="{{ route('payout.skrill.index',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/skrill.png') }}" alt="skrill.png" width="150px" style="margin:10px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                    @if(MerchantSearchSettings($merchant_id,'sslcommerz_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6">
                            <a href="{{ route('payout.sslcommerz.index',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/sslecommerce.png') }}" alt="stripe.png" width="150px" style="margin:20px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                    @if(MerchantSearchSettings($merchant_id,'aamarpay_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6">
                            <a href="{{ route('payout.aamarpay.index',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/aamarpay.png') }}" alt="stripe.png" width="150px" style="margin:25px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                    @if(MerchantSearchSettings($merchant_id,'bkash_status') == \App\Enums\Status::ACTIVE)
                        <div class="col-md-6 ">
                            <a href="{{ route('payout.bkash.index',['merchant_id'=>$merchant_id]) }}" >
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="{{ static_asset('backend/images/default/payout/bkash.png') }}" alt="skrill.png" width="150px" style="margin:10px"/>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <div class="col-xl-8 col-lg-12 col-sm-12 col-12">
            @yield('cardcontent')
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
<!-- js  -->
@push('scripts')
    <script type="text/javascript">
            var merchantUrl = '{{ route('parcel.merchant.get') }}';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ static_asset('backend/js/parcel/filter.js') }}"></script>
@endpush


