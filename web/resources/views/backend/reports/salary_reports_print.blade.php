<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> {{ __('reports.salary_reports') }} | print</title>
    <link rel="shortcut icon" href="{{ static_asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ static_asset('backend/')}}/css/reports_print.css">

</head>
<body>
    <div class="print" style="text-align: right" >
        <a href="#" class="btn-danger"  id="close" onclick="window.close()">{{ __('menus.cancel') }}</a>
    </div>
    <div>
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="officehead">
            <tbody>
            <tr>
                <td class="left-col" style="height: 70px;  border-right: 3px solid">
                    <img alt="Logo" src="{{ static_asset(settings()->logo_image)}}" class="logo" style="max-height: 70px;">
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
    <div class="card" >
        <div class="card-body" >
            <div class="invoice" id="printablediv">

                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12  ">
                        <h3 style="text-align: center">
                                {{ __('reports.salary_reports') }}
                            </h3>
                    </div>
                </div>
                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12 " style="overflow: hidden">
                        <span  style="float: left">

                        </span>
                        <span style="float: right" > <font style="font-weight: bold">{{ __('menus.date') }} :</font>  {{ dateFormat(date('Y-m-d')) }}</span>
                    </div>
                </div>
                <hr>
                <div class="row" style="margin-top: 20px">
                    <div class="col-sm-12 table-responsive">
                        <table class="table   " style="width:100%">
                            @php
                                $i=1;
                            @endphp
                        @foreach($salaries as $key=>$salarys)
                            <thead>
                                <tr class="bg-primary">
                                    <td colspan="6" style="text-align: center;margin-bottom:10px"> {{\Carbon\Carbon::createFromFormat('Y-m',$key)->format('M Y')}}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('#') }}</th>
                                    <th>{{ __('reports.user_details') }}</th>
                                    <th>{{ __('levels.month') }}</th>
                                    <th>{{ __('reports.salary') }}</th>
                                    <th>{{ __('reports.paid_amount') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($salarys as $salary )
                                <tr>
                                    <th>
                                        {{ $i++ }}
                                    </th>
                                    <td>
                                        <span> {{@$salary->user->name}}</span><br>
                                        <span> {{@$salary->user->email}}</span><br>
                                        <span> {{@$salary->user->mobile}}</span><br>
                                    </td>
                                    <td>{{\Carbon\Carbon::createFromFormat('Y-m',$key)->format('M Y')}}</td>
                                    <td> {{ settings()->currency }}{{$salary->amount}}</td>
                                    @if(!blank($salaryPayments)  && isset($salaryPayments[$key]))
                                        @php($status = true)
                                        @foreach($salaryPayments[$key] as $payment)
                                            @if($payment->user_id == $salary->user_id && $payment->amount>0)
                                                @php($status = false)
                                                <td> {{ settings()->currency }}{{$payment->amount}}</td>
                                                @if($salary->amount <=$payment->amount )
                                                    <td><span class="badge badge-success" style="color: white">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::PAID) }}</span></td>
                                                @else
                                                    <td><span class="badge badge-warning" style="color: black">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::PARTIAL_PAID) }}</span></td>
                                                @endif
                                            @endif
                                        @endforeach
                                        @if($status)
                                            <td> {{ settings()->currency }}{{number_format(0,2)}}</td>
                                            <td><span class="badge badge-danger" style="color: white">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::UNPAID) }}</span></td>
                                        @endif
                                    @else
                                        <td> {{ settings()->currency }}{{number_format(0,2)}}</td>
                                        <td><span class="badge badge-danger">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::UNPAID) }}</span></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        window.print();
    </script>
</body>
</html>
