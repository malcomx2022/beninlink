@extends('backend.partials.master')
@section('title'){{ __('delivery_zone.reference') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('menus.settings') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('delivery_zone.reference') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Migration additive : sans montant zoné, la facturation reste celle des
         quatre colonnes. Le dire ici évite de croire l'écran sans effet. --}}
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                {{ __('delivery_zone.legacy_notice') }}
                <a href="{{ route('delivery-zone.grid') }}" class="alert-link">{{ __('delivery_zone.grid') }}</a>
            </div>
        </div>
    </div>

    <!-- Zones -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <p class="h3">{{ __('delivery_zone.zones') }}</p>
                        <small class="text-muted">{{ __('delivery_zone.zones_help') }}</small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('delivery-zone.zones') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width:35%">{{ __('delivery_zone.name') }}</th>
                                        <th style="width:25%">{{ __('delivery_zone.code') }}</th>
                                        <th style="width:20%">{{ __('levels.status') }}</th>
                                        <th style="width:20%">{{ __('delivery_zone.remove') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="zones-body">
                                    @forelse($zones as $i => $zone)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="zones[{{ $i }}][id]" value="{{ $zone->id }}">
                                                <input type="text" name="zones[{{ $i }}][name]" class="form-control" value="{{ old('zones.'.$i.'.name', $zone->name) }}" required>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $zone->code }}</span>
                                                <small class="d-block text-muted">{{ __('delivery_zone.code_help') }}</small>
                                            </td>
                                            <td>
                                                <select name="zones[{{ $i }}][status]" class="form-control">
                                                    @foreach(trans('status') as $key => $label)
                                                        <option value="{{ $key }}" {{ (int) $zone->status === (int) $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="zones[{{ $i }}][delete]" value="1" id="zone-del-{{ $zone->id }}">
                                                    <label class="form-check-label" for="zone-del-{{ $zone->id }}">{{ __('delivery_zone.remove') }}</label>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="zones-empty"><td colspan="4"><span class="text-muted">{{ __('delivery_zone.no_zone') }}</span></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-repeat-add="#zones-body" data-repeat-name="zones" data-repeat-start="{{ $zones->count() }}"
                                data-repeat-template="zone-row">
                            <i class="fa fa-plus"></i> {{ __('delivery_zone.add_zone') }}
                        </button>
                        @if (hasPermission('delivery_charge_update') == true)
                            <button type="submit" class="btn btn-primary float-right">{{ __('levels.save') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delais -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <p class="h3">{{ __('delivery_zone.delays') }}</p>
                        <small class="text-muted">{{ __('delivery_zone.delays_help') }}</small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('delivery-zone.delays') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width:35%">{{ __('delivery_zone.name') }}</th>
                                        <th style="width:25%">{{ __('delivery_zone.surcharge') }}</th>
                                        <th style="width:20%">{{ __('levels.status') }}</th>
                                        <th style="width:20%">{{ __('delivery_zone.remove') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="delays-body">
                                    @foreach($delais as $i => $delai)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="delays[{{ $i }}][id]" value="{{ $delai->id }}">
                                                <input type="text" name="delays[{{ $i }}][name]" class="form-control" value="{{ old('delays.'.$i.'.name', $delai->name) }}" required>
                                                <small class="text-muted">{{ $delai->code }}</small>
                                            </td>
                                            <td>
                                                <input type="number" step="1" min="0" name="delays[{{ $i }}][surcharge]" class="form-control" value="{{ old('delays.'.$i.'.surcharge', (int) $delai->surcharge) }}">
                                            </td>
                                            <td>
                                                <select name="delays[{{ $i }}][status]" class="form-control">
                                                    @foreach(trans('status') as $key => $label)
                                                        <option value="{{ $key }}" {{ (int) $delai->status === (int) $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delays[{{ $i }}][delete]" value="1" id="delay-del-{{ $delai->id }}">
                                                    <label class="form-check-label" for="delay-del-{{ $delai->id }}">{{ __('delivery_zone.remove') }}</label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-repeat-add="#delays-body" data-repeat-name="delays" data-repeat-start="{{ $delais->count() }}"
                                data-repeat-template="delay-row">
                            <i class="fa fa-plus"></i> {{ __('delivery_zone.add_delay') }}
                        </button>
                        @if (hasPermission('delivery_charge_update') == true)
                            <button type="submit" class="btn btn-primary float-right">{{ __('levels.save') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Forfaits par pays -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <p class="h3">{{ __('delivery_zone.countries') }} @if($zoneExport)<small class="text-muted">— {{ $zoneExport->name }}</small>@endif</p>
                        <small class="text-muted">{{ __('delivery_zone.countries_help') }}</small>
                    </div>
                </div>
                <div class="card-body">
                    @if(blank($zoneExport))
                        <p class="text-muted mb-0">{{ __('delivery_zone.no_export_zone') }}</p>
                    @else
                        <form action="{{ route('delivery-zone.countries', $zoneExport->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="table-responsive">
                                <table class="table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width:20%">{{ __('delivery_zone.country_code') }}</th>
                                            <th style="width:35%">{{ __('delivery_zone.country_name') }}</th>
                                            <th style="width:25%">{{ __('delivery_zone.flat_amount') }}</th>
                                            <th style="width:20%">{{ __('delivery_zone.remove') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="countries-body">
                                        @foreach($pays as $i => $ligne)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="countries[{{ $i }}][id]" value="{{ $ligne->id }}">
                                                    <input type="text" name="countries[{{ $i }}][code]" class="form-control text-uppercase" maxlength="2" value="{{ old('countries.'.$i.'.code', $ligne->code) }}" required>
                                                </td>
                                                <td><input type="text" name="countries[{{ $i }}][name]" class="form-control" value="{{ old('countries.'.$i.'.name', $ligne->name) }}" required></td>
                                                <td><input type="number" step="1" min="0" name="countries[{{ $i }}][flat_amount]" class="form-control" value="{{ old('countries.'.$i.'.flat_amount', (int) $ligne->flat_amount) }}"></td>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="countries[{{ $i }}][delete]" value="1" id="country-del-{{ $ligne->id }}">
                                                        <label class="form-check-label" for="country-del-{{ $ligne->id }}">{{ __('delivery_zone.remove') }}</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-repeat-add="#countries-body" data-repeat-name="countries" data-repeat-start="{{ $pays->count() }}"
                                    data-repeat-template="country-row">
                                <i class="fa fa-plus"></i> {{ __('delivery_zone.add_country') }}
                            </button>
                            @if (hasPermission('delivery_charge_update') == true)
                                <button type="submit" class="btn btn-primary float-right">{{ __('levels.save') }}</button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gabarits des lignes ajoutées dynamiquement. `__INDEX__` est remplacé au clic. --}}
<template id="zone-row">
    <tr>
        <td><input type="hidden" name="zones[__INDEX__][id]" value=""><input type="text" name="zones[__INDEX__][name]" class="form-control" placeholder="{{ __('delivery_zone.name') }}"></td>
        <td><small class="text-muted">{{ __('delivery_zone.code_help') }}</small></td>
        <td>
            <select name="zones[__INDEX__][status]" class="form-control">
                @foreach(trans('status') as $key => $label)
                    <option value="{{ $key }}" {{ (int) $key === \App\Enums\Status::ACTIVE ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>{{ __('delivery_zone.remove') }}</button></td>
    </tr>
</template>
<template id="delay-row">
    <tr>
        <td><input type="hidden" name="delays[__INDEX__][id]" value=""><input type="text" name="delays[__INDEX__][name]" class="form-control" placeholder="{{ __('delivery_zone.name') }}"></td>
        <td><input type="number" step="1" min="0" name="delays[__INDEX__][surcharge]" class="form-control" value="0"></td>
        <td>
            <select name="delays[__INDEX__][status]" class="form-control">
                @foreach(trans('status') as $key => $label)
                    <option value="{{ $key }}" {{ (int) $key === \App\Enums\Status::ACTIVE ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>{{ __('delivery_zone.remove') }}</button></td>
    </tr>
</template>
<template id="country-row">
    <tr>
        <td><input type="hidden" name="countries[__INDEX__][id]" value=""><input type="text" name="countries[__INDEX__][code]" class="form-control text-uppercase" maxlength="2" placeholder="TG"></td>
        <td><input type="text" name="countries[__INDEX__][name]" class="form-control" placeholder="{{ __('delivery_zone.country_name') }}"></td>
        <td><input type="number" step="1" min="0" name="countries[__INDEX__][flat_amount]" class="form-control" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>{{ __('delivery_zone.remove') }}</button></td>
    </tr>
</template>
@endsection()

@push('scripts')
    <script src="{{ static_asset('backend/js/deliveryZone/repeat.js') }}"></script>
@endpush
