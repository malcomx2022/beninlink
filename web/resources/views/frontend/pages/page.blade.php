@extends('frontend.layouts.master')
@section('title')
    {{ @$page->title }} | {{ settings()->name}}
@endsection
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 "> 
        <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ @$page->title }} </h3>
        <div class="page-content">
            {!! $page->description !!}
        </div>
    </div>
</section>  
@endsection