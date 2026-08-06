@extends('backend.partials.master')
@section('title')
    {{ __('levels.delivery_type') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('delivery-type.index') }}" class="breadcrumb-link">{{ __('DeliveryType.delivery_type') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- basic form -->
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('DeliveryType.edit_delivery_type') }}</h2>
                    <form action="{{route('delivery-type.update',['id'=>$edit_type->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf

                        <div class="row">
                            <div class="col-12">

                                <div class="form-group">
                                    <label for="title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
                                    <input id="title" type="text" name="title" data-parsley-trigger="change" placeholder="Enter Title" autocomplete="off" class="form-control" value="{{old('title',$edit_type->title)}}" require>
                                    @error('title')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="position">{{ __('levels.position') }}</label> <span class="text-danger">*</span>
                                    <input id="position" type="number" name="position" data-parsley-trigger="change" placeholder="Enter Position" autocomplete="off" class="form-control" value="{{old('position',$edit_type->position)}}" require>
                                    @error('position')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.submit') }}</button>
                                <a href="{{ route('delivery-type.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
@push('scripts')
    <script src="{{ static_asset('backend/js/deliveryCharge/delivery_charge.js') }}"></script>
@endpush

