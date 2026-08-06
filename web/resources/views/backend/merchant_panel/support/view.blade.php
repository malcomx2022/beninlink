@extends('backend.partials.master')
@section('title')
    {{ __('support.supprot') }} {{ __('levels.view') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-md-12 col-sm-12 col-12 m-auto" >
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant-panel.support.index')}}" class="breadcrumb-link">{{ __('support.supprot_list') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12"  >
            <div class="card" style="margin-bottom: 10px;">
                <div class="row p-4  ">
                    <div class="col-12">
                        <p class="h4">{{ $singleSupport->subject }}</p>

                    </div>
                </div>
            </div>
        </div>
        <div class=" col-xl-4 " >
            <div class="card">
                <div class="card-body" style="padding:5px!important;">
                    <div class="row p-4">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-4">{{ __('support.user_name') }}</div>
                                <div class="col-8">: {{ $singleSupport->user->name }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4">{{ __('support.user_email') }}</div>
                                <div class="col-8">: {{ $singleSupport->user->email }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4">{{ __('support.service') }}</div>
                                <div class="col-8">: {{ $singleSupport->service }}</div>
                            </div>
                            <div class="row">
                                    <div class="col-4">{{ __('support.department_id') }}</div>
                                    <div class="col-8">: {{$singleSupport->department->title}}</div>
                            </div>
                            <div class="row">
                                    <div class="col-4">{{ __('support.priority') }}</div>
                                    <div class="col-8">: {{ $singleSupport->priority }}</div>
                            </div>
                            <div class="row">
                                    <div class="col-4">{{ __('support.date') }}</div>
                                    <div class="col-8">: {{ dateFormat($singleSupport->date) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8  ">
            <div id="accordion">
                <div class="card">
                    <div class="card-header" id="headingOne">
                        <a href="#" class="text-primary" data-toggle="collapse" data-target="#collapseOne" aria-expanded="@if($errors->has('message')) true @else false @endif" aria-controls="collapseOne"><i class="fa fa-reply m-2 "></i>{{ __('support.reply') }}</a>
                    </div>
                    {{-- reply form --}}
                    <div id="collapseOne" class="collapse @error('message') show @enderror " aria-labelledby="headingOne" data-parent="#accordion">
                    <div class="card-body">
                        <form action="{{ route('merchant-panel.support.reply',['support_id'=>$singleSupport->id]) }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group">
                                <label for="message">{{ __('support.message')}} <span class="text-danger">*</span></label>
                                <div class="form-control-wrap user-search">
                                    <textarea class="form-control @error('message') is-invalid @enderror" name="message" rows="5" id="message" placeholder="Enter message" value="{{ old('message') }}"></textarea>
                                </div>
                                @error('message')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="image">{{ __('support.attached_file') }}</label>
                                        <input id="attached_file" type="file" name="attached_file" data-parsley-trigger="change" autocomplete="off" class="form-control" value="{{ old('attached_file ') }}" require>

                                        @error('attached_file')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                    <button type="submit" class="btn btn-space btn-primary float-right">{{ __('levels.send') }}</button>
                                </div>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
            @foreach($chats as $chat)
                <div class="card">
                    <div class="row p-4  ">
                        <div class="col-12">
                            <div class="row align-item-center" style="align-items: center">
                                <div class="col-12">
                                    <div class="d-flex">
                                        <img src="{{@$chat->user->image}}" alt="user" class="rounded" width="40" height="40">
                                        <span style="margin-left:10px;width:100%">
                                            <strong>{{@$chat->user->name}}</strong><br/>
                                            <span>{{@$chat->user->email}}</span><br/>
                                            <label class="badge badge-primary">
                                                @if ($chat->user->id == 1)
                                                    {{ __('userType.'.@$chat->user->user_type)  }}
                                                @elseif ($chat->user->user_type == \App\Enums\UserType::MERCHANT)
                                                    {{ __('userType.'. App\Enums\UserType::MERCHANT)  }}
                                                @else
                                                    {{ __('user.title')  }}
                                                @endif
                                            </label>
                                            <small class="float-right">  {{ \Carbon\Carbon::parse()->format('d M Y h:i A')}}</small>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" style="padding-left: 0px">
                                {!! $chat->message !!}
                            </div>
                            @if(@$chat->file)
                            <div class="row" >
                                <div class="col-12 d-flex"style="align-items: center">
                                    <i class="fa fa-file" style="font-size: 30px"></i>
                                    <a href="{{ static_asset(@$chat->file->original) }}" download="" class="d-flex" style="align-items: center">
                                        <span  style="padding:10px">Download File</span>
                                    </a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="card">
                <div class="row p-4  ">
                    <div class="col-12">
                        <div class="row align-item-center" style="align-items: center">
                            <div class="col-12">
                                <div class="d-flex">
                                    <img src="{{@$singleSupport->user->image}}" alt="user" class="rounded" width="40" height="40">
                                    <span style="margin-left:10px;width:100%">
                                        <strong>{{@$singleSupport->user->name}}</strong><br/>
                                        <span>{{@$singleSupport->user->email}}</span><br/>
                                        <label class="badge badge-primary">{{ __('UserType.'.@$singleSupport->user->user_type)  }}</label>
                                        <small class="float-right">  {{ \Carbon\Carbon::parse($singleSupport->created_at)->format('d M Y h:i A')}}</small>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="padding-left: 0px">
                            {!! $singleSupport->description !!}
                        </div>
                        {{-- download file --}}
                        @if(@$singleSupport->file)
                            <div class="row" >
                                <div class="col-12 d-flex"style="align-items: center">
                                    <i class="fa fa-file" style="font-size: 30px"></i>
                                    <a href="{{ static_asset(@$singleSupport->file->original) }}" download="" class="d-flex" style="align-items: center">
                                        <span  style="padding:10px">Download File</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
@push('styles')
    <link href="{{ static_asset('backend/vendor/summernote/css') }}/summernote-lite.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ static_asset('backend/vendor/summernote/js') }}/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#message').summernote({
                placeholder: '{{ __('placeholder.Enter_description') }}',
                height: 182
            });
        });
    </script>
@endpush