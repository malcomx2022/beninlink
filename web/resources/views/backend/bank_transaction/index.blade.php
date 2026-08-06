@extends('backend.partials.master')
@section('title')
    {{ __('menus.bank_transaction') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('bank-transaction.index') }}" class="breadcrumb-link">{{ __('menus.bank_transaction') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('bank-transaction.filter')}}"  method="POST">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6">
                                <label for="date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="date" class="form-control date_range_picker p-2" placeholder="Enter date" value="{{ isset($request->date) ? $request->date : old('date') }}"  >
                                @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6">
                                <label for="type">{{ __('levels.type') }}</label>
                                <select name="type" class="form-control @error('type') is-invalid @enderror">
                                    <option value="" selected>{{ __('merchantPlaceholder.type') }}</option>
                                    @foreach(\config('rxcourier.account_heads_type') as $key => $value)
                                        <option value="{{ $value }}" {{ (isset($request->type) ? $request->type : old('type')) == $value ? 'selected' : '' }}>{{ __('AccountHeads.'.$value)}}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4  col-md-6">
                                <label for="account">{{ __('levels.account') }}</label>
                                <select name="account" class="form-control @error('account') is-invalid @enderror">
                                    <option value="" selected>{{ __('merchantPlaceholder.account') }} </option>
                                    @foreach ($accounts as $account)
                                        @if ($account->type == App\Enums\AccountType::ADMIN)
                                        <option value="{{$account->id}}" {{ ((isset($request->account) ? $request->account : old('account')) == $account->id) ? 'selected' : '' }}>
                                            @if ($account->gateway == 1)
                                                {{ $account->user->name }} | {{ __('merchant.cash') }}
                                            @else
                                                {{$account->account_no}} | ({{$account->account_holder_name}})
                                            @endif
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('account')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6 pt-1 ">
                                <div class="d-flex pt-4">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('bank-transaction.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <div class="d-flex align-item-center">
                            <p class="h3 mb-0">{{ __('menus.bank_transaction') }}</p>
                            <form action="{{ route('bank.transaction.specific.search') }}" class="d-flex" style="width:60%" >
                                @csrf
                                <input id="Psearch" class="form-control parcelSearch group-input" value="{{ $request->search }}" name="search" type="text" placeholder="Search..">
                                <button type="submit" class="btn btn-sm btn-primary group-btn ml-0">Search</button>
                                @if (isset($search) && count($search) > 0)
                                <a  href="{{ route('bank.transaction.filter.print',['ids'=>$search]) }}" target="_blank" class="btn btn-primary ml-2">{{ __('levels.print') }}</a>
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
                                    <th>{{ __('###') }}</th>
                                    <th>{{ __('levels.account_no') }} | {{__('levels.name')}}</th>
                                    <th>{{ __('levels.type') }}</th>
                                    <th>{{ __('levels.amount') }}</th>
                                    <th>{{ __('levels.date') }}</th>
                                    <th>{{ __('levels.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $n = 0;
                                @endphp
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{++$n}}</td>

                                        <td>
                                            @if ($transaction->fund_transfer_id !==null)
                                                <span>From :</span>
                                                    @if($transaction->fundTransfer->fromAccount->gateway == 1)
                                                        {{@$transaction->fundTransfer->fromAccount->user->name}} (Cash)
                                                    @else
                                                        @if($transaction->fundTransfer->fromAccount->gateway == 3)
                                                            bKash ,
                                                        @elseif ($transaction->fundTransfer->fromAccount->gateway == 4)
                                                            Rocket ,
                                                        @elseif ($transaction->fundTransfer->fromAccount->gateway == 5)
                                                            Nagad ,
                                                        @endif
                                                        {{$transaction->fundTransfer->fromAccount->account_holder_name}}
                                                        ({{$transaction->fundTransfer->fromAccount->account_no}}
                                                        {{$transaction->fundTransfer->fromAccount->branch_name}}
                                                        {{$transaction->fundTransfer->fromAccount->mobile}})
                                                    @endif
                                                </br>
                                                <span>To :</span>
                                                    @if($transaction->fundTransfer->toAccount->gateway == 1)
                                                        {{@$transaction->fundTransfer->toAccount->user->name}} (Cash)
                                                    @else
                                                        @if($transaction->fundTransfer->toAccount->gateway == 3)
                                                            bKash ,
                                                        @elseif ($transaction->fundTransfer->toAccount->gateway == 4)
                                                            Rocket ,
                                                        @elseif ($transaction->fundTransfer->toAccount->gateway == 5)
                                                            Nagad ,
                                                        @endif
                                                        {{$transaction->fundTransfer->toAccount->account_holder_name}}
                                                        ({{$transaction->fundTransfer->toAccount->account_no}}
                                                        {{$transaction->fundTransfer->toAccount->branch_name}}
                                                        {{$transaction->fundTransfer->toAccount->mobile}})
                                                    @endif
                                            @elseif($transaction->income_id !==null && $transaction->income)
                                                @if($transaction->income->account_head_id  == 2)
                                                    From: {{ @$transaction->income->deliveryman->user->name }}</br>
                                                    To:
                                                @elseif($transaction->income->account_head_id  == 1)
                                                    From: {{ @$transaction->income->merchant->business_name }}</br>
                                                    To:
                                                @endif

                                                @if($transaction->account->gateway == 1)
                                                    {{@$transaction->account->user->name}} (Cash)
                                                @else
                                                    @if($transaction->account->gateway == 3)
                                                        bKash ,
                                                    @elseif ($transaction->account->gateway == 4)
                                                        Rocket ,
                                                    @elseif ($transaction->account->gateway == 5)
                                                        Nagad ,
                                                    @endif
                                                    {{$transaction->account->account_holder_name}}
                                                    ({{$transaction->account->account_no}}
                                                    {{$transaction->account->branch_name}}
                                                    {{$transaction->account->mobile}})
                                                @endif
                                            @elseif($transaction->cash_received_dvry !==null && $transaction->HubCashReceivedFromDeliveryman)

                                                From: {{ @$transaction->HubCashReceivedFromDeliveryman->deliveryman->user->name }}</br>
                                                To:

                                                @if($transaction->account->gateway == 1)
                                                    {{@$transaction->account->user->name}} (Cash)
                                                @else
                                                    @if($transaction->account->gateway == 3)
                                                        bKash ,
                                                    @elseif ($transaction->account->gateway == 4)
                                                        Rocket ,
                                                    @elseif ($transaction->account->gateway == 5)
                                                        Nagad ,
                                                    @endif
                                                    {{$transaction->account->account_holder_name}}
                                                    ({{$transaction->account->account_no}}
                                                    {{$transaction->account->branch_name}}
                                                    {{$transaction->account->mobile}})
                                                @endif

                                            @elseif($transaction->expense_id !==null && $transaction->expense)

                                                @if($transaction->expense !==null)
                                                    From:
                                                @endif
                                                @if($transaction->account->gateway == 1)
                                                    {{@$transaction->account->user->name}} (Cash)
                                                @else
                                                    @if($transaction->account->gateway == 3)
                                                        bKash ,
                                                    @elseif ($transaction->account->gateway == 4)
                                                        Rocket ,
                                                    @elseif ($transaction->account->gateway == 5)
                                                        Nagad ,
                                                    @endif
                                                    {{$transaction->account->account_holder_name}}
                                                    ({{$transaction->account->account_no}}
                                                    {{$transaction->account->branch_name}}
                                                    {{$transaction->account->mobile}})
                                                @endif

                                                </br>
                                                @if($transaction->expense !==null)
                                                    To:{{ @$transaction->expense->deliveryman->user->name }}
                                                @endif


                                            @else
                                                @if($transaction->account->gateway == 1)
                                                    {{@$transaction->account->user->name}} (Cash)
                                                @else
                                                    @if($transaction->account->gateway == 3)
                                                        bKash ,
                                                    @elseif ($transaction->account->gateway == 4)
                                                        Rocket ,
                                                    @elseif ($transaction->account->gateway == 5)
                                                        Nagad ,
                                                    @endif
                                                    {{$transaction->account->account_holder_name}}
                                                    ({{$transaction->account->account_no}}
                                                    {{$transaction->account->branch_name}}
                                                    {{$transaction->account->mobile}})
                                                @endif
                                            @endif
                                        </td>

                                        <td>{!! $transaction->account_type !!}</td>
                                        <td>{{settings()->currency}}{{$transaction->amount}}</td>
                                        <td>{{dateFormat($transaction->date)}}</td>
                                        <td>{{$transaction->note}}</td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>

                    </div>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <span>{{ $transactions->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $transactions->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $transactions->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $transactions->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>

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

