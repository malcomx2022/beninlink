
<section class="container-fluid  py-3 pb-0"> 
    <div class="container">
        <div class="row  mb-3">
            <div class="col-lg-8 m-auto">
                <h3 class="display-6 title text-center mb-5"><span class="section-title">{{ __('levels.our_services') }}</span></h3>
            </div>
        </div>
        <div class="row py-2 "> 
            @foreach ( $services as $service)      
                <div class="col-lg-3 col-sm-6  mb-3 text-center ">
                    <div class="service-item p-3 h-100"> 
                        <div class="service-item-box d-block">
                            <div class="my-4" >
                                <div class="service-box">
                                    <div class="icon-box d-flex"> 
                                        <img src="{{ $service->image }}" width="100%"/>
                                    </div>
                                </div>
                            </div>
                            <h5 class="mb-3 font-weight-bold">{{ $service->title }}</h5> 
                            <p class="mb-2">{!! \Str::limit(strip_tags($service->description), 120, ' ...<p><a href="'.route('service.details',$service->id).'" class="text-primary"><i class="fa fa-arrow-right"></i></a></p>') !!}</p>  
                        </div>
                    </div>
                </div>  
            @endforeach
 
        </div>
    </div>
</section>