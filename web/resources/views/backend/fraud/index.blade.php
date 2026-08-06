@extends('backend.partials.master')
@section('title')
    {{ __('fraud.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('fraud.title') }}</a></li>
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
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('fraud.title') }}</p>
                    </div>
                    @if(hasPermission('fraud_create') == true)
                        <div class="col-6">
                            <a href="{{route('fraud.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.phone') }}</th>
                                    <th>{{ __('levels.name') }}</th>
                                    <th>{{ __('levels.track_id') }}</th>
                                    @if(hasPermission('fraud_update') == true || hasPermission('fraud_delete') == true )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($frauds as $fraud)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$fraud->phone}}</td>
                                    <td>{{$fraud->name}}</td>
                                    <td>{{$fraud->tracking_id}}</td>
                                    @if(hasPermission('fraud_update') == true || hasPermission('fraud_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('fraud_update') == true  )
                                                    <a href="{{route('fraud.edit',$fraud->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('fraud_delete') == true )
                                                    <form id="delete" value="Test" action="{{route('fraud.delete',$fraud->id)}}" method="POST" data-title="{{ __('delete.fraud') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="{{ __('fraud.title') }}" id="deleteTitle">
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
                        <span>{{ $frauds->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $frauds->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $frauds->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $frauds->total() }}</span>
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
