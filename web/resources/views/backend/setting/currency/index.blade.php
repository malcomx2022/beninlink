@extends('backend.partials.master')
@section('title')
   {{ __('settings.currency') }} {{ __('levels.list') }}
@endsection
@section('maincontent')

<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('settings.currency') }}</a></li>
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
                        <p class="h3">{{ __('settings.currency') }}</p>
                    </div>
                    @if(hasPermission('currency_create'))
                    <div class="col-2">
                        <a href="{{route('currency.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.name') }}</th>
                                    <th>{{ __('settings.symbol') }}</th>
                                    <th>{{ __('settings.exchange_rate') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('levels.position') }}</th>
                                    @if(hasPermission('currency_update') || hasPermission('currency_delete') )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($currencies as $currency)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{@$currency->name}}</td>
                                    <td>{{@$currency->symbol}}</td>
                                    <td>{{number_format(@$currency->exchange_rate,2)}}</td>
                                    <td>{!!  @$currency->my_status!!}</td>
                                    <td>{{@$currency->position}}</td>
                                    @if(hasPermission('currency_update') == true || hasPermission('currency_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('currency_update'))
                                                    <a href="{{route('currency.edit',$currency->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('currency_delete') == true)
                                                    <form id="delete" value="Test" action="{{route('currency.delete',$currency->id)}}" method="POST" data-title="{{ __('Do you want to delete currency ?') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="Currency" id="deleteTitle">
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
                    <span>{{ @$currencies->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ @$currencies->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ @$currencies->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ @$currencies->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

