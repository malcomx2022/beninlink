<section class="py-3 pb-2">
    <div class="mb-5">
       <div class=" mb-3"> 
            <h3 class="display-6 title text-center mb-5">
                <span class="section-title">{{ __('levels.happy_achievement') }}</span>
            </h3> 
       </div>
       <div class="bg-primary "> 
        <div class="container-fluid">
            <div class="container ">
                <div class="row py-2 align-items-center">  
                    <div class="col-lg-3 col-sm-6  text-center "> 
                        <div class="d-flex align-items-center justify-content-center achievement">
                            <div class="text-center mb-0 icon" > 
                                 <i class="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'branch_icon') }}"></i> 
                            </div>
                            <div class="icon-text" > 
                                <h2 class="mb-0 font-weight-bold branch-title " ><span class="odometer" data-count="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'branch_count') }}">0</span><span class="odometer-position"></span></h2> 
                                <p class="branch-title mb-0">{{ section(\App\Enums\SectionType::ACHIEVEMENT,'branch_title') }}</p> 
                            </div>
                        </div> 
                    </div>  
                    <div class="col-lg-3 col-sm-6  text-center "> 
                        <div class="d-flex align-items-center justify-content-center achievement">
                            <div class="text-center mb-0 icon" > 
                                 <i class="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'parcel_icon') }}"></i> 
                            </div>
                            <div class="icon-text" >
                                <h2 class="mb-0 font-weight-bold branch-title  ">
                                    <span class="odometer" data-count="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'parcel_count') }}">0</span><span class="odometer-position"></span>
                                </h2> 
                                <p class="branch-title mb-0">{{ section(\App\Enums\SectionType::ACHIEVEMENT,'parcel_title') }}</p> 
                            </div>
                        </div> 
                    </div>  
                    <div class="col-lg-3 col-sm-6  text-center "> 
                        <div class="d-flex align-items-center justify-content-center achievement">
                            <div class="text-center mb-0 icon" > 
                                 <i class="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'merchant_icon') }}"></i> 
                            </div>
                            <div class="icon-text" >
                                <h2 class="mb-0 font-weight-bold branch-title" >
                                    <span class="odometer" data-count="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'merchant_count') }}">0</span><span class="odometer-position"></span>
                                </h2> 
                                <p class="branch-title mb-0">{{ section(\App\Enums\SectionType::ACHIEVEMENT,'merchant_title') }}</p> 
                            </div>
                        </div> 
                    </div>  
                    <div class="col-lg-3 col-sm-6  text-center "> 
                        <div class="d-flex align-items-center justify-content-center achievement">
                            <div class="text-center mb-0 icon" > 
                                 <i class="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'reviews_icon') }}"></i> 
                            </div>
                            <div class="icon-text" >
                                <h2 class="mb-0 font-weight-bold branch-title" >
                                    <span class="odometer" data-count="{{ section(\App\Enums\SectionType::ACHIEVEMENT,'reviews_count') }}">0</span>
                                    <span class="odometer-position"></span>
                                </h2> 
                                <p class="branch-title mb-0">{{ section(\App\Enums\SectionType::ACHIEVEMENT,'reviews_title') }}</p> 
                            </div>
                        </div> 
                    </div>   
                </div>
            </div>
        </div>
       </div>
   </div>
</section>