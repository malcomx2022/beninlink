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
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * W2 — la position du livreur ne doit toucher que ses courses en cours.
 *
 * La portée n'était bornée que par le livreur : chaque partage de position
 * réécrivait `delivery_lat` / `delivery_long` sur tous ses `parcel_events`, y
 * compris ceux de livraisons déjà closes. La coordonnée enregistrée au moment
 * d'une livraison ancienne — sa preuve géographique — était donc détruite à
 * chaque appel, et l'app livreur appelle cette route silencieusement après
 * chaque déclaration de livraison.
 */
class DeliverymanLocationScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    /** Coordonnées posées au moment d'une livraison passée, à ne plus jamais toucher. */
    private const LAT_HISTORIQUE = '6.3654';
    private const LONG_HISTORIQUE = '2.4183';

    private const LAT_COURANTE = '6.4969';
    private const LONG_COURANTE = '2.6289';

    private Merchant $merchant;
    private User $livreur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();
        $this->livreur = $this->livreur();
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function livreur(): User
    {
        $user = $this->merchant->user->replicate();
        $user->name = 'Livreur position';
        $user->email = 'livreur-w2@example.test';
        $user->mobile = '0022998000021';
        $user->unique_id = 'L-W2';
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

    /** Un colis confié à ce livreur, dans le statut demandé, avec son événement. */
    private function course(int $statut, string $suffixe, ?string $lat = null, ?string $long = null): ParcelEvent
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'hub_id' => $this->livreur->hub_id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client ' . $suffixe,
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 48500,
            'tracking_id' => 'TEST-W2-' . $suffixe,
            'status' => $statut,
        ])->save();

        $event = new ParcelEvent();
        $event->forceFill([
            'parcel_id' => $parcel->id,
            'parcel_status' => $statut,
            'delivery_man_id' => $this->livreur->deliveryman->id,
            'created_by' => $this->merchant->user_id,
            'delivery_lat' => $lat,
            'delivery_long' => $long,
        ])->save();

        return $event;
    }

    private function partagerPosition()
    {
        Sanctum::actingAs($this->livreur, ['deliveryman']);

        return $this->postJson('/api/v10/deliveryman/parcel-location-update', [
            'lat' => self::LAT_COURANTE,
            'long' => self::LONG_COURANTE,
        ], $this->entetes());
    }

    public function test_an_in_progress_course_receives_the_position(): void
    {
        $enCours = $this->course(ParcelStatus::DELIVERY_MAN_ASSIGN, 'A');

        $this->partagerPosition()->assertOk();

        $this->assertSame(self::LAT_COURANTE, (string) $enCours->fresh()->delivery_lat);
        $this->assertSame(self::LONG_COURANTE, (string) $enCours->fresh()->delivery_long);
    }

    /** Le cœur du constat : la preuve géographique d'une livraison passée survit. */
    public function test_a_closed_delivery_keeps_its_recorded_position(): void
    {
        $livre = $this->course(ParcelStatus::DELIVERED, 'B', self::LAT_HISTORIQUE, self::LONG_HISTORIQUE);
        $partiel = $this->course(ParcelStatus::PARTIAL_DELIVERED, 'C', self::LAT_HISTORIQUE, self::LONG_HISTORIQUE);

        $this->partagerPosition()->assertOk();

        foreach ([$livre, $partiel] as $clos) {
            $this->assertSame(self::LAT_HISTORIQUE, (string) $clos->fresh()->delivery_lat);
            $this->assertSame(self::LONG_HISTORIQUE, (string) $clos->fresh()->delivery_long);
        }
    }

    /** Un retour reste en main du livreur : sa position a encore un sens. */
    public function test_a_return_in_hand_still_receives_the_position(): void
    {
        $retour = $this->course(ParcelStatus::RETURN_TO_COURIER, 'D');

        $this->partagerPosition()->assertOk();

        $this->assertSame(self::LAT_COURANTE, (string) $retour->fresh()->delivery_lat);
    }

    /** Les courses d'un collègue ne bougent pas : le correctif S7 tient toujours. */
    public function test_a_colleague_course_is_never_touched(): void
    {
        $enCours = $this->course(ParcelStatus::DELIVERY_MAN_ASSIGN, 'E');

        $collegue = $this->merchant->user->replicate();
        $collegue->email = 'collegue-w2@example.test';
        $collegue->mobile = '0022998000022';
        $collegue->unique_id = 'L-W2-BIS';
        $collegue->user_type = UserType::DELIVERYMAN;
        $collegue->save();
        $autre = DeliveryMan::forceCreate([
            'company_id' => $collegue->company_id,
            'user_id' => $collegue->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 0, 'pickup_charge' => 0, 'return_charge' => 0,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);

        $sonEvenement = $enCours->replicate();
        $sonEvenement->delivery_man_id = $autre->id;
        $sonEvenement->delivery_lat = self::LAT_HISTORIQUE;
        $sonEvenement->delivery_long = self::LONG_HISTORIQUE;
        $sonEvenement->save();

        $this->partagerPosition()->assertOk();

        $this->assertSame(self::LAT_HISTORIQUE, (string) $sonEvenement->fresh()->delivery_lat);
        $this->assertSame(self::LAT_COURANTE, (string) $enCours->fresh()->delivery_lat);
    }
}
