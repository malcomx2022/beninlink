<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserType;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S83 — l'écran web de modification d'une demande de retrait.
 *
 * Lu au passage en S81 : `PaymentRequestController::update()` relisait la
 * demande par l'identifiant du **marchand**, pas par le `id` que le formulaire
 * envoie. Le dépôt cherchait donc, parmi les demandes du marchand, celle dont
 * l'identifiant vaut le sien — le plus souvent rien, et l'écran tombait en page
 * d'erreur ; par coïncidence d'identifiants, une **autre** de ses demandes
 * décidait ; jamais celle qu'il avait ouverte.
 *
 * Le dépôt, lui, lisait déjà `$request->id` et le gardait (S7 : `ownedPayments()`,
 * S81 : `compteDeVersementEtranger()`) : la frontière tenait, et l'écriture
 * visait la bonne ligne. Ce que le socle décidait sur la mauvaise demande, c'est
 * **si** la modification passait — son statut. Trois propriétés, par la route :
 *
 *  1. sans coïncidence d'identifiants, l'écran répond et modifie la demande
 *     ouverte — le socle tombait en page d'erreur ;
 *  2. la demande d'un autre marchand répond 404 et ne bouge pas ;
 *  3. une demande déjà TRAITÉE ne se modifie plus, même quand une autre demande
 *     EN COURS porte par hasard l'identifiant du marchand — le socle lisait le
 *     statut de celle-là et laissait passer.
 *
 * ⚠️ Le premier jet de ce test est tombé sur le hasard même du socle : marchand
 * n° 2, deuxième demande n° 2, et le sabotage restait vert sur la propriété 1.
 * D'où les deux marchands de bourrage dans `setUp()` et les gardes de fixture.
 */
class MerchantPayoutRequestEditTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private User $moi;
    private Merchant $monMarchand;
    private MerchantPayment $monCompte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        // Deux marchands de bourrage : l'identifiant du mien n'est celui d'aucune
        // demande des tests 1 et 2, et le test 3 fabrique la coïncidence à dessein.
        $this->marchandDe('x');
        $this->marchandDe('y');
        [$this->moi, $this->monMarchand] = $this->marchandDe('a');
        $this->monCompte = $this->compteDeVersementDe($this->monMarchand, '22997000001');
        $this->actingAs($this->moi);
    }

    public function test_editing_a_payout_request_answers_and_changes_the_one_that_was_opened(): void
    {
        $autre = $this->demandeDe($this->monMarchand, 10000);
        $ouverte = $this->demandeDe($this->monMarchand, 40000);
        $this->assertNotContains($this->monMarchand->id, [$autre->id, $ouverte->id],
            'fixture : une demande porte l\'identifiant du marchand, le socle ne tomberait plus en erreur ici');

        $this->put(self::HOTE . '/merchant/payment-request/update', [
            'id' => $ouverte->id, 'amount' => 15000, 'merchant_account' => $this->monCompte->id, 'description' => 'Modifiée',
        ])->assertRedirect(self::HOTE . '/merchant/payment-request/index');

        $this->assertSame(15000, (int) $ouverte->fresh()->amount, 'la demande OUVERTE n\'a pas été modifiée');
        $this->assertSame(10000, (int) $autre->fresh()->amount, 'une AUTRE demande a été modifiée');
    }

    public function test_editing_a_neighbours_payout_request_is_refused(): void
    {
        [, $sonMarchand] = $this->marchandDe('b');
        $sonCompte = $this->compteDeVersementDe($sonMarchand, '22997000002');
        $sienne = $this->demandeDe($sonMarchand, 30000, $sonCompte);

        $this->put(self::HOTE . '/merchant/payment-request/update', [
            'id' => $sienne->id, 'amount' => 1, 'merchant_account' => $this->monCompte->id, 'description' => 'Détournée',
        ])->assertNotFound();

        $this->assertSame(30000, (int) $sienne->fresh()->amount);
        $this->assertSame($sonCompte->id, (int) $sienne->fresh()->merchant_account);
    }

    public function test_a_processed_payout_request_cannot_be_edited_even_when_another_one_carries_the_merchant_s_id(): void
    {
        // On fabrique la coïncidence : une demande EN COURS dont l'identifiant est
        // celui du marchand. C'est elle que le socle relisait — et son statut
        // laissait passer la modification d'une demande déjà traitée.
        $enCours = null;
        while (($enCours = $this->demandeDe($this->monMarchand, 10000))->id < $this->monMarchand->id) {
        }
        $this->assertSame($this->monMarchand->id, $enCours->id,
            'fixture : impossible de faire coïncider une demande EN COURS avec l\'identifiant du marchand');

        $traitee = $this->demandeDe($this->monMarchand, 20000, null, ApprovalStatus::PROCESSED);

        $this->put(self::HOTE . '/merchant/payment-request/update', [
            'id' => $traitee->id, 'amount' => 5000, 'merchant_account' => $this->monCompte->id, 'description' => 'Trop tard',
        ])->assertRedirect();

        $this->assertSame(20000, (int) $traitee->fresh()->amount,
            'une demande déjà TRAITÉE a été modifiée : le statut lu est celui d\'une AUTRE demande');
        $this->assertSame(10000, (int) $enCours->fresh()->amount);
    }

    /* ─────────────────────────────── fixtures ───────────────────────────── */

    /** @return array{0: User, 1: Merchant} */
    private function marchandDe(string $suffixe): array
    {
        $utilisateur = new User();
        $utilisateur->company_id = settings()->id;
        $utilisateur->name = 'Marchand ' . $suffixe;
        $utilisateur->email = 'marchand.s83.' . $suffixe . '@example.test';
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

    private function demandeDe(Merchant $marchand, int $montant, ?MerchantPayment $compte = null, int $statut = ApprovalStatus::PENDING): Payment
    {
        return Payment::forceCreate([
            'company_id' => $marchand->company_id, 'merchant_id' => $marchand->id,
            'merchant_account' => ($compte ?? $this->monCompte)->id, 'amount' => $montant,
            'status' => $statut, 'created_by' => UserType::MERCHANT,
        ]);
    }
}
