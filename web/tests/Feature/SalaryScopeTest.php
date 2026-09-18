<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\Backend\Salary;
use App\Models\User;
use App\Repositories\Salary\SalaryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S29, quatrième passe — la paie.
 *
 * `SalaryRepository` lisait **nu** partout : `get()`, `edit()`, `update()`,
 * `delete()`, `singleSalaryGenerate()`. Le bulletin de paie d'un agent d'une autre
 * société — bénéficiaire, mois, montant, compte bancaire — s'ouvrait en changeant
 * l'identifiant dans l'URL, et l'écran `pay-slip` l'imprime.
 *
 * C'est une donnée personnelle, et deux des cinq méthodes déplacent de l'argent :
 *
 * - `update()` **créditait le compte bancaire de l'autre société** du montant lu,
 *   réécrivait sa ligne de paie (bénéficiaire, compte, montant, mois), puis
 *   débitait le nôtre. Un désordre comptable à cheval sur deux sociétés.
 * - `delete()` créditait ce même compte avant d'effacer la ligne.
 *
 * ⚠️ Et `SalaryController::update()` lisait nu **dans le contrôleur**, avec
 * l'identifiant dans le **corps** de la requête (`$request->id`) : l'angle mort de
 * `WebIsolationCoverageTest`, qui n'énumère que les routes à paramètre. Le filet
 * n'aurait jamais montré celle-là.
 *
 * ⚠️ Honnêteté sur la portée du test du contrôleur : cet écran porte désormais
 * **deux** gardes — sur le bulletin, et sur le compte bancaire. Retirer le premier
 * seul ne fait pas rougir le test, parce que le second (`AccountRepository::get()`,
 * `companywise()` depuis S24) attrape déjà le cas. Il faut retirer **les deux**
 * pour voir le comportement du socle revenir : `Attempt to read property "balance"
 * on null` — un **500**. L'écran ne servait donc pas le bulletin du voisin par ce
 * chemin, il plantait ; ce que ce lot ajoute, c'est un refus propre. Le vrai
 * périmètre de la paie est prouvé par les trois autres tests, sur le dépôt.
 *
 * `salaryGenerateDelete()` faisait exception : elle comparait déjà `company_id`.
 */
class SalaryScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->actingAs($this->agent());
    }

    public function test_a_payslip_of_another_company_cannot_be_read(): void
    {
        $sien = $this->bulletinDe(self::AUTRE);
        $depot = app(SalaryInterface::class);

        $this->assertNull($depot->get($sien->id), 'le bulletin de paie d\'une autre société est lisible');
        $this->assertNull($depot->edit($sien->id));
        $this->assertNull($depot->singleSalaryGenerate($this->ligneDePaieDe(self::AUTRE)->id));
    }

    /**
     * `salaryGenerateDelete()` faisait déjà exception : elle comparait `company_id`
     * avant d'effacer. On l'inscrit, on ne la corrige pas.
     */
    public function test_a_generated_payroll_line_of_another_company_cannot_be_deleted(): void
    {
        $sienne = $this->ligneDePaieDe(self::AUTRE);

        $this->assertFalse((bool) app(SalaryInterface::class)->salaryGenerateDelete($sienne->id));
        $this->assertNotNull(SalaryGenerate::withoutGlobalScopes()->find($sienne->id));

        $mienne = $this->ligneDePaieDe(settings()->id);
        $this->assertTrue((bool) app(SalaryInterface::class)->salaryGenerateDelete($mienne->id));
    }

    /**
     * 🔴 Le cœur du défaut : l'écriture bougeait de l'argent sur les comptes de
     * l'autre société.
     */
    public function test_no_money_moves_on_another_companys_payslip(): void
    {
        $sien = $this->bulletinDe(self::AUTRE);
        $sonCompte = Account::find($sien->account_id);
        $soldeAvant = (float) $sonCompte->balance;
        $montantAvant = (float) $sien->amount;
        $ecrituresAvant = BankTransaction::count();

        $monCompte = $this->compteDe(settings()->id, 900000);

        $depot = app(SalaryInterface::class);

        $this->assertFalse((bool) $depot->update($sien->id, new \Illuminate\Http\Request([
            'user_id' => $this->agent()->id, 'account_id' => $monCompte->id,
            'month' => 'Janvier', 'date' => now()->toDateString(), 'amount' => 1000, 'note' => 'Détournement',
        ])), 'update a agi sur le bulletin d\'une autre société');

        $this->assertFalse((bool) $depot->delete($sien->id),
            'delete a agi sur le bulletin d\'une autre société');

        $this->assertSame($soldeAvant, (float) $sonCompte->fresh()->balance,
            'le compte bancaire de l\'autre société a bougé');
        $this->assertSame(900000.0, (float) $monCompte->fresh()->balance,
            'notre compte a bougé pour une paie qui n\'est pas la nôtre');
        $this->assertSame($ecrituresAvant, BankTransaction::count(),
            'une écriture bancaire a été passée');
        $this->assertNotNull(Salary::withoutGlobalScopes()->find($sien->id),
            'le bulletin de l\'autre société a été supprimé');
        $this->assertSame($montantAvant, (float) $sien->fresh()->amount,
            'le montant du bulletin de l\'autre société a été réécrit');
    }

    /**
     * Le contrôle négatif : sur MON bulletin, tout marche — sans lui, un périmètre
     * qui ne renverrait jamais rien ferait passer le test ci-dessus à vide.
     */
    public function test_my_own_payslip_is_still_readable_and_deletable(): void
    {
        $mien = $this->bulletinDe(settings()->id);

        $depot = app(SalaryInterface::class);

        $this->assertNotNull($depot->get($mien->id));
        $this->assertNotNull($depot->edit($mien->id));
        $this->assertNotNull($depot->singleSalaryGenerate($this->ligneDePaieDe(settings()->id)->id));

        $this->assertTrue((bool) $depot->delete($mien->id));
        $this->assertNull(Salary::withoutGlobalScopes()->find($mien->id));
    }

    /**
     * ⚠️ L'identifiant dans le corps de la requête. Le contrôleur lisait nu, et
     * le filet ne pouvait pas le voir.
     */
    public function test_the_controller_refuses_a_payslip_of_another_company(): void
    {
        $sien = $this->bulletinDe(self::AUTRE);

        $requete = new \App\Http\Requests\Salary\UpdateRequest();
        $requete->replace([
            'id' => $sien->id, 'user_id' => $this->agent()->id,
            'account_id' => $this->compteDe(settings()->id, 900000)->id,
            'month' => 'Janvier', 'date' => now()->toDateString(), 'amount' => 1000,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        app(\App\Http\Controllers\Backend\SalaryController::class)->update($requete);
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function agent(): User
    {
        return User::firstWhere('email', 'agent.paie@example.test') ?? tap(new User(), function ($agent) {
            $agent->company_id = settings()->id;
            $agent->name = 'Agent paie';
            $agent->email = 'agent.paie@example.test';
            $agent->mobile = '0022997003333';
            $agent->password = bcrypt('secret');
            $agent->user_type = UserType::ADMIN;
            $agent->save();
        });
    }

    private function compteDe(int $societe, float $solde): Account
    {
        return Account::forceCreate([
            'company_id' => $societe, 'balance' => $solde,
            'account_holder_name' => 'Compte de ' . $societe,
            'account_no' => 'CPT-' . $societe . '-' . uniqid(),
        ]);
    }

    private function bulletinDe(int $societe): Salary
    {
        return Salary::forceCreate([
            'company_id' => $societe,
            'user_id' => User::where('company_id', $societe)->firstOrFail()->id,
            'account_id' => $this->compteDe($societe, 500000)->id,
            'month' => 'Decembre',
            'date' => now()->toDateString(),
            'amount' => 120000,
            'note' => 'Salaire de la societe ' . $societe,
        ]);
    }

    private function ligneDePaieDe(int $societe): SalaryGenerate
    {
        return SalaryGenerate::forceCreate([
            'company_id' => $societe,
            'user_id' => User::where('company_id', $societe)->firstOrFail()->id,
            'month' => 'Decembre',
            'amount' => 120000,
        ]);
    }
}
