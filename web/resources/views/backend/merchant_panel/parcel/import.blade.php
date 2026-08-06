@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }} {{ __('levels.import') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.parcel.index') }}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('parcel.import') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('parcel.parcel_import') }}</p>
                    </div>
                    <div class="col-6">
                        <a href="{{static_asset('sample-parcel/merchantParcel/import-parcel.xlsx')}}" download class="btn btn-success btn-sm float-right" data-toggle="tooltip" data-placement="top" title="download">{{ __('parcel.sample') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="col-md-10 mb-5">
                                <p>{{ __('merchantImport.note') }}</p>
                                <ul class="list list-sm list-success">
                                    <li>{{ __('merchantImport.01') }}</li>
                                    <li>{{ __('merchantImport.02') }}</li>
                                    <li>{{ __('merchantImport.03') }}</li>
                                    <li>{{ __('merchantImport.04') }}: @foreach($deliveryCategories as $category) @if($loop->last){{ $category->id }}={{ $category->title }} @else {{ $category->id }}={{ $category->title }},@endif  @endforeach</li>
                                    <li>{{ __('merchantImport.05') }}: @foreach(trans('deliveryType') as $key => $status) @if($loop->last){{ $key }}={{ $status }} @else {{ $key}}={{ $status }},@endif  @endforeach</li>
                                    <li>{{ __('merchantImport.06') }}</li>
                                    <li class="list-style-none mt-2">
                                        <form action="{{ route('merchant-panel.parcel.file-import') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group ">
                                                        <div class="custom-file text-left">
                                                            <input type="file" name="file" class="form-control" id="customFile">
                                                        </div>
                                                        @error('file')
                                                            <div class="text-danger ">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button class="btn btn-primary form-control">{{ __('parcel.import') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            @if(session()->has('importErrors'))
                                <h2 class="text-center border-bottom">{{__('parcel.validation_log')}}</h2>
                                @foreach(session()->get('importErrors') as $key => $values)
                                    <div class="text-primary ">{{__('parcel.in_row_number')}} : {{ $key }}</div>
                                    @foreach($values as $value)
                                        <div class="text-danger ">{{ $value }}</div>
                                    @endforeach
                                @endforeach
                            @endif
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
<!-- js  -->
@push('scripts')
    <script src="{{ static_asset('backend/js/custom.js') }}"></script>
@endpush


