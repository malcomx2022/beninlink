@extends('backend.partials.master')
@section('title')
    {{ __('hub_payment.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href=" {{ route('hub.hub-payment.index') }}" class="breadcrumb-link">{{ __('hub.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('hub_payment.create_payment') }}</h2>
                    <form action="{{route('hub.hub-payment.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="hub_id">{{ __('hub.title') }}</label> <span class="text-danger">*</span>
                                    <select  id="hub_id" class="form-control d" name="hub_id">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('hub.title') }}</option>
                                        @foreach ($hubs as $hub)
                                            <option value="{{ $hub->id }}">{{ $hub->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('hub_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="amount">{{ __('hub_payment.amount') }}</label> <span class="text-danger">*</span>
                                    <input id="amount" type="number" name="amount" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Amount') }}" autocomplete="off" class="form-control  @error('amount') is-invalid @enderror" value="{{old('amount')}}" require>
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" value="1" id="isprocess" name="isprocess"  @if ($errors->has('transaction_id') || $errors->has('from_account'))
                                       checked @endif/> {{ __('hub.is_processed ') }}?</label>
                                </div>
                                <div class="process" @if ($errors->has('transaction_id') || $errors->has('from_account'))
                                    @else style="display: none" @endif>

                                    <div class="form-group">
                                        <label for="transaction_id">{{ __('hub_payment.transaction_id') }}</label> <span class="text-danger">*</span>
                                        <input id="transaction_id" type="text" name="transaction_id" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Transaction_ID') }}" autocomplete="off" class="form-control @error('transaction_id') is-invalid @enderror" value="{{old('transaction_id')}}" require>
                                        @error('transaction_id')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="from_account">{{ __('hub_payment.from_account') }}</label> <span class="text-danger">*</span>
                                        <select class="form-control @error('from_account') is-invalid @enderror" name="from_account" >
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
                                        <label for="referance_file">{{ __('hub_payment.reference_file') }}</label>
                                        <input id="reference_file" type="file" name="reference_file" data-parsley-trigger="change" autocomplete="off" class="form-control" value="{{ old('reference_file') }}"  >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">{{ __('hub_payment.description') }}</label> <span class="text-danger">*</span>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" require>{{ old('description') }}</textarea>
                                    @error('description')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
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

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
     <script src="{{ static_asset('backend/js/merchantmanaage/create.js') }}"></script>
@endpush

