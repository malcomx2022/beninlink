<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        th,td{
            padding:10px;
        }
        .test td{
            width: 50%;
        }
    </style>
</head>
<body>
    <div>
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="officehead">
            <tbody>
            <tr>
                <td class="" style="height: 70px;  border-right: 3px solid;text-align:right;width:50%">
                </td>
                <td style="padding-left: 10px;line-height: 1.2;" class="right-col">
                    <span> <b style="letter-spacing: 3px;"></b> {{ settings()->name }}</span><br>
                    <span>{{ settings()->email }}</span><br>
                    <span style="  padding-top: 3px; "> {{ settings()->phone }} </span><br>
                </td>
            </tr>
            </tbody>
        </table>
        <hr>
        <table style="width: 100%">
            <thead>
                <tr>
                    <th colspan="5" style="text-align: center"><h3>Merchant Details</h3></th>
                </tr>
                <tr style="background-color: #5969ff;color:white">
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Address</th>
                    <th>Total Shops</th>
                    <th>Total Parcel</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $data['merchant']->business_name }}</td>
                    <td>{{ $data['merchant']->user->mobile }}</td>
                    <td>{{ $data['merchant']->address }}</td>
                    <td> {{ $data['merchant']->merchantShops->count() }}</td>
                    <td> {{ $data['total_parcel'] }}</td>
                </tr>
            </tbody>
        </table>
        <table style="width: 100%" class="test">
            <thead>
                <tr>
                    <th colspan="5" style="text-align: center"><h3  >Total Statistics</h3></th>
                </tr>
                <tr style="background-color: #5969ff;color:white;text-align:left">
                    <th style="text-align: left"> Title
                    </th>
                    <th style="text-align: left"> Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width: 50%" >
                         {{ __('parcelStatus.'.\App\Enums\ParcelStatus::PENDING) }}
                       </td>
                    <td style="width: 50%"> {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::PENDING)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">     {{ __('parcelStatus.'.\App\Enums\ParcelStatus::PICKUP_ASSIGN) }}
                       </td>
                    <td style="width: 50%"> {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::PICKUP_ASSIGN)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">     {{ __('parcelStatus.'.\App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE) }}
                      </td>
                    <td style="width: 50%">  {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">    {{ __('parcelStatus.'.\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE) }}
                        </td>
                    <td style="width: 50%">{{ $data['parcels']->where('status',\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">     {{ __('parcelStatus.'.\App\Enums\ParcelStatus::TRANSFER_TO_HUB) }}
                      </td>
                    <td>  {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::TRANSFER_TO_HUB)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">  {{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN) }}
                      </td>
                    <td>  {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">   {{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE) }}
                      </td>
                    <td>   {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">   {{ __('parcelStatus.'.\App\Enums\ParcelStatus::RETURN_TO_COURIER) }}
                     </td>
                    <td>   {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::RETURN_TO_COURIER)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">  {{ __('parcelStatus.'.\App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT) }}
                        </td>
                    <td>{{ $data['parcels']->where('status',\App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT)->count() }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%">    {{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERED) }}
                      </td>
                    <td>  {{ $data['parcels']->where('status',\App\Enums\ParcelStatus::DELIVERED)->count() }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
