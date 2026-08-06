@extends('backend.partials.master')
@section('title')
   {{ __('push-notification.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('push-notification.index')}}" class="breadcrumb-link">{{ __('push-notification.title') }}</a></li>
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
                    <div class="col-10">
                        <p class="h3">{{ __('push-notification.title') }}</p>
                    </div>
                    <div class="col-2">
                        <a href="{{route('push-notification.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.image') }}</th>
                                    <th>{{ __('levels.title') }}</th>
                                    <th>{{ __('levels.description') }}</th>
                                    <th>{{ __('levels.user') }}</th>
                                    <th>{{ __('levels.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @if(!blank($pushNotifications))
                                @php $i=1; @endphp
                                @foreach($pushNotifications as $pushNotification)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <img src="{{ $pushNotification->image }}" alt="Image" width="45" height="65">
                                    </td>
                                    <td>{{ Str::limit(strip_tags($pushNotification->title), 50) }}</td>
                                    <td>{{ Str::limit(strip_tags($pushNotification->description), 50) }}</td>
                                    @if($pushNotification->user_id)
                                    <td>{{ $pushNotification->user->name }}</td>
                                        @else
                                        @if($pushNotification->type == 'all')
                                        <td>{{ __('All user') }}</td>
                                            @else
                                            <td>{{  __('userType.'.$pushNotification->type) }}</td>
                                            @endif
                                    @endif
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                    @if( hasPermission('push_notification_delete') == true)
                                                <form id="delete" value="Test" action="{{route('push-notification.delete',$pushNotification->id)}}" method="POST" data-title="{{ __('levels.delete') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
                                                 @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $pushNotifications->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $pushNotifications->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $pushNotifications->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $pushNotifications->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>
@endsection()
