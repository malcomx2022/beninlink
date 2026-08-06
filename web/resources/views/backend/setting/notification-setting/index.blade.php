@extends('backend.partials.master')
@section('title')
   {{ __('menus.notification_settings') }} 
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
                            <li class="breadcrumb-item"><a href="{{route('notification-settings.index')}}" class="breadcrumb-link active">{{ __('menus.notification_settings') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="h3 mb-3 pageheader-title">{{__('menus.notification_settings')}}</h2>
                    <form action="{{route('notification-settings.update')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="fcm_secret_key">{{ __('levels.fcm_secret_key') }}</label> <span class="text-danger">*</span>
                                    <input id="fcm_secret_key" type="text" name="fcm_secret_key" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control @error('fcm_secret_key') is-invalid @enderror" value="{{old('fcm_secret_key',$settings->fcm_secret_key) }}" require>
                                    @error('fcm_secret_key')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="fcm_topic">{{ __('levels.fcm_topic') }}</label> <span class="text-danger">*</span>
                                    <input id="fcm_topic" type="text" name="fcm_topic" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_phone') }}" autocomplete="off" class="form-control @error('fcm_topic') is-invalid @enderror" value="{{ old('fcm_topic',$settings->fcm_topic) }}" require>
                                    @error('fcm_topic')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @if(hasPermission('notification_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                        @endif

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

