{{-- Reporting SaaS (chantier 6). Tout vient de App\Services\Reporting\SaasMetrics :
     aucun calcul ici, seulement l'affichage et les définitions. --}}
@extends('backend.partials.master')
@section('title')
    {{ __('saas.title') }}
@endsection
@section('maincontent')
@php($money = fn ($v) => $v === null ? __('saas.not_available') : formatAmount($v))
@php($pct = fn ($v) => number_format($v * 100, 1, ',', ' ') . ' %')
    <div class="container-fluid dashboard-content">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item active">{{ __('saas.title') }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="row p-0 mb-3">
            <div class="col-12 col-md-6">
                <p class="h3 d-inline">{{ __('saas.title') }}</p><br>
                <small class="text-muted">{{ __('saas.subtitle') }}</small>
            </div>
            <div class="col-12 col-md-6 text-right pt-2 pt-sm-0">
                <form action="{{ route('saas.reporting') }}" method="get" class="form-inline float-right">
                    <input type="month" name="month" class="form-control form-control-sm mr-2" value="{{ $month->format('Y-m') }}">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('saas.show') }}</button>
                </form>
            </div>
        </div>

        <div class="row header-summery">
            @foreach ([
                ['label' => __('saas.mrr'), 'hint' => __('saas.mrr_long'), 'value' => $money($current['mrr'])],
                ['label' => __('saas.arr'), 'hint' => __('saas.arr_long'), 'value' => $money($current['arr'])],
                ['label' => __('saas.active_customers'), 'hint' => __('saas.paying_customers') . ' : ' . $current['paying_end'], 'value' => $current['active_end']],
                ['label' => __('saas.churn_rate'), 'hint' => __('saas.churned_customers') . ' : ' . $current['churned_customers'], 'value' => $pct($current['churn_rate'])],
                ['label' => __('saas.ltv'), 'hint' => __('saas.ltv_long'), 'value' => $money($current['ltv'])],
                ['label' => __('saas.cac'), 'hint' => __('saas.cac_spend') . ' : ' . $money($current['cac_spend']), 'value' => $money($current['cac'])],
            ] as $card)
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card border-3 border-top border-top-primary">
                        <div class="card-body">
                            <h5 class="m-0 text-primary">{{ $card['label'] }}</h5>
                            <h2 class="mb-1 m-0 text-primary">{{ $card['value'] }}</h2>
                            <small class="text-muted">{{ $card['hint'] }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="h5">{{ __('saas.trend') }}</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('saas.month') }}</th>
                                        <th class="text-right">{{ __('saas.active_customers') }}</th>
                                        <th class="text-right">{{ __('saas.new_customers') }}</th>
                                        <th class="text-right">{{ __('saas.churned_customers') }}</th>
                                        <th class="text-right">{{ __('saas.churn_rate') }}</th>
                                        <th class="text-right">{{ __('saas.mrr') }}</th>
                                        <th class="text-right">{{ __('saas.arpa') }}</th>
                                        <th class="text-right">{{ __('saas.ltv') }}</th>
                                        <th class="text-right">{{ __('saas.cac') }}</th>
                                        <th class="text-right">{{ __('saas.cash_collected') }}</th>
                                        <th class="text-right">{{ __('saas.bookings') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trend as $row)
                                        <tr class="{{ $row['month'] === $current['month'] ? 'font-weight-bold' : '' }}">
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-right">{{ $row['active_end'] }}</td>
                                            <td class="text-right">{{ $row['new_customers'] }}</td>
                                            <td class="text-right">{{ $row['churned_customers'] }}</td>
                                            <td class="text-right">{{ $pct($row['churn_rate']) }}</td>
                                            <td class="text-right">{{ $money($row['mrr']) }}</td>
                                            <td class="text-right">{{ $money($row['arpa']) }}</td>
                                            <td class="text-right">{{ $money($row['ltv']) }}</td>
                                            <td class="text-right">{{ $money($row['cac']) }}</td>
                                            <td class="text-right">{{ $money($row['cash_collected']) }}</td>
                                            <td class="text-right">{{ $money($row['bookings']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="h5">{{ __('saas.companies') }} — {{ $current['label'] }}</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('saas.company') }}</th>
                                        <th>{{ __('saas.plan') }}</th>
                                        <th class="text-right">{{ __('saas.price') }}</th>
                                        <th class="text-right">{{ __('saas.days') }}</th>
                                        <th>{{ __('saas.start') }}</th>
                                        <th>{{ __('saas.end') }}</th>
                                        <th>{{ __('saas.status') }}</th>
                                        <th class="text-right">{{ __('saas.monthly') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($current['companies'] as $company)
                                        <tr>
                                            <td>{{ $company['name'] }}</td>
                                            <td>{{ $company['plan'] ?: '—' }}</td>
                                            <td class="text-right">{{ $money($company['price']) }}</td>
                                            <td class="text-right">{{ $company['days_count'] ?: '—' }}</td>
                                            <td>{{ $company['start_date'] ?? '—' }}</td>
                                            <td>{{ $company['expired_date'] ?? '—' }}</td>
                                            <td>
                                                @if ($company['active'])
                                                    <span class="badge badge-success">{{ __('saas.active') }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ __('saas.inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ $money($company['mrr']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-muted">—</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="h5">{{ __('saas.definitions') }}</p>
                        <ul class="mb-0 text-muted">
                            <li>{{ __('saas.def_customer') }}</li>
                            <li>{{ __('saas.def_mrr', ['days' => $monthDays]) }}</li>
                            <li>{{ __('saas.def_new') }}</li>
                            <li>{{ __('saas.def_churn') }}</li>
                            <li>{{ __('saas.def_ltv') }}</li>
                            <li>{{ __('saas.def_cac', ['heads' => $cacHeads]) }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
