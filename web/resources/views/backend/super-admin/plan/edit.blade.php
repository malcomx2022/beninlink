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
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('plan.index') }}"
                                        class="breadcrumb-link">{{ __('levels.plans') }}</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0)"
                                        class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
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
                        <h2 class="pageheader-title">{{ __('levels.edit') }} {{ __('levels.plans') }}</h2>
                        <form action="{{ route('plan.update', ['id' => $plan->id]) }}" method="POST"
                            enctype="multipart/form-data" id="basicform">
                            @csrf
                            @method('put')
                            <div class="row">

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="name">{{ __('levels.name') }}</label> <span
                                            class="text-danger">*</span>
                                        <input type="text" id="name" name="name"
                                            placeholder="{{ __('levels.enter_name') }}" class="form-control"
                                            value="{{ old('name', $plan->name) }}">
                                        @error('name')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="parcel_count">{{ __('levels.parcel_count') }}</label> <span
                                            class="text-danger">*</span>
                                        <input type="text" id="parcel_count" name="parcel_count"
                                            placeholder="{{ __('levels.parcel_count') }}" class="form-control"
                                            value="{{ old('parcel_count', $plan->parcel_count) }}">
                                        @error('parcel_count')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="deliveryman_count">{{ __('levels.max_deliveryman') }}</label> <span
                                            class="text-danger">*</span>
                                        <input type="text" id="deliveryman_count" name="deliveryman_count"
                                            placeholder="{{ __('levels.deliveryman_count') }}" class="form-control"
                                            value="{{ old('deliveryman_count',@$plan->deliveryman_count) }}">
                                        @error('deliveryman_count')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                  
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="days_count">{{ __('levels.days_count') }}</label> <span
                                            class="text-danger">*</span>
                                        <input type="text" id="days_count" name="days_count"
                                            placeholder="{{ __('levels.days_count') }}" class="form-control"
                                            value="{{ old('days_count', $plan->days_count) }}">
                                        @error('days_count')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="price">{{ __('levels.price') }} <small class="text-danger">( minimum $0.50 )</small></label> <span
                                            class="text-danger">*</span>
                                        <input type="text" id="price" name="price"
                                            placeholder="{{ __('levels.price') }}" class="form-control"
                                            value="{{ old('price', $plan->price) }}">
                                        @error('price')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>


                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="position">{{ __('levels.position') }}</label> <span
                                            class="text-danger">*</span>
                                        <input type="text" id="position" name="position"
                                            placeholder="{{ __('levels.position') }}" class="form-control"
                                            value="{{ old('position', $plan->position) }}">
                                        @error('position')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="status">{{ __('levels.status') }}</label>
                                        <select name="status" id="status"
                                            class="form-control @error('status') is-invalid @enderror">
                                            @foreach (trans('status') as $key => $status)
                                                <option value="{{ $key }}"
                                                    {{ old('status', $plan->status) == $key ? 'selected' : '' }}>
                                                    {{ $status }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="description">{{ __('levels.description') }}</label>
                                        <div class="form-control-wrap deliveryman-search">
                                            <textarea class="form-control" placeholder="{{ __('placeholder.Enter_description') }}" name="description"
                                                rows="5">{{ $plan->description }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 mt-3 plan-modules">
                                    <label class="form-label cmr-10">{{ __('levels.select_modules') }}</label>
                                    <div class="d-flex border-bottom align-items-center permission-check-box pb-2 pt-2">
                                        <input id="selectAllModules" class="read common-key" type="checkbox" />
                                        <label for="selectAllModules"
                                            class="permission-check-lebel">{{ __('levels.select_all') }}</label>
                                    </div>
                                    <div class="row check-module mt-3">
                                        @foreach ($modules as $module)
                                            <div class="col-lg-2 col-md-4 col-6">
                                                <div class="d-flex align-items-center permission-check-box pb-2 pt-2">
                                                    <input id="{{ @$module }}" class="read module common-key"
                                                        type="checkbox" value="{{ @$module }}" name="modules[]"
                                                        @if (in_array($module, $plan->modules)) checked @endif />
                                                    <label for="{{ @$module }}"
                                                        class="permission-check-lebel">{{ __('permissions.' . $module) }}</label>
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
                                    <a href="{{ route('plan.index') }}"
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
