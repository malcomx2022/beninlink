@extends('backend.payout.index')
@section('title')
   {{ __('levels.payout') }} {{ __('levels.list') }}
@endsection
@section('cardcontent')
    <div class="row">
        <div class="col-md-12 col-md-offset-3  ">
            <div class="card">
                <div class=" card-body panel panel-default credit-card-box">
                    <p class="h3">{{ __('menus.payout') }} {{ __('levels.list') }}</p>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table " style="width:100%">
                                <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.card_type') }}</th>
                                    <th>{{ __('merchant.title') }}</th>
                                    <th>{{ __('levels.from_account') }}</th>
                                    <th>{{ __('levels.transaction_id') }}</th>
                                    <th>{{ __('levels.amount') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $i=1; @endphp
                                @foreach($oPayments as $payment)
                                    <tr>
                                        <td>{{$i++}}</td>
                                        <td>{{ @__('PaymentType.'.$payment->payment_type) }}</td>
                                        <td>
                                            <div class="row">
                                                <div class="pr-3">
                                                    <img src="{{@$payment->merchant->user->image}}" alt="user" class="rounded" width="40" height="40">
                                                </div>
                                                <div>
                                                    <strong>{{$payment->merchant->business_name}}</strong>
                                                    <p>{{@$payment->merchant->user->email}}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                @if(@$payment->account->gateway == 1)
                                                    Cash
                                                @elseif (@$payment->account->gateway == 2)
                                                    {{ @$payment->account->account_holder_name }} <br/>
                                                    {{ @$payment->account->account_no }} <br/>
                                                    {{ @$payment->account->branch_name }}
                                                @else
                                                    @if (@$payment->account->gateway == 3)
                                                        Bkash
                                                    @elseif (@$payment->account->gateway == 4)
                                                        Rocket
                                                    @elseif (@$payment->account->gateway == 5)
                                                        Nagad
                                                    @endif
                                                    {{ @$payment->account->mobile }} <br/>
                                                    {{ @$payment->account->account_type }}
                                                @endif
                                            </div>
                                        </td>
                                        <td> {{ @$payment->transaction_id }} </td>
                                        <td> {{ settings()->currency }} {{ @$payment->amount }} </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="px-3 d-flex flex-row-reverse align-items-center">
                        <span>{{ $oPayments->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $oPayments->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $oPayments->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $oPayments->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

