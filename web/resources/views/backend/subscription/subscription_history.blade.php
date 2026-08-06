@extends('backend.partials.master')
@section('title')
{{ __('parcel.subscription_history') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('parcel.subscription_history') }}</a></li>
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
            @if (Auth::user()->user_type == App\Enums\UserType::SUPER_ADMIN)                
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('subscription.history')}}"  method="GET" id="filter-form"> 
                            <div class="row">
  
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="date">{{ __('parcel.company') }}</label>
                                     <select class="form-control select2" name="company_id">
                                         <option value="4">Select company</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected($company->id == $request->company_id)>{{ $company->name }}</option>
                                        @endforeach
                                     </select>
                                </div>
    
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2 pt-1 pl-0">
                                    <div class="col-12 pt-3 d-flex justify-content text-right">
                                        <button type="submit" class="btn btn-sm btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('subscription.history') }}" class="btn btn-sm btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
    
            @endif
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('parcel.subscription_history') }} </p>
                    </div>
                </div>
                <div class="card-body"> 
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
 
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('parcel.company') }}</th>
                                    <th>{{ __('levels.user_details') }}</th>
                                    <th>{{ __('levels.plan') }}</th>
                                    <th>{{ __('levels.price') }}</th>
                                    <th>{{ __('parcel.parcel_count') }}</th>
                                    <th>{{ __('levels.deliveryman_count') }}</th>
                                    <th>{{ __('parcel.days_count') }}</th>
                                    <th>{{ __('parcel.start_date') }}</th> 
                                    <th>{{ __('parcel.expired_date') }}</th> 

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                @endphp
                                @foreach ($subscriptions as $subscription)
                                    <tr>
                                        <td>{{ ++$i }}</td>
                                        <td>{{ @$subscription->company->name }}</td> 
                                        <td class="merchantpayment"> 
                                        
                                            <p style="margin-bottom:0px!important">{{ @$subscription->user->name }}</p>
                                            <p style="margin-bottom:0px!important">{{ @$subscription->user->mobile }}</p>
                                            <p style="margin-bottom:0px!important">{{ @$subscription->user->address }}</p>
                                        
                                        </td>
                                          
                                        <td>{{ @$subscription->plan->name }}</td>
                                        <td>{{ @$subscription->price }}</td> 
                                        <td>{{ @$subscription->parcel_count }}</td>
                                        <td>{{ @$subscription->deliveryman_count }}</td>
                                        <td>{{ @$subscription->days_count }}</td>
                                        <td>{{ @$subscription->start_date }}</td> 
                                        <td>{{ @$subscription->expired_date }}</td> 
                    
                                    </tr>
                                @endforeach
                                <tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 d-flex flex-row-reverse align-items-center">
                        <span>{{ $subscriptions->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $subscriptions->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $subscriptions->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $subscriptions->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>

                    
                </div>
            </div>
        </div>
        <!-- end table  -->
    </div>
</div>   
<!-- end wrapper  -->
@endsection
  
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> 
    <style>
        #selectAssignType .select2-container .select2-selection--single {
            height: 32px !important;
        }
    </style>
@endpush
@push('scripts') 
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush