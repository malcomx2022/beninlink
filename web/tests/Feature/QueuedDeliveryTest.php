<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Services\PushNotificationService;
use App\Http\Services\SmsService;
use App\Jobs\SendPush;
use App\Jobs\SendSms;
use App\Mail\ContactMail;
use App\Models\Backend\DeviceToken;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\SmsSetting;
use App\Models\Backend\Wallet;
use App\Models\MerchantShops;
use App\Services\Push\PushGateway;
use App\Services\Push\PushMessage;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * D13 — les envois quittent la requête HTTP.
 *
 * Le socle appelait l'opérateur SMS **pendant** la requête, avec un délai
 * d'attente de 80 secondes, et jusqu'à deux fois par changement de statut : un
 * opérateur lent faisait attendre l'agent qui venait de cliquer. Le push (5 s)
 * et les courriels s'ajoutaient dessus.
 *
 * Ce que ces tests fixent :
 *   1. les points d'entrée du socle mettent en file au lieu d'appeler ;
 *   2. **le locataire voyage avec le job** — c'est le vrai risque du
 *      changement : hors requête, `settings()` retombe sur la société 1, et le
 *      SMS partirait avec le nom et les identifiants d'opérateur d'un autre
 *      transporteur, facturés à lui (constat F4) ;
 *   3. exécuté, le job livre bien ;
 *   4. en `sync` — installation sans worker — rien ne change.
 */
class QueuedDeliveryTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->merchant = Merchant::firstOrFail();
    }

    // 1 — on met en file, on n'appelle plus ------------------------------------

    public function test_a_status_change_queues_its_sms_instead_of_calling_the_operator(): void
    {
        Queue::fake();

        app(SmsService::class)->sendSms('0022997000000', 'Votre colis BL-1 est livré.');

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->phone === '0022997000000'
            && str_contains($job->message, 'BL-1')
            && $job->otp === false);
    }

    public function test_the_verification_code_is_queued_too(): void
    {
        Queue::fake();

        app(SmsService::class)->sendOtp('0022997000001', '123456');

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->otp === true && $job->message === '123456');
    }

    public function test_a_push_is_queued_by_the_feed_and_by_the_socle_path(): void
    {
        Queue::fake();
        $this->appareil($this->merchant->user);

        // Chemin du fil : une écriture métier réelle.
        $this->crediterLeWallet(12000);
        Queue::assertPushed(SendPush::class, fn (SendPush $job) => $job->userId === $this->merchant->user->id
            && $job->data['kind'] === 'wallet_credit');

        // Chemin du socle : les quinze appels de ParcelRepository.
        $livreur = $this->livreur('3');
        app(PushNotificationService::class)
            ->sendStatusPushNotification($this->colis(), $livreur->user->email, 'msg', 'deliveryMan');

        Queue::assertPushed(SendPush::class, fn (SendPush $job) => $job->userId === $livreur->user->id);
    }

    public function test_the_three_simple_mails_are_queueable(): void
    {
        foreach ([\App\Mail\ContactMail::class, \App\Mail\MerchantSignup::class, \App\Mail\CompanySignup::class] as $mailable) {
            $this->assertTrue(
                is_subclass_of($mailable, ShouldQueue::class) || in_array(ShouldQueue::class, class_implements($mailable), true),
                $mailable . ' doit partir en file',
            );
        }
    }

    // 2 — le locataire voyage avec le job -------------------------------------

    public function test_the_sms_carries_its_company_because_settings_would_lie_in_the_worker(): void
    {
        Queue::fake();

        // Cas type : le webhook FedaPay, hors session, qui remercie un marchand.
        app(SmsService::class)->forCompany($this->merchant->company_id)
            ->sendSms('0022997000002', 'Recharge reçue.');

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->companyId === $this->merchant->company_id);
    }

    public function test_without_an_explicit_company_the_one_of_the_request_is_captured(): void
    {
        Queue::fake();

        app(SmsService::class)->sendSms('0022997000003', 'Bonjour.');

        // `settings()` est juste DANS la requête ; c'est là qu'on le lit.
        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->companyId === settings()->id);
    }

    public function test_the_contact_mail_freezes_its_sender_at_construction(): void
    {
        // Construit dans la requête, bâti (`build()`) plus tard par le worker :
        // l'expéditeur doit être celui de la société d'alors, pas celui que
        // `settings()` renverrait au moment de l'envoi.
        $mail = new ContactMail([
            'name' => 'Aïcha Kora',
            'email' => 'aicha@example.test',
            'subject' => 'Question',
            'message' => 'Bonjour.',
        ]);

        $attendu = settings()->email;
        $mail->build();

        $this->assertTrue($mail->hasFrom($attendu, settings()->name));
        $this->assertTrue($mail->hasTo($attendu));
        $this->assertTrue($mail->hasReplyTo('aicha@example.test'));
    }

    /**
     * Les deux courriels d'inscription figent la société **entière**.
     *
     * Leur gabarit lisait `settings()` cinq fois — le titre, le logo, la raison
     * sociale dans le corps, le courriel et le téléphone de contact, les
     * mentions de bas de page. Bâti par le worker, `settings()` retombe sur la
     * société 1 : un marchand de « Kola Distribution » recevait un message
     * signé du premier transporteur de la base, logo compris. Relevé en posant
     * l'alternative textuelle du logo — on ne pouvait pas y écrire un nom faux.
     */
    public function test_the_two_signup_mails_freeze_the_whole_carrier(): void
    {
        foreach ([\App\Mail\MerchantSignup::class, \App\Mail\CompanySignup::class] as $classe) {
            $mail = new $classe(['name' => 'Kola Distribution', 'email' => 'kola@example.test']);
            $mail->build();
            $rendu = $mail->render();

            $this->assertStringContainsString(settings()->name, $rendu, $classe);
            $this->assertStringContainsString('alt="' . settings()->name . '"', $rendu, $classe);
        }
    }

    /** Et le courriel de contact fige aussi son nom et ses mentions. */
    public function test_the_contact_mail_freezes_its_brand_too(): void
    {
        $mail = new ContactMail([
            'name' => 'Aïcha Kora',
            'email' => 'aicha@example.test',
            'subject' => 'Question',
            'message' => 'Bonjour.',
        ]);
        $mail->build();

        $this->assertStringContainsString('alt="' . settings()->name . '"', $mail->render());
    }

    // 3 — exécuté, le job livre ------------------------------------------------

    public function test_the_sms_job_delivers_through_the_service_for_the_right_company(): void
    {
        // Aucun opérateur actif : la livraison ne sort pas, mais elle lit bien
        // les réglages de la société portée par le job.
        SmsSetting::create(['company_id' => $this->merchant->company_id, 'key' => 'reve_status', 'value' => 0]);

        $espion = new class extends SmsService {
            public array $livraisons = [];
            public ?int $societeVue = null;

            public function forCompany(?int $companyId): static
            {
                $copie = parent::forCompany($companyId);
                $copie->societeVue = $companyId;
                $copie->livraisons = &$this->livraisons;

                return $copie;
            }

            public function deliverSms($userPhone, $msg)
            {
                $this->livraisons[] = ['phone' => $userPhone, 'company' => $this->societeVue];
            }
        };
        $this->app->instance(SmsService::class, $espion);

        (new SendSms($this->merchant->company_id, '0022997000004', 'Livré.'))->handle($espion);

        $this->assertSame([['phone' => '0022997000004', 'company' => $this->merchant->company_id]], $espion->livraisons);
    }

    public function test_the_push_job_reads_the_devices_at_delivery_time(): void
    {
        $espion = new class implements PushGateway {
            public array $envois = [];

            public function toUser(User $user, PushMessage $message): int
            {
                $this->envois[] = $user->id;

                return 1;
            }
        };
        $this->app->instance(PushGateway::class, $espion);

        (new SendPush($this->merchant->user->id, 'Titre', 'Corps', ['kind' => 'invoice']))->handle($espion);
        $this->assertSame([$this->merchant->user->id], $espion->envois);

        // Compte supprimé entre la mise en file et l'envoi : le job passe son
        // tour au lieu d'échouer en boucle.
        (new SendPush(999999, 'Titre', 'Corps'))->handle($espion);
        $this->assertCount(1, $espion->envois);
    }

    // 4 — en `sync`, rien ne change -------------------------------------------

    public function test_in_sync_the_push_still_leaves_within_the_request(): void
    {
        // C'est la garantie qui rend le changement sûr : une installation sans
        // worker se comporte exactement comme avant.
        $this->assertSame('sync', config('queue.default'));

        $espion = new class implements PushGateway {
            public int $appels = 0;

            public function toUser(User $user, PushMessage $message): int
            {
                $this->appels++;

                return 1;
            }
        };
        $this->app->instance(PushGateway::class, $espion);
        $this->appareil($this->merchant->user);

        $this->crediterLeWallet(8000);

        $this->assertSame(1, $espion->appels, 'le job s\'exécute dans la foulée');
    }

    // 5 — la file `database` et son témoin ------------------------------------

    public function test_on_the_database_driver_the_send_lands_in_the_jobs_table(): void
    {
        // La migration et le pilote se répondent : c'est ce que voit un VPS en
        // production, où `QUEUE_CONNECTION=database`.
        config(['queue.default' => 'database']);

        app(SmsService::class)->sendSms('0022997000005', 'Mis en file.');

        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('jobs')->count());
        $charge = json_decode(\Illuminate\Support\Facades\DB::table('jobs')->value('payload'), true);
        $this->assertStringContainsString('SendSms', $charge['displayName']);
    }

    public function test_the_witness_command_says_when_the_worker_stopped(): void
    {
        config(['queue.default' => 'database']);

        // File vide : rien à signaler.
        $this->artisan('beninlink:file-attente')->assertSuccessful();

        app(SmsService::class)->sendSms('0022997000006', 'En attente.');

        // Un envoi qui vient d'arriver est normal.
        $this->artisan('beninlink:file-attente')->assertSuccessful();

        // Le même, vieux de vingt minutes : le worker ne tourne plus, et
        // c'est exactement la panne silencieuse que la file introduit.
        \Illuminate\Support\Facades\DB::table('jobs')->update(['available_at' => now()->subMinutes(20)->getTimestamp()]);

        $this->artisan('beninlink:file-attente')
            ->expectsOutputToContain('Le worker est probablement arrêté')
            // Le nom du service est épinglé : la commande a longtemps renvoyé
            // vers `systemctl status beninlink-queue`, qui n'existe nulle part.
            // Le dépôt livre un programme SUPERVISOR nommé `beninlink-worker`.
            // Un témoin qui se déclenche pendant une panne ne peut pas envoyer
            // sur une fausse piste.
            ->expectsOutputToContain('supervisorctl status beninlink-worker')
            ->assertFailed();
    }

    public function test_the_witness_command_says_when_nothing_is_queued_at_all(): void
    {
        // En `sync`, la commande ne doit pas laisser croire que la file marche.
        $this->artisan('beninlink:file-attente')
            ->expectsOutputToContain("rien n'est mis en file")
            ->assertSuccessful();
    }

    // — décor -------------------------------------------------------------------

    private function appareil(User $user): DeviceToken
    {
        return DeviceToken::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[' . $user->id . ']',
            'platform' => 'android',
            'app' => 'merchant',
        ]);
    }

    private function crediterLeWallet(int $montant): Wallet
    {
        $wallet = new Wallet();
        $wallet->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'user_id' => $this->merchant->user_id,
            'amount' => $montant,
            'type' => WalletType::INCOME,
            'status' => WalletStatus::PENDING,
            'payment_method' => WalletPaymentMethod::OFFLINE,
            'source' => 'FedaPay',
            'transaction_id' => 'TR-' . $montant,
        ])->save();

        $wallet->status = WalletStatus::APPROVED;
        $wallet->save();

        return $wallet;
    }

    private function colis(): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aïcha K.',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => 'BL-FILE-1',
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ])->save();

        return $parcel;
    }
}
