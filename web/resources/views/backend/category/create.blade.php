@extends('backend.partials.master')
@section('title','Category | Add')
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <h2 class="pageheader-title">Category Add</h2>
                <p class="pageheader-text">Proin placerat ante duiullam scelerisque a velit ac porta, fusce sit amet vestibulum mi. Morbi lobortis pulvinar quam.</p>
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{route('category.create')}}" class="breadcrumb-link active">Categrys Add</a></li>
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
                <h5 class="card-header">Categrys Add</h5>
                <div class="card-body">
                    <form action="{{route('category.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="form-group">
                            <label for="inputUserName">Name</label>
                            <input id="inputUserName" type="text" name="name" data-parsley-trigger="change" placeholder="Enter name" autocomplete="off" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="inputSlug">Slug</label>
                            <input type="text" name="slug" data-parsley-trigger="change" placeholder="Enter slug" autocomplete="off" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="inputDescription">Description</label>
                            <textarea required="" class="form-control" name="description"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                    <button type="submit" class="btn btn-space btn-primary">Save</button>
                                    <button class="btn btn-space btn-secondary">Cancel</button>
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
