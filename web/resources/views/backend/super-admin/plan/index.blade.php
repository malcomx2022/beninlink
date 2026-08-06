@extends('backend.partials.master')
@section('title')
    {{ __('levels.plans') }} {{ __('levels.list') }}
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
                                <li class="breadcrumb-item"><a href="javascript:void(0)"
                                        class="breadcrumb-link">{{ __('levels.plans') }}</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0)"
                                        class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                            <p class="h3">{{ __('levels.plans') }}</p>
                        </div>
                        <div class="col-6">
                            @if (hasPermission('plans_create') == true)
                                <a href="{{ route('plan.create') }}" class="btn btn-primary btn-sm float-right"
                                    data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i
                                        class="fa fa-plus"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table   " style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('levels.id') }}</th>
                                        <th>{{ __('levels.name') }}</th>
                                        <th>{{ __('levels.parcel_count') }}</th>
                                        <th>{{ __('levels.max_deliveryman') }}</th>
                                        <th>{{ __('levels.days_count') }}</th>
                                        <th>{{ __('levels.price') }}</th>
                                        <th>{{ __('levels.description') }}</th>
                                        <th>{{ __('levels.position') }}</th>
                                        <th>{{ __('levels.total_modules') }}</th>
                                        <th>{{ __('levels.modules') }}</th>
                                        <th>{{ __('levels.status') }}</th>

                                        @if (hasPermission('plans_update') == true || hasPermission('plans_delete') == true)
                                            <th>{{ __('levels.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($plans as $plan)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $plan->name }}</td>
                                            <td>{{ $plan->parcel_count }}</td>
                                            <td>{{ $plan->deliveryman_count }}</td>
                                            <td>{{ $plan->days_count }}</td>
                                            <td>{{ $plan->price }}</td>
                                            <td>{{ $plan->description }}</td>
                                            <td>{{ $plan->position }}</td>
                                            <td>{{ count($plan->modules??[]) }}</td>
                                            <td>
                                                <button class="btn btn-primary modalBtn" data-toggle="modal"
                                                    data-target="#dynamic-modal"
                                                    data-url="{{ route('plan.modules.view', $plan->id) }}"
                                                    data-title="{{ $plan->name }}"><i class="fa fa-eye"></i></button>
                                            </td>
                                            <td>{!! $plan->my_status !!}</td>
                                            @if (hasPermission('plans_read') == true ||
                                                    hasPermission('plans_update') == true ||
                                                    hasPermission('plans_delete') == true)
                                                <td>
                                                    <div class="row">
                                                        <button tabindex="-1" data-toggle="dropdown" type="button"
                                                            class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                                class="sr-only">Toggle Dropdown</span></button>
                                                        <div class="dropdown-menu">
                                                            @if (hasPermission('plans_update') == true)
                                                                <a href="{{ route('plan.edit', $plan->id) }}"
                                                                    class="dropdown-item"><i class="fas fa-edit"
                                                                        aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                            @endif
                                                            @if (hasPermission('plans_delete') == true)
                                                                <form id="delete"
                                                                    action="{{ route('plan.delete', $plan->id) }}"
                                                                    method="POST" data-title="{{ __('delete.plan') }}">
                                                                    @method('DELETE')
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item"><i
                                                                            class="fa fa-trash" aria-hidden="true"></i>
                                                                        {{ __('levels.delete') }}</button>
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
                    <div class="px-3 d-flex flex-row-reverse align-items-center">
                        <span>{{ $plans->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $plans->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $plans->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $plans->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        #selectAssignType .select2-container .select2-selection--single {
            height: 32px !important;
        }
    </style>
@endpush

<!-- js  -->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="{{ static_asset('backend/js/filter/index.js') }}"></script>
    <script type="text/javascript">
        $("#month").datepicker({
            format: "yyyy-mm",
            startView: "months",
            minViewMode: "months"
        });
    </script>
@endpush
