@extends('backend.partials.master')
@section('title')
    {{ __('hub_payment.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href=" {{ route('hub.hub-payment.index') }}" class="breadcrumb-link">{{ __('hub_payment.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.process') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('hub_payment.payment_process') }}</h2>
                    <form action="{{route('hub-payment.processed')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('PUT')
                        <input type="hidden" value="{{ $payment->id }}" name="id" />
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="transaction_id">{{ __('hub_payment.transaction_id') }}</label> <span class="text-danger">*</span>
                                    <input id="transaction_id" type="text" name="transaction_id" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Transaction_ID') }}" autocomplete="off" class="form-control" value="{{old('transaction_id')}}" require>
                                    @error('transaction_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="from_account">{{ __('hub_payment.from_account') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control" name="from_account" >
                                        <option selected disabled>{{ __('menus.select') }} {{ __('hub_payment.from_account') }}</option>
                                        @foreach ($accounts as $account)
                                            @if ($account->gateway == 1)
                                                <option value="{{ $account->id }}">{{ $account->user->name }} | {{ __('merchant.cash') }}</option>
                                            @elseif($account->gateway == 3 || $account->gateway == 4 || $account->gateway == 5)
                                                <option value="{{ $account->id }}">{{$account->account_holder_name}} |No : {{ $account->mobile }}|  @if($account->type == 1) Merchant @else Persional @endif | {{ __('hub_payment.current_balance') }}: {{ $account->balance }} </option>
                                            @else
                                                <option value="{{ $account->id }}">{{$account->account_holder_name}} | A.No : {{ $account->account_no }} | {{ __('hub_payment.current_balance') }}: {{ $account->balance }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('from_account')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="reference_file">{{ __('hub_payment.reference_file') }}</label>
                                    <input id="reference_file" type="file" name="reference_file" data-parsley-trigger="change" autocomplete="off" class="form-control" value="{{ old('reference_file') }}"  >
                                </div>
                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.submit') }}</button>
                                <a href="{{ route('hub.hub-payment.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
