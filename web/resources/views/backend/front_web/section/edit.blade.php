@extends('backend.partials.master')
@section('title')
   {{ $section_type }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}" class="breadcrumb-link">{{ __('levels.pages') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ @$section_type }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
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
                    <h2 class="pageheader-title"> {{ @$section_type }} {{ __('levels.edit') }}</h2>
                    <form action="{{route('section.update',$type)}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('put')
                        @if ($type == App\Enums\SectionType::BANNER)
                            @include('backend.front_web.section.banner')
                        @elseif($type == App\Enums\SectionType::ACHIEVEMENT)
                            @include('backend.front_web.section.achievement')
                        @elseif($type == App\Enums\SectionType::ABOUT)
                            @include('backend.front_web.section.about')
                        @elseif($type == App\Enums\SectionType::SUBSCRIBE)
                            @include('backend.front_web.section.subscribe')
                        @elseif($type == App\Enums\SectionType::APP_LINK)
                            @include('backend.front_web.section.app_link')
                        @elseif($type == App\Enums\SectionType::MAP_LINK)
                            @include('backend.front_web.section.map_link')
                        @endif
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12  text-right">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.update') }}</button>
                                <a href="{{ route('section.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: '{{ __("placeholder.Enter_description")}}' ,
                height: 182
            });
        });
    </script>
@endpush
