<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ asset('backend/') }}/images/favicon.jpg">
    <style>
        .page {
            margin-top: 2px;
            display: inline-block;
        }

        .main-table {
            border: 1px dashed;
            padding: 2px;
        }

        .label-section {
            width: 7.5cm;

        }

        @media print {
            .label-size{
                display: none;
            }
            .page {
                page-break-after: always !important;
                margin-top: 3px;
            }

            .main-table {
                border: unset;
                padding: unset;
            }

            * {
                font-size: 10px !important;
            }

            .label-section {
                width: unset;
                height: unset;
            }

            @page {
                size: 7.5cm 3.5cm;
                margin: 5px !important;
            }

            body {
                zoom: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="label-size" style="margin-bottom: 5px">
        <span style="color:red">Label size: 7.5cm X 3.5cm</span>
    </div>
    @foreach ($parcels as $parcel)
        <div class="page" style="padding-top:0px;">
            <div class="label-section">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="main-table">
                    <tbody>
                        <tr>
                            <td colspan="3" width="100%">
                                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                                    <tbody>
                                        <tr>
                                            <td style="width:140px;  border-right: 1px solid;padding-right:2px">
                                                <img alt="Logo" src="{{ settings()->logo_image }}" class="logo"
                                                    style="width:70px">
                                            </td>
                                            <td style="padding-left: 2px;width:100%;">
                                                <b>{{ __('Merchant:') }}</b> {{ $parcel->merchant->business_name }},
                                                {{ $parcel->merchant->address }}, {{ $parcel->merchant->user->mobile }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                                    <tbody>
                                        <tr>
                                            <td
                                                style="line-height:1.2;width:50%; border-right: 1px solid;border-top:#000000  1px solid;border-bottom:#000000 1px solid; padding:2px;padding-left:0px">
                                                <div><b>{{ __('Customer :') }}</b> {{ $parcel->customer_name }} ,
                                                    {{ $parcel->customer_phone }}, {{ $parcel->customer_address }}
                                                </div>
                                            </td>
                                            <td
                                                style="line-height:1.2;width:50%;border-top:#000000  1px solid;border-bottom:#000000 1px solid;padding:2px">
                                                <div>
                                                    <b>Hub</b> : <span>{{ optional($parcel->hub)->name }}</span><br>
                                                    <b>Cash</b> : <span> {{ $parcel->cash_collection }}</span></br>
                                                    <b>Route</b> :<span> ISD </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:center!important">
                                <div style="display: inline-block;margin:auto;margin-top:3px">{!! $parcel->barcodeprint !!}
                                </div>
                                <div>{{ $parcel->tracking_id }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
    <script>
        window.print();
    </script>
</body>

</html>
