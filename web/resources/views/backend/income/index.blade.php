@extends('backend.partials.master')
@section('title')
    {{ __('incharge.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('income.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row"> 
        <!-- table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('income.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-lg-4 col-md-6">
                                <label for="account_ids">{{ __('levels.to_account')}}</label>
                                <select id="account_ids" name="account_id" class="form-control">
                                    <option selected disabled>{{ __('menus.select') }} {{ __('placeholder.account') }}</option>
                                    @foreach($accounts as $account)
                                        @if ($account->type == App\Enums\AccountType::ADMIN)

                                            <option {{ (old('account_id',$request->account_id) == $account->id) ? 'selected' : '' }} value="{{ $account->id }}">
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
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-6">
                                <label for="date">{{ __('levels.date')}}</label>
                                <input type="text" id="date" data-toggle="datepicker" name="date" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',$request->date)}}">
                                @error('date')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-6 pt-1">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 d-flex justify-content pl-0">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('income.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('income.title') }}</p>
                    </div>
                    @if(hasPermission('income_create') == true )
                        <div class="col-6">
                            <a href="{{route('income.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.details') }}</th>
                                    <th>{{ __('levels.to_account')}}</th>
                                    <th>{{ __('levels.date')}}</th>
                                    <th>{{ __('levels.receipt')}}</th>
                                    <th>{{ __('levels.amount')}}</th>
                                    @if(hasPermission('income_update') == true || hasPermission('income_delete') == true )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach ($incomes as $income)
                                    <tr>
                                        <td>{{++$i}}</td>
                                        <td>
                                            @if(
                                                 $income->account_head_id == 1  ||
                                                 $income->account_head_id == 2  ||
                                                 $income->account_head_id == 3
                                                )
                                                <div class="row width300px">
                                                    <span class="col-12">
                                                        @if ($income->account_head_id == 1)
                                                            {{ __('parcel.merchant') }}<br>
                                                        @elseif($income->account_head_id == 2)
                                                        {{ __('parcel.deliveryman') }}<br>
                                                        @elseif($income->account_head_id == 3 && $income->user_id !==null)
                                                        {{ __('levels.user') }}<br>
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                            @if($income->hub_user_account)
                                                {{$income->hub_user_account->account_holder_name}}<br>
                                                {{$income->hub_user_account->account_no}}<br>
                                                {{$income->hub_user_account->branch_name}}
                                            @endif
                                            @if ($income->merchant_id != null)
                                                <div class="row width300px ">
                                                    <span class="col-5">{{ __('levels.name') }}</span>
                                                    <span class="col-7">: {{$income->merchant->business_name}}</span>
                                                </div>
                                                <div class="row  width300px ">
                                                    <span class="col-5">{{ __('levels.mobile') }}</span>
                                                    <span class="col-7">: {{$income->merchant->user->mobile}}</span>
                                                </div>
                                            @endif
                                            {{-- delivery man --}}
                                            @if ($income->delivery_man_id != null)
                                                <div class="row width300px ">
                                                    <span class="col-5">{{ __('levels.name') }}</span>
                                                    <span class="col-7">: {{$income->deliveryman->user->name}}</span>
                                                </div>
                                                <div class="row  width300px">
                                                    <span class="col-5">{{ __('levels.mobile') }}</span>
                                                    <span class="col-7">: {{$income->deliveryman->user->mobile}}</span>
                                                </div>
                                            @endif

                                            @if ($income->user_id != null)
                                                <div class="row width300px ">
                                                    <span class="col-5">{{ __('levels.user') }} {{ __('levels.name') }}</span>
                                                    <span class="col-7">: {{$income->user->name}}</span>
                                                </div>

                                                <div class="row width300px ">
                                                    <span class="col-5">{{ __('levels.mobile') }}</span>
                                                    <span class="col-7">: {{$income->user->mobile}}</span>
                                                </div>
                                            @endif
                                            @if($income->account_head_id == 3)
                                                <div class="row  width300px">
                                                    <span class="col-5">{{ __('levels.title') }}</span>
                                                    <span class="col-7">: {{ @$income->title }}</span>
                                                </div>
                                            @endif
                                            @if($income->parcel_id !==null)

                                                <div class="row width300px ">
                                                    <span class="col-5">{{ __('levels.parcel') }} {{ __('levels.track_id') }}</span>
                                                    <span class="col-7">: <a class="active" href="{{ route('parcel.details',$income->parcel_id) }}" target="_blank">#{{@$income->parcel->tracking_id}}</a></span>
                                                </div>
                                            @endif
                                            <div class="row width300px ">
                                                <span class="col-5">{{ __('levels.note') }}</span>
                                                <span class="col-7">: {{ @$income->note }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            {{$income->account->account_holder_name}}<br>
                                            {{$income->account->account_no}}<br>
                                            {{$income->account->branch_name}}
                                        </td>
                                        <td>{{dateFormat($income->date)}}</td>
                                        <td>
                                            <img src="{{$income->image}}" alt="user" class="rounded" width="45" height="65">
                                        </td>
                                        <td>{{settings()->currency}}{{$income->amount}}</td>
                                        @if(
                                            hasPermission('income_update') == true ||
                                            hasPermission('income_delete') == true
                                        )
                                            <td>
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if(  hasPermission('income_update') == true  )
                                                            <a href="{{route('income.edit',$income->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                        @endif
                                                        @if( hasPermission('income_delete') == true  )
                                                            <form id="delete" action="{{route('income.delete',$income->id)}}" method="POST" data-title="{{ __('delete.income') }}">
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
                    <span>{{ $incomes->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $incomes->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $incomes->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $incomes->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>
<!-- end wrapper  -->

@endsection
