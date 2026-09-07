@extends('backend.partials.master')
@section('title')
    {{ __('delivery_charge.title') }} {{ __('levels.edit') }}
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('delivery_charge.edit_delivery_charge') }}</h2>
                    <form action="{{route('delivery-charge.update')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-6">
                                <input type="hidden" name="id" id="id" value="{{$delivery_charge->id}}">
                                <div class="form-group">
                                    <label for="category">{{ __('levels.category') }}</label> <span class="text-danger">*</span>
                                    <select id="category" name="category" class="form-control @error('category') is-invalid @enderror">
                                        @foreach($categories as $category)
                                            <option {{ old('category',$delivery_charge->category_id) == $category->id ? 'selected':'' }} value="{{ $category->id }}">{{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group" id="weight_group">
                                    <label for="weight">{{ __('levels.weight') }}</label> <span class="text-danger">*</span>
                                    <input id="weight" type="number" name="weight" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_weight') }}" autocomplete="off" class="form-control" value="{{ old('weight',$delivery_charge->weight) }}" require>
                                    @error('weight')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="zone">{{ __('delivery_zone.zone') }}</label> <span class="text-danger">*</span>
                                    <select id="zone" name="zone" class="form-control @error('zone') is-invalid @enderror">
                                        <option value="">{{ __('levels.select') }}</option>
                                        @foreach($zones as $zone)
                                            <option {{ (old('zone', $delivery_charge->zone_id) == $zone->id) ? 'selected' : '' }} value="{{ $zone->id }}">{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('zone')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="amount">{{ __('delivery_zone.amount') }}</label> <span class="text-danger">*</span>
                                    <input id="amount" type="number" name="amount" data-parsley-trigger="change" placeholder="{{ __('delivery_zone.amount') }}" autocomplete="off" class="form-control" value="{{ old('amount', $delivery_charge->amount) }}" require>
                                    @error('amount')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                                        @foreach(trans('status') as $key => $status)
                                            <option value="{{ $key }}" {{ (old('status',$delivery_charge->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="position">{{ __('levels.position') }}</label> <span class="text-danger">*</span>
                                    <input id="position" type="number" name="position" data-parsley-trigger="change" autocomplete="off" placeholder="{{ __('placeholder.Enter_Position') }}" class="form-control" value="{{ old('position',$delivery_charge->position) }}" require>
                                    @error('position')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
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
<!-- end wrapper  -->
@endsection()

@push('scripts')
    <script src="{{ static_asset('backend/js/deliveryCharge/delivery_charge.js') }}"></script>
@endpush

