@extends('backend.partials.master')
@section('title')
   {{ __('to_do.to_do_list') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('to_do.to_do_list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('to_do.to_do_list') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('to_do.sl') }}</th>
                                    <th>{{ __('to_do.date') }}</th>
                                    <th>{{ __('to_do.title') }}</th>
                                    <th>{{ __('to_do.description') }}</th>
                                    <th>{{ __('to_do.assign') }}</th>
                                    <th>{{ __('to_do.note') }}</th>
                                    <th>{{ __('to_do.status') }}</th>
                                    <th>{{ __('to_do.status_update') }}</th>
                                    <th>{{ __('to_do.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($todos as $todo)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td> {{dateFormat($todo->date)}}</td>
                                    <td> {{$todo->title}}</td>
                                    <td> {{\Str::limit($todo->description,100,' ...')}}</td>
                                    <td> {{$todo->user->name}}</td>
                                    <td> {{$todo->note}}</td>
                                    <td>
                                        {!! $todo->TodoStatus !!} <br>
                                        @if($todo->partial_delivered && $todo->status != \App\Enums\TodoStatus::PENDING)
                                            <span class="badge badge-pill badge-success mt-2">{{trans("to_do." . \App\Enums\todoStatus::PENDING)}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($todo->status == \App\Enums\TodoStatus::COMPLETED)
                                            ...
                                            @else
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend be-addon">
                                                <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    {!! TodoStatus($todo)  !!}
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-sm ml-2">...</button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('todo_update')== true)
                                                <a href="" class="dropdown-item" id="todoeditModal1" data-target="#todoeditModal{{$todo->id}}" data-toggle="modal"><i class="fa fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('todo_delete')== true)
                                                <form id="delete" value="Test" action="{{route('todo.delete',$todo->id)}}" method="POST" data-title="{{ __('delete.to_do') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="" value="{{ __('todo.delete') }}" id="deleteTitle">
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('to_do.delete') }}</button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @include('backend.todo.to_do_edit')
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $todos->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $todos->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $todos->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $todos->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
    @include('backend.todo.to_do_proccesing')
    @include('backend.todo.to_do_completed')
</div>

@endsection()

@push('scripts')
    <script>
        $(document).ready(function(){
            $('#todo_btn').click(function(){
                var id = $(this).data('id');
                  $(".modal_todo_id").attr("value",id);
            });
        });
    </script>
@endpush


