@extends('backend.partials.master')
@section('title')
    {{ __('asset_category.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('asset-category.index') }}" class="breadcrumb-link">{{ __('asset_category.title_name') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.create') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('asset_category.assetc_add') }}</h2>
                    <form action="{{route('asset-category.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="form-group">
                            <label for="title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
                            <input id="title" type="text" name="title" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_title') }}" autocomplete="off" class="form-control @error('title') is-invalid @enderror" value="{{old('title')}}" require>
                            @error('title')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group pt-2">
                            <label for="position">{{ __('levels.position') }}</label>
                            <input id="position" type="number" name="position" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Position') }}" autocomplete="off" class="form-control" value="{{old('position')}}" >
                            @error('position')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('delivery-category.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end basic form -->
    </div>
</div>
@endsection()

