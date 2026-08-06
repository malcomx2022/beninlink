@extends('backend.partials.master')
@section('title')
    {{ __('support.supprot') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.support.index') }}" class="breadcrumb-link">{{ __('support.supprot') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- basic form -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('support.support_edit') }}</h2>
                    <form action="{{route('merchant-panel.support.update',['id'=>$singleSupport->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group  ">
                                    <label for="date">{{ __('support.date')}}</label> <span class="text-danger">*</span>
                                    <input type="date" name="date" class="form-control" value="{{old('date',$singleSupport->date)}}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="service">{{ __('support.service') }}</label> <span class="text-danger">*</span>
                                    <input id="service" type="text" name="service" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.service') }}" autocomplete="off" class="form-control" value="{{old('service',$singleSupport->service)}}" require>
                                    @error('service')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">{{ __('support.priority') }}</label> <span class="text-danger">*</span>
                                    <select name="priority" class="form-control @error('priority') is-invalid @enderror" >
                                        <option disabled selected>{{ __('merchantPlaceholder.priority') }}</option>
                                        <option value="low"{{(old('priority',$singleSupport->priority) == 'low') ? 'selected' : ''}}>Low</option>
                                        <option value="medium"{{(old('priority',$singleSupport->priority) == 'medium') ? 'selected' : ''}}>Medium</option>
                                        <option value="high"{{(old('priority',$singleSupport->priority) == 'high') ? 'selected' : ''}}>High</option>
                                    </select>
                                    @error('status')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="department_id">{{ __('support.department_id') }}</label>
                                    <select class="form-control p-1" id="department_id" name="department_id">
                                        <option disabled selected>{{ __('merchantPlaceholder.department') }}</option>
                                        @foreach($departments as $department)
                                            <option value="{{$department->id}}" {{(old('department_id',$singleSupport->department_id) == $department->id) ? 'selected' : ''}}>{{$department->title}}</option>

                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label for="subject">{{ __('support.subject') }}</label> <span class="text-danger">*</span>
                                    <input id="subject" type="text" name="subject" data-parsley-trigger="change" placeholder="{{ __('merchantPlaceholder.subject') }}" autocomplete="off" class="form-control" value="{{old('subject',$singleSupport->subject)}}" require>
                                    @error('subject')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label for="image">{{ __('support.attached') }}</label>
                                    <input id="attached_file" type="file" name="attached_file" data-parsley-trigger="change" autocomplete="off" class="form-control" value="{{ old('attached_file ') }}" require>
                                    @error('attached_file')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">{{ __('support.description')}}</label>
                            <div class="form-control-wrap user-search">
                                <textarea class="form-control" name="description" rows="5" id="description" placeholder="Enter Description">{{ $singleSupport->description }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('merchant-panel.support.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end basic form -->
    </div>
</div>
@endsection()

@push('styles')
    <link href="{{ static_asset('backend/vendor/summernote/css') }}/summernote-lite.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ static_asset('backend/vendor/summernote/js') }}/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#description').summernote({
                placeholder: '{{ __('placeholder.Enter_description') }}',
                height: 182
            });
        });
    </script>
@endpush