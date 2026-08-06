@extends('backend.partials.master')
@section('title')
   {{ __('menus.addons') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('addon.title') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-5  col-lg-12 col-sm-12 col-12">
            <div class="row  ">
                <div class="col-12  ">
                    <div class="card">
                        <div class="card-body">
                            <p class="h3">{{ __('addon.title') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-horizontal" action="{{ route('addons.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-from-label" for="purchase_code">{{ __('addon.purchase_code')}}</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="purchase_code" name="purchase_code" class="form-control" autocomplete="off" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-from-label" for="addon_zip">{{ __('addon.zip_file')}}</label>
                                        <div class="col-sm-9">
                                            <div class="custom-file">
                                                <input id="addon_zip" type="file" name="addon_zip" data-parsley-trigger="change"  class="form-control" require>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0 text-right">
                                        <button type="submit" class="btn btn-primary">{{__('addon.install_update')}}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7 col-lg-12 col-sm-12 col-12">
            <div class="row">
                <div class="col-md-12 col-md-offset-3  ">
                    <div class="card">
                        <div class=" card-body panel panel-default credit-card-box">
                            <p class="h3">{{ __('addon.title') }} {{ __('levels.list') }}</p>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table " style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>{{ __('levels.id') }}</th>
                                            <th>{{ __('addon.image') }}</th>
                                            <th>{{ __('addon.name') }}</th>
                                            <th>{{ __('addon.version') }}</th>
                                            <th>{{ __('addon.purchase_code') }}</th>
                                            <th>{{ __('addon.status') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(blank($addons))
                                        @php $i=1; @endphp
                                        @foreach($addons as $addon)
                                            <tr>
                                                <td>{{$i++}}</td>
                                                <td>
                                                    <div class="row">
                                                        <div class="pr-3">
                                                            <img src="{{ $addon->image}}" alt="user" class="rounded" width="40" height="40">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td> {{ $addon->name }} </td>
                                                <td> {{ $addon->version }} </td>
                                                <td> {{ $addon->purchase_code }} </td>
                                                <td>   <label class="aiz-switch mb-0">
                                                        <input type="checkbox" onchange="updateStatus(this, {{ $addon->id }})" <?php if($addon->activated) echo "checked";?>>
                                                        <span></span>
                                                    </label>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
<!-- css  -->
@push('styles')

@endpush
<!-- js  -->
@push('scripts')
    <script type="text/javascript">
        function updateStatus(el, id){
            if($(el).is(':checked')){
                var status = 1;
            }
            else{
                var status = 0;
            }
            $.post('{{ route('addons.activation') }}', {_token:'{{ csrf_token() }}', id:id, status:status}, function(data){
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
                if(data == 1){
                    Toast.fire({
                        icon: 'success',
                        title: 'Status updated successfully'
                    })
                }
                else{
                    Toast.fire({
                        icon: 'error',
                        title: 'Something went wrong'
                    })
                }
            });
        }

    </script>
@endpush


