<?php

namespace Tests\Feature;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\CustomsAlert;
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
 * **S95** — le livreur voit l'alerte douanière de sa course.
 *
 * Au ramassage d'un colis export, le livreur doit demander au marchand le
 * document que la règle exige ; le socle ne lui disait rien. Comme pour le
 * marchand (S82) et les fiches web (S94), les alertes voyagent **avec le colis
 * déjà vérifié** (`notOwned()`, S7) : `deliveryman/parcel/details/{id}` porte
 * `customs_alerts`, vide pour un domestique, jamais celles d'une autre course.
 * Le livreur **lit** : aucune route de traitement ne lui est ouverte (S68).
 */
class DeliverymanCustomsAlertTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const CLE = 'cle-de-test';

    private Merchant $marchand;
    private User $livreur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::CLE, 'app.app_installed' => 'yes']);

        $this->marchand = Merchant::firstOrFail();
        $this->livreur = $this->livreur('A');
        Sanctum::actingAs($this->livreur, ['deliveryman']);
    }

    private function entetes(): array
    {
        return ['apiKey' => self::CLE];
    }

    public function test_the_course_carries_its_own_customs_alerts_only(): void
    {
        $export = $this->colisConfieA($this->livreur);
        $autre = $this->colisConfieA($this->livreur, 'B');
        $alerte = $this->alerteSur($export, 'Certificat de circulation UEMOA', CustomsLevel::WARNING);
        $this->alerteSur($autre, 'Certificat phytosanitaire', CustomsLevel::BLOCKING);

        $reponse = $this->getJson('/api/v10/deliveryman/parcel/details/' . $export->id, $this->entetes())
            ->assertOk()
            ->assertJsonCount(1, 'data.customs_alerts')
            ->assertJsonPath('data.customs_alerts.0.id', $alerte->id)
            ->assertJsonPath('data.customs_alerts.0.parcel_id', $export->id)
            ->assertJsonPath('data.customs_alerts.0.level', CustomsLevel::WARNING)
            ->assertJsonPath('data.customs_alerts.0.required_document', 'Certificat de circulation UEMOA')
            ->assertJsonPath('data.customs_alerts.0.status', CustomsAlertStatus::PENDING);

        $this->assertSame(__('customs.level_2'), $reponse->json('data.customs_alerts.0.level_name'), 'libellé traduit, comme pour le marchand');
        $this->assertStringNotContainsString('phytosanitaire', $reponse->getContent(), 'l\'alerte de l\'autre course n\'y est pas');
    }

    public function test_a_domestic_course_has_an_empty_list_not_a_missing_key(): void
    {
        $domestique = $this->colisConfieA($this->livreur);

        $this->getJson('/api/v10/deliveryman/parcel/details/' . $domestique->id, $this->entetes())
            ->assertOk()
            ->assertJsonPath('data.customs_alerts', []);
    }

    /** Une course qui n'est pas la sienne reste un 404 : l'alerte ne fuit pas par ce chemin. */
    public function test_the_alert_of_a_colleagues_course_is_out_of_reach(): void
    {
        $collegue = $this->livreur('BB');
        $saCourse = $this->colisConfieA($collegue);
        $this->alerteSur($saCourse, 'Certificat NAFDAC', CustomsLevel::BLOCKING);

        $this->getJson('/api/v10/deliveryman/parcel/details/' . $saCourse->id, $this->entetes())->assertNotFound();
    }

    /** Le livreur lit : la route de traitement des alertes est marchande (S68), pas livreur. */
    public function test_a_courier_cannot_resolve_an_alert(): void
    {
        $alerte = $this->alerteSur($this->colisConfieA($this->livreur), 'Certificat de circulation UEMOA', CustomsLevel::WARNING);

        $this->putJson('/api/v10/customs/alerts/' . $alerte->id . '/resolve', [], $this->entetes())
            ->assertStatus(403);
        $this->assertSame(CustomsAlertStatus::PENDING, $alerte->fresh()->status);
    }

    /* ─────────────────────────── fixtures (TenantIsolationTest, S7) ─────── */

    private function livreur(string $suffixe): User
    {
        $user = $this->marchand->user->replicate();
        $user->name = 'Livreur ' . $suffixe;
        $user->email = 'livreur-' . $suffixe . '@example.test';
        $user->mobile = '0197060' . str_pad((string) strlen($suffixe), 3, '0', STR_PAD_LEFT);
        $user->unique_id = 'L-' . $suffixe;
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

    private function colisConfieA(User $livreur, string $suffixe = ''): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'hub_id' => $livreur->hub_id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_phone' => '0197070001',
            'customer_address' => 'Lomé',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => 'TEST-' . $livreur->unique_id . $suffixe . uniqid(),
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ])->save();

        $event = new ParcelEvent();
        $event->forceFill([
            'parcel_id' => $parcel->id,
            'delivery_man_id' => $livreur->deliveryman->id,
            'parcel_status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
            'created_by' => $livreur->id,
        ])->save();

        return $parcel;
    }

    private function alerteSur(Parcel $colis, string $document, int $niveau): CustomsAlert
    {
        return CustomsAlert::create([
            'company_id' => $colis->company_id,
            'merchant_id' => $colis->merchant_id,
            'parcel_id' => $colis->id,
            'country_code' => 'TG',
            'country_name' => 'Togo',
            'goods_category' => 'textile',
            'level' => $niveau,
            'required_document' => $document,
            'message' => 'Document obligatoire.',
            'status' => CustomsAlertStatus::PENDING,
        ]);
    }
}
