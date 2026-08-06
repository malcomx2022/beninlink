@extends('backend.payout.index')
@section('title')
   {{ __('levels.skrill_payment_details') }}
@endsection
@section('cardcontent')
    <div class="row">
        <div class="col-md-12 col-md-offset-3  ">
            <div class="card">
                <div class=" card-body panel panel-default credit-card-box">
                    <p class="h3">{{ __('levels.skrill_payout_details') }}</p>
                    <div class="panel-body">
                        <div class="col-md-6">
                            <form  action="{{route('payout.skrill.make.payment' )}}" method="get">
                                <input type="hidden" name="merchant_id" value="{{ $merchant_id }}"/>
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
                                <label>{{ __('levels.amount_usd') }} </label>
                                <div class="form-group d-flex">
                                    <input type="number" name="amount" id="skrill_amount" class="form-control w-unset"  />
                                    <button type="submit" class="btn btn-primary btn-block" style="width:unset!important"  >{{ __('levels.pay_now') }}</button>
                                </div>
                            </form>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
