<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge"> 
    <link rel="shortcut icon" href="{{ asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('backend/')}}/css/bulk_print.css">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
</head>
<body>
<div class="print" style="text-align: right" >
    <a href="#" onclick="window.close();" class="btn-danger">Close</a>
</div>
<div>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="officehead">
        <tbody>
            <tr>
                <td class="left-col" style="height: 70px;  border-right: 3px solid">
                    <img alt="Logo" src="{{ asset(settings()->logo_image)}}" class="logo" style="max-height: 70px;">
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
                <div class="col-sm-12 table-responsive">
                    <table class="table table-striped" style="width:100%;">
                        <thead style="background: none!important;color:black!important">
                            <tr style="color:black!important">
                                <th  style="color:black!important" >{{ __('levels.id') }}</th>
                                <th  style="color:black!important">{{ __('levels.from_account') }}</th>
                                <th  style="color:black!important">{{ __('levels.to_account') }}</th>
                                <th  style="color:black!important">{{ __('levels.date') }}</th>
                                <th  style="color:black!important">{{ __('levels.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i=1; @endphp
                            @foreach($fund_transfers as $fund_transfer)
                            <tr>
                                <td>{{$i++}}</td>
                                <td>
                                    <div class="row">
                                        <div class="col-4">
                                            <img src="{{$fund_transfer->fromAccount->user->image}}" alt="Image" class="rounded" width="40" height="40">
                                        </div>
                                        <div class="col-8">
                                            <strong> {{$fund_transfer->fromAccount->user->name}}</strong><br>
                                            <span> {{$fund_transfer->fromAccount->user->email}}</span><br>
                                        </div>
                                    </div>
                                    @if ($fund_transfer->fromAccount->gateway == 2)
                                        <div class="row">
                                            <div class="col-4">{{__('levels.bank')}}</div>
                                            <div class="col-8">:
                                                @if ($fund_transfer->fromAccount->bank == 1)
                                                    BB
                                                @elseif($fund_transfer->fromAccount->bank == 2)
                                                    DBBL
                                                @elseif($fund_transfer->fromAccount->bank == 3)
                                                    IB
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">Branch</div>
                                            <div class="col-8">: {{$fund_transfer->fromAccount->branch_name}}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">{{__('levels.account_no')}}</div>
                                            <div class="col-8">: {{$fund_transfer->fromAccount->account_no}}</div>
                                        </div>
                                    @elseif ($fund_transfer->fromAccount->gateway == 3)
                                        <div class="row">
                                            <div class="col-4">{{__('levels.mobile')}}</div>
                                            <div class="col-8">: {{$fund_transfer->fromAccount->mobile}}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">{{__('levels.type')}}</div>
                                            <div class="col-8">:
                                                @if ($fund_transfer->fromAccount->account_type == 1)
                                                    Merchant
                                                @else
                                                    Personal
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="row">
                                        <div class="col-4">
                                            <img src="{{$fund_transfer->toAccount->user->image}}" alt="Image" class="rounded" width="40" height="40">
                                        </div>
                                        <div class="col-8">
                                            <strong> {{$fund_transfer->toAccount->user->name}}</strong><br>
                                            <span> {{$fund_transfer->toAccount->user->email}}</span><br>
                                        </div>
                                    </div>
                                    @if ($fund_transfer->toAccount->gateway == 2)
                                        <div class="row">
                                            <div class="col-4">{{__('levels.bank')}}</div>
                                            <div class="col-8">:
                                                @if ($fund_transfer->toAccount->bank == 1)
                                                    BB
                                                @elseif($fund_transfer->toAccount->bank == 2)
                                                    DBBL
                                                @elseif($fund_transfer->toAccount->bank == 3)
                                                    IB
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">Branch</div>
                                            <div class="col-8">: {{$fund_transfer->toAccount->branch_name}}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">{{__('levels.account_no')}}</div>
                                            <div class="col-8">: {{$fund_transfer->toAccount->account_no}}</div>
                                        </div>
                                    @elseif ($fund_transfer->toAccount->gateway == 3)

                                        <div class="row">
                                            <div class="col-4">{{__('levels.mobile')}}</div>
                                            <div class="col-8">: {{$fund_transfer->toAccount->mobile}}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">{{__('levels.type')}}</div>
                                            <div class="col-8">:
                                                @if ($fund_transfer->toAccount->account_type == 1)
                                                    Merchant
                                                @else
                                                    Personal
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>{{dateFormat($fund_transfer->date)}}</td>
                                <td>{{settings()->currency}}{{$fund_transfer->amount}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.print();
</script>

</body>
</html>
