@extends('backend.partials.master')
@section('title')
   {{ __('menus.profile') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('menus.profile') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- basic form -->
        <div class="col-12 text-right">
            <a href="{{route('profile.edit',$user->id)}}" class="btn btn-sm btn-primary mb-2"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-4">
             <div class="row">
                <div class="col-xl-12">
                    <div class="card mb-1 pb-5">
                        <div class="card-body">
                            <div class="text-center">
                                <img src="{{@$user->image}}" alt="user" class="img-responsive rounded-circle" width="100" >
                                <p class="mt-2 mb-0"><strong>{{@$user->name}}</strong></p>
                                <p class=" mb-0">{{@$user->mobile}}</p>
                                <p>{{@$user->address}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="list-group mt-3 mb-3">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.joining_date') }} : </span>
                                <span>{{ @dateFormat($user->joining_date) }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.salary') }} : </span>
                                <span>{{ @settings()->currency }} {{ @$user->salary }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.status') }} : </span>
                                @if(@$user->status == 1)
                                    <span class="badge badge-pill badge-success">{{ __('levels.active') }}</span>
                                @else
                                    <span class="badge badge-pill badge-danger">{{ __('levels.inactive') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
             </div>
        </div>
        <div class="col-xl-8 col-lg-8 col-md-8">
            <div class="row">
                <div class="col-xl-12">
                    <div class="list-group">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.name') }} : </span>
                                <span>{{ @$user->name }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.email') }} : </span>
                                <span>{{ @$user->email }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.phone') }} : </span>
                                <span>{{ @$user->mobile }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.nid') }} : </span>
                                <span>{{ @$user->nid_number }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.address') }} : </span>
                                <span>{{ @$user->address }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-12 col-md-12">
                    <div class="list-group mt-3">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.role') }} : </span>
                                <span>{{ @$user->role->name }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.hub') }} : </span>
                                <span>{{ @$user->hub->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-12 col-md-12">
                    <div class="list-group mt-3">
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.department') }} : </span>
                                <span>{{ @$user->department->title }}</span>
                            </div>
                        </div>
                        <div class="list-group-item ">
                            <div class="d-flex">
                                <span class="w-25">{{ __('levels.designation') }} : </span>
                                <span>{{ @$user->designation->title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
             </div>
        </div>
        <!-- end basic form -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
