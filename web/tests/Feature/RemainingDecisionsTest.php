<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Setting;
use App\Models\Backend\Wallet;
use App\Models\MerchantShops;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Les trois décisions laissées ouvertes par la revue, tranchées le 2026-09-05.
 *
 * 1. Les lignes de `settings` sans société ne sont **pas** rattachées : le
 *    seeder visait la société 1 et l'écran de réglages visait la société
 *    connectée, sans qu'on puisse les distinguer. Les attribuer à la société 1
 *    donnerait la clé d'un locataire à un autre. Une commande les constate.
 * 2. Le débit du portefeuille devient **atomique** avec la création du colis —
 *    couvert par `ParcelWalletDebitAtomicityTest`.
 * 3. Les deux chemins d'administration non scopés par société sont fermés.
 */
class RemainingDecisionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;
    private GeneralSettings $voisine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        $this->merchant = Merchant::firstOrFail();

        // Une seconde société, pour tester la frontière.
        $this->voisine = GeneralSettings::findOrFail($this->merchant->company_id)->replicate();
        $this->voisine->name = 'Transporteur voisin';
        $this->voisine->save();

        Auth::login($this->merchant->user);
    }

    // ---- Décision 3a : le statut d'un colis, côté administration ------------

    private function colisDe(int $companyId, string $suffixe): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $companyId,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 48500,
            'tracking_id' => 'TEST-DEC-' . $suffixe,
            'status' => ParcelStatus::PENDING,
        ])->save();

        return $parcel;
    }

    public function test_an_admin_cannot_move_a_parcel_of_another_company(): void
    {
        $sien = $this->colisDe($this->merchant->company_id, 'A');
        $celuiDuVoisin = $this->colisDe((int) $this->voisine->id, 'B');

        $repo = app(ParcelInterface::class);

        $this->assertFalse($repo->statusUpdate($celuiDuVoisin->id, ParcelStatus::DELIVERED));
        $this->assertSame(ParcelStatus::PENDING, (int) $celuiDuVoisin->fresh()->status);

        // Sur le sien, l'administration garde la main.
        $this->assertTrue($repo->statusUpdate($sien->id, ParcelStatus::PICKUP_ASSIGN));
        $this->assertSame(ParcelStatus::PICKUP_ASSIGN, (int) $sien->fresh()->status);
    }

    // ---- Décision 3b : les actions sur un portefeuille ----------------------

    private function rechargeDe(int $companyId): Wallet
    {
        $wallet = new Wallet();
        $wallet->company_id = $companyId;
        $wallet->user_id = $this->merchant->user_id;
        $wallet->merchant_id = $this->merchant->id;
        $wallet->source = 'Wallet Recharge';
        $wallet->transaction_id = 'BL-DEC-' . $companyId;
        $wallet->amount = 5000;
        $wallet->type = WalletType::INCOME;
        $wallet->payment_method = WalletPaymentMethod::OFFLINE;
        $wallet->status = WalletStatus::PENDING;
        $wallet->save();

        return $wallet;
    }

    /**
     * Les trois routes d'administration passent par le même garde. Le service,
     * lui, reste sans scope : le webhook FedaPay l'appelle sans session.
     */
    public function test_the_admin_wallet_actions_stay_inside_the_company(): void
    {
        $celleDuVoisin = $this->rechargeDe((int) $this->voisine->id);
        $laSienne = $this->rechargeDe((int) $this->merchant->company_id);

        $controleur = app(\App\Http\Controllers\Backend\MerchantPanel\WalletController::class);
        $garde = new \ReflectionMethod($controleur, 'proprieteVerifiee');
        $garde->setAccessible(true);

        try {
            $garde->invoke($controleur, $celleDuVoisin->id);
            $this->fail('La recharge d\'une autre société aurait dû être refusée.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }

        // La sienne passe le garde sans lever.
        $garde->invoke($controleur, $laSienne->id);
        $this->assertTrue(true);
    }

    // ---- Décision 1 : les lignes de réglages sans société -------------------

    public function test_the_command_reports_orphan_rows_without_reattaching_them(): void
    {
        Setting::insert([
            ['company_id' => null, 'key' => 'stripe_secret_key', 'value' => 'sk_orpheline'],
            ['company_id' => null, 'key' => 'stripe_status', 'value' => (string) Status::ACTIVE],
        ]);

        $this->artisan('beninlink:reglages-orphelins')
            ->expectsOutputToContain('2 ligne(s) de `settings` sans société')
            ->expectsOutputToContain('Réglages → Pay-out')
            ->assertSuccessful();

        // Rien n'a bougé : ni rattachement, ni suppression.
        $this->assertSame(2, Setting::whereNull('company_id')->count());
    }

    /** Aucun secret n'est affiché, seulement le fait qu'il y en a un. */
    public function test_the_command_never_prints_a_secret(): void
    {
        Setting::insert([['company_id' => null, 'key' => 'stripe_secret_key', 'value' => 'sk_a_ne_pas_montrer']]);

        $this->artisan('beninlink:reglages-orphelins')
            ->doesntExpectOutputToContain('sk_a_ne_pas_montrer')
            ->assertSuccessful();
    }

    public function test_the_purge_is_explicit_and_removes_only_orphan_rows(): void
    {
        Setting::insert([['company_id' => null, 'key' => 'paypal_client_id', 'value' => 'orpheline']]);
        Setting::create(['company_id' => $this->merchant->company_id, 'key' => 'paypal_client_id', 'value' => 'la-mienne']);

        $this->artisan('beninlink:reglages-orphelins --purge')->assertSuccessful();

        $this->assertSame(0, Setting::whereNull('company_id')->count());
        $this->assertSame('la-mienne', Setting::where('company_id', $this->merchant->company_id)
            ->where('key', 'paypal_client_id')->value('value'));
    }

    public function test_a_clean_install_is_told_so(): void
    {
        Setting::whereNull('company_id')->delete();

        $this->artisan('beninlink:reglages-orphelins')
            ->expectsOutputToContain('Aucune ligne orpheline')
            ->assertSuccessful();
    }
}
