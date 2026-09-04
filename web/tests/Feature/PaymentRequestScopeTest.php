<?php

namespace Tests\Feature;

use App\Enums\Merchant_panel\PaymentMethod;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Une demande de retrait (payout) ne peut viser qu'un compte de reglement du
 * marchand connecte.
 *
 * Le socle enregistrait `merchant_account` tel quel : un marchand pouvait
 * designer le compte d'un concurrent, puis `PaymentResource` lui en rendait le
 * detail (titulaire, numero, banque) dans sa propre liste de demandes. Releve
 * en branchant l'ecran de retrait de mobile/.
 */
class PaymentRequestScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;
    private MerchantPayment $monCompte;
    private MerchantPayment $compteDuVoisin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();
        $this->merchant->current_balance = 100000;
        $this->merchant->save();

        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin@example.test';
        $autreUtilisateur->mobile = '0022997000009';
        $autreUtilisateur->unique_id = 'U-VOISIN';
        $autreUtilisateur->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $autreUtilisateur->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->save();

        $this->monCompte = $this->compteMobileMoney($this->merchant, '22997000001');
        $this->compteDuVoisin = $this->compteMobileMoney($voisin, '22997000009');

        Sanctum::actingAs($this->merchant->user, ['merchant']);
    }

    private function compteMobileMoney(Merchant $merchant, string $numero): MerchantPayment
    {
        $compte = new MerchantPayment();
        $compte->forceFill([
            'merchant_id' => $merchant->id,
            'payment_method' => PaymentMethod::mobile,
            'holder_name' => 'Titulaire ' . $merchant->merchant_unique_id,
            'mobile_company' => 'MTN MoMo',
            'mobile_no' => $numero,
            'account_type' => 'Personnel',
        ])->save();

        return $compte;
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    public function test_un_retrait_vers_le_compte_d_un_autre_marchand_est_refuse(): void
    {
        $this->postJson('/api/v10/payment-request/store', [
            'amount' => 5000,
            'merchant_account' => $this->compteDuVoisin->id,
        ], $this->entetes())->assertStatus(422);

        $this->assertSame(0, Payment::where('merchant_id', $this->merchant->id)->count());
    }

    public function test_un_retrait_vers_son_propre_compte_est_enregistre(): void
    {
        $this->postJson('/api/v10/payment-request/store', [
            'amount' => 5000,
            'merchant_account' => $this->monCompte->id,
            'description' => 'Retrait de test',
        ], $this->entetes())->assertOk();

        $demande = Payment::where('merchant_id', $this->merchant->id)->firstOrFail();
        $this->assertSame($this->monCompte->id, (int) $demande->merchant_account);
        $this->assertSame(\App\Enums\ApprovalStatus::PENDING, (int) $demande->status);

        // La demande apparait ensuite dans la liste du marchand, avec le compte.
        $this->getJson('/api/v10/payment-request/index', $this->entetes())
            ->assertOk()
            ->assertJsonPath('data.payments.0.mobile_no', '22997000001');
    }

    public function test_un_retrait_superieur_au_solde_est_refuse(): void
    {
        $this->postJson('/api/v10/payment-request/store', [
            'amount' => 100001,
            'merchant_account' => $this->monCompte->id,
        ], $this->entetes())->assertStatus(422);
    }

    public function test_la_liste_des_comptes_ne_contient_que_les_siens(): void
    {
        $reponse = $this->getJson('/api/v10/payment-accounts/index', $this->entetes())->assertOk();

        $ids = array_column($reponse->json('data.accounts'), 'id');
        $this->assertContains($this->monCompte->id, $ids);
        $this->assertNotContains($this->compteDuVoisin->id, $ids);
    }
}
