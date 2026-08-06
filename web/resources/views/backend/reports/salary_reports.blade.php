@extends('backend.partials.master')
@section('title')
   {{ __('reports.salary_reports') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('reports.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('salary.reports') }}" class="breadcrumb-link">{{ __('reports.salary_reports') }}</a></li>

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
                    <form action="{{route('reports.salary.reports')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12  col-xl-3 col-lg-4 col-md-4 col-sm-6">
                                <label for="salary_date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="salary_date" class="form-control date_range_picker" value="{{ old('salary_date',$request->salary_date) }}">

                                @error('salary_date')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12  col-xl-3 col-lg-4 col-md-4 col-sm-6">
                                <label for="date">{{ __('salary.month')}}</label> <span class="text-danger"></span>
                                <input type="text" id="month" data-toggle="month" name="month" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',isset($request->month) ? $request->month:date('Y-m'))}}">
                                @error('date')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12  col-xl-3 col-lg-4 col-md-4 col-sm-6"  >
                                <label for="user_id">{{ __('levels.user') }}</label>
                                <select multiple='multiple' style="width: 100%" id="user_id"  name="user_id[]" class="form-control salary_user @error('user_id') is-invalid @enderror" data-url="{{ route('salary.users') }}">
                                    <option value="" > {{ __('Select User') }}</option>
                                </select>

                                @error('user_id')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-4 col-sm-6 pt-1">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 d-flex justify-content pl-0">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('salary.reports') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @if(isset($salaries))
            <div class="card">
                <div class="card-header">
                    <div class="row px-4">
                        <div class="col-md-6">
                            <h3>{{ __('reports.salary_reports') }}</h3>
                        </div>
                        <div class="col-md-6 text-right">
                            @if($salaries->count() > 0)
                                <button type="button" id="exportTable" data-title="Salary Reports" data-filename="SalaryReports" class="btn btn-success">{{ __('menus.export') }}</button>
                                <a href="{{ route('salary.reports.print.page',['salary_date'=>$request->salary_date,'month'=>$request->month,'user_id[]'=>$request->user_id,]) }}" class="btn btn-primary" target="_blank">{{ __('reports.print') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive"  >
                        <table class="table   " style="width:100%">
                            @php $i=1; @endphp
                            @foreach($salaries as $key=>$salarys)
                                <thead>
                                    <tr class="bg-primary">
                                        <td></td>
                                        <td class="text-center">  </td>
                                        <td class="ttext-white" style="color: white!important"> {{\Carbon\Carbon::createFromFormat('Y-m',$key)->format('M Y')}}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('reports.user_details') }}</th>
                                        <th>{{ __('levels.month') }}</th>
                                        <th>{{ __('reports.salary') }}</th>
                                        <th>{{ __('reports.paid_amount') }}</th>
                                        <th>{{ __('levels.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($salarys as $salary )
                                        <tr>
                                            <th>{{ $i++ }}</th>
                                            <td>
                                                <span> {{@$salary->user->name}}</span><br>
                                                <span> {{@$salary->user->email}}</span><br>
                                                <span> {{@$salary->user->mobile}}</span><br>
                                            </td>
                                            <td>{{\Carbon\Carbon::createFromFormat('Y-m',$key)->format('M Y')}}</td>
                                            <td> {{ settings()->currency }}{{$salary->amount}}</td>
                                            @if(!blank($salaryPayments)  && isset($salaryPayments[$key]))
                                                @php($status = true)
                                                    <td> {{ settings()->currency }}{{salaryPayments($salary->user_id, $salaryPayments[$key])}}</td>
                                                @if (salaryPayments($salary->user_id, $salaryPayments[$key]) > 0 && $salary->amount > salaryPayments($salary->user_id, $salaryPayments[$key]) )
                                                    <td><span class="badge badge-warning">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::PARTIAL_PAID) }}</span></td>
                                                @elseif(salaryPayments($salary->user_id, $salaryPayments[$key]) == 0)
                                                    <td><span class="badge badge-danger">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::UNPAID) }}</span></td>
                                                @else
                                                    <td><span class="badge badge-success">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::PAID) }}</span></td>
                                                @endif
                                            @else
                                                <td> {{ settings()->currency }}{{number_format(0,2)}}</td>
                                                <td><span class="badge badge-danger">{{ __('SalaryStatus.'.App\Enums\SalaryStatus::UNPAID) }}</span></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            @endif
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
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
    <script>
        var merchantUrl = '{{ route('parcel.merchant.get') }}';
        var merchantID = '{{ $request->parcel_merchant_id }}';
        var deliveryManID = '{{ $request->parcel_deliveryman_id }}';
        var pickupManID = '{{ $request->parcel_pickupman_id }}';
        var dateParcel = '{{ $request->parcel_date }}';
    </script>
    <script src="{{ static_asset('backend/js/parcel/filter.js') }}"></script>

    <script src="{{ static_asset('backend/js/reports/print.js') }}"></script>
    <script src="{{ static_asset('backend/js/reports/jquery.table2excel.min.js') }}"></script>
    <script src="{{ static_asset('backend/js/reports/reports.js') }}"></script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript">
        $("#month").datepicker( {
            format: "yyyy-mm",
            startView: "months",
            minViewMode: "months"
        });
    </script>
 @endpush



