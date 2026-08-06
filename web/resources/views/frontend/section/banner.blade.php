<!-- banner -->
<section class="container-fluid pb-3  ">
    <div class="container pt-5 pb-5 ">
        <div class="row align-items-center mt-3">
            <div class="col-lg-6">
                <h1 class="banner-hilight display-1 text-uppercase ">
                    <span>{{ section(\App\Enums\SectionType::BANNER,'title_1') }}</span><br/>
                    <span class="bg-primary rounded py-2 px-5">{{ section(\App\Enums\SectionType::BANNER,'title_2') }}</span><br/>
                    <span>{{ section(\App\Enums\SectionType::BANNER,'title_3') }}</span> 
                </h1>
                <p class="fs-1 banner-subtitle ">{{ section(\App\Enums\SectionType::BANNER,'sub_title') }}</p>
                 
                <form action="{{ route('tracking.index') }}" method="get">
                    <div class="input-group mb-3 tracking-form">
                        <input type="text" class="form-control" placeholder="{{ __('levels.enter_tracking_id') }}" name="tracking_id"  >
                        <div class="input-group-append">
                        <button type="submit" class="input-group-text bg-primary"  >{{ __('levels.track_now') }}</button>
                        </div>
                    </div>
                </form>
               
            </div>
            <div class="col-lg-6">
                <div class="position-relative offset-lg-1 " data-cue="slideInDown" data-show="true" >
                    @if(section(\App\Enums\SectionType::BANNER,'banner'))
                        <img src="{{ section(\App\Enums\SectionType::BANNER,'banner') }}" class="banner-image" />
                    @endif
                </div>
            </div> 
        </div>
    </div>
</section>