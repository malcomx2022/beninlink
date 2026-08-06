@extends('backend.payout.index')
@section('title')
   {{ __('levels.razorpay_payment_details') }}
@endsection
@section('cardcontent')
<div class="row">
    <div class="col-md-12 col-md-offset-3  ">
        <div class="card">
            <div class=" card-body panel panel-default credit-card-box">
                <p class="h3">{{ __('levels.razorpay_payment_details') }}</p>
                <div class="panel-body">
                    <div class="col-md-6">
                        <div class="form-group  ">
                            <label for="accountId">{{ __('levels.from_account') }}</label>
                            <select style="width: 100%" id="accountId"  name="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror"  >
                                @foreach ($accounts as $account)
                                    @if ($account->gateway == 1)
                                        <option value="{{ $account->id }}">{{ $account->user->name }} | {{ __('merchant.cash') }} : {{ $account->balance }}</option>
                                    @elseif($account->gateway == 3 || $account->gateway == 4 || $account->gateway == 5)
                                        <option value="{{ $account->id }}">{{$account->account_holder_name}} |No : {{ $account->mobile }}|  @if($account->type == 1) {{ __('merchant.title') }} @else {{ __('placeholder.persional') }} @endif | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }} </option>
                                    @else
                                        <option value="{{ $account->id }}">{{$account->account_holder_name}} | A.No : {{ $account->account_no }} | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('merchant_id')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <label>{{ __('levels.amount_usd') }} </label>
                        <div class="form-group d-flex">
                            <input type="number" id="razorpay_amount" class="form-control w-unset"  />
                        </div>
                            <button type="submit" class="btn btn-sm btn-space btn-primary buy_now"><i class="fa fa-filter"></i> {{ __('Payment') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var amount = '10';
        var account_id = $('#accountId').val();
        $(document).ready(function(){
            $('#razorpay_amount').change(function(){
                amount = $('#razorpay_amount').val();
            });
            $('#accountId').change(function(){
                account_id = $(this).val();
            });

        });
        var SITEURL = '{{URL::to('')}}';
        var url = '{{ route('payout.merchant.razorpay.post') }}';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('body').on('click', '.buy_now', function(e){
            var merchantName = '{{ $merchant->name}}';
            var merchantEmail = '{{ $merchant->email}}';
            var merchantId    = '{{ $merchant_id}}';
            var razorpay_key = '{{ MerchantSearchSettings($merchant_id, 'razorpay_key') }}';
            var image = '{{ settings()->logo_image }}';
            var name = '{{ settings()->name }}';
            var totalAmount = $('#razorpay_amount').val();
            var account_id =  account_id = $(this).val();
            var options = {
                "key": razorpay_key,
                "amount": (totalAmount*100),
                "name": name,
                "description": "Payment",
                "image": image,
                "handler": function (response){
                    window.location.href = url+'?payment_id='+response.razorpay_payment_id+'&account_id='+account_id+'&merchantId='+merchantId+'&amount='+totalAmount;
                },
                "prefill": {
                    "contact": merchantName,
                    "email":   merchantEmail,
                },
                "theme": {
                    "color": "#528FF0"
                }
            };
            var rzp1 = new Razorpay(options);
            rzp1.open();
            e.preventDefault();
        });

    </script>


@endpush
