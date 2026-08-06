@extends('backend.partials.master')
@section('title')
    {{ __('menus.profile') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('dashboard.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('menus.profile') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- basic form -->
        <div class="col-12 text-right">
            <a href="{{route('merchant-profile.edit',$merchat->user->id)}}" class="btn btn-sm btn-primary mb-2"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
        </div>
        <!-- end basic form -->
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="row">
               <div class="col-xl-12">
                   <div class="card mb-1 pb-5">
                       <div class="card-body">
                           <div class="text-center">
                               <img src="{{@$merchat->user->image}}" alt="user" class="img-responsive rounded-circle" width="100" >
                               <p class="mt-2 mb-0"><strong>{{@$merchat->user->name}}</strong></p>
                               <p class=" mb-0">{{@$merchat->user->mobile}}</p>
                               <p>{{@$merchat->address}}</p>
                           </div>
                       </div>
                   </div>
               </div>
               <div class="col-xl-12">
                   <div class="list-group mt-3 mb-3">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.hub') }} : </span>
                                <span>{{@$merchat->user->hub->name}}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.vat') }} : </span>
                                <span>{{@$merchat->vat}}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.payment_period') }} : </span>
                                <span>{{@$merchat->payment_period}}</span>
                            </div>
                        </div>

                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.status') }} : </span>
                                 @if($merchat->status == \App\Enums\Status::ACTIVE)
                                     <span class="badge badge-pill badge-success">{{ __('levels.active') }}</span>
                                 @else
                                     <span class="badge badge-pill badge-danger">{{ __('levels.inactive') }}</span>
                                 @endif
                            </div>
                        </div>
                   </div>
               </div>
            </div>
       </div>
       <div class="col-xl-8 col-lg-8 col-md-8">
           <div class="row">
               <div class="col-xl-12">
                   <div class="list-group">
                       <div class="list-group-item ">
                           <div class="d-flex">
                               <span class="w-25">{{ __('levels.name') }} : </span>
                               <span>{{@$merchat->user->name}}</span>
                           </div>
                       </div>
                       <div class="list-group-item ">
                           <div class="d-flex">
                               <span class="w-25">{{ __('levels.email') }} : </span>
                               <span>{{@$merchat->user->email}}</span>
                           </div>
                       </div>
                       <div class="list-group-item ">
                           <div class="d-flex">
                               <span class="w-25">{{ __('levels.phone') }} : </span>
                               <span>{{@$merchat->user->mobile}}</span>
                           </div>
                       </div>
                       <div class="list-group-item ">
                           <div class="d-flex">
                               <span class="w-25">{{ __('levels.business_name') }} : </span>
                               <span>{{@$merchat->business_name}}</span>
                           </div>
                       </div>
                       <div class="list-group-item ">
                           <div class="d-flex">
                               <span class="w-25">{{ __('levels.address') }} : </span>
                               <span>{{ @$merchat->address }}</span>
                           </div>
                       </div>
                   </div>
               </div>
               <div class="col-xl-6 col-lg-12 col-md-12">
                   <div class="list-group mt-3">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('merchantshops.title') }}  : </span>
                                <span>{{@$merchat->active_shop->name}}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.opening_balance') }} : </span>
                                <span>{{@$merchat->opening_balance}}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.nid') }} : </span>
                                <span><img src="{{$merchat->nid}}" alt="user" class=" img-responsive "   width="100" style="object-fit:contain" ></span>
                            </div>
                        </div>
                   </div>
               </div>
               <div class="col-xl-6 col-lg-12 col-md-12">
                   <div class="list-group mt-3">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('reports.total') }} {{ __('merchantshops.title') }}  : </span>
                                <span>{{@$merchat->merchantShops->count()}}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="" style="width:30%">{{ __('levels.merchant_unique_id') }} : </span>
                                <span>{{@$merchat->merchant_unique_id}}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.trade_license') }} : </span>
                                <span> <img src="{{$merchat->trade}}" alt="user" class=" img-responsive"   width="100"  style="object-fit:contain"></span>
                            </div>
                        </div>
                   </div>
               </div>
            </div>
       </div>
    </div>
</div>
<!-- end wrapper  -->
@endsection()
