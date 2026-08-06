@extends('backend.partials.master')
@section('title')
    {{ __('merchant.title') }}  {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
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
                    <div class="col-10">
                        <div class="d-flex parcelsearchFlex">
                            <p class="h3">{{ __('merchant.title') }}</p>
                            <input id="Psearch" class="form-control parcelSearch d-lg-block" type="text" placeholder="Search..">
                        </div>
                    </div>
                    @if( hasPermission('merchant_create') == true )
                    <div class="col-2">
                        <a href="{{route('merchant.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                    <div class="col-12 d-lg-none mt-2">
                        <input id="Psearch" class="form-control " type="text" placeholder="Search..">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table" class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.unique_id') }}</th>  
                                    <th>{{ __('levels.details') }}</th>
                                    <th>{{ __('levels.hub') }}</th>
                                    <th>{{ __('levels.business_name') }}</th> 
                                    <th>{{ __('levels.phone') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('levels.wallet_activation') }}</th>
                                    <th>{{ __('levels.current_balance') }}</th>
                                    @if(
                                        hasPermission('merchant_view') == true ||
                                        hasPermission('merchant_update') == true ||
                                        hasPermission('merchant_delete') == true
                                     )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($merchants as $merchant)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{@$merchant->user->unique_id}}</td>
                                    <td>
                                        <div class="row">
                                            <div class="pr-3">
                                                <img src="{{$merchant->user->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div>
                                                <strong>{{$merchant->user->name}}</strong>
                                                <p>{{$merchant->user->email}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{@$merchant->user->hub->name}}</td>
                                    <td>{{@$merchant->business_name}}</td>
                                    {{-- <td>{{@$merchant->merchant_unique_id}}</td> --}}
                                    <td>{{@$merchant->user->mobile}}</td>
                                    <td>
                                        {!! $merchant->user->my_status !!}
                                    </td>
                                    <td>
                                        {!! $merchant->WalletStatus !!} 
                                    </td>
                                    <td>{{settings()->currency}}{{$merchant->current_balance}}</td>
                                    @if(
                                        hasPermission('merchant_view') == true ||
                                        hasPermission('merchant_update') == true ||
                                        hasPermission('merchant_delete') == true
                                        )
                                        <td>
                                            <div class="row">
                                                <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    <a href="{{route('merchant.invoice.generate',$merchant->id)}}" class="dropdown-item"><i class="fa fa-file" aria-hidden="true"></i> Invoice Generate</a>
                                                    @if( hasPermission('merchant_view') == true  )
                                                        <a href="{{route('merchant.view',$merchant->id)}}" class="dropdown-item"><i class="fa fa-eye" aria-hidden="true"></i> {{ __('levels.view') }}</a>
                                                    @endif
                                                 
                                                    @if( hasPermission('merchant_update') == true   )
                                                        <a href="{{route('merchant.edit',$merchant->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                    @endif
                                                    @if( hasPermission('merchant_delete') == true )
                                                        <form id="delete" value="Test" action="{{route('merchant.delete',$merchant->id)}}" method="POST" data-title="{{ __('delete.merchant') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" name="" value="Merchant" id="deleteTitle">
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
                    <div class="px-3 d-flex flex-row-reverse align-items-center">
                        <span>{{ $merchants->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $merchants->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $merchants->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $merchants->total() }}</span>
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
<!-- js  -->
@push('scripts')
<script src="{{ static_asset('backend/js/parcel/parcel-search.js') }}"></script>
@endpush


