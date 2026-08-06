@extends('backend.partials.master')
@section('title')
    {{ __('merchant.payment_accounts') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('menus.accounts') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('merchant.payment_accounts') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('merchant.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('merchant.edit_account') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{route('payment.account.update',['id'=>$editaccount->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('PUT')
                        <div class="row"  >
                            <div class="col-md-6">
                                <div class="form-group  ">
                                    <label for="payment_method">{{ __('merchant.payment_method') }}</label> <span class="text-danger"></span>
                                    <select id="payment_method" name="payment_method" class="form-control @error('payment_method') is-invalid @enderror"  >
                                        <option>{{ __('merchant.select_payment_method') }}</option>
                                        @foreach (\Config::get('merchantpayment.payment_method') as $value)
                                            <option value="{{ $value }}"
                                                @if(
                                                        $errors->has('bank_name')   ||
                                                        $errors->has('holder_name') ||
                                                        $errors->has('account_no')  ||
                                                        $errors->has('branch_name') ||
                                                        $errors->has('routing_no')
                                                    )
                                                    @if ($value == 'bank')
                                                        selected
                                                    @endif
                                                @elseif(
                                                        $errors->has('mobile_company')  ||
                                                        $errors->has('mobile_no')       ||
                                                        $errors->has('account_type')
                                                        )
                                                    @if ($value == 'mobile')
                                                        selected
                                                    @endif
                                                @elseif ($value == old('payment_method',$editaccount->payment_method))
                                                    selected

                                                @endif
                                            >{{ __('merchant.'.$value) }}</option>
                                        @endforeach

                                    </select>
                                    @error('payment_method')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                {{-- bank --}}
                                <div class="form-group bank  @if($errors->has('mobile_no') || $errors->has('mobile_holder_name')) d-none @endif"
                                    @if(
                                        $errors->has('holder_name') ||
                                        $errors->has('branch_name') ||
                                        $errors->has('bank_name')   ||
                                        $errors->has('account_no')  ||
                                        $errors->has('routing_no')
                                    )
                                    @else
                                        @if($editaccount->payment_method == 'bank' ) @else style="display:none" @endif
                                    @endif  >

                                    <label for="holder_name">{{ __('merchant.holder_name') }}</label> <span class="text-danger">*</span>
                                    <input id="holder_name" type="text" name="holder_name" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.holder_name') }}" autocomplete="off" class="form-control" value="{{old('holder_name',isset($editaccount->payment_method)? $editaccount->payment_method == 'bank'?$editaccount->holder_name:'':'')}}" require>
                                    @error('holder_name')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group bank @if($errors->has('mobile_no') || $errors->has('mobile_holder_name')) d-none @endif"
                                    @if(
                                        $errors->has('holder_name') ||
                                        $errors->has('branch_name') ||
                                        $errors->has('bank_name') ||
                                        $errors->has('account_no') ||
                                        $errors->has('routing_no')
                                        )
                                        @else
                                            @if($editaccount->payment_method == 'bank' ) @else style="display:none" @endif
                                        @endif >

                                    <label for="branch_name">{{ __('merchant.branch_name') }}</label> <span class="text-danger">*</span>
                                    <input id="branch_name" type="text" name="branch_name" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.branch_name') }}" autocomplete="off" class="form-control" value="{{old('branch_name',$editaccount->branch_name)}}" require>
                                    @error('branch_name')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            <div class="form-group mobile   @if(
                                $errors->has('holder_name') ||
                                $errors->has('branch_name') ||
                                $errors->has('bank_name') ||
                                $errors->has('account_no') ||
                                $errors->has('routing_no')
                                )
                                d-none
                                @endif "   @if($errors->has('mobile_no') || $errors->has('mobile_holder_name')) @else
                                    @if($editaccount->payment_method == 'mobile' ) @else style="display:none" @endif
                                @endif>
                                <label for="mobile_holder_name">{{ __('merchant.holder_name') }}</label> <span class="text-danger">*</span>
                                <input id="mobile_holder_name" type="text" name="mobile_holder_name" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.holder_name') }}" autocomplete="off" class="form-control" value="{{old('mobile_holder_name', isset($editaccount->payment_method)? $editaccount->payment_method == 'mobile'? $editaccount->holder_name:'':'')}}" require>
                                @error('mobile_holder_name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group mobile   @if(
                                $errors->has('holder_name') ||
                                $errors->has('branch_name') ||
                                $errors->has('bank_name') ||
                                $errors->has('account_no') ||
                                $errors->has('routing_no')
                                )
                                d-none
                                @endif"   @if($errors->has('mobile_no') || $errors->has('mobile_holder_name') ) @else
                                    @if($editaccount->payment_method == 'mobile' ) @else style="display:none" @endif
                                @endif>
                                <label for="mobile_no">{{ __('merchant.mobile_no') }}</label> <span class="text-danger">*</span>
                                <input id="mobile_no" type="text" name="mobile_no" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.mobile_number') }}" autocomplete="off" class="form-control" value="{{old('mobile_no',$editaccount->mobile_no)}}" require>
                                @error('mobile_no')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            </div>
                            {{-- bank --}}
                            <div class="col-md-6 bank @if($errors->has('mobile_no') || $errors->has('mobile_holder_name')) d-none @endif"
                                @if(
                                $errors->has('bank_name') ||
                                $errors->has('account_no') ||
                                $errors->has('routing_no')
                                )
                                @else
                                    @if($editaccount->payment_method == 'bank' ) @else style="display:none" @endif
                                @endif >
                                <div class="form-group  ">
                                    <label for="bank_name">{{ __('merchant.select_bank') }}</label> <span class="text-danger">*</span>
                                    <select id="bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"   >
                                        @foreach (\Config::get('merchantpayment.banks') as $value)
                                            <option value="{{ __('merchant.'.$value) }}" @if (__('merchant.'.$value) == $editaccount->bank_name ) selected @endif>{{ __('merchant.'.$value) }}</option>
                                        @endforeach
                                    </select>
                                    @error('bank_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="account_no">{{ __('merchant.account_no') }}.</label> <span class="text-danger">*</span>
                                    <input id="account_no" type="text" name="account_no" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.account_number') }}" autocomplete="off" class="form-control" value="{{old('account_no',$editaccount->account_no)}}" require>
                                    @error('account_no')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="routing_no">{{ __('merchant.routing_no') }}.</label> <span class="text-danger">*</span>
                                    <input id="routing_no" type="text" name="routing_no" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.routing_number') }}" autocomplete="off" class="form-control" value="{{old('routing_no',$editaccount->routing_no)}}" require>
                                    @error('routing_no')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            {{-- mobile --}}
                            <div class="col-md-6 mobile
                                @if(
                                    $errors->has('holder_name') ||
                                    $errors->has('branch_name') ||
                                    $errors->has('bank_name') ||
                                    $errors->has('account_no') ||
                                    $errors->has('routing_no')
                                    )
                                d-none
                                @endif "
                                @if($errors->has('mobile_no') || $errors->has('mobile_holder_name'))
                                @else
                                    @if($editaccount->payment_method == 'mobile' )
                                    @else
                                        style="display:none"
                                    @endif
                                @endif >

                                <div class="form-group  mobile"  >
                                    <label for="mobile_company">{{ __('merchant.select_mobile_company') }}</label> <span class="text-danger">*</span>
                                    <select id="mobile_company" name="mobile_company" class="form-control @error('mobile_company') is-invalid @enderror"  >
                                        @foreach (\Config::get('merchantpayment.account_methods') as $value)
                                            <option value="{{ __('merchant.'.$value) }}" @if (__('merchant.'.$value) == $editaccount->mobile_company)
                                                selected
                                            @endif>{{ __('merchant.'.$value) }}</option>
                                        @endforeach
                                    </select>
                                    @error('mobile_company')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group  ">
                                    <label for="account_type">{{ __('merchant.account_type') }}</label> <span class="text-danger">*</span>
                                    <select id="account_type" name="account_type" class="form-control @error('account_type') is-invalid @enderror"  >
                                        @foreach (\Config::get('merchantpayment.account_types') as $value)
                                            <option value="{{ __('merchant.'.$value) }}" @if (__('merchant.'.$value) == $editaccount->account_type)
                                                selected
                                            @endif>{{ __('merchant.'.$value) }}</option>
                                        @endforeach
                                    </select>
                                    @error('account_type')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div  class="col-12 bank-mobile" @if ($errors->any()) @else @if($editaccount->payment_method) @else  style="display:none" @endif @endif>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                            <a href="{{ route('merchant.accounts.payment-account.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
<!-- js  -->
@push('scripts')
    <script src="{{ static_asset('backend/js/merchant_panel/payment_account.js') }}"></script>
@endpush


