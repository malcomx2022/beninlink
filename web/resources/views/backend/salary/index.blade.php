@extends('backend.partials.master')
@section('title')
   {{ __('salary.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('account.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('salary.index') }}" class="breadcrumb-link">{{ __('salary.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('salary.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-md-4" >
                                <label for="user_id">{{ __('levels.user') }}</label>
                                <select style="width: 100%" id="user_id"  name="user_id" class="form-control salary_user @error('user_id') is-invalid @enderror" data-url="{{ route('salary.users') }}">
                                    <option value="" > {{ __('menus.select') }} {{ __('user.title') }}</option>
                                </select>
                                @error('user_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-4">
                                <label for="date">{{ __('salary.month')}}</label> <span class="text-danger"></span>
                                <input type="text" id="month" data-toggle="month" name="month" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',isset($request->month) ? $request->month:date('Y-m'))}}">
                                @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-4 pt-1">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 pl-0 d-flex justify-content">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('salary.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('salary.title') }}</p>
                    </div>
                    <div class="col-6">
                        @if(hasPermission('salary_create') == true )
                            <a href="{{route('salary.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.user')}}</th>
                                    <th>{{ __('levels.from_account')}}</th>
                                    <th>{{ __('levels.month')}}</th>
                                    <th>{{ __('levels.date')}}</th>
                                    <th>{{ __('levels.note')}}</th>
                                    <th>{{ __('levels.amount')}}</th>
                                    @if(hasPermission('salary_update') == true || hasPermission('salary_delete') == true )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach ($salaries as $salary)
                                    <tr>
                                        <td>{{++$i}}</td>
                                        <td>
                                            <div class="row width300px">
                                                <div class="col-3">
                                                    <img src="{{@$salary->user->image}}" alt="user" class="rounded" width="40" height="40">
                                                </div>
                                                <div class="col-9">
                                                    <strong> {{@$salary->user->name}}</strong>
                                                    <p> {{@$salary->user->email}}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if(@$salary->account->account_holder_name)
                                                <div class="row">
                                                    <div  class="col-12"> {{$salary->account->branch_name}} </div>
                                                </div>
                                            @endif
                                            @if(@$salary->account->account_no)
                                                <div class="row">
                                                    <div  class="col-12"> {{$salary->account->account_no}} </div>
                                                </div>
                                            @endif
                                            @if(@$salary->account->branch_name)
                                                <div class="row">
                                                    <div  class="col-12"> {{$salary->account->branch_name}} </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{\Carbon\Carbon::createFromFormat('Y-m',$salary->month)->format('M Y')}}</td>
                                        <td>{{dateFormat($salary->date)}}</td>
                                        <td>{{\Str::limit($salary->note,100,' ...')}}</td>
                                        <td>{{$salary->amount}}</td>
                                        @if(hasPermission('salary_read') == true || hasPermission('salary_update') == true || hasPermission('salary_delete') == true )
                                            <td>
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if(hasPermission('salary_update') == true)
                                                            <a href="{{route('salary.edit',$salary->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                        @endif
                                                        @if(hasPermission('salary_read') == true)
                                                            <a href="{{route('salary.pay.slip',$salary->id)}}" target="_blank" class="dropdown-item"><i class="fas fa-print" aria-hidden="true"></i> {{ __('levels.pay_slip') }}</a>
                                                        @endif
                                                        @if(hasPermission('salary_delete') == true )
                                                            <form id="delete" action="{{route('salary.delete',$salary->id)}}" method="POST" data-title="{{ __('delete.salary') }}">
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
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $salaries->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $salaries->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $salaries->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $salaries->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        #selectAssignType .select2-container .select2-selection--single {
            height: 32px !important;
        }
    </style>
@endpush
<!-- js  -->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="{{ static_asset('backend/js/filter/index.js') }}"></script>
    <script type="text/javascript">
        $("#month").datepicker( {
            format: "yyyy-mm",
            startView: "months",
            minViewMode: "months"
        });
    </script>
@endpush

