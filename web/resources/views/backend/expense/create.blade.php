@extends('backend.partials.master')
@section('title')
    {{ __('expense.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('expense.index') }}" class="breadcrumb-link">{{ __('expense.title') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('expense.create_expense') }}</h2>
                    <form action="{{route('expense.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="account_head">{{ __('levels.account_heads')}}</label> <span class="text-danger">*</span>
                                    <select id="account_head" name="account_head" class="form-control" required>
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.account_heads') }}</option>
                                        @foreach($account_heads as $account_head)
                                            @if ($account_head->type == 2 && $account_head->status == 1)
                                                <option {{ (old('account_head') == $account_head->id) ? 'selected' : '' }} value="{{ $account_head->id }}">{{ $account_head->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('account_head')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group" id="title_group">
                                    <label for="title">{{ __('levels.title')}}</label> </label> <span class="text-danger">*</span>
                                    <input id="title" type="text" name="title" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_title') }}" autocomplete="on" class="form-control" value="{{ old('title') }}" require>
                                    @error('title')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <input type="hidden" name="parcel_id" id="parcel_id" value="">
                                    <label for="track_id">{{ __('levels.parcel')}}</label>
                                    <input id="transfer_to_hub_track_id_" type="text" name="track_id" placeholder="{{ __('placeholder.Enter_tracking_id') }}" class="form-control">
                                    <div class="search_message"></div>
                                    @error('track_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="date">{{ __('levels.date')}}</label> <span class="text-danger">*</span>
                                    <input type="text" id="date" data-toggle="datepicker" name="date" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',date('Y-m-d'))}}" required>
                                    @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="receipt">{{ __('levels.receipt')}}</label>
                                    <input id="receipt" type="file" name="receipt" data-parsley-trigger="change" placeholder="Enter receipt" autocomplete="off" class="form-control" value="{{ old('receipt') }}" require>
                                    @error('receipt')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group delivery_man_search">
                                    <label for="parcelDeliveryManID_">{{ __('parcel.deliveryman') }}</label> <span class="text-danger">*</span>
                                    <select style="width: 100%" id="parcelDeliveryManID_"  name="delivery_man_id" data-url="{{ route('parcel.deliveryman.search') }}" class="form-control @error('delivery_man_id') is-invalid @enderror">
                                        <option value=""> {{ __('menus.select') }} {{ __('deliveryman.title') }}</option>

                                    </select>
                                    @error('delivery_man_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group user_search" id="user_head">
                                    <label for="user_id">{{ __('levels.user') }}</label>
                                    <select style="width: 100%" id="user_id"  name="user_id" class="form-control @error('user_id') is-invalid @enderror" data-url="{{ route('expense.users') }}">
                                        <option value=""> {{ __('menus.select') }} {{ __('user.title') }}</option>
                                    </select>

                                    @error('user_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror

                                </div>
                                <div class="form-group">
                                    <label for="account_id">{{ __('levels.from_account')}}</label> <span class="text-danger">*</span>
                                    <select id="account_id" name="account_id" class="form-control" required>
                                        <option selected disabled>{{ __('menus.select') }} {{ __('placeholder.account') }}</option>
                                        @foreach($accounts as $account)
                                            @if ($account->type == App\Enums\AccountType::ADMIN)
                                                <option {{ (old('account_id') == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">
                                                    @if($account->gateway == 1)
                                                        {{$account->user->name}} (Cash)
                                                    @else
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
                                                    @endif
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('account_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                    <div class="text-danger mt-2" id="account_balance_">{{old('account_balance')}}</div>
                                    <input type="hidden" name="account_balance" value="{{old('account_balance')}}" id="account_balance">
                                </div>
                                <div class="form-group amount_div">
                                    <label for="amount">{{ __('levels.amount')}}</label> <span class="text-danger">*</span>
                                    <div class="form-control-wrap">
                                        <input type="number" class="form-control cash-collection" id="amount" value="{{ old('amount') }}" name="amount" placeholder="{{ __('placeholder.Enter_Amount') }}" required>
                                        @error('amount')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                        <div class="check_message"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="note">{{ __('levels.note')}}</label>
                                    <div class="form-control-wrap deliveryman-search">
                                       <textarea class="form-control" name="note" rows="5"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('expense.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
    <script>
        var merchantUrl = '{{ route('parcel.merchant.get') }}';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ static_asset('backend/js/expense/custom.js') }}"></script>
@endpush

