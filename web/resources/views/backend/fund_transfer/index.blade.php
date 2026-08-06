@extends('backend.partials.master')
@section('title')
    {{ __('fund_transfer.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('account.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('fund-transfer.index')}}" class="breadcrumb-link">{{ __('fund_transfer.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('fund.transfer.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                                <label for="parcel_date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="date" placeholder="Enter date" class="form-control date_range_picker" value="{{ old('parcel_date',$request->date) }}">
                            </div>
                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                                <label for="hub_id">{{ __('levels.from_account') }}</label>
                                <select style="width: 100%" id="hub_id"  name="from_account" class="form-control"  >
                                    <option value="" > {{ __('menus.select') }} {{ __('levels.from_account') }}</option>
                                    @foreach ($accounts as $account)
                                        @if ($account->gateway == 1)
                                            <option value="{{ $account->id }}" @if($request->from_account == $account->id) selected @endif>{{ $account->user->name }} | {{ __('merchant.cash') }} : {{ $account->balance }} </option>
                                        @elseif($account->gateway == 3 || $account->gateway == 4 || $account->gateway == 5)
                                            <option value="{{ $account->id }}" @if($request->from_account == $account->id) selected @endif >{{$account->account_holder_name}} |No : {{ $account->mobile }}|  @if($account->account_type == 1) {{ __('merchant.title') }} @else {{ __('placeholder.persional') }} @endif | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }} </option>
                                        @else
                                            <option value="{{ $account->id }}" @if($request->from_account == $account->id) selected @endif >{{$account->account_holder_name}} | A.No : {{ $account->account_no }} | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                                <label for="hub_id">{{ __('levels.to_account') }}</label>
                                <select style="width: 100%" id="hub_id"  name="to_account" class="form-control"  >
                                    <option value="" > {{ __('menus.select') }} {{ __('levels.to_account') }}</option>
                                    @foreach ($accounts as $account)
                                        @if ($account->gateway == 1)
                                            <option value="{{ $account->id }}" @if($request->to_account == $account->id) selected @endif>{{ $account->user->name }} | {{ __('merchant.cash') }} : {{ $account->balance }} </option>
                                        @elseif($account->gateway == 3 || $account->gateway == 4 || $account->gateway == 5)
                                            <option value="{{ $account->id }}" @if($request->to_account == $account->id) selected @endif>{{$account->account_holder_name}} |No : {{ $account->mobile }}|  @if($account->account_type == 1) {{ __('merchant.title') }} @else {{ __('placeholder.persional') }} @endif | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }} </option>
                                        @else
                                            <option value="{{ $account->id }}" @if($request->to_account == $account->id) selected @endif>{{$account->account_holder_name}} | A.No : {{ $account->account_no }} | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-12 col-md-4 col-lg-4 col-xl-3 pt-1">
                                <div class="col-12 pt-4 d-flex justify-content pl-0">
                                    <button type="submit" class="btn btn-sm btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('fund-transfer.index') }}" class="btn btn-sm btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-10 d-lg-none"> <p class="h3 mb-0">{{ __('fund_transfer.title') }}</p></div>
                    <div class="col-10 d-lg-block">
                        <div class="d-flex align-item-center">
                            <p class="h3 mb-0">{{ __('fund_transfer.title') }}</p>
                            <form action="{{ route('fund.transfer.specific.search') }}" class="d-flex  " style="width:60%">
                                @csrf
                                <input id="Psearch" class="form-control parcelSearch group-input" value="{{ $request->search }}" name="search" type="text" placeholder="Search..">
                                <button type="submit" class="btn btn-primary group-btn ml-0">Search</button>
                                @if (isset($search) && count($search) > 0)
                                <a  href="{{ route('fund.transfer.search.filter.print',['ids'=>$search]) }}" target="_blank" class="btn btn-primary ml-2">{{ __('levels.print') }}</a>
                                @endif
                            </form>
                        </div>
                    </div>
                    @if(hasPermission('fund_transfer_create') )
                    <div class="col-2">
                        <a href="{{route('fund-transfer.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                    <div class="col-12 d-lg-none  mt-2">
                        <div class="d-flex align-item-center">
                            <form action="{{ route('fund.transfer.specific.search') }}" class="d-flex" style="width:100%">
                                @csrf
                                <input id="Psearch" class="form-control parcelSearch fundTransferSearch group-input  " value="{{ $request->search }}" name="search" type="text" placeholder="Search..">
                                <button type="submit" class="btn btn-primary group-btn ml-0">Search</button>
                                @if (isset($search) && count($search) > 0)
                                <a  href="{{ route('fund.transfer.search.filter.print',['ids'=>$search]) }}" target="_blank" class="btn btn-primary ml-2">{{ __('levels.print') }}</a>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th  >{{ __('levels.from_account') }}</th>
                                    <th  >{{ __('levels.to_account') }}</th>
                                    <th>{{ __('levels.date') }}</th>
                                    <th>{{ __('levels.amount') }}</th>
                                    @if(hasPermission('fund_transfer_update') == true || hasPermission('fund_transfer_delete') == true )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($fund_transfers as $fund_transfer)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="row width300px">
                                            <div class="col-4">
                                                <img src="{{$fund_transfer->fromAccount->user->image}}" alt="Image" class="rounded" width="40" height="40">
                                            </div>
                                            <div class="col-8">
                                                <strong> {{$fund_transfer->fromAccount->user->name}}</strong><br>
                                                <span> {{$fund_transfer->fromAccount->user->email}}</span><br>
                                            </div>
                                        </div>
                                        @if ($fund_transfer->fromAccount->gateway == 1)
                                            Cash
                                        @elseif ($fund_transfer->fromAccount->gateway == 2)
                                        {{-- Bank info --}}
                                            <div class="row width300px">
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
                                            <div class="row width300px">
                                                <div class="col-4">{{__('levels.branch_name')}}</div>
                                                <div class="col-8">: {{$fund_transfer->fromAccount->branch_name}}</div>
                                            </div>
                                            <div class="row width300px">
                                                <div class="col-4">{{__('levels.account_no')}}</div>
                                                <div class="col-8">: {{$fund_transfer->fromAccount->account_no}}</div>
                                            </div>
                                        @elseif ($fund_transfer->fromAccount->gateway == 3)
                                        {{-- Mobile bank info --}}
                                            <div class="row width300px">
                                                <div class="col-4">{{__('levels.mobile')}}</div>
                                                <div class="col-8">: {{$fund_transfer->fromAccount->mobile}}</div>
                                            </div>
                                            <div class="row width300px">
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
                                        <div class="row width300px">
                                            <div class="col-4">{{__('levels.balance')}}</div>
                                            <div class="col-8">: {{settings()->currency}}{{$fund_transfer->fromAccount->balance}}</div>
                                        </div>
                                        <div class="row width300px">
                                            <div class="col-4">{{__('levels.opening_balance')}}</div>
                                            <div class="col-8">: {{settings()->currency}}{{$fund_transfer->fromAccount->opening_balance}}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row width300px">
                                            <div class="col-4">
                                                <img src="{{$fund_transfer->toAccount->user->image}}" alt="Image" class="rounded" width="40" height="40">
                                            </div>
                                            <div class="col-8">
                                                <strong> {{$fund_transfer->toAccount->user->name}}</strong><br>
                                                <span> {{$fund_transfer->toAccount->user->email}}</span><br>
                                            </div>
                                        </div>
                                        @if ($fund_transfer->toAccount->gateway == 1)
                                        <div class="row width300px">
                                            <div class="col-4">Payment method</div>
                                            <div class="col-8">: Cash
                                            </div>
                                        </div>
                                        @elseif ($fund_transfer->toAccount->gateway == 2)
                                        {{-- Bank info --}}
                                            <div class="row width300px">
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
                                            <div class="row width300px">
                                                <div class="col-4">{{__('levels.branch_name')}}</div>
                                                <div class="col-8">: {{$fund_transfer->toAccount->branch_name}}</div>
                                            </div>
                                            <div class="row width300px">
                                                <div class="col-4">{{__('levels.account_no')}}</div>
                                                <div class="col-8">: {{$fund_transfer->toAccount->account_no}}</div>
                                            </div>
                                        @elseif ($fund_transfer->toAccount->gateway == 3)
                                        {{-- Mobile bank info --}}
                                            <div class="row width300px">
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
                                        <div class="row width300px">
                                            <div class="col-4">{{__('levels.balance')}}</div>
                                            <div class="col-8">: {{settings()->currency}}{{$fund_transfer->toAccount->balance}}</div>
                                        </div>
                                        <div class="row width300px">
                                            <div class="col-4">{{__('levels.opening_balance')}}</div>
                                            <div class="col-8">: {{settings()->currency}}{{$fund_transfer->toAccount->opening_balance}}</div>
                                        </div>
                                    </td>
                                    <td>{{dateFormat($fund_transfer->date)}}</td>
                                    <td>{{settings()->currency}}{{$fund_transfer->amount}}</td>
                                    @if(hasPermission('fund_transfer_update') == true || hasPermission('fund_transfer_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">

                                            @if(hasPermission('fund_transfer_update') == true)
                                                <a href="{{route('fund-transfer.edit',$fund_transfer->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                            @endif
                                            @if(hasPermission('fund_transfer_delete') == true )
                                                <form id="delete" value="Test" action="{{route('fund-transfer.delete',$fund_transfer->id)}}" method="POST" data-title="{{ __('delete.fund_transfer') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
                                            @endif

                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <span>{{ $fund_transfers->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $fund_transfers->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $fund_transfers->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $fund_transfers->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>

            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
<!-- css  -->
@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

<!-- js  -->
@push('scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
 @endpush

