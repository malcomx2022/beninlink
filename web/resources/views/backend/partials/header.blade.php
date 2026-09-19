<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(app()->getLocale() == 'ar') dir="rtl"@endif>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ settings()->favicon_image }}" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/bootstrap-five/bootstrap.min.css">
    {{-- Bootstrap 4, servi par le dépôt — étape A de la migration vers 5.
         Le socle le prenait sur `maxcdn.bootstrapcdn.com` en **4.1.1**, alors que
         `footer.blade.php` sert le JS de la copie LOCALE, en **4.1.0** : la feuille
         et le script n'étaient donc même pas de la même version. La copie locale
         les réaligne, et retire un CDN tiers de chaque page du back-office.
         Voir docs/guides/bootstrap/migration-4-vers-5.md --}}
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/bootstrap/css/bootstrap.min.css">
    {{-- `Circular Std` est sous licence PROPRIÉTAIRE et embarquée dans le socle.
         La charte l'a remplacée par DM Sans / Sora dès le lot 1 : la charger
         revenait à télécharger 348 Ko de fontes que rien ne peint, et à
         redistribuer une police sous licence. Les fichiers restent dans
         `public/backend/vendor/fonts/` — la règle du projet est « 0 fichier
         supprimé du socle » — ils ne sont simplement plus servis. --}}
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
    {{-- Charte BeninLink — chargée en DERNIER, après @stack('styles') : les pages
         y poussent des bibliothèques (logs.css, progressbar.css) qui portent
         encore le violet du socle. Voir public/beninlink/css/theme-backoffice.css. --}}
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/tokens.css') }}">
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/theme-backoffice.css') }}">
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/components.css') }}">
    {{-- L'ocre du transporteur (lot 3). Vide tant qu'il est resté sur la charte. --}}
    @include('beninlink.brand-accent')
    <title>@yield('title')</title>
</head>
<body >
    <!-- main wrapper -->
    <div class="dashboard-main-wrapper login-dashboard-main-wrapper">

