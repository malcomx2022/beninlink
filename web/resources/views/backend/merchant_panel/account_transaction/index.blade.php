@extends('backend.partials.master')
@section('title')
    {{ __('menus.account_transaction') }}  {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant.accounts.account-transaction.index') }}" class="breadcrumb-link">{{ __('menus.account_transaction') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                    <form action="{{route('merchant.accounts.account-transaction.filter')}}"  method="POST">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-lg-4 col-md-4 col-sm-6 col-xl-3 ">
                                <label for="date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="date" class="form-control date_range_picker p-2" value="{{ isset($request->date) ? $request->date : old('date') }}" placeholder="{{ __('merchantPlaceholder.date') }}">
                                @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-4 col-sm-6 col-xl-3">
                                <label for="type">{{ __('levels.type') }}</label>
                                <select name="type" class="form-control @error('type') is-invalid @enderror">
                                    <option value="" selected>{{ __('merchantPlaceholder.type') }}</option>
                                    @foreach(\config('rxcourier.approval_status') as $key => $value)
                                        <option value="{{ $value }}" {{ (isset($request->type) ? $request->type : old('type')) == $value ? 'selected' : '' }}>{{ __('Approvalstatus.'.$value)}}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-4 col-sm-6 col-xl-3">
                                <label for="account">{{ __('levels.account') }}</label>
                                <select name="account" class="form-control @error('account') is-invalid @enderror">
                                    <option value="" selected>{{ __('merchantPlaceholder.account') }}</option>
                                    @foreach ($accounts as $account)
                                        @if ($account->payment_method == 'bank')
                                            <option value="{{$account->id}}" {{ ((isset($request->account) ? $request->account : old('account')) == $account->id) ? 'selected' : '' }}>{{$account->branch_name}} ({{$account->account_no}})</option>
                                        @elseif ($account->payment_method == 'mobile')
                                            <option value="{{$account->id}}" {{ ((isset($request->account) ? $request->account : old('account')) == $account->id) ? 'selected' : '' }}>{{$account->mobile_company}} ({{$account->mobile_no}})</option>
                                        @endif

                                    @endforeach
                                </select>
                                @error('account')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-4 col-sm-6 col-xl-3 pt-1">
                                <div class="d-flex pt-4">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('merchant.accounts.account-transaction.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <p class="h3">{{ __('menus.account_transaction') }}</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">

                        <table class="table   ">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('paymentrequest.account_details') }}</th>
                                    <th>{{ __('merchantmanage.transaction_id') }}</th>
                                    <th>{{ __('paymentrequest.request_date') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('merchantmanage.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp

                                @foreach($transactions as $transaction)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="w200">
                                            @if ($transaction->merchantAccount->payment_method == 'bank')
                                                {{ $transaction->merchantAccount->holder_name }}<br/>
                                                {{ $transaction->merchantAccount->bank_name }}<br/>
                                                {{ $transaction->merchantAccount->account_no }}<br/>
                                                {{ $transaction->merchantAccount->branch_name }}<br/>
                                                {{ $transaction->merchantAccount->routing_no }}<br/>
                                            @elseif ($transaction->merchantAccount->payment_method == 'mobile')
                                                {{ $transaction->merchantAccount->mobile_company }}<br/>
                                                {{ $transaction->merchantAccount->mobile_no }}<br/>
                                                {{ $transaction->merchantAccount->account_type }}<br/>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{$transaction->transaction_id}}</td>
                                    <td>
                                        <div class="w200">
                                            {{ date('d M Y H:i:s a',strtotime($transaction->created_at)) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($transaction->status == \App\Enums\ApprovalStatus::REJECT)
                                        <span class="badge badge-pill badge-danger">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::REJECT) }}</span>
                                        @elseif($transaction->status == \App\Enums\ApprovalStatus::PENDING)
                                        <span class="badge badge-pill badge-warning">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::PENDING) }}</span>
                                        @elseif($transaction->status == \App\Enums\ApprovalStatus::PROCESSED)
                                        <span class="badge badge-pill badge-success">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::PROCESSED) }}</span>
                                        @endif
                                    </td>
                                    <td>{{settings()->currency}}{{$transaction->amount}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
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
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
<!-- js  -->
@push('scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
 @endpush

