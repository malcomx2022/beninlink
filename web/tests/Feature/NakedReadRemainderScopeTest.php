<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\Config;
use App\Models\User;
use App\Repositories\FundTransfer\FundTransferInterface;
use App\Repositories\Income\IncomeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S64 — les neuf lectures nues que l'instrument désignait encore.
 *
 * L'arriéré du filet S58 étant clos (S63), le chantier restant était la liste de
 * `instrument-lectures-nues.py` : **35 occurrences**. Ce lot en ferme **neuf**,
 * et parmi elles la plus lourde de toute la série.
 */
class NakedReadRemainderScopeTest extends TestCase
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

    /* ───────── 1. le virement : de l'argent SORTI d'un compte d'en face ──── */

    /**
     * Le défaut le plus lourd de la série.
     *
     * `FundTransferRepository::store()` lisait **nûment** `from_account` et
     * `to_account`, puis débitait l'un et créditait l'autre. Un administrateur
     * pouvait donc **sortir de l'argent du compte bancaire d'un concurrent** — et
     * la ligne de virement enregistrée portait NOTRE `company_id`, donc la
     * victime ne la voyait même pas dans son journal.
     *
     * ⚠️ La carte des contreparties de S48 ne couvrait pas ce cas : elle connaît
     * `account_id`, pas `from_account` / `to_account`.
     */
    public function test_a_transfer_can_never_touch_a_foreign_account(): void
    {
        $mien = $this->compteDe(settings()->id, 900000);
        $sien = $this->compteDe(self::AUTRE, 900000);

        $this->actingAs($this->agentDe(settings()->id));

        $depot = app(FundTransferInterface::class);

        $this->assertSame(1, $depot->store($this->requete([
            'from_account' => $mien->id, 'to_account' => $this->compteDe(settings()->id, 10)->id,
        ])), 'contrôle positif tombé : un virement ENTRE NOS comptes ne passe plus');

        foreach ([
            'le compte SOURCE est celui d\'en face' => ['from_account' => $sien->id, 'to_account' => $mien->id],
            'le compte DESTINATAIRE est celui d\'en face' => ['from_account' => $mien->id, 'to_account' => $sien->id],
        ] as $cas => $champs) {
            $soldeAvant = $sien->fresh()->balance;

            $this->assertFalse($depot->store($this->requete($champs)), $cas . ' : le virement a été accepté');
            $this->assertEquals($soldeAvant, $sien->fresh()->balance,
                $cas . ' : le solde du compte d\'une AUTRE société a bougé');
        }
    }

    /* ───────── 2. l'AJAX de solde : un objet d'en face rendu en JSON ─────── */

    public function test_the_balance_ajax_resolves_nothing_of_another_company(): void
    {
        $this->actingAs($this->agentDe(settings()->id));

        $depot = app(IncomeInterface::class);

        $mien = $this->marchandDe(settings()->id);
        $sien = $this->marchandDe(self::AUTRE);
        $this->assertNotNull($depot->hubCheck($this->requete(['from' => 1, 'merchant' => $mien->id])),
            'contrôle positif tombé sur le marchand');
        $this->assertNull($depot->hubCheck($this->requete(['from' => 1, 'merchant' => $sien->id])),
            'l\'AJAX rend le MARCHAND d\'une autre société');

        $monLivreur = $this->livreurDe(settings()->id);
        $sonLivreur = $this->livreurDe(self::AUTRE);
        $this->assertNotNull($depot->hubCheck($this->requete(['from' => 2, 'deliveryman' => $monLivreur->id])),
            'contrôle positif tombé sur le livreur');
        $this->assertNull($depot->hubCheck($this->requete(['from' => 2, 'deliveryman' => $sonLivreur->id])),
            'l\'AJAX rend le LIVREUR d\'une autre société');

        $monHub = $this->entrepotDe(settings()->id);
        $sonHub = $this->entrepotDe(self::AUTRE);
        $this->assertNotNull($depot->hubCheck($this->requete(['from' => 7, 'hub' => $monHub->id])),
            'contrôle positif tombé sur l\'entrepôt');
        $this->assertNull($depot->hubCheck($this->requete(['from' => 7, 'hub' => $sonHub->id])),
            'l\'AJAX rend l\'ENTREPÔT d\'une autre société');
    }

    /* ───────── 3. la bascule d'un réglage d'une autre société ────────────── */

    /**
     * ⚠️ Cette route est un `POST` : `BodyIdentifierCoverageTest` aurait dû la
     * voir. Elle lui a échappé parce que son identifiant s'appelle **`key`**, pas
     * `*_id` — même angle mort que le terme de recherche qui a donné le filet S58.
     */
    public function test_a_delivery_type_of_another_company_cannot_be_toggled(): void
    {
        $sien = Config::forceCreate([
            'company_id' => self::AUTRE, 'key' => 'reglage_s64', 'value' => Status::ACTIVE,
        ]);
        $mien = Config::forceCreate([
            'company_id' => settings()->id, 'key' => 'reglage_s64', 'value' => Status::ACTIVE,
        ]);

        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['delivery_type_status_change'];
        $agent->save();

        $this->actingAs($agent)
            ->post(self::HOTE . '/admin/delivery-type/status', ['key' => 'reglage_s64'])
            ->assertOk();

        $this->assertEquals(Status::INACTIVE, $mien->fresh()->value,
            'contrôle positif tombé : NOTRE réglage n\'a pas basculé');
        $this->assertEquals(Status::ACTIVE, $sien->fresh()->value,
            'le réglage d\'une AUTRE société a basculé');
    }

    /* ───────── 4. les deux oracles qui précèdent leur dépôt ──────────────── */

    public function test_the_cash_handover_never_reads_a_foreign_deliveryman(): void
    {
        // ⚠️ Le solde du livreur d'en face doit etre celui qui DECLENCHE
        // l'avertissement (`current_balance == 0`). Avec -50000, la condition
        // etait fausse des deux cotes et le test passait sans rien mesurer : le
        // sabotage est reste VERT et l'a dit.
        $sien = $this->livreurDe(self::AUTRE);
        $sien->current_balance = 0;
        $sien->save();

        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['cash_received_from_delivery_man_create'];
        $agent->save();

        $reponse = $this->actingAs($agent)->post(self::HOTE . '/admin/hub/cash-received-deliveryman/store', [
            'delivery_man_id' => $sien->id, 'amount' => 1000,
            'date' => now()->toDateString(), 'account_id' => $this->compteDe(settings()->id, 900000)->id,
        ]);

        $reponse->assertRedirect();

        $this->assertStringNotContainsString(__('account.not_enough_balance'), $this->messagesToastr(),
            'la réponse renseigne sur le SOLDE d\'un livreur d\'une autre société');
        $this->assertStringContainsString(__('account.error_msg'), $this->messagesToastr(),
            'le livreur étranger doit être refusé par un message de refus, pas ignoré');
    }

    /**
     * S65 — la REPRISE d'un encaissement portait le même oracle que le dépôt, et
     * une seconde lecture nue : la remise elle-même. Mon correctif de S64
     * n'avait attrapé que `store()` — c'est l'instrument qui l'a dit, en
     * continuant de désigner `update()` après le lot.
     */
    public function test_the_cash_handover_update_never_reads_a_foreign_record(): void
    {
        // ⚠️ MEME PIEGE QU'EN S64, et je l'ai refait : le solde doit etre celui
        // qui DECLENCHE l'avertissement. `$cashReceivedAmount = solde - remise`
        // doit valoir plus que `-$request->amount` ; avec un solde de 0 et une
        // remise de 5000 il vaut -5000, la condition est fausse des DEUX cotes,
        // et le sabotage restait vert.
        $sien = $this->livreurDe(self::AUTRE);
        $sien->current_balance = 10000;
        $sien->save();

        // ⚠️ `user_id` et `hub_id` sont indispensables : sans eux le trait
        // d'enregistrement d'activite leve sur une relation nulle, et le test
        // tomberait pour une raison qui n'est pas la portee.
        $saRemise = \App\Models\CashReceivedFromDeliveryman::forceCreate([
            'company_id' => self::AUTRE,
            'user_id' => $sien->user_id,
            'hub_id' => $sien->user->hub_id,
            'delivery_man_id' => $sien->id,
            'account_id' => $this->compteDe(self::AUTRE, 900000)->id,
            'amount' => 5000,
            'date' => now(),
        ]);

        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['cash_received_from_delivery_man_update'];
        $agent->save();

        $this->actingAs($agent)->put(self::HOTE . '/admin/hub/cash-received-deliveryman/update', [
            'id' => $saRemise->id, 'delivery_man_id' => $sien->id, 'amount' => 1000,
            'date' => now()->toDateString(), 'account_id' => $this->compteDe(settings()->id, 900000)->id,
        ])->assertRedirect();

        $this->assertStringNotContainsString(__('account.not_enough_balance'), $this->messagesToastr(),
            'la reprise renseigne sur le SOLDE d\'un livreur d\'une autre société');
        $this->assertStringContainsString(__('account.error_msg'), $this->messagesToastr(),
            'la remise étrangère doit être refusée par un message de refus');
    }

    public function test_the_salary_precheck_never_reads_a_foreign_payslip(): void
    {
        $sonSalarie = $this->agentDe(self::AUTRE);
        SalaryGenerate::forceCreate([
            'company_id' => self::AUTRE, 'user_id' => $sonSalarie->id,
            'month' => now()->format('Y-m'), 'amount' => 100000,
            'status' => 1, 'due' => 0, 'advance' => 0,
        ]);

        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['salary_generate_create'];
        $agent->save();

        $this->actingAs($agent)->post(self::HOTE . '/admin/salary/salary-generate/store', [
            'user_id' => $sonSalarie->id, 'month' => now()->format('Y-m'), 'amount' => 100000,
        ])->assertRedirect();

        $this->assertStringNotContainsString('Already salary generated', $this->messagesToastr(),
            'la réponse révèle qu\'un bulletin EXISTE chez une autre société : oracle d\'existence');
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    private function messagesToastr(): string
    {
        return json_encode(session('toastr::messages') ?? [], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function requete(array $champs): Request
    {
        return new Request($champs + ['amount' => 500, 'date' => now()->toDateString()]);
    }

    private function compteDe(int $societe, int $solde): Account
    {
        return Account::forceCreate([
            'company_id' => $societe, 'user_id' => $this->agentDe($societe)->id,
            'type' => 1, 'gateway' => 2, 'bank' => 1,
            'balance' => $solde, 'opening_balance' => 0,
            'account_holder_name' => 'Titulaire S64 ' . $societe,
            'account_no' => 'CPT-S64-' . $societe . '-' . $solde,
            'branch_name' => 'Agence', 'mobile' => '0022997000000',
            'account_type' => 1, 'status' => 1,
        ]);
    }

    private function entrepotDe(int $societe): Hub
    {
        return Hub::firstWhere('name', 'Entrepot S64 ' . $societe) ?? Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot S64 ' . $societe,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);
    }

    private function livreurDe(int $societe): DeliveryMan
    {
        $u = $this->agentDe($societe);
        $u->user_type = UserType::DELIVERYMAN;
        $u->hub_id = $this->entrepotDe($societe)->id;
        $u->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'status' => Status::ACTIVE, 'delivery_charge' => 500, 'current_balance' => 0,
        ]);
    }

    private function marchandDe(int $societe): Merchant
    {
        $u = $this->agentDe($societe);
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME S64 ' . $societe, 'current_balance' => 0,
            'opening_balance' => 0, 'wallet_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S64';
        $agent->email = 'agent.s64.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
