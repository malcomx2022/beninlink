<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Services\SmsService;
use App\Models\Backend\FedaPayTransaction;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\SmsSetting;
use App\Models\Backend\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * F4 — le SMS de confirmation partait sous l'identité de la première société.
 *
 * Le webhook FedaPay n'a ni session ni sous-domaine. `settings()` y retombe donc
 * sur la société 1, et `smsSettings()` fait de même explicitement. Le marchand
 * d'une autre société recevait un SMS portant le nom commercial et la devise
 * d'un tiers, émis avec les identifiants d'opérateur SMS de ce tiers, et
 * facturés à lui.
 */
class WalletSmsTenantTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const WEBHOOK_SECRET = 'wh_test_secret';

    private Merchant $merchant;
    private GeneralSettings $societe;
    private GeneralSettings $plateforme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config([
            'fedapay.webhook_secret' => self::WEBHOOK_SECRET,
            'fedapay.webhook_tolerance' => 300,
        ]);

        $this->merchant = Merchant::firstOrFail();

        $this->societe = GeneralSettings::findOrFail($this->merchant->company_id);
        $this->societe->name = 'Transporteur Ouémé';
        $this->societe->currency = 'FCFA ';
        $this->societe->save();

        // La société 1 : celle sur laquelle le socle retombe hors session.
        $this->plateforme = GeneralSettings::findOrFail(1);
        $this->plateforme->name = 'Plateforme BeninLink';
        $this->plateforme->currency = 'XOF ';
        $this->plateforme->save();

        $this->assertNotSame((int) $this->plateforme->id, (int) $this->societe->id);
    }

    /** Un opérateur SMS actif pour chaque société, avec des clés distinctes. */
    private function operateurSms(int $companyId, string $cle): void
    {
        foreach ([
            'reve_status' => Status::ACTIVE,
            'reve_api_key' => $cle,
            'reve_secret_key' => $cle . '-secret',
            'reve_api_url' => 'https://sms.example.test/send',
        ] as $key => $value) {
            SmsSetting::updateOrCreate(
                ['company_id' => $companyId, 'key' => $key],
                ['value' => (string) $value],
            );
        }
    }

    private function rechargeEnAttente(): Wallet
    {
        $wallet = new Wallet();
        $wallet->company_id = $this->merchant->company_id;
        $wallet->user_id = $this->merchant->user_id;
        $wallet->merchant_id = $this->merchant->id;
        $wallet->source = 'FedaPay';
        $wallet->transaction_id = 'BL-F4';
        $wallet->amount = 5000;
        $wallet->type = WalletType::INCOME;
        $wallet->payment_method = WalletPaymentMethod::OFFLINE;
        $wallet->status = WalletStatus::PENDING;
        $wallet->save();

        FedaPayTransaction::create([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'reference' => 'BL-F4',
            'provider_transaction_id' => 'PROV-F4',
            'purpose' => FedaPayTransaction::PURPOSE_WALLET,
            'amount' => 5000,
            'status' => FedaPayTransaction::STATUS_PENDING,
            'wallet_id' => $wallet->id,
        ]);

        return $wallet;
    }

    private function webhookApprouve()
    {
        $payload = json_encode([
            'name' => 'transaction.approved',
            'entity' => ['id' => 'PROV-F4', 'amount' => 5000],
        ]);
        $horodatage = time();

        return $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => "t={$horodatage},s="
                . hash_hmac('sha256', $horodatage . '.' . $payload, self::WEBHOOK_SECRET),
        ], $payload);
    }

    /**
     * Le message porte la marque et la devise de la société du portefeuille,
     * jamais celles de la première société.
     */
    public function test_the_message_carries_the_wallet_company_brand(): void
    {
        $this->rechargeEnAttente();

        $envoyes = $this->capturerLesEnvois();

        $this->webhookApprouve()->assertOk();

        $this->assertCount(1, $envoyes);
        $this->assertSame((int) $this->merchant->company_id, $envoyes[0]['company']);
        $this->assertStringContainsString('Transporteur Ouémé', $envoyes[0]['message']);
        $this->assertStringContainsString('FCFA', $envoyes[0]['message']);
        $this->assertStringNotContainsString('Plateforme BeninLink', $envoyes[0]['message']);
    }

    /** Et il part par l'opérateur SMS de cette société, pas par celui de la première. */
    public function test_the_message_goes_through_the_right_operator_account(): void
    {
        $this->operateurSms(1, 'cle-plateforme');
        $this->operateurSms((int) $this->merchant->company_id, 'cle-locataire');

        $service = app(SmsService::class)->forCompany((int) $this->merchant->company_id);

        $lecture = new \ReflectionMethod(SmsService::class, 'setting');
        $lecture->setAccessible(true);
        $this->assertSame('cle-locataire', $lecture->invoke($service, 'reve_api_key'));

        $marque = new \ReflectionMethod(SmsService::class, 'brand');
        $marque->setAccessible(true);
        $this->assertSame('Transporteur Ouémé', $marque->invoke($service));
    }

    /** Sans société précisée, le comportement du socle est conservé. */
    public function test_without_a_company_the_socle_behaviour_is_kept(): void
    {
        $this->operateurSms(1, 'cle-plateforme');

        $service = app(SmsService::class);

        $lecture = new \ReflectionMethod(SmsService::class, 'setting');
        $lecture->setAccessible(true);
        // Hors session, l'aide du socle retombe sur la société 1 : inchangé.
        $this->assertSame('cle-plateforme', $lecture->invoke($service, 'reve_api_key'));
    }

    /** `forCompany()` rend une copie : l'instance partagée n'est jamais teintée. */
    public function test_for_company_never_taints_the_shared_service(): void
    {
        $partage = app(SmsService::class);
        $copie = $partage->forCompany((int) $this->merchant->company_id);

        $this->assertNotSame($partage, $copie);

        $marque = new \ReflectionMethod(SmsService::class, 'brand');
        $marque->setAccessible(true);
        $this->assertSame('Transporteur Ouémé', $marque->invoke($copie));
        $this->assertNotSame('Transporteur Ouémé', $marque->invoke($partage));
    }

    /**
     * Remplace le service par un double qui note ce qu'on lui demande d'envoyer,
     * en conservant le contexte de société posé par `forCompany()`.
     *
     * Le journal est un objet partagé : une référence ne traverserait pas la
     * fabrique du conteneur.
     */
    private function capturerLesEnvois(): \ArrayObject
    {
        $journal = new \ArrayObject();

        $this->app->bind(SmsService::class, fn () => new class($journal) extends SmsService {
            public function __construct(private \ArrayObject $journal, private ?int $societe = null) {}

            public function forCompany(?int $companyId): static
            {
                return new static($this->journal, $companyId);
            }

            public function sendSms($userPhone, $msg)
            {
                $this->journal[] = ['company' => $this->societe, 'message' => $msg];
            }
        });

        return $journal;
    }
}
