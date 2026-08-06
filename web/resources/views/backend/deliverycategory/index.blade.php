@extends('backend.partials.master')
@section('title')
    {{ __('deliverycategory.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('deliverycategory.title') }}</a></li>
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
                    <div class="col-9">
                        <p class="h3">{{ __('deliverycategory.title') }}</p>
                    </div>
                @if (hasPermission('delivery_category_create'))
                    <div class="col-3">
                        <a href="{{route('delivery-category.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
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
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('levels.position') }}</th>
                                    @if (hasPermission('delivery_category_update') || hasPermission('delivery_category_delete') )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($deliverycategorys as $deliverycategory)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$deliverycategory->title}}</td>

                                    <td>
                                        {!! $deliverycategory->my_status !!}
                                    </td>
                                    <td>{{$deliverycategory->position}}</td>
                                    @if (hasPermission('delivery_category_update') || hasPermission('delivery_category_delete') )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if (hasPermission('delivery_category_update'))
                                                    <a href="{{route('delivery-category.edit',$deliverycategory->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if (hasPermission('delivery_category_delete') )
                                                    @if (!in_array($deliverycategory->id, $notDeleteArray))
                                                    <form id="delete" value="Test" action="{{route('delivery-category.delete',$deliverycategory->id)}}" method="POST" data-title="{{ __('delete.delivery_category') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="Delivery Category" id="deleteTitle">
                                                        <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                    </form>
                                                    @endif
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
                    <span>{{ $deliverycategorys->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $deliverycategorys->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $deliverycategorys->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $deliverycategorys->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()


