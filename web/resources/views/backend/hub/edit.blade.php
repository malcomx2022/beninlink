@extends('backend.partials.master')
@section('title')
    {{ __('hub.title') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <!-- pageheader -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('hubs.index') }}"
                                        class="breadcrumb-link">{{ __('hub.title') }}</a></li>
                                <li class="breadcrumb-item"><a href=""
                                        class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end pageheader -->
        <div class="row">
            <!-- basic form -->
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="pageheader-title">{{ __('hub.edit_hub') }}</h2>
                        <form action="{{ route('hubs.update', ['id' => $hub->id]) }}" method="POST"
                            enctype="multipart/form-data" id="basicform">
                            @csrf
                            @if (isset($hub))
                                @method('PUT')
                            @endif
                            <div class="form-group">
                                <label for="name">{{ __('levels.name') }}</label> <span class="text-danger">*</span>
                                <input id="name" type="text" name="name" data-parsley-trigger="change"
                                    placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off"
                                    class="form-control @error('name') is-invalid @enderror" value="{{ $hub->name }}"
                                    require>
                                @error('name')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="phone">{{ __('levels.phone') }}</label> <span class="text-danger">*</span>
                                <input id="phone" type="number" name="phone" data-parsley-trigger="change"
                                    placeholder="{{ __('placeholder.Enter_phone') }}" autocomplete="off"
                                    class="form-control @error('phone') is-invalid @enderror" value="{{ $hub->phone }}"
                                    require>
                                @error('phone')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>



                            <div class="form-group">
                                <label for="address"
                                    class="col-form-label text-sm-right">{{ __('levels.address') }}</label> <span
                                    class="text-danger">*</span>
                                <input type="hidden" id="lat" name="lat" required="" value="">
                                <input type="hidden" id="long" name="long" required="" value="">
                                <div class="main-search-input-item location location-search">
                                    <div id="autocomplete-container" class="form-group random-search">
                                        <input id="autocomplete-input" type="text" name="address"
                                            class="recipe-search2 form-control" placeholder="Location Here!" required="">
                                        <a href="javascript:void(0)" class="submit-btn btn current-location"
                                            id="locationIcon" onclick="getLocation()">
                                            <i class="fa fa-crosshairs"></i>
                                        </a>
                                        @error('address')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="">
                                    <div id="googleMap" class="custom-map"></div>
                                </div>
                            </div>


                            <div class="form-group">
                                <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                <select name="status" class="form-control @error('status') is-invalid @enderror">
                                    @foreach (trans('status') as $key => $status)
                                        <option value="{{ $key }}"
                                            {{ old('status', $hub->status) == $key ? 'selected' : '' }}>
                                            {{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                    <button type="submit"
                                        class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                    <a href="{{ route('hubs.index') }}"
                                        class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end basic form -->
        </div>
    </div>
    <!-- end wrapper  -->
@endsection()
@push('styles')
    <style>
        .main-search-input-item {
            flex: 1;
            margin-top: 3px;
            position: relative;
        }

        #autocomplete-container,
        #autocomplete-input {
            position: relative;
            z-index: 101;
        }

        .main-search-input input,
        .main-search-input input:focus {
            font-size: 16px;
            border: none;
            background: #fff;
            margin: 0;
            padding: 0;
            height: 44px;
            line-height: 44px;
            box-shadow: none;
        }

        .input-with-icon i,
        .main-search-input-item.location a {
            padding: 5px 10px;
            z-index: 101;
        }

        .main-search-input-item.location a {
            position: absolute;
            right: -50px;
            top: 40%;
            transform: translateY(-50%);
            color: #999;
            padding: 10px;
        }

        .current-location {
            margin-right: 50px;
            margin-top: 5px;
            color: #FFCC00 !important;
        }

        .custom-map {
            width: 100%;
            height: 17rem;
        }

        .pac-container {
            width: 295px;
            position: absolute;
            left: 0px !important;
            top: 28px !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        var mapLat = '{{ $hub->hub_lat }}'
        var mapLong = '{{ $hub->hub_long }}'
    </script>
    <script type="text/javascript" src="{{ static_asset('backend/js/map/map-current.js') }}"></script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key={{ googleMapSettingKey() }}&libraries=places&callback=initMap">
    </script>
@endpush
