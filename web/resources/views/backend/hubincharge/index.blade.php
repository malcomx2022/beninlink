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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('incharge.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hubs.index') }}" class="breadcrumb-link">{{ __('hubs.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hub-incharge.index',$hub->id) }}" class="breadcrumb-link">{{ __('incharge.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('incharge.list') }}</a></li>
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
                        <p class="h3"> {{ $hub->name }} {{  __('incharge.title') }}</p>
                    </div>
                    @if( hasPermission('hub_incharge_create')    == true )
                    <div class="col-6">
                        <a href="{{ route('hub-incharge.create',$hub->id) }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.user') }}</th>
                                    <th>{{ __('levels.phone') }}</th>
                                    <th>{{ __('levels.assigned_date') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(
                                        hasPermission('hub_incharge_update')    == true ||
                                        hasPermission('hub_incharge_delete')    == true
                                        )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @if(!blank($hubInCharges))
                                @php $i=1; @endphp
                                @foreach($hubInCharges as $incharge)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="row">
                                            <div class="pr-3">
                                                <img src="{{$incharge->user->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div>
                                                <strong>{{$incharge->user->name}}</strong>
                                                <p>{{$incharge->user->email}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{$incharge->user->mobile}}</td>
                                    <td>{{date('d M Y', strtotime($incharge->updated_at))}}</td>
                                    <td>{!! $incharge->my_status !!}</td>
                                    @if(
                                        hasPermission('hub_incharge_update')    == true ||
                                        hasPermission('hub_incharge_delete')    == true
                                        )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">{{ __('Toggle Dropdown') }}</span></button>
                                            <div class="dropdown-menu">
                                            @if( hasPermission('hub_incharge_update')    == true)
                                                <a href="{{route('hub-incharge.edit',[$hub->id,$incharge])}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                            @endif
                                            @if( hasPermission('hub_incharge_delete')    == true )
                                                <form id="delete" value="Test" action="{{route('hub-incharge.destroy',[$hub->id,$incharge])}}" method="POST" data-title="{{ __('delete.hub_incharge') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="" value="Hub incharge" id="deleteTitle">
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
                                            @endif
                                            @if( hasPermission('hub_incharge_assigned')    == true )
                                                <a href="{{route('hub-incharge.assigned',[$hub->id,$incharge])}}" class="dropdown-item"><i class="fas fa-plus-circle" aria-hidden="true"></i> {{ __('incharge.assigned') }}</a>
                                            @endif
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                                @endif
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
