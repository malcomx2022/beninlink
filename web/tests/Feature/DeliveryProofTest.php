<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Preuve de livraison : la photo (`image`) et la signature manuscrite du
 * destinataire (`signatureImage`) que le livreur joint à « Livré ». Le socle
 * les stockait déjà (parcel_events.delivered_image / signature_image) mais ne
 * les montrait qu'au panneau d'administration ; le marchand les voit désormais
 * dans le suivi de son colis (API `parcel/details`, `parcel/logs`).
 */
class DeliveryProofTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;
    private User $livreur;
    private Parcel $colis;

    /** Fichiers déposés sous public/uploads par le test, à retirer après. */
    private array $fichiers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();
        $this->livreur = $this->livreur();
        $this->colis = $this->colisConfieA($this->livreur);
    }

    protected function tearDown(): void
    {
        foreach ($this->fichiers as $fichier) {
            @unlink(public_path($fichier));
        }
        parent::tearDown();
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function livreur(): User
    {
        $user = $this->merchant->user->replicate();
        $user->name = 'Livreur signature';
        $user->email = 'livreur-signature@example.test';
        $user->mobile = '0022998000001';
        $user->unique_id = 'L-SIGN';
        $user->user_type = UserType::DELIVERYMAN;
        $user->password = Hash::make('secret123');
        $user->save();

        DeliveryMan::forceCreate([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 0, 'pickup_charge' => 0, 'return_charge' => 0,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);

        return $user->fresh();
    }

    private function colisConfieA(User $livreur): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'hub_id' => $livreur->hub_id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Porto-Novo',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => 'TEST-SIGN',
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ])->save();

        $event = new ParcelEvent();
        $event->forceFill([
            'parcel_id' => $parcel->id,
            'parcel_status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
            'delivery_man_id' => $livreur->deliveryman->id,
            'created_by' => $this->merchant->user_id,
        ])->save();

        return $parcel;
    }

    private function evenementLivre(): ParcelEvent
    {
        $event = ParcelEvent::where('parcel_id', $this->colis->id)
            ->where('parcel_status', ParcelStatus::DELIVERED)
            ->firstOrFail();
        $this->fichiers = array_filter([$event->delivered_image, $event->signature_image]);

        return $event;
    }

    public function test_the_deliveryman_attaches_a_signature_and_a_photo_to_a_delivery(): void
    {
        Sanctum::actingAs($this->livreur, ['deliveryman']);

        $this->post("/api/v10/deliveryman/parcel/delivered/{$this->colis->id}", [
            'note' => 'Remis en main propre',
            'image' => UploadedFile::fake()->image('colis.jpg', 640, 480),
            'signatureImage' => UploadedFile::fake()->image('signature.png', 600, 240),
        ], $this->entetes() + ['Accept' => 'application/json'])->assertOk();

        $event = $this->evenementLivre();

        $this->assertSame('Remis en main propre', $event->note);
        $this->assertStringStartsWith('uploads/parcel/image/', $event->delivered_image);
        $this->assertStringStartsWith('uploads/parcel/signature/', $event->signature_image);
        $this->assertStringEndsWith('.png', $event->signature_image);
        $this->assertFileExists(public_path($event->delivered_image));
        $this->assertFileExists(public_path($event->signature_image));
        $this->assertSame(ParcelStatus::DELIVERED, (int) $this->colis->fresh()->status);
    }

    public function test_the_proof_is_optional(): void
    {
        Sanctum::actingAs($this->livreur, ['deliveryman']);

        $this->postJson("/api/v10/deliveryman/parcel/delivered/{$this->colis->id}", [], $this->entetes())->assertOk();

        $event = $this->evenementLivre();
        $this->assertNull($event->delivered_image);
        $this->assertNull($event->signature_image);
    }

    public function test_the_merchant_sees_the_proof_in_the_parcel_timeline(): void
    {
        Sanctum::actingAs($this->livreur, ['deliveryman']);
        $this->post("/api/v10/deliveryman/parcel/delivered/{$this->colis->id}", [
            'signatureImage' => UploadedFile::fake()->image('signature.png', 600, 240),
        ], $this->entetes() + ['Accept' => 'application/json'])->assertOk();
        $event = $this->evenementLivre();

        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        foreach (["parcel/details/{$this->colis->id}", "parcel/logs/{$this->colis->id}"] as $chemin) {
            $reponse = $this->getJson('/api/v10/' . $chemin, $this->entetes())->assertOk();
            $livre = collect($reponse->json('data.parcelEvents'))
                ->firstWhere('parcel_status', (string) ParcelStatus::DELIVERED);

            $this->assertNotNull($livre, $chemin);
            // URL absolue, prête à afficher : l'app n'a pas à connaître public/uploads.
            $this->assertStringStartsWith('http', $livre['signature_image'], $chemin);
            $this->assertStringEndsWith($event->signature_image, $livre['signature_image'], $chemin);
            $this->assertNull($livre['delivered_image'], $chemin);
        }
    }
}
