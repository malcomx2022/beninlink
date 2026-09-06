@extends('backend.partials.master')
@section('title'){{ __('delivery_zone.grid') }}@endsection
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
                            <li class="breadcrumb-item"><a href="{{ route('delivery-zone.index') }}" class="breadcrumb-link">{{ __('delivery_zone.reference') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('delivery_zone.grid') }}</a></li>
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
                    <form action="{{ route('delivery-zone.grid') }}" method="GET">
                        <div class="row">
                            <div class="form-group col-12 col-md-4">
                                <label for="category">{{ __('delivery_zone.category') }}</label>
                                <select id="category" name="category" class="form-control">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (int) $categoryId === (int) $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-12 col-md-4 pt-4">
                                <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-12">
                        <p class="h3">{{ __('delivery_zone.grid') }}</p>
                        {{-- Le supplément de délai s'ajoute au montant de la case ; le
                             rappeler évite de le saisir une seconde fois par zone. --}}
                        <small class="text-muted">
                            {{ __('delivery_zone.delays_help') }}
                            @foreach($delais as $delai)
                                <span class="badge badge-light">{{ $delai->name }} : +{{ formatAmount($delai->surcharge) }}</span>
                            @endforeach
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    @if($zones->isEmpty())
                        <p class="text-muted mb-0">{{ __('delivery_zone.no_zone') }}</p>
                    @else
                        <form action="{{ route('delivery-zone.grid.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="category" value="{{ $categoryId }}">
                            <div class="table-responsive">
                                <table class="table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{{ __('delivery_zone.weight') }}</th>
                                            @foreach($zones as $zone)
                                                <th>
                                                    {{ $zone->name }}
                                                    @if($zone->isExport())
                                                        <small class="d-block text-muted">{{ __('delivery_zone.countries') }}</small>
                                                    @endif
                                                </th>
                                            @endforeach
                                            <th>{{ __('delivery_zone.remove') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="grid-body">
                                        @foreach($tranches as $i => $tranche)
                                            <tr>
                                                <td>
                                                    <input type="number" step="1" min="0" name="rows[{{ $i }}][weight]" class="form-control" value="{{ $tranche['weight'] }}" readonly>
                                                </td>
                                                @foreach($zones as $zone)
                                                    <td>
                                                        @if($zone->isExport())
                                                            {{-- La zone d'export ne se tarife pas au poids :
                                                                 son prix vit dans les forfaits par pays. --}}
                                                            <a href="{{ route('delivery-zone.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('delivery_zone.countries') }}</a>
                                                        @else
                                                            <input type="number" step="1" min="0" name="rows[{{ $i }}][amounts][{{ $zone->id }}]" class="form-control"
                                                                   value="{{ isset($tranche['amounts'][$zone->id]) ? (int) $tranche['amounts'][$zone->id] : '' }}">
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="rows[{{ $i }}][delete]" value="1" id="grid-del-{{ $i }}">
                                                        <label class="form-check-label" for="grid-del-{{ $i }}">{{ __('delivery_zone.remove') }}</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-repeat-add="#grid-body" data-repeat-name="rows" data-repeat-start="{{ count($tranches) }}"
                                    data-repeat-template="grid-row">
                                <i class="fa fa-plus"></i> {{ __('delivery_zone.add_row') }}
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

<template id="grid-row">
    <tr>
        <td><input type="number" step="1" min="0" name="rows[__INDEX__][weight]" class="form-control" placeholder="{{ __('delivery_zone.weight') }}"></td>
        @foreach($zones as $zone)
            <td>
                @if($zone->isExport())
                    <span class="text-muted">—</span>
                @else
                    <input type="number" step="1" min="0" name="rows[__INDEX__][amounts][{{ $zone->id }}]" class="form-control">
                @endif
            </td>
        @endforeach
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>{{ __('delivery_zone.remove') }}</button></td>
    </tr>
</template>
@endsection()

@push('scripts')
    <script src="{{ static_asset('backend/js/deliveryZone/repeat.js') }}"></script>
@endpush
