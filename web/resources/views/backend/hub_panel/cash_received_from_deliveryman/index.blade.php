@extends('backend.partials.master')
@section('title')
    {{ __('permissions.cash_received_from_delivery_man') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('permissions.cash_received_from_delivery_man') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('permissions.cash_received_from_delivery_man') }}</p>
                    </div>
                    @if(hasPermission('cash_received_from_delivery_man_create') == true )
                        <div class="col-6">
                            <a href="{{route('cash.received.deliveryman.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
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
                                    <th>{{ __('levels.hub')}}</th>
                                    <th>{{ __('levels.to_account')}}</th>
                                    <th>{{ __('levels.date')}}</th>
                                    <th>{{ __('levels.receipt')}}</th>
                                    <th>{{ __('levels.amount')}}</th>
                                    @if(
                                        hasPermission('cash_received_from_delivery_man_update') == true ||
                                        hasPermission('cash_received_from_delivery_man_delete') == true
                                    )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach ($hubStatements as $statement)
                                    <tr>
                                        <td>{{++$i}}</td>
                                        <td>
                                            <div class="row ">
                                                <span class="col-12">
                                                    {{ __('parcel.deliveryman') }}
                                                </span>
                                            </div>
                                            @if ($statement->delivery_man_id != null)
                                                <div class="row  ">
                                                    <span class="col-5">{{ __('levels.name') }}</span>
                                                    <span class="col-7">: {{$statement->deliveryman->user->name}}</span>
                                                </div>
                                                <div class="row  ">
                                                    <span class="col-5">{{ __('levels.mobile') }}</span>
                                                    <span class="col-7">: {{$statement->deliveryman->user->mobile}}</span>
                                                </div>
                                            @endif
                                            <div class="row  ">
                                                <span class="col-5">{{ __('levels.note') }}</span>
                                                <span class="col-7">: {{ @$statement->note }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $statement->user->hub->name }}<br/>
                                            {{ $statement->user->hub->phone }}<br/>
                                            {{ $statement->user->hub->address }}<br/>
                                        </td>
                                        <td>
                                            {{$statement->account->account_holder_name}}<br>
                                            {{$statement->account->account_no}}<br>
                                            {{$statement->account->branch_name}}
                                        </td>
                                        <td>{{dateFormat($statement->date)}}</td>

                                        <td>
                                            <a href="{{ static_asset($statement->upload->original) }}" download="" >Download</a>
                                        </td>
                                        <td>{{settings()->currency}}{{$statement->amount}}</td>
                                        @if(
                                            hasPermission('cash_received_from_delivery_man_update') == true ||
                                            hasPermission('cash_received_from_delivery_man_delete') == true
                                        )
                                            <td>
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if(hasPermission('cash_received_from_delivery_man_update') == true  )
                                                            <a href="{{route('cash.received.deliveryman.edit',$statement->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                        @endif
                                                        @if(hasPermission('cash_received_from_delivery_man_delete') == true  )
                                                            <form id="delete" action="{{route('cash.received.deliveryman.delete',$statement->id)}}" method="POST" data-title="{{ __('delete.cash_received') }}">
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
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>
<!-- end wrapper  --> 

@endsection