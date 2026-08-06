<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> {{ __('parcelStatus.'.$bulk_type) }} | print</title>
    <link rel="shortcut icon" href="{{ asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('backend/')}}/css/bulk_print.css">
</head>
<body>
    <div class="print" style="text-align: right" >
        @if(isset($reprint))
            <a href="#" onclick="window.close();" class="btn-danger">{{ __('levels.save') }}{{ __('levels.cancel') }}</a>
        @else
            <a href="{{ route('parcel.index') }}" class="btn-danger">{{ __('levels.cancel') }}</a>
        @endif
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
    <div class="card" >
        <div class="card-body" >
            <div class="invoice" id="printablediv">
                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12  ">
                        <h3 style="text-align: center"> {{ __('parcelStatus.'.$bulk_type) }}</h3>
                    </div>
                </div>
                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12 " style="overflow: hidden">
                        <span  style="float: left">
                            <font style="font-weight: bold">{{ __('parcel.delivery_man') }} :</font>  <small>{{ @$deliveryman->user->name }}</small>
                        </span>
                        <span style="float: right" > <font style="font-weight: bold">Date :</font>  {{ dateFormat(date('Y-m-d')) }}</span>
                    </div>
                </div>
                <hr>
                <div class="row" style="margin-top: 20px">
                    <div class="col-sm-12 table-responsive">
                        <table class="table table-striped" style="width: 100%;">
                            <thead class="tablehead">
                                <tr style="text-align: left;">
                                    <th >#</th>
                                    <th  >{{ __('merchant.track_id') }}</th>
                                    <th >{{ __('parcel.merchant_details') }}</th>
                                    <th  >{{ __('parcel.customer_details') }}</th>
                                    @if(@$transfered_hub)
                                    <th  >{{ __('parcel.hub') }}</th>
                                    @endif
                                    <th  >{{ __('parcel.status') }}</th>
                                    <th  >{{ __('parcel.cash_collection') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach($parcels as $key => $parcel)
                                    <tr @if($i%2 == 0) class="odd" @endif>
                                        <td data-label="#">{{ ++$i }}</td>
                                        <td data-label="tracking_id" >#{{ $parcel->tracking_id }}</td>
                                        <td data-label="merchant_details">
                                            {{ @$parcel->merchant->business_name }}<br/>
                                            {{ @$parcel->merchantShop->contact_no }}
                                        </td>
                                        <td data-label="customer_details">
                                            {{ $parcel->customer_name }}<br/>
                                            {{ $parcel->customer_phone }}<br/>
                                            {{ $parcel->customer_address }}
                                        </td>
                                        @if(@$transfered_hub)
                                        <td data-label="hub">{{ $parcel->hub->name }} To {{ $parcel->transferhub->name }}</td>
                                        @endif
                                        <td data-label="status ">{!! StatusParcel($parcel->status) !!}</td>
                                        <td data-label="cash_collection ">{{ settings()->currency }}{{ $parcel->cash_collection }}</td>
                                    </tr>

                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="@if(@$transfered_hub) 6 @else 5 @endif"><span class="pull-right"><b>{{ __('parcel.total_cash_collection') }}</b></span></td>
                                    <td><b>{{ settings()->currency }}{{ $parcels->sum('cash_collection')}}</b></td>
                                </tr>
                            </tfoot>
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
