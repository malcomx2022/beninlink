@include('backend.partials.header')
@if(isSuperadmin())
    @include('backend.super-admin.partials.navber')
    @include('backend.super-admin.partials.sidebar')
@elseif (Auth::user()->user_type == \App\Enums\UserType::MERCHANT)
    @include('backend.merchant_panel.partials.navber')
    @include('backend.merchant_panel.partials.sidebar')
@else
    @include('backend.partials.navber')
    @include('backend.partials.sidebar')
@endif
    <main class="dashboard-ecommerce">
      <div class="main-content">
        @yield('maincontent')
@include('backend.partials.dynamic-modal')
@include('backend.partials.footer_text')
@include('backend.partials.footer')
