<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body{
            margin: 0px;
            padding:0px;
        }
        *{
            font-size: 12px;
        }
        table th{
            padding:10px;
            text-align: left;
        }
        table td{
            padding:10px;
            text-align: left;
            border-bottom:1px solid rgba(73, 73, 73, 0.226);
        }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td style="text-align: center;border-bottom:none!important;padding:0px;padding-bottom:5px">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td  style="border-bottom: none!important;" >
                                <div style="text-align: center;margin-bottom:10px!important">
                                    <h4>{{ settings()->name }}</h4>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td  style="border-bottom: none!important;padding:0px">
                                <table >
                                    <tr>
                                        <td style="padding:0px;border-bottom:none!important">
                                            <div style="padding:10px;line-height:1.5;">
                                                <span><i class="fa-sharp fa-solid fa-file-invoice" style="font-size: 15px"></i>Merchant :  <br/>
                                                    <b >{{ $user->merchant->business_name }}</b>  </span><br>
                                                <span> {{$user->mobile}} </span><br>
                                                <span > {{$user->merchant->address}}</span><br>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="border-bottom:none!important;padding:0px; ">
                                <table width="100%" >
                                    <tr>
                                        <td style="border:1px solid rgba(73, 73, 73, 0.226)!important;padding:0px">
                                            <table width="100%">
                                                <tr>
                                                    <td style="border-right:1px solid rgba(73, 73, 73, 0.226);padding:5px!important;"><b>Export Date </b></td>
                                                    <td style="padding:5px!important;">{{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="border-bottom:none!important;border-right:1px solid rgba(73, 73, 73, 0.226);padding:5px!important;"><b>Total payable</b></td>
                                                    <td style="border-bottom:none!important;padding:5px!important;">{{ $parcels->sum('current_payable') }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    <table   cellspacing="0" width="100%">
        <tr style="background-color: #7e0095;color:white">
            <th style=" padding: 3px 10px;">#</th>
            <th style=" padding: 3px 10px;">Invoice ID</th>
            <th style=" padding: 3px 10px;">Tracking ID</th>
            <th style=" padding: 3px 10px;">Customer Info</th>
            <th style=" padding: 3px 10px;">Status</th>
            <th style=" padding: 3px 10px;">Cash Collection <br/>(TK)</th>
            <th style=" padding: 3px 10px;">Delivery<br/>Charge</th>
            <th style=" padding: 3px 10px;">Vat</th>
            <th style=" padding: 3px 10px;">COD</th>
            <th style=" padding: 3px 10px;">Total Charge</th>
            <th style=" padding: 3px 10px;">Payable</th>
        </tr>
        @foreach ($parcels as $key=>$parcel)
            <tr>
                <td style=" padding: 2px 10px;">{{ ++$key }}</td>
                <td style=" padding: 2px 10px;">{{ @$parcel->invoice_no }}</td>
                <td style=" padding: 2px 10px;">{{ @$parcel->tracking_id }}</td>
                <td style=" padding: 2px 10px;"  >{{ @$parcel->customer_name }}<br/>{{ @$parcel->customer_phone }}<br/>{{ @$parcel->customer_address }}</td>
                <td style=" padding: 2px 10px;"  >{{ @$parcel->status_name }}</td>

                <td>{{ number_format(@$parcel->cash_collection,2) }}</td>
                <td style="background-color: rgb(73 73 73 / 7%); padding: 2px 10px;">{{$parcel->delivery_charge }}</td>
                <td style="background-color: rgb(73 73 73 / 7%); padding: 2px 10px;">{{@$parcel->vat_amount}}</td>
                <td style="background-color: rgb(73 73 73 / 7%); padding: 2px 10px;">{{@$parcel->cod_amount}}</td>
                <td style=" padding: 2px 10px;">({{@$parcel->total_delivery_amount + @$parcel->vat_amount}})</td>
                <td style=" padding: 2px 10px;">{{@$parcel->current_payable}}</td>
            </tr>
            @endforeach
            <tr style="background-color: #7e0095;color:white">
                <th style="text-transform: uppercase; padding: 2px 10px;" colspan="5">Total</th>
                <th style=" padding: 2px 10px;"> {{ number_format($parcels->sum('cash_collection'),2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($parcels->sum('delivery_charge'),2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($parcels->sum('vat_amount'),2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($parcels->sum('cod_amount'),2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($parcels->sum('total_delivery_amount') + $parcels->sum('vat_amount'),2) }}  </th>
                <th style=" padding: 2px 10px;"> {{ number_format($parcels->sum('current_payable'),2) }}  </th>
            </tr>
    </table>
</body>
</html>
