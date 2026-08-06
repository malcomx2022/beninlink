@extends('backend.partials.master')
@section('title')
   {{ __('levels.faq') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.faq') }}</a></li>
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
                        <p class="h3">{{ __('levels.faq') }}</p>
                    </div>
                    @if(hasPermission('faq_create'))
                    <div class="col-2">
                        <a href="{{route('faq.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.question') }}</th>  
                                    <th>{{ __('levels.answer') }}</th> 
                                    <th>{{ __('levels.position') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(hasPermission('faq_update') || hasPermission('faq_delete') )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($faqs as $faq)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td width="25%">{{@$faq->question}}</td> 
                                    <td width="25%">{!! @$faq->answer !!}</td> 
                                    <td>{{@$faq->position}}</td>
                                    <td>{!!@$faq->my_status!!}</td>
                                    @if(hasPermission('faq_update') == true || hasPermission('faq_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('faq_update'))
                                                    <a href="{{route('faq.edit',$faq->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('faq_delete') == true)
                                                    <form id="delete" value="Test" action="{{route('faq.delete',$faq->id)}}" method="POST" data-title="{{ __('Do you want to delete faq ?') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="faq" id="deleteTitle">
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
                    <span>{{ @$faqs->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ @$faqs->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ @$faqs->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ @$faqs->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

