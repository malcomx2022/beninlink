@extends('backend.partials.master')
@section('title')
    {{ __('levels.plans') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('plan.index') }}" class="breadcrumb-link">{{ __('levels.plans') }}</a></li>
                                <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.permissions') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="pageheader-title"> {{ __('levels.permissions') }}</h2>
                        <form action="{{ route('company.permissions',['id'=>$id]) }}" method="POST" enctype="multipart/form-data"
                            id="basicform">
                            @csrf
                            @method('put')
                            <div class="row"> 
                                <div class="col-lg-12 mt-3 plan-modules">
                                    <label class="form-label cmr-10">{{ __('levels.select_modules') }}</label>
                                    <div class="d-flex border-bottom align-items-center permission-check-box pb-2 pt-2">
                                        <input id="selectAllModules" class="read common-key" type="checkbox" />
                                        <label for="selectAllModules"  class="permission-check-lebel">{{ __('levels.select_all') }}</label>
                                    </div>
                                    <div class="row check-module mt-3">
                                        @foreach ($modules as $module)
                                            <div class="col-lg-2 col-md-4 col-6">
                                                <div class="d-flex align-items-center permission-check-box pb-2 pt-2">
                                                    <input id="{{ @$module }}"   class="read module common-key" type="checkbox" value="{{ @$module }}" name="modules[]" />
                                                    <label for="{{ @$module }}"  class="permission-check-lebel">{{ __('permissions.' . $module) }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                    <button type="submit"
                                        class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                    <a href="{{ route('company.index') }}"
                                        class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()
@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $(document).on('change', '#selectAllModules', function() {
                if ($(this).is(':checked')) {
                    $('.check-module').find('.common-key').prop('checked', true);
                } else {
                    $('.check-module').find('.common-key').prop('checked', false);
                }
            });
        });
    </script>
@endpush
