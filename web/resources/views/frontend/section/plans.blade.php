<section id="pricing" class="container-fluid   py-3 pb-0">
    <div class="container  pb-5">
        <div class="row  mb-3">
            <div class="col-lg-8 m-auto">
                <h3 class="display-6 title text-center mb-5"><span class="section-title">{{ settings()->name }}
                        {{ __('levels.pricing') }}</span></h3>
            </div>
        </div>
        <div class="row py-2 pt-0 ">
            @foreach ($plans as $key => $plan)
                <div class="col-xl-4 mt-3">
                    <div class="card  text-center p-5 ">
                        <div>
                            <h3 class="text-center my-2">{{ @$plan->name }}</h3>    
                            <div class="d-flex justify-content-center my-4 ">
                                @php
                                    $settings = App\Models\Backend\GeneralSettings::find(1); 
                                @endphp
                                <h3 class="mt-2px font-weight-bold">{{ @$settings->currency }} {{ @$plan->price }} </h3>
                                <div class="mx-2 text-start">
                                    <p class="mb-2 font-weight-bold "> / {{ @$plan->intval_name }}</p>
                                    <p>when billed annually</p>
                                </div>
                            </div>
                            <ul class="list-unstyled text-start plan-accordion">
                                <li><i class="fa fa-check text-success me-3"></i>Total parcel count
                                    {{ @$plan->parcel_count }}</li>

                                @foreach ($allmodules as $key=>$module)
                                    @if (in_array($module, $plan->modules))
                                        <li><i class="fa fa-check text-success me-3"></i>{{ __('permissions.' . @$module) }}
                                        </li>
                                    @else
                                        <li><i class="fa fa-times text-danger  me-3"></i>{{ __('permissions.' . @$module) }}
                                        </li>
                                    @endif
                                @endforeach
                                <li><i class="fa fa-check text-success  me-3"></i>{{ __('levels.more_modules') }}</li>

                            </ul>
                        </div>
                        <div class="align-bottom"> 
                            <a class="btn btn-primary subscribe-btn" href="{{ route('register') }}" >Subscribe</a> 
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
