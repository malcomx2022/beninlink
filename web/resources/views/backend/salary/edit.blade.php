@extends('backend.partials.master')
@section('title')
   {{ __('salary.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('levels.edit') }} {{ __('salary.title') }}</h2>
                    <form action="{{route('salary.update',['id'=>$singleSalary->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="date">{{ __('salary.month')}}</label> <span class="text-danger">*</span>
                                    <input type="text" id="month" data-toggle="month" name="month" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',$singleSalary->month)}}" required>
                                    @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group  "  >
                                    <label for="user_id">{{ __('levels.user') }} <span class="text-danger">*</span></label>
                                    <select style="width: 100%" id="user_id"  name="user_id" class="form-control salary_user @error('user_id') is-invalid @enderror" data-url="{{ route('salary.users') }}">
                                        <option value="" > {{ __('menus.select') }} {{ __('user.title') }}</option>
                                        <option value="{{ $singleSalary->user_id }}" selected>{{ $singleSalary->user->name }}</option>
                                    </select>
                                    <div class="mt-2 active" id="user_salary">
                                        @foreach ($singleSalary->getSalary as $getsalary)
                                            @if ($getsalary->month == $singleSalary->month)
                                                {{ $getsalary->amount }}
                                            @endif
                                        @endforeach
                                    </div>
                                    @error('user_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="date">{{ __('levels.date')}}</label> <span class="text-danger">*</span>
                                    <input type="text" id="date" data-toggle="datepicker" name="date" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',$singleSalary->date)}}" required>
                                    @error('date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="account_id">{{ __('levels.from_account')}}</label> <span class="text-danger">*</span>
                                    <select id="account_id" name="account_id" class="form-control" required>
                                        <option selected disabled>{{ __('menus.select') }}</option>
                                        @foreach($accounts as $account)
                                            @if ($account->type == App\Enums\AccountType::ADMIN)
                                                <option {{ (old('account_id',$singleSalary->account_id) == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">
                                                    @if($account->gateway == 1)
                                                    {{$account->user->name}} (Cash)
                                                @else
                                                    @if($account->gateway == 3)
                                                        bKash ,
                                                    @elseif ($account->gateway == 4)
                                                        Rocket ,
                                                    @elseif ($account->gateway == 5)
                                                        Nagad ,
                                                    @endif
                                                    {{$account->account_holder_name}}
                                                    ({{$account->account_no}}
                                                    {{$account->branch_name}}
                                                    {{$account->mobile}})
                                                @endif
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class=" text-danger mt-2" id="account_balance_" >{{ @$singleSalary->account->balance }}</div>
                                    @error('account_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                    <div class="text-danger mt-2" id="account_balance_">{{old('account_balance')}}</div>
                                    <input type="hidden" name="account_balance" value="{{old('account_balance')}}" id="account_balance">
                                </div>
                                <div class="form-group amount_div">
                                    <label for="amount">{{ __('levels.amount')}}</label> <span class="text-danger">*</span>
                                    <div class="form-control-wrap">
                                        <input type="number" class="form-control cash-collection"   value="{{ old('amount',$singleSalary->amount) }}" name="amount" placeholder="{{ __('placeholder.Enter_Amount') }}" required>
                                        @error('amount')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                        <div class="check_message"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="note">{{ __('levels.note')}}</label>
                                    <div class="form-control-wrap deliveryman-search">
                                       <textarea class="form-control" name="note" rows="5">{{ $singleSalary->note }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('salary.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script>
        var merchantUrl = '{{ route('parcel.merchant.get') }}';
    </script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $("#month").datepicker( {
            format: "yyyy-mm",
            startView: "months",
            minViewMode: "months"
        });
        </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ static_asset('backend/js/expense/salary.js') }}"></script>
@endpush

