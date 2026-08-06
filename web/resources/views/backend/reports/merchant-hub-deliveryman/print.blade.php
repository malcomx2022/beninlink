<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        @if ($user_type == 1)
            Merchant Reports
        @elseif ($user_type == 2)
            Hub Reports
        @elseif ($user_type == 3)
            Deliveryman Reports
        @endif
        | print</title>
    <link rel="shortcut icon" href="{{ static_asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ static_asset('backend/')}}/css/reports_print.css">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
        <style>
            table thead{
                background-color: unset!important;
            }
            body{
                padding:10px!important;
            }
        </style>
</head>
<body>
<div class="print" style="text-align: right" >
    <a href="#" class="btn-danger"  id="close" onclick="window.close()">Close</a>
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
<div class="card" style="border:none" >
    <div class="card-body" >
        <div class="invoice" id="printablediv">

            <div class="row mt-3" style="width:100%">
                <div class="col-sm-12  ">
                    <h3 style="text-align: center">
                            {{ $report_title}}
                        </h3>
                </div>
            </div>
            <div class="row mt-3" style="width:100%">
                <div class="col-sm-12 " style="overflow: hidden">
                    <span  style="float: left">

                    </span>
                    <span style="float: right" > <font style="font-weight: bold">Date :</font>  {{ dateFormat(date('Y-m-d')) }}</span>
                </div>
            </div>
            <hr>
            <div class="row" style="margin-top: 20px">
                <div class="col-sm-12 table-responsive">
                    {{-- content --}}
                    @if($user_type == 1)
                        <div class="merchant">

                            <div class="card mb-5">
                                <div class="card-header">
                                    <div class="row pl-2">
                                        <div class="col-6">
                                            <p class="float-left mb-0 ont-16 font-weight-bold">{{ __('reports.merchant_details') }}</p>
                                        </div>
                                        <div class="col-6 text-right"> </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x:unset">
                                        <table class="table   " style="width:100%">
                                        @php $i=1; @endphp
                                            <thead>
                                                <tr class=" ">
                                                    <td colspan="5">
                                                        <div class="row" style="color:black">
                                                            <span class="col-6 mt-2">
                                                                <div class="row">
                                                                    <span class="col-3">Name</span>
                                                                    <span class="col-9">: {{ $MHDreports['merchant']->business_name }}</span>
                                                                </div>
                                                            </span>
                                                            <span class="col-6 mt-2">
                                                                <div class="row">
                                                                    <span class="col-3">Mobile</span>
                                                                    <span class="col-9">: {{ @$MHDreports['merchant']->user->mobile }}</span>
                                                                </div>
                                                            </span>
                                                            <span class="col-6 mt-2">
                                                                <div class="row">
                                                                    <span class="col-3">Address</span>
                                                                    <span class="col-9">: {{ @$MHDreports['merchant']->address }}</span>
                                                                </div>
                                                            </span>
                                                            <span class="col-6 mt-2">
                                                                <div class="row">
                                                                    <span class="col-3">Total Shops</span>
                                                                    <span class="col-9">: {{ @$MHDreports['merchant']->merchantShops->count() }}</span>
                                                                </div>
                                                            </span>
                                                            <span class="col-6 mt-2">
                                                                <div class="row">
                                                                    <span class="col-3">Total Parcels</span>
                                                                    <span class="col-9">: {{ @$MHDreports['total_parcel']}}</span>
                                                                </div>
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card  ">
                                            <span class="card-header bg-primary pb-1" style="border-bottom: none">
                                                <h4 class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.total_statistics')}}</h4>
                                            </span>
                                            <div class="card-header bg-primary pb-1">
                                                <p class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.parcel_status')}}</p>
                                                <p class="float-right mb-0 ont-16 font-weight-bold">{{  __('reports.count') }}</p>
                                            </div>
                                        <div class="card-body mhd">
                                            <dl class="row card-header pt-0">
                                                <dt class="col-9 font-16 font-weight-bold"> {{ __('parcelStatus.'.\App\Enums\ParcelStatus::PENDING) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::PENDING)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::PICKUP_ASSIGN) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::PICKUP_ASSIGN)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::TRANSFER_TO_HUB) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::TRANSFER_TO_HUB)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold"> {{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE)->count() }} </dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::RETURN_TO_COURIER) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::RETURN_TO_COURIER)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT)->count() }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERED) }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ $MHDreports['parcels']->where('status',\App\Enums\ParcelStatus::DELIVERED)->count() }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <span class="card-header bg-primary pb-1" style="border-bottom: none">
                                            <h4 class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.payable_to_merchant')}}</h4>
                                        </span>
                                        <div class="card-header bg-primary pb-1">
                                            <p class="float-left mb-0 ont-16 font-weight-bold">{{__('levels.title')}}</p>
                                            <p class="float-right mb-0 ont-16 font-weight-bold">{{  __('levels.amount') }}</p>
                                        </div>
                                        <div class="card-body mhd">
                                            <dl class="row card-header pt-0">
                                                <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.total_payable_to_merchant') }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['parcels']->sum('current_payable') }}</dd>
                                            </dl>

                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('reports.total_delivery_charge') }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['parcels']->sum('total_delivery_amount') }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('reports.total_vat') }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['parcels']->sum('vat_amount') }}</dd>
                                            </dl>
                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('reports.merchant_balance') }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['merchant']->current_balance }} </dd>
                                            </dl>

                                            <dl class="row card-header">
                                                <dt class="col-9 font-16 font-weight-bold">{{ __('reports.total_paid_to_merchant') }}</dt>
                                                <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }}  {{ @$MHDreports['total_paid_to_merchant'] }}</dd>
                                            </dl>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    @elseif($user_type == 2){{-- hub --}}

                        <div class="card mb-5">
                            <div class="card-header">
                                <div class="row pl-2">
                                    <div class="col-6">
                                        <p class="float-left mb-0 ont-16 font-weight-bold">{{ __('reports.hub_details') }}</p>
                                    </div>
                                    <div class="col-6 text-right"> </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="overflow-x:unset">
                                    <table class="table" style="width:100%">
                                    @php $i=1; @endphp
                                        <thead style="color:black">
                                            <tr >
                                                <td colspan="5">
                                                    <div class="row">
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Name</span>
                                                                <span class="col-9">: {{ @$MHDreports['hub']->name }}</span>
                                                            </div>
                                                        </span>
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Mobile</span>
                                                                <span class="col-9">: {{ @$MHDreports['hub']->phone }}</span>
                                                            </div>
                                                        </span>
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Address</span>
                                                                <span class="col-9">: {{ @$MHDreports['hub']->address }}</span>
                                                            </div>
                                                        </span>
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Current Balance</span>
                                                                <span class="col-9">: {{ settings()->currency }} {{ @$MHDreports['hub']->current_balance }}</span>
                                                            </div>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                        <span class="card-header bg-primary pb-1" style="border-bottom: none">
                                            <h4 class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.total_statistics')}}</h4>
                                        </span>
                                        <div class="card-header bg-primary">
                                            <dl class="row  pt-0 mb-0" >
                                                <dt class="col-6 text-left ">{{__('reports.titles')}}</dt>
                                                <dd class="col-3 text-left font-16 font-weight-bold" style="padding-left:0px">{{ __('reports.type') }}</dd>
                                                <dd class="col-3 text-center font-16 font-weight-bold">{{  __('reports.amount') }}</dd>
                                            </dl>
                                        </div>
                                    <div class="card-body mhd deliveryman">
                                        @foreach ($MHDreports['hub_statements'] as $h_statement)
                                            <dl class="row card-header pt-0">
                                                <dt class="col-6   " style="font-weight: 200">
                                                    <b class="font-weight-bold"> Note: </b>{{ $h_statement->note }}
                                                </dt>
                                                <dd class="col-3 text-left font-16 font-weight-bold">
                                                    {{ __('AccountHeads.'.$h_statement->type) }}
                                                </dd>
                                                <dd class="col-3 text-right font-16 font-weight-bold">
                                                    {{ settings()->currency }} {{ $h_statement->amount }}
                                                </dd>
                                            </dl>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <span class="card-header bg-primary pb-1" style="border-bottom: none">
                                        <h4 class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.hub_payable')}}</h4>
                                    </span>
                                    <div class="card-header bg-primary " style="border-top:none">
                                            <dl class="row  pt-0 mb-0" >
                                            <dt class="col-9 text-left ">{{__('reports.titles')}}</dt>
                                                <dd class="col-3 text-center font-16 font-weight-bold">{{  __('reports.amount') }}</dd>
                                        </dl>
                                    </div>
                                    <div class="card-body  mhd">
                                        <dl class="row card-header pt-0">
                                            <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.income') }}</dt>
                                            <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['hub_statements']->where('type',\App\Enums\AccountHeads::INCOME)->sum('amount') }}</dd>
                                        </dl>
                                        <dl class="row card-header ">
                                            <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.expense') }}</dt>
                                            <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['hub_statements']->where('type',\App\Enums\AccountHeads::EXPENSE)->sum('amount') }}</dd>
                                        </dl>
                                        <dl class="row card-header ">
                                            <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.hub_payable') }}</dt>
                                            <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }}
                                                {{ @$MHDreports['hub_statements']->where('type',\App\Enums\AccountHeads::INCOME)->sum('amount') -   @$MHDreports['hub_statements']->where('type',\App\Enums\AccountHeads::EXPENSE)->sum('amount') }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($user_type == 3)
                        <div class="card mb-5"  >
                            <div class="card-header">
                                <div class="row pl-2">
                                    <div class="col-6">
                                        <p class="float-left mb-0 ont-16 font-weight-bold">{{ __('reports.deliveryman_details') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="overflow-x:unset">
                                    <table class="table   " style="width:100%">
                                        @php $i=1; @endphp
                                        <thead style="color: black" >
                                            <tr class=" ">
                                                <td colspan="5">
                                                    <div class="row">
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Name</span>
                                                                <span class="col-9">: {{ @$MHDreports['deliveryman']->user->name }}</span>
                                                            </div>
                                                        </span>
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Mobile</span>
                                                                <span class="col-9">: {{ @$MHDreports['deliveryman']->user->mobile }}</span>
                                                            </div>
                                                        </span>
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Address</span>
                                                                <span class="col-9">: {{ @$MHDreports['deliveryman']->user->address }}</span>
                                                            </div>
                                                        </span>
                                                        <span class="col-6 mt-2">
                                                            <div class="row">
                                                                <span class="col-3">Current Balance</span>
                                                                <span class="col-9">: {{ settings()->currency }}  {{ @$MHDreports['deliveryman']->current_balance }}</span>
                                                            </div>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                        <span class="card-header bg-primary pb-1" style="border-bottom: none">
                                            <h4 class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.total_statistics')}}</h4>
                                        </span>
                                        <div class="card-header bg-primary ">
                                            <dl class="row  pt-0 mb-0" >
                                                <dt class="col-6 text-left ">{{__('reports.titles')}}</dt>
                                                <dd class="col-3 text-left font-16 font-weight-bold" style="padding-left:0px">{{ __('reports.type') }}</dd>
                                                <dd class="col-3 text-center font-16 font-weight-bold">{{  __('reports.amount') }}</dd>
                                            </dl>
                                        </div>
                                    <div class="card-body mhd deliveryman">
                                        @foreach ($MHDreports['deliveryman_statements'] as $d_statement)
                                            <dl class="row card-header pt-0">

                                                <dt class="col-6   " style="font-weight: 200">
                                                    @if($d_statement->parcel_id)
                                                        <b class="font-weight-bold">Parcel :</b> <a class="active" href="{{ route('parcel.details',$d_statement->parcel_id) }}" target="_blank">{{ @$d_statement->parcel->tracking_id }}</a><br/>
                                                    @endif
                                                <b class="font-weight-bold"> Note: </b>{{ $d_statement->note }}
                                                </dt>
                                                <dd class="col-3 text-left font-16 font-weight-bold">
                                                        {{ __('AccountHeads.'.$d_statement->type) }}
                                                </dd>
                                                <dd class="col-3 text-right font-16 font-weight-bold">
                                                    {{ settings()->currency }} {{ $d_statement->amount }}
                                                </dd>
                                            </dl>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <span class="card-header bg-primary pb-1" style="border-bottom: none">
                                        <h4 class="float-left mb-0 ont-16 font-weight-bold">{{__('reports.deliveryman')}}</h4>
                                    </span>
                                    <div class="card-header bg-primary pb-1">
                                        <p class="float-left mb-0 ont-16 font-weight-bold">{{__('levels.title')}}</p>
                                        <p class="float-right mb-0 ont-16 font-weight-bold">{{  __('levels.amount') }}</p>
                                    </div>
                                    <div class="card-body mhd">
                                        <dl class="row card-header pt-0">
                                            <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.income') }}</dt>
                                            <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['deliveryman_statements']->where('type',\App\Enums\AccountHeads::INCOME)->sum('amount') }}</dd>
                                        </dl>
                                        <dl class="row card-header ">
                                            <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.expense') }}</dt>
                                            <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }} {{ @$MHDreports['deliveryman_statements']->where('type',\App\Enums\AccountHeads::EXPENSE)->sum('amount') }}</dd>
                                        </dl>
                                        <dl class="row card-header ">
                                            <dt class="col-9 font-16 font-weight-bold"> {{ __('reports.balance') }}</dt>
                                            <dd class="col-3 text-right font-16 font-weight-bold">{{ settings()->currency }}
                                                {{ @$MHDreports['deliveryman_statements']->where('type',\App\Enums\AccountHeads::INCOME)->sum('amount') -   @$MHDreports['deliveryman_statements']->where('type',\App\Enums\AccountHeads::EXPENSE)->sum('amount') }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
