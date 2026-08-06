@extends('backend.partials.master')
@section('title')
    {{ __('levels.company') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="#"
                                        class="breadcrumb-link">{{ __('menus.company') }}</a></li>
                                <li class="breadcrumb-item"><a href=""
                                        class="breadcrumb-link active">{{ __('levels.create') }}</a></li>
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
                        <h2 class="pageheader-title">{{ __('levels.create') }} {{ __('levels.company') }}</h2>
                        <form action="{{ route('company.store') }}" method="POST" enctype="multipart/form-data"
                            id="basicform">
                            @csrf
                            <h4>{{ __('levels.company') }} {{ __('levels.information') }}</h4>
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="company_name">{{ __('levels.company') }}
                                            {{ __('levels.name') }}</label> <span class="text-danger">*</span>
                                        <input id="company_name" type="text" name="company_name"
                                            data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.Enter_company_name') }}" autocomplete="off"
                                            class="form-control @error('company_name') is-invalid @enderror"
                                            value="{{ old('company_name') }}" require>
                                        @error('company_name')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="domain">{{ __('levels.domain') }}  </label> <span class="text-danger">*</span>
                                    <div class="input-group  ">
                                        <span class="input-group-text start-domain" >{{ scheme_name() }}</span>
                                        <input id="domain" type="text" name="domain"
                                        data-parsley-trigger="change"
                                        placeholder="{{ __('placeholder.Enter_domain') }}" autocomplete="off"
                                        class="form-control @error('domain') is-invalid @enderror"
                                        value="{{ old('domain') }}" require>
                                        <span class="input-group-text end-domain" >{{ '.'.get_host() }}</span>
                                      </div>  
                                    @error('domain')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror 
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="currency">{{ __('levels.currency') }}</label>
                                        <select class="form-control select2 @error('currency') is-invalid @enderror" id="currency"
                                            name="currency" required>
                                            <option value="" selected disabled>Select Currency</option>
                                            @forelse ($currencies as $currency)
                                                <option value="{{ $currency->symbol }}">{{ @$currency->name }}
                                                    {{ @$currency->symbol }}</option>
                                            @empty
                                                <option value="&#36;">Dollar &#36;</option>
                                            @endforelse
                                        </select>
                                        @error('currency')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="currency">{{ __('settings.parcel_tracking') }}
                                                    {{ __('levels.prefix') }}</label>
                                                <input type="text" name="par_track_prefix" class="form-control"
                                                    placeholder="Enter Parcel Tracking Prefix" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="currency">{{ __('invoice.invoice') }}
                                                    {{ __('levels.prefix') }}</label>
                                                <input type="text" name="invoice_prefix" class="form-control"
                                                    placeholder="Enter Invoice Prefix" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3"> 
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="logo">{{ __('levels.logo') }} </label>
                                                <input id="logo" type="file" name="logo"
                                                    data-parsley-trigger="change" placeholder="Enter logo" autocomplete="off"
                                                    class="form-control @error('logo') is-invalid @enderror"
                                                    value="{{ old('logo') }}" require>
                                                @error('logo')
                                                    <small class="text-danger mt-2">{{ $message }}</small>
                                                @enderror
                                            </div> 
                                        </div>
                                    </div> 
                                </div>

                                <div class="col-12 col-md-3">
                                    <div class="form-group">
                                        <label for="input-select">{{ __('levels.plan') }}</label> <span
                                            class="text-danger">*</span>
                                        <select class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" required>
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}" > {{ $plan->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('plan_id')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>


                            </div>

                            <h4>{{ __('levels.user') }} {{ __('levels.information') }}</h4>
                            <div class="row">
                                <div class="col-12 col-md-6">

                                    <div class="form-group">
                                        <label for="name">{{ __('levels.name') }}</label> <span
                                            class="text-danger">*</span>
                                        <input id="name" type="text" name="name" data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name') }}" require>
                                        @error('name')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="mobile">{{ __('levels.phone') }}</label> <span
                                            class="text-danger">*</span>
                                        <input id="mobile" type="number" name="mobile" data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.Enter_mobile') }}" autocomplete="off"
                                            class="form-control @error('mobile') is-invalid @enderror"
                                            value="{{ old('mobile') }}" require>
                                        @error('mobile')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="address">{{ __('levels.address') }}</label> <span
                                            class="text-danger">*</span>
                                        <input id="address" type="text" name="address"
                                            data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.Enter_address') }}" autocomplete="off"
                                            class="form-control @error('address') is-invalid @enderror"
                                            value="{{ old('address') }}" require>
                                        @error('address')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="input-select">{{ __('levels.designation') }}</label> <span
                                            class="text-danger">*</span>
                                        <select class="form-control @error('designation_id') is-invalid @enderror"
                                            id="input-select" name="designation_id" required>
                                            @foreach ($designations as $designation)
                                                <option value="{{ $designation->id }}"
                                                    {{ old('designation_id') == $designation->id ? 'selected' : '' }}>
                                                    {{ $designation->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('designation_id')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="input-select">{{ __('levels.department') }}</label> <span
                                            class="text-danger">*</span>
                                        <select class="form-control @error('department_id') is-invalid @enderror"
                                            id="input-select" name="department_id" required>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="status">{{ __('levels.status') }}</label>
                                        <select name="status" id="status"
                                            class="form-control @error('status') is-invalid @enderror">
                                            @foreach (trans('status') as $key => $status)
                                                <option value="{{ $key }}"
                                                    {{ old('status', \App\Enums\Status::ACTIVE) == $key ? 'selected' : '' }}>
                                                    {{ $status }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                </div>
                                <div class="col-12 col-md-6">

                                    <div class="form-group">
                                        <label for="email">{{ __('levels.email') }}</label> <span
                                            class="text-danger">*</span>
                                        <input id="email" type="email" name="email"
                                            data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.enter_email') }}" autocomplete="off"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email') }}" require>
                                        @error('email')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="password">{{ __('levels.password') }}</label> <span
                                            class="text-danger">*</span>
                                        <input id="password" type="password" name="password"
                                            data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.Enter_password') }}" autocomplete="off"
                                            class="form-control @error('password') is-invalid @enderror"
                                            value="{{ old('password') }}" require>
                                        @error('password')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="nid_number">{{ __('levels.nid') }}</label>
                                        <input id="nid_number" type="number" name="nid_number"
                                            data-parsley-trigger="change"
                                            placeholder="{{ __('placeholder.Enter_nid_number') }}" autocomplete="off"
                                            class="form-control @error('nid_number') is-invalid @enderror"
                                            value="{{ old('nid_number') }}" require>
                                        @error('nid_number')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="joining_date">{{ __('levels.opening_date') }}</label> <span
                                            class="text-danger">*</span>
                                        <input id="joining_date" type="text" readonly="readonly"
                                            data-toggle="datepicker" name="joining_date" data-parsley-trigger="change"
                                            placeholder="yyyy-mm-dd" autocomplete="off"
                                            class="form-control @error('joining_date') is-invalid @enderror"
                                            value="{{ old('joining_date', date('Y-m-d')) }}" require>
                                        @error('joining_date')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                             
                                     
                                    <div class="form-group">
                                        <label for="image">{{ __('levels.image') }}</label>
                                        <input id="image" type="file" name="image"
                                            data-parsley-trigger="change" placeholder="Enter image" autocomplete="off"
                                            class="form-control @error('image') is-invalid @enderror"
                                            value="{{ old('image') }}" require>
                                        @error('image')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                    <button type="submit"
                                        class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                    <a href="{{ route('users.index') }}"
                                        class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts') 
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush