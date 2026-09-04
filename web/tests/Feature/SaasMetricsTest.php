<?php

namespace Tests\Feature;

use App\Enums\AccountHeads;
use App\Enums\Status;
use App\Models\Backend\AccountHead;
use App\Models\Backend\Expense;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use App\Services\Reporting\SaasMetrics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Chantier 6 — formules du reporting SaaS, sur un jeu de donnees construit
 * a la main : trois societes, deux plans (mensuel, annuel), un mois de
 * reference (juin 2026).
 *
 *   - A : plan mensuel 10 000 pris le 20 mai, renouvele le 15 juin → actif
 *         tout le mois, ni churn ni nouveau client.
 *   - B : plan annuel 100 000 pris le 20 mai → 100 000 × 30 / 365 par mois.
 *   - C : plan mensuel commence le 11 mai, expire le 10 juin, non renouvele → churn.
 *   - D : premier abonnement le 15 juin → nouveau client.
 * Les abonnements du seed sont supprimes en setUp pour partir d'un etat connu.
 */
class SaasMetricsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Plan $monthly;
    private Plan $annual;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        // Les seeds ne referencent la societe plateforme que par l'id 1 : les
        // societes de test sont creees a part.
        Subscription::query()->delete();

        $this->monthly = $this->plan('Mensuel', 10000, 30);
        $this->annual = $this->plan('Annuel', 100000, 365);
    }

    private function plan(string $name, int $price, int $days): Plan
    {
        $plan = new Plan();
        $plan->forceFill(['name' => $name, 'price' => $price, 'days_count' => $days, 'parcel_count' => 0, 'deliveryman_count' => 0, 'position' => 9, 'status' => Status::ACTIVE])->save();

        return $plan;
    }

    private function company(string $name): GeneralSettings
    {
        $company = new GeneralSettings();
        $company->forceFill(['name' => $name, 'status' => Status::ACTIVE, 'currency' => 'XOF'])->save();

        return $company;
    }

    private function subscribe(GeneralSettings $company, Plan $plan, string $start): Subscription
    {
        $sub = new Subscription();
        $sub->forceFill([
            'company_id' => $company->id,
            'user_id' => User::first()->id,
            'plan_id' => $plan->id,
            'price' => $plan->price,
            'days_count' => $plan->days_count,
            'parcel_count' => 0,
            'deliveryman_count' => 0,
            'start_date' => Carbon::parse($start)->toDateTimeString(),
            'expired_date' => Carbon::parse($start)->addDays($plan->days_count)->toDateTimeString(),
        ])->save();

        return $sub;
    }

    public function test_mrr_churn_ltv_et_cac_du_mois(): void
    {
        $a = $this->company('A');
        $b = $this->company('B');
        $c = $this->company('C');
        $d = $this->company('D');

        $this->subscribe($a, $this->monthly, '2026-05-20'); // expire le 19 juin...
        $this->subscribe($a, $this->monthly, '2026-06-15'); // ...renouvele avant
        $this->subscribe($b, $this->annual, '2026-05-20');
        $this->subscribe($c, $this->monthly, '2026-05-11'); // expire le 10 juin
        $this->subscribe($d, $this->monthly, '2026-06-15');

        // Depense d'acquisition de la plateforme (societe 1) en juin.
        $head = new AccountHead();
        $head->forceFill(['type' => AccountHeads::EXPENSE, 'name' => 'Marketing digital', 'status' => Status::ACTIVE])->save();
        $expense = new Expense();
        $expense->forceFill(['company_id' => 1, 'account_head_id' => $head->id, 'amount' => 50000, 'date' => '2026-06-20', 'title' => 'Campagne'])->save();

        $m = app(SaasMetrics::class)->month(Carbon::parse('2026-06-01'));

        // Actifs au 1er juin : A, B, C. Au 30 juin : A (renouvele), B, D.
        $this->assertSame(3, $m['active_start']);
        $this->assertSame(3, $m['active_end']);
        $this->assertSame([$c->id], $m['churned_customer_ids']);
        $this->assertSame(round(1 / 3, 4), $m['churn_rate']);
        $this->assertSame([$d->id], $m['new_customer_ids']);

        // MRR fin juin : A 10 000 + B 100 000 × 30 / 365 (8 219) + D 10 000.
        $expectedMrr = (int) round(10000 + 100000 * 30 / 365 + 10000);
        $this->assertSame($expectedMrr, $m['mrr']);
        $this->assertSame($expectedMrr * 12, $m['arr']);
        $this->assertSame(3, $m['paying_end']);
        $this->assertSame((int) round($expectedMrr / 3), $m['arpa']);
        // LTV = ARPA / churn.
        $this->assertSame((int) round($m['arpa'] / (1 / 3)), $m['ltv']);
        // CAC = 50 000 / 1 nouveau client.
        $this->assertSame(50000, $m['cac_spend']);
        $this->assertSame(50000, $m['cac']);
    }

    public function test_sans_churn_ni_depense_les_ratios_sont_non_disponibles(): void
    {
        $a = $this->company('A');
        $this->subscribe($a, $this->monthly, '2026-06-01');

        $m = app(SaasMetrics::class)->month(Carbon::parse('2026-06-01'));

        $this->assertSame(0.0, $m['churn_rate']);
        $this->assertNull($m['ltv']);
        $this->assertNull($m['cac_spend']);
        $this->assertNull($m['cac']);
        $this->assertSame(10000, $m['mrr']);
    }

    public function test_un_plan_gratuit_ne_compte_pas_dans_le_mrr_mais_dans_les_actifs(): void
    {
        $free = $this->plan('Essai', 0, 7);
        $a = $this->company('A');
        $this->subscribe($a, $free, '2026-06-28');

        $s = app(SaasMetrics::class)->snapshot(Carbon::parse('2026-06-30'));

        $this->assertSame(1, $s['active_customers']);
        $this->assertSame(0, $s['paying_customers']);
        $this->assertSame(0, $s['mrr']);
    }

    public function test_un_renouvellement_avant_echeance_n_est_pas_un_churn(): void
    {
        $a = $this->company('A');
        $this->subscribe($a, $this->monthly, '2026-05-15'); // expire le 14 juin
        $this->subscribe($a, $this->monthly, '2026-06-10'); // renouvele avant

        $m = app(SaasMetrics::class)->month(Carbon::parse('2026-06-01'));

        $this->assertSame(0, $m['churned_customers']);
        $this->assertSame(1, $m['active_end']);
        // Renouvellement, pas acquisition : le premier abonnement date de mai.
        $this->assertSame(0, $m['new_customers']);
    }

    public function test_la_page_est_reservee_au_super_admin(): void
    {
        // Le groupe super-admin passe par `IsInstalled` : sans ce drapeau, tout
        // est renvoye vers l'installeur.
        config(['app.app_installed' => 'yes']);

        $admin = User::where('email', 'company@wemaxdevs.com')->firstOrFail();
        $this->actingAs($admin)->get('/super-admin/reporting')->assertForbidden();

        $super = User::where('email', 'admin@wemaxdevs.com')->firstOrFail();
        $this->actingAs($super)->get('/super-admin/reporting?month=2026-06')
            ->assertOk()
            ->assertSee(__('saas.title'));
    }
}
