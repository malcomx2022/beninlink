<!-- left sidebar -->
<div class="col-12 nav-left-sidebar sidebar-dark">
    <ul class="navbar-nav">
        <li class="nav-divider">
            {{ __('menus.menu') }}
        </li>


   

        <li class="nav-item ">
            @if (hasPermission('dashboard_read') == true)
                <a class="nav-link {{ request()->is('/dashboard*') ? 'active' : '' }}" href="{{ url('/dashboard') }}"
                    aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1"><i
                        class="fa fa-home"></i>{{ __('menus.dashboard') }}</a>
            @endif
        </li>
        
        @if (hasPermission('support_read') == true)
            <li class="nav-item ">
                <a class="nav-link {{ request()->is('admin/support*') ? 'active' : '' }}"
                    href="{{ route('support.index') }}" aria-expanded="false" data-target="#hubs"
                    aria-controls="hubs"><i class="fa fa-comments"></i>{{ __('menus.support') }}</a>
            </li>
        @endif
  
        @if (hasPermission('role_read') == true ||
                hasPermission('designation_read') == true ||
                hasPermission('department_read') == true ||
                hasPermission('user_read') == true)
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/roles*', 'admin/users*', 'admin/designations*', 'admin/departments*') ? 'active' : '' }} "
                    href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-2"
                    aria-controls="submenu-2"><i class="fas fa-th"></i>{{ __('menus.user_role') }}</a>
                <div id="submenu-2"
                    class="{{ request()->is('admin/roles*', 'admin/users*', 'admin/designations*', 'admin/departments*') ? '' : 'collapse' }} submenu">
                    <ul class="nav flex-column">

                        @if (hasPermission('role_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/roles*') ? 'active' : '' }}"
                                    href="{{ route('roles.index') }}">{{ __('menus.roles') }}</a>
                            </li>
                        @endif
                        @if (hasPermission('designation_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/designations*') ? 'active' : '' }}"
                                    href="{{ route('designations.index') }}">{{ __('menus.designations') }}</a>
                            </li>
                        @endif
                        @if (hasPermission('department_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/departments*') ? 'active' : '' }}"
                                    href="{{ route('departments.index') }}">{{ __('menus.departments') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('user_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                                    href="{{ route('users.index') }}">{{ __('menus.users') }}</a>
                            </li>
                        @endif

                    </ul>
                </div>
            </li>
        @endif
 
        @if (hasPermission('company_read'))
            <li class="nav-item ">
                <a  class="nav-link {{ request()->is('super-admin/company*') ? 'active' : '' }}"
                    href="{{ route('company.index') }}" ><i class="fa-solid fa-building"></i>{{ __('menus.company') }}</a>
            </li>
        @endif

        @if (hasPermission('plans_read'))
            <li class="nav-item ">
                <a  class="nav-link {{ request()->is('super-admin/plan*') ? 'active' : '' }}"
                    href="{{ route('plan.index') }}" ><i class="fa-solid fa-grip-vertical"></i>{{ __('menus.plans') }}</a>
            </li>
        @endif

        @if (hasPermission('subscribe_read') == true)
            <li class="nav-item ">
                <a class="nav-link {{ request()->is('admin/subscribe*') ? 'active' : '' }}"
                    href="{{ route('subscribe.index') }}" aria-expanded="false" data-target="#active_log"
                    aria-controls="active_log"><i class="fas fa-users"></i>{{ __('account.subscribe') }}</a>
            </li>
        @endif
     
        <li class="nav-item ">
            <a class="nav-link {{ request()->is('super-admin/subscription/history*') ? 'active' : '' }}"
                href="{{ route('subscription.history') }}" aria-expanded="false" data-target="#active_log"
                aria-controls="active_log"><i class="fas fa-clock-rotate-left"></i>{{ __('levels.subscription_history') }}</a>
        </li> 
        

        @if (
            hasPermission('social_link_read') == true ||
            hasPermission('service_read')     == true ||
            hasPermission('why_courier_read') == true ||
            hasPermission('faq_read')         == true ||
            hasPermission('partner_read')     == true ||
            hasPermission('blogs_read')       == true ||
            hasPermission('pages_read')       == true ||
            hasPermission('section_read')     == true 
        )
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/front-web*') ? 'active' : '' }}" href="#"
                data-toggle="collapse" aria-expanded="false" data-target="#front-web"
                aria-controls="front-web"><i class="fas fa-globe"></i>{{ __('levels.front_web') }}</a>

            <div id="front-web" class="{{ request()->is('admin/front-web*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">
                    @if (hasPermission('social_link_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/social-link*') ? 'active' : '' }}"
                                href="{{ route('social.link.index') }}">{{ __('levels.social_link') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('service_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/service*') ? 'active' : '' }}"
                                href="{{ route('service.index') }}">{{ __('levels.service') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('why_courier_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/why-courier*') ? 'active' : '' }}"
                                href="{{ route('why.courier.index') }}">{{ __('levels.why_courier') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('faq_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/faq*') ? 'active' : '' }}"
                                href="{{ route('faq.index') }}">{{ __('levels.faq') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('partner_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/partner*') ? 'active' : '' }}"
                                href="{{ route('partner.index') }}">{{ __('levels.partner') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('blogs_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/blogs*') ? 'active' : '' }}"
                                href="{{ route('blogs.index') }}">{{ __('levels.blogs') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('pages_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/pages*') ? 'active' : '' }}"
                                href="{{ route('pages.index') }}">{{ __('levels.pages') }}</a>
                        </li>
                    @endif
                    @if (hasPermission('section_read') == true)
                        <li class="nav-item ">
                            <a class="nav-link {{ request()->is('admin/front-web/section*') ? 'active' : '' }}"
                                href="{{ route('section.index') }}">{{ __('levels.section') }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>
    @endif

 
        @if (hasPermission('database_backup_read') == true ||
                hasPermission('general_settings_read') == true ||
                hasPermission('sms_settings_read') == true ||
                hasPermission('currency_read') == true )
            <!---for setting--->
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/database-backup*', 'admin/delivery-category*', 'admin/delivery-category*', 'admin/delivery-charge*', 'admin/packaging*', 'admin/delivery-type*', 'admin/liquid-fragile*', 'admin/sms-settings*', 'admin/sms-send-settings*', 'admin/general-settings*', 'admin/notification-settings*', 'admin/googlemap-settings*', 'admin/asset-category*', 'admin/social-login-setting*', 'admin/pay-out/setup*', 'admin/settings/pay-out/setup*', 'admin/settings/invoice-generate-menually*', 'admin/currency*') ? 'active' : '' }} "
                    href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-0"
                    aria-controls="submenu-0"><i class="fa fa-cogs"></i> {{ __('menus.settings') }}</a>
                <div class="{{ request()->is('admin/database-backup*', 'admin/delivery-category*', 'admin/delivery-charge*', 'admin/packaging*', 'admin/delivery-type*', 'admin/liquid-fragile*', 'admin/sms-settings*', 'admin/sms-send-settings*', 'admin/general-settings*', 'admin/notification-settings*', 'admin/googlemap-settings*', 'admin/asset-category*', 'admin/social-login-setting*', 'admin/pay-out/setup*', 'admin/settings/pay-out/setup*', 'admin/settings/invoice-generate-menually*', 'admin/currency*') ? '' : 'collapse' }} submenu"
                    id="submenu-0" class="collapse submenu">
                    <ul class="nav flex-column">

                        @if (hasPermission('general_settings_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/general-settings*') ? 'active' : '' }}"
                                    href="{{ route('general-settings.index') }}">{{ __('menus.general_settings') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('payout_setup_settings_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/settings/pay-out/setup*') ? 'active' : '' }}"
                                    href="{{ route('payout.setup.settings.index') }}">{{ __('menus.payout_setup') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('sms_settings_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/sms-settings*') ? 'active' : '' }}"
                                    href="{{ route('sms-settings.index') }}">{{ __('menus.sms_settings') }}</a>
                            </li>
                        @endif
                     
                        @if (hasPermission('currency_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/currency*') ? 'active' : '' }}"
                                    href="{{ route('currency.index') }}">{{ __('settings.currency') }}</a>
                            </li>
                        @endif
 
                        @if (hasPermission('database_backup_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/database-backup*') ? 'active' : '' }}"
                                    href="{{ route('database.backup.index') }}">{{ __('menus.database_backup') }}</a>
                            </li>
                        @endif 
                    </ul>
                </div>
            </li>
        @endif
    </ul>
</div>
<!-- end left sidebar -->
