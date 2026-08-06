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
            <div class="row align-items-start">
                <div class="col-lg-8">
                    <div class="contact-form mt-4">
                        <form action="{{ route('contact.message.send') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row ">
                                <div class="col-sm-6 mb-3">
                                  <label for="exampleFormControlInput1" class="form-label">{{ __('levels.name') }} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="{{ __('levels.enter_name') }}" name="name" value="{{ old('name') }}">
                                  @error('name')
                                  <p class="text-danger mt-2">{{ $message }}</p>
                                  @enderror
                                </div>
                                <div class="col-sm-6 mb-3">
                                  <label for="exampleFormControlInput1" class="form-label">{{ __('levels.email') }} <span class="text-danger">*</span></label>
                                  <input type="email" class="form-control" id="exampleFormControlInput1" name="email" placeholder="{{ __('levels.enter_email') }}" value="{{ old('email') }}">
                                  @error('email')
                                    <p class="text-danger mt-2">{{ $message }}</p>
                                  @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">{{ __('levels.subject') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="exampleFormControlInput1" name="subject" placeholder="{{ __('levels.enter_subject') }}"  value="{{ old('subject') }}">
                                @error('subject')
                                <p class="text-danger mt-2">{{ $message }}</p>
                                @enderror
                              </div>
                            <div class="mb-3">
                                <label for="exampleFormControlTextarea1" class="form-label">{{ __('levels.message') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="exampleFormControlTextarea1" name="message" rows="5" placeholder="{{ __('levels.enter_your_message') }}"> {{ old('message') }}</textarea>
                                @error('message')
                                <p class="text-danger mt-2">{{ $message }}</p>
                                @enderror 
                            </div>
                           <div class="mb-3">
                               <button type="submit" class="btn btn-primary">{{ __('levels.submit') }}</button>
                           </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4  ">
                    <h3 class="font-size-1-5rem display-6 font-weight-bold  my-4  mb-2">  {{ __('levels.address') }}:</h3>
                    <p class="mb-2"><i class="fa fa-envelope me-2 "></i>{{ __('levels.email') }} : {{ settings()->email }}</p>
                    <p class="mb-2"><i class="fa fa-phone me-2 "></i>{{ __('levels.phone') }} : {{ settings()->phone }}</p>
                    <p class="mb-2"><i class="fa fa-location-dot me-2"></i>{{ __('levels.address') }}: {{ settings()->address }}</p>
                </div>
            </div>
        </div> 
    </div>
</section>  
<section class="p-0">
    <iframe src="{{ section(\App\Enums\SectionType::MAP_LINK,'map_link') }}" width="100%" height="700px" allowfullscreen loading="lazy"  ></iframe>
</section>
@endsection