@extends('backend.partials.master')
@section('title')
   {{ __('user.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.user_role')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('user.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('users.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-xl-3 col-md-4 col-sm-6" >
                                <label for="name">{{ __('levels.name') }}</label>
                                <input type="text" id="name" name="name" placeholder="{{ __('levels.user') }} {{ __('levels.name') }}"  class="form-control" value="{{old('name', $request->name)}}">
                                @error('name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-md-4 col-sm-6" >
                                <label for="email">{{ __('levels.email') }}</label>
                                <input type="text" id="email" name="email" placeholder="{{ __('levels.user') }} {{ __('levels.email') }}"  class="form-control" value="{{old('email', $request->email)}}">
                                @error('email')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-md-4 col-sm-6">
                                <label for="phone">{{ __('levels.phone')}}</label> <span class="text-danger"></span>
                                <input type="text" id="phone" name="phone" placeholder="{{ __('levels.phone') }}"  class="form-control" value="{{old('phone', $request->phone)}}">
                                @error('phone')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-md-4 col-sm-6 pt-1">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 pl-0 d-flex justify-content">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('users.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('user.title') }}</p>
                    </div>
                    @if(hasPermission('user_create') == true )
                    <div class="col-6">
                        <a href="{{route('users.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.details') }}</th>
                                    @if(!isSuperadmin())
                                    <th>{{ __('levels.hub') }}</th>
                                    @endif
                                    <th>{{ __('levels.role') }}</th>
                                    <th>{{ __('permissions.permissions') }}</th>
                                    <th>{{ __('levels.salary') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(
                                        hasPermission('permission_update') == true ||
                                        hasPermission('user_update') == true       ||
                                        hasPermission('user_delete') == true
                                    )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($users as $user)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="row">
                                            <div class="pr-3">
                                                <img src="{{$user->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div>
                                                <strong>{{$user->name}}</strong>
                                                <p>{{$user->email}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    @if(!isSuperadmin())
                                    <td>{{@$user->hub->name}}</td>
                                    @endif
                                    <td>{{@$user->role->name}}</td>
                                    <td>
                                        @if(!empty($user->permissions) )
                                        <label class="badge badge-primary">{{ count($user->permissions) }}</label>
                                        @endif
                                    </td>
                                    <td>{{@$user->salary}}</td>
                                    <td>{!! $user->my_status !!}</td>
                                    @if(
                                        hasPermission('permission_update') == true ||
                                        hasPermission('user_update') == true       ||
                                        hasPermission('user_delete') == true
                                    )
                                        <td>
                                            <div class="row">
                                                <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    @if( hasPermission('permission_update') == true )
                                                        <a href="{{route('users.permission',$user->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('permissions.permissions') }}</a>
                                                    @endif
                                                    @if( hasPermission('user_update') == true  )
                                                        <a href="{{route('users.edit',$user->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                    @endif
                                                    @if( hasPermission('user_delete') == true )
                                                        @if($user->id != 1 && $user->company_owner != App\Enums\BooleanStatus::YES)
                                                            <form id="delete" value="Test" action="{{route('user.delete',$user->id)}}" method="POST" data-title="{{ __('delete.user') }}">
                                                                @method('DELETE')
                                                                @csrf
                                                                <input type="hidden" name="" value="User" id="deleteTitle">
                                                                <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $users->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $users->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $users->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $users->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div> 
@endsection