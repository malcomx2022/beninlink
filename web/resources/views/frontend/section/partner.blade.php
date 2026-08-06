 
<section class="container-fluid py-3 pb-0">
    <div class="mb-0">
        <div class=" mb-3"> 
            <h3 class="display-6 title text-center mb-5">
                <span class="section-title">{{ __('levels.our_partner') }}</span>
            </h3> 
        </div> 
        <div class="container partner py-5">
            <div class="swiper">
                <div class="swiper-wrapper ">
                    <!-- Slides -->
                    @foreach ($partners as $partner)    
                        <div class="swiper-slide">
                            <a href="{{ @$partner->link }}" class="d-inline-block" target="_blank">
                                <img src="{{ @$partner->image }}" class="partner-logo"  />
                            </a>
                        </div>    
                    @endforeach
                </div>
                <div class="swiper-pagination position-relative mt-5"></div>
            </div>
        </div> 
    </div>
</section>
 