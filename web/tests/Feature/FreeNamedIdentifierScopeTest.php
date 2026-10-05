<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\FundTransfer;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Models\User;
use App\Repositories\FundTransfer\FundTransferInterface;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S81 (T5) — les identifiants au NOM LIBRE.
 *
 * `BodyIdentifierCoverageTest` reconnaît une convention de nom : `id`, `*_id`,
 * `key`, `slug`. Un identifiant qui s'appelle `account`, `from_account`,
 * `merchant`, `hub` ou `editid` lui échappait — c'était écrit depuis S65, et
 * consigné T5. Ce lot mesure les dix noms que le socle emploie, les donne au
 * filet, et prouve ici les routes qu'il fait entrer et qu'aucun test ne tenait.
 *
 * ## Les deux défauts que la mesure a trouvés
 *
 * 1. **`FundTransferRepository::update()`** : S64 avait gardé `store()` (on sortait
 *    de l'argent du compte d'un concurrent), pas `update()`, qui lit les mêmes
 *    `from_account` / `to_account` **nus** onze lignes sous sa garde S30 sur le
 *    virement. Modifier un virement de la maison déplaçait donc toujours les
 *    soldes d'en face. Et le filet des lectures nues (S65) l'absolvait : il tenait
 *    pour garde toute occurrence du nom entre la garde et la lecture — ici
 *    `$fund_transfer->from_account`, une colonne, pas la requête.
 * 2. **`merchant/payment-request/store`** (panneau web) : la demande de retrait
 *    écrivait `merchant_account` tel quel. Un marchand pouvait nommer le compte
 *    Mobile Money ou bancaire d'un **autre** marchand. L'API l'avait fermé dès S7
 *    (`ownsAccount()`) ; le panneau web est un autre contrôleur.
 *
 * Les deux filtres (`admin/bank-transaction/filter`, `merchant/accounts/
 * account-transaction-filter`) étaient bornés ; ils sont **prouvés** parce que lu
 * n'est pas prouvé (règle S58), par un marqueur que seule une ligne de résultat
 * peut produire (règle S60).
 */
class FreeNamedIdentifierScopeTest extends TestCase
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

    /* ───────── 1. le virement MODIFIÉ : les comptes d'en face, encore ────── */

    /**
     * Le pendant de `NakedReadRemainderScopeTest::test_a_transfer_can_never_touch_
     * a_foreign_account` (S64), sur `update()`. Le virement est À NOUS — c'est ce
     * que la garde S30 vérifie, et ce qui rendait le défaut invisible au test de
     * `BackOfficeMoneyScopeTest`, qui ne rejoue qu'un virement d'en face.
     */
    public function test_an_updated_transfer_can_never_touch_a_foreign_account(): void
    {
        $mien = $this->compteDe(settings()->id, 900000);
        $mienAussi = $this->compteDe(settings()->id, 900000);
        $sien = $this->compteDe(self::AUTRE, 900000);

        $this->actingAs($this->agentDe(settings()->id));

        $depot = app(FundTransferInterface::class);

        $this->assertSame(1, $depot->store($this->requete(['from_account' => $mien->id, 'to_account' => $mienAussi->id])));
        $virement = FundTransfer::companywise()->orderByDesc('id')->firstOrFail();

        $this->assertSame(1, $depot->update($virement->id, $this->requete([
            'from_account' => $mienAussi->id, 'to_account' => $mien->id,
        ])), 'contrôle positif tombé : la modification d\'un virement ENTRE NOS comptes ne passe plus');

        foreach ([
            'le nouveau compte SOURCE est celui d\'en face' => ['from_account' => $sien->id, 'to_account' => $mien->id],
            'le nouveau compte DESTINATAIRE est celui d\'en face' => ['from_account' => $mien->id, 'to_account' => $sien->id],
        ] as $cas => $champs) {
            $soldes = Account::withoutGlobalScopes()->pluck('balance', 'id')->map(fn ($s) => (int) $s)->all();
            $ecritures = BankTransaction::withoutGlobalScopes()->count();

            $this->assertFalse($depot->update($virement->id, $this->requete($champs)), $cas . ' : la modification a été acceptée');

            $this->assertSame($soldes, Account::withoutGlobalScopes()->pluck('balance', 'id')->map(fn ($s) => (int) $s)->all(),
                $cas . ' : des soldes ont bougé — le refus doit précéder la transaction, pas la défaire');
            $this->assertSame($ecritures, BankTransaction::withoutGlobalScopes()->count(),
                $cas . ' : des écritures de banque ont été effacées ou ajoutées');
            $this->assertNotEquals($sien->id, (int) $virement->fresh()->from_account, $cas . ' : le virement nomme le compte d\'en face');
            $this->assertNotEquals($sien->id, (int) $virement->fresh()->to_account, $cas . ' : le virement nomme le compte d\'en face');
        }
    }

    /* ───────── 2. le filtre des transactions de banque par `account` ──────── */

    /**
     * La route `POST` jumelle des écrans de recherche de S60. Le marqueur est la
     * `note` : la liste déroulante des comptes rend `account_no` et
     * `account_holder_name` de tous NOS comptes, résultat ou pas (piège S60).
     */
    public function test_the_bank_transaction_filter_by_account_stays_inside_the_company(): void
    {
        $mien = $this->compteDe(settings()->id, 500000);
        $sien = $this->compteDe(self::AUTRE, 500000);
        $this->transactionDe(settings()->id, $mien, 'MIEN');
        $this->transactionDe(self::AUTRE, $sien, 'SIEN');

        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['bank_transaction_read'];
        $agent->save();
        $this->actingAs($agent);

        $page = $this->post(self::HOTE . '/admin/bank-transaction/filter', ['account' => $mien->id])->assertOk()->getContent();
        $this->assertStringContainsString('NOTE-S81-MIEN', $page, 'contrôle positif tombé : le filtre ne rend plus NOTRE transaction');

        $page = $this->post(self::HOTE . '/admin/bank-transaction/filter', ['account' => $sien->id])->assertOk()->getContent();
        $this->assertStringNotContainsString('NOTE-S81-SIEN', $page,
            'le filtre par compte rend la transaction d\'une AUTRE société quand on nomme son compte');
    }

    /* ───────── 3. la demande de retrait vers le compte d'un AUTRE marchand ── */

    /**
     * Deux marchands de la MÊME société, comme `MerchantPanelWebScopeTest` : la
     * frontière ici est le marchand. Les deux appels répondent par une
     * redirection ; c'est la base qui dit lequel a écrit.
     */
    public function test_a_payout_request_never_names_a_neighbours_account(): void
    {
        [$moi, $monMarchand] = $this->marchandDe('a');
        [, $sonMarchand] = $this->marchandDe('b');
        $monCompte = $this->compteDeVersementDe($monMarchand, '22997000001');
        $sonCompte = $this->compteDeVersementDe($sonMarchand, '22997000002');

        $this->actingAs($moi);

        $this->post(self::HOTE . '/merchant/payment-request/store', ['amount' => 5000, 'merchant_account' => $monCompte->id, 'description' => 'Retrait'])
            ->assertRedirect();
        $this->assertSame(1, Payment::where('merchant_account', $monCompte->id)->count(),
            'contrôle positif tombé : une demande vers MON compte n\'est plus enregistrée');

        $this->post(self::HOTE . '/merchant/payment-request/store', ['amount' => 5000, 'merchant_account' => $sonCompte->id, 'description' => 'Retrait'])
            ->assertRedirect();
        $this->assertSame(0, Payment::where('merchant_account', $sonCompte->id)->count(),
            'une demande de retrait vers le compte d\'un AUTRE marchand a été enregistrée');

        // Et la modification : MA demande, SON compte.
        $demande = Payment::where('merchant_id', $monMarchand->id)->firstOrFail();
        $this->assertFalse((bool) app(PaymentRequestInterface::class)->update(new Request([
            'id' => $demande->id, 'amount' => 5000, 'merchant_account' => $sonCompte->id, 'description' => 'Détourné',
        ])), 'la modification d\'une demande vers le compte d\'un AUTRE marchand a été acceptée');
        $this->assertSame($monCompte->id, (int) $demande->fresh()->merchant_account);
    }

    /* ───────── 4. le filtre des transactions du marchand par `account` ────── */

    public function test_the_merchant_transaction_filter_by_account_stays_with_the_merchant(): void
    {
        [$moi, $monMarchand] = $this->marchandDe('a');
        [, $sonMarchand] = $this->marchandDe('b');
        $monCompte = $this->compteDeVersementDe($monMarchand, '22997000001');
        $sonCompte = $this->compteDeVersementDe($sonMarchand, '22997000002');
        $this->demandeDe($monMarchand, $monCompte, 'TX-S81-MIEN');
        $this->demandeDe($sonMarchand, $sonCompte, 'TX-S81-SIEN');

        $this->actingAs($moi);

        $page = $this->post(self::HOTE . '/merchant/accounts/account-transaction-filter', ['account' => $monCompte->id])->assertOk()->getContent();
        $this->assertStringContainsString('TX-S81-MIEN', $page, 'contrôle positif tombé : le filtre ne rend plus MA transaction');

        $page = $this->post(self::HOTE . '/merchant/accounts/account-transaction-filter', ['account' => $sonCompte->id])->assertOk()->getContent();
        $this->assertStringNotContainsString('TX-S81-SIEN', $page,
            'le filtre rend la transaction d\'un AUTRE marchand quand on nomme son compte');
        $this->assertStringNotContainsString('22997000002', $page, 'le numéro Mobile Money d\'un AUTRE marchand est rendu');
    }

    /* ─────────────────────────────── fixtures ───────────────────────────── */

    private function requete(array $champs): Request
    {
        return new Request($champs + ['amount' => 500, 'date' => now()->toDateString()]);
    }

    private function compteDe(int $societe, int $solde): Account
    {
        $n = Account::withoutGlobalScopes()->count();

        return Account::forceCreate([
            'company_id' => $societe, 'user_id' => $this->agentDe($societe)->id,
            'type' => 1, 'gateway' => 2, 'bank' => 1,
            'balance' => $solde, 'opening_balance' => 0,
            'account_holder_name' => 'Titulaire S81 ' . $societe,
            'account_no' => 'CPT-S81-' . $societe . '-' . $n,
            'branch_name' => 'Agence', 'mobile' => '0022997000000',
            'account_type' => 1, 'status' => 1,
        ]);
    }

    private function transactionDe(int $societe, Account $compte, string $marque): BankTransaction
    {
        return BankTransaction::forceCreate([
            'company_id' => $societe, 'account_id' => $compte->id,
            'type' => 1, 'amount' => 25000, 'date' => now()->toDateString(),
            'note' => 'NOTE-S81-' . $marque,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S81';
        $agent->email = 'agent.s81.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    /** @return array{0: User, 1: Merchant} */
    private function marchandDe(string $suffixe): array
    {
        $utilisateur = new User();
        $utilisateur->company_id = settings()->id;
        $utilisateur->name = 'Marchand ' . $suffixe;
        $utilisateur->email = 'marchand.s81.' . $suffixe . '@example.test';
        $utilisateur->mobile = '002299700' . ord($suffixe);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        $marchand = Merchant::forceCreate([
            'company_id' => settings()->id, 'user_id' => $utilisateur->id,
            'business_name' => 'PME ' . $suffixe, 'current_balance' => 100000,
        ]);

        return [$utilisateur->fresh(), $marchand];
    }

    private function compteDeVersementDe(Merchant $marchand, string $numero): MerchantPayment
    {
        return MerchantPayment::forceCreate([
            'merchant_id' => $marchand->id, 'payment_method' => 'mobile',
            'holder_name' => 'Titulaire ' . $marchand->business_name,
            'mobile_company' => 'MTN MoMo', 'mobile_no' => $numero, 'account_type' => 'Personnel',
        ]);
    }

    private function demandeDe(Merchant $marchand, MerchantPayment $compte, string $transaction): Payment
    {
        return Payment::forceCreate([
            'company_id' => $marchand->company_id, 'merchant_id' => $marchand->id,
            'merchant_account' => $compte->id, 'amount' => 20000, 'transaction_id' => $transaction,
        ]);
    }
}
