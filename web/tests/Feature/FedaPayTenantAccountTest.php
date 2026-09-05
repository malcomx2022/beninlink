<?php

namespace Tests\Feature;

use App\Enums\PayoutSetup;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Backend\PayoutSetupController;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Setting;
use App\Models\User;
use App\Services\Payments\FedaPayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * F2 et F3 — la configuration FedaPay côté administration, et le compte
 * d'encaissement du locataire.
 *
 * F2 : la passerelle n'avait aucune place dans `PayoutSetup`, donc aucun écran,
 * aucun interrupteur, et aucun moyen de savoir sur quel environnement elle
 * tournait. Le `.env` du serveur était sa seule surface de réglage.
 *
 * F3 : `credentialsFor()` prévoyait depuis le premier jour qu'un locataire
 * branche son propre compte, mais rien n'écrivait jamais `fedapay_secret_key` :
 * la branche était inatteignable et tous les encaissements de tous les
 * locataires tombaient sur le compte de la plateforme.
 *
 * Le piège que ces tests verrouillent : un locataire qui encaisse sur son compte
 * reçoit des webhooks signés par le secret de CE compte. Vérifier avec le secret
 * de la plateforme les rejetterait tous — le paiement partirait et le
 * portefeuille ne serait jamais crédité.
 */
class FedaPayTenantAccountTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const SECRET_PLATEFORME = 'wh_plateforme';
    private const SECRET_LOCATAIRE = 'wh_locataire';

    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config([
            'fedapay.secret_key' => 'sk_plateforme',
            'fedapay.webhook_secret' => self::SECRET_PLATEFORME,
            'fedapay.webhook_tolerance' => 300,
        ]);

        $this->companyId = (int) GeneralSettings::where('status', Status::ACTIVE)->orderByDesc('id')->firstOrFail()->id;
    }

    private function gateway(): FedaPayGateway
    {
        return new FedaPayGateway();
    }

    private function reglage(string $cle, ?string $valeur): void
    {
        Setting::updateOrCreate(
            ['company_id' => $this->companyId, 'key' => $cle],
            ['value' => $valeur],
        );
    }

    // ---- F3 : le compte d'encaissement --------------------------------------

    public function test_without_its_own_key_a_tenant_collects_on_the_platform_account(): void
    {
        $passerelle = $this->gateway();

        $this->assertFalse($passerelle->usesOwnAccount($this->companyId));
        $this->assertSame('sk_plateforme', $passerelle->credentialsFor($this->companyId)['secret_key']);
    }

    public function test_a_saved_key_makes_the_tenant_collect_on_its_own_account(): void
    {
        $this->reglage('fedapay_secret_key', 'sk_locataire');

        $passerelle = $this->gateway();

        $this->assertTrue($passerelle->usesOwnAccount($this->companyId));
        $this->assertSame('sk_locataire', $passerelle->credentialsFor($this->companyId)['secret_key']);
        // La plateforme, elle, garde sa propre clé : les abonnements SaaS
        // continuent d'être encaissés par elle.
        $this->assertSame('sk_plateforme', $passerelle->credentialsFor(null)['secret_key']);
    }

    public function test_the_webhook_secret_follows_the_collecting_account(): void
    {
        $passerelle = $this->gateway();
        $this->assertSame(self::SECRET_PLATEFORME, $passerelle->webhookSecretFor($this->companyId));

        $this->reglage('fedapay_webhook_secret', self::SECRET_LOCATAIRE);

        $this->assertSame(self::SECRET_LOCATAIRE, $passerelle->webhookSecretFor($this->companyId));
        $this->assertSame(self::SECRET_PLATEFORME, $passerelle->webhookSecretFor(null));
    }

    /** Le cœur du piège : la recharge d'un locataire est signée par SON secret. */
    public function test_a_tenant_signed_webhook_is_accepted_and_a_platform_signed_one_is_not(): void
    {
        $this->reglage('fedapay_secret_key', 'sk_locataire');
        $this->reglage('fedapay_webhook_secret', self::SECRET_LOCATAIRE);

        FedaPayTransaction::create([
            'company_id' => $this->companyId,
            'reference' => 'BL-F3',
            'provider_transaction_id' => 'PROV-F3',
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'amount' => 5000,
            'status' => FedaPayTransaction::STATUS_PENDING,
        ]);

        $this->webhook('PROV-F3', self::SECRET_LOCATAIRE)->assertOk();
        $this->webhook('PROV-F3', self::SECRET_PLATEFORME)->assertStatus(400);
    }

    /** Un abonnement SaaS est encaissé par la plateforme, même chez un locataire. */
    public function test_a_subscription_stays_signed_by_the_platform(): void
    {
        $this->reglage('fedapay_secret_key', 'sk_locataire');
        $this->reglage('fedapay_webhook_secret', self::SECRET_LOCATAIRE);

        $transaction = FedaPayTransaction::create([
            'company_id' => $this->companyId,
            'reference' => 'BL-SUB-F3',
            'provider_transaction_id' => 'PROV-SUB-F3',
            'purpose' => FedaPayTransaction::PURPOSE_SUBSCRIPTION,
            'amount' => 10000,
            'status' => FedaPayTransaction::STATUS_PENDING,
        ]);

        $this->assertNull($transaction->gatewayCompanyId());
        $this->webhook('PROV-SUB-F3', self::SECRET_PLATEFORME)->assertOk();
        $this->webhook('PROV-SUB-F3', self::SECRET_LOCATAIRE)->assertStatus(400);
    }

    private function webhook(string $providerId, string $secret)
    {
        $payload = json_encode([
            'name' => 'transaction.declined', // ne crédite rien : on teste la porte
            'entity' => ['id' => $providerId, 'amount' => 5000],
        ]);
        $horodatage = time();

        return $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => "t={$horodatage},s="
                . hash_hmac('sha256', $horodatage . '.' . $payload, $secret),
        ], $payload);
    }

    // ---- F2 : l'interrupteur et l'écran -------------------------------------

    public function test_the_gateway_is_usable_until_it_is_explicitly_switched_off(): void
    {
        $passerelle = $this->gateway();

        // Aucun réglage enregistré : comportement d'avant l'écran, préservé.
        $this->assertTrue($passerelle->isEnabled($this->companyId));

        $this->reglage('fedapay_status', (string) Status::INACTIVE);
        $this->assertFalse($passerelle->isEnabled($this->companyId));

        $this->reglage('fedapay_status', (string) Status::ACTIVE);
        $this->assertTrue($passerelle->isEnabled($this->companyId));
    }

    public function test_without_any_key_the_gateway_is_never_enabled(): void
    {
        config(['fedapay.secret_key' => null]);

        $this->assertFalse($this->gateway()->isEnabled($this->companyId));
    }

    public function test_the_settings_screen_saves_the_tenant_account(): void
    {
        $this->connecterAdministrateur();

        $this->enregistrer([
            'fedapay_secret_key' => 'sk_locataire',
            'fedapay_webhook_secret' => self::SECRET_LOCATAIRE,
            'fedapay_status' => 'on',
        ]);

        $this->assertSame('sk_locataire', $this->valeur('fedapay_secret_key'));
        $this->assertSame(self::SECRET_LOCATAIRE, $this->valeur('fedapay_webhook_secret'));
        $this->assertSame((string) Status::ACTIVE, $this->valeur('fedapay_status'));
    }

    /** Un secret laissé vide veut dire « ne pas changer », jamais « effacer ». */
    public function test_an_empty_secret_field_keeps_the_saved_one(): void
    {
        $this->connecterAdministrateur();
        $this->enregistrer([
            'fedapay_secret_key' => 'sk_locataire',
            'fedapay_webhook_secret' => self::SECRET_LOCATAIRE,
            'fedapay_status' => 'on',
        ]);

        // Enregistrement de routine : on ne touche qu'à l'interrupteur.
        $this->enregistrer(['fedapay_secret_key' => '', 'fedapay_webhook_secret' => '']);

        $this->assertSame('sk_locataire', $this->valeur('fedapay_secret_key'));
        $this->assertSame(self::SECRET_LOCATAIRE, $this->valeur('fedapay_webhook_secret'));
        $this->assertSame((string) Status::INACTIVE, $this->valeur('fedapay_status'));
    }

    /** Enregistrer la clé sans le secret de webhook livrerait le piège : refusé. */
    public function test_a_key_without_a_webhook_secret_is_refused(): void
    {
        $this->connecterAdministrateur();

        $this->assertFalse($this->enregistrer([
            'fedapay_secret_key' => 'sk_locataire',
            'fedapay_webhook_secret' => '',
            'fedapay_status' => 'on',
        ]));

        $this->assertNull($this->valeur('fedapay_secret_key'));
    }

    /**
     * L'écran doit dire d'un coup d'œil sur quel environnement et sur quel
     * compte la passerelle tourne : c'est exactement ce qu'aucun administrateur
     * ne pouvait savoir tant que le `.env` était la seule surface de réglage.
     */
    public function test_the_settings_screen_reports_the_environment_and_the_account(): void
    {
        $this->connecterAdministrateur();
        config(['fedapay.environment' => 'sandbox']);

        $vue = app(PayoutSetupController::class)->index($this->gateway());
        $donnees = $vue->getData();

        $this->assertFalse($donnees['fedapayIsLive']);
        $this->assertFalse($donnees['fedapayUsesOwnAccount']);
        $this->assertFalse($donnees['fedapayHasWebhookSecret']);
        $this->assertTrue($donnees['fedapayStatusActive']);

        $this->reglage('fedapay_secret_key', 'sk_locataire');
        $this->reglage('fedapay_webhook_secret', self::SECRET_LOCATAIRE);
        $this->reglage('fedapay_status', (string) Status::INACTIVE);
        config(['fedapay.environment' => 'live']);

        $donnees = app(PayoutSetupController::class)->index($this->gateway())->getData();

        $this->assertTrue($donnees['fedapayIsLive']);
        $this->assertTrue($donnees['fedapayUsesOwnAccount']);
        $this->assertTrue($donnees['fedapayHasWebhookSecret']);
        $this->assertFalse($donnees['fedapayStatusActive']);
    }

    private function connecterAdministrateur(): void
    {
        $admin = User::where('company_id', $this->companyId)
            ->where('user_type', '!=', UserType::SUPER_ADMIN)
            ->firstOrFail();
        Auth::login($admin);
    }

    private function enregistrer(array $donnees): bool
    {
        return app(\App\Repositories\PayoutSetup\PayoutSetupInterface::class)
            ->update(PayoutSetup::FEDAPAY, new Request($donnees));
    }

    private function valeur(string $cle): ?string
    {
        $valeur = Setting::where('company_id', $this->companyId)->where('key', $cle)->value('value');

        return $valeur === null ? null : (string) $valeur;
    }
}
