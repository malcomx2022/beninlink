@extends('backend.partials.master')
@section('title')
   {{ __('settings.currency') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('currency.index') }}" class="breadcrumb-link">{{ __('settings.currency') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('asset_category.assetc_edit') }}</h2>
                    <form action="{{route('currency.update',['id'=>$currency->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="form-group">
                            <label for="name">{{ __('levels.name') }}</label>	<span class="text-danger">*</span>
                            <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror " value="{{ old('name',$currency->name) }}" require>
                            @error('name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group pt-2">
                            <label for="symbol">{{ __('settings.symbol') }} <span class="text-danger">*</span></label>
                            <input id="symbol" type="text" name="symbol" data-parsley-trigger="change" placeholder="{{ __('settings.enter_symbol') }}" autocomplete="off" class="form-control" value="{{old('symbol',$currency->symbol)}}" >
                            @error('symbol')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group pt-2">
                            <label for="exchange_rate">{{ __('settings.exchange_rate') }} <span class="text-danger">*</span></label>
                            <input id="exchange_rate" type="text" name="exchange_rate" data-parsley-trigger="change" placeholder="{{ __('settings.enter_exchange_rate') }}" autocomplete="off" class="form-control" value="{{old('exchange_rate',$currency->exchange_rate)}}" >
                            @error('exchange_rate')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group pt-2">
                            <label for="position">{{ __('levels.position') }}</label>
                            <input id="position" type="number" name="position" data-parsley-trigger="change" autocomplete="off" class="form-control" placeholder="{{ __('placeholder.Enter_Position') }}" value="{{ old('position',$currency->position) }}" require>
                            @error('position')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                @foreach(trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ (old('status',$currency->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('currency.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()
