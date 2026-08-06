@extends('backend.partials.master')
@section('title')
   {{ __('menus.profile') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
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
                    <form action="{{route('profile.update',auth()->user()->id)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('levels.name') }}</label>
                                    <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="Enter name" autocomplete="off" class="form-control" value="{{auth()->user()->name}}" require>
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="address">{{ __('levels.address') }}</label>
                                    <input id="address" type="text" name="address" data-parsley-trigger="change" placeholder="Enter Address" autocomplete="off" class="form-control" value="{{auth()->user()->address}}" require>
                                    @error('address')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-8">
                                            <label for="Image">{{ __('levels.image') }}</label>
                                            <input id="Image" type="file" name="image" data-parsley-trigger="change" placeholder="Enter Image" autocomplete="off" class="form-control">
                                            @error('image')
                                                <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-4 mt-3 mt-md-0">
                                            <img src="{{auth()->user()->image}}" alt="user" class="rounded" width="100" style="aspect-ratio:1!important">
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
        <!-- eNd card form -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()

