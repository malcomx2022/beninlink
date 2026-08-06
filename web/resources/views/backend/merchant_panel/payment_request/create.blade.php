@extends('backend.partials.master')
@section('title')
    {{ __('paymentrequest.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href=" " class="breadcrumb-link">{{ __('paymentrequest.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('paymentrequest.submit_request') }}</h2>
                    <form action="{{route('merchant-panel.payment-request.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <h4 class="active">{{__('levels.current_balance')}}: {{$merchant->current_balance}}</h4>
                                <div class="form-group">
                                    <label for="amount">{{ __('merchantmanage.amount') }}</label> <span class="text-danger">*</span>
                                    <input id="amount" type="number" name="amount" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.amount') }}" autocomplete="off" class="form-control" value="{{old('amount')}}" require>
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="merchant">{{ __('paymentrequest.account') }}</label> <span class="text-danger">*</span>
                                    <select id="merchant_account" class="form-control" name="merchant_account" >
                                        <option selected disabled>{{ __('merchantPlaceholder.select_account') }}</option>
                                        @foreach ($merchantaccounts as $account)
                                            @if($account->payment_method == 'bank')
                                                <option value='{{ $account->id }}'>{{ $account->holder_name }},{{$account->bank_name }},{{ $account->account_no }},{{ $account->branch_name }}</option>
                                            @elseif($account->payment_method == 'mobile')
                                                <option value='{{ $account->id }}'>{{ $account->mobile_company }},{{ $account->holder_name }},{{ $account->mobile_no }},{{ $account->account_type }}</option>
                                            @elseif ($account->payment_method == 'cash')
                                                <option value='{{ $account->id }}'>{{ __('merchant.'.$account->payment_method) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('merchant_account')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="description">{{ __('merchantmanage.description') }}</label>
                                    <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                                </div>
                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('merchant-panel.payment-request.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
     <script src="{{ static_asset('backend/js/merchantmanaage/create.js') }}"></script>
@endpush

