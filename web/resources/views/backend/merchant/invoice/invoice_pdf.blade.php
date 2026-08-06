<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

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
                                    {{-- <img alt="Logo" src="{{settings()->logo_image }}" class="logo" style="width:150px"> --}}

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
                                                <span><i class="fa-sharp fa-solid fa-file-invoice" style="font-size: 15px"></i> Invoice to <br/>
                                                    <b style="">{{ $invoice->merchant->business_name }}</b>  </span><br>
                                                <span style=" "> {{$invoice->merchant->user->mobile}} </span><br>
                                                <span style=""> {{$invoice->merchant->address}}</span><br>
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
                                                    <td style="border-right:1px solid rgba(73, 73, 73, 0.226);padding:5px!important;"><b>Invoice Date </b></td>
                                                    <td style="padding:5px!important;">{{ $invoice->invoice_date }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="border-right:1px solid rgba(73, 73, 73, 0.226);padding:5px!important"><b>Invoice #</b></td>
                                                    <td style="padding:5px!important;">{{ $invoice->invoice_id }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="border-bottom:none!important;border-right:1px solid rgba(73, 73, 73, 0.226);padding:5px!important;"><b>Total paid out</b></td>
                                                    <td style="border-bottom:none!important;padding:5px!important;">{{ number_format($invoice->TotalPayable ,2) }}</td>
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
            <th style=" padding: 3px 10px;">Date</th>
            <th style=" padding: 3px 10px;">Customer Info</th>
            <th style=" padding: 3px 10px;">Invoice ID</th>
            <th style=" padding: 3px 10px;">Tracking ID</th>
            <th style=" padding: 3px 10px;">Cash Collection <br/>(TK)</th>
            <th style=" padding: 3px 10px;">Delivery<br/>Charge</th>
            <th style=" padding: 3px 10px;">Vat</th>
            <th style=" padding: 3px 10px;">COD</th>
            <th style=" padding: 3px 10px;">Total Charge</th>
            <th style=" padding: 3px 10px;">Paid Out</th>
        </tr>
        @foreach ($invoice->parcels as $parcel)
            <tr>
                <td style=" padding: 2px 10px;">{{ \Carbon\Carbon::parse($parcel->updated_at)->format('d-m-Y') }}</td>
                <td style=" padding: 2px 10px;"  >{{ @$parcel->customer_name }}<br/>{{ @$parcel->customer_phone }}<br/>{{ @$parcel->customer_address }}</td>
                <td style=" padding: 2px 10px;">{{ @$parcel->invoice_no }}</td>
                <td style=" padding: 2px 10px;">
                    {{ @$parcel->tracking_id }}<br/>
                    @if(
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT   ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_TO_COURIER           ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_TRANSFER_BY_HUB      ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_RECEIVED_PARCEL
                        )
                            @if($parcel->partial_delivered == \App\Enums\BooleanStatus::YES)  
                                <span class="badge badge-pill badge-info">{{ trans("parcelStatus.24")}} And Partial Delivered</span>
                            @else
                                <span class="badge badge-pill badge-info">{{ trans("parcelStatus.24")}}</span>
                            @endif
                    @else
                        {!! @$parcel->parcel_status!!}
                    @endif
                </td>

                <td>
                    @if(
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT   ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_TO_COURIER           ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_TRANSFER_BY_HUB      ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_RECEIVED_PARCEL

                        )  
                         @if($parcel->partial_delivered == \App\Enums\BooleanStatus::YES)
                            {{ number_format(@$parcel->cash_collection,2) }}
                         @else
                            - {{ number_format(@$parcel->cash_collection,2) }}
                         @endif  
                    @else
                        {{ number_format(@$parcel->cash_collection,2) }}
                    @endif

                </td>
                <td style="background-color: rgb(73 73 73 / 7%); padding: 2px 10px;">{{$parcel->delivery_charge }}</td>
                <td style="background-color: rgb(73 73 73 / 7%); padding: 2px 10px;">{{@$parcel->vat_amount}}</td>
                <td style="background-color: rgb(73 73 73 / 7%); padding: 2px 10px;">{{@$parcel->cod_amount}}</td>
                <td style=" padding: 2px 10px;">
                    
                     @if(
                        $parcel->status == \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT ||
                        $parcel->status == \App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT ||
                        $parcel->status == \App\Enums\ParcelStatus::RETURN_TO_COURIER 
                        ) 
                       
                        - {{ @$parcel->return_charges?? 0 }} 
                        
                    @else  
                        ({{@$parcel->total_delivery_amount + @$parcel->vat_amount}})
                    @endif
             
                    
                    </td>
                <td style=" padding: 2px 10px;">
                   
                    @if(
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT ||
                            $parcel->status == \App\Enums\ParcelStatus::RETURN_TO_COURIER 
                        ) 
                        @if($parcel->partial_delivered == \App\Enums\BooleanStatus::YES)
                            {{@$parcel->current_payable}} - {{ @$parcel->return_charges?? 0 }} 
                        @else
                            - {{ @$parcel->return_charges?? 0 }} 
                        @endif
                    @else
                        {{@$parcel->current_payable}}
                    @endif
                </td>
            </tr>
            @endforeach
           
            <tr style="background-color: #7e0095;color:white">
                <th style="text-transform: uppercase; padding: 2px 10px;" colspan="4">Total</th>
                <th style=" padding: 2px 10px;"> {{ number_format($invoice->TotalCashCollectedAmount,2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($invoice->parcels->sum('delivery_charge'),2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($invoice->parcels->sum('vat_amount'),2) }} </th>
                <th style=" padding: 2px 10px;"> {{ number_format($invoice->parcels->sum('cod_amount'),2) }}   </th>
                <th style=" padding: 2px 10px;">   {{ number_format($invoice->TotalDeliverChargeAmount,2) }}  </th>
                <th style=" padding: 2px 10px;">
                    {{ number_format($invoice->TotalPayable,2) }}
                </th>
                
            </tr>
  

    </table>
    <table style="width:100%;border:none!important;padding:0px">
            <tr>
                <td style="border-bottom:none!important">

                        <div >
                            <span><b>Terms and Conditions</b></span><br/>
                            <p>
                                Payment should be made within 48 hours by bank or mobile-banking.
                            </p>

                        </div>


                </td>
                <td style="border-bottom:none!important">
                    <div >
                        <p style="text-align: left;width:150px;float: right;">
                            (This is a computer
                            generated invoice
                            and requires no
                            signature)
                        </p>
                    </div>
                </td>
            </tr>

    </table>

</body>
</html>
