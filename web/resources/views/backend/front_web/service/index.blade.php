@extends('backend.partials.master')
@section('title')
   {{ __('levels.service') }} {{ __('levels.list') }}
@endsection
@section('maincontent')

<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('levels.front_web')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.service') }}</a></li>
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
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-10">
                        <p class="h3">{{ __('levels.service') }}</p>
                    </div>
                    @if(hasPermission('service_create'))
                    <div class="col-2">
                        <a href="{{route('service.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.title') }}</th> 
                                    <th>{{ __('levels.image') }}</th> 
                                    <th>{{ __('levels.description') }}</th> 
                                    <th>{{ __('levels.position') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(hasPermission('service_update') || hasPermission('service_delete') )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($services as $service)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{@$service->title}}</td>
                                    <td><img src="{{ @$service->image }}"/> </td>
                                    <td width="25%">{!! @$service->description !!}</td> 
                                    <td>{{@$service->position}}</td>
                                    <td>{!!@$service->my_status!!}</td>
                                    @if(hasPermission('service_update') == true || hasPermission('service_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('service_update'))
                                                    <a href="{{route('service.edit',$service->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('service_delete') == true)
                                                    <form id="delete" value="Test" action="{{route('service.delete',$service->id)}}" method="POST" data-title="{{ __('Do you want to delete service ?') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="service" id="deleteTitle">
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
                    <span>{{ @$services->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ @$services->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ @$services->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ @$services->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

