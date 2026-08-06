@extends('backend.partials.master')
@section('title')
    {{ __('menus.logs') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('menus.logs') }}</a></li>
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
                <div class="card-body">
                    <p class="h3">{{ __('menus.logs') }}</p>
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('logs.log_name') }}</th>
                                    <th>{{ __('logs.event') }}</th>
                                    <th>{{ __('logs.subject_type') }}</th>
                                    <th>{{ __('logs.description') }}</th>
                                    <th>{{ __('logs.view') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($logs as $log)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$log->log_name}}</td>
                                    <td>{{__('levels.'.$log->event)}}</td>
                                    <td>{{$log->subject_type}}</td>
                                    <td>{{__('levels.'.$log->description)}}</td>
                                    <td>
                                        <a href="#" class="btn btn-primary btn-sm modalBtn" data-toggle="modal" data-target="#dynamic-modal" data-modalsize="modal-lg" data-title="{{ __('logs.log_details') }}" data-url="{{ route('log-activity-view',$log->id) }}" > <i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <span>{{ $logs->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $logs->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $logs->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $logs->total() }}</span>
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

