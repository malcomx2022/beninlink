@extends('backend.partials.master')
@section('title')
   {{ __('menus.pay_out') }} {{ __('menus.settings') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('menus.settings') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('menus.pay_out') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        {{-- F2/F3 — FedaPay n'avait aucun ecran : ni cle, ni statut, ni meme
             la possibilite de savoir si la passerelle tournait en bac a sable.
             Cette carte est sa seule surface de reglage cote administration. --}}
        @if(gatewayEnabled(\App\Enums\PayoutSetup::FEDAPAY))
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('fedapay.title') }}</h4>

                    <div class="alert alert-light border mb-3">
                        <div class="d-flex justify-content-between">
                            <span>{{ __('fedapay.environment') }}</span>
                            <strong>
                                @if($fedapayIsLive)
                                    <span class="text-danger">{{ __('fedapay.environment_live') }}</span>
                                @else
                                    <span class="text-success">{{ __('fedapay.environment_sandbox') }}</span>
                                @endif
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('fedapay.account') }}</span>
                            <strong>
                                @if($fedapayUsesOwnAccount)
                                    {{ __('fedapay.account_own') }}
                                @else
                                    {{ __('fedapay.account_platform') }}
                                @endif
                            </strong>
                        </div>
                        <small class="d-block text-muted mt-2">{{ __('fedapay.account_hint') }}</small>
                    </div>

                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::FEDAPAY)}}"  method="POST" id="fedapayform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">
                                <div class="form-group">
                                    <label for="fedapay_secret_key">{{ __('fedapay.secret_key') }}</label>
                                    {{-- Jamais reaffiche : un champ vide veut dire « ne pas changer ». --}}
                                    <input id="fedapay_secret_key" type="password" name="fedapay_secret_key" autocomplete="new-password" placeholder="{{ $fedapayUsesOwnAccount ? __('fedapay.secret_kept') : __('fedapay.secret_placeholder') }}" class="form-control @error('fedapay_secret_key') is-invalid @enderror" value="">
                                    @error('fedapay_secret_key')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                    <small class="text-muted">{{ __('fedapay.secret_key_hint') }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="fedapay_webhook_secret">{{ __('fedapay.webhook_secret') }}</label>
                                    <input id="fedapay_webhook_secret" type="password" name="fedapay_webhook_secret" autocomplete="new-password" placeholder="{{ $fedapayHasWebhookSecret ? __('fedapay.secret_kept') : __('fedapay.secret_placeholder') }}" class="form-control @error('fedapay_webhook_secret') is-invalid @enderror" value="">
                                    @error('fedapay_webhook_secret')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                    <small class="text-muted">{{ __('fedapay.webhook_secret_hint', ['url' => route('fedapay.webhook')]) }}</small>
                                </div>

                                <div class="form-group d-flex">
                                    <label for="fedapay-switch">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="fedapay_status" id="fedapay-switch" type="checkbox" role="switch" @if($fedapayStatusActive) checked @endif>
                                    </div>
                                </div>
                                <small class="text-muted">{{ __('fedapay.status_hint') }}</small>
                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endif
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('Paypal') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::PAYPAL)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">

                                <div class="form-group">
                                    <label for="paypal_client_id">{{ __('levels.paypal_client_id') }}</label> <span class="text-danger">*</span>
                                    <input id="paypal_client_id" type="text" name="paypal_client_id" data-parsley-trigger="change" placeholder="{{ __('levels.paypal_client_id') }}" autocomplete="off" class="form-control @error('paypal_client_id') is-invalid @enderror" value="{{ old('paypal_client_id', globalSettings('paypal_client_id')) }}" require >
                                    @error('paypal_client_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="paypal_client_secret">{{ __('levels.paypal_client_secret') }}</label> <span class="text-danger">*</span>
                                    <input id="paypal_client_secret" type="text" name="paypal_client_secret" data-parsley-trigger="change" placeholder="{{ __('levels.paypal_client_secret') }}" autocomplete="off" class="form-control @error('paypal_client_secret') is-invalid @enderror" value="{{ old('paypal_client_secret', globalSettings('paypal_client_secret')) }}" require >
                                    @error('paypal_client_secret')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="paypal_mode">{{ __('levels.test_mode') }}</label> <span class="text-danger">*</span>
                                    <input id="paypal_mode" type="text" name="paypal_mode" data-parsley-trigger="change" placeholder="{{ __('levels.paypal_mode') }}" autocomplete="off" class="form-control @error('paypal_mode') is-invalid @enderror" value="{{ old('paypal_mode', globalSettings('paypal_mode')) }}" require >
                                    @error('paypal_mode')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="paypal_status"  id="switch-id" type="checkbox" role="switch"   @if(old('paypal_status', globalSettings('paypal_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('Stripe') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::STRIPE)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">
                                <div class="form-group">
                                    <label for="stripe_publishable_key">{{ __('levels.stripe_publishable_key') }}</label> <span class="text-danger">*</span>
                                    <input id="stripe_publishable_key" type="text" name="stripe_publishable_key" data-parsley-trigger="change" placeholder="{{ __('levels.stripe_publishable_key') }}" autocomplete="off" class="form-control @error('stripe_publishable_key') is-invalid @enderror" value="{{ old('stripe_publishable_key', globalSettings('stripe_publishable_key')) }}" require >
                                    @error('stripe_publishable_key')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="stripe_secret_key">{{ __('levels.stripe_secret_key') }}</label> <span class="text-danger">*</span>
                                    <input id="stripe_secret_key" type="text" name="stripe_secret_key" data-parsley-trigger="change" placeholder="{{ __('levels.stripe_secret_key') }}" autocomplete="off" class="form-control @error('stripe_secret_key') is-invalid @enderror" value="{{ old('stripe_secret_key', globalSettings('stripe_secret_key')) }}" require >
                                    @error('stripe_secret_key')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="stripe_status"  id="switch-id" type="checkbox" role="switch"   @if(old('stripe_status', globalSettings('stripe_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('Razorpay') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                        <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::RAZORPAY)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                            @method('PUT')
                            @csrf
                            @endif
                            <div class="row">
                                <div class="col-12 ">
                                    <div class="form-group">
                                        <label for="razorpay_key">{{ __('levels.razorpay_key') }}</label> <span class="text-danger">*</span>
                                        <input id="razorpay_key" type="text" name="razorpay_key" data-parsley-trigger="change" placeholder="{{ __('levels.razorpay_key') }}" autocomplete="off" class="form-control @error('razorpay_key') is-invalid @enderror" value="{{ old('razorpay_key', globalSettings('razorpay_key')) }}" require >
                                        @error('razorpay_key')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="razorpay_secret">{{ __('levels.razorpay_secret') }}</label> <span class="text-danger">*</span>
                                        <input id="razorpay_secret" type="text" name="razorpay_secret" data-parsley-trigger="change" placeholder="{{ __('levels.razorpay_secret') }}" autocomplete="off" class="form-control @error('razorpay_secret') is-invalid @enderror" value="{{ old('razorpay_secret', globalSettings('razorpay_secret')) }}" require >
                                        @error('razorpay_secret')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group d-flex">
                                        <label for="switch-id">{{ __('levels.status') }}</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input switch-id ml-3" name="razorpay_status"  id="switch-id" type="checkbox" role="switch"   @if(old('razorpay_status', globalSettings('razorpay_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            @if(hasPermission('payout_setup_settings_update'))
                                <div class="row pt-4">
                                    <div class="col-12 text-right">
                                        <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                    </div>
                                </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('Skrill') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::SKRILL)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">

                                <div class="form-group">
                                    <label for="skrill_merchant_email">{{ __('levels.skrill_merchant_email') }}</label> <span class="text-danger">*</span>
                                    <input id="skrill_merchant_email" type="text" name="skrill_merchant_email" data-parsley-trigger="change" placeholder="{{ __('levels.skrill_merchant_email') }}" autocomplete="off" class="form-control @error('skrill_merchant_email') is-invalid @enderror" value="{{ old('skrill_merchant_email', globalSettings('skrill_merchant_email')) }}" require >
                                    @error('skrill_merchant_email')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="skrill_status"  id="switch-id" type="checkbox" role="switch"   @if(old('skrill_status', globalSettings('skrill_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @if(gatewayEnabled(\App\Enums\PayoutSetup::SSL_COMMERZ)) {{-- S21 --}}
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('SSL Commerz') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::SSL_COMMERZ)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">

                                <div class="form-group">
                                    <label for="sslcommerz_store_id">{{ __('levels.sslcommerz_store_id') }}</label> <span class="text-danger">*</span>
                                    <input id="sslcommerz_store_id" type="text" name="sslcommerz_store_id" data-parsley-trigger="change" placeholder="{{ __('levels.sslcommerz_store_id') }}" autocomplete="off" class="form-control @error('sslcommerz_store_id') is-invalid @enderror" value="{{ old('sslcommerz_store_id', globalSettings('sslcommerz_store_id')) }}" require >
                                    @error('sslcommerz_store_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="sslcommerz_store_password">{{ __('levels.sslcommerz_store_password') }}</label> <span class="text-danger">*</span>
                                    <input id="sslcommerz_store_password" type="text" name="sslcommerz_store_password" data-parsley-trigger="change" placeholder="{{ __('levels.sslcommerz_store_password') }}" autocomplete="off" class="form-control @error('sslcommerz_store_password') is-invalid @enderror" value="{{ old('sslcommerz_store_password', globalSettings('sslcommerz_store_password')) }}" require >
                                    @error('sslcommerz_store_password')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.test_mode') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="sslcommerz_testmode"  id="switch-id" type="checkbox" role="switch"   @if(old('sslcommerz_testmode', globalSettings('sslcommerz_testmode')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>


                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="sslcommerz_status"  id="switch-id" type="checkbox" role="switch"   @if(old('sslcommerz_status', globalSettings('sslcommerz_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endif
        @if(gatewayEnabled(\App\Enums\PayoutSetup::AAMARPAY)) {{-- S21 --}}
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('Aamarpay') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::AAMARPAY)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">
                                <div class="form-group">
                                    <label for="aamarpay_store_id">{{ __('levels.aamarpay_store_id') }}</label> <span class="text-danger">*</span>
                                    <input id="aamarpay_store_id" type="text" name="aamarpay_store_id" data-parsley-trigger="change" placeholder="{{ __('levels.aamarpay_store_id') }}" autocomplete="off" class="form-control @error('aamarpay_store_id') is-invalid @enderror" value="{{ old('aamarpay_store_id', globalSettings('aamarpay_store_id')) }}" require >
                                    @error('aamarpay_store_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="aamarpay_signature_key">{{ __('levels.aamarpay_signature_key') }}</label> <span class="text-danger">*</span>
                                    <input id="aamarpay_signature_key" type="text" name="aamarpay_signature_key" data-parsley-trigger="change" placeholder="{{ __('levels.aamarpay_signature_key') }}" autocomplete="off" class="form-control @error('aamarpay_signature_key') is-invalid @enderror" value="{{ old('aamarpay_signature_key', globalSettings('aamarpay_signature_key')) }}" require >
                                    @error('aamarpay_signature_key')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.sendbox_mode') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="aamarpay_sendbox_mode"  id="switch-id" type="checkbox" role="switch"   @if(old('aamarpay_sendbox_mode', globalSettings('aamarpay_sendbox_mode')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>
                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="aamarpay_status"  id="switch-id" type="checkbox" role="switch"   @if(old('aamarpay_status', globalSettings('aamarpay_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endif
        <div class="col-lg-6  col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="h4 mb-3">{{ __('Bkash') }}</h4>
                    @if(hasPermission('payout_setup_settings_update'))
                    <form action="{{route('payout.setup.settings.update',\App\Enums\PayoutSetup::BKASH)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                    @endif
                        <div class="row">
                            <div class="col-12 ">

                                <div class="form-group">
                                    <label for="bkash_app_id">{{ __('levels.bkash_app_id') }}</label> <span class="text-danger">*</span>
                                    <input id="bkash_app_id" type="text" name="bkash_app_id" data-parsley-trigger="change" placeholder="{{ __('levels.bkash_app_id') }}" autocomplete="off" class="form-control @error('bkash_app_id') is-invalid @enderror" value="{{ old('bkash_app_id', globalSettings('bkash_app_id')) }}" require >
                                    @error('bkash_app_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="bkash_app_secret">{{ __('levels.bkash_app_secret') }}</label> <span class="text-danger">*</span>
                                    <input id="bkash_app_secret" type="text" name="bkash_app_secret" data-parsley-trigger="change" placeholder="{{ __('levels.bkash_app_secret') }}" autocomplete="off" class="form-control @error('bkash_app_secret') is-invalid @enderror" value="{{ old('bkash_app_secret', globalSettings('bkash_app_secret')) }}" require >
                                    @error('bkash_app_secret')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>


                                <div class="form-group">
                                    <label for="bkash_username">{{ __('levels.bkash_username') }}</label> <span class="text-danger">*</span>
                                    <input id="bkash_username" type="text" name="bkash_username" data-parsley-trigger="change" placeholder="{{ __('levels.bkash_username') }}" autocomplete="off" class="form-control @error('bkash_username') is-invalid @enderror" value="{{ old('bkash_username', globalSettings('bkash_username')) }}" require >
                                    @error('bkash_username')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="bkash_password">{{ __('levels.bkash_password') }}</label> <span class="text-danger">*</span>
                                    <input id="bkash_password" type="password" name="bkash_password" data-parsley-trigger="change" placeholder="{{ __('levels.bkash_password') }}" autocomplete="off" class="form-control @error('bkash_password') is-invalid @enderror" value="{{ old('bkash_password', globalSettings('bkash_password')) }}" require >
                                    @error('bkash_password')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.bkash_test_mode') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="bkash_test_mode"  id="switch-id" type="checkbox" role="switch"   @if(old('bkash_test_mode', globalSettings('bkash_test_mode')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>

                                <div class="form-group d-flex">
                                    <label for="switch-id">{{ __('levels.status') }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input switch-id ml-3" name="bkash_status"  id="switch-id" type="checkbox" role="switch"   @if(old('bkash_status', globalSettings('bkash_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @if(hasPermission('payout_setup_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()
