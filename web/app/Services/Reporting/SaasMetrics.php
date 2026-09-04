<?php

namespace App\Services\Reporting;

use App\Enums\AccountHeads;
use App\Models\Backend\Expense;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Subscription;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Indicateurs SaaS de la plateforme, calculés en lecture seule.
 *
 * Définitions retenues (à afficher avec les chiffres, jamais implicites) :
 *   - **Client** : une société locataire (`general_settings`), hors plateforme.
 *   - **Abonnement courant** d'un client à une date : son abonnement le plus
 *     récent commencé à cette date ; **actif** si sa date d'expiration n'est
 *     pas passée.
 *   - **MRR** : somme, sur les clients actifs, du prix de leur abonnement
 *     ramené au mois (`prix × 30 / days_count`). Un plan gratuit vaut 0.
 *   - **ARR** : MRR × 12.
 *   - **Nouveaux clients** du mois : clients dont le **premier** abonnement
 *     commence dans le mois.
 *   - **Churn** du mois : clients actifs au 1er du mois et plus actifs au
 *     dernier jour (expirés sans renouvellement) ; **taux** = churnés / actifs
 *     au 1er.
 *   - **ARPA** : MRR / clients payants (MRR > 0).
 *   - **LTV** : ARPA / taux de churn mensuel. Sans churn, non calculable.
 *   - **CAC** : dépenses d'acquisition de la plateforme (chapitres comptables
 *     de `config/saas_reporting.php`) / nouveaux clients du mois.
 *
 * Montants en XOF entiers. Aucune écriture, aucun `settings()` : la page est
 * réservée au super-admin, hors de tout locataire.
 */
class SaasMetrics
{
    /** Instantané des clients et de leur abonnement courant à une date. */
    public function snapshot(CarbonInterface $at): array
    {
        $platformId = (int) config('saas_reporting.platform_company_id', 1);
        $monthDays = (int) config('saas_reporting.month_days', 30);

        $companies = GeneralSettings::query()
            ->where('id', '!=', $platformId)
            ->orderBy('id')
            ->get(['id', 'name', 'status', 'created_at']);

        // Abonnements commencés à la date, du plus récent au plus ancien, par
        // société : le premier de chaque groupe est l'abonnement courant.
        $current = Subscription::query()
            ->with('plan:id,name')
            ->where('start_date', '<=', $at)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('company_id')
            ->map(fn (Collection $subs) => $subs->first());

        $rows = [];
        $mrr = 0.0;
        $active = 0;
        $paying = 0;

        foreach ($companies as $company) {
            $sub = $current->get($company->id);
            $isActive = $sub !== null && $sub->expired_date !== null && Carbon::parse($sub->expired_date)->gte($at);
            $monthly = 0.0;
            if ($isActive && (float) $sub->price > 0 && (int) $sub->days_count > 0) {
                $monthly = (float) $sub->price * $monthDays / (int) $sub->days_count;
            }
            if ($isActive) {
                $active++;
                if ($monthly > 0) {
                    $paying++;
                }
                $mrr += $monthly;
            }

            $rows[] = [
                'company_id' => $company->id,
                'name' => (string) $company->name,
                'plan' => $sub?->plan?->name ?? '',
                'price' => $sub ? (int) round((float) $sub->price) : 0,
                'days_count' => $sub ? (int) $sub->days_count : 0,
                'start_date' => $sub?->start_date ? Carbon::parse($sub->start_date)->toDateString() : null,
                'expired_date' => $sub?->expired_date ? Carbon::parse($sub->expired_date)->toDateString() : null,
                'active' => $isActive,
                'mrr' => (int) round($monthly),
            ];
        }

        return [
            'at' => $at->toDateString(),
            'customers' => count($rows),
            'active_customers' => $active,
            'paying_customers' => $paying,
            'mrr' => (int) round($mrr),
            // Dérivé du MRR affiché, pour que ARR = 12 × MRR au franc près.
            'arr' => (int) round($mrr) * 12,
            'arpa' => $paying > 0 ? (int) round($mrr / $paying) : 0,
            'companies' => $rows,
        ];
    }

    /** Indicateurs d'un mois civil. */
    public function month(CarbonInterface $month): array
    {
        $start = Carbon::instance($month)->startOfMonth();
        $end = Carbon::instance($month)->endOfMonth();

        $atStart = $this->snapshot($start);
        $atEnd = $this->snapshot($end);

        $activeStart = collect($atStart['companies'])->where('active', true)->pluck('company_id');
        $activeEnd = collect($atEnd['companies'])->where('active', true)->pluck('company_id');
        $churned = $activeStart->diff($activeEnd)->values();

        $newCustomers = $this->newCustomersBetween($start, $end);
        $churnRate = $activeStart->count() > 0 ? $churned->count() / $activeStart->count() : 0.0;

        $cacSpend = $this->acquisitionSpendBetween($start, $end);

        return [
            'month' => $start->format('Y-m'),
            'label' => $start->translatedFormat('F Y'),
            'active_start' => $activeStart->count(),
            'active_end' => $activeEnd->count(),
            'paying_end' => $atEnd['paying_customers'],
            'new_customers' => $newCustomers->count(),
            'new_customer_ids' => $newCustomers->values()->all(),
            'churned_customers' => $churned->count(),
            'churned_customer_ids' => $churned->all(),
            'churn_rate' => round($churnRate, 4),
            'mrr' => $atEnd['mrr'],
            'arr' => $atEnd['arr'],
            'arpa' => $atEnd['arpa'],
            'ltv' => $churnRate > 0 ? (int) round($atEnd['arpa'] / $churnRate) : null,
            'cac_spend' => $cacSpend,
            'cac' => $newCustomers->count() > 0 && $cacSpend !== null
                ? (int) round($cacSpend / $newCustomers->count())
                : null,
            // Encaissements réels d'abonnement du mois (FedaPay approuvé) et
            // souscriptions enregistrées (toutes voies : Stripe, FedaPay, manuel).
            'cash_collected' => $this->subscriptionCashBetween($start, $end),
            'bookings' => (int) round((float) Subscription::whereBetween('created_at', [$start, $end])->sum('price')),
            'companies' => $atEnd['companies'],
        ];
    }

    /** Les N derniers mois jusqu'au mois donné inclus, sans le détail par société. */
    public function trend(int $months, ?CarbonInterface $until = null): array
    {
        $until = Carbon::instance($until ?? now())->startOfMonth();
        $rows = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $row = $this->month($until->copy()->subMonths($i));
            unset($row['companies'], $row['new_customer_ids'], $row['churned_customer_ids']);
            $rows[] = $row;
        }

        return $rows;
    }

    /** Sociétés dont le premier abonnement commence dans l'intervalle. */
    private function newCustomersBetween(Carbon $start, Carbon $end): Collection
    {
        $platformId = (int) config('saas_reporting.platform_company_id', 1);

        return Subscription::query()
            ->selectRaw('company_id, MIN(start_date) as first_start')
            ->whereNotNull('company_id')
            ->where('company_id', '!=', $platformId)
            ->groupBy('company_id')
            ->get()
            ->filter(fn ($row) => $row->first_start !== null
                && Carbon::parse($row->first_start)->between($start, $end))
            ->pluck('company_id');
    }

    /** Dépenses d'acquisition de la plateforme ; `null` si aucun chapitre ne correspond. */
    private function acquisitionSpendBetween(Carbon $start, Carbon $end): ?int
    {
        $platformId = (int) config('saas_reporting.platform_company_id', 1);
        $keywords = array_map('mb_strtolower', (array) config('saas_reporting.cac_account_heads', []));

        $query = Expense::query()
            ->where('expenses.company_id', $platformId)
            ->join('account_heads', 'account_heads.id', '=', 'expenses.account_head_id')
            ->where('account_heads.type', AccountHeads::EXPENSE)
            ->whereBetween('expenses.date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhereRaw('LOWER(account_heads.name) LIKE ?', ['%' . $keyword . '%']);
                }
            });

        if ($keywords === [] || !$query->exists()) {
            return null;
        }

        return (int) round((float) $query->sum('expenses.amount'));
    }

    private function subscriptionCashBetween(Carbon $start, Carbon $end): int
    {
        return (int) FedaPayTransaction::query()
            ->where('purpose', FedaPayTransaction::PURPOSE_SUBSCRIPTION)
            ->where('status', FedaPayTransaction::STATUS_APPROVED)
            ->whereBetween('approved_at', [$start, $end])
            ->sum('amount');
    }
}
