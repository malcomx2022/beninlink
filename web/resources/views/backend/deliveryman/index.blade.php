@extends('backend.partials.master')
@section('title')
    {{ __('deliveryman.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('deliveryman.index') }}" class="breadcrumb-link">{{ __('deliveryman.title') }}</a></li>
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('deliveryman.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-xl-3 col-md-4" >
                                <label for="name">{{ __('levels.name') }}</label>
                                <input type="text" id="name" name="name" placeholder="{{ __('user.title') }} {{ __('levels.name') }}"  class="form-control" value="{{old('name', $request->name)}}">
                                @error('name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-md-4" >
                                <label for="email">{{ __('levels.email') }}</label>
                                <input type="text" id="email" name="email" placeholder="{{ __('user.title') }} {{ __('levels.email') }}"  class="form-control" value="{{old('email', $request->email)}}">
                                @error('email')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-md-4">
                                <label for="phone">{{ __('levels.phone')}}</label> <span class="text-danger"></span>
                                <input type="text" id="phone" name="phone" placeholder="{{ __('levels.phone') }}"  class="form-control" value="{{old('phone', $request->phone)}}">
                                @error('phone')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-3 pt-4">
                                <div class="d-flex">
                                    <a href="{{ route('deliveryman.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
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
                        <p class="h3">{{ __('deliveryman.title') }}</p>
                    </div>
                    @if( hasPermission('delivery_man_create') == true)
                    <div class="col-6">
                        <a href="{{route('deliveryman.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.unique_id') }}</th>
                                    <th>{{ __('levels.user') }}</th>
                                    <th>{{ __('levels.hub') }}</th>
                                    <th>{{ __('levels.delivery_charge') }}</th>
                                    <th>{{ __('levels.pickup_charge') }}</th>
                                    <th>{{ __('levels.return_charge') }}</th>
                                    <th>{{ __('levels.current_balance') }}</th>
                                    <th>{{ __('levels.opening_balance') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if( hasPermission('delivery_man_update') == true || hasPermission('delivery_man_delete') == true )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @if(!blank($deliveryMans))
                                @php $i=1; @endphp
                                @foreach($deliveryMans as $deliveryman)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td> {{ @$deliveryman->user->unique_id }} </td>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <img src="{{$deliveryman->user->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div class="col-md-9">
                                                <strong>{{$deliveryman->user->name}}</strong>
                                                <p>{{$deliveryman->user->email}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{$deliveryman->user->hub->name}}</td>
                                    <td>{{settings()->currency}}{{$deliveryman->delivery_charge}}</td>
                                    <td>{{settings()->currency}}{{$deliveryman->pickup_charge}}</td>
                                    <td>{{settings()->currency}}{{$deliveryman->return_charge}}</td>
                                    <td>{{settings()->currency}}{{$deliveryman->current_balance}}</td>
                                    <td>{{settings()->currency}}{{$deliveryman->opening_balance}}</td>

                                    <td>
                                        {!! $deliveryman->user->my_status !!}
                                    </td>
                                    @if( hasPermission('delivery_man_update') == true || hasPermission('delivery_man_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">{{ __('Toggle Dropdown') }}</span></button>
                                            <div class="dropdown-menu">
                                                @if( hasPermission('delivery_man_update') == true  )
                                                    <a href="{{route('deliveryman.edit',$deliveryman->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('delivery_man_delete') == true )
                                                    <form action="{{route('deliveryman.delete',$deliveryman->id)}}" method="POST" id="delete" data-title="{{ __('delete.delivery_man') }}">
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
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $deliveryMans->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $deliveryMans->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $deliveryMans->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $deliveryMans->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection

