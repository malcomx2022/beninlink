@extends('backend.partials.master')
@section('title')
    {{ __('asset.title') }} {{ __('levels.add') }}
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
                    <h2 class="pageheader-title">{{ __('asset.asset_add') }}</h2>
                    <form action="{{route('asset.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="title">{{ __('asset.name') }}</label> <span class="text-danger">*</span>
                                    <input id="title" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" require>
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="input-select">{{ __('asset.assetcategory_id') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control @error('assetcategory_id') is-invalid @enderror" id="input-select" name="assetcategory_id" required>
                                        <option disabled selected>{{ __('menus.select') }} {{ __('asset_category.title_name') }}</option>
                                        @foreach($assetcategorys as $assetcategory)
                                            <option value="{{$assetcategory->id}}" {{ (old('assetcategory_id') == $assetcategory->id) ? 'selected' : '' }}>{{$assetcategory->title}}</option>
                                        @endforeach
                                    </select>
                                    @error('assetcategory_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="input-select">{{ __('asset.hub_id') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control @error('hub_id') is-invalid @enderror" id="input-select" name="hub_id"  required>
                                        <option disabled selected>{{ __('menus.select') }} {{ __('hub.title') }}</option>
                                        @foreach($hubs as $hub)
                                            <option value="{{$hub->id}}" {{ (old('hub_id') == $hub->id) ? 'selected' : '' }}>{{$hub->name}}</option>
                                        @endforeach
                                    </select>
                                    @error('hub_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="supplyer_name">{{ __('asset.supplyer_name') }}</label>
                                    <input id="supplyer_name" type="text" name="supplyer_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Supplyer_Name') }}" autocomplete="off" class="form-control @error('supplyer_name') is-invalid @enderror" value="{{ old('supplyer_name') }}">
                                    @error('supplyer_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="quantity">{{ __('asset.quantity') }}</label>
                                    <input id="quantity" type="text" name="quantity" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Quantity') }}" autocomplete="off" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}">
                                    @error('quantity')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>


                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="warranty">{{ __('asset.warranty') }}</label>
                                    <input id="warranty" type="text" name="warranty" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Warranty') }}" autocomplete="off" class="form-control @error('warranty') is-invalid @enderror" value="{{ old('warranty') }}">
                                    @error('warranty')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="invoice_no">{{ __('asset.invoice_no') }}</label>
                                    <input id="invoice_no" type="text" name="invoice_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Invoice_No') }}" autocomplete="off" class="form-control @error('invoice_no') is-invalid @enderror" value="{{ old('invoice_no') }}">
                                    @error('invoice_no')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="amount">{{ __('asset.amount') }}</label> <span class="text-danger">*</span>
                                    <input id="amount" type="number" name="amount" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Amount') }}" autocomplete="off" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}">
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">{{ __('levels.description') }}</label>
                                    <textarea placeholder="{{ __('placeholder.Enter_description') }}" autocomplete="off" class="form-control" name="description" id="description" rows="5"></textarea>
                                    @error('description')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
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
@endsection()


