<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\Expense;
use App\Models\Backend\FundTransfer;
use App\Models\Backend\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S60 — les sept écrans de recherche du module comptable.
 *
 * Suite de l'arriéré du filet S58 (32 → 25). Six des sept étaient déjà bornés ;
 * le septième portait **le piège du `orWhere`**, deuxième occurrence après S57 —
 * et sur l'écran **voisin** de celui que S58 avait corrigé.
 *
 * ```php
 * FundTransfer::companywise()
 *     ->whereHas('fromAccount', ...)
 *     ->orWhereHas('toAccount', ...)   // ← sort du périmètre
 * ```
 *
 * En SQL : `company_id = X AND fromAccount… OR toAccount…`. Un virement d'une
 * **autre** société dont le compte **destinataire** correspondait à la recherche
 * remontait, et `fund_transfer/index` rend le numéro de compte, la banque,
 * l'agence, le mobile, le nom et l'e-mail du titulaire — plus le **solde** et le
 * **solde d'ouverture** des deux comptes.
 *
 * ⚠️ **`companywise()` était déjà là.** Ce n'est pas une garde manquante, c'est
 * une garde qui ne couvrait pas ce qu'elle semblait couvrir.
 *
 * ## Le piège du marqueur de présence
 *
 * Ces écrans rendent une **liste déroulante de comptes** qui contient nos
 * propres comptes *indépendamment du résultat*. Un contrôle positif portant sur
 * `account_holder_name` ou `account_no` serait donc **creux** : il passerait au
 * vert avec zéro ligne rendue. Chaque marqueur de présence a été choisi parce
 * que **seule une ligne de résultat** peut le produire — `branch_name` et `note`
 * ne figurent dans aucune option.
 */
class AccountingSearchScopeTest extends TestCase
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

    /* ───────────── le défaut : le OR qui sort du périmètre ──────────────── */

    /**
     * Le virement d'en face correspond **uniquement par son compte
     * DESTINATAIRE** : c'est la branche `orWhereHas('toAccount')`, celle qui
     * s'échappait. Le nôtre correspond par son compte source, à l'intérieur du
     * périmètre. La recherche ne peut donc les confondre que si le `OR` fuit.
     */
    public function test_the_transfer_search_never_escapes_through_the_destination_account(): void
    {
        $mien = $this->virementDe(settings()->id, 'S60-MIEN', 'NEUTRE-1');
        $sien = $this->virementDe(self::AUTRE, 'NEUTRE-2', 'S60-SIEN');

        $page = $this->ecran('/admin/fund-transfer/specific/search', 'fund_transfer_read', ['search' => 'S60']);

        $this->assertStringContainsString('AGENCE-S60-MIEN', $page,
            'contrôle positif tombé : la recherche ne rend plus NOTRE virement, donc son '
            . 'silence sur celui d\'en face ne prouve rien');

        $this->assertStringNotContainsString('CPT-S60-SIEN', $page,
            'le `orWhereHas(\'toAccount\')` est ressorti du périmètre : un virement d\'une '
            . 'AUTRE société remonte par son compte destinataire');
    }

    /* ───────────── les six autres écrans du module, prouvés ─────────────── */

    public function test_the_transfer_filter_stays_inside_the_company(): void
    {
        $this->virementDe(settings()->id, 'S60-MIEN', 'NEUTRE-1');
        $this->virementDe(self::AUTRE, 'S60-SIEN', 'NEUTRE-2');

        $page = $this->ecran('/admin/fund-transfer/filter', 'fund_transfer_read', ['date' => $this->plage()]);

        $this->assertStringContainsString('AGENCE-S60-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CPT-S60-SIEN', $page,
            'le filtre des virements rend celui d\'une AUTRE société');
    }

    public function test_the_account_filter_stays_inside_the_company(): void
    {
        $this->compteDe(settings()->id, 'S60-MIEN');
        $this->compteDe(self::AUTRE, 'S60-SIEN');

        $page = $this->ecran('/admin/accounts/filter', 'account_read', ['holder_name' => 'S60']);

        $this->assertStringContainsString('S60-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('S60-SIEN', $page,
            'le filtre des comptes rend le compte bancaire d\'une AUTRE société');
    }

    public function test_the_bank_transaction_search_stays_inside_the_company(): void
    {
        $this->transactionDe(settings()->id, 'MIEN');
        $this->transactionDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/bank-transaction/specific/search', 'bank_transaction_read', ['search' => 'S60']);

        $this->assertStringContainsString('NOTE-S60-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('NOTE-S60-SIEN', $page,
            'la recherche des transactions rend celle d\'une AUTRE société');
    }

    public function test_the_bank_transaction_print_stays_inside_the_company(): void
    {
        $mien = $this->transactionDe(settings()->id, 'MIEN');
        $sien = $this->transactionDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/bank-transaction/filter/print', 'bank_transaction_read',
            ['ids' => [$mien->id, $sien->id]]);

        $this->assertStringContainsString('S60-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('S60-SIEN', $page,
            'l\'impression des transactions rend celle d\'une AUTRE société — '
            . 'même défaut que `fund-transfer/search/flter/print` en S58');
    }

    public function test_the_income_filter_stays_inside_the_company(): void
    {
        $this->recetteDe(settings()->id, 'MIEN');
        $this->recetteDe(self::AUTRE, 'SIEN');

        // ⚠️ `IncomeRepository::filter` traite `date` comme une date UNIQUE :
        // `where(['date' => date('Y-m-d', strtotime($request->date))])`. Une plage
        // « ...To... » y devient `1970-01-01` et ne rend AUCUNE ligne — le contrôle
        // positif tombait, et c'est lui qui l'a dit.
        $page = $this->ecran('/admin/income/filter', 'income_read', ['date' => now()->toDateString()]);

        // ⚠️ PAS le titre : `income/index` ne le rend que si `account_head_id == 3`.
        // C'est l'agence du compte rattaché qui est rendue a chaque ligne.
        $this->assertStringContainsString('AGENCE-CPTE-S60-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('AGENCE-CPTE-S60-SIEN', $page,
            'le filtre des recettes rend celle d\'une AUTRE société');
    }

    public function test_the_expense_filter_stays_inside_the_company(): void
    {
        $this->depenseDe(settings()->id, 'S60-MIEN');
        $this->depenseDe(self::AUTRE, 'S60-SIEN');

        // ⚠️ DEUX pièges ici, et c'est le SABOTAGE qui les a révélés — mon premier
        // jet était vert et ne prouvait rien :
        //
        //  1. `date` est une date UNIQUE (`strtotime`), comme pour les recettes :
        //     une plage « ...To... » devient `1970-01-01` et ne rend AUCUNE ligne ;
        //  2. la liste déroulante des comptes de cet écran rend
        //     `account_holder_name`, `account_no` ET `branch_name` de tous NOS
        //     comptes — un marqueur de présence pris sur le compte y apparaît
        //     donc même avec zéro résultat.
        //
        // Le marqueur est donc le nom de l'AUTEUR de la dépense : la liste ne
        // rend `$account->user->name` que pour les comptes en espèces
        // (`gateway == 1`), et les nôtres sont bancaires (`gateway == 2`).
        $page = $this->ecran('/admin/expense/filter', 'expense_read', ['date' => now()->toDateString()]);

        $this->assertStringContainsString('DEPENSIER-S60-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('DEPENSIER-S60-SIEN', $page,
            'le filtre des dépenses rend celle d\'une AUTRE société');
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    private function ecran(string $uri, string $droit, array $parametres): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = [$droit];
        $agent->save();

        $reponse = $this->actingAs($agent)->get(self::HOTE . $uri . '?' . http_build_query($parametres));

        // L'ancre : sans elle, un 403 de permission ou un 302 d'abonnement rend
        // toutes les assertions d'absence vraies pour rien (leçon S51).
        $reponse->assertOk();

        return $reponse->getContent();
    }

    private function plage(): string
    {
        return now()->subDay()->toDateString() . 'To' . now()->addDay()->toDateString();
    }

    /**
     * ⚠️ `branch_name` porte le marqueur de PRÉSENCE, et ce n'est pas cosmétique :
     * les listes déroulantes de ces écrans rendent `account_holder_name` et
     * `account_no` de tous NOS comptes, résultat ou pas. Seul `branch_name`
     * n'apparaît que dans une ligne rendue.
     */
    private function compteDe(int $societe, string $marque): Account
    {
        return Account::firstWhere('account_holder_name', $marque) ?? Account::forceCreate([
            'company_id' => $societe,
            'user_id' => $this->agentDe($societe)->id,
            'type' => 1,
            'gateway' => 2, // banque : c'est ce cas qui rend l'agence et le numéro
            'bank' => 1,
            'balance' => 500000,
            'opening_balance' => 0,
            'account_holder_name' => $marque,
            'account_no' => 'CPT-' . $marque,
            'branch_name' => 'AGENCE-' . $marque,
            'mobile' => '0022997000000',
            'account_type' => 1,
            'status' => 1,
        ]);
    }

    private function virementDe(int $societe, string $de, string $vers): FundTransfer
    {
        return FundTransfer::forceCreate([
            'company_id' => $societe,
            'from_account' => $this->compteDe($societe, $de)->id,
            'to_account' => $this->compteDe($societe, $vers)->id,
            'amount' => 15000,
            'date' => now()->toDateString(),
        ]);
    }

    private function transactionDe(int $societe, string $marque): BankTransaction
    {
        return BankTransaction::forceCreate([
            'company_id' => $societe,
            'account_id' => $this->compteDe($societe, 'S60-' . $marque)->id,
            'type' => 1,
            'amount' => 25000,
            'date' => now()->toDateString(),
            'note' => 'NOTE-S60-' . $marque,
        ]);
    }

    private function recetteDe(int $societe, string $marque): Income
    {
        return Income::forceCreate([
            'company_id' => $societe,
            'title' => 'TITRE-S60-' . $marque,
            'account_id' => $this->compteDe($societe, 'CPTE-S60-' . $marque)->id,
            'amount' => 40000,
            'date' => now()->toDateString(),
        ]);
    }

    private function depenseDe(int $societe, string $marque): Expense
    {
        $auteur = $this->agentDe($societe);
        $auteur->name = 'DEPENSIER-' . $marque;
        $auteur->save();

        return Expense::forceCreate([
            'company_id' => $societe,
            'title' => 'DEPENSE-' . $marque,
            'account_id' => $this->compteDe($societe, $marque)->id,
            'user_id' => $auteur->id,
            'amount' => 30000,
            'date' => now()->toDateString(),
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S60';
        $agent->email = 'agent.s60.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
