<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Reporting\SaasMetrics;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reporting SaaS (chantier 6) : MRR, ARR, churn, LTV, CAC de la plateforme.
 *
 * Réservé au super-administrateur : la page lit toutes les sociétés, hors
 * de tout scoping locataire. Aucune écriture.
 */
class ReportingController extends Controller
{
    public function __construct(private SaasMetrics $metrics)
    {
    }

    public function index(Request $request)
    {
        abort_unless(isSuperadmin(), 403, __('saas.forbidden'));

        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : now()->startOfMonth();

        $current = $this->metrics->month($month);
        $trend = $this->metrics->trend((int) config('saas_reporting.trailing_months', 12), $month);

        return view('backend.super-admin.reporting.index', [
            'month' => $month,
            'current' => $current,
            'trend' => $trend,
            'monthDays' => (int) config('saas_reporting.month_days', 30),
            'cacHeads' => implode(', ', (array) config('saas_reporting.cac_account_heads', [])),
        ]);
    }
}
