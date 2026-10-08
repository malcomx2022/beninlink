@extends('backend.partials.master')
@section('title', __('levels.category') . ' | ' . __('levels.add'))
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <h2 class="pageheader-title">{{ __('Add category') }}</h2>
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('category.create')}}" class="breadcrumb-link active">{{ __('Add category') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->

    <div class="row">
        <!-- basic form -->
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">{{ __('Add category') }}</h5>
                <div class="card-body">
                    <form action="{{route('category.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="form-group">
                            <label for="inputUserName">{{ __('levels.name') }}</label>
                            <input id="inputUserName" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="inputSlug">{{ __('levels.slug') }}</label>
                            <input type="text" name="slug" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_slug') }}" autocomplete="off" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="inputDescription">{{ __('levels.description') }}</label>
                            <textarea required="" class="form-control" name="description"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                    <button class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- end wrapper  -->
@endsection();
