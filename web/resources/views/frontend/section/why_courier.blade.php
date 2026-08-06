
<section class="container-fluid py-5 pb-0"> 
    <div class="container mb-5">
        <div class="row  mb-3">
            <div class="col-lg-8 m-auto">
                <h3 class="display-6 title text-center mb-5"><span class="section-title">{{ __('levels.why') }} Choose US</span></h3>
            </div>
        </div>
        <div class="row py-2 align-items-top "> 
            @foreach ($whycouriers as $whycourier)
                <div class="col-lg-4 col-sm-6  text-center why-courier-col"> 
                    <div class="whycourier-item row">
                        <div class="text-center whycourier-box mb-0" > 
                            <img src="{{ $whycourier->image }}" width="100%"/>  
                        </div>
                        <h5 class="my-3 font-weight-bold">{{ $whycourier->title }}</h5> 
                    </div> 
                </div>  
            @endforeach 
        </div>
    </div>
</section>