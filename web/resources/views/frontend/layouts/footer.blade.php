<footer class="container-fluid bg-primary">
    <div class="container text-center">
        <div class="py-5 ">
            <div class="row my-5">
                <div class="col-lg-3 col-sm-6 ">
                    <div class="footer-logo text-left ">
                        <a href="index.html" class="d-inline-block"> 
                            <img class="logo" src="{{ settings()->light_logo_image }}" width="200"  alt="Logo">
                        </a>
                        <p class="text-white mt-3">{!! section(\App\Enums\SectionType::ABOUT,'about_us') !!}</p>
                    </div>

                    <div >
                        <h4 class="title text-white text-start  me-5">{{ __('levels.download_app') }}</h4>
                        <div class="d-flex justify-content-start align-items-center app-download mt-4">
                            <div class="me-4">
                               
                               <a href="{{ section(\App\Enums\SectionType::APP_LINK,'playstore_link') }}" title="{{ __('levels.play_store') }}">
                                    <i class="{{ section(\App\Enums\SectionType::APP_LINK,'playstore_icon') }}"></i>
                                </a> 
                            </div>
                            <div class="">
                               <a href="{{ section(\App\Enums\SectionType::APP_LINK,'ios_link') }}" title="{{ __('levels.ios_store') }}">
                                    <i class="{{ section(\App\Enums\SectionType::APP_LINK,'ios_icon') }}"></i>
                                </a> 
                            </div>
                        </div>
                    </div>
 
                </div>
                @if (tenant())         
                    <div class="col-lg-3 col-sm-6 ">
                        <h4 class="title text-white text-start">{{ __('levels.available_services') }}</h4>
                        <ul class="footer-list list-unstyled mt-3"> 
                            @foreach ($take_services as $footer_service)
                                <li class="list-ite"><a href="#">{{ $footer_service->title }}</a></li> 
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="col-lg-3 col-sm-6 ">
                        <h4 class="title text-white text-start">{{ __('levels.pricing') }}</h4>
                        <ul class="footer-list list-unstyled mt-3"> 
                            @foreach ($plans as $key => $plan)
                                <li class="list-ite"><a href="#">{{ $plan->name }}</a></li> 
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="col-lg-3 col-sm-6 ">
                    <h4 class="title text-white text-start ">{{ __('levels.about') }}</h4>
                    <ul class="footer-list list-unstyled mt-3">
                        <li class="list-ite"><a href="{{ route('get.faq.index') }}">{{ __('levels.faq') }} </a></li>
                        <li class="list-ite"><a href="{{ route('aboutus.index')}}">{{ __('levels.about_us') }}</a></li> 
                        <li class="list-ite"><a href="{{ route('contact.send.page')}}">{{ __('levels.contact_us') }}</a></li> 
                        <li class="list-ite"><a href="{{ route('privacy.policy.index') }}">{{ __('levels.privacy_policy') }}</a></li>
                        <li class="list-ite"><a href="{{ route('termsof.condition.index') }}">{{ __('levels.terms_of_use') }}</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-sm-6 ">
                    <h4 class="title text-white text-start">{{ section(\App\Enums\SectionType::SUBSCRIBE,'subscribe_title') }}</h4>
                    <p class="text-white mt-3 text-start">{{ section(\App\Enums\SectionType::SUBSCRIBE,'subscribe_description') }}</p>
 
                    <form action="{{ route('subscribe.store') }}" method="post">
                        @csrf
                        <div class="input-group mb-3 subscribe-form">
                            <input type="text" class="form-control" placeholder="{{ __('placeholder.enter_email') }}" name="email" value="{{ old('email') }}" required>
                            <div class="input-group-append">
                                <button type="submit" class="input-group-text btn bg-white text-primary"  ><i class="fa fa-paper-plane"></i></button>
                            </div> 
                        </div>
                        @error('email')
                            <p class="text-white text-start">{{ $message }}</p>
                        @enderror
                    </form> 
                    
                    <h4 class="title text-white text-start">{{ __('levels.social') }}</h4>
                    <div class="social-media mb-3 ">
                        <div class="row">
                            @foreach ($social_links as $social) 
                                <div class="col-2 mt-3 text-start">
                                    <a href="{{ @$social->link }}" class="d-inline-block" title="{{ $social->name }}">
                                        <i class="icon  {{ $social->icon }}"></i>
                                    </a>
                                </div> 
                            @endforeach  
                        </div>
                    </div>

                </div>
            </div> 
        </div> 

        <ul class="d-flex flex-wrap list-unstyled m-0 language-list justify-content-center pb-2 pe-0">
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','en') }}">
                    <i class="flag-icon flag-icon-us"></i> {{ __('levels.english') }}
                </a>
            </li>
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','bn') }}">
                    <i class="flag-icon flag-icon-bd"></i> {{ __('levels.bangla') }}
                </a>
            </li>
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','in') }}">
                    <i class="flag-icon flag-icon-in"></i> {{ __('levels.hindi') }}
                </a>
            </li> 
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','ar') }}">
                    <i class="flag-icon flag-icon-sa"></i> {{ __('levels.arabic') }}
                </a>
            </li>
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','fr') }}">
                    <i class="flag-icon flag-icon-fr"></i> {{ __('levels.franch') }}
                </a>
            </li>
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','es') }}">
                    <i class="flag-icon flag-icon-es"></i> {{ __('levels.spanish') }}
                </a>
            </li>
            <li >
                <a class="dropdown-item" href="{{ route('setlocalization','zh') }}">
                    <i class="flag-icon flag-icon-cn"></i> {{ __('levels.chinese') }}
                </a>
            </li> 
        </ul>
    
    </div>
</footer>
<footer class="container-fluid bg-primary border-top p-0">
    <div class="container text-center">
        <div class="py-2   ">
            <p class="text-white py-3 mb-0">  {{@settings()->copyright}}</p>
        </div>
    </div>
</footer>
