@extends('backend.partials.master')
@section('title')
    {{ __('merchantshops.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.shops.index') }}" class="breadcrumb-link">{{ __('merchantshops.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('merchantshops.title') }} {{ __('levels.list') }}</p>
                    </div>

                    <div class="col-6">
                        <a href="{{route('merchant-panel.shops.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                            <tr>
                                <th>{{ __('levels.id') }}</th>
                                <th>{{ __('merchantshops.name') }}</th>
                                <th>{{ __('merchantshops.contact') }}</th>
                                <th>{{ __('merchantshops.address') }}</th>
                                <th>{{ __('levels.status') }}</th>
                                <th>{{ __('levels.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $i=1; @endphp
                            @foreach($merchant_shops as $shop)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="w200">
                                            {{$shop->name}} <strong class="active">( {{ $shop->id }} )</strong>
                                        </div>
                                    </td>
                                    <td>{{$shop->contact_no}}</td>
                                    <td>
                                        <div class="w250">
                                            {{$shop->address}}
                                        </div>
                                    </td>
                                    <td>
                                        @if($shop->status == \App\Enums\Status::ACTIVE)
                                            <span class="badge badge-pill badge-success">{{ __('merchantshops.active') }}</span>
                                        @else
                                            <span class="badge badge-pill badge-danger">{{ __('merchantshops.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                <a href="{{route('merchant-panel.shops.edit',$shop->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>

                                                <form id="delete" value="Test" action="{{route('merchant-panel.shops.delete',$shop->id)}}" method="POST" data-title="{{ __('delete.shop') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="" value="Merchant Shop" id="deleteTitle">
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>

                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $merchant_shops->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $merchant_shops->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $merchant_shops->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $merchant_shops->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end wrapper  -->
@endsection()

