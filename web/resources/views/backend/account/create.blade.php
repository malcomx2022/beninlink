@extends('backend.partials.master')
@section('title')
    {{ __('account.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('accounts.index') }}" class="breadcrumb-link">{{ __('account.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('account.create_account') }}</h2>
                    <form action="{{route('accounts.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="type">{{ __('levels.type') }}</label> <span class="text-danger" id="typeRequired"> @error('type') * @enderror</span>
                                    <select id="type" name="type" class="form-control @error('type') is-invalid @enderror">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.type') }}</option>
                                        @foreach(\config('rxcourier.account_type') as $key => $value)
                                            <option value="{{ $value }}" {{ (old('type') == $value) ? 'selected' : '' }}>{{ __('AccountType.'.$value)}}</option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="user">{{ __('levels.user') }}</label> <span class="text-danger" id="userRequired"> @error('user') * @enderror</span>
                                    <select id="user" name="user" class="form-control @error('user') is-invalid @enderror">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('user.title') }}</option>
                                        @foreach($users as $user)
                                            <option {{ (old('user') == $user->id) ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('user')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="gateway">{{ __('levels.gateway') }}</label> <span class="text-danger">*</span>
                                    <select id="gateway" name="gateway" class="form-control @error('gateway') is-invalid @enderror">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.gateway') }}</option>
                                        <option {{ (old('gateway') == 1) ? 'selected' : '' }} value = "1">Cash</option>
                                        <option {{ (old('gateway') == 2) ? 'selected' : '' }} value = "2">Bank</option>
                                        <option {{ (old('gateway') == 3) ? 'selected' : '' }} value = "3">bKash</option>
                                        <option {{ (old('gateway') == 4) ? 'selected' : '' }} value = "4">Rocket</option>
                                        <option {{ (old('gateway') == 5) ? 'selected' : '' }} value = "5">Nagad</option>
                                    </select>
                                    @error('gateway')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6" >
                                <div class="form-group" id="balance">
                                    <label for="balance">{{ __('levels.opening_balance') }}</label> <span class="text-danger">*</span>
                                    <input type="number" name="balance" data-parsley-trigger="change" placeholder="{{ __('placeholder.Opening_Balance') }}" autocomplete="off" class="form-control" value="{{old('balance')}}" require>
                                    @error('balance')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="account_holder_name">
                                    <label for="account_holder_name">{{ __('levels.account_holder_name') }}</label> <span class="text-danger">*</span>
                                    <input type="text" name="account_holder_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Account_Holder_Name') }}" autocomplete="off" class="form-control" value="{{old('account_holder_name')}}" require>
                                    @error('account_holder_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="account_no">
                                    <label for="account_no">{{ __('levels.account_no') }}</label> <span class="text-danger">*</span>
                                    <input type="number" name="account_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_account_no') }}" autocomplete="off" class="form-control" value="{{old('account_no')}}" require>
                                    @error('account_no')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="bank">
                                    <label for="bank">{{ __('levels.bank') }}</label> <span class="text-danger">*</span>
                                    <select name="bank" class="form-control">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('placeholder.Bank_name') }}</option>
                                        <option value = "1">BB</option>
                                        <option value = "2">DBBL</option>
                                        <option value = "3">IB</option>
                                    </select>
                                    @error('bank')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="branch_name">
                                    <label for="branch_name">{{ __('levels.branch_name') }}</label> <span class="text-danger">*</span>
                                    <input type="text" name="branch_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_branch_name') }}" autocomplete="off" class="form-control" value="{{old('branch_name')}}" require>
                                    @error('branch_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="opening_balance">
                                    <label for="opening_balance">{{ __('levels.opening_balance') }}</label> <span class="text-danger">*</span>
                                    <input type="number" name="opening_balance" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_opening_balance') }}" autocomplete="off" class="form-control" value="{{old('opening_balance')}}" require>
                                    @error('opening_balance')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="mobile">
                                    <label for="mobile">{{ __('levels.mobile') }}</label> <span class="text-danger">*</span>
                                    <input type="number" name="mobile" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_mobile') }}" autocomplete="off" class="form-control" value="{{old('mobile')}}" require>
                                    @error('mobile')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group" id="account_type">
                                    <label for="account_type">{{ __('levels.account_type') }}</label> <span class="text-danger">*</span>
                                    <select name="account_type" class="form-control">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.account_type') }}</option>
                                        <option value = "1" @if(old('account_type') == '1') selected @endif >{{ __('merchant.title') }}</option>
                                        <option value = "2" @if(old('account_type') == '2') selected @endif >{{ __('placeholder.persional') }}</option>
                                    </select>
                                    @error('account_type')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group  @if($errors->any()) d-block  @endif" id="accountStatus">
                                    <label for="status">{{__('levels.status')}}</label> <span class="text-danger">*</span>
                                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                                        @foreach(trans('status') as $key => $status)
                                            <option value="{{ $key }}" {{ (old('status',\App\Enums\Status::ACTIVE) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                            </div>
                        </div>
                        <div class="row " >
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('accounts.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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

@push('scripts')
    <script src="{{ static_asset('backend/js/account/account.js') }}"></script>
@endpush

