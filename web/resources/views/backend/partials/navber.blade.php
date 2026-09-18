
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
                                    <img alt="{{ settings()->name }}" src="{{ settings()->logo_image }}" class="logo" />
                                </a>
                                <div class="dropdown lang-dropdown navbar_menus changeLocale mobileLocale ">
                                    <button class="btn  dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        @include('partials.locale-current')
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        @include('partials.locale-links')
                                    </div>
                                    </div>
                                <div class=" navbar-collapse  " id="navbarSupportedContent">
                                    <ul class="navbar-nav ml-auto navbar-right-top">
                                        <li class="nav-item lang">
                                            <div class="form-group col-12 pt-1">
                                                <div class="dropdown lang-dropdown">
                                                    <button class="btn  dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        @include('partials.locale-current')
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        @include('partials.locale-links')
                                                    </div>
                                                    </div>
                                            </div>
                                        </li>
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            @if( hasPermission('dashboard_read') == true)
                                            <a class="dropdown-item {{ (request()->is('/*')) ? 'active' : '' }}" href="{{url('/')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1"><i class="fa fa-home"></i> {{__('menus.dashboard') }}</a>
                                            @endif
                                        </li>
                                        <li   class="nav-item dropdown nav-user navbar_menus" >
                                            @if( hasPermission('delivery_man_read') == true)
                                                <a class="dropdown-item {{ (request()->is('admin/deliveryman*')) ? 'active' : '' }}" href="{{route('deliveryman.index')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1"><i class="fa fa-people-carry"></i> {{ __('menus.deliveryman') }}</a>
                                            @endif
                                        </li>
                                        {{-- hub manage --}}
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkHubManage" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fas fa-warehouse"></i> {{ __('menus.hub_mange') }}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkHubManage">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{ __('menus.hub_mange') }}</h5>
                                                </div>
                                                @if(hasPermission('hub_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/hubs*','admin/hub*')) ? 'active' : '' }}" href="{{ route('hubs.index') }}" >{{ __('menus.hubs') }}</a>
                                                @endif
                                                @if(hasPermission('hub_payment_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/request/hub/payment*')) ? 'active' : '' }}" href="{{ route('hub.hub-payment.index') }}">{{ __('menus.payments') }}</a>
                                                @endif
                                            </div>
                                        </li>
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkMerchantManage" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fa fa-users"></i>{{ __('menus.merchant_manage') }}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkMerchantManage">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{ __('menus.merchant_manage') }}</h5>
                                                </div>

                                                @if( hasPermission('merchant_read') == true || hasPermission('payment_read') == true)
                                                    @if( hasPermission('merchant_read') == true )
                                                    <a class="dropdown-item {{ (request()->is('admin/merchant*')) ? 'active' : '' }}" href="{{route('merchant.index')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1">{{ __('menus.merchants') }}</a>
                                                    @endif
                                                    @if(hasPermission('payment_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/payment*')) ? 'active' : '' }}" href="{{ route('merchant.manage.payment.index') }}">Payments</a>
                                                    @endif
                                                @endif
                                            </div>
                                        </li>

                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            @if(hasPermission('todo_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/todo/todo_list*')) ? 'active' : '' }}" href="{{ route('todo.index') }}" aria-expanded="false" data-target="#hubs" aria-controls="hubs"><i class="fas fa-tasks"></i> {{ __('menus.todo_list') }}</a>
                                            @endif
                                        </li>
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            @if(hasPermission('support_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/support*')) ? 'active' : '' }}" href="{{ route('support.index') }}" aria-expanded="false" data-target="#hubs" aria-controls="hubs"><i class="fa fa-comments"></i> {{ __('menus.support') }}</a>
                                            @endif
                                        </li>
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            @if( hasPermission('parcel_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/parcel*')) ? 'active' : '' }}" href="{{route('parcel.index')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1"><i class="fa fa-dolly"></i> {{ __('menus.parcel') }}</a>
                                            @endif
                                            @if(hasPermission('news_offer_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/news-offer*')) ? 'active' : '' }}" href="{{route('news-offer.index')}}" ><i class="fa fa-newspaper " style="margin-right: 10px!important"></i> {{__('menus.news_offer')}}</a>
                                            @endif
                                            @if(hasPermission('log_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/logs*')) ? 'active' : '' }}" href="{{route('logs.index')}}" aria-expanded="false" data-target="#active_log" aria-controls="active_log"><i class="fa fa-history"></i> {{ __('menus.active_logs') }}</a>
                                            @endif
                                            @if(hasPermission('fraud_read') == true)
                                            <a class="dropdown-item {{ (request()->is('admin/fraud*')) ? 'active' : '' }}" href="{{route('fraud.index')}}" aria-expanded="false" data-target="#active_log" aria-controls="active_log"><i class="fa fa-user-times"></i> {{__('menus.fraud_check')}}</a>
                                            @endif
                                        </li>
                                        <li  class="nav-item dropdown nav-user navbar_menus">
                                            @if(hasPermission('subscribe_read') == true)
                                                <a class="dropdown-item{{ (request()->is('admin/subscribe*')) ? 'active' : '' }}" href="{{route('subscribe.index')}}" aria-expanded="false" data-target="#active_log" aria-controls="active_log"><i class="fas fa-users"></i>{{__('account.subscribe')}}</a>

                                            @endif
                                        </li>
                                        {{-- pickup request --}}
                                        @if( hasPermission('pickup_request_regular') == true || hasPermission('pickup_request_express') == true)
                                            <li class="nav-item dropdown nav-user navbar_menus">
                                                <a class="dropdown-item {{ (request()->is('admin/pickup-request*')) ? 'active' : '' }}" href="#" id="navbarDropdownMenuLinkPickupRequest" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                                                    <div class="d-flex justify-content-between">
                                                        <span> <i class="fa fa-truck-moving"></i>{{ __('menus.pickup_request') }}</span>
                                                        <span><i class="fa fa-angle-down"></i></span>
                                                    </div>
                                                </a>
                                                <div  class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkHubManage">
                                                    <ul class="nav flex-column">
                                                        @if(hasPermission('pickup_request_regular') == true)
                                                            <a class="dropdown-item  {{ (request()->is('admin/pickup-request/regular*')) ? 'active' : '' }}" href="{{ route('pickup.request.regular') }}" >{{ __('menus.regular') }}</a>
                                                        @endif
                                                        @if(hasPermission('pickup_request_express') == true)
                                                            <a class="dropdown-item {{ (request()->is('admin/pickup-request/express*')) ? 'active' : '' }}" href="{{ route('pickup.request.express') }}" >{{ __('menus.express') }}</a>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </li>
                                        @endif
                                        <li  class="nav-item dropdown nav-user navbar_menus">
                                            @if(hasPermission('assets_read') == true)
                                                <a class="dropdown-item {{ (request()->is('admin/assets*')) ? 'active' : '' }}" href="{{route('asset.index')}}" aria-expanded="false" data-target="#hubs" aria-controls="hubs"><i class="fa fa-fw fa-users"></i> {{ __('menus.assets') }}</a>
                                            @endif
                                        </li>

                                        {{-- payment received --}}
                                        @if(hasPermission('online_payment_read') == true)
                                        <li  class="nav-item dropdown nav-user navbar_menus" >
                                            <a class="dropdown-item {{ (request()->is('admin/online-payment-list*')) ? 'active' : '' }}" href="{{route('online.payment.list')}}"  ><i class="fas fa-credit-card"></i>{{ __('menus.payments_received') }}</a>
                                        </li>
                                        @endif
                                        {{-- payout --}}
                                        @if(hasPermission('payout_read') == true)
                                        <li  class="nav-item dropdown nav-user navbar_menus" >
                                            <a class="dropdown-item {{ (request()->is('admin/payout*')) ? 'active' : '' }}" href="{{route('payout.index')}}"  ><i class="fas fa-hand-holding-usd"></i>{{ __('menus.payout') }}</a>
                                        </li>
                                        @endif
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkAccounts" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fas fa-user"></i> {{ __('menus.accounts') }}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkAccounts">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{ __('menus.accounts') }}</h5>
                                                </div>
                                                @if(hasPermission('account_heads_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/account-heads*')) ? 'active' : '' }}" href="{{route('account.heads.index')}}">{{ __('menus.account_heads') }}</a>
                                                @endif
                                                @if(hasPermission('account_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/accounts*')) ? 'active' : '' }}" href="{{ route('accounts.index') }}">{{ __('menus.accounts') }}</a>
                                                @endif
                                                @if(hasPermission('fund_transfer_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/fund-transfer*')) ? 'active' : '' }}" href="{{ route('fund-transfer.index') }}">{{ __('menus.fund_transfer') }}</a>
                                                @endif
                                                @if(hasPermission('income_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/income*')) ? 'active' : '' }}" href="{{ route('income.index') }}">{{ __('menus.income') }}</a>
                                                @endif
                                                @if(hasPermission('expense_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/expense*')) ? 'active' : '' }}" href="{{ route('expense.index') }}">{{ __('menus.expense') }}</a>
                                                @endif
                                                @if(hasPermission('bank_transaction_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/bank-transaction*')) ? 'active' : '' }}" href="{{ route('bank-transaction.index') }}">{{ __('menus.bank_transaction') }}</a>
                                                @endif
                                                @if(hasPermission('cash_received_from_delivery_man_read' )== true)
                                                    @if(Auth::user()->hub_id)
                                                        <a class="dropdown-item {{ (request()->is('admin/hub/cash-received-deliveryman*')) ? 'active' : '' }}" href="{{ route('cash.received.deliveryman.index') }}">{{ __('permissions.cash_received_from_delivery_man') }}</a>
                                                    @endif
                                                @endif
                                                @if(hasPermission('hub_payment_request_read' )== true)
                                                    @if(auth()->user()->hub_id)
                                                        <a class="dropdown-item {{ (request()->is('admin/hub/payment-request*')) ? 'active' : '' }}" href="{{ route('hub-panel.payment-request.index') }}">{{ __('menus.hub_payment_request') }}</a>
                                                    @endif
                                                @endif

                                                @if(hasPermission('paid_invoice_read' )== true)
                                                    <a class="dropdown-item {{ (request()->is('admin/paid/invoice*')) ? 'active' : '' }}" href="{{ route('paid.invoice.index') }}">{{ __('invoice.paid_invoice') }}</a>
                                                @endif
                                            </div>
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
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkPayroll" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fas fa-hand-holding-usd"></i> {{__('salary.payroll') }}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkPayroll">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{__('salary.payroll') }}</h5>
                                                </div>
                                                @if( hasPermission('salary_generate_read') == true || hasPermission('salary_read') == true)
                                                    @if( hasPermission('salary_generate_read') == true )
                                                    <a class="dropdown-item {{ (request()->is('admin/salary/salary-generate*','admin/reports/parcel-filter-reports')) ? 'active' : '' }}" href="{{route('salary.generate.index')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1">{{ __('salary.salary_generate') }}</a>
                                                    @endif
                                                    @if(hasPermission('salary_read') == true)
                                                    <a class="dropdown-item {{ (request()->is('admin/salarys*')) ? 'active' : '' }}" href="{{ route('salary.index') }}">{{ __('menus.salary') }}</a>
                                                    @endif
                                                @endif
                                            </div>
                                        </li>
                                        <li class="nav-item dropdown nav-user navbar_menus">
                                            <a class="dropdown-item" href="#" id="navbarDropdownMenuLinkReports" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="fas fa-print"></i> {{__('reports.title') }}</span>
                                                    <span><i class="fa fa-angle-down"></i></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLinkReports">
                                                <div class="nav-user-info">
                                                    <h5 class="mb-0 text-white nav-user-name">{{__('reports.title') }}</h5>
                                                </div>
                                                @if( hasPermission('parcel_status_reports') == true || hasPermission('parcel_wise_profit') == true)
                                                    @if( hasPermission('parcel_status_reports') == true )
                                                        <a class="dropdown-item {{ (request()->is('admin/reports/parcel-reports*','admin/reports/parcel-filter-reports')) ? 'active' : '' }}" href="{{route('parcel.reports')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1">{{ __('reports.parcel_reports') }}</a>
                                                    @endif
                                                    @if(hasPermission('parcel_wise_profit') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/reports/parcel-wise*')) ? 'active' : '' }}" href="{{ route('parcel.wise.profit.index') }}">{{ __('reports.parcel_wise_profit') }}</a>
                                                    @endif
                                                    @if(hasPermission('salary_reports') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/reports/salary-reports*','admin/reports/reports-salary-reports*')) ? 'active' : '' }}" href="{{ route('salary.reports') }}">{{ __('reports.salary_reports') }}</a>
                                                    @endif
                                                    @if(hasPermission('merchant_hub_deliveryman') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/reports/merchant-hub-deliveryman*','admin/reports/mhd-reports')) ? 'active' : '' }}" href="{{ route('merchant.hub.deliveryman.reports') }}">{{ __('reports.merchant_hub_deliveryman') }}</a>
                                                    @endif
                                                    @if(hasPermission('parcel_total_summery') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/reports/parcel-total-summery*','admin/reports/parcel-filter-total-summery*')) ? 'active' : '' }}" href="{{ route('parcel.total.summery.index') }}">{{ __('menus.parcel_total_summery') }}</a>
                                                    @endif
                                                @endif
                                            </div>
                                        </li>
                                        {{-- push notification --}}
                                        @if( hasPermission('push_notification_read') == true)
                                            <li  class="nav-item dropdown nav-user navbar_menus" >
                                                <a class="dropdown-item {{ (request()->is('admin/push-notification*')) ? 'active' : '' }}" href="{{route('push-notification.index')}}" aria-expanded="false" data-target="#submenu-1" aria-controls="submenu-1"><i class="fa fa-bell"></i>{{ __('menus.push_notification') }}</a>
                                            </li>
                                        @endif
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
                                                @if(
                                                    hasPermission('delivery_category_read') == true ||
                                                    hasPermission('delivery_charge_read') == true   ||
                                                    hasPermission('delivery_type_read') == true     ||
                                                    hasPermission('liquid_fragile_read') == true    ||
                                                    hasPermission('packaging_read') == true
                                                )
                                                    @if(hasPermission('delivery_category_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/delivery-category*')) ? 'active' : '' }}" href="{{route('delivery-category.index')}}">{{ __('menus.delivery_category') }}</a>
                                                    @endif
                                                    @if(hasPermission('delivery_charge_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/delivery-charge*')) ? 'active' : '' }}" href="{{route('delivery-charge.index')}}">{{ __('menus.delivery_charge') }}</a>
                                                    @endif
                                                    @if(hasPermission('delivery_type_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/delivery-type*')) ? 'active' : '' }}" href="{{route('delivery-type.index')}}">{{ __('menus.delivery_type') }}</a>
                                                    @endif
                                                    @if(hasPermission('liquid_fragile_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/liquid-fragile*')) ? 'active' : '' }}" href="{{route('liquid-fragile.index')}}">{{ __('menus.liquid_fragile') }}</a>
                                                    @endif
                                                    @if(hasPermission('sms_settings_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/sms-settings*')) ? 'active' : '' }}" href="{{route('sms-settings.index')}}">{{ __('menus.sms_settings') }}</a>
                                                    @endif
                                                    @if(hasPermission('sms_send_settings_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/sms-send-settings*')) ? 'active' : '' }}" href="{{route('sms-send-settings.index')}}">{{ __('menus.sms_send_settings') }}</a>
                                                    @endif
                                                    @if (hasPermission('packaging_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/packaging*')) ? 'active' : '' }}" href="{{route('packaging.index')}}">{{ __('menus.packaging') }}</a>
                                                    @endif
                                                    @if(hasPermission('general_settings_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/general-settings*')) ? 'active' : '' }}" href="{{route('general-settings.index')}}">{{ __('menus.general_settings') }}</a>
                                                    @endif
                                                    @if(hasPermission('asset_category_read') == true)
                                                        <a class="dropdown-item  {{ (request()->is('admin/asset-category*')) ? 'active' : '' }} " href="{{route('asset-category.index')}}">{{ __('menus.assets_category') }}</a>
                                                    @endif
                                                    @if(hasPermission('database_backup_read') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/database-backup*')) ? 'active' : '' }}" href="{{route('database.backup.index')}}">{{ __('menus.database_backup') }}</a>
                                                    @endif
                                                    @if(hasPermission('invoice_generate_menually') == true)
                                                        <a class="dropdown-item {{ (request()->is('admin/invoice-generate/menually*')) ? 'active' : '' }}" href="{{route('invoice.generate.menually.index')}}">{{ __('menus.invoice_generate_menually') }}</a>
                                                    @endif
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
                                                                            <img alt="" src="{{ singleUser($notify['user_id'])->image }}" class="user-avatar-md rounded-circle">
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
                                        @if(hasPermission('todo_create')== true)
                                        <li class="nav-item dropdown connection mt-xl-2 mt-md-2 mt-lg-2 d-lg-block">
                                            <label id="todoModal1" data-target="#todoModal" class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-url="{{route('todo.modal')}}"><i class="fa fa-edit"></i> {{ __('to_do.to_do')}}</label>
                                        </li>
                                        @endif
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
                            <img src="{{ settings()->logo_image }}"  style="margin-top: 10px"   height="30px" style="object-fit: contain" alt="Logo">
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
                                                            <img alt="" src="{{ singleUser($notify['user_id'])->image }}" class="user-avatar-md rounded-circle">
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
                        @if(hasPermission('todo_create')== true)
                        <li class="nav-item dropdown connection mt-lg-3 d-lg-none">
                            <label    id="todoModal1" data-target="#todoModal" class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-url="{{route('todo.modal')}}"><i class="fa fa-edit"></i> {{ __('to_do.to_do')}}</label>
                        </li>
                        @endif
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
@include('backend.todo.to_do_list')

