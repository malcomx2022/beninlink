<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Services\PushNotificationService;
use App\Models\Backend\DeviceToken;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
use App\Models\MerchantShops;
use App\Models\User;
use App\Notifications\MerchantNotification;
use App\Services\Push\ExpoPushGateway;
use App\Services\Push\NullPushGateway;
use App\Services\Push\PushGateway;
use App\Services\Push\PushMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * D11 — les notifications poussées, rebranchées.
 *
 * Le socle poussait par l'API FCM « legacy », arrêtée par Google : le fil du
 * marchand s'écrivait en base mais **rien n'arrivait sur le téléphone**. Le
 * transport est désormais le service de push d'Expo, adressé par appareil.
 *
 * Ce que ces tests fixent, dans l'ordre où on s'en sert :
 *   1. un appareil s'abonne, se désabonne, et n'atteint pas celui d'un autre ;
 *   2. un événement métier réel (crédit de wallet) part sur l'appareil — une
 *      fois, avec le même texte et les mêmes clés que le fil en base ;
 *   3. la panne du service de push ne fait rien perdre ;
 *   4. un appareil que le service déclare inconnu est oublié ;
 *   5. le marchand n'est pas notifié deux fois par les deux chemins.
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private const API_KEY = 'cle-de-test';
    private const JETON = 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY, 'push.driver' => 'expo']);

        $this->merchant = Merchant::firstOrFail();
    }

    /** Passerelle de test : enregistre ce qu'on lui demande d'envoyer. */
    private function passerelleEspion(): object
    {
        $espion = new class implements PushGateway {
            public array $envois = [];

            public function toUser(User $user, PushMessage $message): int
            {
                $this->envois[] = ['user_id' => $user->id, 'message' => $message];

                return 1;
            }
        };

        $this->app->instance(PushGateway::class, $espion);

        return $espion;
    }

    /**
     * Passerelle Expo réelle, branchée sur un faux service HTTP.
     *
     * @param array<int,Response|\Throwable> $reponses
     */
    private function passerelleExpo(array $reponses, ?array &$requetes = null): ExpoPushGateway
    {
        $requetes = [];
        $pile = HandlerStack::create(new MockHandler($reponses));
        $pile->push(Middleware::history($requetes));

        return new ExpoPushGateway(new Client(['handler' => $pile]));
    }

    private function appareil(User $user, string $token = self::JETON): DeviceToken
    {
        return DeviceToken::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'token' => $token,
            'platform' => 'android',
            'app' => 'merchant',
        ]);
    }

    // 1 — abonnement -------------------------------------------------------

    public function test_un_appareil_s_abonne_et_le_rejeu_ne_cree_pas_de_doublon(): void
    {
        Sanctum::actingAs($this->merchant->user, ['merchant']);

        $reponse = $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/register', [
                'token' => self::JETON,
                'platform' => 'android',
                'app' => 'merchant',
            ]);

        $reponse->assertOk();
        $this->assertSame(1, DeviceToken::count());

        $appareil = DeviceToken::firstOrFail();
        $this->assertSame($this->merchant->user->id, $appareil->user_id);
        $this->assertSame($this->merchant->company_id, $appareil->company_id);
        $this->assertSame('android', $appareil->platform);

        // L'app réenvoie son jeton à chaque démarrage : une seule ligne.
        $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/register', ['token' => self::JETON])->assertOk();
        $this->assertSame(1, DeviceToken::count());
    }

    public function test_un_jeton_qui_n_est_pas_livrable_est_refuse(): void
    {
        Sanctum::actingAs($this->merchant->user, ['merchant']);

        $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/register', ['token' => 'un-jeton-quelconque'])
            ->assertStatus(422);

        $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/register', [])
            ->assertStatus(422);

        $this->assertSame(0, DeviceToken::count());
    }

    public function test_on_ne_desabonne_que_ses_propres_appareils(): void
    {
        $autre = $this->livreur('9')->user;
        $sien = $this->appareil($this->merchant->user);
        $celuiDeLAutre = $this->appareil($autre, 'ExponentPushToken[autre-appareil]');

        Sanctum::actingAs($this->merchant->user, ['merchant']);

        // Connaître le jeton d'autrui ne suffit pas à le désabonner.
        $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/forget', ['token' => $celuiDeLAutre->token])->assertOk();
        $this->assertDatabaseHas('device_tokens', ['id' => $celuiDeLAutre->id]);

        $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/forget', ['token' => $sien->token])->assertOk();
        $this->assertDatabaseMissing('device_tokens', ['id' => $sien->id]);
    }

    public function test_un_appareil_qui_change_de_main_suit_son_dernier_compte(): void
    {
        $livreur = $this->livreur('8')->user;
        $this->appareil($this->merchant->user);

        Sanctum::actingAs($livreur, ['deliveryman']);
        $this->withHeaders(['apiKey' => self::API_KEY])
            ->postJson('/api/v10/push/register', ['token' => self::JETON, 'app' => 'deliveryman'])
            ->assertOk();

        $this->assertSame(1, DeviceToken::count());
        $this->assertSame($livreur->id, DeviceToken::firstOrFail()->user_id);
    }

    // 2 — un événement métier réel part sur l'appareil ----------------------

    public function test_un_credit_de_wallet_pousse_le_meme_texte_que_le_fil(): void
    {
        $espion = $this->passerelleEspion();
        $this->appareil($this->merchant->user);

        // Écriture Eloquent réelle : c'est l'observer qui doit réagir.
        $this->crediterLeWallet(15000);

        $this->assertCount(1, $espion->envois, 'un crédit, un push');

        $entree = $this->merchant->user->notifications()->firstOrFail()->data;
        $message = $espion->envois[0]['message'];

        $this->assertSame($entree['title'], $message->title);
        $this->assertSame($entree['body'], $message->body);
        $this->assertSame(MerchantNotification::KIND_WALLET_CREDIT, $message->data['kind']);
        $this->assertSame($entree['amount'], $message->data['amount']);
    }

    public function test_sans_appareil_rien_n_est_tente_et_le_fil_reste_ecrit(): void
    {
        $espion = $this->passerelleEspion();

        $this->crediterLeWallet(9000);

        $this->assertSame([], $espion->envois);
        $this->assertSame(1, $this->merchant->user->notifications()->count());
    }

    public function test_le_service_de_push_en_panne_ne_fait_pas_perdre_le_fil(): void
    {
        $this->appareil($this->merchant->user);
        $this->app->instance(PushGateway::class, $this->passerelleExpo([
            new ConnectException('injoignable', new PsrRequest('POST', 'https://exp.host')),
        ]));

        $this->crediterLeWallet(7000);

        // Le fil est écrit, l'appareil est gardé : le prochain événement retentera.
        $this->assertSame(1, $this->merchant->user->notifications()->count());
        $this->assertSame(1, DeviceToken::count());
    }

    public function test_le_pilote_null_n_envoie_rien(): void
    {
        config(['push.driver' => null]);
        $this->appareil($this->merchant->user);

        $this->assertInstanceOf(NullPushGateway::class, app(PushGateway::class));
        $this->assertSame(0, app(PushGateway::class)->toUser(
            $this->merchant->user,
            new PushMessage('Titre', 'Corps'),
        ));
    }

    // 3 — la passerelle Expo elle-même -------------------------------------

    public function test_la_charge_envoyee_a_expo_porte_le_texte_et_les_donnees(): void
    {
        $this->appareil($this->merchant->user);
        $passerelle = $this->passerelleExpo([
            new Response(200, [], json_encode(['data' => [['status' => 'ok', 'id' => 'ticket-1']]])),
        ], $requetes);

        $servis = $passerelle->toUser($this->merchant->user, new PushMessage(
            'Recharge réussie',
            '+15 000 FCFA crédités.',
            ['kind' => 'wallet_credit', 'amount' => 15000],
        ));

        $this->assertSame(1, $servis);
        $this->assertCount(1, $requetes);

        $charge = json_decode((string) $requetes[0]['request']->getBody(), true);
        $this->assertSame(self::JETON, $charge[0]['to']);
        $this->assertSame('Recharge réussie', $charge[0]['title']);
        $this->assertSame('+15 000 FCFA crédités.', $charge[0]['body']);
        $this->assertSame('wallet_credit', $charge[0]['data']['kind']);
        $this->assertSame('default', $charge[0]['channelId']);

        $this->assertNotNull(DeviceToken::firstOrFail()->last_used_at, 'un appareil servi est daté');
    }

    public function test_un_appareil_declare_inconnu_est_oublie_les_autres_restent(): void
    {
        $vivant = $this->appareil($this->merchant->user, 'ExponentPushToken[vivant]');
        $mort = $this->appareil($this->merchant->user, 'ExponentPushToken[desinstalle]');

        $passerelle = $this->passerelleExpo([
            new Response(200, [], json_encode(['data' => [
                ['status' => 'ok', 'id' => 'ticket-1'],
                ['status' => 'error', 'message' => 'not registered', 'details' => ['error' => 'DeviceNotRegistered']],
            ]])),
        ]);

        $this->assertSame(1, $passerelle->toUser($this->merchant->user, new PushMessage('T', 'C')));

        $this->assertDatabaseHas('device_tokens', ['id' => $vivant->id]);
        $this->assertDatabaseMissing('device_tokens', ['id' => $mort->id]);
    }

    public function test_les_envois_sont_decoupes_a_la_limite_du_service(): void
    {
        config(['push.expo.chunk' => 2]);
        foreach (range(1, 5) as $i) {
            $this->appareil($this->merchant->user, "ExponentPushToken[appareil-{$i}]");
        }

        $ok = fn (int $n) => new Response(200, [], json_encode([
            'data' => array_fill(0, $n, ['status' => 'ok', 'id' => 'ticket']),
        ]));

        $passerelle = $this->passerelleExpo([$ok(2), $ok(2), $ok(1)], $requetes);

        $this->assertSame(5, $passerelle->toUser($this->merchant->user, new PushMessage('T', 'C')));
        $this->assertCount(3, $requetes, 'cinq appareils, par lots de deux');
    }

    // 4 — un seul push par événement ---------------------------------------

    public function test_le_chemin_du_socle_sert_le_livreur_et_laisse_le_marchand_a_son_fil(): void
    {
        $espion = $this->passerelleEspion();
        $livreur = $this->livreur('7');
        $this->appareil($livreur->user, 'ExponentPushToken[livreur]');
        $this->appareil($this->merchant->user);

        $colis = $this->colis();
        $service = app(PushNotificationService::class);

        // Le même appel que fait ParcelRepository, pour les deux tiers.
        $service->sendStatusPushNotification($colis, $livreur->user->email, 'Dear ..., please pickup', 'deliveryMan');
        $service->sendStatusPushNotification($colis, $this->merchant->user->email, 'Dear ..., your parcel', 'merchant');

        $this->assertCount(1, $espion->envois, 'le marchand est servi par son fil, pas deux fois');
        $this->assertSame($livreur->user->id, $espion->envois[0]['user_id']);

        // Texte recomposé en français, et non la phrase anglaise du SMS.
        $message = $espion->envois[0]['message'];
        $this->assertStringContainsString($colis->tracking_id, $message->title);
        $this->assertStringContainsString(trans('parcelStatus.' . $colis->status), $message->body);
        $this->assertStringNotContainsString('Dear', $message->body);
        $this->assertSame($colis->id, $message->data['parcel_id']);
    }

    public function test_un_destinataire_inconnu_ou_d_une_autre_societe_ne_recoit_rien(): void
    {
        $espion = $this->passerelleEspion();
        $colis = $this->colis();

        app(PushNotificationService::class)
            ->sendStatusPushNotification($colis, 'personne@example.test', 'msg', 'deliveryMan');

        $this->assertSame([], $espion->envois);
    }

    public function test_une_adresse_partagee_par_deux_societes_ne_pousse_qu_au_bon_compte(): void
    {
        $espion = $this->passerelleEspion();

        // Les adresses ne sont pas uniques en base : deux transporteurs peuvent
        // employer le même livreur, ou simplement deux homonymes.
        $ici = $this->livreur('6');
        $ailleurs = $this->livreur('5', $this->marchandDUneAutreSociete('P')->company_id);
        $ailleurs->user->forceFill(['email' => $ici->user->email])->save();

        $colis = $this->colis();
        app(PushNotificationService::class)
            ->sendStatusPushNotification($colis, $ici->user->email, 'msg', 'deliveryMan');

        $this->assertCount(1, $espion->envois);
        $this->assertSame($ici->user->id, $espion->envois[0]['user_id'], 'le livreur de la société du colis');
    }

    // — décor ---------------------------------------------------------------

    /**
     * Une recharge, puis son approbation : c'est le passage du statut qui
     * alimente le fil (WalletFeedObserver), comme le fait le webhook FedaPay.
     */
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

    private function colis(string $tracking = 'BL-PUSH-1'): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aïcha K.',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou, Fidjrossè',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => $tracking,
            'status' => ParcelStatus::PICKUP_ASSIGN,
        ])->save();

        return $parcel;
    }
}
