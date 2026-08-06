@extends('backend.partials.master')
@section('title')
    {{ __('asset_category.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('asset_category.title_name') }}</a></li>
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
                    <div class="col-10">
                        <p class="h3">{{ __('asset_category.title_name') }}</p>
                    </div>
                    @if(hasPermission('asset_category_create'))
                    <div class="col-2">
                        <a href="{{route('asset-category.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
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
                                    <th>{{ __('levels.position') }}</th>
                                    @if(hasPermission('asset_category_update') || hasPermission('asset_category_delete') )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($assetcategorys as $assetcategory)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$assetcategory->title}}</td>
                                    <td>{{$assetcategory->position}}</td>
                                    @if(hasPermission('asset_category_update') == true || hasPermission('asset_category_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('asset_category_update'))
                                                    <a href="{{route('asset-category.edit',$assetcategory->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('asset_category_delete') == true)
                                                    <form id="delete" value="Test" action="{{route('asset-category.delete',$assetcategory->id)}}" method="POST" data-title="{{ __('Do you want to delete Asset Category ?') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="asset Category" id="deleteTitle">
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
                    <span>{{ $assetcategorys->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $assetcategorys->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $assetcategorys->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $assetcategorys->total() }}</span>
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



