@extends('backend.partials.master')
@section('title')
    {{ __('fund_transfer.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('account.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fund-transfer.index') }}" class="breadcrumb-link">{{ __('fund_transfer.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('fund_transfer.create_fund_transfer') }}</h2>
                    <form action="{{route('fund-transfer.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">

                                <div class="form-group">
                                    <label for="from_account">{{ __('levels.from_account') }}</label> <span class="text-danger">*</span>
                                    <select id="from_account" name="from_account" class="form-control">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.from_account') }}</option>
                                        @foreach($accounts as $account)
                                        @if( $account->gateway == 1 )
                                            <option {{ (old('from_account') == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">{{$account->user->name}} (Cash)</option>
                                        @else
                                            <option {{ (old('from_account') == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">
                                                @if($account->gateway == 3)
                                                    bKash ,
                                                @elseif ($account->gateway == 4)
                                                    Rocket ,
                                                @elseif ($account->gateway == 5)
                                                    Nagad ,
                                                @endif
                                                {{$account->account_holder_name}}
                                                 ({{$account->account_no}}
                                                 {{$account->branch_name}}
                                                 {{$account->mobile}})
                                                </option>
                                        @endif
                                        @endforeach
                                    </select>
                                    @error('from_account')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="to_account">{{ __('levels.to_account') }}</label> <span class="text-danger">*</span>
                                    <select id="to_account" name="to_account" class="form-control">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.to_account') }}</option>
                                        @foreach($accounts as $account)
                                            @if( $account->gateway == 1 )
                                                 <option {{ (old('to_account') == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">{{ $account->user->name }} (Cash)</option>
                                            @else
                                                 <option {{ (old('to_account') == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">
                                                    @if($account->gateway == 3)
                                                        bKash ,
                                                    @elseif ($account->gateway == 4)
                                                        Rocket ,
                                                    @elseif ($account->gateway == 5)
                                                        Nagad ,
                                                    @endif
                                                    {{$account->account_holder_name}}
                                                    ({{$account->account_no}}
                                                    {{$account->branch_name}}
                                                    {{$account->mobile}})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('to_account')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="description">{{ __('levels.description') }}</label>
                                    <textarea id="description" placeholder="{{ __('placeholder.Enter_description') }}" name="description" class="form-control">{{old('description')}}</textarea>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="amount">{{ __('levels.amount') }}</label> <span class="text-danger">*</span>
                                    <input type="number" id="amount" name="amount" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Amount') }}" autocomplete="off" class="form-control" value="{{old('amount')}}" require>
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                    <div class="check_message"></div>
                                    <p class="active h6" id="currentBalanceText">{{__('levels.current_balance')}}:</p> <span class="active h6" id="currentBalance"></span>
                                    <input type="hidden" placeholder="{{ __('placeholder.current_balance') }}" id="current_balance" name="current_balance" value="{{old('current_balance')}}">
                                </div>
                                <div class="form-group pt-1">
                                    <label for="date">{{ __('levels.date') }}</label> <span class="text-danger">*</span>
                                    <input type="text" readonly="readonly" data-toggle="datepicker" name="date" data-parsley-trigger="change" placeholder="yyyy-mm-dd" autocomplete="off" class="form-control" value="{{old('date',date('Y-m-d'))}}" require>
                                    @error('date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('fund-transfer.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
    <script src="{{ static_asset('backend/js/fund_transfer/custom.js') }}"></script>
@endpush

