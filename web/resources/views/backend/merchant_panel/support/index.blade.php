@extends('backend.partials.master')
@section('title')
    {{ __('support.supprot') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('merchant-panel.support.index')}}" class="breadcrumb-link">{{ __('support.supprot_list') }}</a></li>
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
                    <div class="col-8">
                        <p class="h3">{{ __('support.supprot') }}</p>
                    </div>
                    <div class="col-4">
                        <a href="{{route('merchant-panel.support.add')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('support.supprot_add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('support.sl') }}</th>
                                    <th>{{ __('support.user_info') }}</th>
                                    <th>{{ __('support.subject') }}</th>
                                    <th>{{ __('support.date') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('support.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($supports as $support)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="w250">
                                            {{__('support.name')}}: <span class="active">{{ $support->user->name}}</span>
                                            <br>
                                            {{__('support.email')}}: {{ $support->user->email}}
                                            <br>
                                            {{ __('support.department_id') }}: {{$support->department->title}}
                                            <br>
                                            {{ __('support.service') }}: {{$support->service }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="w200">
                                            <a class="text-primary" href="{{route('merchant-panel.support.view',$support->id)}}" data-toggle="tooltip" data-placement="top" title="{{ __('levels.view') }}">{{$support->subject }}</a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="w150">
                                            {{dateFormat($support->date) }}
                                        </div>
                                    </td>
                                    <td> {!! $support->my_status !!} </td> 
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                <a href="{{route('merchant-panel.support.view',$support->id)}}" class="dropdown-item"><i class="fas fa-eye" aria-hidden="true"></i> {{ __('levels.view') }}</a>
                                                <a href="{{route('merchant-panel.support.edit',$support->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                <form id="delete" value="Test" action="{{route('merchant-panel.support.delete',$support->id)}}" method="POST" data-title="{{ __('delete.support') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="" value="{{ __('support.title') }}" id="deleteTitle">
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
                    <span>{{ $supports->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $supports->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $supports->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $supports->total() }}</span>
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
