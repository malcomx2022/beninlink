<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\MerchantShops;
use App\Models\User;
use App\Repositories\Invoice\InvoiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * W1 — `GET /api/v10/parcel/{id}/status/{statusId}` était un setter nu.
 *
 * Un marchand pouvait se déclarer lui-même « Livré » sur un colis jamais
 * ramassé — sans `parcel_events`, sans écriture comptable — puis voir ce colis
 * entrer au relevé de règlement et réclamer au transporteur un encaissement qui
 * n'avait jamais eu lieu. Une valeur hors énumération, `999`, était écrite telle
 * quelle en base.
 *
 * Le socle ne donne au marchand aucun levier sur le cycle de vie : son seul
 * geste légitime est de supprimer un colis encore « En attente » (`destroy`,
 * 422 au-delà). La liste blanche est donc vide, et ces tests la verrouillent.
 */
class MerchantParcelStatusGuardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $this->merchant = Merchant::firstOrFail();
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function colisDe(Merchant $merchant, string $suffixe = 'A'): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $merchant->company_id,
            'merchant_id' => $merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'total_delivery_amount' => 1500,
            'vat_amount' => 270,
            'current_payable' => 48230,
            'tracking_id' => 'TEST-W1-' . $suffixe,
            'status' => ParcelStatus::PENDING,
        ])->save();

        return $parcel;
    }

    /** Un second marchand de la MÊME société, pour distinguer 404 et 422. */
    private function voisin(): Merchant
    {
        $utilisateur = $this->merchant->user->replicate();
        $utilisateur->email = 'voisin-w1@example.test';
        $utilisateur->mobile = '0022997000019';
        $utilisateur->unique_id = 'U-VOISIN-W1';
        $utilisateur->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $utilisateur->id;
        $voisin->merchant_unique_id = 'M-VOISIN-W1';
        $voisin->save();

        return $voisin;
    }

    private function connecter(?User $utilisateur = null): void
    {
        Sanctum::actingAs(($utilisateur ?? $this->merchant->user)->fresh(), ['merchant']);
    }

    public function test_a_merchant_cannot_declare_his_own_parcel_delivered(): void
    {
        $colis = $this->colisDe($this->merchant);
        $this->connecter();

        $this->getJson("/api/v10/parcel/{$colis->id}/status/" . ParcelStatus::DELIVERED, $this->entetes())
            ->assertStatus(422);

        $this->assertSame(ParcelStatus::PENDING, (int) $colis->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $colis->id)->count());
    }

    public function test_a_status_outside_the_enum_is_refused(): void
    {
        $colis = $this->colisDe($this->merchant);
        $this->connecter();

        $this->getJson("/api/v10/parcel/{$colis->id}/status/999", $this->entetes())
            ->assertStatus(422);

        $this->assertSame(ParcelStatus::PENDING, (int) $colis->fresh()->status);
    }

    /**
     * Le cœur du constat : sans le garde-fou, le colis auto-déclaré livré entrait
     * au relevé de règlement et y portait un encaissement fictif.
     */
    public function test_a_self_declared_parcel_never_reaches_the_settlement_statement(): void
    {
        $colis = $this->colisDe($this->merchant);
        $this->connecter();

        $this->getJson("/api/v10/parcel/{$colis->id}/status/" . ParcelStatus::DELIVERED, $this->entetes())
            ->assertStatus(422);

        // Période de règlement échue : la génération n'attend plus que des colis.
        $this->merchant->payment_period = 0;
        $this->merchant->save();

        app(InvoiceInterface::class)->store($this->merchant->id);

        $this->assertSame(0, Invoice::where('merchant_id', $this->merchant->id)->count());
        $this->assertNull($colis->fresh()->invoice_id);
    }

    /** Le colis d'un autre marchand reste un 404 : on ne révèle pas son existence. */
    public function test_a_foreign_parcel_still_answers_404(): void
    {
        $sonColis = $this->colisDe($this->voisin(), 'B');
        $this->connecter();

        $this->getJson("/api/v10/parcel/{$sonColis->id}/status/" . ParcelStatus::DELIVERED, $this->entetes())
            ->assertStatus(404);

        $this->assertSame(ParcelStatus::PENDING, (int) $sonColis->fresh()->status);
    }

    /** La liste blanche est le seul point à modifier pour ouvrir une transition. */
    public function test_the_whitelist_is_the_single_lever(): void
    {
        $this->assertSame(
            [],
            \App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelRepository::MERCHANT_ALLOWED_STATUSES,
        );
    }
}
