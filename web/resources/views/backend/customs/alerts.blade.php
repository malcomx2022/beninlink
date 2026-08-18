@extends('backend.partials.master')
@section('title'){{ __('customs.title') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('customs.title') }}</a></li>
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
                    <div class="col-6">
                        <p class="h3">{{ __('customs.title') }}</p>
                        <small class="text-muted">{{ __('customs.export') }}</small>
                    </div>
                    <div class="col-6 text-right">
                        {{-- Les deux onglets de la maquette. --}}
                        <a href="{{ route('customs.alerts') }}?status=1"
                           class="btn btn-sm {{ $status == 1 ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('customs.pending') }}</a>
                        <a href="{{ route('customs.alerts') }}?status=2"
                           class="btn btn-sm {{ $status == 2 ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('customs.resolved') }}</a>
                        <a href="{{ route('customs.rules') }}" class="btn btn-sm btn-outline-secondary">{{ __('customs.rules') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.date') }}</th>
                                    <th>{{ __('menus.merchant') }}</th>
                                    <th>{{ __('parcel.title') }}</th>
                                    <th>{{ __('customs.country') }}</th>
                                    <th>{{ __('customs.category') }}</th>
                                    <th>{{ __('customs.level') }}</th>
                                    <th>{{ __('customs.required_document') }}</th>
                                    <th>{{ __('levels.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td>{{ dateFormat($alert->created_at) }}</td>
                                    <td>{{ optional($alert->merchant)->business_name }}</td>
                                    <td>{{ optional($alert->parcel)->tracking_id }}</td>
                                    <td>{{ $alert->country_name }}</td>
                                    <td>{{ __('customs.category_' . $alert->goods_category) }}</td>
                                    <td>
                                        {{-- Rouge = incident, comme partout dans la charte. --}}
                                        <span class="badge badge-{{ $alert->level == 3 ? 'danger' : ($alert->level == 2 ? 'warning' : 'info') }}">
                                            {{ $alert->level_name }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $alert->required_document }}
                                        <br><small class="text-muted">{{ $alert->message }}</small>
                                    </td>
                                    <td>
                                        @if ($alert->status == 1 && hasPermission('parcel_update') == true)
                                            <form action="{{ route('customs.alerts.resolve', $alert->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-outline-success">{{ __('customs.resolved') }}</button>
                                            </form>
                                        @else
                                            <small class="text-muted">{{ dateFormat($alert->resolved_at) }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ __('customs.empty') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                        {{ $alerts->appends(['status' => $status])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
