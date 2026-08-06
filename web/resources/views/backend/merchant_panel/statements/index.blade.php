@extends('backend.partials.master')
@section('title')
    {{ __('menus.statements') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant.accounts.statements.index') }}" class="breadcrumb-link">{{ __('menus.statements') }}</a></li>
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
                    <form action="{{route('merchant.accounts.statements.filter')}}"  method="POST">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-4 col-sm-6">
                                <label for="date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="date" class="form-control date_range_picker p-2" value="{{ isset($request->date) ? $request->date : old('date') }}" placeholder="{{ __('merchantPlaceholder.date') }}">
                                @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-4 col-sm-6">
                                <label for="type">{{ __('levels.type') }}</label>
                                <select name="type" class="form-control @error('type') is-invalid @enderror">
                                    <option value="" selected>{{ __('merchantPlaceholder.type') }}</option>
                                        <option value="{{ \App\Enums\AccountHeads::INCOME }}" {{ (isset($request->type) ? $request->type : old('type')) == \App\Enums\AccountHeads::INCOME ? 'selected' : '' }}>{{ __('AccountHeads.'.\App\Enums\AccountHeads::INCOME)}}</option>
                                        <option value="{{ \App\Enums\AccountHeads::EXPENSE }}" {{ (isset($request->type) ? $request->type : old('type')) == \App\Enums\AccountHeads::EXPENSE ? 'selected' : '' }}>{{ __('AccountHeads.'.\App\Enums\AccountHeads::EXPENSE)}}</option>
                                </select>
                                @error('type')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-4 col-sm-6">
                                <label for="parcel_tracking_id">{{ __('levels.track_id')}}</label>
                                <input id="parcel_tracking_id" type="text" name="parcel_tracking_id" placeholder="{{ __('merchantPlaceholder.tracking_id') }}" class="form-control" value="{{ old('parcel_tracking_id',isset($request->parcel_tracking_id) ? $request->parcel_tracking_id:'') }}">
                                @error('parcel_tracking_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-4 col-sm-6 pt-1">
                                <div class="d-flex pt-4">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('merchant.accounts.statements.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('menus.statements') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   ">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('statements.details') }}</th>
                                    <th>{{ __('statements.date') }}</th>
                                    <th>{{ __('levels.type') }}</th>
                                    <th>{{ __('statements.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($statements as $statement)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="w150">
                                            {{ $statement->note }}<br/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="w150">
                                            {{ dateFormat($statement->date)}}
                                        </div>
                                    </td>
                                    <td>
                                        @if($statement->type == \App\Enums\AccountHeads::INCOME)
                                        <span class="badge badge-pill badge-success">{{trans('AccountHeads.'.\App\Enums\AccountHeads::INCOME) }}</span>
                                        @elseif($statement->type == \App\Enums\AccountHeads::EXPENSE)
                                            <span class="badge badge-pill badge-danger">{{trans('AccountHeads.'.\App\Enums\AccountHeads::EXPENSE) }}</span>
                                        @endif
                                    </td>
                                    <td class="@if($statement->type == \App\Enums\AccountHeads::INCOME) text-success @else text-danger @endif">{{settings()->currency}}{{number_format($statement->amount,2)}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $statements->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $statements->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $statements->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $statements->total() }}</span>
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
<!-- css  -->
@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
<!-- js  -->
@push('scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
 @endpush

