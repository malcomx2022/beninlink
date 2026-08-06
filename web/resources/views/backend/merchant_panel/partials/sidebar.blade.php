<!-- left sidebar -->
<div class="col-12 nav-left-sidebar sidebar-dark">
    <ul class="navbar-nav">
        <li class="nav-divider">
            {{ __('menus.menu') }}
        </li>
        <li class="nav-item ">
            <a class="nav-link {{ request()->is('/*') ? 'active' : '' }}" href="{{ url('/dashboard') }}"><i
                    class="fa fa-home"></i>{{ __('dashboard.title') }}</a>
        </li>

        <li class="nav-item ">
            <a class="nav-link {{ request()->is('merchant/support*') ? 'active' : '' }}"
                href="{{ route('merchant-panel.support.index') }}"><i
                    class="fa fa-comments"></i>{{ __('menus.support') }}</a>
        </li>

        <li class="nav-item ">
            <a class="nav-link {{ (request()->is('merchant/my-wallet*')) ? 'active' : '' }}" href="{{route('merchant-panel.my.wallet.index')}}"><i class="fa fa-wallet"></i>{{ __('parcel.my_wallet') }}</a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('merchant/payment-request*', 'merchant/invoice*', 'merchant/payment/received*', 'merchant/online-payment*', 'merchant/invoice*') ? 'active' : '' }}"
                href="#" data-toggle="collapse" aria-expanded="false" data-target="#accounts"
                aria-controls="accounts"><i class="fa fa-users"></i> {{ __('account.title') }}</a>
            <div id="accounts"
                class="{{ request()->is('merchant/payment-request*', 'merchant/invoice*', 'merchant/payment/received*', 'merchant/online-payment*', 'merchant/invoice*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">

                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/payment/received*') ? 'active' : '' }}"
                            href="{{ route('online.payment.received') }}"> {{ __('menus.payments_received') }}</a>
                    </li>
                    {{-- payout --}}
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/online-payment*') ? 'active' : '' }}"
                            href="{{ route('online.payment.index') }}"> {{ __('menus.payout') }}</a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/invoice*') ? 'active' : '' }}"
                            href="{{ route('merchant.panel.invoice.index') }}">{{ __('menus.invoice') }}</a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item ">
            <a class="nav-link {{ request()->is('merchant/parcel/*') ? 'active' : '' }}"
                href="{{ route('merchant-panel.parcel.index') }}"><i
                    class="fa fa-dolly"></i>{{ __('menus.parcel') }}</a>
        </li>
        <li class="nav-item ">
            <a class="nav-link {{ request()->is('merchant/parcel-bank*') ? 'active' : '' }}"
                href="{{ route('merchant-panel.parcel-bank.index') }}"><i
                    class="fa fa-map"></i>{{ __('menus.parcel_bank') }}</a>
        </li>


        <li class="nav-item">
            <a class="nav-link {{ request()->is('merchant/reports/*') ? 'active' : '' }}" href="#"
                data-toggle="collapse" aria-expanded="false" data-target="#reports" aria-controls="reports"><i
                    class="fas fa-print"></i>{{ __('reports.title') }}</a>
            <div id="reports"
                class="{{ request()->is('merchant/reports*', 'merchant/accounts/statements*', 'merchant/accounts/account-transaction*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/reports/parcel-reports*', 'merchant/reports/parcel-filter-reports') ? 'active' : '' }}"
                            href="{{ route('merchant-panel.parcel.reports') }}" aria-expanded="false"
                            data-target="#submenu-1" aria-controls="submenu-1">{{ __('reports.parcel_reports') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/reports/total-summery*', 'merchant/reports/total-summery-filter*') ? 'active' : '' }}"
                            href="{{ route('merchant.total.summery') }}">{{ __('menus.parcel_total_summery') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/accounts/account-transaction*') ? 'active' : '' }}"
                            href="{{ route('merchant.accounts.account-transaction.index') }}">{{ __('menus.account_transaction') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/accounts/statements*') ? 'active' : '' }}"
                            href="{{ route('merchant.accounts.statements.index') }}">{{ __('menus.statements') }}</a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('merchant/settings*', 'merchant/shops*') ? 'active' : '' }}"
                href="#" data-toggle="collapse" aria-expanded="false" data-target="#settings"
                aria-controls="settings"><i class="fa fa-fw fa-cogs"></i> {{ __('menus.settings') }}</a>
            <div id="settings"
                class="{{ request()->is('merchant/settings*', 'merchant/shops*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/settings/cod-charges*') ? 'active' : '' }}"
                            href="{{ route('merchant.cod-charges.index') }}">{{ __('menus.cod_charges') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/settings/delivery-charges*') ? 'active' : ' ' }}"
                            href="{{ route('merchant.delivery-charges.index') }}">{{ __('menus.delivery_charges') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/settings/online-payment-setup*') ? 'active' : ' ' }}"
                            href="{{ route('merchant.online.payment.setup.index') }}">{{ __('menus.online_payment_setup') }}</a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/shops*') ? 'active' : '' }}"
                            href="{{ route('merchant-panel.shops.index') }}"> {{ __('parcel.shop') }}</a>
                    </li>

                </ul>
            </div>
        </li>


    </ul>
</div>
<!-- end left sidebar -->
