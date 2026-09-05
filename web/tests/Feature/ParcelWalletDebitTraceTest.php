<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * W5 — un débit de portefeuille en échec ne doit plus être avalé en silence.
 *
 * À la création d'un colis, le débit du portefeuille marchand était enveloppé
 * dans un `try { … } catch (\Throwable $th) { }` **vide**. Si le débit échouait,
 * le colis était créé, le marchand n'était pas facturé, et rien nulle part n'en
 * gardait la trace.
 *
 * Le colis reste créé — rendre le débit atomique avec la création change le
 * comportement et relève d'une décision à part — mais l'écart est désormais
 * journalisé, avec de quoi le rapprocher et le régulariser.
 */
class ParcelWalletDebitTraceTest extends TestCase
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
        // Le débit n'a lieu que si le marchand règle par portefeuille.
        $this->merchant->wallet_use_activation = Status::ACTIVE;
        $this->merchant->wallet_balance = 100000;
        $this->merchant->save();
    }

    private function creerUnColis()
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        return $this->postJson('/api/v10/parcel/store', [
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'weight' => 1,
            'shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000031',
            'customer_address' => 'Cotonou, Akpakpa',
        ], ['apiKey' => self::API_KEY]);
    }

    /** Le chemin nominal : le colis est créé et le portefeuille débité. */
    public function test_a_successful_debit_leaves_no_error(): void
    {
        Log::spy();

        $this->creerUnColis()->assertOk();

        $this->assertSame(1, Parcel::count());
        $this->assertLessThan(100000, (float) Merchant::find($this->merchant->id)->wallet_balance);

        Log::shouldNotHaveReceived('error');
    }

    /** Le débit échoue : le colis existe toujours, mais l'écart est journalisé. */
    public function test_a_failed_debit_is_logged_with_what_it_takes_to_reconcile(): void
    {
        Log::spy();

        // Le service de portefeuille tombe en panne au moment du débit.
        $this->app->bind(WalletInterface::class, function () {
            return new class implements WalletInterface {
                public function get($request = null) {}
                public function recharges($request = null) {}
                public function getFind($id) {}
                public function store($request) {}
                public function paymentStatus($orderId, $transactionId, $status) {}
                public function approved($id) {}
                public function rejected($id) {}
                public function expense($request)
                {
                    throw new \RuntimeException('base indisponible');
                }
                public function adminstore($request) {}
                public function delete($id) {}
            };
        });

        $this->creerUnColis()->assertOk();

        $colis = Parcel::firstOrFail();

        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $contexte) use ($colis) {
                return str_contains((string) $message, 'Debit du portefeuille en echec')
                    && (int) $contexte['parcel_id'] === (int) $colis->id
                    && $contexte['tracking_id'] === $colis->tracking_id
                    && (int) $contexte['merchant_id'] === (int) $this->merchant->id
                    && (float) $contexte['amount'] === (float) $colis->total_delivery_amount
                    && str_contains((string) $contexte['message'], 'base indisponible');
            })
            ->once();
    }
}
