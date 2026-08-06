<!-- navigation -->
<section class="container-fluid  border-bottom">
    <nav class="navbar navbar-expand-lg center-nav transparent navbar-light p-3">
        <div class="container flex-lg-row flex-nowrap align-items-center">
            <div class="navbar-brand w-100 pt-0">
                <a href="{{ url('/') }}">
                    <img class="logo" src="{{ settings()->logo_image }}" width="200"  alt="Logo">
                </a>
            </div>
            <div class=" navbar-collapse offcanvas offcanvas-nav offcanvas-start text-bg-dark "  tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
                <div class="offcanvas-header w-90 ">
                    <h3 class="text-white fs-30 mb-0"> {{ settings()->name }}</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="menu offcanvas-body ms-lg-auto d-flex flex-column h-100 w-90">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link  @if(request()->is('/')) active @endif " href="{{ url('/') }}">{{ __('levels.home') }}</a></li>
                        <li class="nav-item"><a class="nav-link " href="{{ url('/') }}#pricing"  >{{ __('levels.pricing') }}</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->is('tracking')) active @endif " href="{{ route('tracking.index') }}"  >{{ __('levels.tracking') }}</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->is('get-blogs')) active @endif " href="{{ route('get.blogs') }}"  >{{ __('levels.blogs') }}</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->is('about-us')) active @endif " href="{{route('aboutus.index')}}" >{{ __('levels.about') }}</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->is('contact-send')) active @endif " href="{{ route('contact.send.page') }}">{{ __('levels.contact') }}</a></li>
                    </ul>
                    <div class="offcanvas-footer d-lg-none mt-3">
                        <div>
                            <a href="" class="link-inverse text-white">{{ settings()->email }}</a>
                            <br> {{ settings()->phone }} <br>
                            <nav class="nav social social-white mt-4">
                                @foreach ($social_links as $social) 
                                    <a href="{{ $social->link }}"><i class="{{ $social->icon }} text-white"></i></a>  
                                @endforeach
                            </nav> 
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar-other w-100 d-flex ms-auto auth-info">
                <ul class="navbar-nav flex-row align-items-center ms-auto">
                    @if(Auth::check())
                        <li class="nav-item ">  
                            <div class="dropdown">
                                <a class="dropdown-toggle nav-link auth-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                 {{ Auth::user()->name }}
                                </a> 
                                <ul class="dropdown-menu user-dropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('dashboard.index') }}">
                                            <i class="fa fa-home"></i>
                                            {{ __('levels.dashboard') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();">
                                            <i class="fas fa-power-off mr-2"></i>
                                            {{ __('menus.logout') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li> 
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="nav-item login-button"> 
                            <a href="{{ route('login') }}" class="nav-link auth-btn" >{{ __('levels.login') }}</a>
                        </li>
                        <li class="nav-item ">
                            @if (tenant())
                                <a href="{{ route('merchant.sign-up') }}" class="btn btn-primary auth-btn" >{{ __('levels.register') }}</a>
                            @else
                                <a href="{{ route('company.sign-up') }}" class="btn btn-primary auth-btn" >{{ __('levels.register') }}</a>
                            @endif
                        </li>
                    @endif
                    <li class="nav-item d-lg-none">
                        <button class="offcanvas-nav-btn " type="button"  data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar" ><span class="navbar-toggler-icon"></span></button>
                    </li>
                </ul>
            </div> 
        </div>
    </nav>
</section>
<!-- // navigation --> 