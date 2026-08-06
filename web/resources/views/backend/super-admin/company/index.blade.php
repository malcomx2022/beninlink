@extends('backend.partials.master')
@section('title')
   {{ __('levels.company') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.company')}}</a></li> 
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
                    <div class="col-6">
                        <p class="h3">{{ __('levels.companies') }}</p>
                    </div>
                    @if(hasPermission('company_create') == true )
                    <div class="col-6">
                        <a href="{{route('company.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.logo') }}</th>  
                                    <th>{{ __('levels.name') }}</th>  
                                    <th>{{ __('levels.domain') }}</th>  
                                    <th>{{ __('levels.user_details') }}</th>  
                                    <th>{{ __('levels.plan') }}</th>  
                                    <th>{{ __('permissions.permissions') }} {{ __('levels.modules') }}</th> 
                                    <th>{{ __('levels.subscription') }} </th> 
                                    <th>{{ __('levels.status') }}</th>
                                    @if( 
                                        hasPermission('company_update')    == true    ||
                                        hasPermission('company_delete')    == true
                                    )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($companies as $company)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td> <img src="{{$company->company->LogoImage}}" alt="user" class="rounded" width="40" height="40"></td>
                                    <td>{{@$company->company->name}}</td> 
                                    <td> 
                                        @if(!empty($company->tenantDetails) && isset($company->tenantDetails->domains) )
                                             @foreach ($company->tenantDetails->domains as $domain)
                                                 <a href="{{ scheme_name($domain->domain) }}" target="_blank">{{ scheme_name() }}{{ $domain->domain }}</a><br/>
                                             @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <div  >
                                                <img src="{{$company->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div> 
                                                <strong>{{$company->name}}</strong>
                                                <p class="mb-0">{{$company->email}}</p>
                                                <p>{{$company->mobile}}</p>
                                            </div>
                                        </div>
                                    </td>  
                                   
                                    <td>
                                        @if(!empty($company->company->plan) )
                                             {{ @$company->company->plan->name }}
                                        @endif
                                    </td>
                                   
                                    <td>
                                        @if(!empty($company->company->plan) )
                                            <label class="badge badge-primary">{{ count($company->company->plan->modules) }}</label>
                                        @endif
                                    </td> 
                                    <td>
                                        @if (subscriptionCheck($company))
                                           {{ __('levels.remaining') }} {{ subscriptionCheck($company) }} {{ __('levels.days') }}
                                        @else
                                            <label class="badge badge-danger">{{ __('levels.expired') }}</label>
                                        @endif
                                        <br/>
                                        @if (hasPermission('company_subscribe')) 
                                            <button class="btn btn-primary btn-sm  modalBtn mt-2" data-bs-toggle="modal" data-bs-target="#dynamic-modal" data-title="{{ @$company->company->name }}" data-url="{{ route('company.subscription.switch',$company->id) }}">Subscribe Now</button>
                                        @endif
                                    </td>
                                    <td>{!! $company->my_status !!}</td>
                                    @if( 
                                        hasPermission('company_update') == true    ||
                                        hasPermission('company_delete') == true
                                    )
                                        <td>
                                            <div class="row">
                                                <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    
                                                    @if( hasPermission('company_update') == true )
                                                        <a href="{{route('company.edit',$company->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                    @endif
                                                    
                                                    @if( hasPermission('company_delete') == true )
                                                        @if($company->id != 1)
                                                            <form id="delete" value="Test" action="{{route('company.delete',$company->company_id)}}" method="POST" data-title="{{ __('delete.company') }}">
                                                                @method('DELETE')
                                                                @csrf
                                                                <input type="hidden" name="" value="Company" id="deleteTitle">
                                                                <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                            </form>
                                                        @endif
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
                    <span>{{ $companies->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $companies->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $companies->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $companies->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div> 
@endsection