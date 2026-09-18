<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(app()->getLocale() == 'ar') dir="rtl"@endif>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ settings()->favicon_image }}" type="image/x-icon">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ static_asset('frontend/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ static_asset('frontend/css/style.css') }}"/> 
    <link rel="stylesheet" href="{{ static_asset('frontend/css/odometer.css') }}"/> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"   /> 
    <link rel="stylesheet" href="{{ static_asset('frontend/css/swiper-bundle.min.css') }}"/>
    <link rel="stylesheet" href="{{ static_asset('backend/vendor') }}/toastr/toastr.min.css">
    @stack('styles') 
    {{-- Charte BeninLink. Sora et DM Sans sont auto-hébergées par tokens.css :
         le <link> Google Fonts d'origine a été retiré — il chargeait Bitter, que
         rien n'utilisait, et Roboto, que la charte remplace.
         Ces deux feuilles passent AVANT le bloc ci-dessous : la couleur choisie
         par le transporteur garde le dernier mot (multi-tenant). --}}
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/tokens.css') }}">
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/theme-frontend.css') }}">
    <style>
        :root{
            --bs-white: {{ settings()->text_color }}; 
            --bs-primary:{{ settings()->primary_color }};
        }
    </style>
</head>
<body>   
    @include('frontend.layouts.navbar')
    @yield('content') 
    @include('frontend.layouts.footer')
    <!-- scripts -->
    <script src="{{ static_asset('frontend/js/jquery.min.js') }}" ></script>
    <script src="{{ static_asset('frontend/js/bootstrap.bundle.min.js') }}" ></script> 
    <script src="{{ static_asset('frontend/js/swiper-bundle.min.js') }}" ></script>
    <script src="{{ static_asset('frontend/js/jquery.odometer.min.js') }}" ></script>
    <script src="{{ static_asset('frontend/js/theme.js') }}" ></script> 
    <script src="{{ static_asset('backend/vendor') }}/toastr/toastr.min.js"></script> 
    {!! Toastr::message() !!}
   
</body>
</html>
