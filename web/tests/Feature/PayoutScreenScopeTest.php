<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S43 — l'écran des versements aux marchands : deux défauts, pas un.
 *
 * Cet écran est venu dans ce lot par sa **garde manquante** : il bloquait la
 * garde de `parcel/merchant/shops`, puisqu'on ne dérive pas un droit d'un écran
 * qui n'en a pas. En le regardant, un second défaut est apparu.
 *
 * `MerchantOnlinePaymentReceived` **porte** `scopeCompanywise()`, et la table a
 * bien sa colonne `company_id`. Le périmètre existait ; le contrôleur ne s'en
 * servait pas. On rendait donc la liste des encaissements en ligne de **tous
 * les transporteurs** — montants, comptes et marchands compris.
 *
 * Le second écran est pire dans sa forme : il filtre sur un `merchant_id` venu
 * de l'URL, sans périmètre. Changer ce numéro suffisait à lire les versements
 * du marchand d'en face. C'est exactement la famille que le filet S38 cherche,
 * trouvée ici par une autre porte — celle des gardes d'accès.
 *
 * ⚠️ La garde de droit et le périmètre de société sont **deux axes**, et fermer
 * l'un ne dit rien de l'autre. C'est la leçon que S41 avait tirée dans l'autre
 * sens ; elle vaut aussi dans celui-ci.
 */
class PayoutScreenScopeTest extends TestCase
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

    public function test_the_payout_list_only_shows_my_own_companys_receipts(): void
    {
        $mien = $this->versementDe(settings()->id, 'REF-MAISON', $this->marchandDe(settings()->id, 'mien'));
        $sien = $this->versementDe(self::AUTRE, 'REF-VOISIN', $this->marchandDe(self::AUTRE, 'sien'));

        $this->actingAs($this->agentAvec(['payout_read']));

        $reponse = $this->get(self::HOTE . '/admin/payout');
        $reponse->assertOk();

        $this->assertStringNotContainsString('REF-VOISIN', $reponse->getContent(),
            'la liste rend les encaissements d\'un autre transporteur');

        // Contrôle négatif : le mien y est bien. Sans cette moitié, un périmètre
        // qui ne rendrait jamais rien passerait pour une preuve.
        $this->assertStringContainsString('REF-MAISON', $reponse->getContent());
        $this->assertNotNull($sien->fresh());
        $this->assertNotNull($mien->fresh());
    }

    /**
     * Le second écran, dont l'identifiant de marchand vient de l'URL : c'est la
     * forme que le filet S38 cherche, et elle est ici sur de l'argent.
     */
    public function test_the_merchant_payout_screen_refuses_another_companys_merchant(): void
    {
        $sonMarchand = $this->marchandDe(self::AUTRE, 'sien');
        $sien = $this->versementDe(self::AUTRE, 'REF-VOISIN', $sonMarchand);

        $monMarchand = $this->marchandDe(settings()->id, 'mien');
        $mien = $this->versementDe(settings()->id, 'REF-MAISON', $monMarchand);

        $this->actingAs($this->agentAvec(['payout_read']));

        $sonEcran = $this->get(self::HOTE . '/admin/payout/merchant/payout?merchant_id=' . $sonMarchand->id);
        $sonEcran->assertOk();

        $this->assertStringNotContainsString('REF-VOISIN', $sonEcran->getContent(),
            'changer le numéro de marchand dans l\'URL donne les versements d\'en face');

        // Contrôle négatif : sur mon marchand, l'écran montre bien ses versements.
        $monEcran = $this->get(self::HOTE . '/admin/payout/merchant/payout?merchant_id=' . $monMarchand->id);

        $this->assertStringContainsString('REF-MAISON', $monEcran->getContent());
        $this->assertNotNull($sien->fresh());
        $this->assertNotNull($mien->fresh());
    }

    /* ─────────────────────────── outillage ──────────────────────────────── */

    private function versementDe(int $societe, string $reference, Merchant $marchand): MerchantOnlinePaymentReceived
    {
        $compte = Account::forceCreate([
            'company_id' => $societe,
            'balance' => 0,
            'account_holder_name' => 'Compte versement ' . $societe,
            'account_no' => 'CPT-' . $societe . '-' . uniqid(),
        ]);

        return MerchantOnlinePaymentReceived::forceCreate([
            'company_id' => $societe,
            'payment_type' => 1,
            'account_id' => $compte->id,
            'merchant_id' => $marchand->id,
            'transaction_id' => $reference,
            'amount' => 25000,
            'note' => $reference,
            'status' => 1,
        ]);
    }

    private function marchandDe(int $societe, string $suffixe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand versement ' . $suffixe;
        $utilisateur->email = 'marchand.versement.' . $suffixe . '.' . $societe . '@example.test';
        $utilisateur->mobile = '002299742' . $societe . strlen($suffixe);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME versement ' . $suffixe . ' ' . $societe,
            'current_balance' => 0,
        ]);
    }

    private function agentAvec(array $droits): User
    {
        $agent = new User();
        $agent->company_id = settings()->id;
        $agent->name = 'Agent versements';
        $agent->email = 'agent.versements@example.test';
        $agent->mobile = '0022997430001';
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->role_id = Role::where('company_id', settings()->id)->value('id');
        $agent->permissions = $droits;
        $agent->save();

        return $agent;
    }
}
