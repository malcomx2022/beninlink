<?php

namespace Tests\Feature;

use App\Enums\CustomsLevel;
use App\Enums\Merchant_panel\PaymentMethod;
use App\Enums\ParcelStatus;
use App\Enums\PayoutSetup;
use App\Jobs\SendSms;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\MerchantPayment;
use App\Models\MerchantShops;
use App\Models\User;
use App\Services\Payments\FedaPayGateway;
use App\Services\Pilote\PiloteDataset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S71 — la RÉPÉTITION GÉNÉRALE de la recette pilote (`docs/guides/recette-pilote/`).
 *
 * La liste de recette du §4 compte 34 scénarios à cocher sur des téléphones réels.
 * Chacun a deux moitiés : ce que l'app affiche (un humain, un appareil, un réseau
 * mobile) et ce que le serveur fait quand l'app l'appelle. Ce fichier joue la
 * **seconde moitié de chaque scénario sur le jeu `beninlink:pilote`**, avec les
 * comptes que les PME recevront (`PIL-001`, `LIV-001`, `pilote2026`), par les
 * routes exactes que les deux apps appellent (`mobile/src/api/endpoints.ts`,
 * `mobile-livreur/src/api/endpoints.ts`).
 *
 * Ce qu'il prouve : le jour de la recette, aucun scénario ne tombera sur un
 * serveur qui ne sait pas faire. Ce qu'il ne prouve pas : l'écran. Les cases du
 * guide restent à cocher par un humain ; chaque test porte le numéro de la sienne.
 *
 * ⚠️ Deux scénarios n'ont PAS de moitié serveur jouable ici et restent entièrement
 * humains : A1 (le tableau de bord du back-office, dont les routes ne se montent
 * que sur un domaine de la société pilote) et S3 (`php artisan test` sur la
 * version déployée — c'est cette suite elle-même).
 *
 * ⚠️ Le jeu pilote vit dans la société **2** (celle du premier marchand semé) ; la
 * société ambiante des tests est la 1. Tout ce qui lit la société par la requête
 * (`settings()`) — l'inscription d'une PME, par exemple — l'attribuera à la 1 ici,
 * et à celle du sous-domaine `recette.` en recette. C'est voulu : on prouve le
 * comportement, pas le routage par hôte, que `MountsTenantRoutes` couvre ailleurs.
 */
class RecettePiloteRepetitionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_recette';
    private const WEBHOOK_SECRET = 'whsec_recette';

    private int $societe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        config([
            'rxcourier.api_key' => self::API_KEY,
            'fedapay.environment' => 'sandbox',
            'fedapay.secret_key' => 'sk_sandbox_recette',
            'fedapay.public_key' => 'pk_sandbox_recette',
            'fedapay.webhook_secret' => self::WEBHOOK_SECRET,
            'fedapay.webhook_tolerance' => 300,
        ]);

        $this->societe = (int) Merchant::firstOrFail()->company_id;
        app(PiloteDataset::class)->seed($this->societe);
    }

    /* ═══════════════════════════ Marchand (app `mobile/`) ═══════════════════════ */

    /** M1 — Connexion avec PIL-001 : l'écran affiche l'enseigne et l'agence. */
    public function test_m1_la_connexion_pil001_rend_l_enseigne_et_l_agence(): void
    {
        $reponse = $this->postJson('/api/v10/signin', [
            'merchant_id' => 'PIL-001', 'password' => PiloteDataset::PASSWORD,
        ], $this->entetes())->assertOk();

        $this->assertNotEmpty($reponse->json('data.token'));
        $this->assertSame('Maison Kora Cosmétiques', $reponse->json('data.user.merchant.business_name'));
        $this->assertStringContainsString('Ganhi', (string) $reponse->json('data.user.hub.name'));
    }

    /** M2 — Le devis précède la création ; le colis enregistre exactement les montants du devis. */
    public function test_m2_le_devis_precede_la_creation_et_le_colis_porte_les_memes_montants(): void
    {
        $this->connecterMarchand('PIL-001');
        $payload = $this->colisDe('PIL-001', ['weight' => 2, 'cash_collection' => 25000]);

        $devis = $this->postJson('/api/v10/parcel/quote', $payload, $this->entetes())->assertOk()->json('data');

        $this->assertGreaterThan(0, $devis['total_delivery_amount']);
        $this->assertEquals(round($devis['total_delivery_amount'] * 0.18), $devis['vat_amount'], 'TVA 18 % (D1)');
        $this->assertEquals(25000 - $devis['total_payable_charges'], $devis['current_payable'], 'net = COD − frais TTC');
        $this->assertNull($devis['customs'], 'un colis domestique n’a pas de volet douanier');

        $this->postJson('/api/v10/parcel/store', $payload, $this->entetes())->assertOk();

        $colis = $this->dernierColisDe('PIL-001');
        $this->assertEquals($devis['total_delivery_amount'], (float) $colis->total_delivery_amount);
        $this->assertEquals($devis['vat_amount'], (float) $colis->vat_amount);
        $this->assertEquals($devis['current_payable'], (float) $colis->current_payable);
        $this->assertNotEmpty($colis->tracking_id, 'le numéro de suivi est attribué par le serveur');
    }

    /** M3 — Export CEDEAO : l'alerte s'affiche, l'interdit est refusé, le prix est le forfait du pays quel que soit le poids. */
    public function test_m3_un_export_cedeao_alerte_est_refuse_s_il_est_interdit_et_se_facture_au_forfait(): void
    {
        $this->connecterMarchand('PIL-001');
        $export = ['zone_id' => $this->zone(DeliveryZone::CEDEAO), 'destination_country' => 'TG', 'customs_category' => 'textile'];

        $leger = $this->postJson('/api/v10/parcel/quote', $this->colisDe('PIL-001', $export + ['weight' => 1]), $this->entetes())->assertOk()->json('data');
        $lourd = $this->postJson('/api/v10/parcel/quote', $this->colisDe('PIL-001', $export + ['weight' => 9]), $this->entetes())->assertOk()->json('data');

        $this->assertEquals(12000, $leger['delivery_charge'], 'forfait Togo (D4)');
        $this->assertEquals($leger['delivery_charge'], $lourd['delivery_charge'], 'le forfait ne regarde pas le poids');
        $this->assertSame(CustomsLevel::WARNING, (int) $leger['customs']['level']);
        $this->assertFalse($leger['customs']['blocking']);
        $this->assertNotEmpty($leger['customs']['required_document']);

        // L'interdit : refusé sur le champ, aucun colis créé.
        $avant = Parcel::count();
        $this->postJson('/api/v10/parcel/store', $this->colisDe('PIL-001', [
            'zone_id' => $this->zone(DeliveryZone::CEDEAO), 'destination_country' => 'NG', 'customs_category' => 'alimentaire',
        ]), $this->entetes())->assertStatus(422);
        $this->assertSame($avant, Parcel::count());

        // L'avertissement : créé, et l'alerte apparaît dans l'écran Douane.
        $this->postJson('/api/v10/parcel/store', $this->colisDe('PIL-001', $export), $this->entetes())->assertOk();
        $colis = $this->dernierColisDe('PIL-001');
        $this->assertEquals(12000, (float) $colis->delivery_charge);
        $this->assertSame(1, CustomsAlert::where('parcel_id', $colis->id)->count());
        $this->getJson('/api/v10/customs/alerts', $this->entetes())->assertOk()
            ->assertJsonFragment(['parcel_id' => $colis->id]);
    }

    /** M4 + L4 + M8 — Livré avec photo et signature par le livreur ; le marchand le voit dans la chronologie et son fil. */
    public function test_m4_l4_m8_une_livraison_avec_preuves_remonte_dans_la_chronologie_et_le_fil_du_marchand(): void
    {
        $course = $this->courseEnCoursDe('LIV-001');

        $this->post("/api/v10/deliveryman/parcel/delivered/{$course->id}", [
            'note' => 'Remis en main propre',
            'image' => UploadedFile::fake()->image('colis.jpg', 640, 480),
            'signatureImage' => UploadedFile::fake()->image('signature.png', 600, 240),
        ], $this->entetes() + ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(ParcelStatus::DELIVERED, (int) $course->fresh()->status);
        $event = ParcelEvent::where('parcel_id', $course->id)->where('parcel_status', ParcelStatus::DELIVERED)->latest('id')->firstOrFail();
        $this->assertNotNull($event->delivered_image);
        $this->assertNotNull($event->signature_image);

        // Côté livreur : la course est passée dans « Livrés ».
        $tableau = $this->getJson('/api/v10/deliveryman/dashboard', $this->entetes())->assertOk()->json('data');
        $this->assertContains($course->id, array_column($tableau['delivered'], 'id'));

        // Côté marchand : la chronologie porte les deux preuves, en URL absolues.
        Sanctum::actingAs($course->merchant->user->fresh(), ['merchant']);
        $logs = $this->getJson("/api/v10/parcel/logs/{$course->id}", $this->entetes())->assertOk()->json('data.parcelEvents');
        $livre = collect($logs)->firstWhere('parcel_status', (string) ParcelStatus::DELIVERED);
        $this->assertNotNull($livre, 'la chronologie du marchand ne montre pas la livraison');
        $this->assertStringStartsWith('http', (string) $livre['delivered_image']);
        $this->assertStringStartsWith('http', (string) $livre['signature_image']);

        // Et le fil : une notification « statut » avec le compteur.
        $this->assertGreaterThanOrEqual(1, (int) $this->getJson('/api/v10/notifications/unread-count', $this->entetes())->assertOk()->json('data.unread_count'));
        $this->getJson('/api/v10/notifications/index', $this->entetes())->assertOk()
            ->assertJsonFragment(['tracking_id' => $course->tracking_id]);
    }

    /** M5 — Recharge Mobile Money sandbox : le solde ne bouge qu'au webhook, et une seule fois. */
    public function test_m5_la_recharge_mobile_money_ne_credite_qu_au_webhook_et_une_seule_fois(): void
    {
        $this->partialMock(FedaPayGateway::class, function ($mock) {
            $mock->shouldReceive('initialize')->once()->andReturn([
                'success' => true,
                'payment_url' => 'https://sandbox-checkout.fedapay.com/recette',
                'provider_transaction_id' => 'FP-RECETTE-1',
            ]);
        });

        $portefeuille = $this->marchand('PIL-002');
        $this->connecterMarchand('PIL-002');
        $soldeAvant = (int) round((float) $portefeuille->wallet_balance);

        $init = $this->postJson('/api/v10/fedapay/initiate', ['amount' => 5000], $this->entetes())->assertOk()->json('data');
        $this->assertStringStartsWith('https://', $init['payment_url'], 'l’app n’ouvre que l’URL rendue par le serveur');
        $this->assertSame($soldeAvant, $this->solde('PIL-002'), 'le retour de page ne crédite rien');

        $this->webhook('transaction.approved', 5000, 'FP-RECETTE-1')->assertOk();
        $this->assertSame($soldeAvant + 5000, $this->solde('PIL-002'), 'le webhook signé crédite');

        $this->webhook('transaction.approved', 5000, 'FP-RECETTE-1')->assertOk();
        $this->assertSame($soldeAvant + 5000, $this->solde('PIL-002'), 'un rejeu ne crédite pas deux fois');

        $this->getJson('/api/v10/wallet/history', $this->entetes())->assertOk()
            ->assertJsonFragment(['transaction_id' => $init['reference']]);
    }

    /** M6 — Retrait vers son propre compte Mobile Money ; le compte d'une autre PME est refusé. */
    public function test_m6_le_retrait_va_vers_son_propre_compte_seulement(): void
    {
        $mien = $this->marchand('PIL-001');
        $mien->current_balance = 100000;
        $mien->save();
        $this->connecterMarchand('PIL-001');

        $this->postJson('/api/v10/payment-account/store', [
            'payment_method' => PaymentMethod::mobile,
            'mobile_holder_name' => 'Aïcha Kora',
            'mobile_company' => 'MTN MoMo',
            'mobile_no' => '0022997010001',
            'account_type' => 'Personnel',
        ], $this->entetes())->assertOk();
        $monCompte = MerchantPayment::where('merchant_id', $mien->id)->latest('id')->firstOrFail();

        $autre = new MerchantPayment();
        $autre->forceFill([
            'merchant_id' => $this->marchand('PIL-003')->id, 'payment_method' => PaymentMethod::mobile,
            'holder_name' => 'Sênan Dossou', 'mobile_company' => 'Moov Money', 'mobile_no' => '22997010003', 'account_type' => 'Personnel',
        ])->save();

        $this->postJson('/api/v10/payment-request/store', ['amount' => 20000, 'merchant_account' => $autre->id], $this->entetes())->assertStatus(422);
        $this->postJson('/api/v10/payment-request/store', ['amount' => 20000, 'merchant_account' => $monCompte->id], $this->entetes())->assertOk();
    }

    /** M7 + A2 — Le relevé de PIL-001 : numérotation `PREFIXE-AAAA-NNNNNN`, liste, PDF par lien signé, journal SYSCOHADA équilibré. */
    public function test_m7_a2_le_releve_de_pil001_se_numerote_se_liste_se_telecharge_et_s_exporte(): void
    {
        $this->artisan('invoice:generate', ['--societe' => $this->societe])->assertSuccessful();

        $releve = Invoice::where('merchant_id', $this->marchand('PIL-001')->id)->first();
        $this->assertNotNull($releve, 'aucun relevé émis pour PIL-001 : le colis livré du jeu pilote n’a pas été facturé');
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+-\d{4}-\d{6}$/', $releve->invoice_id);

        $this->connecterMarchand('PIL-001');
        $this->getJson('/api/v10/invoice-list/index', $this->entetes())->assertOk()
            ->assertJsonFragment(['invoice_id' => $releve->invoice_id]);

        $url = $this->getJson('/api/v10/invoice-pdf-link/' . $releve->id, $this->entetes())->assertOk()->json('data.url');
        $pdf = $this->get($url)->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->artisan('beninlink:journal-syscohada', ['--societe' => $this->societe, '--du' => now()->toDateString(), '--au' => now()->toDateString()])
            ->expectsOutputToContain('Équilibré')->assertSuccessful();
    }

    /** M9 — Inscription d'une 6ᵉ PME : IFU (13 chiffres) et RCCM obligatoires ; le code SMS part en file et ouvre la session. */
    public function test_m9_l_inscription_exige_ifu_et_rccm_puis_le_code_sms_ouvre_la_session(): void
    {
        Queue::fake();
        $pme = [
            'business_name' => 'Boutique Zinsou', 'full_name' => 'Clarisse Zinsou', 'address' => 'Cadjèhoun, Cotonou',
            'mobile' => '0022997010006', 'password' => 'pilote2026', 'policy' => 1, 'hub_id' => 1,
            'ifu' => '3202600010006', 'rccm' => 'RB/COT/26 B 10006',
        ];

        $this->postJson('/api/v10/register', array_diff_key($pme, ['ifu' => 1, 'rccm' => 1]), $this->entetes())->assertStatus(422);
        $this->postJson('/api/v10/register', ['ifu' => '123'] + $pme, $this->entetes())->assertStatus(422);
        $this->postJson('/api/v10/register', $pme, $this->entetes())->assertOk();

        $compte = User::where('mobile', \App\Support\BeninPhone::normalize($pme['mobile']))->firstOrFail(); // S133 : rangé sous une seule forme
        $this->assertMatchesRegularExpression('/^\d{5}$/', (string) $compte->otp);
        Queue::assertPushed(SendSms::class);

        $this->postJson('/api/v10/otp-verification', ['mobile' => $pme['mobile'], 'otp' => $compte->otp], $this->entetes())
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    /** M10 — Mot de passe oublié : le lien part, le nouveau mot de passe est accepté et ouvre la session. */
    public function test_m10_le_mot_de_passe_oublie_envoie_un_lien_et_accepte_le_nouveau(): void
    {
        Notification::fake();
        $compte = $this->utilisateur('PIL-001');

        $this->postJson('/api/v10/password/email', ['email' => $compte->email], $this->entetes())->assertOk();
        Notification::assertSentTo($compte, ResetPassword::class);

        $this->postJson('/api/v10/password/reset', [
            'email' => $compte->email, 'token' => Password::broker()->createToken($compte),
            'password' => 'kora-2026-ok', 'password_confirmation' => 'kora-2026-ok',
        ], $this->entetes())->assertOk();

        $this->postJson('/api/v10/signin', ['merchant_id' => 'PIL-001', 'password' => 'kora-2026-ok'], $this->entetes())->assertOk();
        $this->postJson('/api/v10/signin', ['merchant_id' => 'PIL-001', 'password' => PiloteDataset::PASSWORD], $this->entetes())->assertStatus(401);
    }

    /* ═══════════════════════════ Livreur (app `mobile-livreur/`) ════════════════ */

    /** L1 — Connexion avec LIV-001 ; un identifiant marchand est refusé. */
    public function test_l1_la_connexion_livreur_refuse_un_identifiant_marchand(): void
    {
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'LIV-001', 'password' => PiloteDataset::PASSWORD], $this->entetes())
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'PIL-001', 'password' => PiloteDataset::PASSWORD], $this->entetes())
            ->assertStatus(401);
    }

    /** L2 + L3 — Mes courses : les trois onglets sont servis ; chaque course porte téléphone et adresse (Appeler, Itinéraire) ; le détail est complet. */
    public function test_l2_l3_mes_courses_et_le_detail_portent_ce_que_le_livreur_doit_voir(): void
    {
        $this->connecterLivreur('LIV-001');
        $tableau = $this->getJson('/api/v10/deliveryman/dashboard', $this->entetes())->assertOk()->json('data');

        foreach (['deliveryman_assign', 'delivered', 'return_to_courier'] as $onglet) {
            $this->assertNotEmpty($tableau[$onglet], "l'onglet $onglet du jeu pilote est vide");
        }
        foreach ($tableau['deliveryman_assign'] as $course) {
            $this->assertNotEmpty($course['customer_phone'], 'Appeler');
            $this->assertNotEmpty($course['customer_address'], 'Itinéraire');
            $this->assertStringStartsWith(PiloteDataset::TRACKING_PREFIX, $course['tracking_id']);
        }

        $detail = $this->getJson('/api/v10/deliveryman/parcel/details/' . $tableau['deliveryman_assign'][0]['id'], $this->entetes())
            ->assertOk()->json('data');
        foreach (['customer_name', 'customer_phone', 'customer_address', 'cash_collection'] as $champ) {
            $this->assertNotNull($detail['parcel'][$champ] ?? null, "le détail ne porte pas $champ");
        }
        $this->assertNotEmpty($detail['parcel']['merchant']['business_name'] ?? null, 'le détail ne nomme pas le marchand');
        $this->assertNotEmpty($detail['parcel']['merchant']['address'] ?? null, "le détail ne porte pas l'adresse d'enlèvement");
        $this->assertNotEmpty($detail['parcelEvents'], 'le détail ne porte pas l\'historique');
    }

    /** L7 — Livraison partielle : montant encaissé obligatoire ; le net est recalculé côté serveur. */
    public function test_l7_la_livraison_partielle_exige_le_montant_et_recalcule_le_net_cote_serveur(): void
    {
        $course = $this->courseEnCoursDe('LIV-001');
        $netAvant = (float) $course->current_payable;

        $this->postJson("/api/v10/deliveryman/parcel/partial-delivered/{$course->id}", [], $this->entetes())->assertStatus(422);
        $this->postJson("/api/v10/deliveryman/parcel/partial-delivered/{$course->id}", ['cash_collection' => 5000], $this->entetes())->assertOk();

        $apres = $course->fresh();
        $this->assertSame(ParcelStatus::PARTIAL_DELIVERED, (int) $apres->status);
        $this->assertEquals(5000, (float) $apres->cash_collection);
        $this->assertLessThan($netAvant, (float) $apres->current_payable, 'le net doit avoir été recalculé par le serveur');
        $this->assertEquals(5000 - ((float) $apres->total_delivery_amount + (float) $apres->vat_amount), (float) $apres->current_payable);
    }

    /** L8 — Retour : la course passe dans « Retours ». */
    public function test_l8_le_retour_passe_la_course_dans_retours(): void
    {
        $course = $this->courseEnCoursDe('LIV-001');

        $this->postJson('/api/v10/deliveryman/parcel-status-update', [
            'parcel_id' => $course->id, 'status_action' => ParcelStatus::RETURN_TO_COURIER,
        ], $this->entetes())->assertOk();

        $this->assertSame(ParcelStatus::RETURN_TO_COURIER, (int) $course->fresh()->status);
        $tableau = $this->getJson('/api/v10/deliveryman/dashboard', $this->entetes())->assertOk()->json('data');
        $this->assertContains($course->id, array_column($tableau['return_to_courier'], 'id'));
    }

    /** L9 — Partager ma position : elle s'écrit sur les courses en cours du livreur. */
    public function test_l9_la_position_partagee_s_ecrit_sur_les_courses_en_cours(): void
    {
        $course = $this->courseEnCoursDe('LIV-001');

        $this->postJson('/api/v10/deliveryman/parcel-location-update', ['lat' => '6.3703', 'long' => '2.3912'], $this->entetes())->assertOk();

        $event = ParcelEvent::where('parcel_id', $course->id)->whereNotNull('delivery_man_id')->latest('id')->firstOrFail();
        $this->assertSame('6.3703', (string) $event->delivery_lat);
        $this->assertSame('2.3912', (string) $event->delivery_long);
    }

    /** L10 — Gains : solde, gains et encaissements se lisent après une livraison. */
    public function test_l10_les_gains_se_lisent_apres_une_livraison(): void
    {
        $course = $this->courseEnCoursDe('LIV-001');
        $this->postJson("/api/v10/deliveryman/parcel/delivered/{$course->id}", [], $this->entetes())->assertOk();

        $this->getJson('/api/v10/deliveryman/profile', $this->entetes())->assertOk();
        $this->getJson('/api/v10/deliveryman/income-expense', $this->entetes())->assertOk();
        $this->getJson('/api/v10/deliveryman/parcel-payment-logs', $this->entetes())->assertOk();
    }

    /** L11 — Changement de mot de passe, puis reconnexion. */
    public function test_l11_le_changement_de_mot_de_passe_puis_la_reconnexion(): void
    {
        $this->connecterLivreur('LIV-001');

        $this->putJson('/api/v10/update-password', [
            'old_password' => PiloteDataset::PASSWORD, 'new_password' => 'kossi-2026', 'confirm_password' => 'kossi-2026',
        ], $this->entetes())->assertOk();

        $this->fermerLaSession();
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'LIV-001', 'password' => 'kossi-2026'], $this->entetes())->assertOk();
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'LIV-001', 'password' => PiloteDataset::PASSWORD], $this->entetes())->assertStatus(401);
    }

    /* ═══════════════════════════ Administration (`web/`) ════════════════════════ */

    /** A4 — Liste noire : une fiche de PIL-001 est visible par PIL-002, modifiable par PIL-001 seulement. */
    public function test_a4_la_liste_noire_se_partage_mais_ne_se_modifie_que_par_son_auteur(): void
    {
        $this->connecterMarchand('PIL-001');
        $this->postJson('/api/v10/fraud/store', ['phone' => '0022990000001', 'name' => 'Client fantôme', 'details' => 'Trois refus de colis'], $this->entetes())->assertOk();
        $fiche = \App\Models\Backend\Fraud::where('phone', '2290190000001')->firstOrFail(); // S133 : numéro rangé

        $this->connecterMarchand('PIL-002');
        $this->getJson('/api/v10/fraud/index', $this->entetes())->assertOk()->assertJsonFragment(['phone' => '2290190000001']);
        $this->putJson("/api/v10/fraud/update/{$fiche->id}", ['phone' => '0022990000001', 'name' => 'X', 'details' => 'x'], $this->entetes())->assertStatus(404);

        $this->connecterMarchand('PIL-001');
        $this->putJson("/api/v10/fraud/update/{$fiche->id}", ['phone' => '0022990000001', 'name' => 'Client fantôme', 'details' => 'Quatre refus'], $this->entetes())->assertOk();
    }

    /** A5 — Aamarpay et SSLCommerz sont coupées (S21). */
    public function test_a5_aamarpay_et_sslcommerz_sont_coupees(): void
    {
        $this->assertFalse(gatewayEnabled(PayoutSetup::AAMARPAY));
        $this->assertFalse(gatewayEnabled(PayoutSetup::SSL_COMMERZ));
        $this->assertFalse(onlinePayoutEnabled(), 'D10 — le module payout reste coupé');
    }

    /* ═══════════════════════════ Sécurité (à rejouer après chaque livraison) ════ */

    /** S1 — Un jeton de l'autre type est refusé (403). */
    public function test_s1_un_jeton_de_l_autre_type_est_refuse(): void
    {
        $this->connecterMarchand('PIL-001');
        $this->getJson('/api/v10/deliveryman/dashboard', $this->entetes())->assertStatus(403);

        $this->connecterLivreur('LIV-001');
        $this->getJson('/api/v10/parcel/index', $this->entetes())->assertStatus(403);
    }

    /** S2 — Le colis d'une autre PME est introuvable (404). */
    public function test_s2_le_colis_d_une_autre_pme_est_introuvable(): void
    {
        $sien = Parcel::where('merchant_id', $this->marchand('PIL-003')->id)->firstOrFail();
        $this->connecterMarchand('PIL-001');

        $this->getJson("/api/v10/parcel/details/{$sien->id}", $this->entetes())->assertStatus(404);
        $this->getJson("/api/v10/parcel/logs/{$sien->id}", $this->entetes())->assertStatus(404);
    }

    /** S4 — Les trois constats sont vides sur le jeu pilote (un constat non vide est un vrai problème). */
    public function test_s4_les_trois_constats_sont_vides_sur_le_jeu_pilote(): void
    {
        $this->artisan('beninlink:colis-non-debites')->expectsOutputToContain('Aucun colis non débité')->assertSuccessful();
        $this->artisan('beninlink:ecarts-marchands')->expectsOutputToContain('Aucun écart')->assertSuccessful();
        $this->artisan('beninlink:retours-annules', ['--societe' => $this->societe])->expectsOutputToContain('Aucun retour annulé sans réversion')->assertSuccessful();
    }

    /** S5 — La tarification de la société pilote est en état de facturer (D4). */
    public function test_s5_la_tarification_de_la_societe_pilote_est_prete(): void
    {
        $this->artisan('beninlink:tarification-prete', ['--societe' => $this->societe])
            ->expectsOutputToContain('tarife par zones')->assertSuccessful();
    }

    /** S6 — Un colis sans zone est refusé par l'API : la route est le seul axe de tarification. */
    public function test_s6_un_colis_sans_zone_est_refuse(): void
    {
        $this->connecterMarchand('PIL-001');
        $payload = $this->colisDe('PIL-001');
        unset($payload['zone_id']);

        $this->postJson('/api/v10/parcel/store', $payload, $this->entetes())->assertStatus(422);
    }

    /** S7 — Les deux fichiers modèles d'import portent la colonne `zone_code`. */
    public function test_s7_les_fichiers_modeles_d_import_portent_zone_code(): void
    {
        foreach (['merchantParcel', 'parcel'] as $dossier) {
            $feuille = \PhpOffice\PhpSpreadsheet\IOFactory::load(public_path("sample-parcel/$dossier/import-parcel.xlsx"))->getActiveSheet();
            $this->assertContains('zone_code', $feuille->rangeToArray('A1:Z1')[0], $dossier);
        }
    }

    /** S8 — La file d'attente se lit ; en recette sans worker elle dit qu'elle est en `sync`. */
    public function test_s8_la_file_d_attente_se_lit(): void
    {
        $this->artisan('beninlink:file-attente')->expectsOutputToContain("rien n'est mis en file")->run();
    }

    /* ═══════════════════════════ fixtures et aides ═════════════════════════════ */

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function marchand(string $code): Merchant
    {
        return Merchant::where('merchant_unique_id', $code)->firstOrFail();
    }

    private function utilisateur(string $code): User
    {
        return User::where('unique_id', $code)->firstOrFail();
    }

    private function connecterMarchand(string $code): void
    {
        Sanctum::actingAs($this->utilisateur($code), ['merchant']);
    }

    private function connecterLivreur(string $code): void
    {
        Sanctum::actingAs($this->utilisateur($code), ['deliveryman']);
    }

    /**
     * `Sanctum::actingAs()` fait du garde `sanctum` le garde par défaut ; `Auth::attempt()`
     * (la connexion) n'existe que sur le garde `web`. Pour se reconnecter après une
     * session simulée, on rend la main au garde par défaut.
     */
    private function fermerLaSession(): void
    {
        // ⚠️ Pas `config('auth.defaults.guard')` : `Sanctum::actingAs()` l'a déjà réécrit en `sanctum`.
        auth()->shouldUse('web');
        auth()->forgetGuards();
    }

    private function zone(string $code): int
    {
        return (int) DeliveryZone::where('company_id', $this->societe)->where('code', $code)->value('id');
    }

    /** Le colis tel que l'écran de création l'envoie : boutique, catégorie, route, destinataire. */
    private function colisDe(string $code, array $extra = []): array
    {
        $marchand = $this->marchand($code);

        return array_merge([
            'shop_id' => MerchantShops::where('merchant_id', $marchand->id)->value('id'),
            'category_id' => Deliverycategory::where('company_id', $this->societe)->orderBy('id')->value('id'),
            'delivery_type_id' => 2,
            'zone_id' => $this->zone(DeliveryZone::COTONOU),
            'weight' => 1,
            'customer_name' => 'Adjoua Mensah',
            'customer_phone' => '0022995030001',
            'customer_address' => 'Rue 1042, Cadjèhoun, Cotonou',
            'cash_collection' => 15000,
        ], $extra);
    }

    private function dernierColisDe(string $code): Parcel
    {
        return Parcel::where('merchant_id', $this->marchand($code)->id)->latest('id')->firstOrFail();
    }

    /** Une course « Livreur assigné » du jeu pilote, lue sur le tableau du livreur connecté. */
    private function courseEnCoursDe(string $code): Parcel
    {
        $this->connecterLivreur($code);
        $tableau = $this->getJson('/api/v10/deliveryman/dashboard', $this->entetes())->assertOk()->json('data');
        $this->assertNotEmpty($tableau['deliveryman_assign'], "$code n'a aucune course en cours dans le jeu pilote");

        return Parcel::findOrFail($tableau['deliveryman_assign'][0]['id']);
    }

    private function solde(string $code): int
    {
        return (int) round((float) $this->marchand($code)->wallet_balance);
    }

    /** Le webhook FedaPay, signé comme le ferait la passerelle (même forme que `FedaPayWalletOutcomeTest`). */
    private function webhook(string $evenement, int $montant, string $providerId)
    {
        $payload = json_encode(['name' => $evenement, 'entity' => ['id' => $providerId, 'amount' => $montant]]);
        $horodatage = time();

        return $this->call('POST', '/fedapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => "t={$horodatage},s=" . hash_hmac('sha256', $horodatage . '.' . $payload, self::WEBHOOK_SECRET),
        ], $payload);
    }
}
