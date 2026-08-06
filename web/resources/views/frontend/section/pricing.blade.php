<section id="pricing" class="container-fluid   py-3 pb-0"> 
    <div class="container  pb-5">
        <div class="row  mb-3">
            <div class="col-lg-8 m-auto">
                <h3 class="display-6 title text-center mb-5"><span class="section-title">{{ settings()->name }} {{ __('levels.pricing') }}</span></h3>
            </div>
        </div>
        <div class="row py-2 align-items-center">  
            <div class="col-12 " aria-label="breadcrumb"> 
                <ul class="nav nav-pills pricing justify-content-center mb-5 breadcrumb" id="pills-tab" role="tablist" >
                    <li class="nav-item breadcrumb-item" role="presentation">
                      <button class="nav-link  active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">{{ __('levels.same_day') }}</button>
                    </li>
                    <li class="nav-item breadcrumb-item" role="presentation">
                      <button class="nav-link " id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">{{ __('levels.next_day') }}</button>
                    </li>
                    <li class="nav-item breadcrumb-item" role="presentation">
                      <button class="nav-link " id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#pills-contact" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">{{ __('levels.sub_city') }}</button>
                    </li>
                    <li class="nav-item breadcrumb-item" role="presentation">
                      <button class="nav-link " id="pills-disabled-tab" data-bs-toggle="pill" data-bs-target="#pills-disabled" type="button" role="tab" aria-controls="pills-disabled" aria-selected="false" >{{ __('levels.outside_city') }}</button>
                    </li>
                  </ul>
                  <div class="tab-content charge-content" id="pills-tabContent">
                    <div class="tab-pane  show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">
                        <div class="row justify-content-center">
                            @foreach ($pricing as $samedayPrice)      
                                <div class="col-sm-4 col-lg-2 charge-col ">
                                    <div class="text-center charge-item">
                                        <div class="row align-items-center"> 
                                            <p class="mb-0">{{ __('levels.up_to') }} {{ $samedayPrice->weight }} ( {{ @$samedayPrice->category->title }} )</p> 
                                            <h3 class="font-weight-bold ">{{ settings()->currency }} {{ $samedayPrice->same_day }}</h3> 
                                        </div>
                                    </div>
                                </div>    
                            @endforeach

                        </div>
                    </div>
                    <div class="tab-pane " id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
                        <div class="row justify-content-center">
                            @foreach ($pricing as $nextdayPrice)      
                                <div class="col-sm-4 col-lg-2 charge-col">
                                    <div class="text-center charge-item">
                                        <div class="row align-items-center"> 
                                            <p class="mb-0">{{ __('levels.up_to') }} {{ $nextdayPrice->weight }} ( {{ @$nextdayPrice->category->title }} )</p> 
                                            <h3 class="font-weight-bold ">{{ settings()->currency }} {{ $nextdayPrice->next_day }}</h3> 
                                        </div>
                                    </div>
                                </div>    
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane " id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab" tabindex="0">
                        <div class="row justify-content-center">
                            @foreach ($pricing as $subcityPrice)      
                                <div class="col-sm-4 col-lg-2 charge-col ">
                                    <div class="text-center charge-item">
                                        <div class="row align-items-center"> 
                                            <p class="mb-0">{{ __('levels.up_to') }} {{ $subcityPrice->weight }} ( {{ @$subcityPrice->category->title }} )</p> 
                                            <h3 class="font-weight-bold ">{{ settings()->currency }} {{ $subcityPrice->sub_city }}</h3> 
                                        </div>
                                    </div>
                                </div>    
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane " id="pills-disabled" role="tabpanel" aria-labelledby="pills-disabled-tab" tabindex="0">
                        <div class="row justify-content-center">
                            @foreach ($pricing as $outsidecityPrice)      
                                <div class="col-sm-4 col-lg-2 charge-col ">
                                    <div class="text-center charge-item">
                                        <div class="row align-items-center"> 
                                            <p class="mb-0">{{ __('levels.up_to') }} {{ $outsidecityPrice->weight }} ( {{ @$outsidecityPrice->category->title }} )</p> 
                                            <h3 class="font-weight-bold ">{{ settings()->currency }} {{ $outsidecityPrice->outside_city }}</h3> 
                                        </div>
                                    </div>
                                </div>    
                            @endforeach
                        </div>
                    </div>
                  </div>
            </div>  
        </div>
    </div>
</section>