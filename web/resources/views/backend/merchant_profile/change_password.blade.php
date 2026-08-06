@extends('backend.partials.master')
@section('title')
    {{ __('menus.profile') }} {{ __('menus.change_password') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('menus.change_password')}}</a></li>
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
                    <h2 class="pageheader-title">{{ __('menus.change_password') }}</h2>
                    <form action="{{route('merchant-profile.password.update',$merchat->user->id)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="old_password">{{ __('levels.old_password') }}</label>
                                    <input id="old_password" type="password" name="old_password" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_old_password' ) }}" autocomplete="off" class="form-control" value="{{old('old_password')}}" require>
                                    @error('old_password')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="new_password">{{ __('levels.new_password') }}</label>
                                    <input id="new_password" type="password" name="new_password" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_new_password' ) }}" autocomplete="off" class="form-control" value="{{old('new_password')}}" require>
                                    @error('new_password')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">{{ __('levels.confirm_password') }}</label>
                                    <input id="confirm_password" type="password" name="confirm_password" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_confirm_password' ) }}" autocomplete="off" value="{{old('confirm_password')}}" class="form-control" require>
                                    @error('confirm_password')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
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
