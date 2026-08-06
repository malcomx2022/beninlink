@extends('backend.partials.master')
@section('title')
{{ __('parcel.wallet_request') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('parcel.wallet_request') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                    <form action="{{route('wallet.request.index')}}"  method="GET" id="filter-form"> 
                        <div class="row">
                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                <label for="date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="date" placeholder="Enter Date" class="form-control date_range_picker" value="{{ old('date',$request->date) }}">
                                @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                <label for="walletStatus">{{ __('parcel.status') }}</label>
                                <select style="width: 100%" id="walletStatus"  name="status" class="form-control select2 @error('status') is-invalid @enderror" >
                                    <option value="" selected> {{ __('menus.select') }} {{ __('levels.status') }}</option>
                                    @foreach (trans('WalletStatus') as $key => $status)
                                        <option value="{{ $key}}" {{ (old('status',$request->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                <label for="merchant_id">{{ __('parcel.merchant') }}</label>
                                <select style="width: 100%" id="merchant_id"  name="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror" data-url="{{ route('parcel.merchant.get') }}">
                                    <option value="" selected> {{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                </select>
                                
                            </div> 

                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                <label for="search">{{ __('parcel.search') }}</label>
                                <input id="search" type="text" name="search"  placeholder="{{ __('parcel.search') }}" autocomplete="off" class="form-control" value="{{old('search',$request->search)}}">
                                @error('search')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2 pt-1 pl-0">
                                <div class="col-12 pt-3 d-flex justify-content text-right">
                                    <button type="submit" class="btn btn-sm btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('wallet.request.index') }}" class="btn btn-sm btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3  col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <p>Total Recharge</p>
                            <h3 class="mb-0">{{ settings()->currency }} {{ number_format(\App\Models\Backend\Wallet::companywise()->where('type',App\Enums\Wallet\WalletType::INCOME)->sum('amount'),2)}}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <p>Total Deducations</p>
                            <h3 class="mb-0">{{ settings()->currency }} {{ number_format(\App\Models\Backend\Wallet::companywise()->where('type',App\Enums\Wallet\WalletType::EXPENSE)->sum('amount'),2)}}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3  col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <p>Pending</p>
                                    <h3 class="mb-0">{{ \App\Models\Backend\Wallet::companywise()->where('status',\App\Enums\Wallet\WalletStatus::PENDING)->count()}}</h3>
                                </div>
                                <div class="col-lg-4">
                                    <p>Confirm</p>
                                    <h3 class="mb-0">{{ \App\Models\Backend\Wallet::companywise()->where('status',\App\Enums\Wallet\WalletStatus::APPROVED)->count()}}</h3>
                                </div>
                                <div class="col-lg-4">
                                    <p>Rejected</p>
                                    <h3 class="mb-0">{{ \App\Models\Backend\Wallet::companywise()->where('status',\App\Enums\Wallet\WalletStatus::REJECTED)->count()}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3  col-sm-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <a href="#" class="d-block" 
                                data-title="Recharge your Wallet" data-bs-toggle="modal"
                                data-bs-target="#add-to-wallet-modal"
                                data-modalsize="modal-lg"
                                >
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
                        <p class="h3">  {{ __('parcel.wallet_request') }} {{ __('levels.list') }} </p>
                    </div>
                </div>
                <div class="card-body">

                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                          <button class="nav-link @if(!$request->recharge_page) active @endif" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">All Transaction</button>
                          <button class="nav-link @if($request->recharge_page) active @endif" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Recharges</button>
                          </div>
                      </nav>
                      <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade @if(!$request->recharge_page) show active @endif " id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
                                @include('backend.wallet_request.all_transaction')    
                            </div>
                            <div class="tab-pane fade @if($request->recharge_page) show active @endif " id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                                @include('backend.wallet_request.recharge_transaction')
                            </div>
                       </div>

                </div>
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>  
@include('backend.wallet_request.add-recharge-modal') 
<!-- end wrapper  -->
@endsection
  
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" /> 
   
@endpush
<!-- js  -->
@push('scripts') 
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
 
    <script type="text/javascript">
        $(document).ready(function(){ 
            var dateParcel = '{{ $request->date }}';  
        });
    </script>
     <script type="text/javascript">
        $(document).ready(function(){ 
            @if($errors->any())
                $('#add-to-wallet-modal').modal('show');
            @endif 
        });
    </script>

    <script type="text/javascript" src="{{ static_asset('backend/js/wallet/wallet.js') }}"></script>
 @endpush
 