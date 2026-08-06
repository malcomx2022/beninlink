@extends('backend.partials.master')
@section('title')
    {{ __('fraud.title') }}  {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-12 col-lg-8 offset-lg-2">
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
        <div class="col-12 col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header">
                    <p class="h3">{{ __('fraud.title') }}</p>
                    <div class="row">
                        <div class="col-12 d-flex">
                            <form action="{{route('merchant-panel.fraud.check')}}"  method="POST">
                                @csrf
                                <input type="text" name="phone" type="number" placeholder="{{ __('merchantPlaceholder.fraud_check') }}">
                                <button type="submit" class="btn btn-primary btn-sm ml-1"data-toggle="tooltip" data-placement="top" title="{{ __('levels.search') }}"><i class="fas fa-search"></i></button>
                            </form>
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <div style="margin-top: 1px;">
                                    <a href="{{route('merchant-panel.fraud.filter')}}" class="btn btn-primary btn-sm mx-2" data-toggle="tooltip" data-placement="top" title="{{ __('levels.my_list') }}"><i class="fas fa-clipboard-list"></i></a>
                                    <a href="{{route('merchant-panel.fraud.create')}}" class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @foreach($frauds as $fraud)
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                {{__('levels.name')}}: {{$fraud->name}}
                            </div>
                            @if ($fraud->created_by == Auth::user()->id)
                                <div class="col-6 text-right">
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        <a class="dropdown-item p-0 mr-3" href="{{route('merchant-panel.fraud.edit',$fraud->id)}}"><i class="fas fa-edit" aria-hidden="true"></i></a>
                                        <form id="delete" action="{{route('merchant-panel.fraud.delete',$fraud->id)}}" method="POST" data-title="{{ __('delete.fraud') }}">
                                            @method('DELETE')
                                            @csrf
                                            <input type="hidden" name="" value="{{ __('fraud.title') }}" id="deleteTitle">
                                            <button class="dropdown-item p-0" type="submit"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-6">
                                {{__('levels.phone')}}: <strong>{{$fraud->phone}}</strong>
                            </div>
                            <div class="col-6 text-right">
                                {{__('levels.date')}}: {{dateFormat($fraud->created_at)}}
                            </div>
                        </div>
                        <div class="border my-2"></div>
                        <div>{{$fraud->details}}</div>
                    </div>
                </div>
            @endforeach
            <div class="card">
                <div class="card-footer">
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
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
