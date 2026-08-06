@extends('backend.partials.master')
@section('title')
   {{ __('levels.faq') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb"> 
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('levels.front_web')}}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('faq.index') }}" class="breadcrumb-link">{{ __('levels.faq') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.create') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="  col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('levels.faq') }} {{ __('levels.add') }}</h2>
                    <form action="{{route('faq.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            
                            <div class="form-group col-md-12">
                                <label for="question">{{ __('levels.question') }}</label> <span class="text-danger">*</span>
                                <input id="question" type="text" name="question" data-parsley-trigger="change" placeholder="{{ __('levels.Enter_question') }}" autocomplete="off" class="form-control @error('question') is-invalid @enderror" value="{{old('question')}}" require>
                                @error('question')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                          
                            <div class="form-group col-md-12"> 
                                <label for="summernote">{{ __('levels.answer') }} <span class="text-danger">*</span></label>
                                <textarea  class="form-control  @error('answer') is-invalid @enderror" name="answer" id="summernote" rows="12"></textarea>
                                @error('answer')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror 
                            </div>
 
                            <div class="form-group    col-md-6">
                                <label for="position">{{ __('levels.position') }}</label>
                                <input id="position" type="text" name="position" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_Position') }}" autocomplete="off" class="form-control @error('position') is-invalid @enderror" value="{{old('position')}}" >
                                @error('position')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group  col-md-6">
                                <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                <select name="status" class="form-control @error('status') is-invalid @enderror">
                                    @foreach(trans('status') as $key => $status)
                                        <option value="{{ $key }}" {{ (old('status',\App\Enums\Status::ACTIVE) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
 
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12  text-right">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('faq.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link href="{{ static_asset('backend/vendor/summernote/css') }}/summernote-lite.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ static_asset('backend/vendor/summernote/js') }}/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: '{{ __('placeholder.Enter_description') }}',
                height: 182
            });
        });
    </script>
@endpush