@extends('backend.partials.master')
@section('title')
    {{ __('delivery_charge.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('delivery-charge.index') }}" class="breadcrumb-link">{{ __('delivery_charge.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('delivery_charge.create_delivery_charge') }}</h2>
                    <form action="{{route('delivery-charge.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="category">{{ __('levels.category') }}</label> <span class="text-danger">*</span>
                                    <select id="category" name="category" class="form-control @error('category') is-invalid @enderror">
                                        <option selected disabled>{{ __('menus.select') }}</option>
                                        @foreach($categories as $category)
                                            <option {{ (old('category') == $category->id) ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group" id="weight_group">
                                    <label for="weight">{{ __('levels.weight') }}</label> <span class="text-danger">*</span>
                                    <input id="weight" type="number" name="weight" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_weight') }}" autocomplete="off" class="form-control" value="{{old('weight')}}" require>
                                    @error('weight')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="same_day">{{ __('levels.same_day') }}</label> <span class="text-danger">*</span>
                                    <input id="same_day" type="number" name="same_day" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_same_day') }}" autocomplete="off" class="form-control" value="{{old('same_day')}}" require>
                                    @error('same_day')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="next_day">{{ __('levels.next_day') }}</label> <span class="text-danger">*</span>
                                    <input id="next_day" type="number" name="next_day" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_next_day') }}" autocomplete="off" class="form-control" value="{{old('next_day')}}" require>
                                    @error('next_day')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                                        @foreach(trans('status') as $key => $status)
                                            <option value="{{ $key }}" {{ (old('status',\App\Enums\Status::ACTIVE) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="sub_city">{{ __('levels.sub_city') }}</label> <span class="text-danger">*</span>
                                    <input id="sub_city" type="number" name="sub_city" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_sub_city') }}" autocomplete="off" class="form-control" value="{{old('sub_city')}}" require>
                                    @error('sub_city')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="outside_city">{{ __('levels.outside_city') }}</label> <span class="text-danger">*</span>
                                    <input id="outside_city" type="number" name="outside_city" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_outside_city') }}" autocomplete="off" class="form-control" value="{{old('outside_city')}}" require>
                                    @error('outside_city')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="position">{{ __('levels.position') }}</label> <span class="text-danger">*</span>
                                    <input id="position" type="number" name="position" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Position') }}" autocomplete="off" class="form-control" value="{{old('position')}}" require>
                                    @error('position')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('delivery-charge.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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

