@extends('backend.partials.master')
@section('title')
    {{ __('incharge.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('incharge.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hubs.index') }}" class="breadcrumb-link">{{ __('hubs.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hub-incharge.index',$hub->id) }}" class="breadcrumb-link">{{ __('incharge.title') }}</a></li>
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
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title"> {{ __('incharge.edit') }} {{ $hub->name }} {{ __('incharge.title') }}</h2>
                    <form action="{{route('hub-incharge.update',[$hub->id,$inCharge])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('PUT')
                        <div class="row">
                           <div class="col-md-6">
                               <div class="form-group">
                                   <label for="user_id">{{ __('levels.user') }}</label>
                                   <select name="user_id" class="form-control @error('user_id') is-invalid @enderror">
                                       @if(!blank($users))
                                           @foreach($users as $key => $user)
                                               <option value="{{ $user->id }}" {{ (old('user_id',$inCharge->user_id) == $user->id) ? 'selected' : '' }}>{{ $user->name }} ( {{ $user->mobile }})</option>
                                           @endforeach
                                       @else
                                           <option value="">{{ __('-- Select User --') }}</option>
                                       @endif
                                   </select>
                                   @error('user_id')
                                   <small class="text-danger mt-2">{{ $message }}</small>
                                   @enderror
                               </div>
                               <div class="form-group">
                                   <label for="status">{{ __('levels.status') }}</label>
                                   <select name="status" class="form-control @error('status') is-invalid @enderror">
                                       @foreach(trans('status') as $key => $status)
                                           <option value="{{ $key }}" {{ (old('status',$inCharge->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                       @endforeach
                                   </select>
                                   @error('status')
                                   <small class="text-danger mt-2">{{ $message }}</small>
                                   @enderror
                               </div>
                           </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('hub-incharge.index',$hub->id) }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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

