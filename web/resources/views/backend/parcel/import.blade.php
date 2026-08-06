@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}    {{ __('levels.import') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('parcel.index') }}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('parcel.import') }}</a></li>
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
                    <div class="col-10">
                        <p class="h3">{{ __('parcel.title') }} {{ __('parcel.import') }}</p>
                    </div>
                    <div class="col-2">
                        <a href="{{static_asset('sample-parcel/parcel/import-parcel.xlsx')}}" download class="btn btn-success btn-sm float-right" data-toggle="tooltip" data-placement="top" title="download">{{ __('parcel.sample') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="col-md-10 mb-5">
                                <p>{{ __('levels.import_title') }}</p>
                                <ul class="list list-sm list-success">
                                    <li>{{ __('levels.import_title_2') }}</li>
                                    <li>{{ __('levels.import_title_3') }}</li>
                                    <li>{{ __('levels.category') }}: @foreach($deliveryCategories as $category) @if($loop->last){{ $category->id }}={{ $category->title }} @else {{ $category->id }}={{ $category->title }},@endif  @endforeach</li>
                                    <li>{{ __('menus.delivery_type') }}: @foreach(trans('deliveryType') as $key => $status) @if($loop->last){{ $key }}={{ $status }} @else {{ $key}}={{ $status }},@endif  @endforeach</li>
                                    <li>{{ __('parcel.cash_collection') }}=0.00 , {{ __('parcel.selling_price') }}=0.00 {{ __('levels.import_title_4') }}.</li>
                                    <li>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group importParcel">
                                                    <label for="merchant_id">{{ __('merchant.title') }}</label>:
                                                    <select id="merchant_id" class="form-control importMerchant @error('merchant_id') is-invalid @enderror">
                                                        <option value=""> {{ __('Check Merchant ID') }}</option>
                                                    </select>
                                                    @error('merchant_id')
                                                    <small class="text-danger mt-2">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <form action="{{ route('parcel.file-import') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <div class=" text-left d-flex">
                                                            <input type="file" name="file" class="form-control file-upload-input group-input " id="customFile">
                                                            <button class="btn btn-primary form-control float-right group-btn ml-0 w-120px">{{ __('parcel.import') }}</button>
                                                        </div>
                                                        @error('file')
                                                        <div class="text-danger ">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
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
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function(){
            $( "#merchant_id" ).select2({
                ajax: {
                    url: '{{ route('parcel.import.merchant.get') }}',
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            searchQuery: true
                        };
                    },
                    processResults: function (response) {
                        console.log(response);
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
    <script src="{{ static_asset('backend/js/custom.js') }}"></script>
@endpush


