@extends('backend.partials.master')
@section('title')
    {{ __('asset.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('asset.index') }}" class="breadcrumb-link">{{ __('asset.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('asset.asset_edit') }}</h2>
                    <form action="{{route('asset.update',['id'=>$assets->id])}}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="title">{{ __('asset.name') }}</label> <span class="text-danger">*</span>
                                    <input id="title" placeholder="{{ __('placeholder.Enter_name') }}" type="text" name="name" data-parsley-trigger="change" class="form-control" value="{{ $assets->name }}">
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="input-select">{{ __('asset.assetcategory_id') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control" id="input-select" name="assetcategory_id" value="{{old('assetcategory_id')}}">
                                        <option disabled selected>{{ __('menus.select') }} {{ __('asset_category.title_name') }}</option>
                                        @foreach($assetcategorys as $assetcategory)
                                            <option {{ (old('assetcategory_id',$assets->assetcategory_id) == $assetcategory->id) ? 'selected' : '' }} value="{{$assetcategory->id}}">{{$assetcategory->title}}</option>
                                        @endforeach
                                    </select>
                                    @error('assetcategory_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="input-select">{{ __('asset.hub_id') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control" id="input-select" name="hub_id" value="{{ old('hub') }}">
                                        <option disabled selected>{{ __('menus.select') }} {{ __('hub.title') }}</option>
                                        @foreach($hubs as $hub)
                                            <option {{ (old('hub_id',$assets->hub_id) == $hub->id) ? 'selected' : '' }} value="{{$hub->id}}">{{$hub->name}}</option>
                                        @endforeach
                                    </select>

                                    @error('hub')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="supplyer_name">{{ __('asset.supplyer_name') }}</label>
                                    <input id="supplyer_name" type="text" name="supplyer_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Supplyer_Name') }}" class="form-control" value="{{ $assets->supplyer_name }}">
                                    @error('supplyer_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="quantity">{{ __('asset.quantity') }}</label>
                                    <input id="quantity" type="text" name="quantity" data-parsley-trigger="change" class="form-control" placeholder="{{ __('placeholder.Enter_Quantity') }}" value="{{ $assets->quantity }}">
                                    @error('quantity')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>


                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="warranty">{{ __('asset.warranty') }}</label>
                                    <input id="warranty" type="text" placeholder="{{ __('placeholder.Enter_Warranty') }}" name="warranty" data-parsley-trigger="change" class="form-control" value="{{ $assets->warranty }}">
                                    @error('warranty')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="invoice_no">{{ __('asset.invoice_no') }}</label>
                                    <input id="invoice_no" type="text" name="invoice_no" placeholder="{{ __('placeholder.Enter_Invoice_No') }}" class="form-control @error('placeholder.invoice_no') is-invalid @enderror" value="{{ $assets->invoice_no }}">
                                    @error('invoice_no')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="amount">{{ __('asset.amount') }}</label> <span class="text-danger">*</span>
                                <input id="amount" type="number" placeholder="{{ __('placeholder.Enter_Amount') }}" name="amount" class="form-control" value="{{ $assets->amount }}">
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">{{ __('levels.description') }}</label>
                                    <textarea  class="form-control" placeholder="{{ __('placeholder.Enter_description') }}" name="description" id="description" rows="5">{{ $assets->description }}</textarea>
                                    @error('description')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('asset.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
