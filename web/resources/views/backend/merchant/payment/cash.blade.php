
<form action="{{route('merchant.paymentinfo.bank.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
    @csrf
    <input id="merchant_id" type="hidden" name="merchant_id" value="{{ $merchant_id }}" />
    <input type="hidden" id="payment_method_name"  value="{{ $payment_method }}"  name="payment_method_name"  />
    @if(isset($editid) !==null)
        <input type="hidden" name="editid" value="{{ $editid }}" />
    @endif
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.submit') }}</button>
                <a href="{{ route('merchant.paymentaccount.index',$merchant_id) }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
