
<form action="{{route('merchant.paymentinfo.bank.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
    @csrf
    <input id="merchant_id" type="hidden" name="merchant_id" value="{{ $merchant_id }}" />
    <input type="hidden" id="payment_method_name"  value="{{ $payment_method }}"  name="payment_method_name"  />
    @if(isset($editid) !==null)
        <input type="hidden" name="editid" value="{{ $editid }}" />
    @endif
    <div class="form-group  ">
        <label for="bank_name">{{ __('merchant.select_bank') }}</label> <span class="text-danger">*</span>
        <select id="bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"  ">
            @foreach (\Config::get('merchantpayment.banks') as $value)
                <option value="{{ __('merchant.'.$value) }}">{{ __('merchant.'.$value) }}</option>
            @endforeach
        </select>
        @error('bank_name')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group">
        <label for="holder_name">{{ __('merchant.holder_name') }}</label> <span class="text-danger">*</span>
        <input id="holder_name" type="text" name="holder_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Account_Holder_Name') }}" autocomplete="off" class="form-control" value="{{old('holder_name')}}" require>
        @error('holder_name')
        <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group">
        <label for="account_no">{{ __('merchant.account_no') }}.</label> <span class="text-danger">*</span>
        <input id="account_no" type="number" name="account_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_account_no') }}" autocomplete="off" class="form-control" value="{{old('account_no')}}" require>
        @error('account_no')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group">
        <label for="branch_name">{{ __('merchant.branch_name') }}</label> <span class="text-danger">*</span>
        <input id="branch_name" type="text" name="branch_name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_branch_name') }}" autocomplete="off" class="form-control" value="{{old('branch_name')}}" require>
        @error('branch_name')
        <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group">
        <label for="routing_no">{{ __('merchant.routing_no') }}.</label> <span class="text-danger">*</span>
        <input id="routing_no" type="number" name="routing_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Routing_Number') }}" autocomplete="off" class="form-control" value="{{old('routing_no')}}" require>
        @error('routing_no')
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
