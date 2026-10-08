<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('dashboard.bank_transaction') }} | {{ __('levels.print') }}</title>
    <link rel="shortcut icon" href="{{ asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('backend/')}}/css/bulk_print.css">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
</head>
<body>
        <div class="print" style="text-align: right" >
            <a href="#" onclick="window.close();" class="btn-danger">{{ __('pickupRequest.close') }}</a>

        </div>

        <div>
            <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="officehead">
                <tbody>
                <tr>
                    <td class="left-col" style="height: 70px;  border-right: 3px solid">
                        <img alt="{{ settings()->name }}" src="{{ asset(settings()->logo_image)}}" class="logo" style="max-height: 70px;">
                    </td>
                    <td style="padding-left: 10px;line-height: 1.2;" class="right-col">
                        <span> <b style="letter-spacing: 3px;"></b> {{ settings()->name }}</span><br>
                        <span>{{ settings()->email }}</span><br>
                        <span style="  padding-top: 3px; "> {{ settings()->phone }} </span><br>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="card" style="border-top: none!important" >
            <div class="card-body" >
                <div class="invoice" id="printablediv">
                    <div class="row" style="margin-top: 20px">
                        <table class="table table-striped" style="width:100%">
                            <thead style="background:none!important">
                                <tr>
                                    <th style="color:black!important">{{ __('#') }}</th>
                                    <th style="color:black!important">{{ __('levels.account_no') }} | {{__('levels.name')}}</th>
                                    <th style="color:black!important">{{ __('levels.type') }}</th>
                                    <th style="color:black!important">{{ __('levels.amount') }}</th>
                                    <th style="color:black!important">{{ __('levels.date') }}</th>
                                    <th style="color:black!important">{{ __('levels.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $n = 0;

                                @endphp

                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{++$n}}</td>

                                        <td>
                                            @if ($transaction->fund_transfer_id !==null)
                                                <span>{{ __('levels.from') }} :</span>
                                                    @if($transaction->fundTransfer->fromAccount->gateway == 1)
                                                        {{@$transaction->fromAccount->user->name}} ({{ __('account_gateway.1') }})
                                                    @else
                                                        {{ __('account_gateway.' . $transaction->fundTransfer->fromAccount->gateway) }} ,
                                                        {{$transaction->fundTransfer->fromAccount->account_holder_name}}
                                                        ({{$transaction->fundTransfer->fromAccount->account_no}}
                                                        {{$transaction->fundTransfer->fromAccount->branch_name}}
                                                        {{$transaction->fundTransfer->fromAccount->mobile}})
                                                    @endif
                                                </br>
                                                <span>{{ __('To') }} :</span>
                                                    @if($transaction->fundTransfer->toAccount->gateway == 1)
                                                        {{@$transaction->toAccount->user->name}} ({{ __('account_gateway.1') }})
                                                    @else
                                                        {{ __('account_gateway.' . $transaction->fundTransfer->toAccount->gateway) }} ,
                                                        {{$transaction->fundTransfer->toAccount->account_holder_name}}
                                                        ({{$transaction->fundTransfer->toAccount->account_no}}
                                                        {{$transaction->fundTransfer->toAccount->branch_name}}
                                                        {{$transaction->fundTransfer->toAccount->mobile}})
                                                    @endif
                                            @else
                                                @if($transaction->account->gateway == 1)
                                                    {{@$transaction->account->user->name}} ({{ __('account_gateway.1') }})
                                                @else
                                                    {{ __('account_gateway.' . $transaction->account->gateway) }} ,
                                                    {{$transaction->account->account_holder_name}}
                                                    ({{$transaction->account->account_no}}
                                                    {{$transaction->account->branch_name}}
                                                    {{$transaction->account->mobile}})
                                                @endif
                                            @endif
                                        </td>
                                        <td>{!! $transaction->account_type !!}</td>
                                        <td>{{ formatAmount($transaction->amount) }}</td>
                                        <td>{{dateFormat($transaction->date)}}</td>
                                        <td>{{$transaction->note}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script>
            window.print();
        </script>

</body>
</html>
