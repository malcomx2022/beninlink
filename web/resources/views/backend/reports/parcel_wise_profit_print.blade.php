<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> {{ __('reports.parcel_wise_profit') }} | print</title>
    <link rel="shortcut icon" href="{{ static_asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ static_asset('backend/')}}/css/reports_print.css">
</head>
<body>
    <div class="print" style="text-align: right" >
        <a href="#" class="btn-danger"  id="close" onclick="window.close()">
        {{ __('menus.cancel') }}</a>
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
                        <h3 style="text-align: center">{{ __('reports.parcel_wise_profit') }}</h3>
                    </div>
                </div>
                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12 " style="overflow: hidden">
                        <span  style="float: left"></span>
                        <span style="float: right" > <font style="font-weight: bold">{{ __('menus.date') }} :</font>  {{ dateFormat(date('Y-m-d')) }}</span>
                    </div>
                </div>
                <hr>
                <div class="row" style="margin-top: 20px">
                    <div class="col-sm-12 table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('###') }}</th>
                                    <th>{{ __('reports.details') }}</th>
                                    <th>{{ __('reports.income') }}</th>
                                    <th>{{ __('reports.expense') }}</th>
                                    <th>{{ __('reports.profit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($parcels as $key=>$parcel)
                                <tr>
                                    <td>
                                        {{ $i++ }}
                                    </td>
                                    <td>
                                        <span><b>Tracking Id :</b> <a class="active" href="{{ route('parcel.details',$parcel->id) }}" target="_blank">{{ $parcel->tracking_id }}</a></span><br>
                                        <span><b>Merchant :</b> </span>
                                        <span>{{$parcel->merchant->business_name}}</span><br>
                                        <span><b>Customer :</b> </span>
                                        <span>{{$parcel->customer_name}}</span><br>
                                        <td> {{ settings()->currency }} {{ $parcel->total_delivery_amount }}</td>
                                    </td>
                                    <td> {{ settings()->currency }} {{ parcelExpense($parcel->id) }}</td>
                                    <td>{{ settings()->currency }} {{  ($parcel->total_delivery_amount - parcelExpense($parcel->id)) }}    </td>
                                </tr>
                                @endforeach
                                <tr   class="totalCalculationHead bg-primary" style="background-color:#5969ff">
                                    <td ></td>
                                    <td class="">{{ __('reports.total') }}    : </td>
                                    <td> {{ settings()->currency }}  {{ $parcels->sum('total_delivery_amount') }} </td>
                                    <td> {{ settings()->currency }}  {{ parcelExpenseTotal($parcels->pluck('id')) }} </td>
                                    <td >  {{ settings()->currency }}  {{ ($parcels->sum('total_delivery_amount') - parcelExpenseTotal($parcels->pluck('id')) ) }}</td>
                                </tr>
                            </tbody>
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
