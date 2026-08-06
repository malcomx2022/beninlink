@extends('frontend.layouts.master')
@section('title')
    {{ @$page->title }} | {{ settings()->name}}
@endsection
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 "> 
        <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ @$page->title }} </h3>
        <p>   {!! $page->description !!}</p>
        <div class="page-content">
            <h3 class="font-size-1-5rem display-6 font-weight-bold text-start my-4  ">  {{ __('levels.read_our_commonly_asked_questions') }} </h3>
             
            {{-- faq content --}}

            <div class="faq accordion accordion-flush" id="accordionPanelsStayOpenExample">

                @foreach ($faqs as $key=>$faq)     
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-{{ $key }}" aria-expanded="false" aria-controls="panelsStayOpen-{{ $key }}">
                              {{ @$faq->question }}
                            </button>
                        </h2>
                    <div id="panelsStayOpen-{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#accordionPanelsStayOpenExample">
                        <div class="accordion-body"> 
                            {!! $faq->answer !!}
                        </div>
                    </div>
                    </div>
                @endforeach 
                <div class="mt-5">
                     {{ $faqs->links() }}
                </div>
              </div>

            {{-- faq content --}}
        </div>
    </div>
</section>  
@endsection