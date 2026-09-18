@extends('backend.partials.master')
@section('title')
    {{ __('menus.payout') }} {{ __('levels.list') }}
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
        <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="row  ">
                <div class="col-12  ">
                    <div class="card">
                        <div class="card-body">
                            <p class="h3">{{ __('menus.payout') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Lot 5 : un écran vide n'explique rien. Le module de retrait est coupé
                 (D10) ; sans ce message, la page n'affichait qu'un titre et une rangée
                 vide. Le menu ne mène plus ici, mais une URL en favori, si. --}}
            @if (!onlinePayoutEnabled())
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted">
                                {{ __('levels.payout_unavailable') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row mt-2">
                @if(onlinePayoutEnabled() && globalSettings('paypal_status') == \App\Enums\Status::ACTIVE) {{-- D10 --}}
                <div class="col-lg-6 col-md-6 ">
                    <a href="{{ route('online.payment.paypal.index') }}" >
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="{{ static_asset('backend/images/default/payout/paypal.png') }}" alt="PayPal" width="150px" style="margin:-5px"/>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                @if(onlinePayoutEnabled() && globalSettings('stripe_status') == \App\Enums\Status::ACTIVE) {{-- D10 --}}
                    <div class="col-lg-6  col-md-6">
                        <a href="{{ route('online.payment.stripe') }}" >
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="{{ static_asset('backend/images/default/payout/stripe.png') }}" alt="Stripe" width="150px"/>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if(onlinePayoutEnabled() && globalSettings('skrill_status') == \App\Enums\Status::ACTIVE) {{-- D10 --}}
                    <div class="col-lg-6  col-md-6">
                        <a href="{{ route('skrill.index') }}" >
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="{{ static_asset('backend/images/default/payout/skrill.png') }}" alt="Skrill" width="150px" style="margin:10px"/>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if(gatewayEnabled(\App\Enums\PayoutSetup::SSL_COMMERZ) && globalSettings('sslcommerz_status') == \App\Enums\Status::ACTIVE)
                    <div class="col-lg-6 col-md-6">
                        <a href="{{ route('online.payment.sslcommerz.index') }}" >
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="{{ static_asset('backend/images/default/payout/sslecommerce.png') }}" alt="SSLCommerz" width="150px" style="margin:20px"/>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if(gatewayEnabled(\App\Enums\PayoutSetup::AAMARPAY) && globalSettings('aamarpay_status') == \App\Enums\Status::ACTIVE)
                <div class="col-lg-6 col-md-6">
                    <a href="{{ route('online.payment.aamarpay.index') }}" >
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="{{ static_asset('backend/images/default/payout/aamarpay.png') }}" alt="aamarPay" width="150px" style="margin:25px"/>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                @if(onlinePayoutEnabled() && globalSettings('bkash_status') == \App\Enums\Status::ACTIVE) {{-- D10 --}}
                    <div class="col-lg-6 col-md-6">
                        <a href="{{ route('online.payment.bkash.index') }}" >
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="{{ static_asset('backend/images/default/payout/bkash.png') }}" alt="bKash" width="150px" style="margin:10px"/>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12">
            @yield('cardcontent')
        </div>

        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()

