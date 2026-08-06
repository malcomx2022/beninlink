@section('title')
    {{ __('merchant.title') }} {{ __('merchant.delivery_charge') }} {{ __('levels.edit') }}
@endsection
@extends('backend.merchant.view')
@section('backend.merchant.layout.list')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant.shops.index',$singleMerchant->id) }}" class="breadcrumb-link">{{ __('merchantshops.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 class="pageheader-title">{{ __('merchant.edit_delivery_charge') }}</h2>
            <form action="{{route('merchant.deliveryCharge.update',[$singleMerchant->id,$merchantDeliveryCharge->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="form-group col-6">
                        <label for="delivery_charge_id">{{ __('levels.category') }}</label> <span class="text-danger">*</span>
                        <select id="deliveryChargeID" name="delivery_charge_id" class="form-control @error('delivery_charge_id') is-invalid @enderror" data-url="{{ route('merchant.deliveryCharge.deliveryChargeInfo') }}">
                            @foreach($deliveryCharges as $deliverycharge)
                                <option value="{{ $deliverycharge->id }}" {{ (old('delivery_charge_id',$merchantDeliveryCharge->delivery_charge_id) == $deliverycharge->id) ? 'selected' : '' }}>{{ $deliverycharge->category->title }} @if(isset($deliverycharge->weight)) ( {{ $deliverycharge->weight }} ) @endif</option>
                            @endforeach
                        </select>
                        @error('delivery_charge_id')
                        <small class="text-danger mt-2">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div id="deliveryChargeInfo">
                    <div class="row">
                        <div class="form-group col-6">
                            <label for="same_day">{{ __('levels.same_day') }}</label> <span class="text-danger">*</span>
                            <input id="same_day" type="number" name="same_day" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_same_day') }}" autocomplete="off" class="form-control" value="{{old('same_day',$merchantDeliveryCharge->same_day)}}" require>
                            @error('same_day')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-6">
                            <label for="next_day">{{ __('levels.next_day') }}</label> <span class="text-danger">*</span>
                            <input id="next_day" type="number" name="next_day" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_next_day') }}" autocomplete="off" class="form-control" value="{{old('next_day',$merchantDeliveryCharge->next_day)}}" require>
                            @error('next_day')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-6">
                            <label for="sub_city">{{ __('levels.sub_city') }}</label> <span class="text-danger">*</span>
                            <input id="sub_city" type="number" name="sub_city" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_sub_city') }}" autocomplete="off" class="form-control" value="{{old('sub_city',$merchantDeliveryCharge->sub_city)}}" require>
                            @error('sub_city')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-6">
                            <label for="outside_city">{{ __('levels.outside_city') }}</label> <span class="text-danger">*</span>
                            <input id="outside_city" type="number" name="outside_city" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_outside_city') }}" autocomplete="off" class="form-control" value="{{old('outside_city',$merchantDeliveryCharge->outside_city)}}" require>
                            @error('outside_city')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-6">
                            <label for="status">{{__('levels.status')}}</label> <span class="text-danger">*</span>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                @foreach(trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ (old('status',$merchantDeliveryCharge->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <button type="submit" class="btn btn-space btn-primary">{{ __('levels.submit') }}</button>
                            <a href="{{ route('merchant.deliveryCharge.index',$singleMerchant->id) }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection()
@push('scripts')
    <script src="{{ static_asset('backend/js/merchantDeliveryCharge/edit.js') }}"></script>
@endpush

