<!doctype html>
<html lang="en" @if(app()->getLocale() == 'ar') dir="rtl"@endif>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,  minimum-scale=0.8, maximum-scale = 0.8, user-scalable = no , shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ settings()->favicon_image }}" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/bootstrap-five/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link href="{{static_asset('backend')}}/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/fontawesome/css/fontawesome-all.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/charts/chartist-bundle/chartist.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/charts/morris-bundle/morris.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/material-design-iconic-font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/charts/c3charts/c3.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/flag-icon-css/flag-icon.min.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/datepicker.min.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/custom.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.5.1/css/flag-icons.min.css" /> 
    <link rel="stylesheet" href="{{ static_asset('backend/vendor') }}/toastr/toastr.min.css">
    <!-- push target to head -->
    @stack('styles')
    <title>@yield('title')</title>
</head>
<body >
    <!-- main wrapper -->
    <div class="dashboard-main-wrapper login-dashboard-main-wrapper">

