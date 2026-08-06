<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> Salary pay slip | print</title>
    <link rel="shortcut icon" href="{{ static_asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ static_asset('backend/')}}/css/payslip.css">
</head>
<body>
    <div class="print" style="text-align: right" >
        <button type="button" class="btn-danger" onclick="window.close()">{{ __('menus.cancel') }}</button>
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
                            {{ __('salary.pay_slip')}} </span>
                        </h3>
                    </div>
                </div>
                <div style="overflow:hidden;padding:0px 20px;"><span style="float: right;font-size:12px;" > <font style="font-weight: bold ">Date :</font>  {{ dateFormat($salary->date ) }}</span></div>
                <hr>
                <div class="row" style="margin-top: 20px">
                    <div class="col-sm-12 table-responsive">
                        <table class="userInfo">
                            <tr>
                                <td>
                                    <div class="row odd">
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.employee_name') }}</div>
                                                <div class="col-8">: {{ $salary->user->name }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.joining_date') }}</div>
                                                <div class="col-8">: {{ dateFormat($salary->user->joining_date) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row ">
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.email') }}</div>
                                                <div class="col-8">: {{ $salary->user->email }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.hub') }}</div>
                                                <div class="col-8">: {{ @$salary->user->hub->name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row odd">
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.designation') }}</div>
                                                <div class="col-8">: {{ @$salary->user->designation->title }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.pay_period') }}</div>

                                                <div class="col-8">: {{ @\Carbon\Carbon::createFromFormat('Y-m',$salary->month)->format('M Y')}}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row ">
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.department') }}</div>
                                                <div class="col-8">: {{ @$salary->user->department->title }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4">{{ __('salary.salary') }}</div>
                                                <div class="col-8">: {{ @settings()->currency }}{{ @$month_salary->amount }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <table class="userInfo">
                            <tr>
                                <td>
                                    <div class="row bg-color text-white">
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4"> </div>
                                                <div class="col-8"></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="row">
                                                <div class="col-4 font-bold">{{ __('salary.salary_paid') }}</div>
                                                <div class="col-8 font-bold">: {{ settings()->currency }}{{ $salary->amount }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
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
