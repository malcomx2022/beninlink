<!-- wrapper  -->

@extends('backend.partials.master')
@section('title')
    {{ __('merchant.dashboard') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content ">
        <!-- pageheader  -->
   
        <!-- end pageheader  -->
        <div class="ecommerce-widget merchant-dashboard-filter">
            <div class="row p-0 mb-3">
                <div class="col-12 col-md-6">
                    <p class="h3 d-inline">{{ __('merchant.dashboard') }}</p>
                </div>
                <div class="col-12 col-md-6 text-right  pt-2 pt-sm-0">
                    <form action="{{ route('dashboard.index', ['test' => 'custom']) }}" method="get">
                        <button type="submit" class="btn btn-sm btn-primary float-right group-btn ml-0"
                            style="margin-left: 0px">{{ __('levels.filter') }}</button>
                        <input type="hidden" name="days" value="custom" />
                        <input type="text" name="filter_date" placeholder="YYYY-MM-DD" autocomplete="off"
                            class="form-control dashboard-filter-input date_range_picker float-right group-input"
                            value="{{ $request->filter_date }}" style="width: 30%;" required />
                    </form>
                </div>
            </div>
 
            {{-- parcel info --}}
            <div class="row merchant-panel header-summery">
                <div class="col-sm-6  col-lg-6 col-xl-3">
                    <a href="{{ route('company.index') }}" class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex ">
                                    <label class="icon p-10px"><i class="fa-solid fa-building text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class="m-0 text-primary">{{ __('dashboard.total_company') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $total_company }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-6  col-xl-3">
                    <a href="{{ route('plan.index') }}"
                        class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex">
                                    <label class="icon  p-10px"><i class="fa-solid fa-grip-vertical text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class=" m-0 text-primary">{{ __('dashboard.total_plans') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $total_plans }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class=" col-sm-6 col-lg-6 col-xl-3">
                    <a href="javascript:void(0)"  class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex ">
                                    <label class="icon  p-10px"><i class="fa fa-hands-helping text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class=" m-0 text-primary">{{ __('dashboard.total_subscription') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ $total_subscription }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class=" col-sm-6 col-lg-6  col-xl-3">
                    <a href="javascript:void(0)" class="d-block">
                        <div class="card border-3 border-top border-top-primary">
                            <div class="card-body">
                                <div class="d-flex ">
                                    <label class="icon  p-10px"><i class="fa fa fa-donate text-primary"></i></label>
                                    <div class="pl-2 w-100">
                                        <h5 class=" m-0 text-primary">{{ __('dashboard.total_subscription_price') }}</h5>
                                        <h1 class="mb-1 m-0 text-primary">{{ settings()->currency }} {{ $total_subscription_amount }}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
 
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="row pl-4 pr-4 pt-4">
                            <div class="col-6">
                                <p class="h3">{{ __('levels.recent_company') }}</p>
                            </div> 
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table   " style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{{ __('levels.id') }}</th>
                                            <th>{{ __('levels.logo') }}</th>  
                                            <th>{{ __('levels.name') }}</th>  
                                            <th>{{ __('levels.user_details') }}</th>  
                                            <th>{{ __('permissions.permissions') }} {{ __('levels.modules') }}</th> 
                                            <th>{{ __('levels.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $i = 0;
                                        @endphp
                                        @foreach ($recent_companies as $company)
                                            <tr> 
                                                <td>{{ ++$i }}</td>
                                                <td> <img src="{{$company->company->LogoImage}}" alt="user" class="rounded" width="40" height="40"></td>
                                                <td>{{@$company->company->name}}</td>
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
                                                        <label class="badge badge-primary">{{ count($company->company->plan->modules) }}</label>
                                                    @endif
                                                </td> 
                                                <td>{!! $company->my_status !!}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div> 
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="row pl-4 pr-4 pt-4">
                            <div class="col-6">
                                <p class="h3">{{ __('levels.recent_subscriptions') }}</p>
                            </div> 
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table   " style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{{ __('levels.id') }}</th>
                                            <th>{{ __('levels.company') }}</th> 
                                            <th>{{ __('levels.plan') }}</th> 
                                            <th>{{ __('levels.parcel_count') }}</th>
                                            <th>{{ __('levels.max_deliveryman') }}</th>
                                            <th>{{ __('levels.price') }}</th>
                                            <th>{{ __('levels.start_date') }}</th>
                                            <th>{{ __('levels.expired_date') }}</th> 
         
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $i = 0;
                                        @endphp
                                        @foreach ($subscriptions as $subscription)
                                            <tr> 
                                                <td>{{ ++$i }}</td>
                                                <td>{{ $subscription->company->name }}</td>
                                                <td>{{ $subscription->plan->name }}</td>
                                                <td>{{ $subscription->parcel_count }}</td>
                                                <td>{{ $subscription->deliveryman_count }}</td>
                                                <td>{{ settings()->currency }} {{ $subscription->price }}</td>
                                                <td>{{ $subscription->start_date }}</td>
                                                <td>{{ $subscription->expired_date }}</td>  
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- end wrapper  -->
@endsection()

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
@push('scripts')
    <script type="text/javascript" src="{{ static_asset('backend/js/charts/apexcharts.js') }}"></script>
    {{-- @include('backend.merchant_panel.dashboard-chart') --}}
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}">
    </script>
@endpush
