@extends('backend.partials.master')
@section('title')
    {{ __('hub.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{url('/')}}" class="breadcrumb-link">{{__('levels.dashboard')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{__('hub.title')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('levels.list')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('hubs.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-lg-4 col-md-4" >
                                <label for="name">{{ __('levels.name') }}</label>
                                <input type="text" id="name" name="name" placeholder="{{ __('placeholder.Hub_name') }}"  class="form-control" value="{{old('name', $request->name)}}">
                                @error('name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-4">
                                <label for="phone">{{ __('levels.phone')}}</label> <span class="text-danger"></span>
                                <input type="text" id="phone" name="phone" placeholder="{{ __('placeholder.phone') }}"  class="form-control" value="{{old('phone', $request->phone)}}">
                                @error('phone')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-lg-4 col-md-4  pt-4">
                                <div class="d-flex">
                                    <a href="{{ route('hubs.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('hub.title') }}</p>
                    </div>
                    @if(hasPermission('hub_create')             == true )
                    <div class="col-6">
                        <a href="{{route('hubs.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{__('levels.add')}}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   ">
                            <thead>
                                <tr>
                                    <th>{{__('levels.id')}}</th>
                                    <th>{{__('levels.name')}}</th>
                                    <th>{{__('levels.phone')}}</th>
                                    <th>{{__('levels.address')}}</th>
                                    <th>{{__('levels.status')}}</th>
                                    @if(
                                        hasPermission('hub_update')             == true ||
                                        hasPermission('hub_delete')           == true ||
                                        hasPermission('hub_incharge_read')    == true
                                        )
                                    <th>{{__('levels.actions')}}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($hubs as $hub)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$hub->name}}</td>
                                    <td>{{$hub->phone}}</td>
                                    <td>{{$hub->address}}</td>
                                    <td>
                                        @if($hub->status == \App\Enums\Status::ACTIVE)
                                            <span class="badge badge-pill badge-success">{{ $hub->my_status }}</span>
                                        @else
                                            <span class="badge badge-pill badge-danger">{{ $hub->my_status }}</span>
                                        @endif
                                    </td>
                                @if(
                                    hasPermission('hub_update')           == true ||
                                    hasPermission('hub_delete')           == true ||
                                    hasPermission('hub_incharge_read')    == true
                                    )
                                    <td>
                                        <div class="row">

                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>

                                            <div class="dropdown-menu">
                                                @if(hasPermission('hub_view')  == true )
                                                    <a href="{{route('hub.view',$hub->id)}}" class="dropdown-item"><i class="fas fa-eye" aria-hidden="true"></i> {{__('levels.view')}}</a>
                                                @endif

                                                @if(hasPermission('hub_update')  == true )
                                                    <a href="{{route('hubs.edit',$hub->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{__('levels.edit')}}</a>
                                                @endif
                                                @if(hasPermission('hub_incharge_read')    == true )
                                                    <a href="{{route('hub-incharge.index',$hub->id)}}" class="dropdown-item"><i class="fas fa-plus-circle" aria-hidden="true"></i> {{ __('hubs.hub_incharge') }}</a>
                                                @endif
                                                @if(hasPermission('hub_delete')  == true  )
                                                    <form action="{{route('hub.delete',$hub->id)}}" method="POST" id="delete" data-title="{{ __('delete.hub') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <button type="submit"  class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
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
                        <span>{{ $hubs->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $hubs->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $hubs->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $hubs->total() }}</span>
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
