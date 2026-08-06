

<form action="{{route('merchant.paymentinfo.mobile.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
    @csrf
    <input id="merchant_id" type="hidden" name="merchant_id" value="{{ $merchant_id }}" />
    <input type="hidden" id="payment_method_name"  value="{{ $payment_method }}"  name="payment_method_name"  />
    @if(isset($editid) !==null)
        <input type="hidden" name="editid" value="{{ $editid }}" />
    @endif
    <div class="form-group  ">
        <label for="mobile_company">{{ __('merchant.select_mobile_company') }}</label> <span class="text-danger">*</span>
        <select id="mobile_company" name="mobile_company" class="form-control @error('mobile_company') is-invalid @enderror"  >
            @foreach (\Config::get('merchantpayment.account_methods') as $value)
                <option value="{{ __('merchant.'.$value) }}">{{ __('merchant.'.$value) }}</option>
            @endforeach
        </select>
        @error('mobile_company')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>

    <div class="form-group">
        <label for="mobile_holder_name">{{ __('merchant.holder_name') }}</label> <span class="text-danger">*</span>
        <input id="mobile_holder_name" type="text" name="mobile_holder_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Account_Holder_Name') }}" autocomplete="off" class="form-control" value="{{old('holder_name')}}" require>
        @error('mobile_holder_name')
        <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>


    <div class="form-group">
        <label for="mobile_no">{{ __('merchant.mobile_no') }}</label> <span class="text-danger">*</span>
        <input id="mobile_no" type="number" name="mobile_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_mobile_number') }}" autocomplete="off" class="form-control" value="{{old('mobile_no')}}" require>
        @error('mobile_no')
        <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>


    <div class="form-group  ">
        <label for="account_type">{{ __('merchant.account_type') }}</label> <span class="text-danger">*</span>
        <select id="account_type" name="account_type" class="form-control @error('account_type') is-invalid @enderror"  >
            @foreach (\Config::get('merchantpayment.account_types') as $value)
                <option value="{{ __('merchant.'.$value) }}">{{ __('merchant.'.$value) }}</option>
            @endforeach
        </select>
        @error('account_type')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>

    <div class="form-group">
        <label for="status">{{__('levels.status')}}</label> <span class="text-danger">*</span>
        <select name="status" class="form-control @error('status') is-invalid @enderror">
            @foreach(trans('status') as $key => $status)
                <option value="{{ $key }}" {{ (old('status',\App\Enums\Status::ACTIVE) == $key) ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        @error('status')
        <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.submit') }}</button>
                <a href="{{ route('merchant.paymentaccount.index',$merchant_id) }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
