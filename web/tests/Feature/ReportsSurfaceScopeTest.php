<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Salary;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\User;
use App\Repositories\Reports\ReportsInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S62 — les six écrans de rapport.
 *
 * Suite de l'arriéré du filet S58 (17 → 11). Les six étaient bornés ; ils
 * restaient à l'arriéré parce que **lu n'est pas prouvé**.
 *
 * ## Deux niveaux de preuve, et pourquoi
 *
 * Quatre écrans rendent une **identité** — le nom du client, le nom du salarié —
 * et se prouvent donc par un appel HTTP : c'est la donnée qui fuirait.
 *
 * Les deux autres (`parcel-filter-reports`, `parcel-filter-total-summery`) ne
 * rendent que des **compteurs agrégés**. Une assertion sur un chiffre dans du
 * HTML serait fragile — « 1 » et « 7 » apparaissent partout dans une page. Ils
 * sont donc prouvés au niveau du **dépôt**, en vérifiant que la collection
 * rendue contient notre colis et **pas** celui d'en face. C'est l'idiome déjà
 * employé par `BackOfficeMoneyScopeTest` (S30) et `ParcelCatalogScopeTest` (S46).
 */
class ReportsSurfaceScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    /* ─────────────── les quatre écrans qui rendent une identité ──────────── */

    public function test_the_parcel_profit_report_stays_inside_the_company(): void
    {
        $this->colisDe(settings()->id, 'MIEN', ParcelStatus::DELIVERED);
        $this->colisDe(self::AUTRE, 'SIEN', ParcelStatus::DELIVERED);

        $page = $this->ecran('/admin/reports/parcel-wise-profit-reports', 'parcel_wise_profit', [
            'parcel_date' => $this->plage(),
        ]);

        $this->assertStringContainsString('CLIENT-S62-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S62-SIEN', $page,
            'le rapport de rentabilité rend le CLIENT d\'une AUTRE société');
    }

    /**
     * ⚠️ Mon premier jet était CREUX, et c'est le sabotage qui l'a dit : il ne
     * semait que des `Salary`, or la vue boucle sur `SalaryGenerate`. La page
     * n'avait donc aucune ligne — et le contrôle positif passait quand même,
     * parce que `SALARIE-S62-MIEN` figurait dans la LISTE DÉROULANTE des
     * utilisateurs. Même piège qu'en S60.
     */
    public function test_the_salary_report_stays_inside_the_company(): void
    {
        $this->paieDe(settings()->id, 'MIEN');
        $this->paieDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/reports/reports-salary-reports', 'salary_reports', [
            'salary_date' => $this->plage(),
        ]);

        // Le marqueur de PRÉSENCE est le montant du bulletin : la liste déroulante
        // rend les noms, elle ne rend aucun montant.
        $this->assertStringContainsString(formatAmount(123457), $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('SALARIE-S62-SIEN', $page,
            'le rapport de paie rend un salarié d\'une AUTRE société');
    }

    /**
     * L'autre moitié du même rapport : la branche des VERSEMENTS (`Salary`).
     *
     * ⚠️ Elle n'est **pas observable depuis la vue** : les versements y sont
     * appariés au bulletin par `user_id`, et un `user_id` étranger ne correspond
     * à aucune de nos lignes. Sa portée est donc de la défense en profondeur —
     * réelle, mais invisible à l'écran. On la prouve là où elle se voit : sur la
     * collection que le dépôt rend.
     */
    public function test_the_salary_payment_branch_of_the_report_is_scoped_too(): void
    {
        $mien = $this->salaireDe(settings()->id, 'MIEN');
        $sien = $this->salaireDe(self::AUTRE, 'SIEN');

        $this->actingAs($this->agentDe(settings()->id));

        $ids = app(ReportsInterface::class)
            ->salaryReports(new Request(['salary_date' => $this->plage()]))['salaryPayment']
            ->flatten()->pluck('id')->all();

        $this->assertContains($mien->id, $ids, 'contrôle positif tombé');
        $this->assertNotContains($sien->id, $ids,
            'la branche des versements du rapport de paie compte celui d\'une AUTRE société');
    }

    public function test_the_salary_report_print_stays_inside_the_company(): void
    {
        // ⚠️ Cet ecran boucle sur `$totalSalary['salary']`, c'est-a-dire sur
        // `SalaryGenerate` — pas sur `Salary` comme son voisin. Deux tables dans
        // le meme rapport ; il faut semer les DEUX, sinon la page est vide et le
        // controle positif tombe (il l'a fait).
        $this->salaireDe(settings()->id, 'MIEN');
        $this->salaireDe(self::AUTRE, 'SIEN');
        $this->paieDe(settings()->id, 'MIEN');
        $this->paieDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/reports/salary-report-print', 'salary_reports', [
            'salary_date' => $this->plage(),
        ]);

        $this->assertStringContainsString('SALARIE-S62-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('SALARIE-S62-SIEN', $page,
            'l\'impression du rapport de paie rend un salarié d\'une AUTRE société');
    }

    public function test_the_salary_filter_stays_inside_the_company(): void
    {
        $this->paieDe(settings()->id, 'MIEN');
        $this->paieDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/salarys/filter', 'salary_read', ['month' => now()->format('Y-m')]);

        $this->assertStringContainsString('SALARIE-S62-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('SALARIE-S62-SIEN', $page,
            'le filtre des bulletins rend un salarié d\'une AUTRE société');
    }

    /* ───────── les deux écrans d'agrégats, prouvés au niveau du dépôt ────── */

    public function test_the_parcel_report_collection_stays_inside_the_company(): void
    {
        $mien = $this->colisDe(settings()->id, 'MIEN', ParcelStatus::PENDING);
        $sien = $this->colisDe(self::AUTRE, 'SIEN', ParcelStatus::PENDING);

        $this->actingAs($this->agentDe(settings()->id));

        $ids = app(ReportsInterface::class)
            ->parcelReports(new Request(['parcel_date' => $this->plage()]))
            ->flatten()->pluck('id')->all();

        $this->assertContains($mien->id, $ids, 'contrôle positif tombé : notre colis n\'est plus rendu');
        $this->assertNotContains($sien->id, $ids,
            'le rapport des colis compte celui d\'une AUTRE société');
    }

    public function test_the_total_summary_collection_stays_inside_the_company(): void
    {
        $mien = $this->colisDe(settings()->id, 'MIEN', ParcelStatus::PENDING);
        $sien = $this->colisDe(self::AUTRE, 'SIEN', ParcelStatus::PENDING);

        $this->actingAs($this->agentDe(settings()->id));

        $ids = app(ReportsInterface::class)
            ->parcelTotalSummeryReports(new Request(['parcel_date' => $this->plage()]))
            ->pluck('id')->all();

        $this->assertContains($mien->id, $ids, 'contrôle positif tombé');
        $this->assertNotContains($sien->id, $ids,
            'le récapitulatif totalise le colis d\'une AUTRE société');
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    private function ecran(string $uri, string $droit, array $parametres): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = [$droit];
        $agent->save();

        $reponse = $this->actingAs($agent)->get(self::HOTE . $uri . '?' . http_build_query($parametres));
        $reponse->assertOk();

        return $reponse->getContent();
    }

    private function plage(): string
    {
        return now()->subDay()->toDateString() . 'To' . now()->addDay()->toDateString();
    }

    private function colisDe(int $societe, string $marque, int $statut): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $this->marchandDe($societe)->id,
            'tracking_id' => 'SUIVI-S62-' . $marque,
            'customer_name' => 'CLIENT-S62-' . $marque,
            'customer_phone' => '0022997' . ord($marque[0]) . '000',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => $statut,
            'priority_type_id' => 1,
            'created_at' => now(),
        ]);
    }

    /** Un bulletin VERSÉ (table `salaries`), lu par les rapports de paie. */
    private function salaireDe(int $societe, string $marque): Salary
    {
        return Salary::forceCreate([
            'company_id' => $societe,
            'user_id' => $this->salarieDe($societe, $marque)->id,
            // ⚠️ `salaries.account_id` est NOT NULL : sans compte, l'insertion
            // echoue et le test tomberait pour une raison qui n'est pas la portee.
            'account_id' => $this->compteDe($societe)->id,
            'month' => now()->format('Y-m'),
            'amount' => 120000,
            // ⚠️ `salaries` n'a pas de colonne `salary_date` : le rapport filtre sur
            // `created_at` (`whereBetween`), et `salary_date` n'est qu'un parametre
            // de REQUETE. Le test l'a dit avant moi.
            'date' => now()->toDateString(),
            'created_at' => now(),
        ]);
    }

    /** Un bulletin GÉNÉRÉ (table `salary_generates`), lu par `salarys/filter`. */
    private function paieDe(int $societe, string $marque): SalaryGenerate
    {
        return SalaryGenerate::forceCreate([
            'company_id' => $societe,
            'user_id' => $this->salarieDe($societe, $marque)->id,
            'month' => now()->format('Y-m'),
            'amount' => 123457,
            'status' => 1,
            'due' => 0,
            'advance' => 0,
        ]);
    }

    private function compteDe(int $societe): \App\Models\Backend\Account
    {
        return \App\Models\Backend\Account::firstWhere('company_id', $societe)
            ?? \App\Models\Backend\Account::forceCreate([
                'company_id' => $societe, 'type' => 1, 'gateway' => 2, 'bank' => 1,
                'balance' => 900000, 'opening_balance' => 0,
                'account_holder_name' => 'Caisse S62 ' . $societe,
                'account_no' => 'CPT-S62-' . $societe, 'branch_name' => 'Agence',
                'mobile' => '0022997000000', 'account_type' => 1, 'status' => 1,
            ]);
    }

    private function salarieDe(int $societe, string $marque): User
    {
        $u = $this->agentDe($societe);
        $u->name = 'SALARIE-S62-' . $marque;
        $u->save();

        return $u;
    }

    private function marchandDe(int $societe): Merchant
    {
        $existant = Merchant::where('company_id', $societe)->first();

        if ($existant) {
            return $existant;
        }

        $u = $this->agentDe($societe);
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME S62 ' . $societe, 'current_balance' => 0,
            'opening_balance' => 0, 'wallet_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S62';
        $agent->email = 'agent.s62.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
