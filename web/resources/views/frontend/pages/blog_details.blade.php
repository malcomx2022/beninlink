@extends('frontend.layouts.master')
@section('title')
    {{ @$blog->title }} | {{ settings()->name}}
@endsection
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 ">  
        <div class="row">
            <div class="col-xl-8">
                <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ @$blog->title }} </h3>
                <div class="d-flex justify-content-start align-items-center my-3">
                    <p class="card-text mb-0 me-3">
                        <i class="fa fa-user me-2"></i><small class="text-body-secondary">{{ $blog->user->name }}</small>
                    </p>
                    <p class="card-text mb-0">
                        <i class="fa fa-eye me-2"></i><small class="text-body-secondary me-2">{{ $blog->views}}</small>
                        <i class="fa fa-calendar me-2"></i><small class="text-body-secondary">{{ $blog->updated_at->format('d M Y h:i A')}}</small>
                    </p>
                </div> 
                <div class="mb-3">
                    <img src="{{ $blog->image }}" class="card-img-top" alt="{{ $blog->title }}"> 
                </div> 
                <div class="page-content">
                    {!! $blog->description !!}
                </div>
            </div>
            <div class="col-xl-4 mt-5 pt-5 latest-blog"> 
                <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ __('levels.latest_blogs') }}</h3>
                @foreach ($latest_blogs as $latest_blog) 
                    <div class="card mt-3 mb-3 latest-blog-item"  >
                        <div class="row g-0">
                        <div class="col-4" >
                           <a href="{{ route('blog.details',$latest_blog->id) }}"><img src="{{ $latest_blog->image }}" class="img-fluid rounded-start" alt="{{ $latest_blog->title }}"></a> 
                        </div>
                        <div class="col-8" > 
                            <div class="card-body py-0 ">
                                <a class="text-decoration-none" href="{{ route('blog.details',$latest_blog->id) }}"><h5 class="card-title mt-2"> {{\Str::limit($latest_blog->title,25,' ...')}}</h5></a> 
                                <div class="row justify-content-start align-items-center my-2">
                                    <div class="col-sm-6"> 
                                        <p class="card-text mb-0 me-3">
                                            <i class="fa fa-user me-2"></i><small class="text-body-secondary">{{ $latest_blog->user->name }}</small>
                                        </p>
                                    </div>
                                    <div class="col-sm-6"> 
                                        <p class="card-text mb-0">
                                            <i class="fa fa-calendar me-2"></i><small class="text-body-secondary">{{ $latest_blog->updated_at->format('d M Y')}}</small>
                                        </p>
                                    </div>
                                </div> 
                            </div>
                        </div>
                        </div>
                    </div>
                @endforeach 

            </div>
        </div>
    </div>
</section>  
@endsection