@extends('backend.partials.master')
@section('title'){{ __('customs.rules') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customs.alerts') }}" class="breadcrumb-link">{{ __('customs.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('customs.rules') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-8">
                        <p class="h3">{{ __('customs.rules') }}</p>
                        {{-- Le référentiel livré est un point de départ : il engage l'exploitation. --}}
                        <small class="text-muted">{{ __('customs.export') }} — référentiel de départ, à faire valider par un transitaire.</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('customs.country') }}</th>
                                    <th>{{ __('customs.category') }}</th>
                                    <th>{{ __('customs.level') }}</th>
                                    <th>{{ __('customs.required_document') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if (hasPermission('parcel_update') == true)
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($rules as $rule)
                                <tr>
                                    <td>{{ $rule->country_name }} <small class="text-muted">({{ $rule->country_code }})</small></td>
                                    <td>{{ __('customs.category_' . $rule->goods_category) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $rule->level == 3 ? 'danger' : ($rule->level == 2 ? 'warning' : 'info') }}">
                                            {{ $rule->level_name }}
                                        </span>
                                    </td>
                                    <td>{{ $rule->required_document }}</td>
                                    <td>{{ $rule->status ? __('levels.active') : __('levels.inactive') }}</td>
                                    @if (hasPermission('parcel_update') == true)
                                        <td>
                                            <a href="{{ route('customs.rules.edit', $rule->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                            </a>
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
    </div>
</div>
@endsection
