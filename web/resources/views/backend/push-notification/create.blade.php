@extends('backend.partials.master')
@section('title')
   {{ __('push-notification.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('push-notification.index') }}" class="breadcrumb-link">{{ __('push-notification.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('push-notification.create_push_notification') }}</h2>
                    <form action="{{route('push-notification.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
                                    <input id="title" type="text" name="title" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_title') }}" autocomplete="off" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" require>
                                    @error('title')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="input-select">{{ __('levels.role') }}</label> <span class="text-danger">*</span>
                                    <select onchange="getUser(this.value)" class="form-control @error('role_id') is-invalid @enderror" id="input-select" name="role_id" required>
                                        <option value="all" {{ (old('role_id') == 'all') ? 'selected' : '' }}>{{ __('All Role') }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{$role->id}}" {{ (old('role_id') == $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group"  >
                                    <label for="user_id">{{ __('levels.user') }} </label>
                                    <select style="width: 100%" id="user_id"  name="user_id" class="form-control salary_user @error('user_id') is-invalid @enderror" data-url="{{ route('push-notification.users') }}">
                                        <option value="" > {{ __('menus.select') }} {{ __('user.title') }}</option>
                                    </select>
                                    @error('user_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="image">{{ __('levels.file') }}</label>
                                    <input id="image" type="file" name="image" data-parsley-trigger="change" placeholder="Enter file" autocomplete="off" class="form-control @error('image') is-invalid @enderror">
                                    @error('image')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="summernote">{{ __('levels.description') }}</label>
                                    <textarea  class="form-control" name="description" id="summernote" rows="12"></textarea>
                                    @error('description')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('push-notification.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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

@push('styles')
<link href="{{ static_asset('backend/vendor/summernote/css') }}/summernote-lite.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

@endpush
@push('scripts')
<script src="{{ static_asset('backend/vendor/summernote/js') }}/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
          $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: '{{ __('placeholder.Enter_description') }}',
                height: 182
            });
        }); 
    </script>
    <script src="{{ static_asset('backend/js/push-notification/custom.js') }}"></script>
@endpush
 