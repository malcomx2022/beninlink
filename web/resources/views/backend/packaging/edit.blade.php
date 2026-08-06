@extends('backend.partials.master')
@section('title')
    {{ __('packaging.title') }}    {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('packaging.index') }}" class="breadcrumb-link">{{ __('packaging.title') }}</a></li>
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
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('packaging.edit_packaging') }}</h2>
                    <form action="{{ route('packaging.update') }}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <input type="hidden" name="id" id="id" value="{{$packaging->id}}">
                        <div class="form-group">
                            <label for="name">{{ __('levels.name') }}</label> <span class="text-danger">*</span>
                            <input id="name" placeholder="{{ __('placeholder.Enter_name') }}" type="text" name="name" data-parsley-trigger="change" autocomplete="off" class="form-control" value="{{$packaging->name}}" require>
                            @error('name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="price">{{ __('levels.price') }}</label> <span class="text-danger">*</span>
                            <input id="price" type="text" name="price" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_price') }}" autocomplete="off" class="form-control" value="{{$packaging->price}}">
                            @error('price')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                            <select class="form-control" id="status" name="status" value="{{ old('status') }}">
                                <option value="1" {{$packaging->status == 1 ? 'selected':''}}>{{ __('levels.active') }}</option>
                                <option value="0" {{$packaging->status == 0 ? 'selected':''}}>{{ __('levels.inactive') }}</option>
                            </select>
                            @error('status')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group pt-2">
                            <label for="position">{{ __('levels.position') }}</label>
                            <input id="position" type="number" name="position" data-parsley-trigger="change" autocomplete="off" placeholder="{{ __('placeholder.Enter_Position') }}" class="form-control" value="{{$packaging->position}}" require>
                            @error('position')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-9">
                                    <label for="Image">{{ __('levels.image') }}</label>
                                    <input id="Image" type="file" name="image" data-parsley-trigger="change" placeholder="Enter Image" autocomplete="off" class="form-control">
                                </div>
                                <div class="col-3">
                                    <img src="{{static_asset($packaging->image)}}" alt="user" class="rounded"  width="75" height="75">
                                </div>
                            </div>
                            @error('image')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('packaging.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
