@extends('backend.partials.master')
@section('title')
    {{ __('parcel.my_wallet') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <!-- wrapper  -->
    <div class="container-fluid  dashboard-content">
        <!-- pageheader -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="#"
                                        class="breadcrumb-link">{{ __('parcel.my_wallet') }}</a></li>
                                <li class="breadcrumb-item"><a href=""
                                        class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end pageheader -->
        <div class="row">
            <!-- table  -->
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('merchant-panel.my.wallet.index') }}" method="GET" id="filter-form">
                            <div class="row">
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="date">{{ __('parcel.date') }}</label>
                                    <input type="text" autocomplete="off" id="date" name="date"
                                        placeholder="Enter Date" class="form-control date_range_picker"
                                        value="{{ old('date', $request->date) }}">
                                    @error('date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="parcelStatus">{{ __('parcel.status') }}</label>
                                    <select style="width: 100%" id="parcelStatus" name="status"
                                        class="form-control select2 @error('status') is-invalid @enderror">
                                        <option value="" selected> {{ __('menus.select') }}
                                            {{ __('levels.status') }}</option>
                                        @foreach (trans('WalletStatus') as $key => $status)
                                            <option value="{{ $key }}"
                                                {{ old('status', $request->status) == $key ? 'selected' : '' }}>
                                                {{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>


                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="search">{{ __('parcel.search') }}</label>
                                    <input id="search" type="text" name="search"
                                        placeholder="{{ __('parcel.search') }}" autocomplete="off" class="form-control"
                                        value="{{ old('search', $request->search) }}">
                                    @error('search')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2 pt-1 pl-0">
                                    <div class="col-12 pt-3 d-flex justify-content text-right">
                                        <button type="submit" class="btn btn-sm btn-space btn-primary"><i
                                                class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('merchant-panel.my.wallet.index') }}"
                                            class="btn btn-sm btn-space btn-secondary"><i class="fa fa-eraser"></i>
                                            {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


                <div class="row">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <p>Total Wallet Balance</p>
                                <h3 class="mb-0">{{ settings()->currency }} {{ Auth::user()->merchant->wallet_balance }}
                                </h3>
                                @if (Auth::user()->merchant->wallet_balance <= 10)
                                    <p class="text-danger"> You are low on balance. Please recharge</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3  col-sm-6">
                        <div class="card ">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <p>Total Recharge</p>
                                        <h3 class="mb-0">
                                           {{ settings()->currency }} {{ number_format(\App\Models\Backend\Wallet::where(['user_id'=>Auth::user()->id,'type'=>App\Enums\Wallet\WalletType::INCOME])->sum('amount'),2) }}
                                        </h3>
                                    </div>
                                    <div class="col-lg-6">
                                        <p>Total Deducations</p>
                                        <h3 class="mb-0">
                                            {{ settings()->currency }} {{ number_format(\App\Models\Backend\Wallet::where(['user_id'=>Auth::user()->id,'type'=>App\Enums\Wallet\WalletType::EXPENSE])->sum('amount'),2) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3  col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <p>Pending</p>
                                        <h3 class="mb-0">
                                            {{ \App\Models\Backend\Wallet::where('user_id', Auth::user()->id)->where('status', \App\Enums\Wallet\WalletStatus::PENDING)->count() }}
                                        </h3>
                                    </div>
                                    <div class="col-lg-4">
                                        <p>Confirm</p>
                                        <h3 class="mb-0">
                                            {{ \App\Models\Backend\Wallet::where('user_id', Auth::user()->id)->where('status', \App\Enums\Wallet\WalletStatus::APPROVED)->count() }}
                                        </h3>
                                    </div>
                                    <div class="col-lg-4">
                                        <p>Rejected</p>
                                        <h3 class="mb-0">
                                            {{ \App\Models\Backend\Wallet::where('user_id', Auth::user()->id)->where('status', \App\Enums\Wallet\WalletStatus::REJECTED)->count() }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3  col-sm-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <a href="#" class="d-block modalBtn"
                                    data-url="{{ route('merchant-panel.my.wallet.recharge') }}"
                                    data-title="Recharge your Wallet" data-bs-toggle="modal"
                                    data-modalsize="modal-lg"
                                    data-bs-target="#dynamic-modal">
                                    <p>Recharge Wallet</p>
                                    <h3 class="mb-0 "><i class="fa fa-plus"></i></h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="row pl-4 pr-4 pt-4">
                        <div class="col-6">
                            <p class="h3"> {{ __('parcel.wallet_history') }} </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                              <button class="nav-link @if(!$request->recharge_page) active @endif " id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">{{ __('parcel.all_transaction') }}</button>
                              <button class="nav-link @if($request->recharge_page) active  @endif  " id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">{{ __('parcel.recharge') }}</button>
                           </div>
                          </nav>
                          <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade @if(!$request->recharge_page) show active @endif " id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
                                    @include('backend.merchant_panel.mywallet.all_transaction')
                                </div>
                                <div class="tab-pane fade @if($request->recharge_page) show active @endif " id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                                    @include('backend.merchant_panel.mywallet.recharge_transaction')
                                </div>
                           </div>
                    </div>
                </div>
            </div>
            <!-- end table  -->
        </div>
    </div>
    <div id="paytm-checkoutjs"></div>
    <!-- end wrapper  -->
@endsection

<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style >
        .cbm-input-group input{
            border-radius: 30px 0px  0px 30px !important
        }
        .cbm-input-group button{
            border-radius: 0px  30px  30px 0px!important
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        $(document).on('click', '.quick-amount', function() {
            $('#recharge_amount').val(parseFloat($(this).data('amount')));
        });
    </script>
    <script type="text/javascript">
        var dateParcel = '{{ $request->date }}';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}">
    </script>
@endpush
