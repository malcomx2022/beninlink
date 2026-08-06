@extends('backend.partials.master')
@section('title')
    {{ __('merchantmanage.title') }} {{ __('merchantmanage.payment') }}  {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href=" " class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href=" " class="breadcrumb-link">{{ __('merchantmanage.payment') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('merchantmanage.payment_process') }}</h2>
                    <form action="{{route('merchantmanage.payment.processed')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('PUT')
                        <input type="hidden" value="{{ $payment->id }}" name="id" />
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="transaction_id">{{ __('merchantmanage.transaction_id') }}</label> <span class="text-danger">*</span>
                                    <input id="transaction_id" type="text" name="transaction_id" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Transaction_ID') }}" autocomplete="off" class="form-control" value="{{old('transaction_id')}}"  require>
                                    @error('transaction_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="from_account">{{ __('merchantmanage.from_account') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control" name="from_account" >
                                        <option selected disabled>{{ __('menus.select') }} {{ __('merchantmanage.from_account') }}</option>
                                        @foreach ($accounts as $account)
                                            @if ($account->type == App\Enums\AccountType::ADMIN)
                                                @if ($account->gateway == 1)
                                                    <option value="{{ $account->id }}">{{ $account->user->name }} | {{ __('merchant.cash') }} : {{ $account->balance }}</option>
                                                @elseif($account->gateway == 3 || $account->gateway == 4 || $account->gateway == 5)
                                                    <option value="{{ $account->id }}">{{$account->account_holder_name}} |No : {{ $account->mobile }}|  @if($account->type == 1) {{ __('merchant.title') }} @else {{ __('placeholder.persional') }} @endif | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }} </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{$account->account_holder_name}} | A.No : {{ $account->account_no }} | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}</option>
                                                @endif
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('from_account')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="referance_file">{{ __('merchantmanage.reference_file') }}</label>
                                    <input id="referance_file" type="file" name="reference_file" data-parsley-trigger="change" autocomplete="off" class="form-control" value="{{ old('referance_file') }}"  >
                                </div>
                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.submit') }}</button>
                                <a href="{{ route('merchant.manage.payment.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
