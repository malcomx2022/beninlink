@extends('frontend.layouts.master')
@section('title')
   {{ __('levels.blogs') }}| {{ settings()->name}}
@endsection
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 "> 
        <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ __('levels.blogs') }} </h3>
        <div class="page-content blogs">
                <div class="row row-cols-1 row-cols-md-3 g-4 blogs">
                    @foreach ($blogs as $blog)      
                        <div class="col-lg-4">
                        <div class="card h-100"  >
                            <a href="{{ route('blog.details',$blog->id) }}">
                                <img src="{{ $blog->image }}" class="card-img-top" alt="{{ $blog->title }}">
                            </a>
                            <div class="card-body">
                                <a href="{{ route('blog.details',$blog->id) }}" class="text-decoration-none"><h4 class="card-title">{{ $blog->title }}</h4></a> 
                            </div>
                            <div class="card-footer pb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="card-text mb-0">
                                        <i class="fa fa-user me-2"></i><small class="text-body-secondary">{{ $blog->user->name }}</small>
                                    </p>
                                    <p class="card-text mb-0">
                                        <i class="fa fa-eye me-2"></i><small class="text-body-secondary me-2">{{ $blog->views}}</small>
                                        <i class="fa fa-calendar me-2"></i><small class="text-body-secondary">{{ $blog->updated_at->format('d M Y h:i A')}}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        </div> 
                    @endforeach 
              </div>
              <div class="mt-3 text-center"> 
                   {{ $blogs->links() }}
              </div>
        </div>
    </div>
</section>  
@endsection