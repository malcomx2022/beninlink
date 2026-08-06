@extends('frontend.layouts.master')
@section('title')
    {{ @$service->title }} | {{ settings()->name}}
@endsection
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 ">  
        <div class="row">
            <div class="col-xl-6">
                <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ @$service->title }} </h3> 
                {{-- <div class="mb-3">
                    <img src="{{ $service->image }}" class="card-img-top" alt="{{ $service->title }}"> 
                </div>  --}}
                <div class="page-content">
                    <div class="service-item service-details p-3 h-100"> 
                        <div class="service-item-box d-block">
                            <div class="my-4 text-center" >
                                <div class="service-box">
                                    <div class="icon-box d-flex"> 
                                        <img src="{{ $service->image }}" width="100%"/>
                                    </div>
                                </div>
                            </div> 
                            <p class="my-5 ">{!! $service->description !!}</p>  
                        </div>
                    </div> 
                </div>
            </div>
            <div class="col-xl-6    "> 
                <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ __('levels.latest_services') }}</h3>
                <div class="row">
                    @foreach ($latest_services as $latest_service) 
                        <div class="col-lg-6 col-sm-6  mb-3 text-center ">
                            <div class="service-item p-3 h-100"> 
                                <div class="service-item-box d-block">
                                    <div class="my-4" >
                                        <div class="service-box">
                                            <div class="icon-box d-flex"> 
                                                <img src="{{ $latest_service->image }}" width="100%"/>
                                            </div>
                                        </div>
                                    </div>
                                    <h5 class="mb-3 font-weight-bold">{{ $latest_service->title }}</h5> 
                                    <p class="mb-2">{!! \Str::limit(strip_tags($latest_service->description), 60, ' ...<p><a href="'.route('service.details',$latest_service->id).'" class="text-primary"><i class="fa fa-arrow-right"></i></a></p>') !!}</p>  
                                </div>
                            </div>
                        </div> 
                    @endforeach  
                </div>
            </div>
        </div>
    </div>
</section>  
@endsection