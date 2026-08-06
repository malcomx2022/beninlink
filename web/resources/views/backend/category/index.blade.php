@extends('backend.partials.master')
@section('title','Category | Index')
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <h2 class="pageheader-title">Category Manage</h2>
                <p class="pageheader-text">Proin placerat ante duiullam scelerisque a velit ac porta, fusce sit amet vestibulum mi. Morbi lobortis pulvinar quam.</p>
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{route('category.index')}}" class="breadcrumb-link active">Categrys</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    @if ($message = Session::get('success'))
      <div class="alert alert-success alert-block">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
      </div>
    @endif
    @if ($message = Session::get('danger'))
      <div class="alert alert-danger alert-block">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
      </div>
    @endif
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Data Tables - Print, Excel, CSV, PDF Buttons</h5>
                    <p>This example shows DataTables and the Buttons extension being used with the Bootstrap 4 framework providing the styling.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example" class="table    table-bordered second" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    @if ( hasPermission('category_update') || hasPermission('category_delete')  )
                                    <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($categorys as $category)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$category->name}}</td>
                                    <td>{{$category->description}}</td>
                                    @if ( hasPermission('category_update') || hasPermission('category_delete')  )
                                    <td>

                                       <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if ( hasPermission('category_update')  )
                                                <a href="{{route('category.edit',$category->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if ( hasPermission('category_delete')  )
                                                <form action="{{route('category.delete',$category->id)}}" method="POST" id="delete" data-title="{{ __('delete.category') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit"  class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
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
            </div>
        </div>
        <!-- end data table  -->
    </div>

</div>



<!-- end wrapper  --> 
@endsection();
<!-- Data table css  -->
@push('styles')
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/datatables/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/datatables/css/buttons.bootstrap4.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/datatables/css/buttons.bootstrap4.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/datatables/css/select.bootstrap4.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/datatables/css/fixedHeader.bootstrap4.css">

@endpush
<!-- Data table js  -->
@push('scripts')
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.0.4/js/dataTables.rowGroup.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.2.7/js/dataTables.select.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.1.5/js/dataTables.fixedHeader.min.js"></script>


    <script src="{{static_asset('backend')}}/vendor/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{static_asset('backend')}}/vendor/datatables/js/data-table.js"></script>
    <script src="{{static_asset('backend')}}/vendor/slimscroll/jquery.slimscroll.js"></script>
    <script src="{{static_asset('backend')}}/vendor/multi-select/js/jquery.multi-select.js"></script>
    <script src="{{static_asset('backend')}}/libs/js/main-js.js"></script>


    <script>
        function ConfirmDialog() {
            var x=confirm("Are you sure to delete this record?")
            if (x) {
                return true;
            } else {
                return false;
            }
        }
    </script>


@endpush

