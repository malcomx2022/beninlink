@extends('backend.partials.master')
@section('title')
   {{ __('menus.google_map_settings') }}
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
                            <li class="breadcrumb-item"><a href="{{route('googlemap-settings.index')}}" class="breadcrumb-link active">{{ __('menus.google_map_settings') }}</a></li>
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
                    <h2 class="h3 mb-3 pageheader-title">{{__('menus.google_map_settings')}}</h2>
                    <form action="{{route('googlemap-settings.update')}}"  method="POST" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="map_key">{{ __('levels.map_key') }}</label> <span class="text-danger">*</span>
                                    <input id="map_key" type="text" name="map_key" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('map_key') is-invalid @enderror" value="{{old('map_key',optional($settings)->map_key) }}" require>
                                    @error('map_key')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                            <div class="row pt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

