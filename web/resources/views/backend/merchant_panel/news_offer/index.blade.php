@extends('backend.partials.master')
@section('title')
    {{ __('news_offer.title') }}  {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content mt-5">
    <div class="col-12 p-0 offset-md-2 col-md-8 ">
        <!-- card  -->
        <div class="card mt-3">
            <div class="card-header">
                <p class="h3">{{ __('news_offer.title') }}</p>
            </div>
        </div>
        <!-- end card  -->
        @foreach($news_offers as $news_offer)
            <!-- card  -->
            <div class="card">
                <div class="card-header " style="line-height: 2">
                    <strong class="font-20">{{ $news_offer->title }}</strong><br>
                    {{ __('levels.author') }}: {{$news_offer->user->name}} | {{ __('levels.date') }}: {{dateFormat($news_offer->date) }}
                </div>
                <div class="card-body">
                    @if ($news_offer->upload->original != "")
                        <img src="{{$news_offer->image}}" class="pb-3" alt="Image" width="100%" height="350">
                    @endif
                    <div>
                        {!! $news_offer->description !!}
                    </div>
                </div>
            </div>
            <!-- end card  -->
        @endforeach
    </div>
</div>
<!-- end wrapper  -->
@endsection