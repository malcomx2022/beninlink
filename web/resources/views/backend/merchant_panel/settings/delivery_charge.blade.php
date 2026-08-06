@extends('backend.partials.master')
@section('title')
    {{ __('delivery_charge.title') }} {{ __('levels.lsit') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('menus.settings') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('delivery_charge.title') }}</a></li>
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
                        <p class="h3">{{ __('delivery_charge.title') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.category') }}</th>
                                    <th>{{ __('levels.weight') }}</th>
                                    <th>{{ __('levels.same_day') }}</th>
                                    <th>{{ __('levels.next_day') }}</th>
                                    <th>{{ __('levels.sub_city') }}</th>
                                    <th>{{ __('levels.outside_city') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($delivery_charges as $delivery_charge)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$delivery_charge->deliveryCharge->category->title}}</td>
                                    <td>{{$delivery_charge->deliveryCharge->weight ?? 0}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->same_day}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->next_day}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->sub_city}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->outside_city}}</td>
                                    <td>{!! $delivery_charge->my_status !!}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $delivery_charges->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $delivery_charges->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $delivery_charges->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $delivery_charges->total() }}</span>
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

