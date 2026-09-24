<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\FundTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S58 — la surface de RECHERCHE : un GET sans paramètre d'URL.
 *
 * Les cinq filets d'isolation avaient tous le même point aveugle, et il est
 * structurel, pas accidentel :
 *
 * | filet | ce qu'il énumère | pourquoi il ne voit pas cette route |
 * |---|---|---|
 * | `IsolationCoverageTest` | l'API v10 | route web |
 * | `WebIsolationCoverageTest` | les routes **à paramètre d'URL** | celle-ci n'en a aucun |
 * | `BodyIdentifierCoverageTest` | `POST`/`PUT`/`PATCH`/`DELETE` | celle-ci est un `GET` |
 * | `WebAdminPermissionCoverageTest` | les droits `admin/*` | le droit était bien exigé |
 * | `OffRequestScopeCoverageTest` | la surface **hors** requête | celle-ci est dans une requête |
 *
 * Une route qui est à la fois un `GET`, sans paramètre d'URL, et dont
 * l'identifiant voyage dans la **chaîne de requête** tombait donc entre les
 * cinq. Mesure : **203** routes de ce genre, dont **46** lisent un champ de la
 * requête.
 *
 * Ce fichier tient les portées PROUVÉES de cette surface. Son recensement vit
 * dans `SearchSurfaceCoverageTest`.
 *
 * ⚠️ **Ce que ce test ne dit pas.** Il ne dit pas que les 46 sont saines. Il
 * prouve la portée de celles qui y figurent, une par une.
 */
class SearchSurfaceScopeTest extends TestCase
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

    /**
     * `GET admin/fund-transfer/search/flter/print` — le défaut que ce lot a
     * trouvé, et le seul des 46.
     *
     * La requête était `FundTransfer::whereIn('id', $request->ids)`, sans
     * périmètre, quand son jumeau `bankTransactionPrint()` écrit
     * `BankTransaction::companywise()->whereIn('id', $request->ids)`.
     *
     * **Le contrôle positif est dans le MÊME appel** : on demande l'impression
     * des DEUX virements à la fois, le sien et celui d'en face. Le test exige
     * que le numéro de compte du sien soit rendu, et que celui d'en face ne le
     * soit pas. Un vert creux devient impossible :
     *
     * - si la garde saute, le numéro d'en face apparaît → l'absence tombe ;
     * - si la vue cesse de rendre quoi que ce soit (permission, abonnement,
     *   relation nulle), la présence tombe **avant** que l'absence ne puisse
     *   passer pour une preuve.
     *
     * C'est la leçon de S53 : pour prouver une garde, il faut que le chemin NON
     * gardé réussisse.
     */
    public function test_the_transfer_print_screen_refuses_a_foreign_identifier(): void
    {
        $mien = $this->virementDe(settings()->id, 'MIEN');
        $sien = $this->virementDe(self::AUTRE, 'SIEN');

        $page = $this->imprimer([$mien->id, $sien->id]);

        $this->assertStringContainsString('CPT-MIEN-DE', $page,
            'le contrôle positif est tombé : la page ne rend plus le virement de la société '
            . 'connectée, donc son silence sur celui d\'en face ne prouve rien');

        $this->assertStringNotContainsString('CPT-SIEN-DE', $page,
            'l\'écran d\'impression rend le compte bancaire d\'une AUTRE société : '
            . '`FundTransfer::whereIn(\'id\', $request->ids)` a reperdu son `companywise()`');
    }

    /**
     * Et l'autre bout du virement : la vue rend le compte SOURCE et le compte
     * DESTINATAIRE. Une garde qui ne tiendrait qu'un des deux laisserait fuir
     * l'autre — et c'est exactement la famille de défauts des lots S45 à S53,
     * où la ressource était gardée et le second identifiant ne l'était pas.
     */
    public function test_neither_end_of_a_foreign_transfer_is_rendered(): void
    {
        $mien = $this->virementDe(settings()->id, 'MIEN');
        $sien = $this->virementDe(self::AUTRE, 'SIEN');

        $page = $this->imprimer([$mien->id, $sien->id]);

        $this->assertStringContainsString('CPT-MIEN-VERS', $page,
            'le contrôle positif est tombé sur le compte destinataire');

        $this->assertStringNotContainsString('CPT-SIEN-VERS', $page,
            'le compte DESTINATAIRE d\'un virement étranger est rendu');
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    private function imprimer(array $ids): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['fund_transfer_read'];
        $agent->save();

        $reponse = $this->actingAs($agent)->get(
            self::HOTE . '/admin/fund-transfer/search/flter/print?'
            . http_build_query(['ids' => $ids]),
        );

        // L'ancre : sans elle, un 302 d'abonnement ou un 403 de permission
        // rendrait les deux assertions d'absence vraies pour rien (leçon S51).
        $reponse->assertOk();

        return $reponse->getContent();
    }

    private function compteDe(int $societe, string $marque): Account
    {
        $titulaire = $this->agentDe($societe);

        return Account::forceCreate([
            'company_id' => $societe,
            'user_id' => $titulaire->id,
            'type' => 1,
            'gateway' => 2, // banque : c'est ce cas que la vue rend avec le numéro
            'bank' => 1,
            'balance' => 500000,
            'account_holder_name' => 'Titulaire ' . $societe,
            'account_no' => 'CPT-' . $marque,
            'branch_name' => 'Agence ' . $societe,
            'opening_balance' => 0,
            'mobile' => '0022997000000',
            'account_type' => 1,
            'status' => 1,
        ]);
    }

    private function virementDe(int $societe, string $marque): FundTransfer
    {
        return FundTransfer::forceCreate([
            'company_id' => $societe,
            'from_account' => $this->compteDe($societe, $marque . '-DE')->id,
            'to_account' => $this->compteDe($societe, $marque . '-VERS')->id,
            'amount' => 15000,
            'date' => now()->toDateString(),
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S58';
        $agent->email = 'agent.s58.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
