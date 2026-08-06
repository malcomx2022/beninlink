@extends('backend.partials.master')
@section('title')
    {{ __('incharge.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('income.index') }}" class="breadcrumb-link">{{ __('income.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
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
                    <h2 class="pageheader-title">{{ __('income.edit_income') }}</h2>
                    <form action="{{route('income.update',$income->id)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="account_head">{{ __('levels.account_heads') }}</label> <span class="text-danger">*</span>
                                    <select id="account_head" name="account_head_id" class="form-control" required>
                                        <option value="0" selected disabled>{{ __('menus.select') }} {{ __('levels.account_heads') }}</option>

                                        @foreach ($accountHeads as $head)

                                            <option value = "{{$head->id }}" @if(old('account_head_id') == $head->id || $income->account_head_id == $head->id) selected @endif >{{ $head->name }}  </option>
                                        @endforeach
                                    </select>
                                    @error('account_head')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group" id="head_title" style="@if($errors->has('title') || $income->account_head_id == 3) display:block @else display:none @endif">
                                    <label for="title">{{ __('levels.title')}} <span class="text-danger">*</span></label>
                                    <input id="title" type="text" name="title" placeholder="{{ __('placeholder.Enter_title') }}" class="form-control" value="{{ @$income->title }}">

                                    @error('title')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="parcel_id" id="parcel_id" value="{{ $income->parcel_id }}">
                                    <label for="track_id">{{ __('levels.parcel')}}</label>
                                    <input id="transfer_to_hub_track_id_" type="text" name="track_id" placeholder="{{ __('placeholder.Enter_tracking_id') }}" value="{{ $parcel != null ? $parcel->tracking_id : '' }}" class="form-control">
                                    <div class="search_message"></div>
                                    @error('track_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="date">{{ __('levels.date')}}</label> <span class="text-danger">*</span>
                                    <input type="text" id="date" data-toggle="datepicker" name="date" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{ $income->date }}" required>
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
                                  {{-- user --}}
                                <div class="form-group  " id="user_head" style="@if($income->account_head_id == 3) display: block!important @else display:none!important @endif">
                                    <label for="user_id">{{ __('levels.user') }}</label>
                                    <select style="width: 100%" id="user_id"  name="user_id" class="form-control @error('user_id') is-invalid @enderror" data-url="{{ route('income.users') }}">
                                        <option value=""> {{ __('menus.select') }} {{ __('user.title') }}</option>
                                        <option value="{{ @$income->user_id }}" selected> {{ @$income->user->name}}</option>
                                    </select>
                                    @error('user_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                 <div   class="form-group delivery_man_searchs" style="@if($income->account_head_id == 2) @else display:none @endif" >
                                    <label for="parcelDeliveryManID_">{{ __('parcel.deliveryman') }}</label> <span class="text-danger">*</span>
                                    <select style="width: 100%" id="parcelDeliveryManID_"  name="delivery_man_id" data-url="{{ route('parcel.deliveryman.search') }}" class="form-control @error('delivery_man_id') is-invalid @enderror">
                                        <option value=""> {{ __('menus.select') }} {{ __('deliveryman.title') }}</option>
                                        @if ($income->delivery_man_id != null)
                                            <option value="{{ $income->delivery_man_id }}" selected > {{ $deliveryman->user->name }} </option>
                                        @endif
                                    </select>
                                    <input type="hidden" id="deliveryman_amount" value="0"/>
                                    <div  class="deliveryman_balance active">{{ __('placeholder.current_balance') }}:{{ @$income->deliveryman->current_balance }}</div>
                                    @error('delivery_man_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group merchant_search">
                                    <label for="parcelMerchantid_">{{ __('parcel.merchant') }}</label> <span class="text-danger">*</span>
                                    <select style="width: 100%" id="parcelMerchantid_"  name="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror" data-url="{{ route('parcel.merchant.shops') }}">
                                        <option value=""> {{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                        @if ($income->merchant_id != null)
                                            <option value="{{ $income->merchant_id }}" selected> {{ $income->merchant->business_name }}</option>
                                        @endif
                                    </select>
                                    <input type="hidden" id="merchant_amount" value="0"/>
                                    <div  class="merchant_balance active">{{ __('placeholder.current_balance') }} :{{ @$income->merchant->current_balance }}</div>
                                    <small class="check_message text-danger"></small>
                                    @error('merchant_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                {{-- Hub search --}}
                                <div class="form-group hub_search">
                                    <label for="income_hub_id">{{ __('parcel.hub') }}</label> <span class="text-danger">*</span>
                                    <select style="width: 100%" id="income_hub_id"  name="hub_id" class="form-control @error('hub_id') is-invalid @enderror">
                                        <option value=""> {{ __('menus.select') }} {{ __('hub.title') }}</option>
                                        @if ($income->hub_id != null)
                                            <option value="{{ $income->hub_id }}" selected> {{ $income->hub->name }}</option>
                                        @endif
                                    </select>
                                    <input type="hidden" id="hub_amount" value="0"/>
                                    <div  class="hub_balance active">{{ __('placeholder.current_balance') }} :{{ @$income->hub->current_balance }}</div>
                                    <small class="check_message text-danger"></small>
                                    @error('hub_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                {{-- Hub users --}}
                                <div class="form-group hub_users">
                                    <label for="hub_users">{{ __('levels.hub_users') }}</label> <span class="text-danger">*</span>
                                    <select style="width: 100%" id="hub_users"  name="hub_users" class="form-control">
                                        <option value=""> {{ __('menus.select') }} {{ __('hub.title') }} {{ __('user.title') }}</option>
                                        @if ($income->hub_user_id  != null)
                                            <option value="{{ $income->hub_user_id  }}" selected> {{ $income->hub_user->name }}</option>
                                        @endif
                                    </select>
                                    @error('hub_users')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                {{-- Hub user accounts --}}
                                <div class="form-group hub_user_accounts"  >
                                    <label for="hub_user_accounts">{{ __('levels.from_account') }}</label> <span class="text-danger">*</span>
                                    <select style="width: 100%" id="hub_user_accounts"  name="hub_user_accounts" class="form-control">
                                        <option value=""> {{ __('menus.select') }} {{ __('hub.title') }} {{ __('user.title') }} {{ __('placeholder.account') }}</option>
                                        @if ($income->hub_user_account_id  != null)
                                            <option value="{{ $income->hub_user_account_id  }}" selected> {{ $income->hub_user_account->account_holder_name }} ({{ $income->hub_user_account->balance }})</option>
                                        @endif
                                    </select>
                                    @error('hub_user_accounts')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="account_ids">{{ __('levels.to_account')}}</label> <span class="text-danger">*</span>
                                    <select id="account_ids" name="account_id" class="form-control" required>
                                        <option selected disabled>{{ __('menus.select') }} {{ __('levels.to_account') }}</option>
                                        @foreach($accounts as $account)
                                            @if ($account->type == App\Enums\AccountType::ADMIN)
                                                <option {{ $income->account_id  == $account->id ? 'selected' : '' }} value="{{ $account->id }}">
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
                                    <input type="hidden" name="account_balance" value="{{ $t_balance->balance }}" id="account_balance">
                                </div>
                                <div class="form-group amount_divs">
                                    <label for="amount">{{ __('levels.amount')}}</label> <span class="text-danger">*</span>
                                    <div class="form-control-wrap">
                                        <input type="number" class="form-control cash-collection" id="amount" value="{{ $income->amount }}" name="amount" placeholder="{{ __('placeholder.Enter_Amount') }}" required>
                                        @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                        <div class="check_message"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="note">{{ __('levels.note')}}</label>
                                    <div class="form-control-wrap deliveryman-search">
                                       <textarea class="form-control" name="note" rows="5">{{ $income->note }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('income.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
        var hubUrl      = '{{ route('parcel.hub.get') }}';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ static_asset('backend/js/income/custom.js') }}"></script>
@endpush

