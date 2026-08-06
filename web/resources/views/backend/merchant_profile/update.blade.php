@extends('backend.partials.master')
@section('title')
    {{ __('menus.profile') }} {{ __('menus.update') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('dashboard.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ url()->previous() }}" class="breadcrumb-link">{{ __('menus.profile') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.update') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- card form -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('levels.update') }} {{ __('menus.profile') }}</h2>
                    <form action="{{route('merchant-profile.update',$merchat->user->id)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('levels.name') }}</label>
                                    <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="Enter name" autocomplete="off" class="form-control" value="{{$merchat->user->name}}" require>
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="email">{{ __('levels.email') }}</label>
                                    <input id="email" type="text" name="email" data-parsley-trigger="change" placeholder="Enter email" autocomplete="off" class="form-control" value="{{$merchat->user->email}}" require>
                                    @error('email')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="mobile">{{ __('levels.mobile') }}</label>
                                    <input id="mobile" type="text" name="mobile" data-parsley-trigger="change" placeholder="Enter mobile" autocomplete="off" class="form-control" value="{{$merchat->user->mobile}}" require>
                                    @error('mobile')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="business_name">{{ __('levels.business_name') }}</label>
                                    <input id="business_name" type="text" name="business_name" data-parsley-trigger="change" placeholder="Enter business name" autocomplete="off" class="form-control" value="{{$merchat->business_name}}" require>
                                    @error('business_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="address">{{ __('levels.address') }}</label>
                                    <input id="address" type="text" name="address" data-parsley-trigger="change" placeholder="Enter Address" autocomplete="off" class="form-control" value="{{$merchat->address}}" require>
                                    @error('address')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-12 col-md-9">
                                            <label for="image_id">{{ __('levels.image') }}</label>
                                            <input id="image_id" type="file" name="image_id" data-parsley-trigger="change"  autocomplete="off" class="form-control">
                                        </div>
                                        <div class="col-12 col-md-3 mt-3 mt-md-0">
                                            <img src="{{$merchat->user->image}}" alt="user" class="rounded" width="70" height = "70">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-12 col-md-9">
                                            <label for="nid">{{ __('levels.nid') }}</label>
                                            <input id="nid" type="file" name="nid" data-parsley-trigger="change" autocomplete="off" class="form-control">
                                        </div>
                                        <div class="col-12 col-md-3 pt-4">
                                            <img src="{{$merchat->nid}}" alt="user" class="rounded" width="70" height = "70">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-12 col-md-9">
                                            <label for="trade_license">{{ __('levels.trade_license') }}</label>
                                            <input id="trade_license" type="file" name="trade_license" data-parsley-trigger="change" autocomplete="off" class="form-control">
                                        </div>
                                        <div class="col-12 col-md-3 pt-4">
                                            <img src="{{$merchat->trade}}" alt="user" class="rounded" width="70" height = "70">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ url()->previous() }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end card form -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()

