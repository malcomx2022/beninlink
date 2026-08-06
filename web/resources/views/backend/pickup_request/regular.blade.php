@extends('backend.partials.master')
@section('title')
{{ __('pickupRequest.regular') }} {{ __('pickupRequest.pickup_request') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('pickupRequest.pickup_request') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('pickupRequest.regular') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                        <p class="h3">  {{ __('pickupRequest.regular') }} {{ __('pickupRequest.pickup_request') }} </p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.user')}}</th>
                                    <th>{{ __('levels.address')}}</th>
                                    <th>{{ __('levels.estimetad_parcel')}}</th>
                                    <th>{{ __('levels.note')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach ($regulars as $regular)
                                    <tr>
                                        <td>{{++$i}}</td>
                                        <td>
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <img src="{{@$regular->merchant->user->image}}" alt="user" class="rounded" width="40" height="40">
                                                </div>
                                                <div class="col-lg-9">
                                                    <strong> {{@$regular->merchant->user->name}}</strong>
                                                    <p> {{@$regular->merchant->user->email}}<br/>{{@$regular->merchant->user->mobile}}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{@$regular->address}}</td>
                                        <td>{{@$regular->parcel_quantity}}</td>
                                        <td>{{\Str::limit(@$regular->note, 100, '...')}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $regulars->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $regulars->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $regulars->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $regulars->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end wrapper  -->
@endsection()
