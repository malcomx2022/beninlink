@extends('backend.merchant_panel.onlinepayment.index')
@section('title')
    {{ __('levels.aamarpay_payment_details') }}
@endsection
@section('cardcontent')
    <div class="row">
        <div class="col-md-12 col-md-offset-3  ">
            <div class="card">
                <div class=" card-body panel panel-default credit-card-box">
                    <p class="h3">{{ __('levels.aamarpay_payout_details') }}</p>
                    <div class="panel-body">
                        <div class="col-md-6">
                            <form  action="{{route('aamarpay.payment')}}" method="get">

                                <div class="form-group  ">
                                    <label for="accountId">{{ __('levels.to_account') }}</label>
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
                                </div>
                                <label>{{ __('levels.amount') }} </label>
                                <div class="form-group d-flex">
                                    <input type="number" name="amount" id="aamarpay_amount" class="form-control w-unset"  />
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
