@extends('frontend.layouts.master')
@section('title')
    {{ __('levels.home') }} | {{ @settings()->name }}
@endsection
@section('content')  
    @include('frontend.section.banner')
    @if (tenant())
        @include('frontend.section.service')
    @endif
    @include('frontend.section.why_courier')
    @if (tenant())
        @include('frontend.section.pricing')
    @else
        @include('frontend.section.plans')
    @endif
    @include('frontend.section.achievement')
    @include('frontend.section.partner')
    @include('frontend.section.blogs')
@endsection