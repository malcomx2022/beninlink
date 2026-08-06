@extends('backend.partials.master')
@section('title')
   {{ __('smsSettings.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{route('sms-settings.index')}}" class="breadcrumb-link">{{ __('smsSettings.title') }}</a></li>
                                <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('smsSettings.title') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6  col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="h4 mb-3">{{ __('REVE SMS') }}</h4>
                        @if(hasPermission('sms_settings_update') || hasPermission('sms_settings_create'))
                            <form action="{{route('sms-settings.update',\App\Enums\SmsSetup::REVE)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                                @method('PUT')
                                @csrf
                                @endif
                                <div class="row">
                                    <div class="col-12 ">
                                        <input type="hidden" value="{{\App\Enums\SmsSetup::REVE}}" name="smsMethod">
                                        <div class="form-group">
                                            <label for="reve_api_key">{{ __('smsSettings.api_key') }}</label> <span class="text-danger">*</span>
                                            <input id="reve_api_key" type="text" name="reve_api_key" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_api_key') }}" autocomplete="off" class="form-control @error('reve_api_key') is-invalid @enderror" value="{{ old('reve_api_key', smsSettings('reve_api_key')) }}" require >
                                            @error('reve_api_key')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="reve_secret_key">{{ __('smsSettings.secret_key') }}</label> <span class="text-danger">*</span>
                                            <input id="reve_secret_key" type="text" name="reve_secret_key" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_secret_key') }}" autocomplete="off" class="form-control @error('reve_secret_key') is-invalid @enderror" value="{{old('reve_secret_key',smsSettings('reve_secret_key'))}}" >
                                            @error('reve_secret_key')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="reve_api_url">{{ __('smsSettings.api_url') }}</label> <span class="text-danger">*</span>
                                            <input id="reve_api_url" type="text" name="reve_api_url" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_api_url') }}" autocomplete="off" class="form-control @error('reve_api_url') is-invalid @enderror" value="{{old('reve_api_url',smsSettings('reve_api_url'))}}" >
                                            @error('reve_api_url')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="reve_username">{{ __('smsSettings.username') }}</label>
                                            <input id="reve_username" type="text" name="reve_username" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_username') }}" autocomplete="off" class="form-control" value="{{old('reve_username',smsSettings('reve_username'))}}" >
                                            @error('reve_username')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="reve_user_password">{{ __('smsSettings.user_password') }}</label>
                                            <input id="reve_user_password" type="text" name="reve_user_password" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_user_password') }}" autocomplete="off" class="form-control" value="{{old('reve_user_password',smsSettings('reve_user_password'))}}" >
                                            @error('reve_user_password')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group d-flex">
                                            <label for="switch-id">{{ __('levels.status') }}</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input switch-id ml-3" name="reve_status"  id="switch-id" type="checkbox" role="switch"   @if(old('reve_status', smsSettings('reve_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if(hasPermission('sms_settings_update') || hasPermission('sms_settings_create'))
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
                        <h4 class="h4 mb-3">{{ __('TWILIO SMS') }}</h4>
                        @if(hasPermission('sms_settings_update') || hasPermission('sms_settings_create'))
                            <form action="{{route('sms-settings.update',\App\Enums\SmsSetup::TWILIO)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                                @method('PUT')
                                @csrf
                                @endif
                                <div class="row">
                                    <input type="hidden" value="{{\App\Enums\SmsSetup::TWILIO}}" name="smsMethod">
                                    <div class="col-12 ">
                                        <div class="form-group">
                                            <label for="twilio_sid">{{ __('levels.twilio_sid') }}</label> <span class="text-danger">*</span>
                                            <input id="twilio_sid" type="text" name="twilio_sid" data-parsley-trigger="change" placeholder="{{ __('levels.twilio_sid') }}" autocomplete="off" class="form-control @error('twilio_sid') is-invalid @enderror" value="{{ old('twilio_sid', smsSettings('twilio_sid')) }}" require >
                                            @error('twilio_sid')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="twilio_token">{{ __('levels.twilio_token') }}</label> <span class="text-danger">*</span>
                                            <input id="twilio_token" type="text" name="twilio_token" data-parsley-trigger="change" placeholder="{{ __('levels.twilio_token') }}" autocomplete="off" class="form-control @error('twilio_token') is-invalid @enderror" value="{{ old('twilio_token', smsSettings('twilio_token')) }}" require >
                                            @error('twilio_token')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="twilio_from">{{ __('levels.twilio_from') }}</label> <span class="text-danger">*</span>
                                            <input id="twilio_from" type="text" name="twilio_from" data-parsley-trigger="change" placeholder="{{ __('levels.twilio_from') }}" autocomplete="off" class="form-control @error('twilio_from') is-invalid @enderror" value="{{ old('twilio_from', smsSettings('twilio_from')) }}" require >
                                            @error('twilio_from')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group d-flex">
                                            <label for="switch-id">{{ __('levels.status') }}</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input switch-id ml-3" name="twilio_status"  id="switch-id" type="checkbox" role="switch"   @if(old('twilio_status', smsSettings('twilio_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if(hasPermission('sms_settings_update') || hasPermission('sms_settings_create'))
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
                        <h4 class="h4 mb-3">{{ __('NEXMO SMS') }}</h4>
                        @if(hasPermission('sms_settings_update') || hasPermission('sms_settings_create'))
                            <form action="{{route('sms-settings.update',\App\Enums\SmsSetup::NEXMO)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                                @method('PUT')
                                @csrf
                                @endif
                                <div class="row">
                                    <input type="hidden" value="{{\App\Enums\SmsSetup::NEXMO}}" name="smsMethod">
                                    <div class="col-12 ">
                                        <div class="form-group">
                                            <label for="nexmo_key">{{ __('levels.nexmo_key') }}</label> <span class="text-danger">*</span>
                                            <input id="nexmo_key" type="text" name="nexmo_key" data-parsley-trigger="change" placeholder="{{ __('levels.nexmo_key') }}" autocomplete="off" class="form-control @error('nexmo_key') is-invalid @enderror" value="{{ old('nexmo_key', smsSettings('nexmo_key')) }}" require >
                                            @error('nexmo_key')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="nexmo_secret_key">{{ __('levels.nexmo_secret_key') }}</label> <span class="text-danger">*</span>
                                            <input id="nexmo_secret_key" type="text" name="nexmo_secret_key" data-parsley-trigger="change" placeholder="{{ __('levels.nexmo_secret_key') }}" autocomplete="off" class="form-control @error('nexmo_secret_key') is-invalid @enderror" value="{{ old('nexmo_secret_key', smsSettings('nexmo_secret_key')) }}" require >
                                            @error('nexmo_secret_key')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group d-flex">
                                            <label for="switch-id">{{ __('levels.status') }}</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input switch-id ml-3" name="nexmo_status"  id="switch-id" type="checkbox" role="switch"   @if(old('nexmo_status', smsSettings('nexmo_status')) == \App\Enums\Status::ACTIVE) checked @else @endif>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if(hasPermission('sms_settings_update') || hasPermission('sms_settings_create'))
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



