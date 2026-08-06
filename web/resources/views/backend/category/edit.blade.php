@extends('backend.partials.master')
@section('title','Category | Edit')
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <h2 class="pageheader-title">Category Edit</h2>
                <p class="pageheader-text">Proin placerat ante duiullam scelerisque a velit ac porta, fusce sit amet vestibulum mi. Morbi lobortis pulvinar quam.</p>
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{route('category.create')}}" class="breadcrumb-link active">Categrys Edit</a></li>
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
                <h5 class="card-header">Categrys Edit</h5>
                <div class="card-body">
                    <form action="{{route('category.update')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <input type ="hidden" name="id" value="{{isset($editCatagory)? $editCatagory->id:old('id')}}">
                        <div class="form-group">
                            <label for="inputUserName">Name</label>
                            <input id="inputUserName" type="text" name="name" class="form-control" value="{{isset($editCatagory)? $editCatagory->name:old('name')}}">
                        </div>
                        <div class="form-group">
                            <label for="inputDescription">Description</label>
                            <div class="col-12 col-sm-8 col-lg-12">
                                <textarea required="" class="form-control" name="description" value="{{isset($editCatagory)? $editCatagory->name:old('description')}}"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 pl-0">
                                <p class="text-right">
                                    <button type="submit" class="btn btn-space btn-primary">Submit</button>
                                    <button class="btn btn-space btn-secondary">Cancel</button>
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end basic form -->
    </div>
</div>

<!-- end wrapper  -->
@endsection();

