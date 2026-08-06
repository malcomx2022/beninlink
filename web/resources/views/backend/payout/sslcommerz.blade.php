@extends('backend.payout.index')
@section('title')
   {{ __('levels.sslcommerz_payment_details') }}
@endsection
@section('cardcontent')
    <div class="row">
        <div class="col-md-12 col-md-offset-3  ">
            <div class="card">
                <div class=" card-body panel panel-default credit-card-box">
                    <p class="h3"> {{ __('levels.sslcommerz_payout_details') }}</p>
                    <div class="panel-body">
                        <div class="col-md-6">
                            <div class="form-group  ">
                                <label for="accountId">{{ __('levels.from_account') }}</label>
                                <select style="width: 100%" id="accountId"  name="account_id" class="form-control @error('merchant_id') is-invalid @enderror"  >
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
                            <label>{{ __('levels.amount') }}</label>
                            <div class="form-group d-flex">
                                <input type="number" id="total_amount" class="form-control w-unset"  />
                            <button class="btn btn-primary btn-lg btn-block" id="sslczPayBtn"
                                token="if you have any token validation"
                                postdata=""
                                order="If you already have the transaction generated for current order"
                                endpoint="{{ url('admin/payout/pay-via-ajax') }}"> {{ __('levels.pay_now') }}
                            </button>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
     
@endsection
@push('scripts')
 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
            integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
            crossorigin="anonymous"> </script>
            <script src="{{static_asset('backend')}}/libs/js/datepicker.min.js"></script>

    <!-- If you want to use the popup integration, -->
    <script type="text/javascript">
    $(document).ready(function(){
        var obj = {};
        $("#total_amount").on('change',function(){
            obj.amount = $(this).val();
            $('#sslczPayBtn').prop('postdata', obj);
        });
        obj.merchant_id  = '{{ $merchant_id }}';
        obj.account_id = $("#accountId").val();

        $("#accountId").on('change',function(){
            obj.account_id = $(this).val();
            $('#sslczPayBtn').prop('postdata', obj);
        });


        $('#sslczPayBtn').click(function(){
                if($('#total_amount').val() == ''){
                    alert('Amount fieds is required');
                }else{

                }
        });
    });

    (function (window, document) {
            var loader = function () {
                var script = document.createElement("script"), tag = document.getElementsByTagName("script")[0];
                script.src = "https://sandbox.sslcommerz.com/embed.min.js?" + Math.random().toString(36).substring(7); // USE THIS FOR SANDBOX
                tag.parentNode.insertBefore(script, tag);
            };
        window.addEventListener ? window.addEventListener("load", loader, false) : window.attachEvent("onload", loader);
    })(window, document);
    </script>
@endpush
