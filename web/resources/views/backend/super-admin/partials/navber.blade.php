
        <!-- navbar -->
        <nav class="navbar navbar-expand-lg center-nav transparent navbar-light p-3 fixed-top">
            <div class="container flex-lg-row flex-nowrap align-items-center">
                <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start text-bg-dark " tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
                    <div class="offcanvas-header w-90 ">
                        <h3 class="text-white fs-30 mb-0">{{ settings()->name }}</h3>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100 w-90">
                        <div class="dashboard-header">
                            <nav class="navbar navbar-expand-lg navbar-light  fixed-top   " >
                                <a class="navbar-brand" href="{{url('/')}}">
                                    <img src="{{ settings()->logo_image }}" class="logo"/>
                                </a>
                                <div class="dropdown lang-dropdown navbar_menus changeLocale mobileLocale ">
                                    <button class="btn  dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        @if(app()->getLocale() == "en")
                                            <i class="flag-icon flag-icon-us"></i> {{ __('levels.english') }}
                                        @elseif(app()->getLocale() == 'bn')
                                            <i class="flag-icon flag-icon-bd"></i> {{ __('levels.bangla') }}
                                        @elseif(app()->getLocale() == 'in')
                                            <i class="flag-icon flag-icon-in"></i> {{ __('levels.hindi') }}
                                        @elseif(app()->getLocale() == 'ar')
                                            <i class="flag-icon flag-icon-sa"></i> {{ __('levels.arabic') }}
                                        @elseif(app()->getLocale() == 'fr')
                                            <i class="flag-icon flag-icon-fr"></i> {{ __('levels.franch') }}
                                        @elseif(app()->getLocale() == 'es')
                                            <i class="flag-icon flag-icon-es"></i> {{ __('levels.spanish') }}
                                       @elseif(app()->getLocale()  == 'zh')
                                            <i class="flag-icon flag-icon-cn"></i> {{ __('levels.chinese') }}
                                        @endif
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="{{ route('setlocalization','en') }}"> <i class="flag-icon flag-icon-us"></i> {{ __('levels.english') }}</a>
                                        <a class="dropdown-item" href="{{ route('setlocalization','bn') }}"> <i class="flag-icon flag-icon-bd"></i> {{ __('levels.bangla') }}</a>
                                        <a class="dropdown-item" href="{{ route('setlocalization','in') }}"> <i class="flag-icon flag-icon-in"></i> {{ __('levels.hindi') }}</a>
                                        <a class="dropdown-item" href="{{ route('setlocalization','ar') }}"> <i class="flag-icon flag-icon-sa"></i> {{ __('levels.arabic') }}</a>
                                        <a class="dropdown-item" href="{{ route('setlocalization','fr') }}"> <i class="flag-icon flag-icon-fr"></i> {{ __('levels.franch') }}</a>
                                        <a class="dropdown-item" href="{{ route('setlocalization','es') }}"> <i class="flag-icon flag-icon-es"></i> {{ __('levels.spanish') }}</a>
                                        <a class="dropdown-item" href="{{ route('setlocalization','zh') }}"> <i class="flag-icon flag-icon-cn"></i> {{ __('levels.chinese') }}</a>
                                    </div>
                                    </div>
                                <div class=" navbar-collapse  " id="navbarSupportedContent">
                                    <ul class="navbar-nav ml-auto navbar-right-top">
                                        <li class="nav-item lang">
                                            <div class="form-group col-12 pt-1">
                                                <div class="dropdown lang-dropdown">
                                                    <button class="btn  dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        @if(app()->getLocale() == "en")
                                                            <i class="flag-icon flag-icon-us"></i> {{ __('levels.english') }}
                                                        @elseif(app()->getLocale() == 'bn')
                                                            <i class="flag-icon flag-icon-bd"></i> {{ __('levels.bangla') }}
                                                        @elseif(app()->getLocale() == 'in')
                                                            <i class="flag-icon flag-icon-in"></i> {{ __('levels.hindi') }}
                                                        @elseif(app()->getLocale() == 'ar')
                                                            <i class="flag-icon flag-icon-sa"></i> {{ __('levels.arabic') }}
                                                        @elseif(app()->getLocale() == 'fr')
                                                            <i class="flag-icon flag-icon-fr"></i> {{ __('levels.franch') }}
                                                        @elseif(app()->getLocale() == 'es')
                                                            <i class="flag-icon flag-icon-es"></i> {{ __('levels.spanish') }}
                                                        @elseif(app()->getLocale() == 'zh')
                                                            <i class="flag-icon flag-icon-cn"></i> {{ __('levels.chinese') }}
                                                        @endif
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="{{ route('setlocalization','en') }}"> <i class="flag-icon flag-icon-us"></i> {{ __('levels.english') }}</a>
                                                        <a class="dropdown-item" href="{{ route('setlocalization','bn') }}"> <i class="flag-icon flag-icon-bd"></i> {{ __('levels.bangla') }}</a>
                                                        <a class="dropdown-item" href="{{ route('setlocalization','in') }}"> <i class="flag-icon flag-icon-in"></i> {{ __('levels.hindi') }}</a>
                                                        <a class="dropdown-item" href="{{ route('setlocalization','ar') }}"> <i class="flag-icon flag-icon-sa"></i> {{ __('levels.arabic') }}</a>
                                                        <a class="dropdown-item" href="{{ route('setlocalization','fr') }}"> <i class="flag-icon flag-icon-fr"></i> {{ __('levels.franch') }}</a>
                                                        <a class="dropdown-item" href="{{ route('setlocalization','es') }}"> <i class="flag-icon flag-icon-es"></i> {{ __('levels.spanish') }}</a>
                                                        <a class="dropdown-item" href="{{ route('setlocalization','zh') }}"> <i class="flag-icon flag-icon-cn"></i> {{ __('levels.chinese') }}</a>
                                                    </div>
                                                    </div>
                                            </div>
                                        </li>
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            @if( hasPermission('dashboard_read') == true)
                                            <a class="dropdown-item {{ (request()->is('/*')) ? 'active' : '' }}" href="{{url('/')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1"><i class="fa fa-home"></i> {{__('menus.dashboard') }}</a>
                                            @endif
                                        </li>
                                     
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            @if(hasPermission('support_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/support*')) ? 'active' : '' }}" href="{{ route('support.index') }}" aria-expanded="false" data-target="#hubs" aria-controls="hubs"><i class="fa fa-comments"></i> {{ __('menus.support') }}</a>
                                            @endif
                                        </li>
                                    
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkUserRole" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fas fa-th"></i> {{__('menus.user_role')}}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkUserRole">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{__('menus.user_role')}}</h5>
                                                </div>
                                                @if(
                                                    hasPermission('role_read') == true        ||
                                                    hasPermission('designation_read') == true ||
                                                    hasPermission('department_read') == true  ||
                                                    hasPermission('user_read') == true
                                                )
                                                    @if(hasPermission('role_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/roles*')) ? 'active' : '' }}" href="{{ route('roles.index') }}">{{ __('menus.roles') }}</a>
                                                    @endif
                                                    @if(hasPermission('designation_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/designations*')) ? 'active' : '' }}" href="{{ route('designations.index') }}">{{ __('menus.designations') }}</a>
                                                    @endif
                                                    @if(hasPermission('department_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/departments*')) ? 'active' : '' }}" href="{{ route('departments.index') }}">{{ __('menus.departments') }}</a>
                                                    @endif
                                                    @if(hasPermission('user_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/users*')) ? 'active' : '' }}" href="{{ route('users.index') }}">{{ __('menus.users') }}</a>
                                                    @endif
                                                @endif
                                            </div>
                                        </li>
                            
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkSettings" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fa fa-cogs"></i> {{ __('menus.settings') }}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkSettings">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{ __('menus.settings') }}</h5>
                                                </div>
                                            
                                                @if(hasPermission('general_settings_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/general-settings*')) ? 'active' : '' }}" href="{{route('general-settings.index')}}">{{ __('menus.general_settings') }}</a>
                                                @endif 

                                                
                                                @if(hasPermission('database_backup_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/database-backup*')) ? 'active' : '' }}" href="{{route('database.backup.index')}}">{{ __('menus.database_backup') }}</a>
                                                @endif 
                                          
                                            </div>
                                        </li>
                                        <li class="nav-item dropdown admin-panel notification  d-lg-block">
                                            <a href="{{ url('/') }}" class="me-2"><i class="fa fa-globe navbar-globe"></i></a>
                                        </li>
                                        
                                        <li class="nav-item dropdown admin-panel notification d-lg-block">
                                            <a class="nav-link nav-icons mt-md-3" href="#" id="navbarDropdownMenuLink1" data-toggle="dropdown"   aria-haspopup="true" aria-expanded="false"><i class="fas fa-fw fa-bell"></i> <span class="indicator"></span></a>
                                            <ul class="dropdown-menu dropdown-menu-right notification-dropdown">
                                                <li>
                                                    <div class="notification-title"> Notification</div>
                                                    <div class="notification-list">
                                                        <div class="list-group">
                                                            @foreach (notifications() as $notify )
                                                                <a href="
                                                                @if($notify['type'] === 'support') {{ route('support.view',$notify['support_id']) }}
                                                                @elseif($notify['type'] === 'newsoffer') {{ route('news-offer.index') }} @endif"
                                                                class="list-group-item list-group-item-action active">
                                                                    <div class="notification-info">
                                                                        <div class="notification-list-user-img">
                                                                            <img src="{{ singleUser($notify['user_id'])->image }}" class="user-avatar-md rounded-circle">
                                                                        </div>
                                                                        <div class="notification-list-user-block">
                                                                            <span class="notification-list-user-name">
                                                                                {{ singleUser($notify['user_id'])->name }}
                                                                            </span>
                                                                            {{ $notify['subject'] }}
                                                                            <div class="notification-date">
                                                                                {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $notify['created_at'])->diffForHumans() }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </li>
                                        <!---To-do list---->
                                      
                                        <!---To-do list---->
                                        <li class="nav-item dropdown nav-user d-lg-block">
                                            <a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <img src="{{Auth::user()->image}}" alt="" class="user-avatar-md rounded-circle" style="object-fit: contain">
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLink2">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{ Auth::user()->name }}</h5>
                                                </div>
                                                <a class="dropdown-item" href="{{route('profile.index',Auth::user()->id)}}"><i class="fas fa-user mr-2"></i>{{ __('menus.profile') }}</a>
                                                <a class="dropdown-item" href="{{route('password.change',Auth::user()->id)}}"><i class="fas fa-key mr-2"></i>{{ __('menus.change_password') }}</a>
                                                <a class="dropdown-item" href="{{ route('logout') }}"
                                                    onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">
                                                    <i class="fas fa-power-off mr-2"></i>
                                                    {{ __('menus.logout') }}
                                                </a>
                                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                                    @csrf
                                                </form>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="navbar-other w-100 d-flex justify-content-between ">
                    <div  class="d-lg-none">
                        <a href="{{ url('/') }}">
                            <img src="{{ settings()->logo_image }}"  style="margin-top: 10px" width="150" alt="Logo">
                        </a>
                    </div>
                    <ul class="navbar-nav flex-row align-items-center ">
                        <li class="nav-item dropdown admin-panel notification  d-lg-none">
                            <a href="{{ url('/') }}" class="me-2"><i class="fa fa-globe"></i></a>
                        </li>
                        <li class="nav-item dropdown admin-panel notification  d-lg-none">
                            <a class="nav-link nav-icons mt-md-3" href="#" id="navbarDropdownMenuLink1" data-toggle="dropdown"   aria-haspopup="true" aria-expanded="false"><i class="fas fa-fw fa-bell"></i> <span class="mobile-notification indicator admin"></span></a>
                            <ul class="dropdown-menu dropdown-menu-right notification-dropdown">
                                <li>
                                    <div class="notification-title"> Notification</div>
                                    <div class="notification-list">
                                        <div class="list-group">
                                            @foreach (notifications() as $notify )
                                                <a href="
                                                @if($notify['type'] === 'support') {{ route('support.view',$notify['support_id']) }}
                                                @elseif($notify['type'] === 'newsoffer') {{ route('news-offer.index') }} @endif"
                                                class="list-group-item list-group-item-action active">
                                                    <div class="notification-info">
                                                        <div class="notification-list-user-img">
                                                            <img src="{{ singleUser($notify['user_id'])->image }}" class="user-avatar-md rounded-circle">
                                                        </div>
                                                        <div class="notification-list-user-block">
                                                            <span class="notification-list-user-name">
                                                                {{ singleUser($notify['user_id'])->name }}
                                                            </span>
                                                            {{ $notify['subject'] }}
                                                            <div class="notification-date">
                                                                {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $notify['created_at'])->diffForHumans() }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!---To-do list---->
                      
                        <!---To-do list---->
                        <li class="nav-item dropdown nav-user mobile d-lg-none">
                            <a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="{{Auth::user()->image}}" alt="" class="user-avatar-md rounded-circle">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLink2">
                                <div class="nav-user-info">
                                    <h5 class="mb-0 text-white nav-user-name">{{ Auth::user()->name }}</h5>
                                </div>
                                <a class="dropdown-item" href="{{route('profile.index',Auth::user()->id)}}"><i class="fas fa-user mr-2"></i>{{ __('menus.profile') }}</a>
                                <a class="dropdown-item" href="{{route('password.change',Auth::user()->id)}}"><i class="fas fa-key mr-2"></i>{{ __('menus.change_password') }}</a>

                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                    document.getElementById('logout-form').submit();">
                                    <i class="fas fa-power-off mr-2"></i>
                                    {{ __('menus.logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        <li class="nav-item d-lg-none">
                            <button class="offcanvas-nav-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar"><span class="navbar-toggler-icon"></span></button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
{{-- @include('backend.todo.to_do_list') --}}

