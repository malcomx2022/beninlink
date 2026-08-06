@extends('backend.partials.master')
@section('title')
    {{ __('fraud.title') }}  {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.fraud.index') }}" class="breadcrumb-link">{{ __('fraud.title') }}</a></li>
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('fraud.create_fraud') }}</h2>
                    <form action="{{route('merchant-panel.fraud.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="phone">{{ __('levels.phone') }}</label> <span class="text-danger">*</span>
                                    <input id="phone" type="text" name="phone" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.phone') }}" autocomplete="off" class="form-control @error('phone') is-invalid @enderror" value="{{old('phone')}}" require>
                                    @error('phone')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="name">{{ __('levels.name') }}</label> <span class="text-danger">*</span>
                                    <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{old('name')}}" require>
                                    @error('name')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="tracking_id">{{ __('levels.track_id') }}</label>
                                    <input id="tracking_id" type="text" name="tracking_id" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.tracking_id') }}" autocomplete="off" class="form-control @error('tracking_id') is-invalid @enderror" value="{{old('tracking_id')}}">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="details">{{ __('levels.details') }}</label> <span class="text-danger">*</span>
                                    <textarea name="details" id="details" class="form-control" rows="6"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('merchant-panel.fraud.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
