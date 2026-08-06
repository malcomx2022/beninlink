
<section class="container-fluid py-3 pb-0">
    <div class="mb-5">
        <div class=" mb-3"> 
            <h3 class="display-6 title text-center mb-5">
                <span class="section-title">{{ __('levels.blogs') }}</span>
            </h3> 
        </div> 
        <div class="container partner py-5">
            <div class="row row-cols-1 row-cols-md-3 g-4 blogs">
                 
                @foreach ($blogs as $key=>$blog)  
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
                                        <i class="fa fa-calendar me-2"></i><small class="text-body-secondary">{{ $blog->updated_at->format('d M Y')}}</small>
                                    </p>
                                </div>
                            </div>
                        </div> 
                    </div>  
                @endforeach 
             </div>
        </div>
    </div>
</section>