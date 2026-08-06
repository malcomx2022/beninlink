@extends('backend.partials.master')
@section('title')
   {{ __('user.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.user_role')}}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="breadcrumb-link">{{ __('user.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('user.edit_user') }}</h2>
                    <form action="{{route('users.update')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @if (isset($user))
                            @method('PUT')
                        @endif
                        <input type="hidden" name="id" value="{{$user->id}}">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('levels.name') }}</label> <span class="text-danger">*</span>
                                    <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{$user->name}}" require>
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="mobile">{{ __('levels.phone') }}</label> <span class="text-danger">*</span>
                                    <input id="mobile" type="number" name="mobile" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_mobile') }}" autocomplete="off" class="form-control @error('mobile') is-invalid @enderror" value="{{$user->mobile}}" require>
                                    @error('mobile')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="address">{{ __('levels.address') }}</label> <span class="text-danger">*</span>
                                    <input id="address" type="text" name="address" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_address') }}" autocomplete="off" class="form-control @error('address') is-invalid @enderror" value="{{$user->address}}" require>
                                    @error('address')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                @if($user->id != 1)
                                <div class="form-group">
                                    <label for="designation-select">{{ __('levels.designation') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control @error('designation_id') is-invalid @enderror" id="designation-select" name="designation_id" required>
                                        @foreach($designations as $designation)
                                        <option {{$user->designation_id == $designation->id ? 'selected':''}} value="{{$designation->id}}">{{$designation->title}}</option>
                                        @endforeach
                                    </select>
                                    @error('designation_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="department-select">{{ __('levels.department') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control @error('department_id') is-invalid @enderror" id="department-select" name="department_id" required>
                                        @foreach($departments as $department)
                                        <option {{$user->department_id == $department->id ? 'selected':''}} value="{{$department->id}}">{{$department->title}}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="input-select">{{ __('levels.role') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control @error('role_id') is-invalid @enderror" id="input-select" name="role_id" required>
                                        @foreach($roles as $role)
                                        <option value="{{$role->id}}" {{ (old('role_id',$user->role_id) == $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                                        @foreach(trans('status') as $key => $status)
                                            <option value="{{ $key }}" {{ (old('status',$user->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                @endif
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="email">{{ __('levels.email') }}</label> <span class="text-danger">*</span>
                                    <input id="email" type="text" name="email" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_email') }}" autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{$user->email}}" require>
                                    @error('email')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="password">{{ __('levels.password') }}</label>
                                    <input id="password" type="password" name="password" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_password') }}" autocomplete="off" class="form-control @error('password') is-invalid @enderror">
                                     @error('password')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                @if($user->id != 1)
                                <div class="form-group">
                                    <label for="nid_number">{{ __('levels.nid') }}</label>
                                    <input id="nid_number" type="number" name="nid_number" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_nid_number') }}" autocomplete="off" class="form-control @error('nid_number') is-invalid @enderror" value="{{$user->nid_number}}" require>
                                    @error('nid_number')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="joining_date">{{ __('levels.joining_date') }}</label> <span class="text-danger">*</span>
                                    <input id="joining_date" type="text" readonly="readonly" data-toggle="datepicker" name="joining_date" data-parsley-trigger="change" placeholder="yyyy-mm-dd" autocomplete="off" class="form-control @error('joining_date') is-invalid @enderror" value="{{$user->joining_date}}" require>
                                    @error('joining_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                @if (!isSuperadmin()) 
                                <div class="form-group pt-1">
                                    <label for="hub-select">{{ __('levels.hub') }}</label>
                                    <select class="form-control @error('hub_id') is-invalid @enderror" id="hub-select" name="hub_id">
                                        <option value="" >None</option>
                                        @foreach($hubs as $hub)
                                        <option {{$user->hub_id == $hub->id ? 'selected':''}} value="{{$hub->id}}">{{$hub->name}}</option>
                                        @endforeach
                                    </select>
                                    @error('hub_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                @endif
                                @endif
                                <div class="form-group pt-1">
                                    <label for="input-select">{{ __('levels.salary') }}</label>
                                    <input type="number" class="form-control" placeholder="{{ __('salary.title') }}"  value="{{ $user->salary }}" name="salary" />
                                    @error('salary')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div >
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-9">
                                            <label for="Image">{{ __('levels.image') }}</label>
                                            <input id="Image" type="file" name="image" data-parsley-trigger="change" placeholder="Enter Image" autocomplete="off" class="form-control">
                                        </div>
                                        <div class="col-3">
                                            <img src="{{$user->image}}" alt="user" class="rounded"  width="75" height="75" style="object-fit: contain">
                                        </div>
                                    </div>
                                    @error('image')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('users.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

